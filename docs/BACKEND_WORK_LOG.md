# TaskFlow Backend — سجل العمل الكامل

توثيق شامل لكل حاجة اتعملت على الباك اند، بالترتيب الزمني، من أول ما كان المشروع سكيلتون ناقص لحد ما بقى شغال بالكامل مع Real-time وBilling حقيقيين. كل قسم بيقول: كان فيه إيه، اتعمل إيه، وليه.

---

## 0. الحالة الأولية

المشروع كان Laravel 13 منظّم بأسلوب Domain-Driven (`app/Domains/{Domain}/{Controllers,Actions,Requests,Resources,Policies}`) بدل التنظيم الكلاسيكي. البنية كانت موجودة لكل الدومينات (Auth, Workspace, Board, Column, Card, Comment, Checklist, Attachment, Label, Achievement, Notification, Subscription, Search, User)، بس تنفيذها كان ناقص وفيه أعطاب حقيقية بتمنع المشروع من الشغل أصلاً:

- مسارات في `routes/api.php` بتنادي على methods مش موجودة في الكود خالص (`WorkspaceController::invite/invites/members/...`, `AchievementController::streak/activity/stats`, `SubscriptionController::invoices`) — أي حد يجرب يستخدمها كان هياخد 500.
- الصلاحيات (Policies) كانت مكتوبة لبعض الدومينات (Board, Card, Workspace) بس **مش متسجّلة مع الـ Gate خالص** — يعني `authorize()` كان هيرفض أي حد حتى لو هو المالك الفعلي.
- مفيش أي enforcement لحدود الخطة (Free/Pro) رغم وجود `PlanLimitExceededException` جاهز.
- البحث (`SearchController`) كان بيدوّر في بيانات كل الناس، مش بس الـ workspaces بتاعتك.
- الـ Notification model المخصّص كان بيتنادى عليه methods (`markAsRead`, `unreadNotifications`) مش موجودة فيه.
- مفيش أي منطق حقيقي للـ Activity/Streaks/Achievements — كانت مجرد جداول فاضية.
- مفيش أي endpoints لإدارة البروفايل الشخصي (تغيير باسورد، رفع صورة، الجلسات، الإعدادات).

---

## 1. المرحلة الأولى: تدقيق وإكمال الباك اند الأساسي

### 1.1 إصلاح المسارات المكسورة
كل الـ methods الناقصة اتبنت كاملة في `WorkspaceController` (إدارة الأعضاء، الأدوار، نقل الملكية، الدعوات)، `AchievementController` (streak/activity/stats)، وأضفت `SubscriptionController::invoices` كـ stub (بعدين اتحول لحقيقي في مرحلة الـ Billing).

### 1.2 تفعيل الصلاحيات في كل مكان
اكتشفت إن الـ Policies الموجودة (`BoardPolicy`, `CardPolicy`, `WorkspacePolicy`) معندهاش `Gate::policy()` مسجّلة لها خالص — سجّلتها كلها في `AppServiceProvider::boot()`. وبنيت Policies جديدة كانت ناقصة تمامًا: `ColumnPolicy`, `CommentPolicy` (التعليق يتعدّل بس من صاحبه)، `ChecklistPolicy`, `AttachmentPolicy`, `LabelPolicy` — كلهم بيتأكدوا إنك عضو في الـ workspace قبل أي عملية.

### 1.3 حدود الخطة (Plan Limits)
بنيت `app/Domains/Subscription/Support/PlanLimits.php` كمكان واحد بيحدد كل أرقام الخطة المجانية (workspace واحدة، 3 بوردات، 3 أعضاء، 10 ميجا مرفقات، 7 أيام سجل نشاط) وربطته في كل نقطة إنشاء (workspace، board، دعوة عضو، رفع مرفق) — لو تجاوزت الحد بيرجع 403 برمز `PLAN_LIMIT` بدل ما يسمح بيها بصمت.

### 1.4 تصحيح تسريب البيانات في البحث
`SearchController` كان بيرجع نتايج من كل الـ boards/cards/comments في قاعدة البيانات كلها. اتقصر على الـ workspaces اللي اليوزر عضو فيها بس.

### 1.5 محرك الـ Activity والـ Streaks والـ Achievements
- `LogActivityAction`: بيسجّل صف في جدول `activities` كل ما حاجة تحصل (كارت اتعمل، بورد اتعمل، تعليق جديد، ...).
- `ComputeStreakAction`: بيحسب عدد الأيام المتتالية اللي فيها نشاط، مع سماحية يوم واحد يفوت من غير ما ينكسر الـ streak.
- `CheckAchievementsAction`: بيتشغّل تلقائي بعد كل نشاط، بيحسب تقدّمك في كل إنجاز معروف، ولو وصلت لحد الإنجاز لأول مرة بيفتحه ويبعتلك إشعار.
- `AchievementSeeder`: 6 إنجازات حقيقية (أول بورد، أول كارت، 10 مهام، 100 مهمة، streak 7 أيام، streak 30 يوم).

### 1.6 إشعارات حقيقية
`CreateNotificationAction` بقت بتتنادى فعليًا من: تعيين عضو على كارت، منشن (`@username`) في تعليق، دعوة لـ workspace، فتح إنجاز. وضفت أمر مجدول `cards:notify-due-soon` بيشتغل يوميًا وبيدور على الكروت اللي هتستحق خلال 24 ساعة.

### 1.7 حماية من الإغراق (Rate Limiting)
مسارات التسجيل/الدخول/OTP محدودة بـ 6 محاولات/دقيقة لكل IP.

### 1.8 دومين البروفايل الشخصي
بنيت `UserController` كامل من الصفر: بروفايل عام (`GET /users/{username}`)، تعديل البيانات، رفع صورة، تغيير الباسورد (بيلغي باقي الجلسات تلقائي)، عرض/إلغاء الجلسات، حذف الحساب (برفض لو لسه مالك workspace)، والإعدادات (السمة، اللغة، تفضيلات الإشعارات).

### 1.9 أعطاب تانية اتكشفت أثناء البناء والاختبار
دول مكنوش هيظهروا من غير تشغيل فعلي:

| العطب | التفصيل |
|---|---|
| `AchievementResource` | كان بيقرا عمود `unlocked` مش موجود بدل `unlocked_at` — الإنجاز يفضل "مقفول" حتى لو اتفتح |
| `MoveCardAction` / `ReorderColumnAction` | الـ type-hint كان `int $newPosition` بينما استراتيجية الـ gap بتنتج float |
| `Invitation::expires_at` | مش متعمول له cast لـ datetime — `isPast()` كان هيضرب على string |
| `UserFactory` | ملهاش `username` — أي seed كان بيفشل |
| `CardPolicy` | ملهاش method `create()` — كل إنشاء كارت كان بيرجع 403 |
| `BoardController` | كان بيقرا `workspace_id` من الـ body بدل الـ route |
| `InvitationResource` | ماكنش راجع الـ `token` — مفيش طريقة تقبلي بيها الدعوة |

### 1.10 التحقق
شغّلت المشروع فعليًا عن طريق Docker (Sail) — الـ PHP المتاح على الجهاز (8.1/8.3) مبيشغّلش المشروع أصلاً لأن `composer.lock` محتاج PHP ≥ 8.4.1. بعد التشغيل: 66 مسار سجلوا صح، migrate+seed نجحوا، وكل الاختبارات عدّت.

---

## 2. المرحلة الثانية: Real-time (Laravel Reverb)

### 2.1 ليه محتاجينه
عشان أي تغيير على البورد (كارت اتحرك، عمود اتعمل، تعليق جديد) يوصل لكل اللي فاتحين نفس البورد لحظيًا، من غير ما يعملوا refresh.

### 2.2 التثبيت
`composer require laravel/reverb` ثم `php artisan install:broadcasting --reverb` — ده وصّل `config/broadcasting.php`، حطّ متغيرات `REVERB_*` في `.env`، وعمل `routes/channels.php`. لاحظت إن التثبيت اللاإنترأكتيفي (`--no-interaction`) ماكملش توليد مفاتيح Reverb (APP_ID/APP_KEY/APP_SECRET) — ولّدتهم يدوي وضفتهم للـ `.env`.

### 2.3 صلاحيات القنوات (`routes/channels.php`)
ثلاث قنوات، كل واحدة بتتأكد إنك عضو في الـ workspace بنفس منطق الـ Policies:
- `board.{boardId}` — تغييرات البورد.
- `presence-board.{boardId}` — مين فاتح البورد دلوقتي (presence).
- `user.{userId}` — الإشعارات الشخصية، خاصة بيك انت بس.

### 2.4 الأحداث (Events)
8 كلاسات في `app/Events/` (`CardCreated`, `CardUpdated`, `CardDeleted`, `ColumnCreated`, `ColumnUpdated`, `ColumnDeleted`, `CommentCreated`, `NotificationCreated`) — كل واحد بيبعت لحظة ما العملية تحصل فعليًا من جوه نفس الـ controllers اللي بنيناها في المرحلة الأولى. استخدمت `ShouldBroadcastNow` (بث فوري، من غير Queue Worker) عشان نبسّط الإعداد.

نقطة تعليمية مهمة: استخدمت `->toOthers()` على بث الكارت/العمود/التعليق (عشان اللي عمل التغيير ما ياخدش نسخة من التغيير بتاعه هو نفسه راجعة له تاني)، لكن **مستخدمتهاش** على بث الإشعارات — لأن اليوزر ممكن يكون هو نفسه اللي هيستقبل الإشعار (زي فتح إنجاز بنفسه)، وهنا محتاج يوصله البث حتى لو هو نفسه مصدر الحدث.

### 2.5 السيرفر نفسه
Reverb محتاج عملية شغالة طول الوقت (مش زي الـ API العادي). ضفت `service` جديدة في `docker-compose.yml` اسمها `reverb`، بتشتغل على بورت 8080 جنب الـ mysql/redis/mailpit.

### 2.6 عطب اتكشف هنا (مش قبل كده)
جدولين الربط `card_assignee` و `card_label` كان فيهم عمود `id` UUID من غير أي قيمة افتراضية. ده كان شغال بالصدفة على SQLite (اللي كان مستخدم الأول) بس فشل فعليًا لما اختبرنا بـ MySQL — لأن `sync()`/`attach()` بيعملوا insert مباشر من غير Eloquent model يولّد الـ UUID. الحل: مسحت عمود الـ `id` خالص من الجدولين (جداول الربط البسيطة مش محتاجاه أصلاً).

### 2.7 التحقق
كتبت اختبارات بـ `Event::fake()` بتتأكد إن كل عملية (إنشاء عمود، تحريك كارت، مسح كارت، تعليق جديد، تعيين عضو) بتبعت الـ Event الصح على القناة الصح. الـ 5 اختبارات عدّوا، والـ container اتأكد إنه شغال وبيستقبل اتصالات على بورت 8080.

---

## 3. المرحلة الثالثة: الفوترة (Laravel Cashier + Stripe)

### 3.1 المشكلة الأساسية
كان فيه جدول `subscriptions` مصنوع يدوي، وعليه `User::subscription(): HasOne`. لكن Laravel Cashier (باكيدج الفوترة الرسمي) بيعرّف method اسمها بالظبط `subscription()` وبيتوقع يملك جدول اسمه بالظبط `subscriptions`. تركيب Cashier فوق الجدول القديم كان هيعمل تعارض مباشر — اسم method واحد، اسم جدول واحد، حاجتين مختلفين. القرار: نسيب Cashier يملك حالة الاشتراك بالكامل، مربوطة بـ Stripe حقيقي بدل عمود status كنا بنخمنه إحنا.

### 3.2 التثبيت والـ Migrations
`composer require laravel/cashier` ثم نشرت الـ migrations بتاعته (`vendor:publish --tag=cashier-migrations`). دي بتضيف أعمدة `stripe_id`, `pm_type`, `pm_last_four`, `trial_ends_at` على جدول `users`، وتعمل جدولين جداد `subscriptions` و `subscription_items`.

### 3.3 عطب تاني اتكشف
الـ migration الجاهزة من Cashier بتفترض إن `user_id` رقم عادي (`foreignId`) — لكن عندنا كل الـ IDs في المشروع UUID (`foreignUuid`). غيّرتها عشان تتماشى مع باقي المشروع. ده حاجة موثّقة رسميًا في Cashier نفسه كنقطة تخصيص متوقعة لو بتستخدم UUIDs.

### 3.4 التغييرات في الكود
- مسحت الجدول والموديل القديمين (`app/Models/Subscription.php` والـ migration بتاعته).
- `User` model: ضفت `use Laravel\Cashier\Billable;` وشلت الـ relation القديمة.
- `PlanLimits::planFor()`: بقى بيسأل `$user->subscribed('default', ...)` بدل ما يقرا عمود محلي.
- `SubscriptionController`: اتعاد كتابته بالكامل فوق Cashier API الحقيقي:
  - `POST /me/subscription` — بياخد `payment_method` (معرّف Stripe للبطاقة، مش رقم البطاقة نفسه — ده بيتجمع من الفرونت عن طريق Stripe.js).
  - `PATCH` — تغيير الخطة.
  - `DELETE` — إلغاء (بيفضل الاشتراك شغال لحد آخر فترة مدفوعة، مش إلغاء فوري).
  - `GET /me/invoices` — فواتير حقيقية من Stripe بدل القايمة الفاضية اللي كانت stub.
- مسار جديد `POST /api/stripe/webhook` — عشان أي حدث من Stripe نفسه (تجديد، فشل دفع، إلغاء) يوصلنا ويحدّث بياناتنا تلقائي.

### 3.5 التحقق (حقيقي مش تمثيلي)
كتبت اختبار بيستخدم `pm_card_visa` (كارت الاختبار الرسمي من Stripe، بيشتغل على أي test-mode secret key من غير كارت حقيقي)، عمل subscribe فعلي على حسابك في Stripe test mode، وأكّد إن:
1. البورد الرابع كان ممنوع على الخطة المجانية.
2. بعد الاشتراك، الخطة بقت "pro" فعليًا (Stripe رجّع كده، مش إحنا خمّنا).
3. البورد الرابع بقى مسموح.
4. بعد الإلغاء، الاشتراك دخل فترة سماح صحيحة (مش اتقفل فورًا).

### 3.6 Stripe CLI والـ Webhook
ثبّت Stripe CLI (كملف تنفيذي مباشر في `~/bin/stripe`، من غير sudo)، وشغّلت `stripe listen --forward-to localhost/api/stripe/webhook` كعملية دائمة في الخلفية. ده ولّد `STRIPE_WEBHOOK_SECRET` حطيته في `.env`. اختبرت بأحداث Stripe حقيقية (`stripe trigger customer.subscription.updated`) وكل الأحداث رجّعت 200 من الـ webhook بتاعنا.

---

## 4. الحالة النهائية

- **66 مسار API** فعلي، كلهم بيردّوا رد حقيقي (مفيش ولا واحد بيضرب 500).
- **17 اختبار** بتغطي: المسارات اللي كانت مكسورة، الصلاحيات، حدود الخطة، البحث، الإنجازات، البث اللحظي، والفوترة الحقيقية.
- **Real-time شغال فعليًا** عن طريق Reverb، container منفصل على بورت 8080.
- **Billing شغال فعليًا** مربوط بحساب Stripe test mode بتاعك، مع webhook متزامن لحظيًا عن طريق Stripe CLI.
- **Postman collection** محدّثة (67 request، 154 مثال نجاح/خطأ).

### مؤجل عمدًا (قرار واضح، مش نسيان)
- رابط استرجاع الباسورد الكلاسيكي (فيه تدفق OTP شغال بالفعل يغطي نفس الغرض).
- تحويل Stripe CLI listener لحل دائم في الإنتاج (لسه بيشتغل يدوي محليًا — للإنتاج هيحتاج webhook endpoint حقيقي متسجّل في Stripe Dashboard مباشرة).

---

## 5. إزاي تشغّلي وتختبري كل ده بنفسك

```bash
# تشغيل كل الـ containers (بما فيهم reverb الجديدة)
cd ~/taskflow && docker compose up -d

# لو عايزة الـ webhooks الحقيقية تتزامن وانتي بتجربي
# (STRIPE_SECRET من نفس الـ .env بتاعك — متحطيش المفتاح في أي ملف مشترك)
~/bin/stripe listen --forward-to localhost/api/stripe/webhook --api-key "$STRIPE_SECRET"

# تشغيل كل الاختبارات
docker compose exec laravel.test php artisan test

# قايمة كل المسارات
docker compose exec laravel.test php artisan route:list --path=api
```
