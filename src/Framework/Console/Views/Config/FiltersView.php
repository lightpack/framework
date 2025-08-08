<?php

namespace Lightpack\Console\Views\Config;

class FiltersView
{
    public static function getTemplate()
    {
        return <<<'PHP'
<?php

return [
    'filters' => [
        // Filter settings
    ],
];
PHP;
    }
}
