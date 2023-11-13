<?php
declare(strict_types=1);
/**
 * Author: Weida
 * Date: 2023/11/4 23:26
 * Email: sgenmi@gmail.com
 */

namespace Weida\JinritemaiCore;

use Psr\SimpleCache\CacheInterface;
use Weida\Oauth2Core\Config;
use Weida\Oauth2Core\Contract\ConfigInterface;
use Weida\Oauth2Core\Contract\HttpClientInterface;
use Weida\JinritemaiCore\Cache\RedisAdapter;
use Weida\JinritemaiCore\Contract\AccessTokenInterface;

abstract class AbstractApplication
{
    private Oauth2 $oauth2;
    private ConfigInterface $config;
    private AccessTokenInterface $accessToken;
    private CacheInterface $cache;
    private HttpClientInterface $httpClient;
    private int|string $shopId=0;
    protected string $cacheNamespace="weida:oceanengine";

    public function __construct(array $config)
    {
        $this->setConfig(new Config($config));
    }

    /**
     * @return ConfigInterface
     * @author Weida
     */
    public function getConfig():ConfigInterface {
        return $this->config;
    }

    /**
     * @param ConfigInterface $config
     * @return $this
     * @author Weida
     */
    public function setConfig(ConfigInterface $config):static {
        $this->config = $config;
        return $this;
    }

    public function getCache(): CacheInterface
    {
        if(empty($this->cache)){
            $cacheConfig = $this->config->get('cache');
            if(!empty($cacheConfig['redis'])){
                $this->cache = new RedisAdapter($cacheConfig['redis'],$this->getCacheNamespace());
            }else{
                $this->cache = new FileSystemAdapter();
            }
        }
        return $this->cache;
    }

    /**
     * @return string
     * @author Weida
     */
    public function getCacheNamespace(): string
    {
        return $this->cacheNamespace;
    }

    /**
     * @param string $cacheNamespace
     * @return $this
     * @author Weida
     */
    public function setCacheNamespace(string $cacheNamespace): static
    {
        $this->cacheNamespace = $cacheNamespace;
        return $this;
    }

    /**
     * @param CacheInterface $cache
     * @return $this
     * @author Weida
     */
    public function setCache(CacheInterface $cache):static{
        $this->cache = $cache;
        return $this;
    }

    /**
     * @return HttpClientInterface
     * @author Weida
     */
    public function getHttpClient():HttpClientInterface
    {
        if(empty($this->httpClient)){
            $this->httpClient =   new HttpClient(['base_uri'=>'https://openapi-fxg.jinritemai.com/']);
        }
        return $this->httpClient;
    }

    /**
     * @param HttpClientInterface $httpClient
     * @return $this
     * @author Weida
     */
    public function setHttpClient(HttpClientInterface $httpClient): static
    {
        $this->httpClient = $httpClient;
        return $this;
    }

    /**
     * @return Oauth2
     * @author Weida
     */
    public function getOauth2():Oauth2{
        if(empty($this->oauth2)){
            $this->oauth2 = new Oauth2($this->getConfig(),$this->getAccessToken(0,));
            $this->oauth2->setHttpClient($this->getHttpClient());
        }
        return $this->oauth2;
    }

    /**
     * @param int|string $shopId
     * @param string $refreshToken
     * @return AccessTokenInterface
     * @author Weida
     */
    public function getAccessToken(int|string $shopId,string $refreshToken=''):AccessTokenInterface {
        if(empty($this->accessToken) || $this->shopId != $shopId){
            $this->accessToken = new AccessToken(
                $this->getConfig()->get('client_id'),
                $this->getConfig()->get('client_secret'),
                $shopId,$refreshToken,
                $this->getCache(),
                $this->getHttpClient(),
                $this->getConfig()->get('access_token_callback',null)
            );
        }
        return $this->accessToken;
    }

    /**
     * @param AccessTokenInterface $accessToken
     * @return $this
     * @author Weida
     */
    public function setAccessToken(AccessTokenInterface $accessToken):static {
        $this->accessToken = $accessToken;
        return $this;
    }



}