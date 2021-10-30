<?php
/**
 * @Author: [FENG] <1161634940@qq.com>
 * @Date:   2020-10-13 17:11:17
 * @Last Modified by:   [FENG] <1161634940@qq.com>
 * @Last Modified time: 2021-05-23T14:32:59+08:00
 */
namespace fengkui\Xcx;

use Exception;
use fengkui\Supports\Http;

/**
 * 微信小程序
 */
class Wechat
{
    // 登录凭证校验
    private static $jscode2sessionUrl = 'https://api.weixin.qq.com/sns/jscode2session';
    // 获取小程序全局唯一后台接口调用凭据（access_token）
    private static $tokenUrl = 'https://api.weixin.qq.com/cgi-bin/token';
    // 发送订阅消息
    private static $sendUrl = 'https://api.weixin.qq.com/cgi-bin/message/subscribe/send';
    // 下发小程序和公众号统一的服务消息
    private static $uniformSendUrl = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/uniform_send';
    // 获取小程序二维码
    private static $createwxaqrcodeUrl = 'https://api.weixin.qq.com/cgi-bin/wxaapp/createwxaqrcode';
    // 获取小程序码
    private static $getwxacodeUrl = 'https://api.weixin.qq.com/wxa/getwxacode';
    // 获取小程序码
    private static $getwxacodeunlimitUrl = 'https://api.weixin.qq.com/wxa/getwxacodeunlimit';

    private static $config = array(
        'appid'     => '', // appid
        'secret'    => '', // secret
        'access_token' => '', // access_token
    );

    /**
     * [__construct 构造函数]
     * @param [type] $config [传递相关配置]
     */
    public function __construct($config=NULL){
        $config && self::$config = $config;
    }

    /**
     * [openid 获取小程序 openid]
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
     * @return [type]              [description]
     */
    public static function send($openid, $template_id, $data=[], $page='pages/index/index')
    {
        $access_token = self::getAccessToken();
        $sendUrl = self::$sendUrl . "?access_token=" . $access_token;

        $dataArr = [];
        foreach ($data as $key => $value) {
            $dataArr[$key] = array('value'=>$value);
        }
        $postData = array(
            'access_token'  => $access_token,
            'touser'        => $openid, // 用户openid
            'template_id'   => $template_id, // 消息模板ID
            'page'          => $page, // 小程序跳转页面
            'data'          => $dataArr, // 模板内容
        );

        $response = Http::post($sendUrl, json_encode($postData), ['Content-Type: application/json']);
        $result = json_decode($response, true);
        return $result;
    }

    /**
     * [uniformSend 下发小程序和公众号统一的服务消息]
     * @param  [type] $openid      [用户openid]
     * @param  [type] $template_id [订阅消息模板ID]
     * @param  array  $data        [发送数据]
     * @param  string $page        [打开页面]
     * @param  string $id          [form_id 或 公众号appid]
     * @param  string $keyword     [小程序模板放大关键词（区分使用小程序模板还是公众号模板）]
     * @return [type]              [description]
     */
    public static function uniformSend($openid, $template_id, $data=[], $page='pages/index/index', $id='', $keyword="")
    {
        $access_token = self::getAccessToken();
        $sendUrl = self::$uniformSendUrl . "?access_token=" . $access_token;

        $postData = array(
            'access_token'          => $access_token,
            'touser'                => $openid, // 用户openid
        );

        // 处理小程序模板数据
        $dataArr = [];
        foreach ($data as $key => $value) {
            $dataArr[$key] = array('value'=>$value);
        }

        if ($keyword && isset($data[$keyword])) {
            // 小程序模板消息相关的信息
            $postData['weapp_template_msgv'] = array(
                'template_id' => $template_id, // 小程序模板ID
                'page' => $page, // 小程序页面路径
                'form_id' => $id, // 小程序模板消息formid
                'data' => $dataArr, // 小程序模板数据
                'emphasis_keyword' => $keyword, // 小程序模板放大关键词
            );
        }

        // 公众号模板消息相关的信息
        $postData['mp_template_msg'] = array(
            'appid'         => $id, // 公众号appid，要求与小程序有绑定且同主体
            'template_id'   => $template_id, // 公众号模板id
            'url'           => $page, // 公众号模板消息所要跳转的url
            'miniprogram'   => [
                'appid'     => self::$config['appid'],
                'pagepath'  => $page,
                ], // 公众号模板消息所要跳转的小程序，小程序的必须与公众号具有绑定关系
            'data'          => $dataArr, // 公众号模板消息的数据
        );

        $response = Http::post($sendUrl, json_encode($postData), ['Content-Type: application/json']);
        $result = json_decode($response, true);
        return $result;
    }

    /**
     * [qrcode 获取小程序码或小程序二维码，图片 Buffer]
     * @param  [type]  $path       [小程序页面路径]
     * @param  integer $width      [小程序码宽度 px (默认430)]
     * @param  integer $type       [获取类型 1:createwxaqrcode 2:getwxacode 3:getwxacodeunlimit  (默认2)]
     * @param  boolean $is_hyaline [是否需要透明底色 (默认true)]
     * @return [type]              [description]
     */
    public static function qrcode($path, $width = 430, $type=2, $is_hyaline=true)
    {
        $access_token = self::getAccessToken();
    	$params = array(
    		'path'    => $path, // 扫码进入的小程序页面路径
    		'width'   => $width, // 二维码的宽度，单位 px。
    	);
    	$postUrl = self::$createwxaqrcodeUrl . "?access_token=" . $access_token;

    	if ($type == 2) {
    		$postUrl = self::$getwxacodeUrl . "?access_token=" . $access_token;
    		$params['auto_color'] = false; // 自动配置线条颜色
    		$params['is_hyaline'] = $is_hyaline; // 是否需要透明底色
    	}
    	if ($type == 3) {
            $postUrl = self::$getwxacodeunlimitUrl . "?access_token=" . $access_token;
            unset($params['path']);
            $page = explode('?', $path);
            $params['page'] = isset($page[0]) ? $page[0] : $path; // 扫码进入的小程序页面路径
            $params['scene'] = isset($page[1]) ? $page[1] : '1=1'; // 扫码进入的小程序携带参数

            $params['auto_color'] = false; // 自动配置线条颜色
            $params['is_hyaline'] = $is_hyaline; // 是否需要透明底色
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
