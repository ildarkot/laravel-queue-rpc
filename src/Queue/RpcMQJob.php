<?php

namespace IldarK\LaravelQueueRpc\Queue;

use IldarK\LaravelQueueRpc\Exceptions\RpcException;
use IldarK\LaravelQueueRpc\Services\RpcResponse;
use IldarK\LaravelQueueRpc\Routing\RpcRouter;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Queue\Jobs\JobName;
use Illuminate\Validation\ValidationException;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;
use VladimirYuldashev\LaravelQueueRabbitMQ\Queue\Jobs\RabbitMQJob;

class RpcMQJob extends RabbitMQJob
{
    public function payload(): array
    {
        return [
            'job' => $this->parseJobName(),
            'data' => json_decode($this->getRawBody(), true)['payload'] ?? [],
        ];
    }

    private function parseJobName(): string
    {
        /** @var RpcRouter $rpcRouter */
        $rpcRouter = app('rpcRouter');
        $route = $rpcRouter->findRouteByRoutingKey($this->message->getRoutingKey());

        return "{$route['action'][0]}@{$route['action'][1]}";
    }

    public function fire()
    {
        $payload = $this->payload();

        [$class, $method] = JobName::parse($payload['job']);

        try {
            $response = ($this->instance = $this->resolve($class))->{$method}($payload['data']);
        } catch (RpcException $exception) {
            $response = RpcResponse::clientError($exception->getMessage(), $exception->getCode());
        } catch (ValidationException $exception) {
            $response = RpcResponse::validationError($exception->validator->errors()->all());
        } catch (Throwable $exception) {
            $response = RpcResponse::serverError($exception->getMessage());
        }

        $this->publish($this->prepareResponseData($response));
    }

    private function publish(string $response)
    {
        try {
            if ($this->message->has('reply_to')) {
                $properties = [
                    'content_type' => 'application/json;charset=utf-8',
                    'delivery_mode' => 1,
                ];
                if ($this->message->has('correlation_id')) {
                    $properties['correlation_id'] = $this->message->get('correlation_id');
                }

                $message = new AMQPMessage($response, $properties);

                $channel = $this->getRabbitMQ()->getChannel();
                $channel->basic_publish($message, routing_key: $this->message->get('reply_to'));
            }
        } finally {
            $this->delete();
        }
    }

    private function prepareResponseData(RpcResponse|JsonResource|array|null $response): string
    {
        if (is_array($response) || !$response) {
            $response = RpcResponse::success($response);
        }

        if ($response instanceof JsonResource) {
            $response = RpcResponse::success($response->toArray(request()));
        }

        return $response->toJson();
    }
}
