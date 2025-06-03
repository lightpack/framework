<?php

namespace Lightpack\Settings;

use Lightpack\Container\Container;

trait SettingsTrait
{
    public function settings(): Settings
    {
        return Container::getInstance()
            ->get('settings')
            ->group($this->table)
            ->owner($this->{$this->primaryKey});
    }
}
