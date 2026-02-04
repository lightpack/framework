<?php

namespace Lightpack\Auth\Models;

use Lightpack\Auth\IdentityInterface;
use Lightpack\Database\Lucid\Model;
use Lightpack\Auth\Models\AccessToken;

class AuthUser extends Model implements IdentityInterface
{
    protected $table = 'users';

    protected $primaryKey = 'id';

    protected $timestamps = true;

    /**
     * Attribute casts for automatic type conversion
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'mfa_backup_codes' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Hidden attributes (excluded from toArray/JSON serialization)
     */
    protected $hidden = [
        'password',
        'remember_token',
        'verification_token',
        'recovery_token',
        'mfa_totp_secret',
        'mfa_backup_codes',
    ];

    /**
     * The access token used for the current API request, if any.
     *
     * @var \Lightpack\Auth\Models\AccessToken|null
     */
    public ?AccessToken $currentAccessToken = null;

    public function getId(): mixed
    {
        return $this->id;
    }

    public function getRememberToken(): ?string
    {
        return $this->remember_token;
    }

    public function setRememberToken(string $token): void
    {
        $this->remember_token = $token;
        $this->save();
    }

    public function accessTokens()
    {
        return $this->hasMany(AccessToken::class, 'user_id');
    }

    public function createToken(string $name, array $abilities = ['*'], ?string $expiresAt = null): AccessToken
    {
        $plainTextToken = bin2hex(random_bytes(40));

        $accessToken = new AccessToken;

        $accessToken->user_id = $this->id;
        $accessToken->name = $name;
        $accessToken->token = hash('sha256', $plainTextToken);
        $accessToken->abilities = $abilities; // Cast will handle JSON encoding
        $accessToken->expires_at = $expiresAt;
        $accessToken->save();

        // Set the plain text token temporarily for the response
        $accessToken->plainTextToken = $plainTextToken;

        return $accessToken;
    }

    public function deleteTokens(?string $token = '')
    {
        if (!$token) {
            // Delete all tokens for the current user only
            AccessToken::query()->where('user_id', $this->id)->delete();
            return;
        }

        $tokenHash = hash('sha256', $token);

        AccessToken::query()
            ->where('user_id', $this->id)
            ->where('token', $tokenHash)
            ->delete();
    }

    /**
     * Delete one or more tokens by their database ID(s).
     *
     * @param int|int[] $ids
     */
    public function deleteTokensById($ids)
    {
        $ids = (array) $ids;

        if (empty($ids)) {
            return;
        }

        AccessToken::query()
            ->where('user_id', $this->id)
            ->whereIn('id', $ids)
            ->delete();
    }

    public function deleteCurrentRequestToken()
    {
        $plainTextToken = request()->bearerToken();

        if ($plainTextToken) {
            $this->deleteTokens($plainTextToken);
        }
    }

    public function tokenCan(string $ability): bool
    {
        return $this->currentAccessToken && $this->currentAccessToken->can($ability);
    }

    public function tokenCannot(string $ability): bool
    {
        return !$this->tokenCan($ability);
    }
}
