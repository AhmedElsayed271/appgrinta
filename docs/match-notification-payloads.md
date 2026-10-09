# Firebase Push Notifications — Mobile Payload Reference

> Audience: **mobile team**. Every push the backend sends carries four fields — `title`, `body`, `image` and a `notify` data payload. The app should drive navigation **only from `notify`**, never from the `title` / `body` text.
>
> Scope: it reflects the code **as it is today** (the same strings are literally used by the backend commands, see `app/` references). Three match senders still send a single `team_id` instead of both team IDs — see [Open Issues](#open-issues) at the end.

---

## 1. Message envelope

Every notification is delivered twice — once to **`en`** clients and once to **`ar`** clients — so `title` and `body` are language-specific. `notify`, `image` and `id`/`type` are identical for both languages.

```json
{
  "title":  "<language-specific title>",
  "body":   "<language-specific body>",
  "image":  "<image url>",
  "notify": { "...navigation data — the contract below..." }
}
```

| Field    | Meaning                                                                       |
|----------|--------------------------------------------------------------------------------|
| `title`  | Heading shown by the OS (team / league / post name, language-specific)          |
| `body`   | Message text (score line + status/event text, language-specific)                |
| `image`  | Team logo, player photo, merged "vs" image, league logo, or post image          |
| `notify` | **Data payload the app reads to navigate** — documented below                    |

### Delivery path (backend, for reference)

```
command / trait  →  Notify::topicNotifyByFirebaseTokens($tokens, $data)
                  →  app/Jobs/SendFirebaseNotifications.php
                  →  App\Services\FirebaseService::sendNotification($tokens, $data)
                  →  FCM HTTP v1
```

---

## 2. Routing — which screen to open

Read `notify` and pick the screen in this priority order:

| Priority | Condition in `notify` | Screen |
|----------|------------------------|--------|
| 1 | `type == "post"` or a `post_id` key | **Post detail** |
| 2 | `screen == "2"` or `lineup == "ok"` | **Lineup tab** of the match |
| 3 | `screen == "1"` (with `league_id` / `competition_id` / `round`) | **League / round** |
| 4 | `match_id` present | **Match detail** |
| 5 | `url == "matches"` | **Matches tab** |
| 6 | none of the above / `{ match: {...} }` | default (home) — or open the match object |

### Keys you will see in `notify`

| Key | Type | Meaning |
|-----|------|---------|
| `id` | string | Object id (post / match / general) |
| `type` | string | `post`, `match_status`, `reminder`, `lineup`, … |
| `post_id` | string | Post id → post detail |
| `match_id` | string | Match id (database) → match detail |
| `fixture_id` | string | External Football-API fixture id |
| `team_id` | string | ⚠️ **varies by sender** — see [Open Issues](#open-issues) |
| `home_team_id` | string | Home team id (database) |
| `away_team_id` | string | Away team id (database) |
| `screen` | string | `"1"` = league · `"2"` = lineup tab |
| `round` | string | Round/week number |
| `competition_id` | string | Competition id (database) |
| `league_id` | string | League id (Football-API) |
| `competition_en` / `competition_ar` | string | League display name per language |
| `lineup` | string | `"ok"` → lineup tab |
| `url` | string | `"matches"` → matches tab |
| `match` | object | Full `MatchResource` object (legacy senders only) |

---

## 3. Screen payloads

### 3.1 Post detail

| | EN | AR | | |
|---|---|---|---|---|
| Title | post name (`en`) → fallback `ar` → `New Post` | post name (`ar`) → fallback `en` → `منشور جديد` |
| Body | first 100 chars of the stripped description + `...` (empty if none) | same |
| `notify` | `type='post'`, `id={post_id}` (+ `post_id` after normalization) | same |
| Senders | auto on publish: `PostNotificationService::send()` · admin "post" notification (`Api\V1\NotificationController::post_post`) |

### 3.2 Match detail

Any notification that carries `match_id` (+ optional `fixture_id`) opens the match. If `home_team_id` / `away_team_id` are present, the app can highlight those teams; when only `team_id` is present it means "the team whose followers were notified" (home on the first loop, away on the second).

| # | Notification | Title (EN) | Title (AR) | `notify` keys |
|---|--------------|------------|------------|---------------|
| 1 | Match status (live) — backend trait | `{home}-{away}` | `{home_ar}-{away_ar}` | `fixture_id`, `match_id`, `team_id` |
| 2 | Match status (live) — `UpdateMatchesStatus` | `{home} - {away}` | `{home_ar} - {away_ar}` | `match_id`, `fixture_id`, `home_team_id`, `away_team_id` |
| 3 | Match events — backend trait | `{home}-{away}` | `{home_ar}-{away_ar}` | `fixture_id`, `match_id`, `team_id` |
| 4 | Match events — `UpdateMatchesEvents` | `{home}-{away}` | `{home_ar}-{away_ar}` | `match_id`, `fixture_id`, `team_id` |
| 5 | 45-minute reminder | `{home} vs {away}` | `{home_ar} ضد {away_ar}` | `match_id`, `fixture_id`, `home_team_id`, `away_team_id` |
| 6 | Match started (third-degree) | `{home} vs {away}` | `{home_ar} ضد {away_ar}` | `match_id`, `fixture_id`, `home_team_id`, `away_team_id` |
| 7 | Favourite team plays today | `{team_en}` | `{team_ar}` | `fixture_id`, `match_id`, `team_id` |
| 8 | 45-min / lineup (legacy controller) | `{team}` | `{team_ar}` | `{ match: {...} }` (full object) |

#### Body formatting

**Status** (`showGoals=true` except `1H`):

```
EN  "{home}-{away}\r\n  {message_en}"      AR  "{away}-{home}\r\n  {message_ar}"
```

> Arabic score is intentionally **reversed** (away–home). `1H` has **no score prefix**.

| Status | `message_en` | `message_ar` | Score prefix |
|--------|--------------|--------------|--------------|
| `1H` (kickoff) | `Match Started ` | `بداية المباراة ` | no |
| `HT` | `First Half Finished ` | `نهاية الشوط الأول` | yes |
| `2H` | `Second Half Started ` | ` بداية الشوط الثاني ` | yes |
| `FT` | `Match Finished ` | `انتهت المباراة ` | yes |
| `ET` | `Extra Time Started ` | ` بداية الأشواط الاضافية ` | yes |
| `BT` | `Break During Extra Time ` | `استراحة ما بين الشوطين الاضافيين ` | yes |
| `P` | `Penalty Started ` | ` بداية ضربات الجزاء ` | yes |
| `AET` | `Match Finished After Extra Time ` | `انتهى الشوطين الاضافيين و انتهت المباراة ` | yes |
| `PEN` | `Match Finished After Penalty ` | `انتهت ضربات الجزاء و انتهت المباراة ` | yes |

Notes: line break is `\r\n`; `image` is the **merged home/away "vs" image**.

**Events** (goal / card / VAR) — score is already inside the text (`showGoals=false`):

```
EN  "{home}-{away}\n\r '{minute}{extra}   {text}"      AR  "{away}-{home}\n\r '{minute}{extra}   {text}"
```

> Line break here is `\n\r` (reversed), there is a **literal apostrophe** before the minute, and `extra` renders as ` + N` in added time.

| Event | `text` (EN) | `text` (AR) |
|-------|-------------|-------------|
| Goal (normal) | `{player} scored goal for {team} team` | `سجل اللاعب {player} هدفا لصالح فريق {team}` |
| Goal (penalty) | `{player} scored goal with penalty for {team} team` | `سجل اللاعب {player} هدفا بضربة جزاء لصالح فريق {team}` |
| Missed penalty | ` Missed penalty for {team} team` | ` ضربة جزاء ضائعه {team}` |
| Own goal | `{player} scored an own goal for {team} team` | `سجل اللاعب {player} هدفا في مرماه لصالح فريق {team}` |
| Yellow card | `{player} took yellow card` | `حصل اللاعب {player} على كرت أصفر` |
| 2nd yellow card | `{player} took second yellow card` | `حصل اللاعب {player} على 2 كرت أصفر` |
| Red card | `{player} took red card` | `حصل اللاعب {player} على كرت أحمر` |
| VAR — goal cancelled | `Goal Cancelled [VAR]` | `[VAR] تم الغاء الهدف` |
| VAR — penalty confirmed | `[VAR] The Penalty is confirmed for {team} team` | `[VAR] تم احتساب ضربة الجزاء لفريق {team}` |
| VAR — offside | `Goal Disallowed - offside [VAR]` | `[VAR] الهدف غير مسموح به - تسلل ` |
| VAR — goal allowed | `Goal Allowed [VAR]` | `[VAR] تم احتساب الهدف` |
| VAR — generic | `There are a var now in match [VAR]` | ` جاري مراجعة ال[VAR] حاليا` |

Examples:

```
EN:  "2-1\n\r '45   Salah scored goal for Egypt team"
AR:  "1-2\n\r '45   سجل اللاعب صلاح هدفا لصالح فريق مصر"
AR:  "1-1\n\r '70   [VAR] تم الغاء الهدف"
```

If no player was resolved, the text falls back to `From Team {team}` / `من فريق {team}`.

### 3.3 Lineup tab (inside match)

| | EN | AR |
|---|---|---|
| Title | `{home} - {away}` | `{home_ar} - {away_ar}` |
| Body | `The lineup for both teams is available now` | `تشكيلة الفريقين متاحة الان` |
| `notify` | `screen='2'`, `fixture_id`, `match_id`, `home_team_id`, `away_team_id`, `lineup='ok'` | same |
| Senders | `SendNotificationLineupReady` (old) · `SendNotificationLineupReadyNew` (new — currently uses the trait's `team_id` payload) |

### 3.4 League / round

| | EN | AR |
|---|---|---|
| Title | `{league_en}` | `{league_ar}` |
| Body | `{RoundLabelEn} of {league_en} starts today` | `انطلاق {RoundLabelAr} من {league_ar} اليوم` |
| `notify` | `screen='1'`, `round`, `competition_id`, `league_id`, `competition_en`, `competition_ar`, `image` | same |
| Senders | `SendNotificationFavouriteLeagueNew` (`notification:favourite_league`) · legacy `SendNotificationFavouriteLeague` |

`RoundLabel` = `Round {n}` / `الجولة {n}` for leagues. Knockout cups use special labels when the round is the highest active bracket round: `The Final` / `المباراة النهائية`, `The 3rd Place Match` / `مباراة تحديد المركز الثالث`, `The Semi-Finals` / `الدور نصف النهائي`, `The Quarter-Finals` / `دور ربع النهائي`, `The Round of 16/32/64/128` / `دور الـ 16/32/64/128`, etc.

Legacy sender body: EN `The {round} of {league_en} will start today`, AR `انطلاق الاسبوع {week} من {league_ar} اليوم`.

### 3.5 Matches tab (static reminder)

| | EN | AR |
|---|---|---|
| Title | `Today's Matches` | `تعرف على مباريات اليوم ⚽` |
| Body | `Check today's top football matches and schedules on Grinta` | `تابع أهم مباريات اليوم ومواعيدها مباشرة من جرينتا.` |
| `notify` | `url='matches'`, `image` | same |
| Sender | `StaticCommand` (`notification:static`, daily **09:00 server time**) | |

### 3.6 Test only (not shipped)

`HomeController::testNotification` — one hard-coded token; Title `Good Morning`, Body `Body good morning`, `notify` `{ 'post_id' => '20' }`.

---

## 4. Concrete envelope examples — every notification as full JSON

> Values below are **concrete examples** using a sample match (Al Ahly 2 – 1 Zamalek, `fixture_id 456789`, DB `match_id 123`, home DB id `1` / external `33`, away DB id `2` / external `40`). Real values are resolved at runtime. `notify` is **identical for the EN and AR dispatch** — only `title` and `body` differ.

### 4.1 Match status — live (trait) → Match detail

Sender: `NotificationOfMatchesTrait::notifyUsers()` (`:314` home loop, `:325` away loop) — status `FT`.

```json
{
  "title": "Al Ahly-Zamalek",
  "body": "2-1\r\n  Match Finished ",
  "image": "https://cdn.example.com/vs/al-ahly-zamalek.png",
  "notify": {
    "fixture_id": "456789",
    "match_id": "123",
    "team_id": "1"
  }
}
```

AR dispatch (same `notify`; score reversed):

```json
{
  "title": "الأهلي-الزمالك",
  "body": "1-2\r\n  انتهت المباراة "
}
```

`team_id` = the team currently being looped over (`"1"` on the home loop, `"2"` on the away loop) — it is the **DB primary key**.

### 4.2 Match status — `UpdateMatchesStatus` → Match detail

Sender: `UpdateMatchesStatus::sendStatusNotification()` (`:188-193`); sends both team IDs.

```json
{
  "title": "Al Ahly - Zamalek",
  "body": "2-1\r\n  Match Finished ",
  "image": "https://cdn.example.com/logo/al-ahly.png",
  "notify": {
    "match_id": "123",
    "fixture_id": "456789",
    "home_team_id": "1",
    "away_team_id": "2"
  }
}
```

AR block:

```json
{
  "title": "الأهلي - الزمالك",
  "body": "1-2\r\n  انتهت المباراة "
}
```

### 4.3 Match events — trait → Match detail

Sender: `NotificationOfMatchesTrait::events()` (goal/card/VAR). Score is inside the text, line break `\n\r` + apostrophe before the minute.

```json
{
  "title": "Al Ahly-Zamalek",
  "body": "2-1\n\r '45   Salah scored goal for Egypt team",
  "image": "https://cdn.example.com/players/salah.png",
  "notify": {
    "fixture_id": "456789",
    "match_id": "123",
    "team_id": "1"
  }
}
```

AR block (score reversed):

```json
{
  "title": "الأهلي-الزمالك",
  "body": "1-2\n\r '45   سجل اللاعب صلاح هدفا لصالح فريق مصر"
}
```

### 4.4 Match events — `UpdateMatchesEvents` → Match detail

Sender: `UpdateMatchesEvents` (`:346-350`); still single `team_id`.

```json
{
  "title": "Al Ahly-Zamalek",
  "body": "2-1\n\r '63   حصل اللاعب على كرت أصفر",
  "image": "https://cdn.example.com/logo/zamalek.png",
  "notify": {
    "match_id": "123",
    "fixture_id": "456789",
    "team_id": "2"
  }
}
```

AR block:

```json
{
  "title": "الأهلي-الزمالك",
  "body": "1-2\n\r '63   حصل اللاعب على كرت أصفر"
}
```

### 4.5 45-minute reminder → Match detail

Sender: `SendReminderBefore45Minutes` (`:138-143`).

```json
{
  "title": "Al Ahly vs Zamalek",
  "body": "Match is about to start",
  "image": "https://cdn.example.com/vs/al-ahly-zamalek.png",
  "notify": {
    "match_id": "123",
    "fixture_id": "456789",
    "home_team_id": "1",
    "away_team_id": "2"
  }
}
```

AR block:

```json
{
  "title": "الأهلي ضد الزمالك",
  "body": "المباراة على وشك البدء"
}
```

### 4.6 Match started (third-degree) → Match detail

Sender: `TheirdDegreeNotifications` (`:79-84`).

```json
{
  "title": "Al Ahly vs Zamalek",
  "body": "Match Started",
  "image": "https://cdn.example.com/vs/al-ahly-zamalek.png",
  "notify": {
    "match_id": "123",
    "fixture_id": "456789",
    "home_team_id": "1",
    "away_team_id": "2"
  }
}
```

AR block:

```json
{
  "title": "الأهلي ضد الزمالك",
  "body": "بداية المباراة"
}
```

### 4.7 Favourite team plays today → Match detail

Sender: `SendNotificationFavouriteTeams` (`:136-140`). ⚠️ `team_id` here is the **external Football-API id** (`teams.team_id`), not the DB key.

```json
{
  "title": "Al Ahly",
  "body": "Your favourite team Al Ahly has a match today",
  "image": "https://cdn.example.com/logo/al-ahly.png",
  "notify": {
    "fixture_id": "456789",
    "match_id": "123",
    "team_id": "33"
  }
}
```

AR block:

```json
{
  "title": "الأهلي",
  "body": "فريقك المفضل الأهلي لديه مباراة اليوم"
}
```

### 4.8 Legacy controller — 45-min / lineup → full match object

Sender: `GetDataFromFootBallApi` (`:551`, `:600`). ⚠️ AR clients get the **English** template (known bug) with the missing space before `has` / `is`.

```json
{
  "title": "Al Ahly",
  "body": "Your Favourite team Al Ahlyhas match after 45 minutes",
  "image": "https://cdn.example.com/logo/al-ahly.png",
  "notify": {
    "match": {
      "id": 123,
      "fixture_id": 456789,
      "home": { "id": 1, "team_id": 33, "...": "..." },
      "away": { "id": 2, "team_id": 40, "...": "..." }
    }
  }
}
```

### 4.9 Lineup ready → Lineup tab

Sender: `SendNotificationLineupReady` (`:138-145`); carries `screen='2'` + `lineup='ok'`.

```json
{
  "title": "Al Ahly - Zamalek",
  "body": "The lineup for both teams is available now",
  "image": "https://cdn.example.com/vs/al-ahly-zamalek.png",
  "notify": {
    "screen": "2",
    "fixture_id": "456789",
    "match_id": "123",
    "home_team_id": "1",
    "away_team_id": "2",
    "lineup": "ok"
  }
}
```

AR block:

```json
{
  "title": "الأهلي - الزمالك",
  "body": "تشكيلة الفريقين متاحة الان"
}
```

### 4.10 Favourite league round starts → League / round

Sender: `SendNotificationFavouriteLeagueNew` (window **09:00–09:09** local).

```json
{
  "title": "Egyptian Premier League",
  "body": "Round 12 of Egyptian Premier League starts today",
  "image": "https://cdn.example.com/leagues/epl.png",
  "notify": {
    "screen": "1",
    "round": "12",
    "competition_id": "5",
    "league_id": "1204",
    "competition_en": "Egyptian Premier League",
    "competition_ar": "الدوري المصري الممتاز",
    "image": "https://cdn.example.com/leagues/epl.png"
  }
}
```

AR block:

```json
{
  "title": "الدوري المصري الممتاز",
  "body": "انطلاق الجولة 12 من الدوري المصري الممتاز اليوم"
}
```

### 4.11 Static daily reminder → Matches tab

Sender: `StaticCommand` (`notification:static`).

```json
{
  "title": "Today's Matches",
  "body": "Check today's top football matches and schedules on Grinta",
  "image": "https://cdn.example.com/static/matches.png",
  "notify": {
    "url": "matches",
    "image": "https://cdn.example.com/static/matches.png"
  }
}
```

AR block:

```json
{
  "title": "تعرف على مباريات اليوم ⚽",
  "body": "تابع أهم مباريات اليوم ومواعيدها مباشرة من جرينتا."
}
```

### 4.12 Post published → Post detail

Sender: `PostNotificationService::send()` — title = post name (EN or AR), body = first 100 chars of the stripped description + `...`.

```json
{
  "title": "Preview: Al Ahly vs Zamalek",
  "body": "The Egyptian derby returns this weekend as Al Ahly host Zamalek at the Cairo Internati...",
  "image": "https://cdn.example.com/posts/45.jpg",
  "notify": {
    "type": "post",
    "id": "45"
  }
}
```

AR block (Arabic post name + Arabic description):

```json
{
  "title": "الأهلي والزمالك في قمة منتظرة",
  "body": "يعود الديربي المصري هذا الأسبوع حيث يستضيف الأهلي نظيره الزمالك في استاد القاهرة الدولي..."
}
```

### 4.13 Admin-created push (dashboard / API) → varies by variant

Senders: `Api\V1\NotificationController` (`post_post`, `match_post`, `custom`, `general`); `Dashboard\NotificationsController`. Admin provides EN and AR `name` / `description`; `notify` is flattened per variant (e.g. `post_id`, match context, `content_id`, or empty).

```json
{
  "title": "Grinta Update",
  "body": "A brand new version is now available on the store.",
  "image": "https://cdn.example.com/banners/update.png",
  "notify": {}
}
```

AR block:

```json
{
  "title": "تحديث جديد في جرينتا",
  "body": "نسخة جديدة متاحة الآن على المتجر."
}
```

### 4.14 Test only (not shipped)

`HomeController::testNotification` — hard-coded token:

```json
{
  "title": "Good Morning",
  "body": "Body good morning",
  "notify": { "post_id": "20" }
}
```

---

## 5. Note on OS handling

- Notifications arrive through FCM; **no inline actions** are attached.
- `image` can be: player photo (events), merged home/away image (status, reminder), league logo (league), team logo (favourite team), or post image (posts).

---

## 6. Full-object payload (legacy)

Two legacy senders pass the whole match instead of flat keys:

```php
'notify' => (object) array('match' => new MatchResource($match))
```

`MatchResource` shape:

```json
{
  "id": 123,
  "fixture_id": 456789,
  "home": { "id": 1, "team_id": 33, "...": "..." },
  "away": { "id": 2, "team_id": 40, "...": "..." }
}
```

Both team ids are reachable under `home.team_id` / `away.team_id`.

---

## Appendices

### Appendix A — Backend call-site index (`notify` payload builders)

| # | Source | Used for | `notify` keys |
|---|--------|----------|---------------|
| 1 | `NotificationOfMatchesTrait::notifyUsers()` `:314`, `:325` | Match status + events (trait) | `fixture_id`, `match_id`, `team_id` |
| 2 | `NotificationOfMatchesTrait::sendNotifications()` `:426`, `:433` | Legacy fallback | `{ match: MatchResource }` |
| 3 | `UpdateMatchesStatus::sendStatusNotification()` `:188-193` | Match status (current) | `match_id`, `fixture_id`, `home_team_id`, `away_team_id` |
| 4 | `UpdateMatchesEvents` `:346-350` | Match events (current) | `match_id`, `fixture_id`, `team_id` |
| 5 | `SendReminderBefore45Minutes` `:138-143` | 45-minute reminder | `match_id`, `fixture_id`, `home_team_id`, `away_team_id` |
| 6 | `SendNotificationLineupReady` `:138-145` | Lineup ready (old) | `screen`, `fixture_id`, `match_id`, `home_team_id`, `away_team_id`, `lineup` |
| 7 | `TheirdDegreeNotifications` `:79-84` | Match started | `match_id`, `fixture_id`, `home_team_id`, `away_team_id` |
| 8 | `GetDataFromFootBallApi` `:551`, `:600` | 45-min + lineup (legacy) | `{ match: MatchResource }` |
| 9 | `SendNotificationFavouriteTeams` `:136-140` | Favourite team plays today | `fixture_id`, `match_id`, `team_id` |

**Call sites 1, 4 and 9 are the only ones that still send a single `team_id`.** Call sites 3, 5, 6 and 7 already send both team IDs.

### Appendix B — Scheduling & timing (per-client local timezone unless noted)

| Command | Window | Notes |
|---------|--------|-------|
| `notification:favourite_team` | **10:00–10:09** local | `everyMinute()`; per-team/day cache written after send |
| `notification:favourite_league` | **09:00–09:09** local | `everyMinute()`; fires on the **first day of the round** |
| `notification:static` | `dailyAt('9:00')` **server time** | static daily reminder |
| `notification:daily` / `notification:weekly` | 07:00 / Sunday 07:30 local | ⚠️ **no FCM push** — only fetches and stores `daily_matches` / `weekly_matches` |

### Appendix C — In-app bell (not a push)

`NotificationPersistence` stores `title_ar` / `title_en` / `body_ar` / `body_en` on the server for the bell API (`ClientNotificationsController`). It is refreshed from the same push payloads but is a separate mechanism.

---

## Open issues

1. **Three call sites still send one `team_id` instead of both teams:**
   - `NotificationOfMatchesTrait::notifyUsers()` (`:314`, `:325`)
   - `UpdateMatchesEvents` (`:349`)
   - `SendNotificationFavouriteTeams` (`:139`)

2. **`team_id` means two different things** across call sites:
   - call sites 1 and 4 → database primary key (`matches.team1_id` = `teams.id`)
   - call site 9 → `teams.team_id`, the external Football-API id
   The app currently cannot treat this key as one type — needs a decision before unifying.

3. **Title separator is inconsistent:** `"-"` (traits/events) vs `" - "` (status) vs `" vs "` / `" ضد "` (reminder).

4. **Line break is inconsistent:** `\r\n` in status bodies vs `\n\r` in event bodies.

5. **Legacy controller sends English text to Arabic clients** (call site 8).

6. **Two payload families exist:** flat IDs (`home_team_id` / `away_team_id`) vs whole object (`{ match: MatchResource }`). The app should pick one before call sites 1, 4 and 9 are aligned.