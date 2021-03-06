<?php

/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager;

use Sulu\Component\DocumentManager\Query\Query;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DocumentManager
{
    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Find a document by path or UUID in the given
     * locale, optionally enforcing the given type.
     *
     * @param string $id Path or UUID
     * @param string $locale Locale
     * @param array $options
     */
    public function find($identifier, $locale = null, array $options = array())
    {
        $options = $this->getOptionsResolver(Events::FIND)->resolve($options);

        $event = new Event\FindEvent($identifier, $locale, $options);
        $this->eventDispatcher->dispatch(Events::FIND, $event);

        return $event->getDocument();
    }

    /**
     * Create a new document instance for the given alias.
     *
     * @param string
     *
     * @throws MetadataNotFoundException
     *
     * @return object
     */
    public function create($alias)
    {
        $event = new Event\CreateEvent($alias);
        $this->eventDispatcher->dispatch(Events::CREATE, $event);

        return $event->getDocument();
    }

    /**
     * Persist a document to a PHPCR node.
     *
     * @param object $document
     * @param string $locale
     * @param array $options
     */
    public function persist($document, $locale, array $options = array())
    {
        $options = $this->getOptionsResolver(Events::FIND)->resolve($options);

        $event = new Event\PersistEvent($document, $locale, $options);
        $this->eventDispatcher->dispatch(Events::PERSIST, $event);
    }

    /**
     * Remove the document. The document should be unregistered
     * and the related PHPCR node should be removed from the session.
     *
     * @param object $document
     */
    public function remove($document)
    {
        $event = new Event\RemoveEvent($document);
        $this->eventDispatcher->dispatch(Events::REMOVE, $event);
    }

    /**
     * Move the PHPCR node to which the document is mapped to be
     * a child of the node at the given path or UUID.
     *
     * @param object $document
     * @param string $destId Path or UUID
     */
    public function move($document, $destId)
    {
        $event = new Event\MoveEvent($document, $destId);
        $this->eventDispatcher->dispatch(Events::MOVE, $event);
    }

    /**
     * Create a copy of the node representing the given document
     * at the given path.
     *
     * @param object $document
     * @param string $destPath
     */
    public function copy($document, $destPath)
    {
        $event = new Event\CopyEvent($document, $destPath);
        $this->eventDispatcher->dispatch(Events::COPY, $event);

        return $event->getCopiedPath();
    }

    public function reorder($document, $destId, $after = false)
    {
        $event = new Event\ReorderEvent($document, $destId, $after);
        $this->eventDispatcher->dispatch(Events::REORDER, $event);
    }

    /**
     * Refresh the given document with the persisted state of the node.
     *
     * @param object $document
     */
    public function refresh($document)
    {
        $event = new Event\RefreshEvent($document);
        $this->eventDispatcher->dispatch(Events::REFRESH, $event);
    }

    /**
     * Persist changes to the persistent storage.
     */
    public function flush()
    {
        $event = new Event\FlushEvent();
        $this->eventDispatcher->dispatch(Events::FLUSH, $event);
    }

    /**
     * Clear the document manager, should reset the underlying PHPCR
     * session and deregister all documents.
     */
    public function clear()
    {
        $event = new Event\ClearEvent();
        $this->eventDispatcher->dispatch(Events::CLEAR, $event);
    }

    /**
     * Create a new query from a JCR-SQL2 query string.
     *
     * NOTE: This should not be used generally as it exposes the
     *       database structure and breaks abstraction. Use the domain-aware
     *       query builder instead.
     *
     * @param mixed $innertQuery Either a JCR-SQL2 string, or a PHPCR query object
     *
     * @return Query
     */
    public function createQuery($query, $locale = null)
    {
        $event = new Event\QueryCreateEvent($query, $locale);
        $this->eventDispatcher->dispatch(Events::QUERY_CREATE, $event);

        return $event->getQuery();
    }

    /**
     * Create a new query builder.
     *
     * By default this will return the PHPCR-ODM query bulder
     *
     * http://doctrine-phpcr-odm.readthedocs.org/en/latest/reference/query-builder.html
     */
    public function createQueryBuilder()
    {
        $event = new Event\QueryCreateBuilderEvent();
        $this->eventDispatcher->dispatch(Events::QUERY_CREATE_BUILDER, $event);

        return $event->getQueryBuilder();
    }

    private function getOptionsResolver($event)
    {
        if (isset($this->optionResolver)) {
            return $this->optionsResolvers[$event];
        }

        $resolver = new OptionsResolver();
        $resolver->setDefault('locale', null);

        $event = new Event\ConfigureOptionsEvent($resolver);
        $this->eventDispatcher->dispatch(Events::CONFIGURE_OPTIONS, $event);

        return $resolver;
    }
}
