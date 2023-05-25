<?php

namespace IldarK\LaravelQueueRpc\Services;

use Exception;
use Illuminate\Support\Str;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use RuntimeException;

class RpcService
{
    /**
     * @throws Exception
     */
    public function exchange(string $exchange, string $routingKey, array $data, int $timeout = 5): RpcResponse
    {
        $response = null;
        $correlationId = Str::uuid()->toString();
        $responseQueue = 'amq.rabbitmq.reply-to';

        $connection = $this->makeConnection();
        $channel = $connection->channel();

        $channel->exchange_declare(
            exchange: $exchange,
            type: 'direct',
            durable: true,
            auto_delete: false
        );
        $channel->basic_consume(
            queue: $responseQueue,
            no_ack: true,
            callback: function ($amqpResponse) use ($correlationId, &$response) {
                if ($amqpResponse->get('correlation_id') == $correlationId) {
                    $response = $amqpResponse->body;
                }
            },
        );

        $msg = new AMQPMessage(
            json_encode([
                'payload' => $data
            ]),
            [
                'correlation_id' => $correlationId,
                'reply_to' => $responseQueue,
                'delivery_mode' => 1,
            ]
        );

        $channel->basic_publish(
            $msg,
            $exchange,
            $routingKey,
            true
        );

        while (!$response) {
            $channel->wait(timeout: $timeout);
        }

        $channel->close();
        $connection->close();

        return RpcResponse::parse(json_decode($response, true));
    }

    /**
     * @throws Exception
     */
    public function initDirectExchanges(): void
    {
        $connections = collect(config('queue.connections'))
            ->filter(fn($item, $key) => Str::startsWith($key, config('queue.connections.rabbitmq.service_exchange')));

        $connection = $this->makeConnection();
        $channel = $connection->channel();

        foreach ($connections as $connection) {
            $queueOptions = $connection['options']['queue'];

            $channel->exchange_declare(
                exchange: $queueOptions['exchange'],
                type: $queueOptions['exchange_type'],
                durable: true,
                auto_delete: false
            );
            $channel->queue_declare(queue: $queueOptions['exchange_routing_key'], durable: true, auto_delete: false);
            $channel->queue_bind($connection['queue'], $queueOptions['exchange'], $queueOptions['exchange_routing_key']);
        }

        $channel->close();
    }

    public function initFanoutExchanges(): void
    {
        $fanouts = config('queue.connections.rabbitmq.fanouts', []);

        if (!count($fanouts)) {
            return;
        }

        $connection = $this->makeConnection();
        $channel = $connection->channel();

        foreach ($fanouts as $fanout) {
            $channel->exchange_declare(
                exchange: $fanout,
                type: 'fanout',
                durable: true,
                auto_delete: false
            );
        }

        $channel->close();
    }

    /**
     * @throws Exception
     */
    private function makeConnection(): AMQPStreamConnection
    {
        $settings = config('queue.connections.rabbitmq.hosts')[0];

        return new AMQPStreamConnection(
            host: $settings['host'],
            port: $settings['port'],
            user: $settings['user'],
            password: $settings['password'],
            vhost: $settings['vhost'],
            connection_timeout: 30,
            heartbeat: 10
        );
    }
}
