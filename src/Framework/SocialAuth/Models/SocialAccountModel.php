<?php

namespace Lightpack\SocialAuth\Models;

use Lightpack\Database\Lucid\Model;

class SocialAccountModel extends Model
{
    /** @inheritDoc */
    protected $table = 'social_accounts';

    /** @inheritDoc */
    protected $primaryKey = 'id';

    /** @inheritDoc */
    protected $timestamps = true;

    public function user()
    {
        return $this->belongsTo(config('social.user.provider'), 'user_id');
    }

    public function scopeProvider($query, $provider)
    {
        if (!empty($provider)) {
            $query->where('provider', '=', $provider);
        }
    }
}
