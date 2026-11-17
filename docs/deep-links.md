# Grinta — Self-hosted Dynamic Links

A self-hosted replacement for Firebase Dynamic Links. No third-party link service is used. Domain in use: `https://appgrinta.com`.

## 1) What the server provides

### Web pages
| Path | Purpose |
|---|---|
| `/l/GR-XXXXXXXX` | Landing page for a short link. Contains OG meta, an "open in app" button, a Google Play button, and the claim code for iOS. |
| `/post/{id}` | Share page for a post with OG meta. |
| `/league/{id}` | Share page for a league with OG meta. |

> These routes are outside the localization group, so there is no `/en/...` or `/ar/...` prefix.

### Verification files
- `https://appgrinta.com/.well-known/assetlinks.json` — contains both Android SHA-256 fingerprints.
- `https://appgrinta.com/.well-known/apple-app-site-association` — declares `/l/*`, `/post/*`, and `/league/*`.
- Both must return `200` with no redirect and `Content-Type: application/json`.

### `.env` variables
```env
DEEPLINK_WEB_URL=https://appgrinta.com
DEEPLINK_PLAY_STORE_URL=https://play.google.com/store/apps/details?id=com.muhmmad.grinta
DEEPLINK_APP_STORE_URL=        # iOS URL is not available yet; currently a temporary Android URL
DEEPLINK_APP_SCHEME=grinta
DEEPLINK_ANDROID_PACKAGE=com.muhmmad.grinta
DEEPLINK_IOS_APP_ID=6RPWXA5835.elsawy.Grinta
DEEPLINK_CLAIM_LIMIT=10
DEEPLINK_CREATE_LIMIT=30
```

> While `DEEPLINK_APP_STORE_URL` still points to Google Play, the App Store button is hidden so iOS users are not sent to the wrong store. As soon as the real iOS URL is set, the button appears automatically.

## 2) API

All endpoints are under `https://appgrinta.com/api/v1` and do **not** require `auth:api`.

### Create or reuse a code
```http
POST /api/v1/deeplinks
Content-Type: application/json

{"type": "post", "id": 123}
```
`type` is either `post` or `league`. The same content always returns the same code. Scheduled posts (`published_at` in the future) are rejected with 404.

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

### Example response
```json
{
  "isSuccess": true,
  "message": "success",
  "additionalInfo": null,
  "data": {
    "code": "GR-AB12CD34",
    "claim_code": "GR-AB12CD34",
    "target_type": "post",
    "target_id": 123,
    "title": "Post title",
    "description": "Post description",
    "image": "https://appgrinta.com/storage/uploads/post_images/...",
    "path": "/l/GR-AB12CD34",
    "web_url": "https://appgrinta.com/l/GR-AB12CD34",
    "share_url": "https://appgrinta.com/l/GR-AB12CD34",
    "app_url": "grinta://open?code=GR-AB12CD34",
    "play_url": "https://play.google.com/store/apps/details?id=com.muhmmad.grinta&referrer=dl%3DGR-AB12CD34",
    "app_store_url": null
  },
  "error": null,
  "code": 200
}
```

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
</intent-filter>
```

In `onCreate` / `onNewIntent`:
- `/l/{code}` → call `deeplinks/claim` or `deeplinks/resolve` with the code, then navigate using `target_type` + `target_id`.
- `/post/{id}` and `/league/{id}` → open the screen directly using the id.

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

### B) Manual claim code (deferred)
The landing page shows `GR-XXXXXXXX`. On the first run, ask the user to enter or paste the code, then call:
```http
POST /api/v1/deeplinks/claim
{"code": "GR-XXXXXXXX"}
```
Then open the screen from `target_type` / `target_id`.

## 5) Pre-release checklist
- [ ] Replace `DEEPLINK_APP_STORE_URL` with the real App Store URL.
- [ ] `curl -I https://appgrinta.com/.well-known/apple-app-site-association` returns `200` and `application/json`.
- [ ] `curl https://appgrinta.com/.well-known/assetlinks.json` contains both fingerprints.
- [ ] Opening `/l/GR-...` on Android hands off to the installed app.
- [ ] Installing from Play and opening the app for the first time lands on the shared content.
- [ ] Entering an iOS claim code manually lands on the shared content.
- [ ] Sharing a link on Twitter/WhatsApp shows the OG image.
