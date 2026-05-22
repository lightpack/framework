<?php

namespace Lightpack\Console\Views;

class TransformerView
{
    public static function getTemplate(): string
    {
        return <<<PHP
<?php

namespace __NAMESPACE__;

use Lightpack\Database\Lucid\Transformer;

class __TRANSFORMER_NAME__ extends Transformer
{
    protected function data(\$model): array
    {
        return [
            // ...
        ];
    }
}
PHP;
    }
}
