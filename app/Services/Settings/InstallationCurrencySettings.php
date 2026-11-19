<?php

namespace App\Services\Settings;

use App\Services\Payments\MoneyConverter;
use InvalidArgumentException;

/**
 * India-only currency settings.
 *
 * The application is pinned to the currency declared in config/regional.php
 * (INR). Stored settings rows are never honored for regional values, so no
 * module can override the supported currency set.
 */
final class InstallationCurrencySettings
{
    public function __construct(
        private MoneyConverter $money,
    ) {}

    public function default(bool $fresh = false): string
    {
        return $this->money->normalizeCurrency(
            (string) config('regional.currency.code', 'INR'),
        );
    }

    /**
     * @return array<int, string>
     */
    public function supported(): array
    {
        $supported = config('regional.currency.supported', ['INR']);

        if (! is_array($supported) || $supported === []) {
            return [$this->default()];
        }

        return array_values(array_map(
            fn (mixed $currency): string => $this->money->normalizeCurrency(
                is_string($currency) ? $currency : $this->default(),
            ),
            $supported,
        ));
    }

    /**
     * @return array<int, string>
     */
    public function freshSupported(): array
    {
        return $this->supported();
    }

    /**
     * Serialize rate-creating requests across concurrent writes. Kept for
     * call-site compatibility; regional settings are no longer editable.
     */
    public function lockForUpdate(): void {}

    public function supports(?string $currency, bool $fresh = false): bool
    {
        try {
            $normalized = $this->money->normalizeCurrency($currency);
        } catch (\Throwable) {
            return false;
        }

        return in_array($normalized, $this->supported(), true);
    }

    /**
     * @param  array<int, mixed>  $currencies
     * @return array<int, string>
     *
     * @throws InvalidArgumentException
     */
    public function normalize(array $currencies, ?string $default = null): array
    {
        if ($currencies === []) {
            throw new InvalidArgumentException('At least one supported currency is required.');
        }

        $normalized = [];
        foreach ($currencies as $currency) {
            if (! is_string($currency)) {
                throw new InvalidArgumentException('Supported currencies must be ISO 4217 codes.');
            }

            $normalized[] = $this->money->normalizeCurrency($currency);
        }

        $normalized = array_values(array_unique($normalized));

        if ($default !== null) {
            $default = $this->money->normalizeCurrency($default);

            if (! in_array($default, $normalized, true)) {
                throw new InvalidArgumentException('The default currency must be supported.');
            }
        }

        return $normalized;
    }
}
