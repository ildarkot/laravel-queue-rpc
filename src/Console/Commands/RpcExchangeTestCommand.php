<?php

namespace IldarK\LaravelQueueRpc\Console\Commands;

use IldarK\LaravelQueueRpc\Services\RpcService;
use Exception;
use Illuminate\Console\Command;

class RpcExchangeTestCommand extends Command
{
    protected $signature = 'rpc:exchange-test {routingKey} {json}';

    /**
     * @throws Exception
     */
    public function __construct(private readonly RpcService $rpcService)
    {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        $exchange = config('queue.connections.rabbitmq.service_exchange');
        $routingKey = $this->argument('routingKey');
        $json = json_decode($this->argument('json'), true);

        $response = $this->rpcService->exchange($exchange, $routingKey, $json);

        $this->info($response->toJson());
    }
}
