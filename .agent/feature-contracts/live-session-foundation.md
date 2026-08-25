# عقد ميزة Laravel: أساس الجلسة المباشرة

## تعريف الميزة

- **الفاعل:** `teacher` ينشئ جلسة لطالب عضو فعال في حلقته.
- **المسار:** `POST /api/v1/sessions` وفق `CreateSessionInput` و`SessionResponse` في OpenAPI.
- **النتيجة:** جلسة رسمية بحالة `requested`، مرتبطة بالحلقة والمعلم والطالب ونوع المهمة، مع `direct_p2p_only=true`.
- **منع التكرار:** تفحص الخدمة وجود جلسة نشطة للطالب داخل Transaction وتعيد تعارضًا منظمًا بدل إنشاء جلسة ثانية.

## الطبقات

```text
CreateSessionRequest -> LiveSessionService -> LiveSession/HalaqaMembership/TrackingType -> SessionResponseResource
```

يقع Request وController وResource وService وModel وMigration في المسارات المعيارية. لا يدخل الصوت أو الفيديو أو SDP أو ICE إلى Laravel أو قاعدة البيانات؛ هذه الشريحة تسجل التحكم فقط.

## الصلاحيات والاختبارات

لا يُسمح إلا للمعلم المصادق. تتحقق الخدمة من أن الطالب عضو فعال في الحلقة وأن الحلقة مملوكة للمعلم. يغطي `LiveSessionTest` الإنشاء الناجح، `direct_p2p_only`, غياب حقول الوسائط، ومنع الجلسة النشطة الثانية. ستُضاف انتقالات القبول والاتصال والإنهاء والإشارة الداخلية في شرائح لاحقة منفصلة بعد شهادة هذه الأساس.
