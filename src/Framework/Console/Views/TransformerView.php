<?php

namespace Lightpack\Console\Views;

class TransformerView
{
    public static function getTemplate(): string
    {
        return <<<PHP
<?php

namespace __NAMESPACE__;

use Lightpack\Database\Lucid\Model;
use Lightpack\Database\Lucid\Transformer;

class __TRANSFORMER_NAME__ extends Transformer
{
    protected function data(Model \$model): array
    {
        return [
            // ...
        ];
    }
}
PHP;
    }
}
