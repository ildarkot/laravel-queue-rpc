<?php

namespace IldarK\LaravelQueueRpc\Console\Commands;

use IldarK\LaravelQueueRpc\Services\RpcService;
use Exception;
use Illuminate\Console\Command;

class RpcInitExchangesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rpc:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

    public function __construct(private readonly RpcService $rpcService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     * @throws Exception
     */
    public function handle()
    {
        $this->rpcService->initExchanges();
    }
}
