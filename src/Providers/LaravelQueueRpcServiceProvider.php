<?php

namespace IldarK\LaravelQueueRpc\Providers;

use Illuminate\Foundation\AliasLoader;
use IldarK\LaravelQueueRpc\Queue\RpcMQJob;
use IldarK\LaravelQueueRpc\Routing\Facades\RpcRoute;
use IldarK\LaravelQueueRpc\Routing\RpcRouter;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use IldarK\LaravelQueueRpc\Console\Commands\RpcConsumeCommand;
use IldarK\LaravelQueueRpc\Console\Commands\RpcExchangeTestCommand;
use IldarK\LaravelQueueRpc\Console\Commands\RpcInitExchangesCommand;

class LaravelQueueRpcServiceProvider extends ServiceProvider
{
    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function register(): void
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            RpcConsumeCommand::class,
            RpcExchangeTestCommand::class,
            RpcInitExchangesCommand::class,
        ]);

        $this->app->singleton('rpcRouter', function ($app) {
            return (new RpcRouter($app));
        });

        $this->app->booting(function () {
            $loader = AliasLoader::getInstance();
            $loader->alias(RpcRoute::class, RpcRouter::class);
        });

        $this->booted(function () {
            /** @var RpcRouter $router */
            $router = $this->app['rpcRouter'];
            $router->loadFromPath(base_path('routes/rpc.php'));

            $this->initExchangeConnections($router->getRoutes());
        });
    }

    private function initExchangeConnections(Collection $collection)
    {
        $connections = Config::get('queue.connections');
        $rabbitMQConnection = Arr::get($connections, 'rabbitmq');

        $exchangeConnections = $collection->mapWithKeys(function ($route) use ($rabbitMQConnection) {
            $routingKey = $route['path'];

            return [
                $routingKey => [
                    'driver' => Arr::get($rabbitMQConnection, 'driver', 'rabbitmq'),
                    'hosts' => Arr::get($rabbitMQConnection, 'hosts', []),
                    'queue' => $routingKey,
                    'options' => [
                        'queue' => [
                            'exchange' => $rabbitMQConnection['service_exchange'],
                            'exchange_type' => 'direct',
                            'exchange_routing_key' => $routingKey,
                            'job' => RpcMQJob::class,
                        ],
                        'heartbeat' => 10,
                    ],
                    'worker' => $rabbitMQConnection['worker'],
                ]
            ];
        })->toArray();

        Config::set('queue.connections', array_merge($connections, $exchangeConnections));
    }
}
