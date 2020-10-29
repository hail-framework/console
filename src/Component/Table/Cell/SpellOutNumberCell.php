<?php
namespace Hail\Console\Component\Table\Cell;

use NumberFormatter;

class SpellOutNumberCell extends NumberCell
{
    public function __construct($locale)
    {
        $this->locale = $locale;
        $this->formatter = new NumberFormatter($locale, NumberFormatter::SPELLOUT);
        $this->formatter->setTextAttribute(NumberFormatter::DEFAULT_RULESET, '%financial');
    }
}
