<?php

namespace Alchemy\Zippy\Resource\Reader\Guzzle;

use Alchemy\Zippy\Resource\Resource as ZippyResource;
use Alchemy\Zippy\Resource\ResourceReader;
use Guzzle\Http\Client;
use Guzzle\Http\ClientInterface;
use Guzzle\Http\EntityBodyInterface;

class LegacyGuzzleReader implements ResourceReader
{
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var \Alchemy\Zippy\Resource\Resource $resource
     */
    private $resource;

    /**
     * This is necessary to prevent the underlying PHP stream from being destroyed
     * @link https://github.com/guzzle/guzzle/issues/366#issuecomment-20295409
     * @var EntityBodyInterface|null
     */
    private $stream = null;

    /**
     * @param ZippyResource   $resource
     * @param ClientInterface $client
     */
    public function __construct(ZippyResource $resource, ClientInterface $client = null)
    {
        $this->client = $client ?: new Client();
        $this->resource = $resource;
    }

    /**
     * @return string
     */
    public function getContents()
    {
        return $this->buildRequest()->send()->getBody(true);
    }

    /**
     * @return resource
     */
    public function getContentsAsStream()
    {
        if (!$this->stream) {
            $this->stream = $this->buildRequest()->send()->getBody(false);
        }

        return $this->stream->getStream();
    }

    /**
     * @return \Guzzle\Http\Message\RequestInterface
     */
    private function buildRequest()
    {
        return $this->client->get($this->resource->getOriginal());
    }
}
