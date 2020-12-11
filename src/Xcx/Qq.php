<?php
/**
 * @Author: [FENG] <1161634940@qq.com>
 * @Date:   2020-10-13 17:11:17
 * @Last Modified by:   [FENG] <1161634940@qq.com>
 * @Last Modified time: 2020-12-11T15:07:57+08:00
 */
namespace fengkui\Xcx;

use Exception;
use fengkui\Supports\Http;

/**
 * QQ小程序
 */
class Qq
{
    // 登录凭证校验，获取用户 openid 等信息
    private static $jscode2sessionUrl = 'https://api.q.qq.com/sns/jscode2session';
    // 获取小程序全局唯一后台接口调用凭据（access_token）
    private static $tokenUrl = 'https://api.q.qq.com/api/getToken';
    // 获取小程序码
    private static $CreateMiniCodeUrl = 'https://api.q.qq.com/api/json/qqa/CreateMiniCode';

    private static $config = array(
        'appid' => '', // appid
        'secret' => '', // secret
        'access_token' => '', // access_token
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
        $params = [
            'js_code'   => $code,
            'appid'     => self::$config['appid'],
            'secret'    => self::$config['secret'],
            'grant_type' => 'authorization_code'
        ];

        $response = Http::get(self::$jscode2sessionUrl, $params);
        $result = json_decode($response, true);
        return $result;
    }

    /**
     * [accessToken 获取 access_token]
     * @return [type] [description]
     */
    public static function accessToken()
    {
        $params = [
            'grant_type' => 'client_credential',
            'appid'     => self::$config['appid'],
            'secret'    => self::$config['secret'],
        ];

        $response = Http::get(self::$tokenUrl, $params);
        $result = json_decode($response, true);
        return $result;
    }

    /**
     * [qrcode 获取小程序二维码，图片 Buffer]
     * @param  [type]  $path  [小程序页面路径]
     * @param  integer $width [小程序码宽度 px (默认430)]
     * @param  integer $type  [获取类型 1:二维码短链 2:二维码长链  (默认1)]
     * @param  boolean $mf    [是否包含logo 默认包含]
     * @return [type]         [description]
     */
    public function qrcode($path)
    {
        if (empty(self::$config['access_token'])) {
            $access_token = self::accessToken();
            $access_token = $access_token['access_token'];
        } else {
            $access_token = self::$config['access_token'];
        }
    	$params = array(
    		'access_token' => $access_token, // 接口调用凭证
    		'appid' => self::$config['appid'], // 小程序/小游戏appid
            'path' => $path, // 扫码进入的小程序页面路径
    	);
    	$postUrl = self::$CreateMiniCodeUrl . "?access_token=" . $access_token;

    	$response = Http::post($postUrl, json_encode($params), array('Content-Type: application/json'));
        $result = json_decode($response, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            return $response;
        } else {
            throw new Exception("[" . $result['errcode'] . "] " . $result['errmsg']);
        }
    }

    /**
     * [decrypt 检验数据的真实性，并且获取解密后的明文.]
     * @param  [type] $sessionKey    [SESSSION KEY]
     * @param  [type] $encryptedData [加密的用户数据]
     * @param  [type] $iv            [与用户数据一同返回的初始向量]
     * @return [type]                [description]
     */
    public static function decrypt($sessionKey, $encryptedData, $iv)
    {
        $appid = self::$config['appid'];
        if (strlen($sessionKey) != 24) // -41001（长度错误）
            throw new Exception("[41001] session_key Wrong length");

        $aesKey=base64_decode($sessionKey);
        if (strlen($iv) != 24) // -41002（长度错误）
            throw new Exception("[41002] session_key Wrong length");

        $aesIV = base64_decode($iv);
        $aesCipher = base64_decode($encryptedData);
        $result = openssl_decrypt($aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);
        $dataObj = json_decode($result, true);

        if(!$dataObj) // -41003（数据为空）
            throw new Exception("[41003] Encrypted data is empty");

        if($dataObj['watermark']['appid'] != $appid) // -41003（appid不匹配）
            throw new Exception("[41003] Appid does not match");

        return $result;
    }

}
