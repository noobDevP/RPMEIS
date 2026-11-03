<?php

namespace App\Models\Concerns;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

trait HasTwoFactorAuthentication
{
    /**
     * Decrypt two_factor_secret transparently, falling back gracefully
     * if an existing database value was stored before encryption at rest was enabled.
     */
    public function getTwoFactorSecretAttribute(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return $value;
        }
    }

    /**
     * Encrypt two_factor_secret at rest using AES-256-CBC (APP_KEY).
     */
    public function setTwoFactorSecretAttribute(?string $value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['two_factor_secret'] = null;
            return;
        }

        $this->attributes['two_factor_secret'] = Crypt::encryptString($value);
    }

    /**
     * Decrypt and decode two_factor_recovery_codes into an array of strings.
     *
     * @return array<int, string>
     */
    public function getTwoFactorRecoveryCodesAttribute(?string $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        try {
            $json = Crypt::decryptString($value);
        } catch (DecryptException) {
            $json = $value;
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? array_values($decoded) : [];
    }

    /**
     * Encode and encrypt two_factor_recovery_codes at rest.
     *
     * @param  array<int, string>|string|null  $value
     */
    public function setTwoFactorRecoveryCodesAttribute(array|string|null $value): void
    {
        if ($value === null || $value === '' || $value === []) {
            $this->attributes['two_factor_recovery_codes'] = null;
            return;
        }

        $json = is_array($value) ? json_encode(array_values($value)) : $value;
        $this->attributes['two_factor_recovery_codes'] = Crypt::encryptString((string) $json);
    }

    /**
     * Generate a fresh set of 8 single-use recovery codes (format: XXXX-XXXX)
     * and persist them encrypted to the database.
     *
     * @return array<int, string>
     */
    public function generateRecoveryCodes(int $count = 8): array
    {
        $alphabet = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $max = strlen($alphabet) - 1;
        $codes = [];

        for ($i = 0; $i < $count; $i++) {
            $part1 = '';
            $part2 = '';
            for ($j = 0; $j < 4; $j++) {
                $part1 .= $alphabet[random_int(0, $max)];
                $part2 .= $alphabet[random_int(0, $max)];
            }
            $codes[] = "{$part1}-{$part2}";
        }

        $this->two_factor_recovery_codes = $codes;
        $this->save();

        return $codes;
    }

    /**
     * Verify and consume a single-use recovery code in constant time.
     * If valid, removes the code from the user's list and saves the model.
     */
    public function consumeRecoveryCode(string $inputCode): bool
    {
        $normalizedInput = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $inputCode));
        if ($normalizedInput === '') {
            return false;
        }

        $codes = $this->two_factor_recovery_codes;
        if (empty($codes)) {
            return false;
        }

        $matchedIndex = null;
        foreach ($codes as $index => $storedCode) {
            $normalizedStored = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', (string) $storedCode));
            if ($normalizedStored !== '' && hash_equals($normalizedStored, $normalizedInput)) {
                $matchedIndex = $index;
                break;
            }
        }

        if ($matchedIndex === null) {
            return false;
        }

        unset($codes[$matchedIndex]);
        $this->two_factor_recovery_codes = array_values($codes);
        $this->save();

        return true;
    }
}
