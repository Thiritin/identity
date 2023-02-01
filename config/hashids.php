<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the connections below you wish to use as
    | your default connection for all work. Of course, you may use many
    | connections at once using the manager class.
    |
    */

    'default' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Hashids Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the connections setup for your application. Example
    | configuration has been included, but you may add as many connections as
    | you would like.
    |
    */

    'connections' => [
        'default' => [
            'salt' => env('HASHIDS_SALT'),
            'length' => 16,
            'alphabet' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890',
        ],
        'user' => [
            'salt' => env('HASHIDS_USER_SALT'),
            'length' => 16,
            'alphabet' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890',
        ],
        'group' => [
            'salt' => env('HASHIDS_GROUP_SALT'),
            'length' => 16,
            'alphabet' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890',
        ],
    ],

];
