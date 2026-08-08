<?php
declare(strict_types = 1);
namespace M12R\Scanner\Tests\Unit\Fixtures;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @extensionScannerIgnoreFile
 */
class CodeStatisticsFixture
{
    /**
     * Some comment
     */
    public function aMethod()
    {
        // @extensionScannerIgnoreLine
        $some = 'ignored line';

        // @extensionScannerIgnoreLine
        $some = 'other ignored line';
    }
}
