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
use Lightpack\Filters\FilterInterface;

class __FILTER_NAME__ implements IFilter
{
    public function before(Request $request, array $params = [])
    {
        // ...
    }

    public function after(Request $request, Response $response, array $params = []): Response
    {
        return $response;
    }
}
TEMPLATE;
    }
}
