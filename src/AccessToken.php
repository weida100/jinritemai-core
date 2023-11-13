<?php
declare(strict_types=1);

/**
 * Author: Weida
 * Date: 2023/11/5 10:09
 * Email: sgenmi@gmail.com
 */

namespace Weida\JinritemaiCore;

use RuntimeException;
use Psr\SimpleCache\CacheInterface;
use Weida\Oauth2Core\Contract\HttpClientInterface;
use Weida\JinritemaiCore\Contract\AccessTokenInterface;

class AccessToken implements AccessTokenInterface
{
    private int|string $clientId;
    private string $clientSecret;
    private int|string $uid;
    private string $refreshToken;
    private string $accessToken='';
    private ?CacheInterface $cache;
    private ?HttpClientInterface $httpClient;
    private $callback;
    private string $cacheKey='';
    public function __construct(
        int|string $clientId,string $clientSecret,int $shopId, string $refreshToken,
        ?CacheInterface $cache=null, ?HttpClientInterface $httpClient=null,?callable $callback=null
    )
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->shopId = $shopId;
        $this->refreshToken = $refreshToken;
        $this->cache = $cache;
        $this->httpClient = $httpClient;
        $this->callback = $callback;
    }

    public function getToken(bool $isRefresh = false): string
    {
        if(!empty($this->accessToken)){
            return $this->accessToken;
        }
        if(!$isRefresh){
            $token = $this->cache->get($this->getCacheKey());
            if (!empty($token)) {
                return $token;
            }
        }
        $url = "https://openapi-fxg.jinritemai.com/token/refresh";

        $params=[
            'method'=>'token.refresh',
            'app_key'=> $this->clientId,
            'param_json'=>[
                'grant_type'=>'refresh_token',
                'refresh_token'=>$this->refreshToken,
            ],
            'timestamp'=>time(),
            'sign_method'=>'hmac-sha256',
            'v'=>'2'
        ];
        $params['param_json'] = json_encode($params['param_json']);
        $params['sign'] = Encryptor::sign($params,$this->clientSecret);

        $resp = $this->httpClient->request('POST',$url,[
            'headers'=>[
                'Content-Type'=>'application/json'
            ],
            'body'=>json_encode($params)
        ]);

        if($resp->getStatusCode()!=200){
            throw new RuntimeException('Request access_token exception');
        }
        $arr = json_decode($resp->getBody()->getContents(),true);

        if (empty($arr['data']['access_token'])) {
            throw new RuntimeException('Failed to get access_token: ' . json_encode($arr, JSON_UNESCAPED_UNICODE));
        }
        //走刷新流程，这里刷新和其他一般的oauth2不太一样。存在同时刷新access_token和refresh_token,
        //如果用于保存refresh_token,这里走回调处理
        if($this->callback && is_callable($this->callback)){
            try {
                call_user_func($this->callback,$arr['data']);
            }catch (\Throwable $e){
            }
        }
        $this->cache->set($this->getCacheKey(), $arr['data']['access_token'], intval($arr['data']['expires_in'])-1800);
        return $arr['data']['access_token'];
    }

    public function setToken(string $accessToken): static
    {
        $this->accessToken = $accessToken;
        return $this;
    }

    public function expiresTime(): int
    {
        return  $this->cache->ttl($this->getCacheKey());
    }

    public function getParams(): array
    {
        return [
            'client_id'=>$this->clientId,
            'secret'=>$this->clientSecret,
            'refresh_token'=>$this->refreshToken,
            'shop_id'=>$this->shopId,
            'cache'=>$this->cache,
            'httpClient'=>$this->httpClient
        ];
    }

    public function getCacheKey(): string
    {
        if(!$this->shopId){
            throw new RuntimeException('shop_id not fund');
        }
        if(empty($this->cacheKey)){
            $this->cacheKey = sprintf("access_token:%s:%s", $this->clientId,$this->shopId);
        }
        return $this->cacheKey;
    }

    public function setCacheKey(string $key): static
    {
        $this->cacheKey = $key;
        return $this;
    }

    public function saveCache(int|string $shopId,string $accessToken,int $expiresIn):bool {
        $this->shopId = $shopId;
        $this->cache->set($this->getCacheKey(), $accessToken, $expiresIn-1800);
        return true;
    }
}