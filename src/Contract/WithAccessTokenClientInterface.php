<?php
declare(strict_types=1);
/**
 * Author: Weida
 * Date: 2023/11/5 23:15
 * Email: sgenmi@gmail.com
 */

namespace Weida\JinritemaiCore\Contract;

use Psr\Http\Message\ResponseInterface;

interface WithAccessTokenClientInterface
{
    public function request(string $method, string|ApiInterface $uri, array $options = []): ResponseInterface;
    public function get(string|ApiInterface $uri, array $options = []): ResponseInterface;
    public function post(string|ApiInterface $uri, array $options = []): ResponseInterface;
    public function postJson(string|ApiInterface $uri, array $postData = []):ResponseInterface;

}