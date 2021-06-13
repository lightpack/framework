<?php

namespace Lightpack\Console\Views;

class ModelView
{
    public static function getTemplate()
    {
        return <<<'TEMPLATE'
<?php

namespace App\Models;

use Lightpack\Database\Lucid\Model;

class __MODEL_NAME__ extends Model
{
    public function __construct()
    {
        parent::__construct('__TABLE_NAME__');
    }
}
TEMPLATE;
    }
}
