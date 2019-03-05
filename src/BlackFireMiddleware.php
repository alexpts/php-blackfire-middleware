<?php
declare(strict_types=1);

namespace PTS\PSR15\BlackFireMiddleware;

use Blackfire\Client;
use Blackfire\ClientConfiguration;
use Blackfire\Profile\Configuration;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class BlackFireMiddleware implements MiddlewareInterface
{

    /** @var Client */
    protected $client;
    /** @var Configuration */
    protected $probeConfig;

    public function __construct(string $clientId = null, string $clientToken = null)
    {
        $config = new ClientConfiguration($clientId, $clientToken);
        $this->client = new Client($config);
        $this->probeConfig = new Configuration;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        $isNeedProfile = $request->getQueryParams()['profiler'] ?? null;
        if ($isNeedProfile !== '1') {
            return $next->handle($request);
        }

        $probeConfig = clone $this->probeConfig;
        $probeConfig = $probeConfig->setTitle((string)$request->getUri());

        $probe = $this->client->createProbe($probeConfig);
        $response = $next->handle($request);
        $this->client->endProbe($probe);

        return $response;
    }
}
