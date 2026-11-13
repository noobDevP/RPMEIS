<?php

namespace App\Support;

class LegacySignatureVerifier
{
    /**
     * Compute HMAC-SHA256 signature for "{timestamp}.{rawBody}".
     */
    public function sign(string $rawBody, int $timestamp, string $secret): string
    {
        return hash_hmac('sha256', $timestamp . '.' . $rawBody, $secret);
    }

    /**
     * Verify an incoming HMAC-SHA256 signature and timestamp window.
     */
    public function verify(string $rawBody, ?string $timestampHeader, ?string $signatureHeader, string $secret, int $toleranceSeconds = 300): bool
    {
        if ($secret === '' || $timestampHeader === null || $signatureHeader === null) {
            return false;
        }

        if (! ctype_digit(trim($timestampHeader))) {
            return false;
        }

        $timestamp = (int) trim($timestampHeader);
        if (abs(time() - $timestamp) > max(1, $toleranceSeconds)) {
            return false;
        }

        $provided = strtolower(trim($signatureHeader));
        if (str_starts_with($provided, 'sha256=')) {
            $provided = substr($provided, 7);
        }

        $expected = $this->sign($rawBody, $timestamp, $secret);

        return hash_equals($expected, $provided);
    }
}
