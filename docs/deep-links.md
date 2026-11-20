# Grinta — Self-hosted Dynamic Links

A self-hosted replacement for Firebase Dynamic Links. No third-party link service is used. Domain in use: `https://appgrinta.com`.

## 1) What the server provides

### Web pages
| Path | Purpose |
|---|---|
| `/l/GR-XXXXXXXX` | Landing page for a short link. Contains OG meta, an "open in app" button, a Google Play button, and the claim code for iOS. |
| `/post/{id}` | Share page for a post with OG meta. |
| `/league/{id}` | Share page for a league with OG meta. |
| `/match/{id}` | Share page for a match (database id) with OG meta. |
| `/team/{id}` | Share page for a team (database id) with OG meta. |

> These routes are outside the localization group, so there is no `/en/...` or `/ar/...` prefix.

### Verification files
- `https://appgrinta.com/.well-known/assetlinks.json` — contains both Android SHA-256 fingerprints.
- `https://appgrinta.com/.well-known/apple-app-site-association` — declares `/l/*`, `/post/*`, `/league/*`, `/match/*`, and `/team/*`.
- Both must return `200` with no redirect and `Content-Type: application/json`.

### `.env` variables
```env
DEEPLINK_WEB_URL=https://appgrinta.com
DEEPLINK_PLAY_STORE_URL=https://play.google.com/store/apps/details?id=com.muhmmad.grinta
DEEPLINK_APP_STORE_URL=https://apps.apple.com/app/id6443478180
DEEPLINK_APP_SCHEME=grinta
DEEPLINK_ANDROID_PACKAGE=com.muhmmad.grinta
DEEPLINK_IOS_APP_ID=6RPWXA5835.elsawy.Grinta
DEEPLINK_CLAIM_LIMIT=10
DEEPLINK_CREATE_LIMIT=30
```

> While `DEEPLINK_APP_STORE_URL` points to Google Play, the App Store button is hidden so iOS users are not sent to the wrong store. The real App Store URL is now configured, so the button appears automatically.

## 2) API

All endpoints are under `https://appgrinta.com/api/v1` and do **not** require `auth:api`.

### Create or reuse a code
```http
POST /api/v1/deeplinks
Content-Type: application/json

{"type": "match", "id": 8123}
```

| `type` | `id` means | Notes |
|---|---|---|
| `post` | post database id | Scheduled posts (`published_at` in the future) are rejected with 404. |
| `league` | competition database id | |
| `match` | match database id | Also exposes `fixture_id`, team ids, date and status. |
| `team` | team database id | The external Football-API `team_id` is accepted too and reuses the same code. |

`screen` is **not** a link type. It is a navigation field inside the response, following the push-notification contract. Sending `{"type": "screen"}` returns 422.

The same content always returns the same code.

### Resolve a code
```http
GET /api/v1/deeplinks/resolve?code=GR-XXXXXXXX
```

### Record an open (optional analytics)
```http
POST /api/v1/deeplinks/click
{"code": "GR-XXXXXXXX"}
```

### Claim an iOS manual code
```http
POST /api/v1/deeplinks/claim
{"code": "GR-XXXXXXXX"}
```
Limited to 10 requests per minute per IP. A code is never consumed, so multiple devices can use the same share link.

### Response shape

```json
{
  "isSuccess": true,
  "message": "success",
  "additionalInfo": null,
  "data": {
    "code": "GR-CD12EF34",
    "claim_code": "GR-CD12EF34",
    "title": "Home FC vs Away FC",
    "description": "Grinta - Home FC vs Away FC - 2026-11-21 20:00",
    "image": "https://appgrinta.com/storage/uploads/default.png",
    "path": "/match/8123",
    "web_url": "https://appgrinta.com/l/GR-CD12EF34",
    "share_url": "https://appgrinta.com/l/GR-CD12EF34",
    "app_url": "grinta://open?code=GR-CD12EF34",
    "play_url": "https://play.google.com/store/apps/details?id=com.muhmmad.grinta&referrer=dl%3DGR-CD12EF34",
    "app_store_url": "https://apps.apple.com/app/id6443478180",
    "screen": "2",
    "match_id": 8123,
    "fixture_id": 778899,
    "home_team_id": 9001,
    "away_team_id": 9002,
    "competition_id": 15,
    "match_date": "2026-11-21T20:00:00+00:00",
    "status": "pending"
  },
  "error": null,
  "code": 200
}
```

#### Common keys (every response)
`code`, `claim_code`, `title`, `description`, `image`, `path`, `web_url`, `share_url`, `app_url`, `play_url`, `app_store_url`.

#### Navigation keys (added on top)

| Created with | `screen` | Additional keys |
|---|---|---|
| `post` | omitted | `post_id` |
| `league` | `"1"` | `competition_id` |
| `team` | `"1"` | `team_id` (external Football-API id) |
| `match` | `"2"` | `match_id`, `fixture_id`, `home_team_id`, `away_team_id`, `competition_id`, `match_date` (ISO-8601), `status` |

Keys that do not apply to the type are **omitted**, never returned as `null`. For example, a team link has no `match_id` or `fixture_id`, and a post link has no `screen`.

`path` is `/post/{id}`, `/league/{id}`, `/match/{id}` or `/team/{id}` (the internal database id). `web_url` and `share_url` are always `/l/{code}`.

#### Breaking change for the mobile app
The following keys were **removed** from `data`:

- `target_type`
- `target_id`
- `screen` as a link type (`type: "screen"` is no longer accepted)

Use `screen` plus the concrete id keys (`post_id`, `competition_id`, `team_id`, `match_id`, `fixture_id`) instead.

The target is always read from the database. Any `redirect` query parameter is ignored, so the endpoint cannot be used as an open redirect.

## 3) Android

### A) App Links
In `AndroidManifest.xml`, inside the activity that handles links:

```xml
<intent-filter android:autoVerify="true">
    <action android:name="android.intent.action.VIEW" />
    <category android:name="android.intent.category.DEFAULT" />
    <category android:name="android.intent.category.BROWSABLE" />
    <data android:scheme="https" android:host="appgrinta.com" android:pathPrefix="/l" />
    <data android:scheme="https" android:host="appgrinta.com" android:pathPrefix="/post" />
    <data android:scheme="https" android:host="appgrinta.com" android:pathPrefix="/league" />
    <data android:scheme="https" android:host="appgrinta.com" android:pathPrefix="/match" />
    <data android:scheme="https" android:host="appgrinta.com" android:pathPrefix="/team" />
</intent-filter>
```

In `onCreate` / `onNewIntent`:
- `/l/{code}` → call `deeplinks/claim` or `deeplinks/resolve` with the code, then navigate using `screen` + the concrete id keys.
- `/post/{id}`, `/league/{id}`, `/match/{id}`, `/team/{id}` → open the screen directly using the id.

Navigation rule for the resolve/claim response (same priority as push notifications):

| Keys present | Open |
|---|---|
| `post_id` | post detail |
| `screen == "1"` + `competition_id` | league screen |
| `screen == "1"` + `team_id` | team screen |
| `match_id` (+ `screen == "2"`) | match detail / lineup tab |
| `fixture_id` | match detail by external fixture id (fallback) |

Quick check:
```bash
adb shell am start -a android.intent.action.VIEW \
  -d "https://appgrinta.com/l/GR-AB12CD34" com.muhmmad.grinta
```

### B) Deferred deep link after install
The Google Play button on the landing page appends:
```
referrer=dl%3DGR-AB12CD34
```

After the first launch, read the Play Install Referrer and send the code to the API:

```kotlin
val client = InstallReferrerClient.newBuilder(context).build()
client.startConnection(object : InstallReferrerStateListener {
    override fun onInstallReferrerSetupFinished(code: Int) {
        if (code != InstallReferrerClient.InstallReferrerResponse.OK) return
        val raw = client.installReferrer?.installReferrer.orEmpty()
        val value = URLDecoder.decode(raw, "UTF-8")
        val linkCode = value.split("&")
            .firstOrNull { it.startsWith("dl=") }
            ?.removePrefix("dl=")
            ?.takeIf { it.isNotBlank() }
        if (linkCode != null) claimCode(linkCode) // POST /deeplinks/claim
    }

    override fun onInstallReferrerServiceDisconnected() {}
})
```

## 4) iOS

### A) Universal Links
In the entitlements:
```
applinks:appgrinta.com
```

In `SceneDelegate`:
```swift
func scene(_ scene: UIScene, continue userActivity: NSUserActivity) {
    guard userActivity.activityType == NSUserActivityTypeBrowsingWeb,
          let url = userActivity.webpageURL else { return }
    // /l/GR-XXXXXXXX
    if let code = url.pathComponents.dropFirst().first(where: { $0.hasPrefix("GR-") }) {
        claim(code) // POST /deeplinks/claim
    }
}
```

The Play `dl=` referrer is Android-specific; iOS uses the manual claim code.

For `/post/{id}`, `/league/{id}`, `/match/{id}` and `/team/{id}` there is no code in the path: read the first path component and the id from `url.pathComponents` and navigate directly using the table in the Android section (the same logic applies on iOS).

### B) Manual claim code (deferred)
The landing page shows `GR-XXXXXXXX`. On the first run, ask the user to enter or paste the code, then call:
```http
POST /api/v1/deeplinks/claim
{"code": "GR-XXXXXXXX"}
```
Then open the screen from `screen` + the concrete id keys.

## 5) Pre-release checklist
- [ ] `curl -I https://appgrinta.com/.well-known/apple-app-site-association` returns `200` and `application/json`.
- [ ] `curl https://appgrinta.com/.well-known/assetlinks.json` contains both fingerprints.
- [ ] Opening `/l/GR-...` on Android hands off to the installed app.
- [ ] Installing from Play and opening the app for the first time lands on the shared content.
- [ ] Entering an iOS claim code manually lands on the shared content.
- [ ] Sharing a link on Twitter/WhatsApp shows the OG image.
