<?php
namespace Kameleoon\RealTime;

use Exception;

use Clue\React\EventSource\EventSource;
use Clue\React\EventSource\MessageEvent;

class RealTimeConfigurationService
{
    private const EVENT_NAME = "configuration-update-event";
    private $eventSource;

    public function subscribe($eventURL, callable $eventHandler) {
        $this->eventSource = new EventSource($eventURL);

        $this->eventSource->on(self::EVENT_NAME, function (MessageEvent $message) use ($eventHandler) {
            $data = json_decode($message->data);
            $eventHandler($data);
        });
    }

    public function unsubscribe() {
        $this->eventSource->close();
        $this->eventSource = null;
    }
}