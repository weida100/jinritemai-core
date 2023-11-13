<?php
declare(strict_types=1);
/**
 * Author: Weida
 * Date: 2023/11/4 21:35
 * Email: sgenmi@gmail.com
 */

namespace Weida\JinritemaiCore\Contract;

interface AccessTokenInterface
{
    public function getToken(bool $isRefresh=false):string;
    public function setToken(string $accessToken):static;
    public function expiresTime():int;
    public function getParams():array;
    public function getCacheKey():string;
    public function setCacheKey(string $key):static;
}