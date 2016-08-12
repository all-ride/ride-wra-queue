<?php

namespace ride\web\rest\controller;

use ride\library\http\jsonapi\JsonApiQuery;

/**
 * Controller for the queue JSON API interface
 */
class QueueController extends AbstractResourceJsonApiController {

    /**
     * Hook to perform extra initializing
     * @return null
     */
    protected function initialize() {
        $this->setType('queues');
        $this->setIdField('id');
        $this->setAttribute('queue');
        $this->setAttribute('slots');

        $this->setRoute(self::ROUTE_INDEX, 'api.queues.index');
        $this->setRoute(self::ROUTE_DETAIL, 'api.queues.detail');
    }

    /**
     * Gets the resources for the provided query
     * @param \ride\library\http\jsonapi\JsonApiQuery $query
     * @param integer $total Total number of entries before pagination
     * @return mixed Array with resource data or false when an error occured
     */
    protected function getResources(JsonApiQuery $query, &$total) {
        $queueManager = $this->dependencyInjector->get('ride\\library\\queue\\QueueManager');

        $queues = $queueManager->getQueueStatus();

        $queueQuery = null;

        $filters = $query->getFilters();
        foreach ($filters as $filterName => $filterValue) {
            switch ($filterName) {
                case 'queue':
                    $queueQuery = $filterValue;

                    break;
                default:
                    $this->addFilterNotFoundError($this->type, $filterName);

                    break;
            }
        }

        $sorter = $this->createSorter($this->type, array('queue', 'slots'));

        if ($this->document->getErrors()) {
            return false;
        }

        // perform filter
        if ($queueQuery) {
            foreach ($queues as $queue => $slots) {
                if ($nameQuery && $this->filterStringValue($queueQuery, $queue) === false) {
                    unset($queues[$queue]);
                }
            }
        }

        // create resource data from the queue stats
        foreach ($queues as $queue => $slots) {
            $queues[$queue] = array(
                'id' => $queue,
                'queue' => $queue,
                'slots' => $slots,
            );
        }

        // perform sort
        $queues = $sorter->sort($queues);

        // perform pagination
        $total = count($queues);
        $queues = array_slice($queues, $query->getOffset(), $query->getLimit(100));

        // return
        return $queues;
    }

    /**
     * Gets the resource for the provided id
     * @param string $id Id of the resource
     * @param boolean $addError Set to false to skip adding the error when the
     * resource is not found
     * @return mixed Resource data if found or false when an error occured
     */
    protected function getResource($id, $addError = true) {
        $queueManager = $this->dependencyInjector->get('ride\\library\\queue\\QueueManager');
        $queues = $queueManager->getQueueStatus();

        if (!isset($queues[$id])) {
            if ($addError) {
                $this->addResourceNotFoundError($this->type, $id);
            }

            return false;
        }

        return array(
            'id' => $id,
            'queue' => $id,
            'slots' => $queues[$id],
        );
    }

}
