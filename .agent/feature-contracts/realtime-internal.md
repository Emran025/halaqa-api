# عقد طبقة الاتصال اللحظي الداخلية

## تعريف الميزة

- **اسم الميزة:** خادم WebSocket وWebRTC signaling داخلي داخل Laravel.
- **النطاق:** handshake، تفويض القناة الخاصة، إطارات WebSocket النصية، وتمرير الرسائل المؤقتة بين طرفي جلسة واحدة.
- **القناة:** `private-live-session.{session_id}`.
- **المستلم:** الطرف الآخر في الجلسة فقط؛ لا يسمح الخادم بقناة عامة أو طرف ثالث.
- **النقل الإعلامي:** لا يمر الصوت أو الفيديو عبر Laravel. يبقى WebRTC P2P، ويُقبل Host ICE فقط.
- **الحفظ:** لا تُحفظ إطارات WebSocket أو SDP أو ICE. الحفظ الرسمي للمصحف والأخطاء والمهام والتقارير يتم عبر REST وخدمات المجال.
- **نشر Laravel إلى WebSocket:** تحفظ الأحداث الرسمية الموجهة في `realtime_outbox_messages` داخل MySQL، مع `dedupe_key` فريد لكل recipient وحدث وpayload، ثم يقرأها خادم WebSocket الداخلي ويحوّلها إلى إطارات server-originated للطرفين. يظل ذلك مسار تحكم داخليًا بين عمليتي Laravel، وليس قناة وسائط أو خدمة خارجية.
- **التشغيل:** الأمر `php artisan realtime:websocket --host=127.0.0.1 --port=8081` يشغل الخادم الداخلي باستخدام إمكانات PHP streams، دون حزمة WebSocket أو مزود خارجي. يملك الخادم دورة تسليم outbox الخاصة به ويعلّم الرسائل بعد محاولة الكتابة الناجحة فقط.

## handshake والتفويض

يقبل الخادم `GET /ws?channel=private-live-session.{session_id}` مع `Upgrade: websocket` و`Sec-WebSocket-Version: 13` وBearer Sanctum. يتحقق `HandshakeService` من مفتاح WebSocket، ويثبت هوية المستخدم من Sanctum. بعد ذلك يتحقق `LiveSessionChannelAuthorizer` من القناة، ووجود الجلسة، وPolicy `realtime`، وحالة الجلسة، وأن المستخدم هو `teacher_id` أو `student_id`.

## الإطار

`FrameCodec` يدعم إطارات النص النهائية المقنّعة من العميل، وإطارات close وping وpong، ويرفض الإطارات غير المقنّعة، المجزأة، الثنائية، ذات الامتدادات، أو الأكبر من حد الحجم. لا يفسر محتوى SDP؛ يتحقق `WebRtcSignalingService` من envelope والحمولة حسب نوع الرسالة.

## الرسائل العميلية المدعومة

| النوع | المصدر | التحقق |
|---|---|---|
| `webrtc.offer` و`webrtc.answer` | الطرف البادئ/المجيب | `type` و` sdp` فقط، مع UUIDs ومطابقة الطرفين. |
| `webrtc.ice_candidate` | الطرفان | `candidate`, `sdp_mid`, `sdp_m_line_index`, `username_fragment`، مع Host ICE فقط. |
| `webrtc.renegotiate` | الطرفان | `reason` و`attempt` بحدود صريحة. |
| `mushaf.page_changed` | الطرفان | `edition_id` و`page_number` رقميان؛ عرض مؤقت وليس مصدر الحقيقة. |
| `mushaf.ayah_selected` | الطرفان | `edition_id`, `ayah_id`, `page_number`؛ لا يثبت حالة رسمية. |
| `mistake.created` و`updated` و`deleted` | الطرفان | UUIDs وحقول المصحف ونوع الخطأ وفق المخطط؛ الحفظ الرسمي عبر REST. |
| `guidance.request_repeat` | المعلم | `task_id`, `ayah_id`, `reason`. |
| `task.changed` | المعلم | `task_id`, `state`, والمواضع الحالية؛ لا يغير قاعدة البيانات مباشرة. |

أما `session.*` و`report.updated` و`realtime.direct_connection_unavailable` فهي رسائل يصدرها Laravel بعد الحفظ الرسمي، ولا يقبلها الخادم كرسائل عميلة تغيّر الحالة الرسمية. يرسل server-originated envelope `source: server` و`sender_role: server` و`sender_id: null` إلى recipient واحد في كل مرة، ولا يثق في recipient قادم من العميل.

## مسار البيانات

```text
REST Service -> after-commit domain event -> RealtimeOutboxPublisher
             -> realtime_outbox_messages (pending)
             -> WebSocketServer poller -> RealtimeServerEventEnvelope
             -> ConnectionManager -> الطرف المقصود فقط

TCP socket -> HandshakeService -> Sanctum User
           -> LiveSessionChannelAuthorizer -> ConnectionManager
           -> FrameCodec -> JSON envelope
           -> WebRtcSignalingService -> ConnectionManager للطرف الآخر
```

لا يعتمد `FrameCodec` على Model أو Policy، ولا يضع Controller أو Resource قواعد نقل. ولا يُكتب SDP أو ICE أو الصوت أو الفيديو إلى قاعدة البيانات.

## انتقال فشل الاتصال المباشر

يستخدم الطرف المشارك `POST /api/v1/sessions/{sessionId}/direct-connection-unavailable` مع `reason` و`client_operation_id`. يتحقق `LiveSessionPolicy` من أن الطالب أو المعلم طرف في الجلسة ومن أن الحالة قابلة لهذا الانتقال، ثم يحفظ Service الحالة الرسمية وينشر `realtime.direct_connection_unavailable`. يمكن للطرفين إعادة المحاولة عبر `POST /api/v1/sessions/{sessionId}/reconnect`، ولا ينشأ أي مسار وسيط أو نقل للوسائط.

## حالات الفشل

يفشل handshake عند نقص Upgrade أو Bearer أو القناة أو مفتاح WebSocket، ويُغلق اتصال البروتوكول برسالة HTTP مناسبة. يفشل الإطار غير الصحيح أو الرسالة غير المصرح بها بإغلاق WebSocket بكود سياسة. لا يوجد مسار بديل وسيط عند فشل الاتصال المباشر؛ يبقى انتقال `direct_connection_unavailable` قرارًا رسميًا عبر Service/REST.

## الاختبارات

- round-trip لإطار نصي مقنّع وحساب طول الإطار المستهلك.
- رفض الإطار غير المقنّع.
- تطبيع `sender_role` من هوية Sanctum بدل ثقة العميل.
- قبول Host ICE ورفض غير Host ICE.
- رفض recipient أو session spoofing ورسائل client التي تخص Laravel فقط.
- تمرير ConnectionManager إلى الطرف المقابل فقط.
- handshake صحيح مع Bearer Sanctum، ورفض مستخدم غير مشارك في القناة.
- تحقق outbox من recipient والجلسة وعدم تكرار التسليم، وتسليم الرسائل server-originated للطرف المقابل فقط.
- انتقال `direct_connection_unavailable` لصاحب الجلسة فقط، ورفض الحالة غير القابلة للانتقال، وقابلية retry الآمن.
- ظهور أمر Artisan والتحقق المحلي من عدم وجود حزمة أو تكامل نقل خارجي.

## حدود هذه الشريحة

ينفذ الخادم handshake وchannel authorization وclient-to-client temporary forwarding داخل عملية WebSocket، ويقرأ الرسائل الرسمية الموجهة من outbox بعد commit. لا يُدّعى هنا تشغيل متعدد النسخ مع claim موزع؛ التشغيل المدعوم لهذه الشريحة هو خادم WebSocket داخلي واحد لكل بيئة تشغيل، مع بقاء الرسائل pending عند توقفه. كما لم يُنفذ نقل WebRTC نفسه، لأن Laravel لا يحمل الوسائط.
