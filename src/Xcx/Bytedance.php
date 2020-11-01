<?php
/**
 * @Author: [FENG] <1161634940@qq.com>
 * @Date:   2020-10-13 17:11:17
 * @Last Modified by:   [FENG] <1161634940@qq.com>
 * @Last Modified time: 2020-10-31T12:43:13+08:00
 */
namespace fengkui\Xcx;

use Exception;
use fengkui\Supports\Http;

/**
 * 字节跳动小程序
 */
class Bytedance
{
    private static $jscode2sessionUrl = 'https://developer.toutiao.com/api/apps/jscode2session';
    private static $tokenUrl = 'https://developer.toutiao.com/api/apps/token';
    private static $antidirtUrl = 'https://developer.toutiao.com/api/v2/tags/text/antidirt';

    private static $config = array(
        'appid' => '', // appid
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
        $params = [
            'code'  => $code,
            'appid' => self::$config['appid'],
            'secret' => self::$config['secret'],
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

        return $result;
    }

    /**
     * [antidirt 检测文本是否包含违规内容]
     * @param  string $text [待检测的文本]
     * @return [type]       [description]
     */
    public static function antidirt($text='')
    {
        if (!$text)
            throw new Exception("[$text] Filter word is empty");

        $result = self::accessToken();
        $access_token = $result['access_token'];
        $params = '{"tasks": [{"content": "'.$text.'"}]}';

        $response = Http::post(self::$antidirtUrl, $params, ['X-Token: '.$access_token]);
        $result = json_decode($response, true);

        if ($result['data'][0]['predicts'][0]['hit']) {
            // die('包含违规内容，请更换');
            return true;
        } else {
            return true;
        }
    }

}
