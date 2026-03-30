<?php
namespace Lightpack\Mfa\Drivers;

use Lightpack\Mfa\MfaInterface;
use Lightpack\Auth\Models\AuthUser;
use Lightpack\Mfa\BackupCodeHelper;

/**
 * MFA Driver for Backup Codes
 */
class BackupCodeDriver implements MfaInterface
{
    public function send(AuthUser $user): void
    {
        // No challenge to send; user must use a backup code they've saved.
    }

    public function validate(AuthUser $user, ?string $input): bool
    {
        $codes = $user->mfa_backup_codes;

        if (!$input || empty($codes)) {
            return false;
        }

        [$valid, $codes] = BackupCodeHelper::verifyAndRemoveCode($codes, $input);

        if ($valid) {
            $user->mfa_backup_codes = $codes;
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
