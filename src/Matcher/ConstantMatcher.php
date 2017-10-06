<?php
declare(strict_types=1);
namespace TYPO3\CMS\Scanner\Matcher;

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

use PhpParser\Node;
use PhpParser\Node\Expr\ConstFetch;

/**
 * Find usages of class constants.
 *
 * Test for "THE_CONSTANT", matches are considered "strong"
 */
class ConstantMatcher extends AbstractCoreMatcher
{
    /**
     * Validate config
     *
     * @param array $matcherDefinitions Incoming main configuration
     */
    public function __construct(array $matcherDefinitions)
    {
        $this->matcherDefinitions = $matcherDefinitions;
        $this->validateMatcherDefinitions();
    }

    /**
     * Called by PhpParser.
     *
     * @param Node $node
     */
    public function enterNode(Node $node)
    {
        if (!$this->isFileIgnored($node)
            && !$this->isLineIgnored($node)
            && $node instanceof ConstFetch
            && in_array($node->name->toString(), array_keys($this->matcherDefinitions), true)
        ) {
            // Access to constants is detected as strong match
            $this->matches[] = [
                'restFiles' => $this->matcherDefinitions[$node->name->toString()]['restFiles'],
                'line' => $node->getAttribute('startLine'),
                'message' => 'Call to global constant "' . $node->name->toString() . '"',
                'indicator' => static::INDICATOR_STRONG,
            ];
        }
    }
}