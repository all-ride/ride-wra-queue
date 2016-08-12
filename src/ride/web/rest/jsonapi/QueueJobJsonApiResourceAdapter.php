<?php

namespace ride\web\rest\jsonapi;

use ride\library\http\jsonapi\exception\JsonApiException;
use ride\library\http\jsonapi\JsonApiDocument;
use ride\library\http\jsonapi\JsonApiResourceAdapter;
use ride\library\queue\QueueJobStatus;

use ride\web\WebApplication;

/**
 * JSON API Resource adapter for a queue job
 */
class QueueJobJsonApiResourceAdapter implements JsonApiResourceAdapter {

    /**
     * Constructs a new model resource adapter
     * @param \ride\web\WebApplication $web Instance of the web application
     * @param string $type Resource type for the parameters
     * @return null
     */
    public function __construct(WebApplication $web, $type = null) {
        if ($type === null) {
            $type = 'queue-jobs';
        }

        $this->web = $web;
        $this->type = $type;
    }

    /**
     * Gets a resource instance for the provided parameter
     * @param mixed $parameter Parameter to adapt
     * @param \ride\library\http\jsonapi\JsonApiDocument $document Document
     * which is requested
     * @param string $relationshipPath dot-separated list of relationship names
     * @return JsonApiResource|null
     */
    public function getResource($queueJob, JsonApiDocument $document, $relationshipPath = null) {
        if ($queueJob === null) {
            return null;
        } elseif (!$queueJob instanceof QueueJobStatus) {
            throw new JsonApiException('Could not get resource: provided data is not a queue job status');
        }

        $query = $document->getQuery();
        $api = $document->getApi();
        $id = $queueJob->getId();

        $resource = $api->createResource($this->type, $id, $relationshipPath);
        $resource->setLink('self', $this->web->getUrl('api.queue-jobs.detail', array('id' => $id)));

        if ($query->isFieldRequested($this->type, 'className')) {
            $resource->setAttribute('className', $queueJob->getClassName());
        }
        if ($query->isFieldRequested($this->type, 'status')) {
            $resource->setAttribute('status', $queueJob->getStatus());
        }
        if ($query->isFieldRequested($this->type, 'description')) {
            $resource->setAttribute('description', $queueJob->getDescription());
        }
        if ($query->isFieldRequested($this->type, 'slots')) {
            $resource->setAttribute('slots', $queueJob->getSlots());
        }
        if ($query->isFieldRequested($this->type, 'slot')) {
            $resource->setAttribute('slot', $queueJob->getSlot());
        }
        if ($query->isFieldRequested($this->type, 'dateAdded')) {
            $resource->setAttribute('dateAdded', (integer) $queueJob->getDateAdded());
        }
        if ($query->isFieldRequested($this->type, 'dateScheduled')) {
            $resource->setAttribute('dateScheduled', (integer) $queueJob->getDateScheduled());
        }

        if ($query->isFieldRequested($this->type, 'queue')) {
            $fieldRelationshipPath = ($relationshipPath ? $relationshipPath . '.' : '') . 'queue';
            $adapter = $api->getResourceAdapter('queues');

            $queue = array(
                'queue' => $queueJob->getQueue(),
                'slots' => $queueJob->getSlots(),
            );

            $relationship = $api->createRelationship();
            $relationship->setResource($adapter->getResource($queue, $document, $fieldRelationshipPath));

            $resource->setRelationship('queue', $relationship);
        }

        return $resource;
    }

}
