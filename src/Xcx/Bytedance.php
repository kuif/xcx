<?php
/**
 * @Author: [FENG] <1161634940@qq.com>
 * @Date:   2020-10-13 17:11:17
 * @Last Modified by:   [FENG] <1161634940@qq.com>
 * @Last Modified time: 2021-05-23T14:33:09+08:00
 */
namespace fengkui\Xcx;

use Exception;
use fengkui\Supports\Http;

/**
 * 字节跳动小程序
 */
class Bytedance
{
    // 通过服务器发送请求的方式获取 session_key 和 openId
    private static $jscode2sessionUrl = 'https://developer.toutiao.com/api/apps/jscode2session';
    // 获取 access_token
    private static $tokenUrl = 'https://developer.toutiao.com/api/apps/token';
    // 订阅消息推送
    private static $sendUrl = 'https://developer.toutiao.com/api/apps/subscribe_notification/developer/v1/notify';
    // 内容安全检测
    private static $antidirtUrl = 'https://developer.toutiao.com/api/v2/tags/text/antidirt';
    // 获取二维码
    private static $qrcodeUrl = 'https://developer.toutiao.com/api/apps/qrcode';

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
            'access_token'  => $access_token, // 小程序 access_token，参考登录凭证检验
            'app_id'        => self::$config['appid'], // 小程序的 id
            'tpl_id'        => $template_id, // 模板的 id，参考订阅消息能力
            'open_id'       => $openid, // 接收消息目标用户的 open_id，参考code2session
            'data'          => $dataArr, // 用于填充模板的关键词数据
            'page'          => $page, // 跳转的页面
        );

        $response = Http::post($sendUrl, json_encode($postData), ['Content-Type: application/json']);
        $result = json_decode($response, true);
        return $result;
    }

    /**
     * [qrcode 获取小程序二维码，图片 Buffer]
     * @param  [type]  $path  [小程序页面路径]
     * @param  integer $width [小程序码宽度 px (默认430)]
     * @param  integer $type  [获取类型 1:今日头条 2:抖音 3:皮皮虾 4:火山小视频  (默认1)]
     * @param  boolean $icon  [是否包含logo 默认包含]
     * @return [type]         [description]
     */
    public static function qrcode($path, $width = 430, $type=1, $icon=true)
    {
        $access_token = self::getAccessToken();
        $appname = array( 1 => 'toutiao', 2 => 'douyin', 3 => 'pipixia', 4 => 'huoshan' );

    	$params = array(
            'access_token'  => $access_token,
            'appname'       => isset($appname[$type]) ? $appname[$type] : 'toutiao',
            'path'          => $path, // 扫码进入的小程序页面路径
            'width'         => $width, // 二维码的宽度，单位 px。
            // 'line_color'    => '{"r":0,"g":0,"b":0}', // 自动配置线条颜色
            // 'background'    => '', // 是否需要透明底色
            'set_icon'      => $icon, //  是否展示小程序/小游戏 icon，默认不展示
    	);
    	$postUrl = self::$qrcodeUrl;

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
            return false;
        }
    }

}
