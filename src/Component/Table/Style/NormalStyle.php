<?php
namespace Hail\Console\Component\Table\Style;

use Hail\Singleton\SingletonTrait;

class NormalStyle
{
    use SingletonTrait;

    public $cellPadding = 1;

    public $cellPaddingChar = ' ';

    public $verticalBorderChar = '|';

    public $rowSeparatorBorderChar = '-';

    public $rowSeparatorCrossChar = '+';

    public $rowSeparatorLeftmostCrossChar = '+';

    public $rowSeparatorRightmostCrossChar = '+';

    public $drawTableBorder = true;

    public $drawRowSeparator = false;
}
