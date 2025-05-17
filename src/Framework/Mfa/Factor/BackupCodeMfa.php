<?php
namespace Lightpack\Mfa\Factor;

use Lightpack\Mfa\MfaInterface;
use Lightpack\Auth\Models\AuthUser;
use Lightpack\Mfa\BackupCodeHelper;

/**
 * MFA Factor for Backup Codes
 */
class BackupCodeMfa implements MfaInterface
{
    public function send(AuthUser $user): void
    {
        // No challenge to send; user must use a backup code they've saved.
    }

    public function validate(AuthUser $user, ?string $input): bool
    {
        if (!$input || empty($user->mfa_backup_codes)) {
            return false;
        }
        $codes = json_decode($user->mfa_backup_codes, true);
        [$valid, $codes] = BackupCodeHelper::verifyAndRemoveCode($codes, $input);
        if ($valid) {
            $user->mfa_backup_codes = json_encode($codes);
            $user->save();
            return true;
        }
        return false;
    }

    public function getName(): string
    {
        return 'backup_code';
    }
}
