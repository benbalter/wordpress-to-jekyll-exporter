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

use Alchemy\Zippy\Resource\Reader\Guzzle\LegacyGuzzleReaderFactory;
use Alchemy\Zippy\Resource\ResourceLocator;
use Alchemy\Zippy\Resource\ResourceReaderFactory;
use Alchemy\Zippy\Resource\Writer\FilesystemWriter;
use Guzzle\Http\Client;

/**
 * Guzzle Teleporter implementation for HTTP resources
 *
 * @deprecated Use \Alchemy\Zippy\Resource\GenericTeleporter instead. This class will be removed in v0.5.x
 */
class LegacyGuzzleTeleporter extends GenericTeleporter
{
    /**
     * @param Client $client
     * @param ResourceReaderFactory $readerFactory
     * @param ResourceLocator $resourceLocator
     */
    public function __construct(
        Client $client = null,
        ResourceReaderFactory $readerFactory = null,
        ResourceLocator $resourceLocator = null
    ) {
        parent::__construct($readerFactory ?: new LegacyGuzzleReaderFactory($client), new FilesystemWriter(),
            $resourceLocator);
    }

    /**
     * Creates the GuzzleTeleporter
     *
     * @deprecated
     * @return LegacyGuzzleTeleporter
     */
    public static function create()
    {
        return new static();
    }
}
