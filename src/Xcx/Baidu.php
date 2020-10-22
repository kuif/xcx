<?php
/**
 * @Author: [FENG] <1161634940@qq.com>
 * @Date:   2020-10-13 17:11:17
 * @Last Modified by:   [FENG] <1161634940@qq.com>
 * @Last Modified time: 2020-10-22T14:56:05+08:00
 */
namespace fengkui\Xcx;

use Exception;
use GuzzleHttp\Client;

/**
 * 百度小程序
 */
class Baidu
{
    private static $jscode2session = 'https://spapi.baidu.com/oauth/jscode2sessionkey';
    private static $token = 'https://openapi.baidu.com/oauth/2.0/token';

    private static $config = array(
        'appid' => '', // appid
        'appkey' => '', // appkey
        'secret' => '', // secret
    );

    /**
     * [__construct 构造函数]
     * @param [type] $config [传递支付相关配置]
     */
    public function __construct($config=NULL){
        $config && self::$config = $config;
    }

    /**
     * [openid 获取 openid]
     * @param  string $code [code]
     * @return [type]       [description]
     */
    public static function openid($code)
    {
        $options = [
            'client_id' => self::$config['appkey'],
            'sk'        => self::$config['secret'],
            'code'      => $code,
        ];

        $response = Http::get(self::$jscode2session, $options);
        $result = json_decode($response, true);
        return $result;
    }

    /**
     * [accessToken 获取 access_token]
     * @return [type] [description]
     */
    public static function accessToken()
    {
        $options = [
            'client_id' => self::$config['appkey'],
            'client_secret' => self::$config['secret'],
            'grant_type' => 'client_credentials',
            'scop' => 'smartapp_snsapi_base'
        ];

        $response = Http::get(self::$token, $options);
        $result = json_decode($response, true);
        return $result;
    }

    /**
     * [decrypt 数据解密：低版本使用mcrypt库（PHP < 5.3.0），高版本使用openssl库（PHP >= 5.3.0）。]
     * @param  [type] $session_key [登录的code换得的]
     * @param  [type] $ciphertext  [待解密数据，返回的内容中的data字段]
     * @param  [type] $iv          [加密向量，返回的内容中的iv字段]
     * @return [type]              [description]
     */
    public static function decrypt($session_key, $ciphertext, $iv)
    {
        $app_key = self::$config['appkey'];

        $session_key = base64_decode($session_key);
        $iv = base64_decode($iv);
        $ciphertext = base64_decode($ciphertext);

        $plaintext = false;
        if (function_exists("openssl_decrypt")) {
            $plaintext = openssl_decrypt($ciphertext, "AES-192-CBC", $session_key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);
        } else {
            $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, null, MCRYPT_MODE_CBC, null);
            mcrypt_generic_init($td, $session_key, $iv);
            $plaintext = mdecrypt_generic($td, $ciphertext);
            mcrypt_generic_deinit($td);
            mcrypt_module_close($td);
        }
        if ($plaintext == false) {
            return false;
        }

        // trim pkcs#7 padding
        $pad = ord(substr($plaintext, -1));
        $pad = ($pad < 1 || $pad > 32) ? 0 : $pad;
        $plaintext = substr($plaintext, 0, strlen($plaintext) - $pad);

        // trim header
        $plaintext = substr($plaintext, 16);
        // get content length
        $unpack = unpack("Nlen/", substr($plaintext, 0, 4));
        // get content
        $content = substr($plaintext, 4, $unpack['len']);
        // get app_key
        $app_key_decode = substr($plaintext, $unpack['len'] + 4);

        return $app_key == $app_key_decode ? $content : false;
    }

}
