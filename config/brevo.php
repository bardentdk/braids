<?php

return [
    'api_key'  => env('BREVO_API_KEY'),
    'sender'   => [
        'email' => env('BREVO_SENDER_EMAIL', 'contact@patricia-braids.fr'),
        'name'  => env('BREVO_SENDER_NAME',  'Patricia Braids'),
    ],
    'reply_to' => [
        'email' => env('BREVO_REPLY_TO_EMAIL', 'contact@patricia-braids.fr'),
        'name'  => env('BREVO_REPLY_TO_NAME',  'Patricia Braids'),
    ],
];