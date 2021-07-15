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
    /** @inheritDoc */
    protected $table = '{__TABLE_NAME__}';

    /** @inheritDoc */
    protected $primaryKey = '{__PRIMARY_KEY__}';

    /** @inheritDoc */
    protected $timestamps = false;
}
TEMPLATE;
    }
}
