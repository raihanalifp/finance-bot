<?php

namespace App\Services\Transactions;

class AmountParser
{
    public function parseToken(string $token): ?float
    {
        $normalized = strtolower(trim($token));
        $normalized = str_replace(['rp', 'idr', '.', ',', '_'], '', $normalized);

        if (! preg_match('/^([0-9]+(?:\.[0-9]+)?)(k|rb|ribu|jt|juta|m)?$/', $normalized, $matches)) {
            return null;
        }

        $amount = (float) $matches[1];
        $suffix = $matches[2] ?? null;

        $multiplier = match ($suffix) {
            'k', 'rb', 'ribu' => 1000,
            'jt', 'juta', 'm' => 1000000,
            default => 1,
        };

        return $amount * $multiplier;
    }
}
