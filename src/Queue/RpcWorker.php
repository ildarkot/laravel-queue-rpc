<?php

namespace IldarK\LaravelQueueRpc\Queue;

use Illuminate\Queue\WorkerOptions;
use VladimirYuldashev\LaravelQueueRabbitMQ\Consumer;

class RpcWorker extends Consumer
{
    /**
     * Create a new queue worker.
     *
     * @param Consumer $parent
     *
     * @return void
     */
    public function __construct(
        Consumer $parent
    )
    {
        parent::__construct($parent->manager, $parent->events, $parent->exceptions, $parent->isDownForMaintenance);
    }

    public function process($connectionName, $job, WorkerOptions $options)
    {
        $this->raiseBeforeJobEvent($connectionName, $job);

        $this->markJobAsFailedIfAlreadyExceedsMaxAttempts(
            $connectionName, $job, (int)$options->maxTries
        );

        if ($job->isDeleted()) {
            $this->raiseAfterJobEvent($connectionName, $job);

            return;
        }

        $job->fire();

        $this->raiseAfterJobEvent($connectionName, $job);
    }
}
