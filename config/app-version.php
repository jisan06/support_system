<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Version
    |--------------------------------------------------------------------------
    |
    | The current version of the application. Update this value with each
    | new release.
    |
    */
    'version' => '1.0.0',

    /*
    |--------------------------------------------------------------------------
    | Update Check URL
    |--------------------------------------------------------------------------
    |
    | The URL where version.json is hosted. The app fetches this to check
    | if a newer version is available. Host this on your own server.
    |
    | Expected JSON format: { "version": "1.1.0" }
    |
    */
    'update_url' => env('APP_UPDATE_URL', ''),

];
