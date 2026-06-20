<?php

declare(strict_types=1);

namespace B2B\PriceImport\Service;

use Currency;
use RuntimeException;

final class CurrencyRateResolver
{
    public function getRateToUah(string $currencyCode): float
    {
        $currencyCode = strtoupper(trim($currencyCode));

        if ($currencyCode === '' || $currencyCode === 'UAH') {
            return 1.0;
        }

        $idCurrency = (int) Currency::getIdByIsoCode($currencyCode);
        if ($idCurrency <= 0) {
            throw new RuntimeException('Unknown currency: ' . $currencyCode);
        }

        $currency = new Currency($idCurrency);
        $rate = (float) $currency->conversion_rate;

        if ($rate <= 0) {
            throw new RuntimeException('Invalid currency rate: ' . $currencyCode);
        }

        return $rate;
    }
}
