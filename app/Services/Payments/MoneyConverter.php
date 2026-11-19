<?php

namespace App\Services\Payments;

use App\Services\Localization\ApplicationLocale;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use InvalidArgumentException;
use OpenKOS\Core\Data\Payment\Money;

final class MoneyConverter
{
    private const MAX_INTEGER_DIGITS = 17;

    public function __construct(private ?ApplicationLocale $localeResolver = null) {}

    /**
     * India-only application: INR is the sole supported currency. The
     * authoritative definition lives in config/regional.php. Historical rows
     * from legacy installations may still carry other ISO 4217 codes; they
     * render with the regional minor-unit scale as a fallback.
     *
     * @var array<string, int>
     */
    private const MINOR_UNITS = [
        'INR' => 2,
    ];

    /**
     * @return array<string, int>
     */
    public function scales(): array
    {
        return self::MINOR_UNITS;
    }

    public function normalizeCurrency(?string $currency = null): string
    {
        $currency = strtoupper(trim($currency ?? (string) config('regional.currency.code', 'INR')));

        if (! preg_match('/\A[A-Z]{3}\z/D', $currency)) {
            throw new InvalidArgumentException('Currency must be a three-letter ISO 4217 code.');
        }

        return $currency;
    }

    public function scale(string $currency): int
    {
        return self::MINOR_UNITS[$this->normalizeCurrency($currency)]
            ?? (int) config('regional.currency.minor_units', 2);
    }

    public function normalizeAmount(string $majorAmount, string $currency): string
    {
        $currency = $this->normalizeCurrency($currency);
        $scale = $this->scale($currency);

        try {
            $amount = BigDecimal::of($majorAmount);

            if ($amount->isNegative()) {
                throw new InvalidArgumentException('Money amounts cannot be negative.');
            }

            $normalized = $amount
                ->toScale($scale, RoundingMode::Unnecessary)
                ->toString();

            $integerDigits = max(
                1,
                strlen($amount->toScale($scale, RoundingMode::Unnecessary)->getUnscaledValue()->abs()->toString()) - $scale,
            );

            if ($integerDigits > self::MAX_INTEGER_DIGITS) {
                throw new InvalidArgumentException(
                    'Amount exceeds the 17-digit integer limit for decimal(20,3) storage.',
                );
            }

            return $normalized;
        } catch (InvalidArgumentException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new InvalidArgumentException(
                'Amount has more precision than the configured currency supports.',
                previous: $exception,
            );
        }
    }

    public function compare(string $left, string $right): int
    {
        return BigDecimal::of($left)->compareTo(BigDecimal::of($right));
    }

    public function subtract(string $left, string $right, string $currency): string
    {
        $this->normalizeCurrency($currency);

        return BigDecimal::of($left)
            ->minus($right)
            ->toString();
    }

    public function toMoney(string $majorAmount, string $currency): Money
    {
        $currency = $this->normalizeCurrency($currency);
        $scale = $this->scale($currency);

        try {
            $amount = BigDecimal::of($majorAmount);

            if ($amount->isNegative()) {
                throw new InvalidArgumentException('Money amounts cannot be negative.');
            }

            $minorUnits = $amount
                ->multipliedBy(10 ** $scale)
                ->toScale(0, RoundingMode::Unnecessary)
                ->toInt();
        } catch (InvalidArgumentException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new InvalidArgumentException(
                'Amount has more precision than the configured currency supports.',
                previous: $exception,
            );
        }

        return new Money($minorUnits, $currency);
    }

    public function format(string $majorAmount, string $currency, ?string $locale = null): string
    {
        $currency = $this->normalizeCurrency($currency);
        $scale = $this->scale($currency);
        $amount = BigDecimal::of($majorAmount)->toScale($scale, RoundingMode::HalfEven);
        $absolute = $amount->abs()->toScale($scale, RoundingMode::Unnecessary)->toString();
        [$integer, $fraction] = array_pad(explode('.', $absolute, 2), 2, '');

        $localeResolver = $this->localeResolver ?? new ApplicationLocale;
        $displayLocale = $localeResolver->intlLocale($locale);
        $formatter = new \NumberFormatter(
            $displayLocale,
            \NumberFormatter::CURRENCY,
        );
        $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, $scale);

        $groupingSeparator = $formatter->getSymbol(\NumberFormatter::MONETARY_GROUPING_SEPARATOR_SYMBOL);
        $decimalSeparator = $formatter->getSymbol(\NumberFormatter::MONETARY_SEPARATOR_SYMBOL);
        $pattern = (string) $formatter->getPattern();
        preg_match('/([#,]+0)(?:\.[0#]+)?/', $pattern, $matches);
        $integerPattern = $matches[1] ?? '#,##0';
        $groups = explode(',', $integerPattern);
        $primaryGroupSize = strlen((string) end($groups));
        $secondaryGroupSize = count($groups) > 2
            ? strlen((string) $groups[count($groups) - 2])
            : $primaryGroupSize;
        $formattedInteger = $this->groupInteger(
            $integer,
            $groupingSeparator,
            $primaryGroupSize,
            $secondaryGroupSize,
        );
        $formattedNumber = $formattedInteger.($scale > 0 ? $decimalSeparator.$fraction : '');
        $template = $formatter->formatCurrency($amount->isNegative() ? -1.0 : 1.0, $currency);
        $templateNumber = '1'.($scale > 0 ? $decimalSeparator.str_repeat('0', $scale) : '');

        return is_string($template)
            ? str_replace($templateNumber, $formattedNumber, $template)
            : $formattedNumber;
    }

    private function groupInteger(
        string $integer,
        string $separator,
        int $primaryGroupSize,
        int $secondaryGroupSize,
    ): string {
        if (strlen($integer) <= $primaryGroupSize) {
            return $integer;
        }

        $groups = [substr($integer, -$primaryGroupSize)];
        $integer = substr($integer, 0, -$primaryGroupSize);

        while ($integer !== '') {
            $groups[] = substr($integer, -$secondaryGroupSize);
            $integer = substr($integer, 0, -$secondaryGroupSize);
        }

        return implode($separator, array_reverse($groups));
    }
}
