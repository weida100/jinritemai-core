<?php
declare(strict_types=1);
/**
 * Author: Weida
 * Date: 2023/11/9 23:12
 * Email: sgenmi@gmail.com
 */

namespace Weida\JinritemaiCore;

use GuzzleHttp\Client;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use Weida\Oauth2Core\Contract\HttpClientInterface;
use Weida\JinritemaiCore\Contract\AccessTokenInterface;
use Weida\JinritemaiCore\Contract\ApiInterface;
use Weida\JinritemaiCore\Contract\WithAccessTokenClientInterface;

class WithAccessTokenClient implements WithAccessTokenClientInterface
{
    private AccessTokenInterface $accessToken;
    private Client $client;
    public function __construct(HttpClientInterface $httpClient,AccessTokenInterface $accessToken)
    {
        $this->client = $httpClient->getClient();
        $this->accessToken = $accessToken;
    }

    /**
     * @param string $method
     * @param string|ApiInterface $uri
     * @param array $options
     * @return ResponseInterface
     * @throws Throwable
     * @author Weida
     */
    public function request(string $method, string|ApiInterface $uri, array $options = []): ResponseInterface
    {
        $method = strtoupper($method);
        return match ($method) {
            'GET' => $this->get($uri, $options),
            'POST' => $this->post($uri, $options),
            default => throw new InvalidArgumentException(sprintf("%s not supported", $method)),
        };
    }

    /**
     * @param string|ApiInterface $uri
     * @param array $options
     * @return ResponseInterface
     * @throws Throwable
     * @author Weida
     */
    public function get(string|ApiInterface $uri, array $options = []): ResponseInterface
    {
        if($uri instanceof ApiInterface){
            $method = $uri->getMethod();
            $arr = $this->_gerParams($method,$uri->getParams());
            $uri = $uri->getUrl();
        }else{
            $method = $uri;
            if(!isset($options['query']) && $options){
                if(str_contains($uri,'http')){
                    $method = $options['method']??"";
                }
            }else{
                if( str_contains($uri,'http')){
                    $method = $options['query']['method']??"";
                }
            }
            $arr= $this->_gerParams($method,$options);
        }
        if(empty($uri)){
            $uri = '/'.str_replace('.','/',$method);
        }
        $arr['common_params']['param_json'] = $arr['param_json'];
        $options['query'] =  $arr['common_params'];
        return $this->client->get($uri,$options);
    }

    /**
     * @param string|ApiInterface $uri
     * @param array $options
     * @return ResponseInterface
     * @throws Throwable
     * @author Weida
     */
    public function post(string|ApiInterface $uri, array $options = []): ResponseInterface
    {
        if($uri instanceof ApiInterface){
            $method = $uri->getMethod();
            if(isset($options['body'])){
                $arr = $this->_gerParams($method,$options['body']);
            }else{
                $arr = $this->_gerParams($method,$uri->getParams());
            }
            $uri = $uri->getUrl();
        }else{
            $method = $uri;
            if(str_contains($uri,'http')){
                $method = $options['body']['method']??"";
            }
            if(isset($options['body'])){
                $arr = $this->_gerParams($method,$options['body']);
            }else{
                $arr = $this->_gerParams($method,$options);
            }
        }
        $options['headers']['Content-Type'] = 'application/json';
        if(empty($uri)){
            $uri = '/'.str_replace('.','/',$method);
        }
        $options['query']= $arr['common_params'];
        $options['body']= $arr['param_json'];
        return $this->client->post($uri,$options);
    }

    /**
     * @param string|ApiInterface $uri
     * @param array $postData
     * @return ResponseInterface
     * @throws Throwable
     * @author Weida
     */
    public function postJson(string|ApiInterface $uri, array $postData = []): ResponseInterface
    {
        return $this->post($uri,['body'=>$postData]);
    }

    /**
     * @param string $method
     * @param array|string $params
     * @return array
     * @author Weida
     */
    private function _gerParams(string $method,array|string $params): array
    {
        if(is_string($params)){
            $params = json_decode($params,true);
        }
        if(empty($method)){
            throw new InvalidArgumentException("method not fund");
        }
        unset($params['method']);
        $_accessParams = $this->accessToken->getParams();
        $comParams['method'] = $method;
        $comParams["app_key"] = $_accessParams['client_id']??'';
        $comParams['access_token'] = $this->accessToken->getToken();
        $comParams["timestamp"] = date('Y-m-d H:i:s');
        $comParams['v']='2';
        $comParams['sign_method']='hmac-sha256';
        $apiParams['param_json'] = $params;
        $allPrams = array_merge($comParams, $apiParams);
        //签名
        $comParams["sign"] = Encryptor::sign($allPrams,$_accessParams['secret']);
        return [
            'common_params'=>$comParams,
            'param_json'=>json_encode($params)
        ];
    }

}