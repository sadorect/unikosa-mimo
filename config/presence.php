<?php

/*
|--------------------------------------------------------------------------
| Presence
|--------------------------------------------------------------------------
| How the platform decides whether somebody is "online".
|
| There is no websocket here, so presence means "made an authenticated request
| recently" — which is all a page-based application can honestly claim. The
| windows below are what turn that timestamp into a word.
*/

return [

    // Seen within this many minutes: shown as online.
    'online_minutes' => 5,

    // Seen within this many minutes: shown as recently active.
    'away_minutes' => 30,

    /*
    | How often the timestamp is actually written. Every request would mean a
    | database write per page view for every signed-in member; a member's
    | presence is not worth that. The write is skipped while a cache marker is
    | still alive, so at most one write per member per interval.
    */
    'write_every_seconds' => 60,

];
