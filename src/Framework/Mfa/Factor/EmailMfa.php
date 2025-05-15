<?php
namespace Lightpack\Mfa\Factor;

use Lightpack\Cache\Cache;
use Lightpack\Mfa\MfaInterface;
use Lightpack\Mfa\Job\EmailMfaJob;

/**
 * Email-based MFA factor implementation.
 */
class EmailMfa implements MfaInterface
{
    protected $cache;
    protected $codeTtl = 300; // 5 minutes

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    public function send($user): void
    {
        $code = random_int(100000, 999999);
        $key = $this->getCacheKey($user);
        $this->cache->set($key, $code, $this->codeTtl);

        (new EmailMfaJob)->dispatch([
            'user' => $user->toArray(),
            'mfa_code' => $code,
        ]);
    }

    public function validate($user, $input): bool
    {
        $key = $this->getCacheKey($user);
        $code = $this->cache->get($key);
        if ($code && $input == $code) {
            $this->cache->delete($key); // One-time use
            return true;
        }
        return false;
    }

    public function getName(): string
    {
        return 'email';
    }

    protected function getCacheKey($user): string
    {
        return 'mfa_email_' . $user->id;
    }
}
