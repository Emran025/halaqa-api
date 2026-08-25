# عقد قاعدة بيانات MySQL
## Quran Halaqa Live — الإصدار 1.0

هذه الوثيقة هي العقد المرجعي لقاعدة بيانات المشروع. تصف ما يجب تخزينه، نوع كل قيمة، العلاقة بين الكيانات، قواعد الخصوصية، الفهارس، وتسلسل Laravel Migrations. ملف `database_schema.sql` يمثل DDL مرجعيًا قابلًا للمراجعة، وليس تنفيذًا مباشرًا على قاعدة الإنتاج.

## قرارات قاعدة البيانات

| القرار | القيمة المعتمدة |
|---|---|
| المحرك | MySQL 8.0 أو أحدث مع InnoDB. |
| الترميز | `utf8mb4` و`utf8mb4_unicode_ci`. |
| المنطقة الزمنية | تخزين كل `DATETIME` بتوقيت UTC، مع حفظ منطقة المستخدم الزمنية في الحقول المخصصة للجدولة. |
| معرفات المجال | UUID نصي بطول 36 حرفًا بترميز ASCII؛ المعرفات المرجعية الثابتة للمصحف والأصناف رقمية عند الحاجة. |
| حذف البيانات | Soft Delete للمستخدمين والوثائق والأخطاء والبيانات التي تحتاج تاريخًا؛ لا يُحذف السجل التعليمي المعتمد حذفًا فعليًا. |
| الصوت والفيديو | لا تُخزن في MySQL؛ تنتقل P2P بين المعلم والطالب. |
| WebSocket | لا تُخزن SDP أو ICE أو إطارات الوسائط؛ تحفظ حالة الجلسة والأحداث الرسمية فقط. |
| الأدوار | `teacher` و`student` فقط. لا توجد جداول مشرفين أو إدارة مدرسية. |
| مصدر الحقيقة | بيانات Laravel الرسمية هي المصدر المعتمد، وملفات العميل المحلية مجرد مسودات قابلة للمزامنة. |

## خريطة المجال

```text
users
├── teacher_profiles ──< teacher_documents
├── student_profiles
├── halaqas ──< halaqa_memberships >── users(student)
├── registration_requests ── registration_request_profiles
├── student_availability_profiles ──< student_availability_slots
├── follow_up_plans ──< follow_up_plan_details ──< follow_up_items
├── live_sessions ──< session_mushaf_states
├── live_sessions ──< session_tasks ──< tracking_details ──< mistakes
├── daily_trackings ──< tracking_details
├── session_reports
├── task_notes
├── task_evaluations
├── notifications
├── idempotency_keys
└── audit_events

quran_editions ──< quran_surahs ──< quran_ayahs ──< quran_ayah_words
quran_editions ──< quran_pages
quran_editions ──< quran_range_units
tracking_types ──< follow_up_plan_details, session_tasks, tracking_details
tracking_units ──< follow_up_plan_details, quran_range_units
```

## المستخدمون والملفات

### `users`

يمثل الحساب الموحد للمعلم أو الطالب. لا ينشأ صف مستخدم ثالث.

| الحقل | النوع | Null | القاعدة |
|---|---|---:|---|
| `id` | `CHAR(36)` | لا | UUID والمفتاح الأساسي. |
| `role` | `VARCHAR(20)` | لا | `teacher` أو `student`. |
| `username` | `VARCHAR(60)` | نعم | فريد عند وجوده. |
| `name` | `VARCHAR(120)` | لا | الاسم الظاهر. |
| `email` | `VARCHAR(255)` | لا | فريد للحساب. |
| `password` | `VARCHAR(255)` | لا | قيمة Hash، ولا تعاد عبر Resource. |
| `gender` | `VARCHAR(20)` | لا | `male` أو `female`. |
| `birth_date` | `DATE` | لا | تاريخ الميلاد. |
| `country`, `city` | `VARCHAR(100)` | لا | الموقع العام. |
| `residence` | `VARCHAR(200)` | نعم | التفاصيل الإضافية للسكن. |
| `avatar_path` | `VARCHAR(500)` | نعم | مسار الصورة لا محتواها الثنائي. |
| `phone`, `phone_zone` | `VARCHAR` | لا | وسيلة التواصل الأساسية. |
| `whatsapp_phone`, `whatsapp_zone` | `VARCHAR` | نعم | WhatsApp عند توفره. |
| `status` | `VARCHAR(20)` | لا | `active`, `inactive`, `suspended`. |
| `email_verified_at`, `last_login_at` | `DATETIME` | نعم | حالة وتاريخ الاستخدام. |
| `deleted_at` | `DATETIME` | نعم | حذف منطقي. |

### `teacher_profiles`

| الحقل | النوع | Null | القاعدة |
|---|---|---:|---|
| `user_id` | `CHAR(36)` | لا | PK وFK إلى مستخدم بدور teacher. |
| `teacher_code` | `VARCHAR(40)` | لا | فريد ويستخدم للتوجيه الاختياري للطلبات. |
| `qualification` | `VARCHAR(250)` | لا | المؤهل المختصر. |
| `experience_years` | `SMALLINT UNSIGNED` | لا | من 0 إلى 80. |
| `bio` | `TEXT` | نعم | السيرة أو التعريف. |
| `available_time` | `TIME` | نعم | الوقت المعتاد العام للمعلم. |
| `max_halaqas` | `SMALLINT UNSIGNED` | لا | صفر يعني لا يوجد حد محدد. |

القدرة الفعلية للمعلم تحسب من `max_halaqas` وعدد الحلقات الفعالة بواسطة Query/Service، ولا تعتمد على قيمة مخزنة قابلة للتقادم.

### `student_profiles`

تخزن البيانات التعليمية الحالية للطالب بعد إنشاء الحساب، بينما تحفظ نسخة بيانات الطلب وقت التسجيل في `registration_request_profiles`.

| الحقل | النوع | Null | القاعدة |
|---|---|---:|---|
| `user_id` | `CHAR(36)` | لا | PK وFK إلى مستخدم بدور student. |
| `memorization_level` | `VARCHAR(120)` | نعم | المستوى السابق أو الحالي. |
| `review_level` | `VARCHAR(120)` | نعم | مستوى المراجعة. |
| `memorized_juz_count` | `DECIMAL(4,1) UNSIGNED` | نعم | من 0 إلى 30. |
| `previous_memorization_notes` | `TEXT` | نعم | وصف الخبرة أو الملاحظات السابقة. |
| `stop_reasons` | `TEXT` | نعم | أسباب التوقف السابقة عند وجودها. |
| `bio` | `TEXT` | نعم | تعريف الطالب. |

### `teacher_documents`

يخزن بيانات المؤهل والوثيقة دون جعل الملف شرطًا لموافقة إدارية.

| الحقل | النوع | Null | القاعدة |
|---|---|---:|---|
| `id` | `BIGINT UNSIGNED` | لا | PK متزايد. |
| `teacher_id` | `CHAR(36)` | لا | FK إلى المعلم. |
| `name` | `VARCHAR(250)` | لا | اسم الوثيقة. |
| `certificate_type` | `VARCHAR(100)` | لا | التصنيف. |
| `certificate_type_other` | `VARCHAR(150)` | نعم | يستخدم عند التصنيف الآخر. |
| `riwayah` | `VARCHAR(100)` | نعم | الرواية عند انطباقها. |
| `issuing_place` | `VARCHAR(200)` | نعم | جهة الإصدار. |
| `issuing_date` | `DATE` | نعم | تاريخ الإصدار. |
| `storage_disk`, `storage_path` | `VARCHAR` | نعم | مرجع الملف، وليس الملف داخل MySQL. |
| `mime_type`, `file_size_bytes` | `VARCHAR`, `BIGINT` | نعم | بيانات فنية اختيارية للملف. |
| `deleted_at` | `DATETIME` | نعم | حذف منطقي. |

## الحلقات والعضويات

### `halaqas`

| الحقل | النوع | Null | القاعدة |
|---|---|---:|---|
| `id` | `CHAR(36)` | لا | UUID. |
| `teacher_id` | `CHAR(36)` | لا | المعلم المالك. |
| `name` | `VARCHAR(150)` | لا | اسم الحلقة. |
| `description` | `VARCHAR(1000)` | نعم | وصف الحلقة الظاهر ضمن الصلاحية. |
| `gender` | `VARCHAR(20)` | لا | نطاق الجنس. |
| `country`, `residence` | `VARCHAR` | لا | النطاق الجغرافي المعلن. |
| `avatar_path` | `VARCHAR(500)` | نعم | صورة الحلقة. |
| `status` | `VARCHAR(20)` | لا | `active` أو `inactive`. |
| `max_students` | `SMALLINT UNSIGNED` | نعم | السعة، وNull يعني غير محددة. |
| `timezone` | `VARCHAR(64)` | لا | منطقة الحلقة الافتراضية. |

### `halaqa_memberships`

تربط الطالب بالحلقة وتحفظ تاريخ الانضمام والمغادرة. يسمح التاريخ بأكثر من عضوية غير فعالة، ويضمن المفتاح المولد وجود عضوية فعالة واحدة فقط للطالب في النطاق الحالي.

| الحقل | النوع | Null | القاعدة |
|---|---|---:|---|
| `id` | `CHAR(36)` | لا | UUID. |
| `halaqa_id`, `student_id` | `CHAR(36)` | لا | طرفا العلاقة. |
| `status` | `VARCHAR(20)` | لا | `active`, `inactive`, `removed`. |
| `joined_at`, `left_at` | `DATETIME` | لا/نعم | حدود العلاقة. |
| `active_student_key` | Generated `TINYINT` | محسوب | 1 للحالة active وNull لغيرها، مع Unique لمنع ازدواج العضوية النشطة. |

سعة الحلقة وتعارض إسناد الطالب وامتلاك المعلم للحلقة قرارات Policy/Service، مع وجود الفهارس اللازمة للاستعلام السريع.

## التسجيل والخصوصية

### `registration_requests`

| الحقل | النوع | Null | القاعدة |
|---|---|---:|---|
| `id` | `CHAR(36)` | لا | UUID. |
| `student_id` | `CHAR(36)` | لا | صاحب الطلب. |
| `teacher_id` | `CHAR(36)` | نعم | يملأ عند وجود كود معلم. |
| `teacher_code_snapshot` | `VARCHAR(40)` | نعم | حفظ الكود المستخدم وقت الطلب. |
| `requested_halaqa_id` | `CHAR(36)` | نعم | حلقة محددة عند اختيارها. |
| `routing_mode` | `VARCHAR(30)` | لا | `specific_teacher` أو `all_available_teachers`. |
| `state` | `VARCHAR(30)` | لا | `pending`, `completion_requested`, `accepted`, `rejected`, `withdrawn`, `cancelled`. |
| `public_message` | `VARCHAR(1000)` | نعم | رسالة عامة للمعلم. |
| `decision_note` | `VARCHAR(2000)` | نعم | ملاحظة القرار. |
| `decided_by_teacher_id` | `CHAR(36)` | نعم | المعلم الذي اتخذ القرار. |
| `submitted_at`, `decided_at`, `accepted_at`, `withdrawn_at` | `DATETIME` | نعم بحسب الحالة | خط زمني للطلب. |

### `registration_request_profiles`

يحفظ Snapshot كاملًا للبيانات التي قدمها الطالب وقت الطلب، ولا يعاد كاملًا من قائمة الطلبات العامة.

يشمل: الجنس، تاريخ الميلاد، الدولة، المدينة، السكن، الهاتف ورمز الدولة، WhatsApp ورموزه، مستوى الحفظ، مستوى المراجعة، عدد الأجزاء، الملاحظات السابقة، والسيرة المختصرة.

### قاعدة الإسقاط الآمن

قبل القبول تستخدم Laravel Resource منفصلة تعيد `ApplicantPublicSummary` فقط. بعد القبول والعلاقة التعليمية يمكن لـResource تفصيلي إعادة البيانات وفق Policy. لا تعتمد الخصوصية على WPF؛ يجب أن يحجبها Query/Resource في الخادم.

## الحضور والخطة والجدولة

### `student_availability_profiles` و`student_availability_slots`

يخزن الأول المنطقة الزمنية ومدة الجلسة المفضلة، ويخزن الثاني أيام الأسبوع والفترة الزمنية. قيمة `day_of_week` من 0 للأحد إلى 6 للسبت، ويمنع القيد أن تكون نهاية الفترة قبل بدايتها أو مساوية لها.

### `follow_up_plans`

| الحقل | النوع | القاعدة |
|---|---|---|
| `id` | UUID | معرف الخطة. |
| `student_id` | UUID | الطالب. |
| `created_by_user_id` | UUID | الطالب أو المعلم. |
| `source_registration_request_id` | UUID nullable | مصدر الخطة الأولية. |
| `frequency` | Enum نصي | `daily`, `onceAWeek`, `twiceAWeek`, `thriceAWeek`. |
| `status` | Enum نصي | `draft`, `proposed`, `active`, `paused`, `archived`. |
| `timezone` | String | منطقة تنفيذ الخطة. |
| `starts_on`, `ends_on` | Date nullable | مدة الخطة. |
| `version` | Unsigned int | إصدار الخطة للتتبع. |
| `approved_by_user_id`, `approved_at` | nullable | اعتماد المعلم عند الحاجة. |

### `follow_up_plan_details`

يمثل كل بند في الخطة: `tracking_type_id` للحفظ أو المراجعة أو التلاوة، `tracking_unit_id` للجزء أو الحزب أو نصف الحزب أو ربع الحزب أو الصفحة، `amount` للكمية، و`sort_order` للترتيب.

### `follow_up_items`

يمثل الموعد الفعلي الناتج عن الخطة. يحتوي على الطالب والحلقة إن وجدت، وقت التنفيذ، المنطقة الزمنية، الحالة، وقت الإكمال أو التجاوز، سبب التجاوز، رابط إعادة الجدولة، ووقت إرسال التنبيه. وتحتفظ الأعمدة الداخلية `last_client_operation_id` و`last_operation_by_user_id` و`last_operation_type` بآخر عملية قابلة لإعادة المحاولة، بينما يحفظ `reschedule_reason` سبب إنشاء الموعد الجديد. يضمن UUID الفريد عدم تكرار العملية، وتتحقق Service من هوية صاحبها وحالتها، ولا ينشئ العناصر Controller أو Cron closure مباشرة.

## المصحف المرجعي

المصحف بيانات مرجعية للعرض والربط، ويجب تحميله من Seed/Import موثق لا من إدخالات المستخدم.

| الجدول | الغرض |
|---|---|
| `quran_editions` | تعريف نسخة المصحف والنص والرواية/الإصدار. |
| `quran_surahs` | السورة واسمها وأسماءها وعدد آياتها وأول صفحة ونوع النزول. |
| `quran_pages` | الصفحة والنص المعروض. |
| `quran_ayahs` | رقم الآية العام، النص العثماني، النص الإملائي، رقمها في السورة، الصفحة، الجزء، والسجدة. |
| `quran_ayah_words` | كلمات الآية مع `word_index` للنقر ورصد الخطأ. |
| `quran_range_units` | حدود الجزء والحزب ونصف الحزب وربع الحزب والصفحة من سورة/آية إلى سورة/آية مع `gap`. |

المفتاح المركب `(id, edition_id)` في الآيات والسور يمنع خلط موضع من إصدار مصحف بموضع من إصدار آخر.

## الجلسات والمهام

### `live_sessions`

يربط الحلقة والمعلم والطالب وعنصر المتابعة، ويخزن `task_type_id` وحالة الجلسة والجدولة وخطها الزمني. يفرض الحقل `direct_p2p_only = TRUE` داخل قاعدة البيانات، ويضاف إلى Policy تحقق أن المعلم والطالب طرفا الجلسة.

حالات الجلسة هي `requested`, `accepted`, `connecting`, `direct_negotiation`, `connected`, `weak_connection`, `reconnecting`, `disconnected`, `direct_connection_unavailable`, `ended`, `cancelled`, و`rejected`.

### `session_mushaf_states`

يحفظ آخر حالة رسمية للمصحف داخل الجلسة: إصدار المصحف، الصفحة الحالية، السورة والآية الحالية عند وجودهما، نطاق التلاوة، المستخدم الذي حفظ الحالة، رقم الإصدار، `last_client_operation_id` الاختياري لمنع تكرار retry، والطوابع الزمنية. لا يحفظ هذا الجدول أي نص صوتي أو فيديو أو SDP أو ICE. تكون العلاقة واحدًا لواحد مع `live_sessions`، وتتحقق Service من أن السورة والآيات والنطاق تنتمي إلى `edition_id` نفسه.

### `session_tasks`

يخزن بنود الجلسة بالترتيب، نوع التتبع، النطاق المخطط، الكمية المخططة والفعلية، الحالة، التعليق، الدرجة، الفجوة، ووقت البداية والنهاية. حالات المهمة هي `draft`, `in_progress`, `completed`, `skipped`, و`cancelled`، ويطابقها قيد `chk_session_task_state`. يرتبط نطاق البداية والنهاية بـ`quran_range_units` بدل تخزين اسم السورة وحده.

## المتابعة اليومية والتفاصيل

### `daily_trackings`

يمثل سجل يوم الطالب: العضوية، التاريخ، نوع الحضور `present` أو `absent` أو `excused` أو `late`، الملاحظة، والملاحظة السلوكية. يفرض Unique على `(student_id, date)` لمنع إنشاء سجلين ليوم واحد.

### `tracking_details`

يحفظ نوع التتبع، وحدة البداية والنهاية، الكمية الفعلية، الحالة، التعليق، الدرجة، والفجوة، ويرتبط اختياريًا بمهمة جلسة مباشرة. الحقل `uuid` هو المعرف الذي تستخدمه الأخطاء في الربط.

### `mistakes`

| الحقل | النوع | الغرض |
|---|---|---|
| `id` | UUID | معرف الخطأ. |
| `tracking_detail_id` | UUID | سجل التفصيل الأب. |
| `ayah_id`, `edition_id` | مرجع مركب | الآية في إصدار المصحف. |
| `word_index` | Unsigned smallint | ترتيب الكلمة داخل الآية. |
| `mistake_type_id` | Tinyint | نوع الخطأ. |
| `source_role` | Enum | `teacher` أو `student`. |
| `note` | `VARCHAR(2000)` nullable | الملاحظة التفصيلية. |
| `created_by_user_id` | UUID | صاحب التسجيل. |
| `deleted_at` | `DATETIME` nullable | حذف منطقي. |
| `active_mistake_key` | Generated `TINYINT` | 1 للسجل غير المحذوف منطقيًا وNull للسجلات المحذوفة، مع مفتاح فريد لمنع تكرار الخطأ النشط على الموضع نفسه. |

تأتي أنواع الأخطاء من كود Shafeea: `none` غير مصنف، `memory` نسيان، `grammar` نحوي، `pronunciation` مخارج حروف، و`timing` وقف وابتداء. القيد الفريد يمنع تكرار النوع نفسه على الكلمة نفسها داخل تفصيل التتبع.

## الملاحظات والتقييمات والتقارير

`task_notes` يدعم الملاحظة العامة أو المرتبطة بآية وكلمة، ويحتوي `client_operation_id` فريدًا لمنع إنشاء الملاحظة مرتين. و`task_evaluations` يسمح بتقييم المعلم وتقييم الطالب في السجل نفسه مع منع تكرار تقييم المقيم للمهمة.

`session_reports` تقرير واحد لكل جلسة، مع الحالة والملخص والمدة وعدد المهام والأخطاء وإحصاء الأنواع ونسخة التقرير واعتماد المعلم وتأكيد الطالب وإعادة الفتح وسببها. ويحفظ `teacher_approval_note` ملاحظة الاعتماد، بينما تحفظ أعمدة `last_client_operation_id` و`last_operation_by_user_id` و`last_operation_type` آخر عملية قابلة لإعادة المحاولة بفهرس UUID فريد. يمثل `mistake_counts` JSON قائمة عناصر ذات `mistake_type` و`count` كما يحددها مخطط `MistakeCount` في OpenAPI، وليس خريطة مفاتيح غير معرفة. لا تنتقل الحالة إلى مكتملة إلا عبر Service اعتماد التقرير ثم إقرار الطالب.

## الإشعارات والتتبع التقني

`notifications` يخزن إشعار المستخدم ونوعه وعنوانه ونصه وحقل `payload` JSON الصريح ووقت قراءته. يقتصر `type` على `registration_request`, `session_scheduled`, `session_started`, `session_ended`, `report_ready`, `follow_up_due`, `reminder`, و`system`، مع فهرس `(user_id, read_at, created_at)`. لا يستخدم العقد حقلًا عامًا باسم `data`. ويخزن `idempotency_keys` نتيجة العمليات القابلة لإعادة المحاولة حتى لا يتكرر الطلب عند ضعف الاتصال.

`audit_events` ليس سجلًا تعليميًا؛ هو سجل تقني/تشغيلي محدود لتتبع من نفذ قرارًا ومتى وعلى أي مورد، ولا يخزن SDP أو ICE أو الصوت أو الفيديو.

## مواءمة Laravel

| طبقة Laravel | الجداول/المسؤولية |
|---|---|
| `app/Models` | Model لكل جدول مجال، مع العلاقات وcasts وScopes البسيطة. |
| `app/Enums` | Role، SessionState، ReportState، TrackingType، TrackingUnit، MistakeType، AttendanceType. |
| `app/Http/Requests` | قواعد التسجيل، الخطط، الجلسات، الأخطاء، التقييمات، والتقارير. |
| `app/Http/Resources` | الإسقاط العام للمتقدم، الإسقاط التفصيلي بعد العلاقة، وموارد الحلقات والجلسات والتقارير. |
| `app/Services` | قبول التسجيل، إنشاء الخطة، توليد عناصر المتابعة، إنشاء الجلسة، مزامنة الخطأ، اعتماد التقرير. |
| `app/Queries` | صندوق طلبات المعلم، قائمة المتابعة، تقدم الطالب، التقارير والأخطاء التاريخية. |
| `app/Policies` | ملكية الحلقة، علاقة المعلم بالطالب، صلاحية التقرير والجلسة. |
| `app/Realtime` | WebSocket الداخلي والإشارة فقط، دون تغيير قاعدة البيانات مباشرة من Frame Codec. |
| `database/migrations` | Migration مرتبة حسب الاعتماد الموضح في قسم التسلسل. |

## ترتيب Laravel Migrations

ينفذ العقد على دفعات مترابطة بالترتيب التالي: `users`، ثم ملفات المعلمين والطلاب، ثم أنواع التتبع والوحدات، ثم الحلقات والعضويات، ثم طلبات التسجيل وSnapshot الملف، ثم توافر الطالب، ثم بيانات المصحف المرجعية، ثم الخطط وتفاصيلها، ثم عناصر المتابعة، ثم الجلسات، ثم حالة مصحف الجلسة، ثم المهام، ثم المتابعة اليومية وتفاصيلها والأخطاء، ثم الملاحظات والتقييمات، ثم التقارير والإشعارات، وأخيرًا idempotency والتدقيق والرموز.

## قواعد الاختبار

يجب اختبار قيد أن الطالب لا يملك عضويتين فعالتين، وقيد سعة الحلقة، واكتمال حالة المصحف الرسمية وتطابق `edition_id` مع السورة والآيات والنطاق، وتوجيه الطلب بالكود أو بدونه، وإخفاء البيانات الحساسة قبل القبول، وإظهارها لمعلم العلاقة فقط بعد القبول، وعدم كشف الطالب لمعلم آخر، وتوليد عناصر المتابعة حسب التكرار، ومنع تكرار الخطأ على موضع واحد، وسلامة استعادة المسودة، واعتماد التقرير مرة واحدة، ومنع تخزين أي بيانات وسائط أو SDP أو ICE.

## مراجع الكود المصدرية

استُخدمت كيانات الكود الفعلية من مستودع [Shafeea](https://github.com/Emran025/shafeea_teach)، وبالأخص نماذج الطالب والمعلم والحلقة والخطة والتتبع والخطأ والمصحف. لم تُستخدم ملفات المتطلبات الوصفية لاستخراج الحقول؛ وقد جرى تسجيل الحقول المصدرية في `shafeea_code_findings.md` خارج عقد قاعدة البيانات.


## بنية Laravel للعمليات المؤجلة

تستخدم `jobs` و`failed_jobs` عند اختيار `QUEUE_CONNECTION=database` لتشغيل العمليات المؤجلة داخل Laravel، مثل توليد عناصر المتابعة وإرسال التنبيهات وإعادة المحاولات. لا تمثل هذه الجداول طرفًا ثالثًا ولا تنقل الوسائط. يجب أن يضع Job منطق التنفيذ داخل Service قابلة للاختبار، وأن يكون قابلاً لإعادة التنفيذ دون تكرار باستخدام المعاملات و`idempotency_keys`.


### Snapshot أوقات الحضور داخل طلب التسجيل

لأن أوقات الحضور والتكرار جزء من بيانات التسجيل، لا يكفي حفظها في ملف الطالب الحالي فقط. لذلك تحفظ `registration_request_availability` المنطقة الزمنية ومدة الجلسة المفضلة، وتحفظ `registration_request_availability_slots` أيام الأسبوع والفترات الزمنية كما قدمها الطالب. بعد القبول تنسخ Service هذه القيم إلى `student_availability_profiles` و`student_availability_slots`، مع بقاء Snapshot الطلب ثابتًا لأغراض التتبع والمقارنة.
