<?php

/*
 * This file is part of Zippy.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Zippy\Archive;

use Alchemy\Zippy\Adapter\Resource\ResourceInterface;
use Alchemy\Zippy\Exception\InvalidArgumentException;
use Alchemy\Zippy\Exception\RuntimeException;

interface MemberInterface
{
    /**
     * Gets the location of an archive member
     *
     * @return string
     */
    public function getLocation();

    /**
     * Tells whether the member is a directory or not
     *
     * @return bool
     */
    public function isDir();

    /**
     * Returns the last modified date of the member
     *
     * @return \DateTime
     */
    public function getLastModifiedDate();

    /**
     * Returns the (uncompressed) size of the member
     *
     * If the size is unknown, returns -1
     *
     * @return integer
     */
    public function getSize();

    /**
     * Extract the member from its archive
     *
     * Be careful using this method within a loop
     * This will execute one extraction process for each file
     * Prefer the use of ArchiveInterface::extractMembers in that use case
     *
     * @param string|null $to        The path where to extract the member, if no path is not provided the member is extracted in the same directory of its archive
     * @param bool        $overwrite Whether to overwrite destination file if it already exists. Defaults to false
     *
     * @return \SplFileInfo The extracted file
     *
     * @throws RuntimeException In case of failure
     * @throws InvalidArgumentException In case no members could be removed or provide extract target directory is not valid
     */
    public function extract($to = null, $overwrite = false);
    
    /**
     * Get resource.
     * 
     * @return ResourceInterface
     * */
    public function getResource();
    
    /**
     * @inheritdoc
     */
    public function __toString();
}
