<?php

namespace Alchemy\Zippy\Resource\Reader\Stream;

use Alchemy\Zippy\Resource\Resource as ZippyResource;
use Alchemy\Zippy\Resource\ResourceReader;
use Alchemy\Zippy\Resource\ResourceReaderFactory;

class StreamReaderFactory implements ResourceReaderFactory
{
    /**
     * @param ZippyResource $resource
     * @param string        $context
     *
     * @return ResourceReader
     */
    public function getReader(ZippyResource $resource, $context)
    {
        return new StreamReader($resource);
    }
}
