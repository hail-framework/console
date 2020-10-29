<?php
namespace Hail\Console\Component\Table\Cell;

class CurrencyCell extends NumberCell
{
    protected $currency;

    public function __construct($locale, $currency)
    {
        parent::__construct($locale);
        $this->currency = $currency;
    }

    public function format($cell)
    {
        return $this->formatter->formatCurrency($cell, $this->currency);
    }
}
