<?php

namespace PHPosh\Provider\Poshmark;

class Price
{
    /** @var string Decimal amount, such as "4.95" */
    private $amount = '0.00';

    /** @var string Three char currency code, such as "USD" */
    private $currency_code = "USD";

    /** @var array<string, string> Currency symbol to 3 char code map */
    public const SYMBOL_MAP = [
        '$' => 'USD',
        '£' => 'GBP',
        '€' => 'EUR',
    ];

    /**
     * @param string|float|null $priceString
     * @return Price
     */
    public static function fromString($priceString): Price
    {
        $priceString = trim(((string) $priceString) ?: '$0.00');
        $price = new self();
        $matches = [];
        // match number and symbol, "$123.23" or "$123"
        $result = preg_match('/([$£€])? *([0-9]+\.[0-9]+|[0-9]+)/u', $priceString, $matches);
        if ($result) {
            $value = $matches[2] ?? '0.00';
            $price->setAmount($value);
            $symbol = $matches[1] ?? '$';
            $code = self::SYMBOL_MAP[$symbol] ?? 'USD';
            return $price->setCurrencyCode($code);
        }
        // Match second format type, "123.12 USD" or "123 USD"
        $result = preg_match('/([0-9]+\.[0-9]+|[0-9]+) ?([A-Za-z]{3})/u', $priceString, $matches);
        if ($result) {
            $price->setAmount($matches[1] ?? '0.00');
            $price->setCurrencyCode($matches[2] ?? 'USD');
        }
        return $price;
    }

    /**
     * @return string
     */
    public function getAmount(): string
    {
        return number_format($this->amount, 2, '.', '');
    }

    /**
     * @param string $amount
     *
     * @return Price
     */
    public function setAmount(string $amount): Price
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * @return string
     */
    public function getCurrencyCode(): string
    {
        return $this->currency_code;
    }

    /**
     * @param string $currency_code
     *
     * @return Price
     */
    public function setCurrencyCode(string $currency_code): Price
    {
        $this->currency_code = $currency_code;
        return $this;
    }

    /**
     * @return string
     */
    public function getCurrencySymbol(): string
    {
        $map = array_flip(self::SYMBOL_MAP);
        return $map[$this->getCurrencyCode()] ?? '$';
    }

    public function __toString()
    {
        return "" . $this->getCurrencySymbol() . $this->getAmount();
    }
}
