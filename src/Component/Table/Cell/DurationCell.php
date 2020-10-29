<?php
namespace Hail\Console\Component\Table\Cell;

use NumberFormatter;

class DurationCell extends NumberCell
{
    public function __construct($locale)
    {
        $this->locale = $locale;
        $this->formatter = new NumberFormatter($locale, NumberFormatter::DURATION);
    }
}
