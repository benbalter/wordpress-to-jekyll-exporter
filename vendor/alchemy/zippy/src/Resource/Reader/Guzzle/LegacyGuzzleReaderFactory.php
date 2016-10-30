<?php

namespace Alchemy\Zippy\Resource\Reader\Guzzle;

use Alchemy\Zippy\Resource\Resource as ZippyResource;
use Alchemy\Zippy\Resource\ResourceReader;
use Alchemy\Zippy\Resource\ResourceReaderFactory;
use Guzzle\Http\Client;
use Guzzle\Http\ClientInterface;
use Guzzle\Plugin\Backoff\BackoffPlugin;
use Symfony\Component\EventDispatcher\Event;

class LegacyGuzzleReaderFactory implements ResourceReaderFactory
{
    /**
     * @var ClientInterface|null
     */
    private $client = null;

    public function __construct(ClientInterface $client = null)
    {
        $this->client = $client;

        if (!$this->client) {
            $this->client = new Client();

            $this->client->getEventDispatcher()->addListener('request.error', function(Event $event) {
                // override guzzle default behavior of throwing exceptions
                // when 4xx & 5xx responses are encountered
                $event->stopPropagation();
            }, -254);

            $this->client->addSubscriber(BackoffPlugin::getExponentialBackoff(5, array(500, 502, 503, 408)));
        }
    }

    /**
     * @param ZippyResource $resource
     * @param string        $context
     *
     * @return ResourceReader
     */
    public function getReader(ZippyResource $resource, $context)
    {
        return new LegacyGuzzleReader($resource, $this->client);
    }
}
