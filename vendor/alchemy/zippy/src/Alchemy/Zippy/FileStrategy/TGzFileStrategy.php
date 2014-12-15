<?php

/*
 * This file is part of Zippy.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Zippy\FileStrategy;

class TGzFileStrategy extends AbstractFileStrategy
{
    /**
     * {@inheritdoc}
     */
    protected function getServiceNames()
    {
        return array(
            'Alchemy\\Zippy\\Adapter\\GNUTar\\TarGzGNUTarAdapter',
            'Alchemy\\Zippy\\Adapter\\BSDTar\\TarGzBSDTarAdapter'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFileExtension()
    {
        return 'tgz';
    }
}
