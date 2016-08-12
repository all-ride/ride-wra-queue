<?php

namespace ride\web\rest\controller;

use ride\library\http\jsonapi\JsonApiQuery;

/**
 * Controller for the queue job JSON API interface
 */
class QueueJobController extends AbstractResourceJsonApiController {

    /**
     * Hook to perform extra initializing
     * @return null
     */
    protected function initialize() {
        $this->setType('queue-jobs');
        $this->setIdField('id');
        $this->setAttribute('className');
        $this->setAttribute('status');
        $this->setAttribute('description');
        $this->setAttribute('slots');
        $this->setAttribute('slot');
        $this->setAttribute('dateAdded');
        $this->setAttribute('dateScheduled');
        $this->setRelationship('queue', 'queues', 'queue');

        $this->setRoute(self::ROUTE_INDEX, 'api.queue-jobs.index');
        $this->setRoute(self::ROUTE_DETAIL, 'api.queue-jobs.detail');
    }

    /**
     * Gets the resources for the provided query
     * @param \ride\library\http\jsonapi\JsonApiQuery $query
     * @param integer $total Total number of entries before pagination
     * @return mixed Array with resource data or false when an error occured
     */
    protected function getResources(JsonApiQuery $query, &$total) {
        $queueManager = $this->dependencyInjector->get('ride\\library\\queue\\QueueManager');
        $queueJobs = array();

        $queues = $queueManager->getQueueStatus();
        foreach ($queues as $queue => $slots) {
            $queueJobs += $queueManager->getQueueJobStatuses($queue);
        }

        $queueQuery = null;
        $classNameQuery = null;
        $statusQuery = null;
        $descriptionQuery = null;

        $filters = $query->getFilters();
        foreach ($filters as $filterName => $filterValue) {
            switch ($filterName) {
                case 'queue':
                    $queueQuery = $filterValue;

                    break;
                case 'className':
                    $classNameQuery = $filterValue;

                    break;
                case 'status':
                    $statusQuery = $filterValue;

                    break;
                case 'description':
                    $descriptionQuery = $filterValue;

                    break;
                default:
                    $this->addFilterNotFoundError($this->type, $filterName);

                    break;
            }
        }

        $sorter = $this->createSorter($this->type, array('queue', 'className', 'slot', 'slots', 'status', 'description', 'dateAdded', 'dateScheduled'));

        if ($this->document->getErrors()) {
            return false;
        }

        // perform filter
        if ($queueQuery) {
            foreach ($queueJobs as $index => $queueJob) {
                if ($queueQuery && $this->filterStringValue($queueQuery, $queueJob->getQueue()) === false) {
                    unset($queueJobs[$index]);
                }
                if ($classNameQuery && $this->filterStringValue($classNameQuery, $queueJob->getClassName()) === false) {
                    unset($queueJobs[$index]);
                }
                if ($statusQuery && $this->filterStringValue($statusQuery, $queueJob->getStatus()) === false) {
                    unset($queueJobs[$index]);
                }
                if ($statusQuery && $this->filterStringValue($descriptionQuery, $queueJob->getDescription()) === false) {
                    unset($queueJobs[$index]);
                }
            }
        }

        // perform sort
        $queueJobs = $sorter->sort($queueJobs);

        // perform pagination
        $total = count($queueJobs);
        $queueJobs = array_slice($queueJobs, $query->getOffset(), $query->getLimit(100));

        // return
        return $queueJobs;
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
        $queueJob = $queueManager->getQueueJobStatus($id);

        if (!$queueJob) {
            if ($addError) {
                $this->addResourceNotFoundError($this->type, $id);
            }

            return false;
        }

        return $queueJob;
    }

}
