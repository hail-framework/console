<?php
namespace Hail\Console\Component\Table\Style;

class CompactStyle extends NormalStyle
{
    public $verticalBorderChar = ' ';

    public $drawTableBorder = false;

    public $rowSeparatorCrossChar = '-';

    public $rowSeparatorLeftmostCrossChar = '-';

    public $rowSeparatorRightmostCrossChar = '-';
}
