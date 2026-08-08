<?php
namespace M12R\Scanner;

use PhpParser\ParserFactory;
use PhpParser\PhpVersion;
use Symfony\Component\Finder\Finder;
use M12R\Scanner\Matcher\MatcherFactory;
use M12R\Scanner\Visitor\TraverserFactory;

class ScannerFactory
{
    private $parserFactory;
    private $traverserFactory;

    public static function create()
    {
        return new static(
            new ParserFactory(),
            new TraverserFactory(
                new MatcherFactory()
            )
        );
    }

    public function __construct(
        ParserFactory $parserFactory,
        TraverserFactory $traverserFactory
    )
    {
        $this->parserFactory = $parserFactory;
        $this->traverserFactory = $traverserFactory;
    }

    public function createFor(
    ): Scanner
    {
        $finder = new Finder();
        $parser = (new ParserFactory())->createForVersion(PhpVersion::fromComponents(8, 2));;

        return new Scanner(
            $finder,
            $parser,
            $this->traverserFactory
        );
    }
}