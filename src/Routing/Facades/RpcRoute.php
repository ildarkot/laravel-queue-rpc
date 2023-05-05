<?php

namespace IldarK\LaravelQueueRpc\Routing\Facades;

use IldarK\LaravelQueueRpc\Routing\RpcRouter;
use Illuminate\Support\Facades\Facade;

/**
 * @method static RpcRouter route(string $routingKey, array|callable $action)
 */
class RpcRoute extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'rpcRoute';
    }
}
