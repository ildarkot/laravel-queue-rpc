<?php

namespace IldarK\LaravelQueueRpc\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class RpcResponse
{
    private readonly Collection $errors;

    public function __construct(
        private readonly int    $httpCode,
        private readonly ?array $data = null,
        $errors = []
    )
    {
        $this->errors = collect($errors ?? []);
    }

    public function toJson(): string
    {
        $responseData = [
            'httpCode' => $this->httpCode,
            'result' => $this->data
        ];

        if (count($this->errors)) {
            $responseData['errors'] = $this->errors->toArray();
        }

        return json_encode($responseData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public static function success(?array $data = null, int $httpCode = Response::HTTP_OK): static
    {
        return new static($httpCode, $data);
    }

    public static function clientError(string $error, int $httpCode = Response::HTTP_BAD_REQUEST, ): static
    {
        return new static($httpCode, errors: [$error]);
    }

    public static function validationError(array $errors): static
    {
        return new static(Response::HTTP_UNPROCESSABLE_ENTITY, errors: $errors);
    }

    public static function serverError(string $error): static
    {
        return new static(Response::HTTP_INTERNAL_SERVER_ERROR, errors: [$error]);
    }


    public static function parse(array $data): static
    {
        return new static(
            Arr::get($data, 'httpCode', Response::HTTP_OK),
            Arr::get($data, 'result'),
            Arr::get($data, 'errors', [])
        );
    }

    public function hasErrors(): bool
    {
        return $this->errors->isNotEmpty();
    }

    public function getErrors(): Collection
    {
        return $this->errors;
    }

    public function getData(): ?array
    {
        return $this->data;
    }
}
