# Grinta — Project Map

> Whole-project reference (dashboard + API + data + notifications). This is a **living map** to make future work faster. Keep it updated when structure changes.
>
> Generated from a full scan of `C:\laragon\www\appgrinta_1`. Laravel **12.x** on a **classic skeleton** (`bootstrap/app.php` + `App\Http\Kernel`).

---

## 1. Stack & runtime

| Item | Value |
|------|-------|
| Framework | `laravel/framework ^12.0` (running **12.62.0**), classic skeleton |
| PHP | `^8.2` — use `C:\laragon\bin\php\php-8.2.20-nts-Win32-vs16-x64\php.exe` (default 8.1 fails the platform check) |
| DB | MySQL — local `appgrint_grinta` @ `127.0.0.1:3306`, root / no password |
| Cache / Queue | `QUEUE_CONNECTION=sync` (jobs run in-process via `->afterResponse()`), file cache |
| Localization | `mcamara/laravel-localization ^2.0` + `astrotomic/laravel-translatable ^11.14` |
| Other | `google/apiclient` (FCM OAuth), `guzzlehttp/guzzle`, `intervention/image-laravel`, `stichoza/google-translate-php` (dashboard translate) |

**Site URLs (local)**
- App root: `http://appgrinta_1.test`
- API base: `http://appgrinta_1.test/api/v1` (header `Authorization: Bearer <api_token>`)
- Dashboard: `http://appgrinta_1.test/{locale}/dashboard` (e.g. `/en/dashboard`, `/ar/dashboard`)

**Commands cheat-sheet**
```powershell
# Always call the 8.2 binary explicitly
& "C:\laragon\bin\php\php-8.2.20-nts-Win32-vs16-x64\php.exe" artisan route:list
& "C:\laragon\bin\php\php-8.2.20-nts-Win32-vs16-x64\php.exe" artisan schedule:list
```
> `route:list --columns` is **not** supported on this skeleton. `composer dump-autoload` / `require` can hang locally — use manual PSR-4 edits as a workaround.

---

## 2. Directory structure (app layer)

```
app/
├── Console/
│   ├── Kernel.php                 # command registry + full schedule
│   └── Commands/                  # 21 Artisan commands (see §7)
├── Http/
│   ├── Controllers/
│   │   ├── Api/V1/                # 13 public mobile-API controllers (see §4)
│   │   ├── Dashboard/             # 17 admin controllers (see §5)
│   │   ├── Auth/                  # classic Auth scaffolding (web login)
│   │   ├── GetDataFromFootBallApi.php  # legacy update endpoints (dashboard settings)
│   │   └── HomeController.php
│   ├── Middleware/                # LocaleMiddleware + framework middleware
│   └── Kernel.php                 # global/group/route middleware
├── Models/                        # 31 Eloquent models (see §6)
├── Services/                      # FirebaseService, NotificationPersistence, PostNotificationService, TranslationService
├── Traits/                        # Notify, NotificationOfMatchesTrait, pusher, roles, responses, tables
├── Jobs/                          # SendFirebaseNotifications, etc.
├── Providers/                     # RouteServiceProvider (route families), AppServiceProvider
└── resources/                     # Blade views for the dashboard, lang files
```

**Route families** (registered in `app/Providers/RouteServiceProvider.php`):
1. `api/v1` → `routes/api/v1.php` (middleware `api`, guard `clients`)
2. `{locale}/dashboard` → `routes/dashboard/web.php` (middleware `web`, guard `users`)
3. root → `routes/web.php` (public/auth pages + a few test endpoints)

Route pattern: `Route::pattern('locale', '[a-zA-Z]{2}(-[a-zA-Z]{2})?')`. **No `routes/api.php`** — API routes live in `routes/api/v1.php`. Total≈**199 routes**.

---

## 3. Authentication

`config/auth.php`:

| Guard | Driver | Provider | Table | Used by |
|-------|--------|----------|-------|---------|
| `web` (default) | session | `users` | `users` | Dashboard + web login |
| `api` | **token** | `clients` | `clients` | Mobile app (`api_token` column) |

- Mobile sends `Authorization: Bearer <clients.api_token>`. Demo token: `mobile-demo-test-token-1234567890`.
- Rate limit: `throttle:api` = `60/min` per client id (or IP).
- **Dashboard has NO dedicated admin guard** — it uses the default `web` guard (`users`); admin flag is the manual `users.is_admin` column.
- `LocaleMiddleware` sets the request locale from `?locale=` (or session). Dashboard routes are localized via `mcamara`.

---

## 4. API layer — `app/Http/Controllers/Api/V1/`

Base prefix `api/v1`. Standard envelope: `{ isSuccess, message, additionalInfo, data, error, code }`.

| Controller | Highlights |
|------------|-----------|
| `AuthController` | `login`, `register`, `social`, `google`, `logout`, `deleteAccount`, `toggleBiometric`, `biometricLogin`, `verifiedEmail` |
| `ClientController` | profile, favourite teams/competitions (add/remove), locale update, `getToken`, `changePassword` |
| `ClientNotificationsController` | the in-app bell: `index`, `unreadCount`, `read`, `readAll`, `destroy` |
| `NotificationController` | legacy `daily` / `weekly` (and `match_post` — ⚠ see open issues) |
| `MatchesController` | `index`, `show` (`MatchResource`), `events` |
| `TeamsController` | list/show, posts, competitions, matches, standings, confrontations, search |
| `CompetitionsController` | show, child, posts, teams, points, matches |
| `PlayersController` | index, `allpl`, show, statistic |
| `PostsController` | index, `allPosts`, `sendPosts`, `featuredPosts`, `searchPosts`, `show`, `react` (reactions) |
| `CategoriesController`, `CountriesController` | lookup lists |
| `SettingController` | app settings |
| `ClientNotificationsController` | the in-app bell |

**Public (no auth):** `login`, `register`, `social`, `google`, `biometric/login`, `verify_email`, `countries*`, `notification/daily/{id}`, `notification/weekly/{id}`, `setting`, `sent_token_to_email`, `change_password`.
**Protected (`auth:api`)**: everything under `client/*`, posts, teams, players, matches, competitions, categories, notifications bell, favourites.

**Match API** (`{match}` = internal `matches.id`, not the football-API fixture id):
- `GET /api/v1/matches/{match}` → `MatchResource`
- `GET /api/v1/matches/{match}/events`

Resources/transformers live under `app/Http/Resources/`.

---

## 5. Dashboard layer — `app/Http/Controllers/Dashboard/`

Mounted at `{locale}/dashboard`, names prefixed `dashboard.`. Uses the `web` (session/users) guard.

| Controller | Routes / role |
|------------|---------------|
| `WelcomeController` | dashboard home / stats |
| `UsersController`, `RolesController` | users + role/permission admin |
| `NotificationsController` | manual push senders: `post`, `match`, `url` (announcement), `team`, `competition` (+ `_post` handlers) |
| `CategoriesController`, `CountriesController`, `CompetitionsController` | CRUD resources |
| `TeamsController`, `PlayersController`, `ClientsController` | CRUD resources |
| `MatchesController` + `MatchEventsController` | nested resource `matches.matches.events` |
| `PostsController` | resource + bulk (`assignAll`, `featureAll`, `destroyAll`, `changePostSort`) + `updatePostData` |
| `SettingsController` | settings + `updateLeaguesAndSeason` |
| `TranslateController` | Google-translate helper |
| `CkeditorController` | image upload/delete |
| `CallApiController` | football-API import helpers (`add_football_team_id`, `teamsWithLeague`, `playerWithTeams`) |

Legacy: `GetDataFromFootBallApi` backs `settings/update-in-minute/{minute}`, `settings/start-update`, `settings/stop-update`.

> Dashboard controllers share functionality via traits `TablesQuery`, `CustomResponser`, `HasRolesAndPermissions` rather than a base CRUD controller.

---

## 5b. Web routes (`routes/web.php`)

Root `/` (`HomeController@index` = `home`), classic `login/register/logout/password.*`, plus helper/test endpoints: `cache-all`, `send-test-email`, `test-matches`, `test-notification`, `time_now`, `privacyPolicy`, `termsAndConditions`.

---

## 6. Data layer — 31 models / 56 tables

### Models (`app/Models/`)
`User, Client, Guest, Enter`, `Category(+Translation)`, `CountryTranslation`/`Country`, `Competition(+Translation)`, `Team`/`TeamTranslation`, `Player`/`PlayerTranslation`, `PlayerStatistic`, `Matche` (+`MatcheTranslation`), `MatchEvent` + `MatchEventTranslation`, `Post` + `PostTranslation`, `PostReaction`, `Point`, `Notification` + `NotificationTranslation`, `Setting` + `SettingTranslation`, `Role`, `Permission`, `FirstMatchesOfRounds`, `testtest`.

- **11 translatable models** (Astrotomic) — those with a `*Translation` model: Category, Competition, Country, Matche, MatchEvent, Notification, Player, Post, Setting, Team (+ Set user-facing).
- Tables **without** models: `password_resets`, `failed_jobs`, `jobs`, `daily_matches`, `weekly_matches`.
- Model naming is legacy: `Matche` (table `matches`), `FirstMatchesOfRounds`, `Enter`.

**Columns added manually to the live DB (not in migrations)** — beware when rebuilding:
`users.is_admin`, `posts.featured`, `posts.sort`, `competitions.league_id`, `competitions.sort`, `teams.team_id`, `teams.sort`, `matches.fixture_id`, `matches.elapsed`, `players.player_id`, `settings.football_key`, `settings.leagues`, `settings.season`.

**`testtest`** model has **no migration** — a manual single-row table with `enable` + `minute`, used as a runtime toggle (local = `0`).

---

## 6b. Migrations (`database/migrations/`, 57 files)

Grouped by area:
- **Auth/RBAC:** `users`, `password_resets`, `roles`, `permissions`, `roles_permissions`, `users_roles`, `users_permissions`, `failed_jobs`, `jobs`.
- **Content:** `posts` (+ `add_user_id`, `add_youtube_link`, `add_scheduling`), `post_translations`, `post_reactions`, `post_team`, `post_competition`, `categories`, `category_translations`.
- **Football:** `countries`, `competitions` (+ `children/parent`), `competition_translations`, `competition_team`, `teams`, `team_translations`, `players`, `player_translations`, `player_statistics`, `matches` (+ remove channel), `match_translations`, `match_events` (+ `event_type`), `match_event_translations`, `points`, `daily_matches`, `weekly_matches`, `first_matches_of_rounds`, `favourite_team`, `favourite_competition`.
- **Clients/misc:** `clients` (+ social_id, locale, nullable name/email, timezone, biometric), `guests`, `enters`, `settings`, `setting_translations`, `notifications` (2026), `notification_translations`.

---

## 7. Commands & schedule (`app/Console/Kernel.php`)

### Registered commands
`SendNotificationDailyMatches`, `getMatchesFromApiAndSaveOnDatabaseEvery15Days`, `UpdateMatchesEvents`, `UpdateMatchesStatus`, `SendReminderBefore45Minutes`, `SendNotificationFavouriteLeague`, `SendNotificationLineupReady`, `ClearNotificationsCommand` (plus everything auto-loaded from `Commands/`).

### Schedule (high level)
| Command | Cadence | Purpose |
|---------|---------|---------|
| `events:update` | every minute | Poll API for goals/cards/VAR — **gated by `testtest.enable==1`** |
| `live:get` (`MatchesLiveEvenetsAndStatus`) | every minute | Fetch live status; fire per-event pushes; **then `Artisan::call('match:events')` sweep** |
| `end:match` (`EndMatchEventCommand`) | every minute | FT/AET/PEN/BT end-of-match notifications (DB) |
| `send:reminder` | every minute | 45-min pre-match reminders (per-client timezone) |
| `theird:send` | every minute | Third-degree (no fixture_id) kick-off alerts |
| `lineup:ready` | every minute | Legacy lineup notifier (fallback) |
| `save:matches` | daily `00:05` | Persist today's fixtures/teams/competitions |
| `future:matches` | daily | Preload next 3 days |
| `notification:daily` | every minute | "Today's matches" per league (TZ-aware; target 10:00-ish) |
| `notification:weekly` | every minute | Weekly round-up (TZ-aware) |
| `notification:favourite_team` | every minute | "Your team plays today" (10:00 local) |
| `notification:favourite_league` | every minute | "Round X starts today" (09:00 local) |
| `notification:static` | daily `09:00` | Static daily reminder to all |
| `posts:publish-scheduled` | every minute | Send push for scheduled posts when due |
| `clear:events` | daily `03:00` | Purge AUTO MatchEvent rows + `vs_images` dir |
| `clear:notifications` | **hourly** | Purge in-app notifications by per-type retention |

> Commands set **no** `->timezone()`; per-client timezone logic lives inside the commands via `Client::cachedTimezones()`.

---

## 8. Services / Traits / Jobs

### Services (`app/Services/`)
- **`FirebaseService`** — HTTP v1 FCM sender.
  - `getAccessToken()`: `Google\Client` assertion → cached `firebase_access_token` (3500s).
  - `sendNotification(tokens, data)`: chunks of 100, `Http::pool` (parallel), 30s timeout, high priority + default sound; stale-token cleanup (404 + `UNREGISTERED` → null out `clients.fb_token`).
  - FCM send uses Laravel `Http` (**fakeable in tests**); OAuth uses Guzzle (**not fakeable** — seed the cache in tests).
- **`NotificationPersistence`** — writes bell rows.
  - `persist(clientIds, data)` / `persistFromTokens(tokens, data)` (maps tokens → client ids via `fb_token`).
  - Inserts `notifications` + both `ar`/`en` `notification_translations`, in 500-row chunks, transactional.
  - `normalizePayload()` ensures `type` + `id` + `post_id` + `match_id`.
- **`PostNotificationService`** — auto push when a post is published.
- **`TranslationService`** — dashboard translation helper.

### Traits (`app/Traits/`)
- **`Notify`** — the push entrypoint. `topicNotifyByFirebase()`, `topicNotifyByFirebaseTokens()`, `sendNotification()`, `persistInApp()`, `partition()`.
  - **`$persistNotifications = true`** default → every push is also written to the bell. Callers that persist themselves set it to `false` to avoid duplicates (e.g. `UpdateMatchesStatus`, `UpdateMatchesEvents`, `SendReminderBefore45Minutes`, admin senders).
- **`NotificationOfMatchesTrait`** — match-event/status push helpers; now adds explicit `type` to notify payloads.
- **`PusherNotification`**, **`HasRolesAndPermissions`** (RBAC), **`CustomResponser`** / **`ApiResponser`** (envelopes), **`TablesQuery`** (dashboard data-tables), **`Helper`**.

### Jobs (`app/Jobs/`)
- **`SendFirebaseNotifications`** — `ShouldQueue`, timeout 300s, tries 3; chunks tokens by 500 and calls `FirebaseService`.

---

## 9. Notification system (contract)

There are **two channels**, written together:
1. **FCM push** (via `Notify` / `FirebaseService`) — see `docs/match-notification-payloads.md`.
2. **In-app bell** (DB `notifications` + `notification_translations`) — see `docs/database-notifications.md`.

### Final DB `type` set (8) — `App\Models\Notification::TYPES`
```
goal, events, match_status, reminder, announcement, post, team, league
```
(`system` was removed; default fallback is `reminder`.)

### Type mapping
| Source | `type` | Notes |
|--------|--------|-------|
| Goals / own goals | `goal` | |
| Cards / VAR / missed penalty | `events` | |
| Match state changes | `match_status` | `data.sub_type` ∈ `match_started, half_time, second_half, extra_time, penalty, match_ended` |
| Favourite team playing today | `team` | |
| Favourite league round starting | `league` | |
| Lineup ready / static / 45-min / third-degree start | `reminder` / `match_status` | third-degree start → `match_status` + `sub_type=match_started` |
| Post published | `post` | |
| Unclassified | `reminder` | |

### `sub_type` mapping
`1H→match_started`, `HT→half_time`, `2H→second_half`, `ET/BT→extra_time`, `P→penalty`, `FT/AET/PEN→match_ended`.

### `normalizePayload` adds
- `type` (keeps supplied, else `reminder`)
- `id` (from `post_id` → `match_id` → `fixture_id` → `team_id` → `competition_id` → `player_id` → `notification_id`)
- `post_id`, `match_id` (always present, `""` when N/A)

### Retention (`clear:notifications`, hourly)
- `events` / `match_status` / `team` / `league` → **48h**
- `goal` / `reminder` → **24h**
- `post` / `announcement` → **168h (7d)**

### Cold-start guard
`UpdateMatchesStatus::handleStatusNotification` seeds the `notificationSent` flag without sending when there is no prior state and status ≠ `1H` (prevents replaying stale pushes on deploy):
```php
if (empty($notificationSentArray) && $status !== '1H') {
    $match->notificationSent = $status;
    $match->save();
    return;
}
```

### Payload changes from the notifications work (all **additive**)
- FCM `notify`: added `type` (events/status/team/league/reminder) + `sub_type` (match_status only).
- In-app `data`: added `type`, `id`, `post_id`, `match_id`.
- **No old key removed or renamed** — fully backward compatible.

---

## 10. External integrations

| Integration | Where | Notes |
|-------------|-------|-------|
| Firebase FCM (HTTP v1) | `FirebaseService`, `Notify` | OAuth cached `firebase_access_token`; project id from `FIREBASE_PROJECT_ID`; credentials `config('firebase.credentials')` |
| Football API | `Commands/*`, `GetDataFromFootBallApi`, `CallApiController` | key in `settings.football_key`; ingestion + live polling + imports |
| Google Translate | `TranslationService` / `TranslateController` | dashboard content translation |
| Google auth | `AuthController@google` | social login |
| Pusher | `Traits/PusherNotification` | legacy realtime |

---

## 10b. Localization

- Two locales: **`ar`, `en`** (route pattern enforces `xx[-XX]`).
- Astrotomic translatable on 11 models → per-row `*_translations` tables.
- `mcamara` provides `/{locale}/...` route translation + `LocaleMiddleware`.
- API reads the client's locale (`clients.locale`) for translation rows; both `ar` + `en` rows are always stored for the bell.

---

## 11. Docs index (`docs/`)

| File | Covers |
|------|--------|
| `PROJECT_MAP.md` | **this file** — whole-project map |
| `database-notifications.md` | bell API + 8 DB types (**needs sync** — still mentions `system`) |
| `match-notification-payloads.md` | FCM push payloads per event/type |
| `mobile-notifications-contract.txt` | mobile contract summary |
| `biometric-login.md` | biometric auth flow |
| `notification-*.md/.html/.txt`, `notifications-*`, `db-notification-types-*` | Arabic/plain variants of the notification type reference |
| `football-api.postman_collection.json`, `Grinta API-documentation.html` | API docs |
| `notification-data-keys.html` | data-key reference |

Postman collections also live in `postman/` (`AppgrintaAuth`, `AppgrintaNotificationsAuth`).

---

## 12. Known issues / gotchas

1. **`data.type` vs column `type`** — most legacy rows store `"type":"system"` inside `data` even when the column is specific. Branch on the **column** `type`; use `data.post_id`/`match_id` for navigation.
2. **`team_id` is not consistent** — DB PK in match events/status, but **external football-API id** in favourite-team and admin team pushes.
| `Api\V1\NotificationController::match_post` persists `announcement` while `Dashboard\NotificationsController::match_post` persists `match_status` — unresolved, keep `match_status` for dashboard unless directed otherwise.
| Both `ar` + `en` translation rows always inserted; single-language senders duplicate the same text.
| Legacy rows may have `data = null` (whole-object payload) — app must fall back safely.
| No `fb_token` ⇒ no bell row (persistence goes through tokens).
| `testtest` and several columns exist only in the live DB, not migrations — snapshot the schema before any rebuild.
| `composer dump-autoload`/`require` can hang locally.
| Contabo HTTPS/TLS broken — blocks production deploy/verification.
| Server `storage/framework/cache` `fopen` error — status unknown.

---

## 12b. Related notification docs to keep in sync

- `docs/database-notifications.md` — update §4/§5 from `system` → the 8 types + `sub_type`.
- `docs/match-notification-payloads.md` — mirrors the FCM-side `type`/`sub_type`.
- `docs/mobile-notifications-contract.txt` — high-level contract.
