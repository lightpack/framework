<?php

namespace Framework\Settings;

trait HasSettings
{
    public function settings()
    {
        return new Settings($this->table, $this->id, app('cache'));
    }
}
