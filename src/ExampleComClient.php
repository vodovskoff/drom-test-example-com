<?php

declare(strict_types=1);

namespace Vodovskoff\ExampleComClient;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Vodovskoff\ExampleComClient\Dto\Comment;
use Vodovskoff\ExampleComClient\Exception\ExampleComClientException;

class ExampleComClient
{
    private const BASE_URL = 'https://example.com';

    public function __construct(
        private readonly ClientInterface $client,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
    ) {
    }

    /** @return Comment[]|ClientExceptionInterface|\JsonException|ExampleComClientException */
    public function getComments(): array|ClientExceptionInterface|\JsonException|ExampleComClientException
    {
        try {
            $request = $this->requestFactory->createRequest('GET', self::BASE_URL.'/comments');
            $response = $this->client->sendRequest($request);

            if ($response->getStatusCode() > 299 || $response->getStatusCode() < 200) {
                return new ExampleComClientException("Failed to get comments. Status code: {$response->getStatusCode()}. Response body: {$response->getBody()}");
            }

            $responseBody = json_decode(
                json: $response->getBody()->getContents(),
                associative: true,
                flags: JSON_THROW_ON_ERROR);
            $comments = [];
            foreach ($responseBody as $item) {
                if (isset($item['id'], $item['name'], $item['text'])) {
                    $comments[] = new Comment($item['id'], $item['name'], $item['text']);
                } else {
                    return new ExampleComClientException("Invalid comment data in response: Status code: {$response->getStatusCode()}. Response body: {$response->getBody()}");
                }
            }

            return $comments;
        } catch (ClientExceptionInterface|\JsonException $exception) {
            return $exception;
        } catch (\Throwable $exception) {
            return new ExampleComClientException(
                $exception->getMessage(),
                $exception->getCode(),
                $exception
            );
        }
    }

    public function addComment(string $name, string $text): true|ClientExceptionInterface|ExampleComClientException
    {
        try {
            $request = $this->requestFactory->createRequest('POST', self::BASE_URL.'/comment')
                ->withHeader('Content-Type', 'application/json')
                ->withBody($this->streamFactory->createStream(json_encode(['name' => $name, 'text' => $text])));

            $response = $this->client->sendRequest($request);

            if ($response->getStatusCode() > 299 || $response->getStatusCode() < 200) {
                return new ExampleComClientException("Failed to add comment. Status code: {$response->getStatusCode()}. Response body: {$response->getBody()}");
            }

            return true;
        } catch (ClientExceptionInterface $exception) {
            return $exception;
        }
    }

    public function updateComment(int $id, ?string $name, ?string $text): true|ClientExceptionInterface|ExampleComClientException
    {
        try {
            $body = [];
            if (null !== $name) {
                $body['name'] = $name;
            }
            if (null !== $text) {
                $body['text'] = $text;
            }

            $request = $this->requestFactory->createRequest('PUT', self::BASE_URL.'/comment/'.$id)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($this->streamFactory->createStream(json_encode($body)));
            $response = $this->client->sendRequest($request);

            if ($response->getStatusCode() > 299 || $response->getStatusCode() < 200) {
                return new ExampleComClientException("Failed to update comment. Status code: {$response->getStatusCode()}. Response body: {$response->getBody()}");
            }

            return true;
        } catch (ClientExceptionInterface $exception) {
            return $exception;
        } catch (\Throwable $exception) {
            return new ExampleComClientException(
                $exception->getMessage(),
                $exception->getCode(),
                $exception->getPrevious()
            );
        }
    }
}
