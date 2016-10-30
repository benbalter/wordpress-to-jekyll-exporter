<?php

namespace Alchemy\Zippy\Resource;

use Alchemy\Zippy\Resource\Resource as ZippyResource;

interface ResourceReaderFactory
{
    /**
     * @param ZippyResource $resource
     * @param string        $context
     *
     * @return ResourceReader
     */
    public function getReader(ZippyResource $resource, $context);
}
