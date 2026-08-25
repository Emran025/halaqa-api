# عقد الاتصال اللحظي
## WebSocket وWebRTC Signaling

ملف `openapi.yaml` يصف REST API، أما هذا الملف فيصف قناة الاتصال اللحظي التي يستخدمها تطبيق WPF مع Laravel Reverb أو خادم WebSocket متوافق.

## القناة

```text
private-live-session.{session_id}
```

لا يسمح Laravel بالاشتراك إلا للمستخدم الذي يطابق `teacher_id` أو `student_id` في الجلسة، وبعد التحقق من أن الجلسة ليست `cancelled` أو `rejected` أو `ended` نهائيًا.

## غلاف الرسالة

```json
{
  "message_id": "uuid",
  "session_id": "uuid",
  "sender_id": "uuid",
  "sender_role": "teacher",
  "type": "webrtc.offer",
  "occurred_at": "2026-08-25T10:00:00Z",
  "client_operation_id": "uuid",
  "payload": {}
}
```

`client_operation_id` اختياري للأحداث التي يجب ألا تحفظ مرتين، مثل خطأ أو تغيير حالة مصحف. يجب أن يعيد الخادم أو طبقة الحفظ العملية نفسها إذا وصلت الرسالة مرة أخرى.

## أنواع الرسائل

| النوع | المرسل | المستقبل | الغرض | حفظ رسمي |
|---|---|---|---|---|
| `session.requested` | Laravel | الطالب | إشعار بطلب الجلسة. | نعم في Session. |
| `session.accepted` | Laravel | المعلم | إشعار قبول الطالب. | نعم. |
| `session.rejected` | Laravel | المعلم | إشعار رفض الطالب. | نعم. |
| `session.state_changed` | Laravel | الطرفان | تحديث حالة الجلسة. | نعم. |
| `webrtc.offer` | الطرف الذي يبدأ | الطرف الآخر | SDP Offer. | لا، مؤقت. |
| `webrtc.answer` | الطرف الآخر | الطرف الذي بدأ | SDP Answer. | لا، مؤقت. |
| `webrtc.ice_candidate` | كلا الطرفين | الطرف الآخر | ICE Candidate. | لا، مؤقت. |
| `webrtc.renegotiate` | كلا الطرفين | الطرف الآخر | إعادة التفاوض. | لا، مؤقت. |
| `mushaf.page_changed` | كلا الطرفين | الطرف الآخر | مزامنة الصفحة الحالية. | مسودة عند الحاجة. |
| `mushaf.ayah_selected` | المعلم/الطالب | الطرف الآخر | مزامنة الآية المحددة. | عند ارتباطها بنطاق المهمة. |
| `mistake.created` | المعلم/الطالب | الطرف الآخر | ظهور العلامة فورًا. | نعم عبر REST/Service. |
| `mistake.updated` | المعلم/الطالب | الطرف الآخر | تحديث التصنيف أو الملاحظة. | نعم عبر REST/Service. |
| `mistake.deleted` | المعلم/الطالب | الطرف الآخر | إزالة العلامة. | نعم عبر REST/Service. |
| `guidance.request_repeat` | المعلم | الطالب | طلب إعادة آية أو كلمة. | اختياري في التقرير. |
| `task.changed` | المعلم | الطالب | تغيير نوع المهمة أو نطاقها. | نعم إذا أصبح رسميًا. |
| `report.updated` | Laravel | الطرفان | وجود تغيير في التقرير. | نعم. |
| `session.ended` | Laravel | الطرفان | انتهاء الاتصال المنطقي. | نعم. |

## رسائل WebRTC

### `webrtc.offer`

```json
{
  "message_id": "uuid",
  "session_id": "uuid",
  "sender_id": "uuid",
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
  "sender_role": "student",
  "type": "webrtc.ice_candidate",
  "occurred_at": "2026-08-25T10:00:02Z",
  "payload": {
    "candidate": "...",
    "sdp_mid": "0",
    "sdp_m_line_index": 0,
    "username_fragment": "..."
  }
}
```

لا يفسر Laravel محتوى SDP أو ICE؛ يكتفي بالتحقق من هوية المرسل والجلسة وتمرير الرسالة إلى الطرف الآخر. تُعاد التفاوضات عند تغير الشبكة أو عودة الاتصال.

## رسائل المصحف

```json
{
  "message_id": "uuid",
  "session_id": "uuid",
  "sender_id": "uuid",
  "sender_role": "teacher",
  "type": "mistake.created",
  "occurred_at": "2026-08-25T10:02:00Z",
  "client_operation_id": "uuid",
  "payload": {
    "task_id": "uuid",
    "mistake_id": "uuid",
    "ayah_id": "ayah-1-1",
    "page_number": 1,
    "word_index": 4,
    "mistake_type": "articulation",
    "note": "..."
  }
}
```

تعرض الواجهة العلامة فورًا من الرسالة اللحظية، ثم يرسل التطبيق عملية الحفظ إلى `POST /sessions/{sessionId}/tasks/{taskId}/mistakes`. إذا فشل REST، تبقى العملية في طابور محلي وتُعاد لاحقًا.

## دورة الحالة

```text
requested
  -> accepted
  -> connecting
  -> connected
  -> weak_connection
  -> reconnecting
  -> connected
  -> ended
```

يمكن الانتقال من `connecting` أو `connected` إلى `disconnected` عند الانقطاع. لا ينتقل التقرير إلى `completed` إلا عبر REST بعد اعتماد المعلم. إنهاء WebRTC أو مغادرة الطرف لا يساوي اعتماد التقرير.

## أحداث الاتصال الخادمية

| الحدث | المصدر | سلوك العميل |
|---|---|---|
| `session.state_changed` | Laravel | تحديث شارة الحالة وإتاحة زر إعادة الاتصال عند اللزوم. |
| `realtime.authorization_failed` | Laravel/Reverb | إغلاق القناة وطلب مصادقة جديدة. |
| `realtime.peer_left` | Laravel أو العميل | إظهار تنبيه وحفظ المسودة. |
| `realtime.sync_required` | Laravel | جلب الحالة الرسمية من REST وإعادة مزامنة المصحف. |
| `realtime.token_expiring` | Laravel | تجديد الرمز قبل إعادة فتح القناة. |

## قاعدة الأمان

لا يرسل العميل دور المستخدم أو صلاحياته بوصفها مصدر الحقيقة؛ يتحقق Laravel من الرمز والعلاقة بالجسلة. لا تُقبل أحداث `mistake.*` أو `report.*` إلا إذا كانت العملية مسموحة لحالة التقرير الحالية. لا توضع مفاتيح TURN الدائمة داخل التطبيق؛ يعيد Endpoint الإعدادات بيانات مؤقتة عند الحاجة.
