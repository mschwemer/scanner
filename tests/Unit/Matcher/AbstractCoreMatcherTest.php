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

use PHPUnit\Framework\TestCase;
use M12S\Scanner\Matcher\AbstractCoreMatcher;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test case
 */
class AbstractCoreMatcherTest extends TestCase
{
    /**
     * @skip
     */
    #[Test]
    public function validateMatcherDefinitionsRunsFineWithProperDefinition()
    {
        $this->markTestSkipped('must be revisited.');

        $matcher = $this->getMockForAbstractClass(AbstractCoreMatcher::class, [], '', false);
        $configuration = [
            'foo/bar->baz' => [
                'requiredArg1' => 42,
                'restFiles' => [
                    'aRest.rst',
                ],
            ],
        ];
        $matcher->_set('matcherDefinitions', $configuration);
        $matcher->_call('validateMatcherDefinitions', ['requiredArg1']);
    }

    #[Test]
    public function validateMatcherDefinitionsThrowsIfRequiredArgIsNotInConfig()
    {
        $this->markTestSkipped('must be revisited.');

        $matcher = $this->getMockForAbstractClass(AbstractCoreMatcher::class, [], '', false);
        $configuration = [
            'foo/bar->baz' => [
                'someNotRequiredConfig' => '',
                'restFiles' => [
                    'aRest.rst',
                ],
            ],
        ];
        $matcher->_set('matcherDefinitions', $configuration);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1500492001);
        $matcher->_call('validateMatcherDefinitions', ['requiredArg1']);
    }

    #[Test]
    public function validateMatcherDefinitionsThrowsWithMissingRestFiles()
    {
        $this->markTestSkipped('must be revisited.');

        $matcher = $this->getMockForAbstractClass(AbstractCoreMatcher::class, [], '', false);
        $configuration = [
            'foo/bar->baz' => [
                'restFiles' => [],
            ],
        ];
        $matcher->_set('matcherDefinitions', $configuration);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1500496068);
        $matcher->_call('validateMatcherDefinitions', []);
    }

    #[Test]
    public function validateMatcherDefinitionsThrowsWithEmptySingleRestFile()
    {
        $this->markTestSkipped('must be revisited.');

        $matcher = $this->getMockForAbstractClass(AbstractCoreMatcher::class, [], '', false);
        $configuration = [
            'foo/bar->baz' => [
                'restFiles' => [
                    'foo.rst',
                    '',
                ],
            ],
        ];
        $matcher->_set('matcherDefinitions', $configuration);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1500735983);
        $matcher->_call('validateMatcherDefinitions', []);
    }

    #[Test]
    public function initializeMethodNameArrayThrowsWithInvalidKeys()
    {
        $this->markTestSkipped('must be revisited.');

        $matcher = $this->getMockForAbstractClass(AbstractCoreMatcher::class, [], '', false);
        $configuration = [
            'no\method\given' => [
                'restFiles' => [],
            ],
        ];
        $matcher->_set('matcherDefinitions', $configuration);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1500557309);
        $matcher->_call('initializeFlatMatcherDefinitions');
    }
}
