<?php

namespace IldarK\LaravelQueueRpc\Console\Commands;

use IldarK\LaravelQueueRpc\Queue\RpcWorker;
use IldarK\LaravelQueueRpc\Services\RpcService;
use Exception;
use VladimirYuldashev\LaravelQueueRabbitMQ\Console\ConsumeCommand;

class RpcConsumeCommand extends ConsumeCommand
{
    protected $signature = 'rpc:consume
                            {connection? : The name of the queue connection to work}
                            {--name=default : The name of the consumer}
                            {--queue= : The names of the queues to work}
                            {--once : Only process the next job on the queue}
                            {--stop-when-empty : Stop when the queue is empty}
                            {--delay=0 : The number of seconds to delay failed jobs (Deprecated)}
                            {--backoff=0 : The number of seconds to wait before retrying a job that encountered an uncaught exception}
                            {--max-jobs=0 : The number of jobs to process before stopping}
                            {--max-time=0 : The maximum number of seconds the worker should run}
                            {--force : Force the worker to run even in maintenance mode}
                            {--memory=128 : The memory limit in megabytes}
                            {--sleep=3 : Number of seconds to sleep when no job is available}
                            {--timeout=60 : The number of seconds a child process can run}
                            {--tries=1 : Number of times to attempt a job before logging it failed}
                            {--rest=0 : Number of seconds to rest between jobs}

                            {--max-priority=}
                            {--consumer-tag}
                            {--prefetch-size=0}
                            {--prefetch-count=1000}
                           ';

    /**
     * @throws Exception
     */
    public function __construct(private readonly RpcService $rpcService)
    {
        parent::__construct(new RpcWorker(app('rabbitmq.consumer')), app('cache.store'));
    }

    public function handle(): void
    {
        $connectionName = $this->argument('connection');

        if (!$connectionName) {
            $this->error('connection not set');
            exit(1);
        }

        parent::handle();
    }
}
