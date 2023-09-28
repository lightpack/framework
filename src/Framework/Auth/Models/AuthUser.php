<?php

namespace Lightpack\Auth\Models;

use Lightpack\Auth\Identity;
use Lightpack\Database\Lucid\Model;

class AuthUser extends Model implements Identity
{
    /** @inheritDoc */
    protected $table = 'users';

    /** @inheritDoc */
    protected $primaryKey = 'id';

    /** @inheritDoc */
    protected $timestamps = true;

    public function getId(): mixed
    {
        return $this->id;
    }

    public function getAuthToken(): ?string
    {
        return $this->api_token;
    }

    public function getRememberToken(): ?string
    {
        return $this->remember_token;
    }

    public function setAuthToken(string $token): void
    {
        $this->api_token = $token;
        $this->save();
    }

    public function setRememberToken(string $token): void
    {
        $this->remember_token = $token;
        $this->save();
    }
}
