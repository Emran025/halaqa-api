# عقد الاتصال اللحظي
## Laravel WebSocket وWebRTC P2P

هذا الملف مكمل لملف `openapi.yaml`. يصف القناة اللحظية التي يتصل بها تطبيق WPF على `.NET 8 (net8.0-windows)` مع خادم WebSocket المضمن داخل تطبيق Laravel نفسه.

## المبادئ الملزمة

| القرار | العقد |
|---|---|
| خادم التحكم | Laravel داخل المستودع نفسه. |
| WebSocket | تنفيذ داخلي مخصص داخل Laravel/PHP، دون Reverb أو Pusher أو Soketi أو Socket.IO أو أي مكتبة خارجية. |
| الصوت والصورة | WebRTC P2P مباشر بين المعلم والطالب فقط. |
| الوسيط الإعلامي | غير موجود. لا Media Server ولا Relay ولا Proxy. |
| STUN/TURN | غير مستخدمين. يعتمد الاتصال على Host ICE Candidates المباشرة. |
| البيانات الرسمية | REST API إلى Laravel. |
| أحداث العرض المؤقتة | WebRTC DataChannel P2P. |
| إشارات التفاوض | WebSocket Laravel، تمرير مؤقت فقط. |

## القناة

```text
private-live-session.{session_id}
```

ينفذ `LiveSessionChannelAuthorizer` التحقق من Bearer Token ومن أن المستخدم هو أحد طرفي الجلسة. لا يسمح بالاشتراك العام أو بالاعتماد على معرف الجلسة وحده. المثال السابق يوضح `webrtc.offer`؛ وتستخدم بقية الأنواع حمولة النوع المحددة في جدول تعريف الحمولة أدناه.

## غلاف الرسالة

```json
{
  "message_id": "uuid",
  "session_id": "uuid",
  "sender_id": "uuid",
  "recipient_id": "uuid",
  "sender_role": "teacher",
  "type": "webrtc.offer",
  "occurred_at": "2026-08-25T10:00:00Z",
  "client_operation_id": null,
  "payload": {
    "type": "offer",
    "sdp": "v=0..."
  }
}
```

يتحقق Laravel من `sender_id` و`recipient_id` وعلاقتهما بالجلسة، ولا يثق في الدور الذي يرسله العميل. لا يسمح بتمرير رسالة إلى طرف ثالث.

## رسائل WebSocket المسموح بها

| النوع | المصدر | الوجهة | التخزين |
|---|---|---|---|
| `session.requested` | Laravel | الطالب | حالة الجلسة الرسمية. |
| `session.accepted` | Laravel | المعلم | حالة الجلسة الرسمية. |
| `session.rejected` | Laravel | المعلم | حالة الجلسة الرسمية. |
| `session.state_changed` | Laravel | الطرفان | الحالة الرسمية. |
| `webrtc.offer` | الطرف البادئ | الطرف الآخر | تمرير مؤقت فقط. |
| `webrtc.answer` | الطرف الآخر | الطرف البادئ | تمرير مؤقت فقط. |
| `webrtc.ice_candidate` | كلا الطرفين | الطرف الآخر | تمرير مؤقت فقط، Host candidate فقط. |
| `webrtc.renegotiate` | كلا الطرفين | الطرف الآخر | تمرير مؤقت فقط. |
| `mushaf.page_changed` | كلا الطرفين | الطرف الآخر | P2P مؤقت. |
| `mushaf.ayah_selected` | كلا الطرفين | الطرف الآخر | P2P مؤقت، ويحفظ فقط إن أصبح نطاقًا رسميًا. |
| `mistake.created` | المعلم/الطالب | الطرف الآخر | عرض فوري ثم حفظ عبر REST. |
| `mistake.updated` | المعلم/الطالب | الطرف الآخر | عرض فوري ثم حفظ عبر REST. |
| `mistake.deleted` | المعلم/الطالب | الطرف الآخر | عرض فوري ثم حفظ عبر REST. |
| `guidance.request_repeat` | المعلم | الطالب | اختياري في سجل الجلسة. |
| `task.changed` | المعلم | الطالب | يحفظ عبر Service عند اعتماده. |
| `report.updated` | Laravel | الطرفان | حالة التقرير الرسمية. |
| `session.ended` | Laravel | الطرفان | حالة الجلسة الرسمية. |
| `realtime.direct_connection_unavailable` | Laravel بعد REST الرسمي | الطرفان | حالة فشل الاتصال المباشر، دون تحويل إلى Relay. لا يقبلها الخادم كرسالة عميلة. |

## رسائل WebRTC

### `webrtc.offer`

```json
{
  "message_id": "uuid",
  "session_id": "uuid",
  "sender_id": "uuid",
  "recipient_id": "uuid",
  "sender_role": "teacher",
  "type": "webrtc.offer",
  "occurred_at": "2026-08-25T10:00:00Z",
  "payload": {
    "type": "offer",
    "sdp": "..."
  }
}
```

### `webrtc.answer`

```json
{
  "message_id": "uuid",
  "session_id": "uuid",
  "sender_id": "uuid",
  "recipient_id": "uuid",
  "sender_role": "student",
  "type": "webrtc.answer",
  "occurred_at": "2026-08-25T10:00:01Z",
  "payload": {
    "type": "answer",
    "sdp": "..."
  }
}
```

### `webrtc.ice_candidate`

```json
{
  "message_id": "uuid",
  "session_id": "uuid",
  "sender_id": "uuid",
  "recipient_id": "uuid",
  "sender_role": "student",
  "type": "webrtc.ice_candidate",
  "occurred_at": "2026-08-25T10:00:02Z",
  "payload": {
    "candidate": "candidate:... typ host",
    "sdp_mid": "0",
    "sdp_m_line_index": 0,
    "username_fragment": "..."
  }
}
```

يرفض Laravel أي Candidate من نوع `srflx` أو `relay` لأن سياسة المشروع P2P-only دون STUN/TURN. ولا يفسر Laravel SDP، بل يتحقق من الغلاف والصلاحية ويمرر الحمولة المؤقتة.

## رسائل المصحف التفاعلي

```json
{
  "message_id": "uuid",
  "session_id": "uuid",
  "sender_id": "uuid",
  "recipient_id": "uuid",
  "sender_role": "teacher",
  "type": "mistake.created",
  "occurred_at": "2026-08-25T10:02:00Z",
  "client_operation_id": "uuid",
  "payload": {
    "task_id": "uuid",
    "mistake_id": "uuid",
    "edition_id": 1,
    "ayah_id": 1,
    "page_number": 1,
    "word_index": 4,
    "mistake_type": "pronunciation",
    "note": "..."
  }
}
```

يعرض العميل العلامة فورًا من DataChannel أو WebSocket عند الحاجة، ثم يرسل عملية الحفظ إلى `POST /sessions/{sessionId}/tasks/{taskId}/mistakes`. إذا فشل REST، يحفظ العميل العملية في مسودة محلية ويعيد إرسالها دون تكرار.

## دورة الاتصال

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

لا تعني `direct_connection_unavailable` أن Laravel سيحمل الوسائط أو أن النظام سيستخدم Relay. تعني فقط فشل الاتصال المباشر، ويمكن إعادة المحاولة أو إنهاء الجلسة.

## نشر أحداث Laravel الرسمية بين العمليات

تُنشئ خدمات Laravel بعد نجاح transaction رسائل server-originated في جدول `realtime_outbox_messages`. كل رسالة تحتوي `id`, `session_id`, `recipient_id`, `event_type`, `dedupe_key`, `payload`, `attempts`, `last_attempted_at`, `delivered_at` والطوابع الزمنية. لا يسمح العقد إلا بالأحداث الرسمية `session.requested`, `session.accepted`, `session.rejected`, `session.state_changed`, `session.ended`, `report.updated`, و`realtime.direct_connection_unavailable`.

تقرأ عملية خادم WebSocket الرسائل غير المسلّمة بترتيب الإنشاء، وتتحقق من أن recipient طرف في الجلسة، ثم تبني envelope من مصدر الخادم (`source: server`, `sender_role: server`, `sender_id: null`) وترسله إلى recipient المطابق فقط. لا يثق الخادم في recipient أو type قادمين من العميل، ولا يضع `delivered_at` إلا بعد نجاح الكتابة. عند توقف الخادم تبقى الرسالة pending. هذه الشريحة تدعم خادم WebSocket داخليًا واحدًا لكل بيئة تشغيل ولا تدّعي claim موزعًا لعدة نسخ.

## انتقال direct_connection_unavailable

يستدعي الطرف المشارك `POST /api/v1/sessions/{sessionId}/direct-connection-unavailable` مع `reason` و`client_operation_id`. تتحقق Policy من طرفية المستخدم وحالة الجلسة، ويحفظ Service الحالة الرسمية ثم ينشر الحدث. يستطيع الطرفان طلب إعادة المحاولة عبر `POST /api/v1/sessions/{sessionId}/reconnect`. لا يؤدي هذا الانتقال إلى تحميل الوسائط أو إنشاء مسار بديل.

## تنفيذ Laravel الداخلي

```text
app/
├── Console/Commands/Realtime/RunWebSocketServerCommand.php
├── Realtime/WebSocket/WebSocketServer.php
├── Realtime/WebSocket/ConnectionManager.php
├── Realtime/WebSocket/FrameCodec.php
├── Realtime/WebSocket/HandshakeService.php
├── Realtime/Channels/LiveSessionChannelAuthorizer.php
└── Realtime/Signaling/WebRtcSignalingService.php
```

تستقبل طبقة WebSocket الرسالة، تتحقق من القناة والطرفين، وتستدعي `WebRtcSignalingService`. لا تضع قواعد المجال داخل Frame Codec ولا داخل Controller ولا داخل Resource.

## قواعد الحفظ

يحفظ Laravel فقط البيانات الرسمية: الجلسة، قبولها وإنهاؤها، نطاق المهمة، حالة المصحف الرسمية في `session_mushaf_states`، الأخطاء، التقييمات، الملاحظات، التقرير، الاعتمادات، وجدولة المتابعة. لا يحفظ الصوت أو الفيديو أو SDP أو ICE في السجل التعليمي.

يستخدم كل تغيير رسمي `client_operation_id` أو `Idempotency-Key` لمنع التكرار عند إعادة الاتصال. يجب أن تكون عمليات الحفظ في Services ومعاملات ذرية عند ارتباط أكثر من Model.

## الممنوعات

يمنع نقل الصوت أو الفيديو إلى Laravel أو WebSocket، ويمنع تشغيل Media Server أو Relay أو Proxy، ويمنع استخدام STUN أو TURN، ويمنع Reverb وPusher وSoketi وSocket.IO وSIPSorcery وأي مكتبة خارجية لتنفيذ WebSocket أو WebRTC. كما يمنع فتح قناة عامة أو قبول رسالة لا يثبت Laravel أن مرسلها ومستقبلها طرفان في الجلسة.


## تعريف حمولة الرسائل

الحقل `payload` ليس خريطة عامة؛ يختار الخادم مخطط الحمولة حسب قيمة `type`، ولا يقبل حقولًا إضافية. جميع المعرفات في الحمولة الرقمية الخاصة بالمصحف هي أرقام، ويجب أن تتطابق مع `edition_id` نفسه.

| `type` | الحقول الإلزامية في `payload` | التخزين أو النقل |
|---|---|---|
| `webrtc.offer` | `type: offer`, `sdp: string` | تمرير مؤقت فقط عبر WebSocket؛ لا تخزين. |
| `webrtc.answer` | `type: answer`, `sdp: string` | تمرير مؤقت فقط عبر WebSocket؛ لا تخزين. |
| `webrtc.ice_candidate` | `candidate: string`, `sdp_mid: string|null`, `sdp_m_line_index: integer`, `username_fragment: string|null` | Host candidate فقط، تمرير مؤقت، ولا تخزين. |
| `webrtc.renegotiate` | `reason: string`, `attempt: integer` | تمرير مؤقت فقط؛ لا يغير حالة الجلسة إلا عبر Service. |
| `mushaf.page_changed` | `edition_id: integer`, `page_number: integer` | عرض مؤقت عبر DataChannel P2P؛ التثبيت الرسمي عبر REST. |
| `mushaf.ayah_selected` | `edition_id: integer`, `ayah_id: integer`, `page_number: integer` | عرض مؤقت عبر DataChannel P2P؛ لا يصبح رسميًا تلقائيًا. |
| `mistake.created` | `task_id: uuid`, `edition_id: integer`, `ayah_id: integer`, `page_number: integer`, `word_index: integer`, `mistake_type: enum`, `note: string|null` | عرض فوري، ثم حفظ رسمي عبر REST. |
| `mistake.updated` | `task_id: uuid`, `mistake_id: uuid`, `mistake_type: enum`, `note: string|null` | عرض فوري، ثم حفظ رسمي عبر REST. |
| `mistake.deleted` | `task_id: uuid`, `mistake_id: uuid` | عرض فوري، ثم حذف منطقي رسمي عبر REST. |
| `guidance.request_repeat` | `task_id: uuid`, `ayah_id: integer|null`, `reason: string|null` | رسالة توجيه مؤقتة؛ يسجلها REST أو التقرير عند اعتمادها. |
| `task.changed` | `task_id: uuid`, `state: enum`, `current_page: integer|null`, `current_ayah_id: integer|null` | عرض فوري، والحالة الرسمية عبر REST. |
| `report.updated` | `report_id: uuid`, `state: enum`, `version: integer` | يرسلها Laravel بعد حفظ رسمي. |
| `realtime.direct_connection_unavailable` | `state: direct_connection_unavailable`, `reason: string` | حالة رسمية للجلسة؛ لا تنشئ Relay أو مسارًا وسيطًا. |

يجب أن يطبق `WebRtcSignalingService` تحققًا مستقلًا لكل نوع رسالة عميلة: صحة الحقول، مطابقة `session_id`، مطابقة المرسل والمستقبل، وعدم السماح بأن يجعل DataChannel أو WebSocket الحالة الرسمية بدل REST وService. وتطبق طبقة outbox تحققًا مستقلًا على كل حدث صادر من Laravel قبل تسليمه.
