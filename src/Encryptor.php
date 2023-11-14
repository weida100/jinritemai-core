<?php
declare(strict_types=1);

/**
 * Author: Sgenmi
 * Date: 2023/11/13 2:58 PM
 * Email: 150560159@qq.com
 */

namespace Weida\JinritemaiCore;

class Encryptor
{
    /**
     * @param array $params
     * @param string $secret
     * @return string
     * @author Weida
     */
    public static function sign(array $params,string $secret):string{
        unset($params["sign"]);
        $paramJsonArr = $params['param_json'];

        if(is_string($paramJsonArr)){
            $paramJsonArr = json_decode($paramJsonArr,true);
        }
        self::recKSort($paramJsonArr);
        $paramJson= json_encode($paramJsonArr, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $paramPattern = 'app_key'.$params['app_key'].'method'.$params['method'].'param_json'.$paramJson.'timestamp'.$params['timestamp'].'v'.$params['v'];
        $signPattern = $secret.$paramPattern.$secret;
        return hash_hmac("sha256", $signPattern, $secret);
    }

    // 关联数组排序，递归
    private static function recKSort(&$arr): void
    {
        $kString = true;
        foreach ($arr as $k => &$v) {
            if (!is_string($k)) {
                $kString = false;
            }
            if (is_array($v)) {
                self::recKSort($v);
            }
        }
        unset($v);
        if ($kString) {
            ksort($arr);
        }
    }

}