<?php

namespace Alchemy\Zippy\Resource;

interface ResourceWriter 
{
    public function writeFromReader(ResourceReader $reader, $target);
}
