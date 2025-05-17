<?php
namespace Lightpack\Mfa;

class BackupCodeHelper
{
    public static function generateCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(4))); // 8-char code
        }
        return $codes;
    }

    public static function hashCodes(array $codes): array
    {
        return array_map(fn($code) => password_hash($code, PASSWORD_DEFAULT), $codes);
    }

    public static function verifyAndRemoveCode(array $hashes, string $input): array
    {
        foreach ($hashes as $i => $hash) {
            if (password_verify($input, $hash)) {
                unset($hashes[$i]);
                return [true, array_values($hashes)];
            }
        }
        return [false, $hashes];
    }
}
