<?php

namespace Lightpack\Audit;

use Lightpack\Database\Lucid\Model;

class AuditLog extends Model
{
    protected $table = 'audit_logs';
    protected $primaryKey = 'id';
    protected $timestamps = true;
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];
}
