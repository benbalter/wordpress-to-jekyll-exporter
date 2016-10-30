<?php

namespace Alchemy\Zippy\Resource\Writer;

use Alchemy\Zippy\Resource\ResourceReader;
use Alchemy\Zippy\Resource\ResourceWriter;

class FilesystemWriter implements ResourceWriter
{
    /**
     * @param ResourceReader $reader
     * @param string $target
     */
    public function writeFromReader(ResourceReader $reader, $target)
    {
        $directory = dirname($target);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($target, $reader->getContentsAsStream());
    }
}
