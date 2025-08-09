<?php

namespace Lightpack\Console\Views\Config;

class LogsView
{
    public static function getTemplate()
    {
        return <<<'PHP'
<?php

return [
    'logs' => [
        'path' => DIR_STORAGE . '/logs',
        'max_file_size' => 500 * 1024 * 1024, // 10mb
        'max_log_files' => 10,
    ]
];
PHP;
    }
}
