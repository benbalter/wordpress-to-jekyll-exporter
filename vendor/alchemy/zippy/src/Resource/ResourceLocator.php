<?php

namespace Alchemy\Zippy\Resource;

use Alchemy\Zippy\Resource\Resource AS ZippyResource;

class ResourceLocator
{
    public function mapResourcePath(ZippyResource $resource, $context)
    {
        return rtrim($context, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $resource->getTarget();
    }
}
