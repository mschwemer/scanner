# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A PHP library (`m12s/typo3-cms-scanner`, a fork of `typo3/cms-scanner`) that statically analyses PHP files for usage of code that TYPO3 deprecated or removed. It parses files with `nikic/php-parser` into an AST and runs a set of *matchers* (php-parser node visitors) over it, each driven by a *rule set* — a plain PHP array of deprecated symbols. It is a library only: it ships no CLI. Consumers build the tool around it (see `examples/test.php`).

Matcher classes in `src/Matcher/` and visitors in `src/Visitor/` are ports of TYPO3 core's EXT:install extension scanner (hence the `@internal … only meant to be used within EXT:install` docblocks). Keeping them in sync with upstream core is a recurring task — see commits like `[FEATURE] Update code with changes in TYPO3 core`. Commit subjects follow the TYPO3 convention: `[FEATURE]`, `[TASK]`, `[BUGFIX]`.

## Commands

Composer is configured with `bin-dir: .Build/bin` and `vendor-dir: .Build/vendor` — there is no top-level `vendor/`.

```bash
composer install
./.Build/bin/phpunit tests                                   # whole suite
./.Build/bin/phpunit tests/Unit/Matcher/MethodCallMatcherTest.php   # one file
./.Build/bin/phpunit --filter hitsFromFixtureAreFound tests   # one test
```

There is no `phpunit.xml`, so tests must be given a path and there are no named suites. No linter or static analyser is configured.

## Known state / gotchas

Verify these before trusting them — they reflect the repository as of the last inspection:

- **Nothing autoloads right now.** `composer.json` maps PSR-4 `M12S\Scanner\` → `src/`, but every file in `src/` and `tests/` still declares `TYPO3\CMS\Scanner`. Both `class_exists('TYPO3\CMS\Scanner\Scanner')` and `class_exists('M12S\Scanner\Scanner')` return false. Completing the rename across `src/` and `tests/` (then `composer dump-autoload`) is the fix.
- **The test suite does not run.** Two independent causes, both predating the dependency bump:
  - PHPUnit 12 dropped doc-comment metadata, so the `/** @test */` annotations no longer register anything — every test class reports "No tests found in class". They need `#[PHPUnit\Framework\Attributes\Test]` (and `#[DataProvider]`), and data provider methods must be `static`.
  - `tests/` still uses the php-parser v4 API (`ParserFactory::create(ParserFactory::PREFER_PHP7)`), removed in v5. `src/` was already migrated (`createForVersion()`, `enterNode(): null`); the test-side equivalent is `(new ParserFactory())->createForVersion(PhpVersion::fromComponents(8, 2))`.
- **`src/Visitor/MatcherFactory.php` is dead code** — a verbatim core copy importing `TYPO3\CMS\Core\Utility\GeneralUtility` and `TYPO3\CMS\Install\ExtensionScanner\CodeScannerInterface`, neither of which exists here. The factory actually used is `src/Matcher/MatcherFactory.php`.
- **`examples/test.php` is stale**: it requires `../vendor/autoload.php` (should be `.Build/vendor`) and calls `createFor(ParserFactory::PREFER_PHP7)`, while `ScannerFactory::createFor()` now takes no arguments.
- `Readme.md`'s matcher list predates `AbstractMethodImplementationMatcher`, `ConstructorArgumentMatcher`, `MethodCallArgumentValueMatcher`, and `ScalarStringMatcher`.
- `ScannerFactory::createFor()` hardcodes the parser target to PHP 8.2.

## Architecture

Scan pipeline, from the outside in:

1. **`MatcherBundle`** (`src/Domain/Model/`) is the unit of configuration: a rule-set directory (e.g. `config/Matcher/v13`), a documentation path used as the prefix for `.rst` references, and a variadic list of matcher class names. For each class it derives `configurationFile = <configPath>/<ShortClassName>.php`. **The rule file's basename must equal the matcher class's short name** — that mapping is the only link between the two.
2. **`MatcherBundleCollection`** merges several bundles (e.g. v12 + v13 rules) into one flat configuration array.
3. **`Scanner::scanPath()`** walks `*.php` under a directory with symfony/finder and calls `scanFile()` per file. Matchers accumulate state, so `TraverserFactory::createMatchers()` builds a **fresh matcher instance for every file**.
4. **`TraverserFactory::createTraverser()`** assembles the visitor chain, and order matters: `NameResolver` (expands `use` statements to FQCNs) → `GeneratorClassesResolver` (turns `makeInstance('Foo\Bar')` string arguments into `FullyQualified` nodes) → `CodeStatistics` (counts effective and ignored lines) → all matchers.
5. **`MatcherFactory::createAll()`** instantiates matchers, requiring exactly one of `configurationFile` / `configurationArray`, and prefixes every `restFiles` entry with the bundle's documentation path.
6. Each matcher pushes raw arrays onto `$this->matches` with keys `restFiles`, `line`, `message`, `indicator` (and sometimes `subject`).
7. **`Scanner::buildMatches()`** converts those into `TheMatch` objects. Severity is *inferred from the `.rst` filename prefix* — `Breaking-…` / `Deprecation-…` / `Important-…` / `Feature-…` — and when a match cites several files the highest wins (BREAKING 100 > DEPRECATION 80 > IMPORTANT 50 > FEATURE 20), defaulting to `IMPORTANT`. A parse error yields a single BREAKING match with indicator `impossible` instead of aborting.

Result objects are `\ArrayObject` subclasses: `DirectoryMatches` holds `FileMatches`, which holds `TheMatch`.

Two cross-cutting matcher behaviours live in `AbstractCoreMatcher`: the `@extensionScannerIgnoreLine` / `@extensionScannerIgnoreFile` annotations (every `enterNode()` guards on `isFileIgnored()` / `isLineIgnored()`), and `initializeFlatMatcherDefinitions()`, which builds the method-name-keyed "candidates" lookup used for **weak** matches. Matchers report `indicator => 'strong'` when the class is fully qualified and resolvable, `'weak'` when only the method/property name matched — consumers are expected to treat weak hits as needing human review.

## Rule sets (`config/Matcher/`)

One directory per target TYPO3 version, `v7` through `v14`. Each file is a plain PHP `return [...]`, keyed by the symbol being matched — `Foo\Bar::method`, `Foo\Bar->method`, a bare class or constant name, or a literal `$GLOBALS['…']['…']` expression, depending on the matcher. Every entry must carry a non-empty `restFiles` array; matchers declare additional required keys via `validateMatcherDefinitions()` in their constructor (e.g. `MethodCallStaticMatcher` requires `numberOfMandatoryArguments` and `maximumNumberOfArguments`), and a missing key throws at construction time.

The set of files differs per version — `v14` adds `MethodCallArgumentValueMatcher` and `ScalarStringMatcher`; `v7` still has `ArrayMatcher`, `ClassNamePatternMatcher`, `GlobalMatcher`. A bundle lists its matcher classes explicitly, so only reference classes whose rule file exists in that version directory.

## Tests

`tests/Unit/Matcher/<Matcher>Test.php` pairs with `tests/Unit/Matcher/Fixtures/<Matcher>Fixture.php`. Each test parses the fixture, runs the traverser, and asserts the **exact line numbers** the matcher hit. Editing a fixture shifts those numbers — update the expectations in the same change. New or extended matchers are expected to come with both files.
