# عقد ميزة Laravel: الحسابات والحلقات والتسجيل والعضويات

## تعريف الميزة

- **اسم الميزة:** الحسابات والمصادقة وإدارة الحلقات وطلبات التسجيل والعضويات.
- **الفاعل الأساسي:** `teacher` أو `student` فقط.
- **النتيجة المطلوبة:** إنشاء الحسابات وتسجيل الدخول، إنشاء المعلم لحلقاته، تقديم الطالب طلبًا عامًا أو موجهًا، اتخاذ المعلم قرارًا، ثم إسناد الطالب إلى الحلقة مع عزل البيانات الحساسة قبل القبول.
- **نطاق API:** `/api/v1/auth/*`, `/api/v1/me`, `/api/v1/halaqas/*`, `/api/v1/registration-requests/*`.
- **مخطط الطلب:** `RegisterStudentRequest`, `RegisterTeacherRequest`, `CreateHalaqaInput`, `UpdateHalaqaInput`, `AssignStudentInput`, `UpdateMembershipInput`, `CreateRegistrationInput`, `DecisionNoteInput`, `CompletionRequestInput` من `.agent/openapi.yaml`.
- **مفتاح الاستجابة الجذري:** `user`, `halaqa`, `membership`, `registration_request`, `halaqas`, `students`, `registration_requests`, أو `error`. يُمنع `data`.
- **هل العملية متزامنة أم مؤجلة؟** متزامنة داخل معاملات قاعدة البيانات؛ لا يوجد Job لهذه الشريحة.
- **هل البيانات رسمية محفوظة أم حدث لحظي مؤقت؟** الحسابات والحلقات والعضويات وطلبات التسجيل سجلات رسمية محفوظة. لا توجد وسائط أو إشارات WebRTC في هذه الشريحة.
- **آلية منع التكرار:** `client_operation_id` فريد على الحسابات، وفريد لكل طالب داخل طلب التسجيل؛ إعادة نفس عملية التسجيل تعيد المورد المنشأ ولا تنشئ سجلًا ثانيًا. التسجيل المصادق عليه يمنع طلبًا مفتوحًا ثانيًا للطالب.

## المدخلات

| الاسم | النوع | مطلوب؟ | قاعدة التحقق |
|---|---|---:|---|
| `client_operation_id` | UUID | نعم في التسجيل | فريد على حساب التسجيل أو طلب الطالب |
| `name`, `gender`, `country`, `residence` | string/enum | نعم في الحلقة | حدود OpenAPI، والجنس `male` أو `female` |
| `max_students` | integer/null | اختياري | أكبر من أو يساوي 1 عند وجوده |
| `student_id` | UUID | نعم للإسناد | مستخدم نشط بدور `student` |
| `status` | enum | نعم عند قرار العضوية | `active`, `inactive`, `removed` |
| `profile`, `attendance_preferences`, `follow_up_plan` | objects | نعم في طلب التسجيل | بنية صريحة وحقول متداخلة محددة، دون مفاتيح مجهولة |
| `note`, `message` | string/null | اختياري | حدود OpenAPI، ورفض الحقول الزائدة |

## المخرجات

| العنصر | Resource | الحقول المسموح بها | المصدر أو الاشتقاق |
|---|---|---|---|
| الحساب | `UserResource`, `AuthResponseResource` | `user`, `token`, `token_type`, `expires_at` | User وSanctum |
| الحلقة | `HalaqaResource`, `HalaqaResponseResource` | `halaqa` مع teacher وstudent_count وavailable_capacity | Halaqa، عضوياتها النشطة، وTeacherPublicSummary |
| العضوية | `MembershipResource`, `MembershipResponseResource` | `membership` مع الطالب والحالة والتواريخ | HalaqaMembership |
| الطلب | `RegistrationRequestResource`, `RegistrationResponseResource` | `registration_request` وحقول الرؤية والحالة | RegistrationRequest وsnapshot؛ التفاصيل الحساسة null قبل القبول للمعلم |
| القوائم | `HalaqaCollectionResource`, `StudentCollectionResource`, `RegistrationCollectionResource` | مفتاح المجال و`pagination` | paginator مع Query محدد بحسب الفاعل |
| التعارض | معالج `ApiConflictException` | `error.code`, `error.message`, `error.details` | حالات السعة، التكرار، الحالة، أو عدم توافق التوجيه |

لا تُخرج الموارد غلافًا باسم `data`. حقول الطالب التفصيلية لا تظهر للمعلم العام قبل القبول؛ بعد القبول تظهر فقط للمعلم صاحب العلاقة، وللطالب نفسه.

## الصلاحيات

- **المستخدم المسموح له:** الحساب المصادق النشط بدور `teacher` أو `student`.
- **علاقة المستخدم بالمورد:** المعلم يملك حلقاته ويدير عضوياتها؛ الطالب يرى حلقته النشطة وطلباته الخاصة؛ المعلم المؤهل يرى ملخصات الطلبات العامة فقط.
- **الإسقاط العام والحقول الحساسة:** قبل القبول يعاد `student_summary` فقط للمعلم، وتكون `profile`, `previous_memorization`, `attendance_preferences`, و`follow_up_plan` فارغة.
- **Policy/Gate المطلوب:** `HalaqaPolicy`, `HalaqaMembershipPolicy`, `RegistrationRequestPolicy` عبر `Gate::authorize`.
- **هل يتغير الوصول بعد القبول أو إنشاء العضوية؟** نعم؛ قبول الطلب وربط `teacher_id` أو إنشاء عضوية فعالة يفتحان العلاقة المصرح بها، ولا يفتحانها لبقية المعلمين.

## توزيع الملفات

```text
app/Http/Controllers/Api/V1/Halaqas/*
app/Http/Controllers/Api/V1/Memberships/*
app/Http/Controllers/Api/V1/Registrations/*
app/Http/Requests/Api/V1/Halaqas/*
app/Http/Requests/Api/V1/Memberships/*
app/Http/Requests/Api/V1/Registrations/*
app/Http/Resources/Api/V1/Halaqas/*
app/Http/Resources/Api/V1/Memberships/*
app/Http/Resources/Api/V1/Registrations/*
app/Services/Halaqas/HalaqaService.php
app/Services/Memberships/MembershipService.php
app/Services/Registrations/RegistrationService.php
app/Policies/HalaqaPolicy.php
app/Policies/HalaqaMembershipPolicy.php
app/Policies/RegistrationRequestPolicy.php
app/Exceptions/ApiConflictException.php
```

## مسار التنفيذ

```text
Request -> Gate/Policy -> Controller -> Service/Query -> Model/Transaction -> Resource
```

## قواعد القرار

الحلقة والعضوية وطلب التسجيل تتعامل مع أكثر من Model أو مع انتقال حالة، لذلك تستخدم Services ومعاملات DB. فحص السعة ومنع العضوية النشطة المزدوجة يتم داخل المعاملة مع `lockForUpdate`. قيد MySQL للعضوية النشطة يستخدم عمودًا مولدًا وفهرسًا فريدًا، بينما تعتمد اختبارات SQLite على نفس فحص الخدمة لتعذر نقل الصيغة المولدة حرفيًا بين المحركين.

## الاختبارات المطلوبة

تغطي الاختبارات إنشاء الحلقة وتعديلها وتفعيلها وإيقافها، القوائم، الإسناد، السعة، الجنس، العضوية المكررة، التحويل إلى `removed` دون حذف التاريخ، صلاحيات الطالب، الحقول المجهولة، تقديم الطلب العام، الخصوصية قبل القبول وبعده، القبول، الرفض، السحب، التكرار، وحالات التعارض. وتُشغّل معها migrations وPint ومدققات العقود والشجرة وLaravel-only/P2P-only قبل كل Commit.
