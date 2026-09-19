<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Initial Admin Account
    |--------------------------------------------------------------------------
    |
    | Credentials used by the ProductionSeeder to create the first admin
    | account on a fresh production database. Only the email is required;
    | when the password is left empty the seeder generates a strong random
    | password and prints it once to the console.
    |
    */

    'name' => env('ADMIN_NAME', 'Administrator'),

    'email' => env('ADMIN_EMAIL'),

    'password' => env('ADMIN_PASSWORD'),

];
