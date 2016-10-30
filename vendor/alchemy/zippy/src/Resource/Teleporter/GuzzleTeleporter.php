<?php

/*
 * This file is part of Zippy.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Zippy\Resource\Teleporter;

use Alchemy\Zippy\Resource\Reader\Guzzle\GuzzleReaderFactory;
use Alchemy\Zippy\Resource\ResourceLocator;
use Alchemy\Zippy\Resource\ResourceReaderFactory;
use Alchemy\Zippy\Resource\Writer\FilesystemWriter;

/**
 * Guzzle Teleporter implementation for HTTP resources
 *
 * @deprecated Use \Alchemy\Zippy\Resource\GenericTeleporter instead. This class will be removed in v0.5.x
 */
class GuzzleTeleporter extends GenericTeleporter
{
    /**
     * @param ResourceReaderFactory $readerFactory
     * @param ResourceLocator $resourceLocator
     */
    public function __construct(ResourceReaderFactory $readerFactory = null, ResourceLocator $resourceLocator = null)
    {
        parent::__construct($readerFactory ?: new GuzzleReaderFactory(), new FilesystemWriter(), $resourceLocator);
    }

    /**
     * Creates the GuzzleTeleporter
     *
     * @deprecated This method will be removed in v0.5.x
     * @return GuzzleTeleporter
     */
    public static function create()
    {
        return new static();
    }
}
