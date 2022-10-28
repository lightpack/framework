<?php

namespace Lightpack\Database\Migrations;

use Lightpack\Database\Schema\Schema;

abstract class Migration
{
    /**
     * @var \Lightpack\Database\Schema\Schema
     */
    protected $schema;

    public function boot(Schema $schema)
    {
        $this->schema = $schema;
    }

    abstract public function up(): string;
    abstract public function down(): string;
}
