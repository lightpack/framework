<?php

namespace Lightpack\Webhook;

use Lightpack\Database\Lucid\Model;

class WebhookEvent extends Model
{
    protected $table = 'webhook_events';
    public $timestamps = false;

    protected $casts = [
        'payload' => 'array',
        'headers' => 'array',
    ];
}
