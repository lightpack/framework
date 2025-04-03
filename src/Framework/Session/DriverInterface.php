<?php

namespace Lightpack\Session;

interface DriverInterface
{
    public function set(string $key, $value);
    public function get(?string $key = null, $default = null);
    public function delete(string $key);
    public function regenerate(): bool;
    public function destroy();
    public function started(): bool;
}
