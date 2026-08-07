<?php
declare(strict_types = 1);
namespace M12S\Scanner\Tests\Unit\Matcher;

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
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use M12S\Scanner\Matcher\ConstantMatcher;
use M12S\Scanner\Visitor\GeneratorClassesResolver;
use PHPUnit\Framework\TestCase;
use PhpParser\PhpVersion;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test case
 */
class ConstantMatcherTest extends TestCase
{
    #[Test]
    public function hitsFromFixtureAreFound()
    {
        $parser = (new ParserFactory())->createForVersion(PhpVersion::fromComponents(8, 2));
        $fixtureFile = __DIR__ . '/Fixtures/ConstantMatcherFixture.php';
        $statements = $parser->parse(file_get_contents($fixtureFile));

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());
        $traverser->addVisitor(new GeneratorClassesResolver());

        $configuration = [
            'TYPO3_DLOG' => [
                'restFiles' => [
                    'Breaking-82162-GlobalErrorConstantsRemoved.rst',
                ],
            ],
        ];
        $subject = new ConstantMatcher($configuration);
        $traverser->addVisitor($subject);
        $traverser->traverse($statements);
        $expectedHitLineNumbers = [
            26,
        ];
        $actualHitLineNumbers = [];
        foreach ($subject->getMatches() as $hit) {
            $actualHitLineNumbers[] = $hit['line'];
        }
        $this->assertEquals($expectedHitLineNumbers, $actualHitLineNumbers);
    }

    /**
     * @return array
     */
    public static function matchesReturnsExpectedRestFilesDataProvider()
    {
        return [
            'a straight match' => [
                [
                    'aConstant' => [
                        'restFiles' => [
                            'Foo-1.rst',
                            'Foo-2.rst',
                        ],
                    ],
                ],
                '<?php
                $foo = aConstant;',
                [
                    0 => [
                        'restFiles' => [
                            'Foo-1.rst',
                            'Foo-2.rst',
                        ],
                    ],
                ],
            ],
        ];
    }

    #[Test]
    #[DataProvider('matchesReturnsExpectedRestFilesDataProvider')]
    public function matchesReturnsExpectedRestFiles(array $configuration, string $phpCode, array $expected)
    {
        $parser = (new ParserFactory())->createForVersion(PhpVersion::fromComponents(8, 2));
        $statements = $parser->parse($phpCode);

        $subject = new ConstantMatcher($configuration);

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());
        $traverser->addVisitor($subject);
        $traverser->traverse($statements);

        $result = $subject->getMatches();
        $this->assertSame($expected[0]['restFiles'], $result[0]['restFiles']);
    }
}
