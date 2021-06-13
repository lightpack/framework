<?php

namespace Lightpack\Console\Views;

class FilterView
{
    public static function getTemplate()
    {
        return <<<'TEMPLATE'
<?php

namespace App\Filters;

use Lightpack\Http\Request;
use Lightpack\Http\Response;
use Lightpack\Filters\IFilter;

class __FILTER_NAME__ implements IFilter
{
    public function before(Request $request)
    {
       
    }

    public function after(Request $request, Response $response): Response
    {
        return $response;
    }
}
TEMPLATE;
    }
}
