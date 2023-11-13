<?php
declare(strict_types=1);
/**
 * Author: Weida
 * Date: 2023/11/9 23:07
 * Email: sgenmi@gmail.com
 */

namespace Weida\JinritemaiCore;

use GuzzleHttp\Client;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use Weida\Oauth2Core\Contract\HttpClientInterface;

class HttpClient implements HttpClientInterface
{
    private Client $client;
    private array $config;

    public function __construct(array $config,)
    {
        $this->config = $config;
        $this->client= new Client($config);
    }

    /**
     * @param RequestInterface $request
     * @param array $options
     * @return ResponseInterface
     * @throws Throwable
     * @author Weida
     */
    public function send(RequestInterface $request, array $options = []): ResponseInterface
    {
        return $this->client->send($request,$options);
    }

    /**
     * @param RequestInterface $request
     * @param array $options
     * @return PromiseInterface
     * @author Weida
     */
    public function sendAsync(RequestInterface $request, array $options = []): PromiseInterface
    {
        return $this->client->sendAsync($request,$options);
    }

    /**
     * @param string $method
     * @param $uri
     * @param array $options
     * @return ResponseInterface
     * @throws Throwable
     * @author Weida
     */
    public function request(string $method, $uri, array $options = []): ResponseInterface
    {
        return $this->client->request($method,$uri,$options);
    }

    /**
     * @param string $method
     * @param $uri
     * @param array $options
     * @return PromiseInterface
     * @author Weida
     */
    public function requestAsync(string $method, $uri, array $options = []): PromiseInterface
    {
        return $this->client->requestAsync($method,$uri,$options);
    }

    /**
     * @param string|null $option
     * @return array|mixed|null
     * @author Weida
     */
    public function getConfig(string $option = null):mixed
    {
        return $option? ($this->config[$option]??null):$this->config;
    }

    /**
     * @return Client
     * @author Weida
     */
    public function getClient(): Client
    {
        return $this->client;
    }
}