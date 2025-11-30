<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Admin Login Credentials
    |--------------------------------------------------------------------------
    |
    | Special admin login credentials. These bypass normal email validation
    | and allow direct admin access.
    |
    | IMPORTANT: Set these in your .env file:
    |   ADMIN_EMAIL=your-admin@email.com
    |   ADMIN_PASSWORD=your-secure-password
    |
    */

    'email' => env('ADMIN_EMAIL'),
    'password' => env('ADMIN_PASSWORD'),
];



