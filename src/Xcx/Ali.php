<?php
/**
 * @Author: [FENG] <1161634940@qq.com>
 * @Date:   2020-10-13 17:11:17
 * @Last Modified by:   [FENG] <1161634940@qq.com>
 * @Last Modified time: 2020-10-31T12:49:39+08:00
 */
namespace fengkui\Xcx;

use Exception;
use fengkui\Supports\Http;

/**
 * 支付宝小程序
 */
class Ali
{
    // 调用的接口版本
    private static $apiVersion = '1.0';

    // 商户生成签名字符串所使用的签名算法类型，目前支持RSA2和RSA，推荐使用RSA2
    private static $signType = 'RSA2';

    // 请求使用的编码格式
    private static $postCharset='GBK';

    // 	仅支持JSON
    private static $format='json';

    private static $gatewayUrl = 'https://openapi.alipay.com/gateway.do';
    // private static $token = 'https://api.weixin.qq.com/cgi-bin/token';

    private static $config = array(
        'app_id' => '', // 支付宝分配给开发者的应用ID
        'public_key' => '', // 请填写支付宝公钥，一行字符串
        'private_key' => '', // 请填写开发者私钥去头去尾去回车，一行字符串
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
    public static function openid($code, $refresh_token='')
    {
        $params = [
            'app_id'    => self::$config['app_id'], // 支付宝分配给开发者的应用ID	2014072300007148
            'method'    => 'mybank.credit.user.system.oauth.query', // 接口名称	mybank.credit.user.system.oauth.query
            'format'    => self::$format, // 仅支持JSON	JSON
            'charset'   => self::$postCharset, // 请求使用的编码格式，如utf-8,gbk,gb2312等	utf-8
            'sign_type' => self::$signType, // 商户生成签名字符串所使用的签名算法类型，目前支持RSA2和RSA，推荐使用RSA2	RSA2
            'sign'      => '', // 商户请求参数的签名串，详见签名	详见示例
            'timestamp' => date('Y-m-d H:i:s'), // 发送请求的时间，格式"yyyy-MM-dd HH:mm:ss"	2014-07-24 03:07:50
            'version'   => self::$apiVersion, // 调用的接口版本，固定为：1.0	1.0
            'app_auth_token' => '', // 详见应用授权概述
            'biz_content' => json_encode([ // 请求参数的集合，最大长度不限，除公共参数外所有请求参数都必须放在这个参数中传递，具体参照各产品快速接入文档
                'grant_type'    => 'authorization_code', // authorization_code时，用code换取；refresh_token时，用refresh_token换取
                'code'          => $code, // 授权码，用户对应用授权后得到。
                'refresh_token' => $refresh_token, // 刷新令牌，上次换取访问令牌时得到。见出参的refresh_token字段
            ]),
        ];


        $response = Http::get(self::$gatewayUrl, $params);
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

        $response = Http::get(self::$token, $params);
        $result = json_decode($response, true);
        return $result;
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

        if( $dataObj->watermark->appid != $appid ) // -41003（appid不匹配）
            throw new Exception("[41003] Appid does not match");

        return $result;
    }

}
