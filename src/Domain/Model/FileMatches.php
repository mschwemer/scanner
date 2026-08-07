<?php
namespace M12S\Scanner\Domain\Model;

use M12S\Scanner\Utility;

class FileMatches extends \ArrayObject
{
    private $path;

    public function __construct(string $path, TheMatch ...$matches)
    {
        parent::__construct($matches);
        $this->path = $path;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }
}