<?php

namespace App\Realtime;

final class RealtimeEventTypes
{
    public const SESSION_REQUESTED = 'session.requested';

    public const SESSION_ACCEPTED = 'session.accepted';

    public const SESSION_REJECTED = 'session.rejected';

    public const SESSION_STATE_CHANGED = 'session.state_changed';

    public const SESSION_ENDED = 'session.ended';

    public const REPORT_UPDATED = 'report.updated';

    public const DIRECT_CONNECTION_UNAVAILABLE = 'realtime.direct_connection_unavailable';

    /** @return list<string> */
    public static function all(): array
    {
        return [
            self::SESSION_REQUESTED,
            self::SESSION_ACCEPTED,
            self::SESSION_REJECTED,
            self::SESSION_STATE_CHANGED,
            self::SESSION_ENDED,
            self::REPORT_UPDATED,
            self::DIRECT_CONNECTION_UNAVAILABLE,
        ];
    }
}
