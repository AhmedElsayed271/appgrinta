# In-App (Database) Notifications — API & Type Reference

> Audience: **mobile team**. These are the notifications stored in the database and shown inside the app's **bell** — separate from FCM push notifications (which are documented in `match-notification-payloads.md`). One DB row per client, written **at the same time** the push is sent, so the bell and the push always match.

---

## 1. How they are stored

```
push sender  →  Notify::topicNotifyByFirebaseTokens() / sendNotification()
              →  Notify::persistInApp()
              →  App\Services\NotificationPersistence::persistFromTokens() / persist()
              →  notifications  +  notification_translations  rows
```

Callers that write the bell **themselves** set `$this->persistNotifications = false` (so `Notify` does not double-write) and call `(new NotificationPersistence())->persist(...)` directly — `UpdateMatchesStatus`, `UpdateMatchesEvents`, `SendReminderBefore45Minutes`, and the admin dashboard/API senders.

### Database schema

```sql
notifications (
  id, client_id(FK → clients.id),
  type,            -- see §4 (nullable)
  image,           -- url or null
  data,            -- JSON text — the payload the app navigates with (nullable)
  is_read,         -- bool, default false
  created_at, updated_at
)
-- indexed (client_id, is_read)

notification_translations (
  id, notification_id(FK → notifications.id),
  title, body,
  locale,          -- 'ar' | 'en'
  -- unique (notification_id, locale)
)
```

One `notifications` row is created per client, and **both** an `ar` and an `en` translation row are always inserted for it.

---

## 2. API — the bell

All endpoints require a logged-in client and return the standard envelope `{ isSuccess, message, additionalInfo, data, error, code }`. Base: `{{base_url}}` (`/api/v1`).

| Method | Endpoint | Query / body | Description |
|--------|----------|--------------|-------------|
| GET | `/notifications` | `?type=` (optional, filters by column `type`) · `?per_page=` (2..50, default 15) | Paginated bell, newest first |
| GET | `/notifications/unread-count` | — | Number of unread items |
| POST | `/notifications/read` | `{ "id": 12 }` **or** `{ "ids": [12, 13] }` | Mark unread as read |
| POST | `/notifications/read-all` | — | Mark all as read |
| DELETE | `/notifications/{id}` | — | Delete one item (only own items) |

**Examples**

```http
GET /api/v1/notifications?per_page=15
Authorization: Bearer <token>

GET /api/v1/notifications/unread-count

POST /api/v1/notifications/read
{ "ids": [12, 13] }

DELETE /api/v1/notifications/12
```

### Item shape — `NotificationResource`

```json
{
  "id": 12,
  "title": "Al Ahly - Zamalek",
  "body": "2-1\r\n  Match Finished ",
  "image": "https://cdn.example.com/logo/al-ahly.png",
  "type": "match_status",
  "data": { "match_id": "123", "fixture_id": "456789", "home_team_id": "1", "away_team_id": "2" },
  "is_read": false,
  "created_at": "2026-10-06T09:00:00.000000Z"
}
```

| Field | Source | Notes |
|-------|--------|-------|
| `id` | `notifications.id` | use this for read/delete |
| `title`, `body` | locale translation (`ar`/`en`) of the client | falls back to `ar` when the client locale row is empty |
| `type` | `notifications.type` | see §4 — use to branch the list UI |
| `data` | `notifications.data` (JSON) | **navigation payload** — decoded object, may be `null` (legacy) |
| `image` | `notifications.image` | item thumbnail |
| `is_read` | `notifications.is_read` | drive the unread badge |

---

## 3. What gets saved as `type` and `data`

The persisted `type` is chosen as: explicit **`type`** passed by the sender → else the payload's `type` → else **`"system"`**. The `data` JSON column is the normalized `notify` payload plus a few helpers so the app can branch without knowing the sender:

```php
// App\Services\NotificationPersistence::normalizePayload()
$payload['type'] = $payload['type'] ?? 'system';                    // always present
$payload['id']    = first of [post_id, match_id, fixture_id,        // single navigation id
                              team_id, competition_id, player_id, notification_id]
                     that is present;                                // else '' (omitted)
$payload['post_id']  = explicit post_id, or id when type == 'post';
$payload['match_id'] = explicit match_id, or id when type in
                       [match_status, goal, reminder, match, event];
```

> ⚠️ Two quirks to be aware of:
> 1. Senders that pass their own `type` for the **column** (e.g. `UpdateMatchesStatus` → `match_status`) still normalize the `data` payload, so `data.type` inside the JSON is often **`"system"`** even when the column `type` is specific. **Prefer the column `type` for display, and `data` keys for navigation.**
> 2. When the payload contains a non-scalar object (legacy `{ match: {...} }`), `data` is stored as **`null`**.

---

## 4. Notification types (what the app will receive)

| Column `type` | Meaning | Opens / highlights | Main senders |
|---------------|---------|--------------------|--------------|
| `goal` | A match event: goal, penalty, VAR decision | Match detail (event) | `UpdateMatchesEvents` |
| `match_status` | Match changed state: 1H/HT/2H/FT/ET/BT/P/AET/PEN | Match detail | `UpdateMatchesStatus` · admin match push |
| `reminder` | "Match is about to start", 45 min before | Match detail | `SendReminderBefore45Minutes` |
| `announcement` | Manual / custom push (free text) | Per `data` keys | admin `url` / `match` push |
| `post` | New post published | Post detail | `PostNotificationService` · admin post push |
| `team` | Manual push about a team | Team screen (`screen` + `team_id`) | admin team push |
| `league` | Manual push about a league | League screen (`screen` + `competition_id`) | admin competition push |
| `system` | Default bucket | Per `data` keys | favourite team / favourite league / static / third-degree / lineup / trait status & events / legacy controller |

> The model whitelist (`app/Models/Notification.php`) matches the list above: `goal, match_status, reminder, announcement, post, team, league, system`.

---

## 5. Example for every type

Concrete values (sample client id 7, match 123, fixture 456789, Al Ahly 2 – 1 Zamalek, post 45). Showed as the **stored row** + the **`GET /notifications` item** you receive.

### 5.1 `goal` — a goal / penalty / VAR event

Sender: `UpdateMatchesEvents` (persists `:404`, `type` = `goal` for goal/penalty/VAR events, else `match_status`).

```json
{
  "id": 12,
  "title": "Al Ahly - Zamalek",
  "body": "2-1\n\r '45   Salah scored goal for Egypt team",
  "image": "https://cdn.example.com/logo/al-ahly.png",
  "type": "goal",
  "data": {
    "match_id": "123",
    "fixture_id": "456789",
    "team_id": "1",
    "type": "system",
    "id": "123",
    "post_id": ""
  },
  "is_read": false,
  "created_at": "2026-10-06T09:00:00.000000Z"
}
```

### 5.2 `match_status` — match state changed

Sender: `UpdateMatchesStatus` (persists `:247`).

```json
{
  "id": 11,
  "title": "Al Ahly - Zamalek",
  "body": "2-1\r\n  Match Finished ",
  "image": "https://cdn.example.com/logo/al-ahly.png",
  "type": "match_status",
  "data": {
    "match_id": "123",
    "fixture_id": "456789",
    "home_team_id": "1",
    "away_team_id": "2",
    "type": "system",
    "id": "123",
    "post_id": ""
  },
  "is_read": false,
  "created_at": "2026-10-06T16:45:00.000000Z"
}
```

Also written as `match_status` by the admin **match** push (`NotificationController::match_post`, `Dashboard\NotificationsController`) — there `data` = `{ "match_id": "..." }` and `title`/`body` come from the admin's EN/AR inputs.

### 5.3 `reminder` — match starts in 45 minutes

Sender: `SendReminderBefore45Minutes` (persists `:176`).

```json
{
  "id": 10,
  "title": "Al Ahly vs Zamalek",
  "body": "Match is about to start",
  "image": "https://cdn.example.com/vs/al-ahly-zamalek.png",
  "type": "reminder",
  "data": {
    "match_id": "123",
    "fixture_id": "456789",
    "home_team_id": "1",
    "away_team_id": "2",
    "type": "system",
    "id": "123",
    "post_id": ""
  },
  "is_read": false,
  "created_at": "2026-10-06T15:15:00.000000Z"
}
```

### 5.4 `announcement` — admin custom / URL push

Sender: admin `url` push (`NotificationController::url_post` `:233`, `Dashboard\NotificationsController`). `body` = `strip_tags(description)`.

```json
{
  "id": 9,
  "title": "Grinta Update",
  "body": "A brand new version is now available on the store.",
  "image": "https://cdn.example.com/banners/update.png",
  "type": "announcement",
  "data": {
    "url": "matches",
    "type": "system",
    "id": "",
    "post_id": "",
    "match_id": ""
  },
  "is_read": true,
  "created_at": "2026-10-05T10:00:00.000000Z"
}
```

### 5.5 `post` — new post published

Sender: auto on publish `PostNotificationService::send()` (persists through `Notify`, `type` read from `notify.type`).

```json
{
  "id": 8,
  "title": "Preview: Al Ahly vs Zamalek",
  "body": "The Egyptian derby returns this weekend as Al Ahly host Zamalek at the Cairo Internati...",
  "image": "https://cdn.example.com/posts/45.jpg",
  "type": "post",
  "data": {
    "type": "post",
    "id": "45",
    "post_id": "45",
    "match_id": ""
  },
  "is_read": false,
  "created_at": "2026-10-04T12:30:00.000000Z"
}
```

### 5.6 `team` — admin push about a team

Sender: admin team push (`NotificationController::team_post` `:294`). `data.team_id` = the **external Football-API id**; `screen` = where the app opens (e.g. `"1"`).

```json
{
  "id": 7,
  "title": "Support Al Ahly this season!",
  "body": "Follow every match of your favourite club.",
  "image": "https://cdn.example.com/logo/al-ahly.png",
  "type": "team",
  "data": {
    "team_id": "33",
    "screen": "1",
    "type": "system",
    "id": "33",
    "post_id": "",
    "match_id": ""
  },
  "is_read": false,
  "created_at": "2026-10-03T09:00:00.000000Z"
}
```

### 5.7 `league` — admin push about a league/competition

Sender: admin competition push (`NotificationController::competition_post` `:351`).

```json
{
  "id": 6,
  "title": "The derby week is here!",
  "body": "Don't miss the Egyptian Premier League action.",
  "image": "https://cdn.example.com/leagues/epl.png",
  "type": "league",
  "data": {
    "competition_id": "5",
    "screen": "1",
    "type": "system",
    "id": "5",
    "post_id": "",
    "match_id": ""
  },
  "is_read": false,
  "created_at": "2026-10-02T08:00:00.000000Z"
}
```

### 5.8 `system` — default bucket (automatic senders)

Anything sent through the `Notify` trait **without** an explicit `type` lands here: favourite team plays today, favourite league round, static daily, match-started (third-degree), lineup ready, trait-driven status/events, and the legacy controller. Tapping should read `data` keys (`match_id`, `team_id`, `url`, `screen`, `round`, …) to navigate — see `match-notification-payloads.md`.

Favourite team plays today (`SendNotificationFavouriteTeams` — ⚠️ `team_id` = external API id):

```json
{
  "id": 5,
  "title": "Al Ahly",
  "body": "Your favourite team Al Ahly has a match today",
  "image": "https://cdn.example.com/logo/al-ahly.png",
  "type": "system",
  "data": {
    "fixture_id": "456789",
    "match_id": "123",
    "team_id": "33",
    "type": "system",
    "id": "123",
    "post_id": ""
  },
  "is_read": false,
  "created_at": "2026-10-01T10:05:00.000000Z"
}
```

Favourite league round starts (`SendNotificationFavouriteLeagueNew`):

```json
{
  "id": 4,
  "title": "Egyptian Premier League",
  "body": "Round 12 of Egyptian Premier League starts today",
  "image": "https://cdn.example.com/leagues/epl.png",
  "type": "system",
  "data": {
    "screen": "1",
    "round": "12",
    "competition_id": "5",
    "league_id": "1204",
    "competition_en": "Egyptian Premier League",
    "competition_ar": "الدوري المصري الممتاز",
    "type": "system",
    "id": "5",
    "post_id": "",
    "match_id": ""
  },
  "is_read": false,
  "created_at": "2026-10-01T09:02:00.000000Z"
}
```

Static daily (`StaticCommand`):

```json
{
  "id": 3,
  "title": "Today's Matches",
  "body": "Check today's top football matches and schedules on Grinta",
  "image": "https://cdn.example.com/static/matches.png",
  "type": "system",
  "data": {
    "url": "matches",
    "image": "https://cdn.example.com/static/matches.png",
    "type": "system",
    "id": "",
    "post_id": "",
    "match_id": ""
  },
  "is_read": false,
  "created_at": "2026-10-01T09:00:00.000000Z"
}
```

Legacy controller (`GetDataFromFootBallApi` — whole match object, so `data` is **`null`**):

```json
{
  "id": 2,
  "title": "Al Ahly",
  "body": "Your Favourite team Al Ahlyhas match after 45 minutes",
  "image": "https://cdn.example.com/logo/al-ahly.png",
  "type": "system",
  "data": null,
  "is_read": false,
  "created_at": "2026-09-30T14:30:00.000000Z"
}
```

---

## 6. Suggested app-side handling

1. Show the bell from `GET /notifications` (paginate via `links.next`).
2. Unread badge = `GET /notifications/unread-count` (refresh on `read` / `read-all`).
3. On tap: branch on **column `type`** first; use **`data` keys** for navigation:
   - `data.match_id` / `data.fixture_id` → match detail
   - `data.post_id` / `data.type === "post"` → post detail
   - `data.screen === "1"` + `data.competition_id` / `data.league_id` → league
   - `data.screen` + `data.team_id` → team
   - `data.url === "matches"` → matches tab
   - otherwise → open home
4. `data` may be `null` (legacy rows) — tap safely falls back to home.

---

## 7. Notes / open issues

1. **`data.type` vs column `type`:** most rows carry `"type": "system"` **inside** `data` even when the column is specific (e.g. `goal`) — don't rely on `data.type`.
2. **Both `ar` and `en` translation rows** are always inserted; senders that only pass a single-language `title`/`body` store the same text for both locales (the app reads the client's own locale, so display is right, but the unused locale row is redundant).
3. **`team_id` is not consistent:** DB primary key in `UpdateMatchesEvents` / `UpdateMatchesStatus` style data, but **external Football-API id** in favourite-team and admin `team` pushes.
4. **Legacy rows carry `data = null`** (whole-object payload can't be flattened).
5. `read` only marks **unread** ids; `read-all` marks all unread. Delete is per-item and owner-scoped.
6. The bell is written on the **same dispatch** as the FCM push, so a client without a registered `fb_token` gets **no** bell row (persistence goes through tokens).
7. `notifications` rows are never auto-cleaned — consider a retention policy before adding a cleanup command.