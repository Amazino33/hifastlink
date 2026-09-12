<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'google' => [
        'client_id'     => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect'      => env('GOOGLE_REDIRECT_URI', '/auth/google/callback'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'radius' => [
        'bridge_url' => env('RADIUS_BRIDGE_URL', 'http://142.93.47.189/radius_bridge.php'),
        'secret_key' => env('RADIUS_SECRET_KEY', 'SimpleTestKey123'),
        'server' => env('RADIUS_SERVER', env('RADIUS_DB_HOST', '142.93.47.189')),
        'secret' => env('RADIUS_SECRET', 'testing123'),
        'disconnect_port' => (int) env('RADIUS_DISCONNECT_PORT', 3799),
    ],

    'wireguard' => [
        'server_public_key' => env('WG_SERVER_PUBLIC_KEY'),
        'server_endpoint' => env('WG_SERVER_ENDPOINT', '194.36.184.49'),
        'server_ip' => env('WG_SERVER_IP', '192.168.42.1'),
        'server_port' => env('WG_SERVER_PORT', '51820'),
        'listen_port' => env('WG_LISTEN_PORT', '13231'),
        'vpn_network' => env('WG_VPN_NETWORK', '192.168.42.0/24'),
        'start_ip' => env('WG_START_IP', 10), // Start from 192.168.42.10
    ],

    'mikrotik' => [
        'gateway' => env('MIKROTIK_GATEWAY', 'login.wifi'),
        'domain' => env('APP_DOMAIN', 'hifastlink.com'),
        'dns_name' => env('MIKROTIK_DNS_NAME', 'login.wifi'),
        'website_ip' => env('WEBSITE_IP', '194.36.184.49'),
        'api_host' => env('MIKROTIK_API_HOST', '192.168.88.1'),
        'api_user' => env('MIKROTIK_API_USER', 'hifastlink'),
        'api_password' => env('MIKROTIK_API_PASSWORD', ''),
        'api_port' => (int) env('MIKROTIK_API_PORT', 80),
    ],

    'digitalocean' => [
        'ip' => env('VPS_IP'),
        'user' => env('VPS_USERNAME'),
        'pass' => env('VPS_PASSWORD'),
    ],

];
