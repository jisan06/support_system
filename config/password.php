<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Password Rules
    |--------------------------------------------------------------------------
    |
    | Configure password requirements. These rules are enforced on both
    | server-side validation and the client-side strength meter.
    |
    */

    'min_length'  => 8,
    'uppercase'   => true,   // Require at least one uppercase letter
    'lowercase'   => true,   // Require at least one lowercase letter
    'numbers'     => true,   // Require at least one number
    'symbols'     => true,  // Require at least one special character
];
