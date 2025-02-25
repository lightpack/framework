<?php

namespace Lightpack\Auth\Models;

use Lightpack\Database\Lucid\Model;

class AccessToken extends Model
{
    protected $table = 'access_tokens';
    
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
        $this->abilities = json_decode($this->abilities, true);
        
        if (in_array('*', $this->abilities)) {
            return true;
        }
        
        return in_array($ability, $this->abilities);
    }
}
