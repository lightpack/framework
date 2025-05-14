<?php

namespace Lightpack\Audit;

class Audit
{
    /**
     * Log an audit event.
     *
     * @param array $data
     * @return AuditLog
     */
    public static function log(array $data): AuditLog
    {
        $log = new AuditLog();
        $log->user_id = $data['user_id'] ?? null;
        $log->action = $data['action'] ?? '';
        $log->audit_type = $data['audit_type'] ?? '';
        $log->audit_id = $data['audit_id'] ?? null;
        $log->old_values = $data['old_values'] ?? null;
        $log->new_values = $data['new_values'] ?? null;
        $log->url = $data['url'] ?? null;
        $log->ip_address = $data['ip_address'] ?? null;
        $log->user_agent = $data['user_agent'] ?? null;
        $log->message = $data['message'] ?? null;
        $log->save();
        return $log;
    }
}
