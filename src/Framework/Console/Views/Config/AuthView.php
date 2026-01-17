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
            'default' => [
                'model' => App\Models\User::class,
                'identifier' => Lightpack\Auth\Identifiers\EmailPasswordIdentifier::class,
                'remember_duration' => 60 * 24 * 30, // 30 days in minutes
            ],
        ],

        // Routes used by auth filters
        'routes' => [
            'login' => 'login',      // Named route for login page
            'home' => 'dashboard',   // Named route for authenticated users
        ],
    ],
];
PHP;
    }
}
