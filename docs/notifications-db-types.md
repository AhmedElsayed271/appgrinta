Types بتاعة جدول `notifications` (عمود `type`)

الـ 8 قيم الموجودة في الكود: `App\Models\Notification::TYPES`

1. `goal` – حدث ماتش: هدف / ضربة جزاء / قرار VAR  
   بيستخدمه: `UpdateMatchesEvents`

2. `match_status` – تغيير حالة الماتش (1H / HT / 2H / FT / ET / BT / P / AET / PEN)  
   بيستخدمه: `UpdateMatchesStatus` + إشعارات الماتش اللي بيبعتها الأدمن (`NotificationController::match_post` / Dashboard)

3. `reminder` – تذكير قبل الماتش بـ 45 دقيقة  
   بيستخدمه: `SendReminderBefore45Minutes`

4. `announcement` – إشعار مخصص من الأدمن (نص حر)  
   بيستخدمه: `NotificationController` / `Dashboard` (`url`، `match`)

5. `post` – بوست جديد تم نشره  
   بيستخدمه: `PostNotificationService` + `NotificationController::post_post`

6. `team` – إشعار مخصص عن فريق  
   بيستخدمه: `NotificationController` / `Dashboard` (`team`)

7. `league` – إشعار مخصص عن دوري/بطولة  
   بيستخدمه: `NotificationController` / `Dashboard` (`competition`)

8. `system` – إشعارات تلقائية (افتراضي)  
   بيستخدمه: `SendNotificationFavouriteTeams`، `SendNotificationFavouriteLeague` / `SendNotificationFavouriteLeagueNew`، `StaticCommand`، `TheirdDegreeNotifications`، `SendNotificationLineupReady`، Trait `notifyUsers`/`events`، `GetDataFromFootBallApi`

ملاحظات:
- عمود `type` في `notifications` هو اللي المفروض تعتمد عليه للتصنيف (عرض الإشعار).
- جوه `data` غالبًا بيظهر `"type": "system"` حتى لو عمود `type` مختلف — متعتمدش على `data.type`.