<?php

namespace IldarK\LaravelQueueRpc\Routing;

use Illuminate\Container\Container;
use Illuminate\Support\Collection;

class RpcRouter
{
    /**
     * The IoC container instance.
     *
     * @var \Illuminate\Container\Container
     */
    protected $container;

    /**
     * The route collection instance.
     *
     * @var Collection
     */
    protected Collection $routes;

    public function __construct(Container $container = null)
    {
        $this->routes = new Collection();
        $this->container = $container ?: new Container;
    }

    public function route(string $routingKey, array $action): RpcRouter
    {
        $this->routes->add(['path' => $routingKey, 'action' => $action]);

        return $this;
    }

    public function loadFromPath(string $path): void
    {
        require $path;
    }

    public function findRouteByRoutingKey(string $routingKey): array
    {
        return $this->routes->where('path', $routingKey)->first();
    }

    public function getRoutes(): Collection
    {
        return $this->routes;
    }
}

