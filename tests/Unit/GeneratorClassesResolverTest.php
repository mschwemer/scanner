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

use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;
use PhpParser\PhpVersion;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test case
 */
class GeneratorClassesResolverTest extends TestCase
{
    #[Test]
    public function visitorCreatesFullyQualifiedNameFromStringArgumentInMakeInstance()
    {
        $phpCode = <<<'EOC'
<?php
\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Does\\Not\\Exist');
EOC;
        $parser = (new ParserFactory())->createForVersion(PhpVersion::fromComponents(8, 2));
        $statements = $parser->parse($phpCode);
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new \M12R\Scanner\Visitor\GeneratorClassesResolver());
        $statements = $traverser->traverse($statements);

        // The resolver rewrites the class-string argument into its class constant
        // representation: 'TYPO3\CMS\Does\Not\Exist' -> \TYPO3\CMS\Does\Not\Exist::class
        $argument = $statements[0]->expr->args[0]->value;
        $this->assertInstanceOf(ClassConstFetch::class, $argument);
        $this->assertInstanceOf(FullyQualified::class, $argument->class);
        $this->assertEquals(['TYPO3', 'CMS', 'Does', 'Not', 'Exist'], $argument->class->getParts());
    }

    #[Test]
    public function visitorDoesNotTransformDynamicallyCreatesFullyQualifiedNameFromStringArgumentInMakeInstance()
    {
        $phpCode = <<<'EOC'
<?php
\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Does\\Not\\' . $foo);
EOC;
        $parser = (new ParserFactory())->createForVersion(PhpVersion::fromComponents(8, 2));
        $statements = $parser->parse($phpCode);
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new \M12R\Scanner\Visitor\GeneratorClassesResolver());
        $statements = $traverser->traverse($statements);
        // A runtime-concatenated argument cannot be resolved statically, so it must
        // be left untouched rather than rewritten to a ::class constant.
        $this->assertNotInstanceOf(ClassConstFetch::class, $statements[0]->expr->args[0]->value);
        $this->assertNotInstanceOf(FullyQualified::class, $statements[0]->expr->args[0]->value);
    }
}
