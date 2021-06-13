<?php

namespace Lightpack\Console\Views;

class EventView
{
    public static function getTemplate()
    {
        return <<<'TEMPLATE'
<?php

namespace App\Events;

class __EVENT_NAME__
{
    public function handle()
    {
        
    }
}
TEMPLATE;
    }
}
