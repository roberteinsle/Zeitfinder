<?php

return [

    // The first administrator. Never hard-code a real address; set it in the environment.
    'admin_email' => env('ADMIN_EMAIL'),

    // All dates are stored in UTC and shown in this time zone.
    'display_timezone' => 'Europe/Berlin',

    // Set to true only when the server accepts web traffic exclusively through Cloudflare.
    // The real visitor IP is then taken from the CF-Connecting-IP header.
    'behind_cloudflare' => (bool) env('BEHIND_CLOUDFLARE', false),

    'magic_link_ttl_minutes' => 15,

    'max_options_per_poll' => 60,

    // Credit line in the footer ("Made with ♥ in <place> by <name>"). Personal data:
    // set it in the environment only. Without a name the line is not shown.
    'credit' => [
        'name' => env('CREDIT_NAME'),
        'url' => env('CREDIT_URL'),
        'place' => env('CREDIT_PLACE'),
    ],

];
