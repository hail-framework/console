<?php
namespace Hail\Console\Component\Table\Style;

class BorderlessStyle extends NormalStyle
{
    public $verticalBorderChar = ' ';

    public $drawTableBorder = false;

    public $rowSeparatorCrossChar = ' ';

    public $rowSeparatorBorderChar = ' ';

    public $rowSeparatorLeftmostCrossChar = ' ';

    public $rowSeparatorRightmostCrossChar = ' ';
}
