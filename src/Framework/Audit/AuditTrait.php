<?php

namespace Lightpack\Audit;

trait AuditTrait
{
    /**
     * Log an audit event for this model instance.
     * Usage: $model->audit([ 'action' => 'update', ... ]);
     *
     * @param array $data Audit log data (same as Audit::log)
     * @return AuditLog
     */
    public function audit(array $data): AuditLog
    {
        $data['audit_type'] = $this->table;
        $data['audit_id'] = $this->id;
        $data['tenant_id'] = $data['tenant_id'] ?? $this->tenant_id ?? 0;

        return Audit::log($data);
    }
}
