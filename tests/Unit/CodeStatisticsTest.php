<?php
declare(strict_types = 1);
namespace M12R\Scanner\Tests\Unit;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;
use PhpParser\PhpVersion;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test case
 */
class CodeStatisticsTest extends TestCase
{
    #[Test]
    public function enterNodeSumsStatistics()
    {
        $parser = (new ParserFactory())->createForVersion(PhpVersion::fromComponents(8, 2));
        $fixtureFile = __DIR__ . '/Fixtures/CodeStatisticsFixture.php';
        $statements = $parser->parse(file_get_contents($fixtureFile));

        $traverser = new NodeTraverser();
        $subject = new \M12R\Scanner\Visitor\CodeStatistics();
        $traverser->addVisitor($subject);
        $traverser->traverse($statements);

        $this->assertTrue($subject->isFileIgnored());
        $this->assertEquals(2, $subject->getNumberOfIgnoredLines());
        $this->assertEquals(7, $subject->getNumberOfEffectiveCodeLines());
    }
}
