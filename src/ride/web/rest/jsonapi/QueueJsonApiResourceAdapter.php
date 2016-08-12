<?php

namespace ride\web\rest\jsonapi;

use ride\library\http\jsonapi\exception\JsonApiException;
use ride\library\http\jsonapi\JsonApiDocument;
use ride\library\http\jsonapi\JsonApiResourceAdapter;
use ride\library\queue\QueueManager;

use ride\web\WebApplication;

/**
 * JSON API Resource adapter for the queue
 */
class QueueJsonApiResourceAdapter implements JsonApiResourceAdapter {

    /**
     * Constructs a new model resource adapter
     * @param \ride\web\WebApplication $web Instance of the web application
     * @param string $type Resource type for the parameters
     * @return null
     */
    public function __construct(WebApplication $web, QueueManager $queueManager, $type = null) {
        if ($type === null) {
            $type = 'queues';
        }

        $this->web = $web;
        $this->queueManager = $queueManager;
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
    public function getResource($queue, JsonApiDocument $document, $relationshipPath = null) {
        if ($queue === null) {
            return null;
        } elseif (!is_array($queue) || !isset($queue['queue']) || !isset($queue['slots'])) {
            throw new JsonApiException('Could not get resource: provided data is not a queue');
        }

        $query = $document->getQuery();
        $api = $document->getApi();
        $id = $queue['queue'];

        $resource = $api->createResource($this->type, $id, $relationshipPath);
        $resource->setLink('self', $this->web->getUrl('api.queues.detail', array('id' => $id)));

        if ($query->isFieldRequested($this->type, 'queue')) {
            $resource->setAttribute('queue', $queue['queue']);
        }
        if ($query->isFieldRequested($this->type, 'slots')) {
            $resource->setAttribute('slots', $queue['slots']);
        }

        return $resource;
    }

}
