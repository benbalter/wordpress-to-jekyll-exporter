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

use Alchemy\Zippy\Exception\InvalidArgumentException;
use Alchemy\Zippy\Exception\IOException;
use Alchemy\Zippy\Resource\Resource as ZippyResource;
use Alchemy\Zippy\Resource\ResourceLocator;
use Symfony\Component\Filesystem\Exception\IOException as SfIOException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * This class transports an object using the local filesystem
 */
class LocalTeleporter extends AbstractTeleporter
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var ResourceLocator
     */
    private $resourceLocator;

    /**
     * Constructor
     *
     * @param Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
        $this->resourceLocator = new ResourceLocator();
    }

    /**
     * {@inheritdoc}
     */
    public function teleport(ZippyResource $resource, $context)
    {
        $target = $this->resourceLocator->mapResourcePath($resource, $context);
        $path = $resource->getOriginal();

        if (!file_exists($path)) {
            throw new InvalidArgumentException(sprintf('Invalid path %s', $path));
        }

        try {
            if (is_file($path)) {
                $this->filesystem->copy($path, $target);
            } elseif (is_dir($path)) {
                $this->filesystem->mirror($path, $target);
            } else {
                throw new InvalidArgumentException(sprintf('Invalid file or directory %s', $path));
            }
        } catch (SfIOException $e) {
            throw new IOException(sprintf('Could not write %s', $target), $e->getCode(), $e);
        }
    }

    /**
     * Creates the LocalTeleporter
     *
     * @return LocalTeleporter
     * @deprecated This method will be removed in a future release (0.5.x)
     */
    public static function create()
    {
        return new static(new Filesystem());
    }
}
