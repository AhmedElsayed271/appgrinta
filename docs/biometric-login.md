# Biometric (Fingerprint / Face ID) Login — API Documentation

Opt-in login feature for the Grinta mobile app. After enabling it once, the user can sign in by authenticating on their device (fingerprint / face) instead of typing credentials again.

## How it works (one paragraph)

A logged-in user enables biometric login by calling `toggle`. The server responds with a one-time secret `biometric_token`, which the app stores in the device's secure, biometric-protected storage (Android Keystore / iOS Keychain). On later app launches, the app prompts the device biometric, then exchanges the stored `biometric_token` for a fresh `api_token` session.

---

## Endpoints

### 1. Enable / Disable biometric login

```
POST {{base_url}}/biometric/toggle
Authorization: Bearer <api_token>
```

Request body:

```json
{
  "enabled": true
}
```

#### Enable (`enabled: true`) — 200 OK
Server generates a new `biometric_token` and only enables it once.

```json
{
  "isSuccess": true,
  "message": "success",
  "additionalInfo": null,
  "data": {
    "biometric_enabled": true,
    "biometric_token": "Zgd71wUjCbN1cHxsuUfoxxkzSse9k1cjkv1jyMV6EVDvupizOH2YP3BO72Aq",
    "client": {
      "id": 2909,
      "full_name": "Ahmed",
      "email": "user@example.com",
      "biometric_enabled": true,
      "...": "..."
    }
  },
  "error": null,
  "code": 200
}
```

> `biometric_token` is returned **only here, once**. Keep it in secure storage. It is never returned inside the `client` object.

#### Disable (`enabled: false`) — 200 OK

```json
{
  "isSuccess": true,
  "message": "success",
  "additionalInfo": null,
  "data": {
    "biometric_enabled": false,
    "client": { "...": "..." }
  },
  "error": null,
  "code": 200
}
```

Disabling clears the token on the server and makes every previously issued token invalid.

---

### 2. Biometric login

```
POST {{base_url}}/biometric/login
```

Public endpoint (no Bearer required).

Request body:

```json
{
  "biometric_token": "<token returned by toggle>",
  "fb_token": "expo_push_token_here",
  "locale": "en"
}
```

- `biometric_token` — **required**
- `fb_token` — optional; stored for push notifications if provided
- `locale` — optional, accepts `en` / `ar` (defaults to `en`)

#### Response — 200 OK

```json
{
  "isSuccess": true,
  "message": "success",
  "additionalInfo": null,
  "data": {
    "token": "2026-09-26 20:49:22AbCdEfGhIkLmNoPqRsTuVwXyZ0123456789abcdefghijklmnopqrstuvwxyz",
    "biometric_token": "pNfQ2e1T5QaBcDeFgHiJkLmNoPqRsTuVwXyZ0123456789",
    "client": { "...": "..." }
  },
  "error": null,
  "code": 200
}
```

- `token` — the new `api_token`. Use it as `Authorization: Bearer <token>` for all protected calls.
- `biometric_token` — **new** token. The app **must** replace the stored one with this value (see Rotation below).

#### Errors
| HTTP | Body `message` | Cause |
|---|---|---|
| 400 | `Invalid biometric token` | Token not recognized or biometric login is disabled |
| 422 | Validation errors | Missing/invalid `biometric_token` |
| 401 | — | (toggle only) Missing/invalid `Authorization` header |

---

## End-to-end flows

### Enable (first time)
```
1. User logs in normally (login / social / google)  -> save api_token
2. POST /biometric/toggle { enabled: true }         -> save biometric_token (secure storage)
```

### Login with biometric (subsequent launches)
```
1. GET /client/profile -> check biometric_enabled == true (optional, for UI)
2. Prompt device biometric (BiometricPrompt / Face ID / Touch ID)
3. On success: POST /biometric/login { biometric_token }
4. Store the NEW biometric_token returned in data
5. Use data.token as the new api_token
```

### Disable
```
POST /biometric/toggle { enabled: false }   -> server clears token
Also delete the stored biometric_token from the device
```

---

## Security model

- **Server stores only a hash** — the database keeps `sha256(biometric_token)`, never the raw secret. A DB leak does not expose usable tokens.
- **Token rotation** — every successful `biometric/login` issues a new `biometric_token`. The previously stored token is permanently invalid.
- **api_token rotation** — every successful login (biometric or normal) issues a new `api_token`; old sessions cannot be replayed.
- **No leakage in responses** — `biometric_token` is hidden from model serialization (e.g. inside `client`).
- **Disabled means dead** — while `biometric_enabled = false`, all biometric logins are rejected with `400`.

---

## Mobile app requirements

| Platform | Secure storage | Biometric check |
|---|---|---|
| Android | Android Keystore (key protected with `setUserAuthenticationRequired(true)`) | `BiometricPrompt` |
| iOS | Keychain with `kSecAccessControlBiometryCurrentSet` | `LAContext` (Face ID / Touch ID) |

Guidelines for the mobile developer:

1. Only call `toggle { enabled: true }` **after** the user has successfully completed the device biometric prompt (to avoid enabling from a stolen unlocked device).
2. Persist the `biometric_token` inside the same biometric-protected key — it should not be readable without a fresh fingerprint/Face ID.
3. After every `biometric/login`, immediately overwrite the stored `biometric_token` with the new one from the response (rotation).
4. If `biometric/login` returns `400` (e.g. token expired because the app failed to persist the rotated value), fall back to normal login once — do **not** loop on the biometric prompt.
5. When the user toggles the feature off, clear the stored token from secure storage as well as calling `toggle { enabled: false }`.
6. Do **not** store the `biometric_token` or `api_token` in plain SharedPreferences / NSUserDefaults.

---

## Testing checklist (server side, local)

| Scenario | Expected |
|---|---|
| `toggle { enabled: true }` with valid Bearer | `200`, returns `biometric_token` |
| DB stores `sha256(token)` (not raw) | verified |
| `biometric/login` with the token | `200`, returns new `api_token` + new `biometric_token` |
| Reusing the **old** `biometric_token` after a login | `400` |
| Login with the **new** `biometric_token` | `200` |
| Random/garbage token | `400` |
| `toggle { enabled: false }` then login | `400` |
| `toggle` without Bearer | `401` |
| `client/profile` shows `biometric_enabled` | present, no `biometric_token` field |