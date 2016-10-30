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

class Resource
{
    private $original;
    private $target;

    /**
     * Constructor
     *
     * @param string $original
     * @param string $target
     */
    public function __construct($original, $target)
    {
        $this->original = $original;
        $this->target = $target;
    }

    /**
     * Returns the target
     *
     * @return string
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * Returns the original path
     *
     * @return string
     */
    public function getOriginal()
    {
        return $this->original;
    }

    /**
     * Returns whether the resource can be processed in place given a context or not.
     *
     * For example :
     *   - /path/to/file1 can be processed to file1 in /path/to context
     *   - /path/to/subdir/file2 can be processed to subdir/file2 in /path/to context
     *
     * @param string $context
     *
     * @return bool
     */
    public function canBeProcessedInPlace($context)
    {
        if (!is_string($this->original)) {
            return false;
        }

        if (!$this->isLocal()) {
            return false;
        }

        $data = parse_url($this->original);

        return sprintf('%s/%s', rtrim($context, '/'), $this->target) === $data['path'];
    }

    /**
     * Returns a context for computing this resource in case it is possible.
     *
     * Useful to avoid teleporting.
     *
     * @return null|string
     */
    public function getContextForProcessInSinglePlace()
    {
        if (!is_string($this->original)) {
            return null;
        }

        if (!$this->isLocal()) {
            return null;
        }

        if (PathUtil::basename($this->original) === $this->target) {
            return dirname($this->original);
        }
        
        return null;
    }

    /**
     * Returns true if the resource is local.
     *
     * @return bool
     */
    private function isLocal()
    {
        if (!is_string($this->original)) {
            return false;
        }

        $data = parse_url($this->original);

        return isset($data['path']);
    }
}
