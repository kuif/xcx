<?php
/**
 * @Author: [FENG] <1161634940@qq.com>
 * @Date:   2020-10-13 17:11:17
 * @Last Modified by:   [FENG] <1161634940@qq.com>
 * @Last Modified time: 2024-01-02T16:47:05+08:00
 */
namespace fengkui\Xcx;

use Exception;
use fengkui\Supports\Http;

/**
 * 微信小程序
 */
class Wechat
{
    // 微信小程序url
    private static $wxaUrl = 'https://api.weixin.qq.com/wxa';
    // 登录凭证校验
    private static $jscode2sessionUrl = 'https://api.weixin.qq.com/sns/jscode2session';
    // 获取小程序全局唯一后台接口调用凭据（access_token）
    private static $stableTokenUrl = 'https://api.weixin.qq.com/cgi-bin/stable_token';
    // 发送订阅消息
    private static $sendUrl = 'https://api.weixin.qq.com/cgi-bin/message/subscribe/send';
    // 下发小程序和公众号统一的服务消息
    private static $uniformSendUrl = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/uniform_send';
    // 获取小程序二维码
    private static $createwxaqrcodeUrl = 'https://api.weixin.qq.com/cgi-bin/wxaapp/createwxaqrcode';

    private static $config = array(
        'appid'     => '', // appid
        'secret'    => '', // secret
        'access_token' => '', // access_token

        'aes_sn'    => '',
        'aes_key'   => '',

        'rsa_sn'    => '',
        'public_key' => '',
        'private_key' => '',

        'cert_sn'   => '',
        'cert_key' => '',
    );

    /**
     * [__construct 构造函数]
     * @param [type] $config [传递相关配置]
     */
    public function __construct($config=NULL){
        $config && self::$config = array_merge(self::$config, $config);
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
     * [userPhone 获取用户手机号]
     * @param  string $code [code]
     * @return [type]       [description]
     */
    public static function userPhone($code)
    {
        $access_token = self::getAccessToken();
        $postUrl = self::$wxaUrl . '/business/getuserphonenumber' . "?access_token=" . $access_token;

        $postData = array(
            'code'  => $code, // 模板内容
        );

        $response = Http::post($postUrl, json_encode($postData, JSON_UNESCAPED_UNICODE), ['Content-Type: application/json']);
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

        $response = Http::post(self::$stableTokenUrl, json_encode($params, JSON_UNESCAPED_UNICODE), ['Content-Type: application/json']);
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

        $response = Http::post($sendUrl, json_encode($postData, JSON_UNESCAPED_UNICODE), ['Content-Type: application/json']);
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

        $response = Http::post($sendUrl, json_encode($postData, JSON_UNESCAPED_UNICODE), ['Content-Type: application/json']);
        $result = json_decode($response, true);
        return $result;
    }

    /**
     * [qrcode
     * @param  [type]  $path       [小程序页面路径]
     * @param  integer $width      []
     * @param  integer $type       [
     * @param  boolean $is_hyaline []
     * @return [type]              [description]
     */

    /**
     * [qrcode 获取小程序码或小程序二维码，图片 Buffer]
     * @param  [type]  $path        [小程序页面路径]
     * @param  integer $type        [获取类型 1:createwxaqrcode 2(默认):getwxacode 3:getwxacodeunlimit]
     * @param  string  $env_version [要打开的小程序版本。正式版为 release(默认) 体验版为 trial 开发版为 develop]
     * @param  integer $width       [小程序码宽度 px (默认430)]
     * @param  boolean $is_hyaline  [是否需要透明底色 (默认true)]
     * @return [type]               [description]
     */
    public static function qrcode($path, $type=2, $env_version = 'release', $width = 430,  $is_hyaline=true)
    {
        $access_token = self::getAccessToken();
    	$params = array(
    		'path'    => $path, // 扫码进入的小程序页面路径
    		'width'   => $width, // 二维码的宽度，单位 px。
    	);
    	$postUrl = self::$createwxaqrcodeUrl . "?access_token=" . $access_token;

    	if ($type == 2) {
    		$postUrl = self::$wxaUrl . '/getwxacode' . "?access_token=" . $access_token;
    		$params['auto_color'] = false; // 自动配置线条颜色
    		$params['is_hyaline'] = $is_hyaline; // 是否需要透明底色
    	}
    	if ($type == 3) {
            $postUrl = self::$wxaUrl . '/getwxacodeunlimit' . "?access_token=" . $access_token;
            unset($params['path']);
            $page = explode('?', $path);
            $params['page'] = isset($page[0]) ? $page[0] : $path; // 扫码进入的小程序页面路径
            $params['scene'] = isset($page[1]) ? $page[1] : '1=1'; // 扫码进入的小程序携带参数

            $params['auto_color'] = false; // 自动配置线条颜色
            $params['check_path'] = false; // 不检查页面是否存在
            $params['is_hyaline'] = $is_hyaline; // 是否需要透明底色
    	}

        $params['env_version'] = $env_version;
    	$response = Http::post($postUrl, json_encode($params, JSON_UNESCAPED_UNICODE), array('Content-Type: application/json'));
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
            $postUrl = self::$wxaUrl. '/media_check_async' . "?access_token=" . $access_token;
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
            $postUrl = self::$wxaUrl. '/msg_sec_check' . "?access_token=" . $access_token;
            $params['content'] = $content;
        }
    	$response = Http::post($postUrl, json_encode($params, JSON_UNESCAPED_UNICODE), array('Content-Type: application/json'));
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

    // 封装curl加密请求
    public static function request($url, $req)
    {
        $config = self::$config;
        $time = time();

        if (strstr($url, '?')) {
            $urls = $url;
            $url = mb_substr($url, 0, strpos($url, '?'));
        } else {
            $access_token = self::getAccessToken();
            $urls = $url . "?access_token=" . $access_token;
        }

        // dump($urls);die;

        $nonce = rtrim(base64_encode(random_bytes(16)), '='); // 16位随机字符
        $addReq = ["_n" => $nonce, "_appid" => $config['appid'], "_timestamp" => $time]; // 添加字段
        $realReq = array_merge($addReq, $req);
        $realReq = json_encode($realReq, JSON_UNESCAPED_UNICODE);

        //额外参数
        $message = $url . "|" . $config['appid'] . "|" . $time . "|" . $config['aes_sn'];

        $iv = random_bytes(12); // 12位随机字符
        // 数据加密处理
        $cipher = openssl_encrypt($realReq, "aes-256-gcm", base64_decode($config['aes_key']), OPENSSL_RAW_DATA, $iv, $tag, $message);
        $iv = base64_encode($iv);
        $data = base64_encode($cipher);
        $authTag = base64_encode($tag);
        $reqData = ["iv" => $iv, "data" => $data, "authtag" => $authTag];

        // 获取签名
        $reqData = json_encode($reqData, JSON_UNESCAPED_UNICODE);
        $payload = $url . "\n" . $config["appid"] . "\n" . $time . "\n" . $reqData; // 拼接字符串用双引号
        // 使用phpseclib3\Crypt\RSA（phpseclib V3）版本生成签名
        $private_key = self::getCertFile($config['private_key']);
        if (!class_exists('\phpseclib3\Crypt\RSA'))
            throw new \Exception("composer包phpseclib/phpseclib不存在，请安装后在进行操作");

        $rsa = \phpseclib3\Crypt\RSA::loadPrivateKey($private_key);
        $signature = $rsa->withPadding(\phpseclib3\Crypt\RSA::SIGNATURE_PSS)
                ->withHash('sha256')
                ->withMGFHash('sha256')
                ->sign($payload);
        $signature = base64_encode($signature);
        $header = [
            'Content-Type:application/json;charset=utf-8',
            'Accept:application/json',
            'Wechatmp-Appid:' . $config['appid'],
            'Wechatmp-TimeStamp:' . $time,
            'Wechatmp-Signature:' . $signature
        ];

        // 封装的curl请求 httpRequest($url, $method="GET", $params='', $headers=[], $pem=[], $debug = false, $timeout = 60)
        $response = Http::httpRequest($urls, "POST", $reqData, $header, [], true);
        $result = json_decode($response['response'], true);
        // 请求平台报错
        if (isset($result['errcode']))
            throw new \Exception("[" . $result['errcode'] . "] " . $result['errmsg']);

        // 响应参数验签
        $vertify = self::verifySign($url, $response);
        if (!$vertify)
            throw new \Exception("微信响应接口，验证签名失败");

        // 响应参数解密
        return self::decryptToString($url, $response['response_header']['Wechatmp-TimeStamp'], $result);
    }

    // 获取证书文件
    public static function getCertFile($filePath='')
    {
        $filePath = iconv('utf-8', 'gb2312', $filePath); //对可能出现的中文名称进行转码
        if (file_exists($filePath))
            return file_get_contents($filePath);
        return '';
    }

    // 验证签名
    private static function verifySign($url, $response)
    {
        $config = self::$config;
        $headers = $response['response_header'];
        $reTime = $headers['Wechatmp-TimeStamp'];

        if ($config['appid'] != $headers['Wechatmp-Appid'] || time() - $reTime > 300){
            throw new \ErrorException('返回值安全字段校验失败');
        }
        if ($config['cert_sn'] == $headers['Wechatmp-Serial']) {
            $signature = $headers['Wechatmp-Signature'];
        } elseif (isset($headers['Wechatmp-Serial-Deprecated']) && $config['cert_sn'] == $headers['Wechatmp-Serial-Deprecated']) {
            $signature = $headers['Wechatmp-Signature-Deprecated'];
        } else {
            throw new \ErrorException('返回值sn不匹配');
        }
        $reData = $response['response'];
        $payload = $url . "\n" . $config["appid"] . "\n" . $reTime . "\n" . $reData;
        $payload = utf8_encode($payload);
        $signature = base64_decode($signature);

        $cert_key = self::getCertFile($config['cert_key']);
        $pkey = openssl_pkey_get_public($cert_key);
        $keyData = openssl_pkey_get_details($pkey);
        $public_key = str_replace('-----BEGIN PUBLIC KEY-----', '', $keyData['key']);
        $public_key = trim(str_replace('-----END PUBLIC KEY-----', '', $public_key));

        $rsa = \phpseclib3\Crypt\RSA::loadPublicKey($public_key);
        $recode = $rsa->withPadding(\phpseclib3\Crypt\RSA::SIGNATURE_PSS)
                ->withHash('sha256')
                ->withMGFHash('sha256')
                ->verify($payload, $signature);

        return $recode;
    }

    // 解析加密信息
    private static function decryptToString($url, $ts, $result)
    {
        $config = self::$config;
        $message = $url . '|' . $config['appid'] . '|' . $ts . '|' . $config['aes_sn'];
        $key = base64_decode($config['aes_key']);
        $iv = base64_decode($result['iv']);
        $data = base64_decode($result['data']);
        $authTag = base64_decode($result['authtag']);
        $result = openssl_decrypt($data, "aes-256-gcm", $key, OPENSSL_RAW_DATA, $iv, $authTag, $message);
        if (!$result) {
            throw new Exception('加密字符串使用 aes-256-gcm 解析失败');
        }
        return json_decode($result, true) ?: '';
    }

}
