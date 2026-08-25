# الشجرة المعيارية لمشروع Laravel

استخدم هذه الشجرة كخط أساس عند إنشاء ملفات جديدة. لا تُنشئ كل المجلدات فعليًا لمجرد مطابقة الشجرة؛ أنشئ المجلدات التي تحتاجها الميزة فقط.

```text
project-root/
├── app/
│   ├── Console/
│   │   └── Commands/
│   ├── Enums/
│   ├── Events/
│   ├── Exceptions/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/
│   │   │       └── V1/
│   │   │           ├── Auth/
│   │   │           ├── Halaqas/
│   │   │           ├── Registrations/
│   │   │           ├── LiveSessions/
│   │   │           └── Reports/
│   │   ├── Middleware/
│   │   ├── Requests/
│   │   │   └── Api/
│   │   │       └── V1/
│   │   │           ├── Auth/
│   │   │           ├── Halaqas/
│   │   │           ├── Registrations/
│   │   │           ├── LiveSessions/
│   │   │           └── Reports/
│   │   └── Resources/
│   │       └── Api/
│   │           └── V1/
│   │               ├── Auth/
│   │               ├── Halaqas/
│   │               ├── Registrations/
│   │               ├── LiveSessions/
│   │               └── Reports/
│   ├── Jobs/
│   ├── Listeners/
│   ├── Models/
│   ├── Notifications/
│   ├── Policies/
│   ├── Providers/
│   ├── Queries/
│   │   ├── Halaqas/
│   │   ├── LiveSessions/
│   │   └── Reports/
│   ├── Services/
│   │   ├── Auth/
│   │   ├── Halaqas/
│   │   ├── Registrations/
│   │   ├── LiveSessions/
│   │   ├── Mushaf/
│   │   └── Reports/
│   ├── Support/
│   │   ├── Data/
│   │   ├── Integrations/
│   │   └── Rules/
│   └── ValueObjects/
├── bootstrap/
├── config/
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── lang/
├── public/
├── resources/
│   ├── css/
│   ├── js/
│   └── views/
├── routes/
│   ├── api.php
│   ├── channels.php
│   ├── console.php
│   └── web.php
├── storage/
├── tests/
│   ├── Feature/
│   └── Unit/
├── composer.json
└── phpunit.xml
```

## قاعدة المسارات المهمة

يُقصد بعبارة **Resources** الخاصة بواجهات API المسار `app/Http/Resources`، وليس مجلد `resources/` الموجود في جذر Laravel. ويُقصد بعبارة **Requests** الخاصة بالتحقق المسار `app/Http/Requests`، وليس `app/Http/Request` بالمفرد.

تُستخدم `resources/` الجذرية للـviews وملفات الواجهة الأمامية، ولا توضع فيها API Resources. يوضع كل إصدار API داخل `Api/V1` أو الإصدار الفعلي المعتمد، مع الحفاظ على التوافق مع بنية المشروع القائمة.

## مثال ميزة الجلسات المباشرة

```text
app/
├── Http/
│   ├── Controllers/Api/V1/LiveSessions/
│   │   ├── CreateLiveSessionController.php
│   │   ├── AcceptLiveSessionController.php
│   │   └── FinalizeLiveSessionController.php
│   ├── Requests/Api/V1/LiveSessions/
│   │   ├── CreateLiveSessionRequest.php
│   │   └── FinalizeLiveSessionReportRequest.php
│   └── Resources/Api/V1/LiveSessions/
│       ├── LiveSessionResource.php
│       └── LiveSessionReportResource.php
├── Events/LiveSession/
├── Models/
│   ├── LiveSession.php
│   ├── SessionMistake.php
│   └── SessionReport.php
├── Policies/LiveSessionPolicy.php
├── Services/LiveSessions/
│   ├── CreateLiveSessionService.php
│   ├── FinalizeLiveSessionService.php
│   └── SyncSessionMistakeService.php
└── Jobs/LiveSession/
    └── PersistSessionAnalyticsJob.php
```
