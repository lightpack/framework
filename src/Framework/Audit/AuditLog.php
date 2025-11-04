<?php

namespace Lightpack\Audit;

use Lightpack\Auth\Models\AuthUser;
use Lightpack\Database\Lucid\Model;

class AuditLog extends Model
{
    protected $table = 'audit_logs';
    protected $primaryKey = 'id';
    protected $timestamps = true;
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Relation to user.
     */
    public function user()
    {
        return $this->belongsTo(AuthUser::class, 'user_id');
    }

    /**
     * Get associative diff between old_values and new_values
     */
    public function diff(): array
    {
        $old = is_array($this->old_values) ? $this->old_values : [];
        $new = is_array($this->new_values) ? $this->new_values : [];
        $added = array_diff_assoc($new, $old);
        $removed = array_diff_assoc($old, $new);
        return ['added' => $added, 'removed' => $removed];
    }

    /**
     * Scope for filtering by user_id
     */
    public function scopeUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for filtering by action
     */
    public function scopeAction($query, $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope for filtering by audit_type
     */
    public function scopeAuditType($query, $type)
    {
        return $query->where('audit_type', $type);
    }
}
