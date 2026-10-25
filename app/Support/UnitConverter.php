<?php

namespace App\Support;

final class UnitConverter
{
    private const UNITS = [
        'gram' => ['dimension' => 'mass', 'factor' => 1.0],
        'gr' => ['dimension' => 'mass', 'factor' => 1.0],
        'g' => ['dimension' => 'mass', 'factor' => 1.0],
        'kilogram' => ['dimension' => 'mass', 'factor' => 1000.0],
        'kg' => ['dimension' => 'mass', 'factor' => 1000.0],
        'mililiter' => ['dimension' => 'volume', 'factor' => 1.0],
        'milliliter' => ['dimension' => 'volume', 'factor' => 1.0],
        'ml' => ['dimension' => 'volume', 'factor' => 1.0],
        'liter' => ['dimension' => 'volume', 'factor' => 1000.0],
        'litre' => ['dimension' => 'volume', 'factor' => 1000.0],
        'l' => ['dimension' => 'volume', 'factor' => 1000.0],
        'lt' => ['dimension' => 'volume', 'factor' => 1000.0],
    ];

    public static function normalize(string $unit): string
    {
        return strtolower(trim($unit));
    }

    public static function dimension(string $unit): ?string
    {
        return self::UNITS[self::normalize($unit)]['dimension'] ?? null;
    }

    public static function factor(string $unit): ?float
    {
        return self::UNITS[self::normalize($unit)]['factor'] ?? null;
    }

    public static function isConvertible(string $from, string $to): bool
    {
        return self::convert(1.0, $from, $to) !== null;
    }

    public static function convert(float $value, string $from, string $to): ?float
    {
        $from = self::normalize($from);
        $to = self::normalize($to);

        if ($from === $to) {
            return $value;
        }

        $fromUnit = self::UNITS[$from] ?? null;
        $toUnit = self::UNITS[$to] ?? null;

        if ($fromUnit === null || $toUnit === null) {
            return null;
        }

        if ($fromUnit['dimension'] !== $toUnit['dimension']) {
            return null;
        }

        return $value * $fromUnit['factor'] / $toUnit['factor'];
    }
}
