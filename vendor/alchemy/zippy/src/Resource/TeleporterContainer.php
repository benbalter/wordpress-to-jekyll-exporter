<?php

/*
 * This file is part of Zippy.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Zippy\Resource;

use Alchemy\Zippy\Exception\InvalidArgumentException;
use Alchemy\Zippy\Resource\Reader\Guzzle\GuzzleReaderFactory;
use Alchemy\Zippy\Resource\Reader\Guzzle\LegacyGuzzleReaderFactory;
use Alchemy\Zippy\Resource\Resource as ZippyResource;
use Alchemy\Zippy\Resource\Teleporter\GenericTeleporter;
use Alchemy\Zippy\Resource\Teleporter\LocalTeleporter;
use Alchemy\Zippy\Resource\Teleporter\StreamTeleporter;
use Alchemy\Zippy\Resource\Teleporter\TeleporterInterface;
use Alchemy\Zippy\Resource\Writer\FilesystemWriter;
use Symfony\Component\Filesystem\Filesystem;

/**
 * A container of TeleporterInterface
 */
class TeleporterContainer implements \ArrayAccess, \Countable
{
    /**
     * @var TeleporterInterface[]
     */
    private $teleporters = array();

    /**
     * @var callable[]
     */
    private $factories = array();

    /**
     * Returns the appropriate TeleporterInterface for a given Resource
     *
     * @param ZippyResource $resource
     *
     * @return TeleporterInterface
     */
    public function fromResource(ZippyResource $resource)
    {
        switch (true) {
            case is_resource($resource->getOriginal()):
                $teleporter = 'stream-teleporter';
                break;
            case is_string($resource->getOriginal()):
                $data = parse_url($resource->getOriginal());

                if (!isset($data['scheme']) || 'file' === $data['scheme']) {
                    $teleporter = 'local-teleporter';
                } elseif (in_array($data['scheme'], array('http', 'https')) && isset($this->factories['guzzle-teleporter'])) {
                    $teleporter = 'guzzle-teleporter';
                } else {
                    $teleporter = 'stream-teleporter';
                }
                break;
            default:
                throw new InvalidArgumentException('No teleporter found');
        }

        return $this->getTeleporter($teleporter);
    }

    private function getTeleporter($typeName)
    {
        if (!isset($this->teleporters[$typeName])) {
            $factory = $this->factories[$typeName];
            $this->teleporters[$typeName] = $factory();
        }

        return $this->teleporters[$typeName];
    }

    /**
     * Instantiates TeleporterContainer and register default teleporters
     *
     * @return TeleporterContainer
     */
    public static function load()
    {
        $container = new static();

        $container->factories['stream-teleporter'] = function () {
            return new StreamTeleporter();
        };

        $container->factories['local-teleporter'] = function () {
            return new LocalTeleporter(new Filesystem());
        };

        if (class_exists('GuzzleHttp\Client')) {
            $container->factories['guzzle-teleporter'] = function () {
                return new GenericTeleporter(
                    new GuzzleReaderFactory(),
                    new FilesystemWriter(),
                    new ResourceLocator()
                );
            };
        }
        elseif (class_exists('Guzzle\Http\Client')) {
            $container->factories['guzzle-teleporter'] = function () {
                return new GenericTeleporter(
                    new LegacyGuzzleReaderFactory(),
                    new FilesystemWriter(),
                    new ResourceLocator()
                );
            };
        }

        return $container;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Whether a offset exists
     *
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     *
     * @param mixed $offset <p>
     *                      An offset to check for.
     *                      </p>
     *
     * @return bool true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($offset)
    {
        return isset($this->teleporters[$offset]);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset)
    {
        return $this->getTeleporter($offset);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        throw new \BadMethodCallException();
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     */
    public function offsetUnset($offset)
    {
        throw new \BadMethodCallException();
    }

    public function count()
    {
        return count($this->teleporters);
    }
}
