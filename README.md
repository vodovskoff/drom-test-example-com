example.com client
===============

Client for example.com API compatible with PSR-7 and PSR-17 Interfaces

## Requirements
- Docker
- GNU Make

## How to run
Run `make start` or `make create` (for the 1st time), then you can see weather it works with `make test`

## Standalone usage example:

```php
$httpClient = new GuzzleHttp\Client();
$httpFactory = new GuzzleHttp\Psr7\HttpFactory();
$exampleComClient = new ExampleComClient(
    $httpClient, $httpFactory, $httpFactory
);

$comments = $exampleComClient->getComments();
if (!is_array($comments)) {
    // handle error
}
foreach ($comments as $comment) {
    echo $comment->getId();
    echo $comment->getName();
    echo $comment->getText();
}

if (true !== $exampleComClient->addComment('name', 'text')) {
    // handle error
}

if (true !== $exampleComClient->updateComment(1, 'name', 'text')) {
    // handle error
}
```

## Usage example in Symfony app:

#### **`config.yml`**
```yaml
    Psr\Http\Message\RequestFactoryInterface:
      class: Nyholm\Psr7\Factory\Psr17Factory

    Psr\Http\Message\StreamFactoryInterface:
      class: Nyholm\Psr7\Factory\Psr17Factory

    GuzzleHttp\ClientInterface:
      class: GuzzleHttp\Client
      arguments:
        - { timeout: 2.0 }

    Vodovskoff\ExampleComClient\ExampleComClient:
      public: true
      arguments:
        $client: '@GuzzleHttp\ClientInterface'
        $requestFactory: '@Psr\Http\Message\RequestFactoryInterface'
        $streamFactory: '@Psr\Http\Message\StreamFactoryInterface'
```
#### **`SomeService.php`**

```php
namespace App\Service;

use Psr\Log\LoggerInterface;
use Vodovskoff\ExampleComClient\ExampleComClient;

class SomeService
{
    private ExampleComClient $exampleComClient;
    private LoggerInterface $logger;

    public function __construct(ExampleComClient $exampleComClient, LoggerInterface $logger)
    {
        $this->exampleComClient = $exampleComClient;
        $this->logger = $logger;
    }

    /**
     * @return Comment[]|false
     */
    public function getComments(): array|false
    {
        $result = $this->exampleComClient->getComments();
        if (!is_array($result)) {
            $this->logger->error('Failed to get comments', ['result' => $result]);
            return false;
        }

        return $comments;
    }
    
    public function addComment(string $name, string $text): bool
    {
        $result = $this->exampleComClient->addComment($name, $text);
        if (true !== $this->exampleComClient->addComment($name, $text)) {
            $this->logger->error('Failed to add comment', ['result' => $result]);
            return false;
        }

        return true;
    }
    
    public function updateComment(int $id, string $name, string $text): bool
    {
        $result = $this->exampleComClient->updateComment($id, $name, $text);
        if (true !== $result) {
            $this->logger->error('Failed to update comment', ['result' => $result]);
            return false;
        }

        return true;
    }
}
```