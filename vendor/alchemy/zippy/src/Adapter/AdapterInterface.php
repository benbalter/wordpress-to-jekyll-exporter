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

use Alchemy\Zippy\Adapter\Resource\ResourceInterface;
use Alchemy\Zippy\Archive\ArchiveInterface;
use Alchemy\Zippy\Exception\NotSupportedException;
use Alchemy\Zippy\Exception\RuntimeException;
use Alchemy\Zippy\Exception\InvalidArgumentException;

Interface AdapterInterface
{
    /**
     * Opens an archive
     *
     * @param string $path The path to the archive
     *
     * @return ArchiveInterface
     *
     * @throws InvalidArgumentException In case the provided path is not valid
     * @throws RuntimeException In case of failure
     */
    public function open($path);

    /**
     * Creates a new archive
     *
     * Please note some adapters can not create empty archives.
     * They would throw a `NotSupportedException` in case you ask to create an archive without files
     *
     * @param string                            $path      The path to the archive
     * @param string|string[]|\Traversable|null $files     A filename, an array of files, or a \Traversable instance
     * @param bool                              $recursive Whether to recurse or not in the provided directories
     *
     * @return ArchiveInterface
     *
     * @throws RuntimeException In case of failure
     * @throws NotSupportedException In case the operation in not supported
     * @throws InvalidArgumentException In case no files could be added
     */
    public function create($path, $files = null, $recursive = true);

    /**
     * Tests if the adapter is supported by the current environment
     *
     * @return bool
     */
    public function isSupported();

    /**
     * Returns the list of all archive members
     *
     * @param ResourceInterface $resource The path to the archive
     *
     * @return array
     *
     * @throws RuntimeException In case of failure
     */
    public function listMembers(ResourceInterface $resource);

    /**
     * Adds a file to the archive
     *
     * @param ResourceInterface         $resource  The path to the archive
     * @param string|array|\Traversable $files     An array of paths to add, relative to cwd
     * @param bool                      $recursive Whether or not to recurse in the provided directories
     *
     * @return array
     *
     * @throws RuntimeException In case of failure
     * @throws InvalidArgumentException In case no files could be added
     */
    public function add(ResourceInterface $resource, $files, $recursive = true);

    /**
     * Removes a member of the archive
     *
     * @param ResourceInterface         $resource The path to the archive
     * @param string|array|\Traversable $files    A filename, an array of files, or a \Traversable instance
     *
     * @return array
     *
     * @throws RuntimeException In case of failure
     * @throws InvalidArgumentException In case no files could be removed
     */
    public function remove(ResourceInterface $resource, $files);

    /**
     * Extracts an entire archive
     *
     * Note that any existing files will be overwritten by the adapter
     *
     * @param ResourceInterface $resource The path to the archive
     * @param string|null       $to       The path where to extract the archive
     *
     * @return \SplFileInfo The extracted archive
     *
     * @throws RuntimeException In case of failure
     * @throws InvalidArgumentException In case the provided path where to extract the archive is not valid
     */
    public function extract(ResourceInterface $resource, $to = null);

    /**
     * Extracts specific members of the archive
     *
     * @param ResourceInterface $resource  The path to the archive
     * @param string|string[]   $members   A path or array of paths matching the members to extract from the resource.
     * @param string|null       $to        The path where to extract the members
     * @param bool              $overwrite Whether to overwrite existing files in target directory
     *
     * @return \SplFileInfo The extracted archive
     *
     * @throws RuntimeException In case of failure
     * @throws InvalidArgumentException In case no members could be removed or providedd extract target directory is not valid
     */
    public function extractMembers(ResourceInterface $resource, $members, $to = null, $overwrite = false);

    /**
     * Returns the adapter name
     *
     * @return string
     */
    public static function getName();
}
