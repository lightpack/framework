<?php

namespace Lightpack\Console\Views;

class CommandView
{
    public static function getTemplate()
    {
        return <<<'TEMPLATE'
<?php

namespace App\Commands;

use Lightpack\Console\CommandInterface;

class __COMMAND_NAME__ implements CommandInterface
{
    public function run(array $arguments = [])
    {
        fputs(STDOUT, "Hello\n\n");
    }
}
TEMPLATE;
    }
}
