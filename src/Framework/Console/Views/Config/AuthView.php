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

        /**
         * Named routes used by auth filters
         */ 
        'routes' => [
            /**
             * Where to redirect unauthenticated users 
             * Note: used by 'auth' filter
             */
            'login' => 'login',

            /**
             * Where to redirect authenticated users trying to access guest-only routes 
             * Note: used by 'guest' filter
             */
            'home' => 'dashboard',
        ],
    ],
];
PHP;
    }
}
