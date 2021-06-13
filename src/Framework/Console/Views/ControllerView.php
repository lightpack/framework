<?php

namespace Lightpack\Console\Views;

class ControllerView
{
    public static function getTemplate()
    {
        return <<<'TEMPLATE'
<?php

namespace App\Controllers;

class __CONTROLLER_NAME__ 
{
    public function index()
    {
        // Code goes here
    }
}
TEMPLATE;
    }
}
