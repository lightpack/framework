<?php

namespace Lightpack\Console\Views;

class ModelView
{
    public static function getTemplate()
    {
        return <<<'TEMPLATE'
<?php

namespace __NAMESPACE__;

use Lightpack\Database\Lucid\Model;

class __MODEL_NAME__ extends Model
{
    protected $table = '__TABLE_NAME__';

    protected $primaryKey = '__PRIMARY_KEY__';

    protected $timestamps = false;

    protected $strictMode = true;

    protected $hidden = [];

    protected $casts = [];

    protected $allowedLazyRelations = [];
}
TEMPLATE;
    }
}
