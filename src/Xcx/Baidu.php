<?php
/**
 * @Author: [FENG] <1161634940@qq.com>
 * @Date:   2020-10-13 17:11:17
 * @Last Modified by:   [FENG] <1161634940@qq.com>
 * @Last Modified time: 2020-12-15T18:27:37+08:00
 */
namespace fengkui\Xcx;

use Exception;
use fengkui\Supports\Http;

/**
 * 百度小程序
 */
class Baidu
{
    // 获取 Session Key 的 URL 地址
    private static $jscode2sessionUrl = 'https://spapi.baidu.com/oauth/jscode2sessionkey';
    // 获取小程序全局唯一后台接口调用凭据（access_token）
    private static $tokenUrl = 'https://openapi.baidu.com/oauth/2.0/token';
    // 推送模板消息
    private static $sendUrl = 'https://openapi.baidu.com/rest/2.0/smartapp/template/send';
    // 二维码短链
    private static $getUrl = 'https://openapi.baidu.com/rest/2.0/smartapp/qrcode/get';
    // 二维码长链
    private static $getunlimitedUrl = 'https://openapi.baidu.com/rest/2.0/smartapp/qrcode/getunlimited';

    private static $config = array(
        'appid' => '', // appid
        'appkey' => '', // appkey
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
            'client_id' => self::$config['appkey'],
            'sk'        => self::$config['secret'],
            'code'      => $code,
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
            'client_id' => self::$config['appkey'],
            'client_secret' => self::$config['secret'],
            'grant_type' => 'client_credentials',
            'scop' => 'smartapp_snsapi_base'
        ];

        $response = Http::get(self::$tokenUrl, $params);
        $result = json_decode($response, true);
        return $result;
    }

    // 获取access_token
    public static function getAccessToken()
    {
        if (empty(self::$config['access_token'])) {
            $access_token = self::accessToken();
            $access_token = $access_token['access_token'];
        } else {
            $access_token = self::$config['access_token'];
        }
        return $access_token;
    }

    /**
     * [send 微信小程序发送订阅消息]
     * @param  [type] $openid      [用户openid]
     * @param  string $template_id [订阅消息模板ID]
     * @param  array  $data        [发送数据]
     * @param  string $page        [打开页面]
     * @param  string $id          [场景 id ，例如表单 Id 、 orderId 或 payId]
     * @param  string $type        [场景 type ，1：表单；2：百度收银台订单；3：直连订单。]
     * @return [type]              [description]
     */
    public static function send($openid, $template_id, $data=[], $page='pages/index/index', $id='', $type=1)
    {
        $access_token = self::getAccessToken();
        $sendUrl = self::$sendUrl . "?access_token=" . $access_token;

        $dataArr = [];
        foreach ($data as $key => $value) {
            $dataArr[$key] = array('value'=>$value);
        }
        $postData = array(
            'template_id'   => $template_id, // 所需下发的模板消息的 id
            // 'touser' => '', // 接收者 swan_id (touser touser_openId两个参数不能都为空)
            'touser_openId' => $openid, // 接收者 open_id (touser touser_openId两个参数不能都为空)
            'data'          => $dataArr, // 模板内容
            'page'          => $page, // 小程序跳转页面（示例 index?foo=bar），该字段不填则模板无跳转
            'scene_id'      => $id, // 场景 id ，例如表单 Id 、 orderId 或 payId 。
            'scene_type'    => $type, // 场景 type ，1：表单；2：百度收银台订单；3：直连订单。
            // 'ext' => '', // {"xzh_id":111,"category_id":15}。
        );

        $response = Http::post($sendUrl, json_encode($postData), ['Content-Type: application/json']);
        $result = json_decode($response, true);
        if ($result['errno'] == 0)
            return $result;
        throw new Exception("[" . $result['errno'] . "] " . $result['msg']);
    }

    /**
     * [qrcode 获取小程序二维码，图片 Buffer]
     * @param  [type]  $path  [小程序页面路径]
     * @param  integer $width [小程序码宽度 px (默认430)]
     * @param  integer $type  [获取类型 1:二维码短链 2:二维码长链  (默认1)]
     * @param  boolean $mf    [是否包含logo 默认包含]
     * @return [type]         [description]
     */
    public static function qrcode($path, $width = 430, $type=1, $mf=true)
    {
        $access_token = self::getAccessToken();
    	$params = array(
    		'path' => $path, // 扫码进入的小程序页面路径
    		'width' => $width, // 二维码的宽度，单位 px。
            'mf' => $mf ? 1 : 1001, // 是否包含二维码内嵌 logo 标识, 1001 为不包含，默认包含
    	);
    	$postUrl = self::$getUrl . "?access_token=" . $access_token;

    	if ($type == 2) {
    		$postUrl = self::$getunlimitedUrl . "?access_token=" . $access_token;
    	}

    	$response = Http::post($postUrl, json_encode($params), array('Content-Type: application/json'));
        $result = json_decode($response, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            return $response;
        } else {
            throw new Exception("[" . $result['errcode'] . "] " . $result['errmsg']);
        }
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
