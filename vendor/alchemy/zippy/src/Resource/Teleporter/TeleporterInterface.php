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

interface TeleporterInterface
{
    /**
     * Teleports a file from a destination to an other
     *
     * @param ZippyResource $resource A Resource
     * @param string        $context  The current context
     *
     * @throws IOException when file could not be written on local
     * @throws InvalidArgumentException when path to file is not valid
     */
    public function teleport(ZippyResource $resource, $context);
}
