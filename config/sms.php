<?php

return [
    'fake' => [
        'endpoint' => env('SMS_FAKE_ENDPOINT', 'https://sms.fake.test/api/messages'),
        'timeout' => (int) env('SMS_TIMEOUT', 5),
        'connect_timeout' => (int) env('SMS_CONNECT_TIMEOUT', 2),
    ],
];
