# عقد ميزة إشعارات المستخدم

## تعريف الميزة

- **اسم الميزة:** قائمة إشعارات المستخدم وتعليم القراءة.
- **الفاعل الأساسي:** `teacher` أو `student` فقط.
- **النتيجة المطلوبة:** تمكين المستخدم من رؤية إشعاراته الرسمية، تصفية غير المقروء منها، وتعليم إشعار واحد أو جميع إشعاراته كمقروءة.
- **نطاق API:** `GET /notifications`، `POST /notifications/{notificationId}/read`، و`POST /notifications/read-all`.
- **مخطط الطلب:** query parameters `unread_only`, `page`, و`per_page` المعرفة في OpenAPI؛ لا يوجد body لمساري التعليم.
- **مفتاح الاستجابة الجذري:** `notifications` للقائمة، ولا يعاد غلاف `data`.
- **هل العملية متزامنة أم مؤجلة؟** متزامنة.
- **هل البيانات رسمية محفوظة أم حدث لحظي مؤقت؟** الإشعار وpayload بيانات رسمية محفوظة؛ لا يحمل وسائط أو SDP أو ICE.
- **آلية منع التكرار:** التعليم عملية idempotent بذاتها؛ تحديث `read_at` لا يكرر أثرًا، وتعليم الكل يحدّث غير المقروء فقط. أما إشعارات الأحداث فتستخدم `dedupe_key` إلزاميًا وفريدًا على مستوى الجدول.

## المدخلات

| الاسم | النوع | مطلوب؟ | قاعدة التحقق |
|---|---|---:|---|
| `unread_only` | boolean | اختياري | القيمة الافتراضية `false`، وتطبع قيم query النصية إلى boolean قبل التحقق. |
| `page` | integer | اختياري | حد أدنى 1. |
| `per_page` | integer | اختياري | من 1 إلى 100. |
| `notificationId` | UUID | نعم في مسار التعليم الفردي | model binding؛ المورد غير الموجود أو غير المملوك يعاد كـ404. |
| `dedupe_key` | string | نعم داخليًا | مفتاح فريد للإشعار الناتج عن حدث ومتلقيه، ولا يظهر في Resource. |

## المخرجات

| العنصر | Resource | الحقول المسموح بها | المصدر أو الاشتقاق |
|---|---|---|---|
| إشعار | `NotificationResource` | `id`, `type`, `title`, `body`, `payload`, `read_at`, `created_at` | من جدول `notifications` مع payload مضبوط إلى مفاتيح NotificationPayload المعلنة. |
| قائمة الإشعارات | `NotificationCollectionResource` | `notifications` و`meta` | استعلام `NotificationService` مقيد بـ`user_id`، مع ترتيب الأحدث وترقيم الصفحات. |
| التعليم | استجابة HTTP 204 | لا يوجد body | يغير `read_at` للمالك فقط. |

## الصلاحيات

- القائمة متاحة لدوري `teacher` و`student`، وكل مستخدم يرى إشعاراته فقط.
- تعليم الإشعار يمر عبر `NotificationPolicy`؛ عدم تطابق المالك لا يكشف الإشعار ويعاد كـ404.
- تعليم الكل يطبق شرط `user_id` داخل Service ولا يقبل معرف مستخدم من العميل.

## توزيع الملفات

```text
app/Http/Controllers/Api/V1/Notifications/ListNotificationsController.php
app/Http/Controllers/Api/V1/Notifications/MarkNotificationReadController.php
app/Http/Controllers/Api/V1/Notifications/MarkAllNotificationsReadController.php
app/Http/Requests/Api/V1/Notifications/ListNotificationsRequest.php
app/Http/Resources/Api/V1/Notifications/NotificationResource.php
app/Http/Resources/Api/V1/Notifications/NotificationCollectionResource.php
app/Models/Notification.php
app/Policies/NotificationPolicy.php
app/Services/Notifications/NotificationService.php
app/Events/Notifications/SessionScheduled.php
app/Events/Notifications/SessionEnded.php
app/Events/Notifications/SessionReportApproved.php
app/Listeners/Notifications/CreateSessionScheduledNotification.php
app/Listeners/Notifications/CreateSessionEndedNotifications.php
app/Listeners/Notifications/CreateSessionReportReadyNotification.php
database/migrations/2026_08_25_000019_create_notifications_table.php
```

## مسار التنفيذ

```text
ListNotificationsRequest -> NotificationPolicy::viewAny -> ListNotificationsController -> NotificationService -> Notification model -> NotificationCollectionResource
Notification model binding -> NotificationPolicy::markRead -> MarkNotificationReadController -> NotificationService -> 204
Authenticated user -> MarkAllNotificationsReadController -> NotificationService -> 204
```

## قواعد القرار

تستخدم `NotificationService` لأن القائمة تحتاج filter وpagination، ولأن عمليتي التعليم تحتاجان تقييد الملكية والمعاملة والتحديث الآمن. لا تستخدم هذه الشريحة Laravel Broadcasting أو مزودًا خارجيًا؛ الإشعارات المخزنة هنا نسخة رسمية قابلة للعرض، بينما الأحداث اللحظية مؤجلة إلى WebSocket الداخلي الموثق بعقد realtime.

## الاختبارات المطلوبة

- عزل قائمة كل مستخدم عن إشعارات المستخدمين الآخرين.
- فلتر `unread_only` وترقيم الصفحات وبنية payload الصريحة.
- رفض query fields غير المعروفة وقيم الحدود غير الصالحة.
- تعليم إشعار واحد مرتين دون تغيير إضافي وإرجاع 204 في المرتين.
- تعليم جميع إشعارات المستخدم دون لمس إشعارات مستخدم آخر.
- إعادة 404 عند محاولة مستخدم قراءة إشعار غير مملوك أو غير موجود.
- اتساق migration/model/resource مع DDL وNotificationPayload في OpenAPI.
- عدم تخزين أي وسائط أو SDP أو ICE في payload أو جدول الإشعارات.
