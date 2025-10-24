<?php

namespace Lightpack\Auth\Models;

use Lightpack\Database\Lucid\Model;

class AccessToken extends Model
{
    protected $table = 'access_tokens';
    
    protected $timestamps = true;
    
    protected $casts = [
        'abilities' => 'array',
    ];
    
    public function user()
    {
        return $this->belongsTo(AuthUser::class, 'user_id');
    }
    
    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false;
        }
        
        return strtotime($this->expires_at) < time();
    }
    
    public function can(string $ability): bool
    {
        // abilities is always an array due to cast
        if (in_array('*', $this->abilities)) {
            return true;
        }
        
        return in_array($ability, $this->abilities);
    }
}
