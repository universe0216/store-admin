<?php

namespace App\Enums\Concerns;

trait HasEnumOptions
{
    /**
     * @return array<string, string> value => label
     */
    public static function labels(): array
    {
        $labels = [];

        foreach (self::cases() as $case) {
            $labels[$case->value] = $case->label();
        }

        return $labels;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[] = [
                'value' => $case->value,
                'label' => $case->label(),
            ];
        }

        return $options;
    }

    public static function tryFromString(?string $value): ?static
    {
        if ($value === null || $value === '') {
            return null;
        }

        return self::tryFrom($value);
    }

    public static function isValid(?string $value): bool
    {
        return self::tryFromString($value) !== null;
    }
}
