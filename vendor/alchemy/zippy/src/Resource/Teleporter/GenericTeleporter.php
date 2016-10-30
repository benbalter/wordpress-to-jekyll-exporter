<?php

namespace Alchemy\Zippy\Resource\Teleporter;

use Alchemy\Zippy\Exception\InvalidArgumentException;
use Alchemy\Zippy\Exception\IOException;
use Alchemy\Zippy\Resource\Resource as ZippyResource;
use Alchemy\Zippy\Resource\ResourceLocator;
use Alchemy\Zippy\Resource\ResourceReaderFactory;
use Alchemy\Zippy\Resource\ResourceWriter;

class GenericTeleporter implements TeleporterInterface
{
    /**
     * @var ResourceReaderFactory
     */
    private $readerFactory;

    /**
     * @var ResourceWriter
     */
    private $resourceWriter;

    /**
     * @var ResourceLocator
     */
    private $resourceLocator;

    /**
     * @param ResourceReaderFactory $readerFactory
     * @param ResourceWriter        $resourceWriter
     * @param ResourceLocator       $resourceLocator
     */
    public function __construct(
        ResourceReaderFactory $readerFactory,
        ResourceWriter $resourceWriter,
        ResourceLocator $resourceLocator = null
    ) {
        $this->readerFactory = $readerFactory;
        $this->resourceWriter = $resourceWriter;
        $this->resourceLocator = $resourceLocator ?: new ResourceLocator();
    }

    /**
     * Teleports a file from a destination to an other
     *
     * @param ZippyResource $resource A Resource
     * @param string        $context  The current context
     *
     * @throws IOException when file could not be written on local
     * @throws InvalidArgumentException when path to file is not valid
     */
    public function teleport(ZippyResource $resource, $context)
    {
        $reader = $this->readerFactory->getReader($resource, $context);
        $target = $this->resourceLocator->mapResourcePath($resource, $context);

        $this->resourceWriter->writeFromReader($reader, $target);
    }
}
