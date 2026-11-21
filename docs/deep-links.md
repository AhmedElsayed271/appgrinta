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
| `/screen/{id}` | Share page for a screen number (`0` to `4`) with OG meta. |

> These routes are outside the localization group, so there is no `/en/...` or `/ar/...` prefix.

### Verification files
- `https://appgrinta.com/.well-known/assetlinks.json` — contains both Android SHA-256 fingerprints.
- `https://appgrinta.com/.well-known/apple-app-site-association` — declares `/l/*`, `/post/*`, `/league/*`, `/match/*`, `/team/*`, and `/screen/*`.
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

{"type": "match", "id": 8123, "football_id": 1367248, "screen": "2"}
```

| Body field | Required | Notes |
|---|---|---|
| `type` | yes | One of `post`, `league`, `match`, `team`, `screen`. |
| `id` | one of `id` / `football_id` | Our internal database id. For `screen`, this is the screen number (`0`–`4`). |
| `football_id` | one of `id` / `football_id` | The id returned by the Football API: `fixture_id` for a match, `team_id` for a team. Ignored for other types. |
| `screen` | no | `"0"` to `"4"`; integer `0` to `4` is also accepted. Stored on the link and returned in every response. Omit it to get `screen: null`. A value outside `0`–`4` returns 422. |

At least one of `id` or `football_id` is required; otherwise the API returns 422. When both are sent, the lookup is a single `where <local table>.id = id OR where <external column> = football_id` query. If the two values point to different rows, the database decides which one comes back first.

| `type` | External column for `football_id` | Notes |
|---|---|---|
| `post` | none | Only `id` is used. Scheduled posts are rejected with 404. |
| `league` | none | Only `id` is used. |
| `match` | `fixture_id` | Both identifiers resolve to the same code. The response stores the internal id in `target_id`; `fixture_id` is returned as a helper. |
| `team` | `team_id` | Both identifiers resolve to the same code. |
| `screen` | none | `id` is the screen number (`0`–`4`). Labels are defined in `config/deeplinks.php`. |

Screen labels, used as the title/description of a `screen` link, are defined in `config/deeplinks.php` and can be renamed there:

| Screen | Label |
|---|---|
| `0` | Screen 0 |
| `1` | League / team screen |
| `2` | Match lineup tab |
| `3` | Screen 3 |
| `4` | Screen 4 |

`screen` is **caller-supplied**, not derived from `target_type`. The server stores exactly what you sent and returns it on `resolve`, `click` and `claim`. If you omit it, the `screen` key is still present with a `null` value. `screen` is both a valid link type **and** a fixed key in every response.

The same `target_type` + `target_id` + `screen` always returns the same code. A different `screen` for the same target returns a **different** code.

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
    "target_type": "match",
    "target_id": 8123,
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
`code`, `claim_code`, `target_type`, `target_id`, `screen`, `title`, `description`, `image`, `path`, `web_url`, `share_url`, `app_url`, `play_url`, `app_store_url`.

`target_type` and `target_id` are the original contract and are always present. `target_id` is the internal database id, except for a `screen` link where it is the screen number.

`screen` is a **fixed key in every response**. Its value is exactly what the caller sent in the create body (`"0"` to `"4"`), or `null` when the field was omitted. It is never inferred from `target_type`.

#### Additional keys by type (additive helpers)

| Created with | `screen` value | Additional keys |
|---|---|---|
| `post` | caller-supplied or `null` | none |
| `league` | caller-supplied or `null` | none |
| `team` | caller-supplied or `null` | `team_id` (external Football-API id) |
| `match` | caller-supplied or `null` | `fixture_id`, `home_team_id`, `away_team_id`, `competition_id`, `match_date` (ISO-8601), `status` |
| `screen` | caller-supplied or `null` | none |

Keys that would merely repeat `target_id` (`post_id`, `match_id`, and `competition_id` on a league link) are **not returned**. Only ids that differ from `target_id` are kept as helpers: the external `team_id`, and the match-related `fixture_id`, `home_team_id`, `away_team_id`, `competition_id`, `match_date` and `status`. Non-applicable keys are **omitted**, never returned as `null`. Only `screen` is always present, and it may be `null`.

`path` is `/post/{id}`, `/league/{id}`, `/match/{id}`, `/team/{id}` or `/screen/{id}` (the internal database id). `web_url` and `share_url` are always `/l/{code}`.

#### For the mobile app
The original navigation contract still works: read `target_type` and `target_id`. For a match link, `target_id` **is the internal match id** (the old `match_id`); there is no separate `match_id` key. Use `fixture_id` only when you need the external id. When creating a match or team link you may send `id`, `football_id`, or both; they are combined in one OR query and resolve to the same code. `id` is always our local id. The `screen` key and the extra ids (`team_id`, `fixture_id`, `home_team_id`, `away_team_id`, `competition_id` on a match link) are helpers and can be ignored if not needed. `type: "screen"` is still accepted.

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
    <data android:scheme="https" android:host="appgrinta.com" android:pathPrefix="/screen" />
</intent-filter>
```

In `onCreate` / `onNewIntent`:
- `/l/{code}` → call `deeplinks/claim` or `deeplinks/resolve` with the code, then navigate using `target_type` + `target_id` (or the extra keys).
- `/post/{id}`, `/league/{id}`, `/match/{id}`, `/team/{id}` → open the screen directly using the id.
- `/screen/{id}` → open the screen number directly (`0` to `4`; see the label table above).

Navigation rule for the resolve/claim response (same priority as push notifications):

| Keys present | Open |
|---|---|
| `target_type == "post"` | post detail (`target_id`) |
| `target_type == "league"` | league screen (`target_id`) |
| `target_type == "team"` | team screen (`target_id`, or `team_id` for the external id) |
| `target_type == "match"` | match detail / lineup tab (`target_id`, plus `fixture_id` as fallback) |
| `target_type == "screen"` | screen `target_id` (`0` to `4`); the `screen` key matches it when supplied |
| `fixture_id` | match detail by external fixture id (fallback) |

`screen` may be `null` when the creator omitted it. In that case navigate from `target_type` / `target_id` or the concrete id keys.

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

For `/post/{id}`, `/league/{id}`, `/match/{id}`, `/team/{id}` and `/screen/{id}` there is no code in the path: read the first path component and the id from `url.pathComponents` and navigate directly using the table in the Android section (the same logic applies on iOS). For `/screen/{id}`, the id is the screen number (`0` to `4`).

### B) Manual claim code (deferred)
The landing page shows `GR-XXXXXXXX`. On the first run, ask the user to enter or paste the code, then call:
```http
POST /api/v1/deeplinks/claim
{"code": "GR-XXXXXXXX"}
```
Then open the screen from `target_type` + `target_id` (or the extra `screen` / id keys).

## 5) Pre-release checklist
- [ ] `curl -I https://appgrinta.com/.well-known/apple-app-site-association` returns `200` and `application/json`.
- [ ] `curl https://appgrinta.com/.well-known/assetlinks.json` contains both fingerprints.
- [ ] Opening `/l/GR-...` on Android hands off to the installed app.
- [ ] Installing from Play and opening the app for the first time lands on the shared content.
- [ ] Entering an iOS claim code manually lands on the shared content.
- [ ] Sharing a link on Twitter/WhatsApp shows the OG image.
