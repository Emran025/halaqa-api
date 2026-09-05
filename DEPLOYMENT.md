# Quran Halaqa Laravel Deployment

يعمل Workflow النشر من خلال `.github/workflows/deploy.yml` عند الدفع إلى الفرع `feature/session-mushaf-state` أو عند تشغيله يدويًا. صُمم هذا الفرع كبيئة تحقق متصلة بالكود، ولا يفعّل أي نشر من `main`.

## أسرار GitHub المطلوبة

| الاسم | إلزامي | الاستخدام |
|---|---:|---|
| `SERVER_SSH_KEY` | نعم | المفتاح الخاص للحساب الذي يملك مجلد النشر؛ لا يوضع في المستودع |
| `SERVER_KNOWN_HOSTS` | نعم | سطر أو أسطر host key موثوقة للخادم، ويُرفض استخدام `ssh-keyscan` التلقائي |
| `SERVER_HOST` | نعم | اسم المضيف أو عنوان الخادم |
| `SERVER_USERNAME` | نعم | حساب SSH الذي يملك مسار النشر |
| `PROJECT_DOMAIN` | نعم | اسم مجلد المشروع تحت `/home/<username>/web/` |
| `SERVER_PORT` | لا | منفذ SSH؛ الافتراضي `22` |
| `SERVER_WEBSOCKET_SERVICE` | لا | اسم خدمة Supervisor أو systemd الخاصة بـ`php artisan realtime:websocket`; إذا تُرك فارغًا يُنشر REST وتظهر ملاحظة بأن WebSocket لم يُعد تشغيله |

لا يستطيع Workflow قراءة أو إنشاء هذه الأسرار تلقائيًا من الكود. يجب إدخالها في إعدادات Secrets الخاصة بالمستودع من قبل مالك الخادم، مع عدم إرسال المفتاح الخاص في الرسائل أو commit.

## متطلبات الخادم

يجب أن يكون الخادم قادرًا على تشغيل PHP 8.2 أو أحدث مع امتداد `pdo_mysql` وامتدادات Laravel الأساسية، وأن يوفّر SSH و`rsync` و`crontab`. يجب أن يكون المسار `/home/<username>/web/<domain>/public_html` قابلًا للكتابة، وأن يكون ملف البيئة موجودًا قبل أول نشر في:

```text
/home/<username>/web/<domain>/private/.env
```

يجب أن يحتوي ملف البيئة على `APP_KEY` حقيقي و`APP_ENV=production` و`APP_DEBUG=false` و`APP_URL` الصحيح وإعدادات MySQL (`DB_CONNECTION=mysql` وبقية بيانات الاتصال) وإعدادات الجلسة والتخزين والطابور المناسبة للبيئة. لا يُرسل ملف `.env` من GitHub؛ يرفع Workflow الكود فقط ويصل الملف الخاص عبر symlink داخل release.

للتشغيل اللحظي، ينبغي إعداد خدمة خارجية عن عملية الطلبات العادية ولكنها من كود Laravel الداخلي نفسه لتشغيل الأمر:

```text
php artisan realtime:websocket --host=127.0.0.1 --port=8081
```

ويجب أن تكون الخدمة مهيأة باسم يطابق `SERVER_WEBSOCKET_SERVICE` إن أُريد أن يعيد Workflow تشغيلها بعد كل نشر. يظل الاتصال الإعلامي P2P وHost ICE فقط وفق سياسة المشروع؛ لا يضيف Workflow STUN أو TURN أو Relay أو Media Server.

## ما ينفذه Workflow

يمر النشر أولًا ببوابة جودة تعيد تثبيت SQLite وتشغيل migrations وseed والاختبارات وPint. بعد نجاحها، يثبت Composer production dependencies ويبني أصول Vite باستخدام `npm ci` و`npm run build`. ثم يرفع محتوى `halaqat_api` فقط، مع استبعاد `.env` و`storage` و`tests` وملفات التطوير والوثائق الداخلية.

بعد الرفع، يربط ملف البيئة الخاص، يجهز مجلدات Laravel، يشغل `optimize:clear` و`storage:link` و`migrate`، ثم يفحص عدد المستخدمين. إذا كانت قاعدة البيانات جديدة ولا تحتوي أي مستخدم، يشغل `php artisan db:seed --force` مرة واحدة لتهيئة البيانات الأولية؛ أما إذا كانت تحتوي مستخدمين فيتجاوز الـseeder بالكامل ويحافظ على بيانات الإنتاج. تغييرات البيانات اللاحقة يجب أن تأتي عبر migrations أو أوامر ترحيل مخصصة قابلة للتدقيق، وليس عبر إعادة تشغيل `DatabaseSeeder`. بعد ذلك ينشئ config/route/view caches. كما يسجل `php artisan schedule:run` في crontab كل دقيقة حتى يعمل الأمر المجدول `follow-up:process` كل ساعة وفق تعريف Laravel. يفشل النشر إذا غابت متطلبات البيئة أو فشل migration أو فشل إعداد الجدولة، ولا يخفي الأخطاء التشغيلية الحقيقية.

## ملاحظات السلامة

يستخدم Workflow host key مثبتًا من Secret بدل `ssh-keyscan` غير الموثوق. لا تُطبع قيم الأسرار في السجل، ولا يُضمَّن `.env` أو قاعدة SQLite أو `storage` في الرفع. لا يُنفذ أي تغيير على `main`؛ نقطة التشغيل الحالية هي فرع `feature/session-mushaf-state` فقط.
