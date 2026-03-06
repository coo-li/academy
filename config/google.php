<?php

return [

    'credentials_path' => storage_path('app/google-auth.json'),

    'calendar' => [
        'default_calendar_id' => env('GOOGLE_CALENDAR_ID', 'primary'),
    ],

];
