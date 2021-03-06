<?php

/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Collection;

use PHPCR\NodeInterface;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Lazily hydrate query results.
 *
 * TODO: Performance -- try fetch depth like in teh PHPCR-ODM ChildrenCollection
 */
class ChildrenCollection extends AbstractLazyCollection
{
    private $dispatcher;
    private $parentNode;
    private $locale;
    private $options;

    private $initialized = false;

    public function __construct(NodeInterface $parentNode, EventDispatcherInterface $dispatcher, $locale, $options = array())
    {
        $this->parentNode = $parentNode;
        $this->dispatcher = $dispatcher;
        $this->locale = $locale;
        $this->options = $options;
    }

    public function current()
    {
        $this->initialize();
        $childNode = $this->elements->current();

        $hydrateEvent = new HydrateEvent($childNode, $this->locale, $this->options);
        $this->dispatcher->dispatch(Events::HYDRATE, $hydrateEvent);

        return $hydrateEvent->getDocument();
    }

    protected function initialize()
    {
        if (true === $this->initialized) {
            return;
        }

        $this->elements = $this->parentNode->getNodes();
        $this->initialized = true;
    }
}
