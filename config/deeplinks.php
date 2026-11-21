<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Self-hosted dynamic links
    |--------------------------------------------------------------------------
    |
    | No third-party link service is used. Set the store URLs in production
    | .env; Play URL is built from the package when DEEPLINK_PLAY_STORE_URL is
    | empty. While DEEPLINK_APP_STORE_URL still points at Google Play (temporary
    | value), the App Store button is hidden to avoid sending iOS users to the
    | wrong store.
    |
    */

    'web_base_url'    => env('DEEPLINK_WEB_URL', env('APP_URL', 'http://localhost')),
    'app_scheme'      => env('DEEPLINK_APP_SCHEME', 'grinta'),
    'android_package' => env('DEEPLINK_ANDROID_PACKAGE', 'com.muhmmad.grinta'),
    'ios_app_id'      => env('DEEPLINK_IOS_APP_ID', '6RPWXA5835.elsawy.Grinta'),

    'play_store_url' => env('DEEPLINK_PLAY_STORE_URL'),
    'app_store_url'  => env('DEEPLINK_APP_STORE_URL'),

    /*
     | Numeric screen ids accepted by the API: 0 through 4 inclusive.
     | Rename the labels here to match the mobile app's screen names.
     */
    'screens' => [
        0 => 'Screen 0',
        1 => 'League / team screen',
        2 => 'Match lineup tab',
        3 => 'Screen 3',
        4 => 'Screen 4',
    ],

    'claim_limit' => (int) env('DEEPLINK_CLAIM_LIMIT', 10),
    'create_limit' => (int) env('DEEPLINK_CREATE_LIMIT', 30),
];
