<?php

/*
|--------------------------------------------------------------------------
| Audit log
|--------------------------------------------------------------------------
|
| `redact`: attribute names (case-insensitive substrings) whose values are never stored; the log
| keeps "[redacted]" so a change is visible without its content. `ignore`: attributes that are
| not worth a log line on their own (a change to only these writes no entry).
|
*/

return [

    'redact' => ['password', 'remember_token', 'token', 'secret', 'api_key'],

    'ignore' => ['updated_at', 'created_at', 'remember_token'],

    'redacted' => '[redacted]',

];
