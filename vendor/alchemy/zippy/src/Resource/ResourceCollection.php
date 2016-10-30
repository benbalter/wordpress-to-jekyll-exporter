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
use Doctrine\Common\Collections\ArrayCollection;

class ResourceCollection extends ArrayCollection
{
    private $context;
    /**
     * @var bool
     */
    private $temporary;

    /**
     * Constructor
     * @param string $context
     * @param Resource[] $elements An array of Resource
     * @param bool $temporary
     */
    public function __construct($context, array $elements, $temporary)
    {
        array_walk($elements, function($element) {
            if (!$element instanceof Resource) {
                throw new InvalidArgumentException('ResourceCollection only accept Resource elements');
            }
        });

        $this->context = $context;
        $this->temporary = (bool) $temporary;
        parent::__construct($elements);
    }

    /**
     * Returns the context related to the collection
     *
     * @return string
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Tells whether the collection is temporary or not.
     *
     * A ResourceCollection is temporary when it required a temporary folder to
     * fetch data
     *
     * @return bool
     */
    public function isTemporary()
    {
        return $this->temporary;
    }

    /**
     * Returns true if all resources can be processed in place, false otherwise
     *
     * @return bool
     */
    public function canBeProcessedInPlace()
    {
        if (count($this) === 1) {
            if (null !== $context = $this->first()->getContextForProcessInSinglePlace()) {
                $this->context = $context;
                return true;
            }
        }

        foreach ($this as $resource) {
            if (!$resource->canBeProcessedInPlace($this->context)) {
                return false;
            }
        }

        return true;
    }
}
