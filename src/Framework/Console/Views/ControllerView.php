<?php

namespace Lightpack\Console\Views;

class ControllerView
{
    public static function getTemplate()
    {
        return <<<'TEMPLATE'
<?php

namespace __NAMESPACE__;

class __CONTROLLER_NAME__ 
{
    public function index()
    {
        // return response()->view('', []);
    }
}
TEMPLATE;
    }
}
