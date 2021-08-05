<?php

namespace Lightpack\Database\Migrations;

interface Migration
{
    public function do(): void;
    public function undo(): void;
}