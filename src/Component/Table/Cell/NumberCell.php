<?php
namespace Hail\Console\Component\Table\Cell;

use Hail\Console\Component\Table\CellAttribute;
use NumberFormatter;

class NumberCell extends CellAttribute
{
    protected $locale;

    public function __construct($locale)
    {
        $this->locale = $locale;
        $this->formatter = new NumberFormatter($locale, NumberFormatter::DECIMAL);
    }

    public function format($cell)
    {
        if (\is_numeric($cell)) {
            return $this->formatter->format($cell);
        }
        return $cell;
    }
}
