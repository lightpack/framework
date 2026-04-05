<?php

namespace Lightpack\Console\Views;

class CommandView
{
    public static function getTemplate()
    {
        return <<<'TEMPLATE'
<?php

namespace App\Commands;

use Lightpack\Console\Command;

class __COMMAND_NAME__ extends Command
{
    public function run(): int
    {
        $this->output->line("Hello!");
        
        return self::SUCCESS;
    }
}
TEMPLATE;
    }
}
