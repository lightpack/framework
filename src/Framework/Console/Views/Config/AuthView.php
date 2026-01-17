<?php

namespace Lightpack\Console\Views\Config;

class AuthView
{
    public static function getTemplate()
    {
        return <<<'PHP'
<?php

return [
    'auth' => [
        'drivers' => [
            /**
             * Default authentication driver based on email/password credentials.
             */ 
            'default' => [
                'model' => App\Models\User::class,
                'identifier' => Lightpack\Auth\Identifiers\EmailPasswordIdentifier::class,
                'remember_duration' => 60 * 24 * 30, // 30 days in minutes
            ],
            // add custom drivers here
        ],

        'routes' => [
            /**
             * Where to redirect unauthenticated users (used by 'auth' filter)
             */
            'guest' => 'login',

            /**
             * Where to redirect authenticated users from guest-only routes (used by 'guest' filter)
             */
            'authenticated' => 'dashboard',
        ],
    ],
];
PHP;
    }
}
