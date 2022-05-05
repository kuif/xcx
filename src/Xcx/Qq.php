<?php
/**
 * @Author: [FENG] <1161634940@qq.com>
 * @Date:   2020-10-13 17:11:17
 * @Last Modified by:   [FENG] <1161634940@qq.com>
 * @Last Modified time: 2021-05-23T14:33:03+08:00
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
    // 发送订阅消息
    private static $sendSubscriptionMessageUrl = 'https://api.q.qq.com/api/json/subscribe/SendSubscriptionMessage';
    // 获取小程序码
    private static $createMiniCodeUrl = 'https://api.q.qq.com/api/json/qqa/CreateMiniCode';
    // 检查内容是否违规
    private static $securityUrl = 'https://api.q.qq.com/api/json/security/';

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
     * [send 小程序发送订阅消息]
     * @param  [type] $openid      [用户openid]
     * @param  string $template_id [订阅消息模板ID]
     * @param  array  $data        [发送数据]
     * @param  string $page        [打开页面]
     * @return [type]              [description]
     */
    public static function send($openid, $template_id, $data=[], $page='pages/index/index')
    {
        $access_token = self::getAccessToken();
        $sendUrl = self::$sendSubscriptionMessageUrl . "?access_token=" . $access_token;

        $dataArr = [];
        foreach ($data as $key => $value) {
            $dataArr[$key] = array('value'=>$value);
        }
        $postData = array(
            'access_token'  => $access_token,
            'touser'        => $openid, // 接收者（用户）的 openid
            'template_id'   => $template_id, // 所需下发的订阅消息的模板id
            'page'          => $page, // 点击订阅消息卡片后的跳转页面
            'data'          => $dataArr, // 模板内容

            // 'emphasis_keyword' => '', // 模板需要放大的关键词，不填则默认无放大。
            // 'oac_appid' => '', // 若希望通过小程序绑定的公众号下发，则在该字段填入公众号的 appid
            // 'use_robot' => '', // 若希望通过客服机器人下发，则在该字段填1
        );

        $response = Http::post($sendUrl, json_encode($postData), ['Content-Type: application/json']);
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
    public static function qrcode($path)
    {
        $access_token = self::getAccessToken();
    	$params = array(
    		'access_token' => $access_token, // 接口调用凭证
    		'appid' => self::$config['appid'], // 小程序/小游戏appid
            'path' => $path, // 扫码进入的小程序页面路径
    	);
    	$postUrl = self::$createMiniCodeUrl . "?access_token=" . $access_token;

    	$response = Http::post($postUrl, json_encode($params), array('Content-Type: application/json'));
        $result = json_decode($response, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            return $response;
        } else {
            throw new Exception("[" . $result['errcode'] . "] " . $result['errmsg']);
        }
    }

    /**
     * [check 检查内容是否违规]
     * @param  [type]  $content [内容]
     * @param  [type]  $openid  [用户openid]
     * @param  integer $scene   [场景枚举值（1 资料；2 评论；3 论坛；4 社交日志）]
     * @return [type]           [description]
     */
    public static function check($content, $openid, $scene = 1)
    {
        $access_token = self::getAccessToken();
        $info = pathinfo($content);

        $params = array(
            'openid'    => $openid, // 用户的openid
            'version'   => 2, // 接口版本号，2.0版本为固定值2
            'scene'     => in_array($scene, [1,2,3,4]) ? $scene : 1, // 场景枚举值（1 资料；2 评论；3 论坛；4 社交日志）
        );

        if (isset($info['extension'])) {
            $postUrl = self::$securityUrl. 'ImgSecCheck' . "?access_token=" . $access_token;
            $extension = $info['extension'];
            $imageExtensionArr = ['jpg', 'jepg', 'png', 'bmp', 'gif'];
            $audioExtensionArr = ['mp3', 'aac', 'ac3', 'wma', 'flac', 'vorbis', 'opus', 'wav'];
            $params['media_url'] = $content;
            if (in_array($extension, $imageExtensionArr)) {
                $params['media_type'] = 2;
            } elseif (in_array($extension, $audioExtensionArr)) {
                $params['media_type'] = 1;
            } else {
                throw new Exception("[10000] 当前类型不支持");
            }
        } else {
            $postUrl = self::$securityUrl. 'MsgSecCheck' . "?access_token=" . $access_token;
            $params['content'] = $content;
        }
        $response = Http::post($postUrl, json_encode($params), array('Content-Type: application/json'));
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

        if($dataObj['watermark']['appid'] != $appid) // -41003（appid不匹配）
            throw new Exception("[41003] Appid does not match");

        return $result;
    }

}
