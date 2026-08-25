# سياسة الاتصال اللحظي وWebRTC

## القرار المعماري الملزم

Laravel هو التطبيق الخلفي الأولي والوحيد للمشروع. يتولى REST API والمصادقة والصلاحيات وحالة الجلسة وحفظ البيانات الرسمية وتشغيل قناة WebSocket المضمنة في كود Laravel نفسه. لا تستخدم هذه المنظومة Reverb أو Pusher أو Soketi أو Socket.IO أو SIPSorcery أو أي مكتبة أو مزود خارجي.

> **الصوت والصورة وبيانات التفاعل المباشر تنتقل P2P مباشرة بين جهاز المعلم وجهاز الطالب. Laravel ليس وسيط وسائط، ولا توجد خوادم Relay أو Media Server أو طرف ثالث.**

## حدود القنوات

| نوع البيانات | المسار الإلزامي | هل تمر عبر Laravel؟ |
|---|---|---:|
| المصادقة والصلاحيات | REST API | نعم |
| إنشاء وقبول وإنهاء الجلسة | REST API | نعم |
| إشارات WebRTC | WebSocket مدمج داخل Laravel | نعم، signaling فقط |
| الصوت والفيديو | WebRTC P2P | لا |
| تغيير صفحة المصحف والمؤشر | WebRTC DataChannel P2P | لا، إلا إذا أصبح تغييرًا رسميًا |
| الخطأ والتقييم والملاحظة والتقرير | REST API إلى Laravel | نعم، للحفظ الرسمي |
| حالة الاتصال اللحظية | WebSocket Laravel أو DataChannel حسب الحدث | WebSocket للحالة، وDataChannel للتفاعل المباشر |

## تنفيذ WebSocket داخل Laravel

ينشأ خادم WebSocket داخلي بواسطة أمر Artisan داخل التطبيق، وتوضع مكوناته في المسارات التالية:

```text
app/
├── Console/Commands/Realtime/RunWebSocketServerCommand.php
├── Realtime/
│   ├── WebSocket/
│   │   ├── WebSocketServer.php
│   │   ├── ConnectionManager.php
│   │   ├── FrameCodec.php
│   │   └── HandshakeService.php
│   ├── Channels/
│   │   └── LiveSessionChannelAuthorizer.php
│   └── Signaling/
│       └── WebRtcSignalingService.php
├── Services/LiveSessions/
│   ├── AuthorizeRealtimeSessionService.php
│   └── PublishSessionStateService.php
└── Events/LiveSession/
```

يعتمد التنفيذ على إمكانات PHP المضمنة المتاحة في بيئة التشغيل، ويستخدم Laravel Services وPolicies وEvents وModels. لا تُضاف حزمة Composer لتنفيذ WebSocket، ولا يُستدعى خادم أو API خارجي. يجب أن يبقى خادم WebSocket جزءًا من المستودع نفسه، مع أمر تشغيل، تسجيل، إيقاف آمن، واختبارات وحدة واتصال.

## القناة الخاصة

```text
private-live-session.{session_id}
```

لا يسمح الخادم بالاشتراك إلا للمستخدم الذي يكون `teacher_id` أو `student_id` في الجلسة. يتأكد `LiveSessionChannelAuthorizer` من الرمز والعلاقة وحالة الجلسة، ويرفض أي طرف ثالث أو مستخدم يحاول تمرير معرف جلسة لا يملكه.

## التفاوض المباشر

يستخدم WebSocket Laravel لتمرير الرسائل المؤقتة التالية فقط:

```text
webrtc.offer
webrtc.answer
webrtc.ice_candidate
webrtc.renegotiate
```

لا يفسر Laravel محتوى SDP أو ICE ولا يخزن الوسائط. يمرر الرسالة بعد التحقق من هوية المرسل والجلسة والطرف المقصود، ثم يرسلها إلى الطرف الآخر فقط.

## سياسة P2P الصارمة

يستخدم WebRTC مرشحي الاتصال المباشرين Host ICE Candidates فقط. يُمنع استخدام STUN وTURN وICE Relay وأي Media Server أو Proxy أو مزود مكالمات خارجي. إذا تعذر إنشاء اتصال مباشر بين الطرفين، تنتقل الجلسة إلى `direct_connection_unavailable` وتظهر للمستخدم رسالة واضحة مع خيار إعادة المحاولة؛ لا يُسمح بتحويلها إلى اتصال مرحل.

هذه السياسة تعني أن نجاح الاتصال يعتمد على إمكانية الوصول المباشر بين الشبكتين. هذا قيد مقصود في نطاق المشروع وليس سببًا لإضافة طرف ثالث أو مسار بديل خارجي.

## مزامنة المصحف

تنتقل تغييرات الصفحة، الآية المحددة، موضع المؤشر، وطلب إعادة القراءة عبر DataChannel P2P لتظهر فورًا للطرف الآخر. لا تُحفظ هذه التغييرات في قاعدة البيانات إلا عندما يعتمدها Service كجزء من نطاق مهمة أو تقرير.

أما الخطأ والتقييم والملاحظة ونطاق المهمة واعتماد التقرير فهي بيانات رسمية. تعرض الواجهة الحدث لحظيًا، ثم ترسله إلى REST API في Laravel لحفظه. يستخدم العميل `client_operation_id` أو `Idempotency-Key` لمنع التكرار عند إعادة الإرسال.

## دورة الجلسة

```text
requested
  -> accepted
  -> connecting
  -> direct_negotiation
  -> connected
  -> reconnecting
  -> connected
  -> direct_connection_unavailable
  -> ended
```

لا تعني حالة `direct_connection_unavailable` أن الخادم سيصبح وسيطًا. ولا يعني إغلاق WebRTC اعتماد التقرير؛ اعتماد التقرير عملية Laravel مستقلة يجب أن ينفذها المعلم وفق Policy.

## ممنوعات غير قابلة للتفاوض

يُمنع إرسال الصوت أو الفيديو إلى Laravel أو WebSocket. يُمنع استخدام خادم وسائط أو Relay أو STUN أو TURN أو مزود اتصال خارجي. يُمنع إضافة مكتبة Composer أو JavaScript خارجية لتنفيذ WebSocket أو WebRTC. يُمنع حفظ SDP أو ICE في السجل التعليمي. يُمنع جعل DataChannel بديلًا عن REST لحفظ الأخطاء أو التقييمات أو التقارير. ويُمنع قبول حدث لحظي من مستخدم لا يثبت Laravel علاقته بالجلسة.
