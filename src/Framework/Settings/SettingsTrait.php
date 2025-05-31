<?php

namespace Lightpack\Settings;

trait HasSettings
{
    public function settings(): Settings
    {
        return app('settings')
            ->group($this->table)
            ->owner($this->{$this->primaryKey});
    }
}
