<?php

/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\EventDispatcher;

use Symfony\Component\Stopwatch\Stopwatch;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Logging and profiling event dispatcher for the document manager
 */
class DebugEventDispatcher extends ContainerAwareEventDispatcher
{
    /**
     * @var Stopwatch
     */
    private $stopwatch;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param EventDispatcherInterface $dispatcher
     * @param Stopwatch $stopwatch
     * @param LoggerInterface $logger
     */
    public function __construct(
        ContainerInterface $container,
        Stopwatch $stopwatch,
        LoggerInterface $logger
    )
    {
        parent::__construct($container);
        $this->stopwatch = $stopwatch;
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    protected function doDispatch($listeners, $eventName, Event $event)
    {
        $eventStopwatch = $this->stopwatch->start($eventName, 'section');

        foreach ($listeners as $listener) {
            list($listenerInstance, $methodName) = $listener;
            $name = $this->getDebugClassName($listenerInstance);

            $listenerStopwatch = $this->stopwatch->start($name . '->' . $methodName, 'document_manager_listener');

            call_user_func($listener, $event, $eventName, $this);

            $this->logger->debug(sprintf(
                '%-40s%-20s %s', $name, $methodName, $event->getDebugMessage()
            ));


            if ($listenerStopwatch->isStarted()) {
                $listenerStopwatch->stop();
            }

            if ($event->isPropagationStopped()) {
                break;
            }
        }

        if ($eventStopwatch->isStarted()) {
            $eventStopwatch->stop();
        }
    }

    private function getDebugClassName($subscriber)
    {
        $className = get_class($subscriber);
        $parts = explode('\\', $className);
        $last = array_pop($parts);
        $parts = array_map(function ($part) {
            return substr($part, 0, 1);
        }, $parts);

        return implode('\\', $parts) . '\\' . $last;
    }
}
