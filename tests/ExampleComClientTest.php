<?php

declare(strict_types=1);

namespace App\Tests;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Vodovskoff\ExampleComClient\Dto\Comment;
use Vodovskoff\ExampleComClient\ExampleComClient;
use Vodovskoff\ExampleComClient\Exception\ExampleComClientException;

final class ExampleComClientTest extends TestCase
{
    private ClientInterface $httpClient;
    private ExampleComClient $exampleComClient;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $streamFactory = $this->createMock(StreamFactoryInterface::class);

        $requestMock = $this->createMock(RequestInterface::class);

        $requestMock->method('withHeader')
            ->willReturnSelf();
        $requestMock->method('withBody')
            ->willReturnSelf();

        $requestFactory->expects($this->any())
            ->method('createRequest')
            ->willReturn($requestMock);

        $this->exampleComClient = new ExampleComClient(
            $this->httpClient,
            $requestFactory,
            $streamFactory
        );
    }

    public function testAddCommentSuccess(): void
    {
        $this->httpClient
            ->method('sendRequest')
            ->willReturn(new Response(200));

        $result = $this->exampleComClient->addComment('name', 'text');
        $this->assertTrue($result);
    }

    public function testAddCommentBadResponse(): void
    {
        $this->httpClient
            ->method('sendRequest')
            ->willReturn(new Response(
                status: 404,
                body: 'Not Found'
            ));

        $result = $this->exampleComClient->addComment('Test Name', 'Test Comment');

        $this->assertInstanceOf(ExampleComClientException::class, $result);
        $this->assertEquals($result->getMessage(), 'Failed to add comment. Status code: 404. Response body: Not Found');
    }

    public function testAddCommentClientException(): void
    {
        $this->httpClient
            ->method('sendRequest')
            ->willThrowException($this->createMock(ClientExceptionInterface::class));

        $result = $this->exampleComClient->addComment('Test Name', 'Test Comment');

        $this->assertInstanceOf(ClientExceptionInterface::class, $result);
    }

    #[DataProvider('updateCommentDataProvider')]
    public function testUpdateCommentSuccess(
        int $id,
        ?string $name,
        ?string $text
    ): void {
        $this->httpClient->method('sendRequest')->willReturn(new Response(200));

        $result = $this->exampleComClient->updateComment($id, $name, $text);

        $this->assertTrue($result);
    }

    public function testUpdateCommentReturnsClientException(): void
    {
        $exception = $this->createMock(ClientExceptionInterface::class);
        $this->httpClient->method('sendRequest')->willThrowException($exception);

        $result = $this->exampleComClient->updateComment(1, 'Updated Name', 'Updated Comment');
        $this->assertInstanceOf(ClientExceptionInterface::class, $result);
    }

    #[DataProvider('updateCommentDataProvider')]
    public function testUpdateCommentBadResponse(
        int $id,
        ?string $name,
        ?string $text
    ): void {
        $this->httpClient->method('sendRequest')->willReturn(new Response(
            status: 404,
            body: 'Not Found'
        ));

        $result = $this->exampleComClient->updateComment($id, $name, $text);

        $this->assertInstanceOf(ExampleComClientException::class, $result);
        $this->assertEquals($result->getMessage(), 'Failed to update comment. Status code: 404. Response body: Not Found');
    }

    #[DataProvider('commentsDataProvider')]
    public function testGetCommentsSuccess(string $responseBody, array $expectedComments): void
    {
        $response = new Response(200, [], $responseBody);

        $this->httpClient->method('sendRequest')->willReturn($response);

        $result = $this->exampleComClient->getComments();

        $this->assertEquals($expectedComments, $result);
    }

    public function testGetCommentsReturnsClientException(): void
    {
        $exception = $this->createMock(ClientExceptionInterface::class);
        $this->httpClient->method('sendRequest')->willThrowException($exception);

        $result = $this->exampleComClient->getComments();

        $this->assertInstanceOf(ClientExceptionInterface::class, $result);
    }

    public function testGetCommentsReturnsNotJson(): void
    {
        $this->httpClient->method('sendRequest')->willReturn(new Response(
            status: 200,
            body: 'Not a JSON'
        ));
        $result = $this->exampleComClient->getComments();
        $this->assertInstanceOf(\JsonException::class, $result);
    }

    public function testGetCommentsReturnsBadJson(): void
    {
        $this->httpClient->method('sendRequest')->willReturn(new Response(
            status: 200,
            body: '[{"idi":1,"name":"name1","text":"comment"}]'
        ));
        $result = $this->exampleComClient->getComments();
        $this->assertInstanceOf(ExampleComClientException::class, $result);
    }

    public static function updateCommentDataProvider(): array
    {
        return [
            'update with name and text' => [1, 'name', 'text'],
            'update with only text' => [1, null, 'text'],
            'update with only name' => [1, 'name', null],
        ];
    }

    public static function commentsDataProvider(): array
    {
        return [
            'no comments' => [
                'responseBody' => '[]',
                'expectedComments' => [],
            ],
            'one comment' => [
                'responseBody' => '[{"id":1,"name":"name1","text":"comment1"}]',
                'expectedComments' => [
                    new Comment(1, 'name1', 'comment1'),
                ],
            ],
            'two comments' => [
                'responseBody' => '[{"id":1,"name":"name1","text":"comment1"},{"id":2,"name":"name2","text":"comment2"}]',
                'expectedComments' => [
                    new Comment(1, 'name1', 'comment1'),
                    new Comment(2, 'name2', 'comment2'),
                ],
            ],
        ];
    }
}
