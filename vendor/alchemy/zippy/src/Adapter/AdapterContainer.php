<?php

/*
 * This file is part of Zippy.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Zippy\Adapter;

use Alchemy\Zippy\Adapter\BSDTar\TarBSDTarAdapter;
use Alchemy\Zippy\Adapter\BSDTar\TarGzBSDTarAdapter;
use Alchemy\Zippy\Adapter\BSDTar\TarBz2BSDTarAdapter;
use Alchemy\Zippy\Adapter\GNUTar\TarGNUTarAdapter;
use Alchemy\Zippy\Adapter\GNUTar\TarGzGNUTarAdapter;
use Alchemy\Zippy\Adapter\GNUTar\TarBz2GNUTarAdapter;
use Alchemy\Zippy\Resource\ResourceManager;
use Alchemy\Zippy\Resource\RequestMapper;
use Alchemy\Zippy\Resource\TeleporterContainer;
use Alchemy\Zippy\Resource\ResourceTeleporter;
use Alchemy\Zippy\Resource\TargetLocator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\ExecutableFinder;

class AdapterContainer implements \ArrayAccess
{

    private $items = array();

    /**
     * Builds the adapter container
     *
     * @return AdapterContainer
     */
    public static function load()
    {
        $container = new static();

        $container['zip.inflator'] = null;
        $container['zip.deflator'] = null;

        $container['resource-manager'] = function ($container) {
            return new ResourceManager(
                $container['request-mapper'],
                $container['resource-teleporter'],
                $container['filesystem']
            );
        };

        $container['executable-finder'] = function ($container) {
            return new ExecutableFinder();
        };

        $container['request-mapper'] = function ($container) {
            return new RequestMapper($container['target-locator']);
        };

        $container['target-locator'] = function () {
            return new TargetLocator();
        };

        $container['teleporter-container'] = function ($container) {
            return TeleporterContainer::load();
        };

        $container['resource-teleporter'] = function ($container) {
            return new ResourceTeleporter($container['teleporter-container']);
        };

        $container['filesystem'] = function () {
            return new Filesystem();
        };

        $container['Alchemy\\Zippy\\Adapter\\ZipAdapter'] = function ($container) {
            return ZipAdapter::newInstance(
                $container['executable-finder'],
                $container['resource-manager'],
                $container['zip.inflator'],
                $container['zip.deflator']
            );
        };

        $container['gnu-tar.inflator'] = null;
        $container['gnu-tar.deflator'] = null;

        $container['Alchemy\\Zippy\\Adapter\\GNUTar\\TarGNUTarAdapter'] = function ($container) {
            return TarGNUTarAdapter::newInstance(
                $container['executable-finder'],
                $container['resource-manager'],
                $container['gnu-tar.inflator'],
                $container['gnu-tar.deflator']
            );
        };

        $container['Alchemy\\Zippy\\Adapter\\GNUTar\\TarGzGNUTarAdapter'] = function ($container) {
            return TarGzGNUTarAdapter::newInstance(
                $container['executable-finder'],
                $container['resource-manager'],
                $container['gnu-tar.inflator'],
                $container['gnu-tar.deflator']
            );
        };

        $container['Alchemy\\Zippy\\Adapter\\GNUTar\\TarBz2GNUTarAdapter'] = function ($container) {
            return TarBz2GNUTarAdapter::newInstance(
                $container['executable-finder'],
                $container['resource-manager'],
                $container['gnu-tar.inflator'],
                $container['gnu-tar.deflator']
            );
        };

        $container['bsd-tar.inflator'] = null;
        $container['bsd-tar.deflator'] = null;

        $container['Alchemy\\Zippy\\Adapter\\BSDTar\\TarBSDTarAdapter'] = function ($container) {
            return TarBSDTarAdapter::newInstance(
                $container['executable-finder'],
                $container['resource-manager'],
                $container['bsd-tar.inflator'],
                $container['bsd-tar.deflator']
            );
        };

        $container['Alchemy\\Zippy\\Adapter\\BSDTar\\TarGzBSDTarAdapter'] = function ($container) {
            return TarGzBSDTarAdapter::newInstance(
                $container['executable-finder'],
                $container['resource-manager'],
                $container['bsd-tar.inflator'],
                $container['bsd-tar.deflator']
            );
        };

        $container['Alchemy\\Zippy\\Adapter\\BSDTar\\TarBz2BSDTarAdapter'] = function ($container) {
            return TarBz2BSDTarAdapter::newInstance(
                $container['executable-finder'],
                $container['resource-manager'],
                $container['bsd-tar.inflator'],
                $container['bsd-tar.deflator']);
        };

        $container['Alchemy\\Zippy\\Adapter\\ZipExtensionAdapter'] = function () {
            return ZipExtensionAdapter::newInstance();
        };

        return $container;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($offset)
    {
        return isset($this->items[$offset]);
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
        if (array_key_exists($offset, $this->items) && is_callable($this->items[$offset])) {
            $this->items[$offset] = call_user_func($this->items[$offset], $this);
        }

        if (array_key_exists($offset, $this->items)) {
            return $this->items[$offset];
        }

        throw new \InvalidArgumentException();
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
        $this->items[$offset] = $value;
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
        unset($this->items[$offset]);
    }
}
