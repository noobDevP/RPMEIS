<?php

namespace App\Enums;

enum ApprovalStatus: string
{
    case Go = 'GO';
    case NoGo = 'NO GO';
    case Compliance = 'COMPLIANCE';
    case WaitingForResult = 'WAITING FOR RESULT';

    public static function tryFromValue(?string $value): ?self
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $normalized = strtoupper(trim($value));

        return match ($normalized) {
            'GO' => self::Go,
            'NO GO' => self::NoGo,
            'COMPLIANCE' => self::Compliance,
            'WAITING FOR RESULT' => self::WaitingForResult,
            default => null,
        };
    }

    public function cssClass(): string
    {
        return match ($this) {
            self::Go => 'status-go',
            self::NoGo => 'status-no-go',
            self::Compliance => 'status-compliance',
            self::WaitingForResult => 'status-waiting',
        };
    }

    public static function cssClassFor(?string $value): string
    {
        return self::tryFromValue($value)?->cssClass() ?? 'status-pending';
    }
}
