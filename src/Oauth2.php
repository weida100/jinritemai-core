<?php
declare(strict_types=1);
/**
 * Author: Weida
 * Date: 2023/11/9 21:53
 * Email: sgenmi@gmail.com
 */

namespace Weida\JinritemaiCore;

use InvalidArgumentException;
use RuntimeException;
use Throwable;
use Weida\Oauth2Core\Contract\ConfigInterface;
use Weida\Oauth2Core\Contract\UserInterface;
use Weida\Oauth2Core\AbstractApplication;
use Weida\Oauth2Core\User;
use Weida\JinritemaiCore\Contract\AccessTokenInterface;

class Oauth2 extends AbstractApplication
{

    private AccessTokenInterface $accessToken;
    public function __construct(array|ConfigInterface $config,AccessTokenInterface $accessToken)
    {
        parent::__construct($config);
        $this->accessToken = $accessToken;
    }

    /**
     * @return string
     * @author Weida
     */
    protected function getAuthUrl(): string
    {

    }

    /**
     * @param string $code
     * @return string
     * @author Sgenmi
     */
    protected function getTokenUrl(string $code): string
    {
        $params=[
            'method'=>'token.create',
            'app_key'=> $this->getConfig()->get('client_id'),
            'param_json'=>[
                'code'=>$code,
                'grant_type'=>'authorization_code',
            ],
            'timestamp'=>time(),
            'sign_method'=>'hmac-sha256',
            'v'=>'2'
        ];
        $params['sign'] = Encryptor::Sign($params,$this->getConfig()->get('client_secret'));
        $params['param_json'] = json_encode($params['param_json']);
        return 'https://openapi-fxg.jinritemai.com?'.http_build_query($params);
    }


    protected function getUserInfoUrl(string $accessToken): string
    {
        return '';
    }

    /**
     * @param string $accessToken
     * @return UserInterface
     * @throws Throwable
     * @author Weida
     */
    public function userFromToken(string $accessToken): UserInterface
    {
        throw new \Exception("官方api不支持，只能授权时获取用户消息");
    }

    /**
     * @param string $code
     * @return UserInterface
     * @throws Throwable
     * @author Weida
     */
    public function userFromCode(string $code): UserInterface
    {
        $res = $this->tokenFromCode($code);
        return new User(array_merge([
            'uid'=>$res['data']['shop_id'],
            'nickname'=>$res['data']['shop_name'],
            'name'=>$res['data']['operator_name']
        ],$res));
    }

    /**
     * @param string $code
     * @return array
     * @throws Throwable
     * @author Weida
     */
    public function tokenFromCode(string $code): array
    {
        $url =  $this->getTokenUrl($code);
        $resp = $this->getHttpClient()->request('GET',$url);
        if($resp->getStatusCode()!=200){
            throw new RuntimeException('Request access_token exception');
        }
        $arr = json_decode($resp->getBody()->getContents(),true);
        if (empty($arr['data']['access_token'])) {
            throw new RuntimeException('Failed to get access_token: ' . json_encode($arr, JSON_UNESCAPED_UNICODE));
        }
        $callback = $this->getConfig()->get('access_token_callback');
        if($callback && is_callable($callback)){
            try {
                call_user_func($callback,$arr['data']);
            }catch (Throwable $e){
            }
        }
        $this->accessToken->saveCache($arr['data']['shop_id'],$arr['data']['access_token'],intval($arr['data']['expires_in']));
        return $arr;
    }
}