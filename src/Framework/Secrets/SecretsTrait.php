<?php

namespace Lightpack\Secrets;

use Lightpack\Container\Container;

trait SecretsTrait
{
    public function secrets(): Secrets
    {
        return Container::getInstance()
            ->get('secrets')
            ->group($this->table)
            ->owner($this->{$this->primaryKey});
    }
}
