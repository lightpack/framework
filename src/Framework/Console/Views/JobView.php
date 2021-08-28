<?php

namespace Lightpack\Console\Views;

class JobView
{
    public static function getTemplate()
    {
        return <<<'TEMPLATE'
<?php

namespace App\Jobs;

use Lightpack\Jobs\Job as Job;

class __JOB_NAME__ extends Job
{
    /** @inheritDoc */
    public function execute(array $payload)
    {
        // ...
    }
}
TEMPLATE;
    }
}
