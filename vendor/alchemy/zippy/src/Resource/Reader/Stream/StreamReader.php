<?php

namespace Alchemy\Zippy\Resource\Reader\Stream;

use Alchemy\Zippy\Resource\Resource as ZippyResource;
use Alchemy\Zippy\Resource\ResourceReader;

class StreamReader implements ResourceReader
{
    /**
     * @var ZippyResource
     */
    private $resource;

    /**
     * @param ZippyResource $resource
     */
    public function __construct(ZippyResource $resource)
    {
        $this->resource = $resource;
    }

    /**
     * @return string
     */
    public function getContents()
    {
        return file_get_contents($this->resource->getOriginal());
    }

    /**
     * @return resource
     */
    public function getContentsAsStream()
    {
        $stream = is_resource($this->resource->getOriginal()) ?
            $this->resource->getOriginal() : @fopen($this->resource->getOriginal(), 'rb');

        return $stream;
    }
}
