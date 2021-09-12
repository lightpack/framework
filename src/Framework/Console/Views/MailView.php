<?php

namespace Lightpack\Console\Views;

class MailView
{
    public static function getTemplate()
    {
        return <<<'TEMPLATE'
<?php

namespace App\Mails;

use Lightpack\Mail\Mail;

class __MAIL_NAME__ extends Mail
{
    public function execute(array $payload = [])
    {
        // ...
    }
}
TEMPLATE;
    }
}
