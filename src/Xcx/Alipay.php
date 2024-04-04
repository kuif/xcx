<?php
/**
 * @Author: [FENG] <1161634940@qq.com>
 * @Date:   2020-10-13 17:11:17
 * @Last Modified by:   [FENG] <1161634940@qq.com>
 * @Last Modified time: 2021-06-01T09:32:36+08:00
 */
namespace fengkui\Xcx;

use Exception;
use fengkui\Supports\Http;

/**
 * 支付宝小程序
 */
class Alipay
{
    //沙盒地址
    private static $sandurl = 'https://openapi-sandbox.dl.alipaydev.com/gateway.do';
    //正式地址
    private static $apiurl  = 'https://openapi.alipay.com/gateway.do';
    //网关地址（设置为公有，外部需要调用）
    private static $gateway;
    // 请求使用的编码格式
    private static $charset = 'utf-8';
    //  仅支持JSON
    private static $format='JSON';
    // 调用的接口版本
    private static $version = '1.0';
    // 商户生成签名字符串所使用的签名算法类型，目前支持RSA2和RSA，推荐使用RSA2
    private static $signType = 'RSA2';
    // 订单超时时间
    private static $timeout = '15m';


    private static $config = array(
        'app_id'        => '', // 开发者的应用ID
        'public_key'    => '', // 支付宝公钥，一行字符串
        'private_key'   => '', // 开发者私钥去头去尾去回车，一行字符串

        'sign_type'     => 'RSA2', // 生成签名字符串所使用的签名算法类型，目前支持RSA2和RSA，默认使用RSA2
        'is_sandbox'    => false, // 是否使用沙箱调试，true使用沙箱，false不使用，默认false不使用
    );

    /**
     * [__construct 构造函数]
     * @param [type] $config [传递相关配置]
     */
    public function __construct($config=NULL){
        $config && self::$config = array_merge(self::$config, $config);
        isset(self::$config['sign_type']) && self::$signType = self::$config['sign_type'];
        self::$gateway = !empty(self::$config['is_sandbox']) ? self::$sandurl : self::$apiurl; //请求地址，判断是否使用沙箱，默认不使用
    }

    public static function unified($data, $params, $type=false)
    {
        // 获取配置项
        $config = self::$config;
        //请求参数
        $requestParams = [];
        $requestParams = $type ? $data : array_merge($requestParams, $data);
        //公共参数
        $commonParams = array(
            'app_id'    => $config['app_id'],
            // 'method'    => $params['method'], // 接口名称
            'format'    => self::$format,
            'charset'   => self::$charset,
            'sign_type' => self::$signType,
            'timestamp' => date('Y-m-d H:i:s'),
            'version'   => self::$version,
            'biz_content' => json_encode($requestParams, JSON_UNESCAPED_UNICODE),
        ];
        $commonParams = array_merge($commonParams, $params);
        // dump($commonParams);die;
        $commonParams["sign"] = self::makeSign($commonParams);
        return $commonParams;
    }

    /**
     * 获取access_token和user_id
     */
    public function token($type = true)
    {
        $config = self::$config;
        //通过code获得access_token和user_id
        if (isset($_GET['auth_code'])){
            //获取code码，以获取openid
            $params = array(
                'app_id'    => $config['app_id'],
                'method'    => 'alipay.system.oauth.token', // 接口名称
                'format'    => self::$format,
                'charset'   => self::$charset,
                'sign_type' => self::$signType,
                'timestamp' => date('Y-m-d H:i:s'),
                'version'   => self::$version,
                'grant_type' =>'authorization_code',
                'code'  => $_GET['auth_code'],
            );

            $params["sign"] = self::makeSign($params);
            $response = Http::post(self::$gateway . '?charset=' . self::$charset, $params);
            $result = json_decode($response, true);
            $result = $result['alipay_system_oauth_token_response'];
            if (isset($result['code']) && $result['code'] != 10000) { // 错误抛出异常
                throw new \Exception("[" . $result['code'] . "] " . $result['sub_code']. ' ' . $result['sub_msg']);
            }
            return $result;
        } else {
            //触发返回code码
            $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on' ? 'https://' : 'http://';
            $redirectUrl = urlencode($scheme.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']);
            $_SERVER['QUERY_STRING'] && $redirectUrl = $baseUrl.'?'.$_SERVER['QUERY_STRING'];
            $urlObj['app_id'] = $config['app_id'];
            $urlObj['scope'] = $type ? 'auth_base' : 'auth_user';
            $urlObj['redirect_uri'] = urldecode($redirectUrl);
            $bizString = http_build_query($urlObj);
            $url = 'https://openauth' . ($config['is_sandbox'] ? '-sandbox.dl.alipaydev' : '.alipay') . '.com/oauth2/publicAppAuthorize.htm?' . $bizString;
            Header("Location: $url");
            exit();
        }
    }

    // 获取会员信息
    public static function userInfo($token)
    {
        $params['method'] = 'alipay.user.info.share'; // 接口名称
        $params['auth_token'] = $token; // 接口名称
        $params = self::unifiedOrder([], $params, true);

        $response = Http::post(self::$gateway . '?charset=' . self::$charset, $params);
        $result = json_decode($response, true);
        $result = $result['alipay_user_info_share_response'];
        if (isset($result['code']) && $result['code'] != 10000) { // 错误抛出异常
            throw new \Exception("[" . $result['code'] . "] " . $result['sub_code']. ' ' . $result['sub_msg']);
        }
        return $result;
    }

    /**
     * [send 小程序发送模板消息]
     * @param  [type] $user_id     [接收模板消息的用户 user_id]
     * @param  [type] $template_id [消息模板ID]
     * @param  array  $data        [模板消息内容]
     * @param  string $page        [小程序的跳转页面]
     * @param  string $form_id     [支付消息模板（trade_no）;表单提交模板（表单号）;刷脸消息模板（ftoken）;说明：订阅消息模板无需传入本参数。]
     * @return [type]              [description]
     */
    public static function send($user_id, $template_id, $data=[], $page='pages/index/index', $form_id='')
    {
        $dataArr = [];
        foreach ($data as $key => $value) {
            $dataArr[$key] = array('value'=>$value);
        }
        $data = [
            'to_user_id'        => $user_id, // 接收模板消息的用户 user_id
            'user_template_id'  => $template_id, // 消息模板ID
            'page'              => $page, // 小程序的跳转页面
            'data'              => $dataArr, // 模板消息内容
        ];
        // form_id:支付消息模板（trade_no）;表单提交模板（表单号）;刷脸消息模板（ftoken）;说明：订阅消息模板无需传入本参数。
        $form_id && $data['form_id'] = $form_id;
        $params['method'] = 'alipay.open.app.mini.templatemessage.send'; // 接口名称
        $params = self::unified($data, $params, true);
        $response = Http::get(self::$gateway . '?charset=' . self::$charset, $params);
        $result = json_decode($response, true);
        return $result;
    }

    /**
     * [qrcode 小程序推广码，链接地址]
     * @param  string $url      [页面路径]
     * @param  string $query    [启动参数]
     * @param  string $describe [二维码描述]
     * @return [type]           [description]
     */
    public static function qrcode($url='pages/index/index', $query='x=1', $describe='二维码描述')
    {
        $data = [ // 请求参数的集合，最大长度不限，除公共参数外所有请求参数都必须放在这个参数中传递，具体参照各产品快速接入文档
            'url_param'     => $url, // 小程序中能访问到的页面路径
            'query_param'   => $query, // 小程序的启动参数，打开小程序的query ，在小程序 onLaunch的方法中获取
            'describe'      => $describe, // 对应的二维码描述	二维码描述
        ];
        $params['method'] = 'alipay.open.app.qrcode.create'; // 接口名称

        $params = self::unified($data, $params, true);
        $response = Http::get(self::$gateway . '?charset=' . self::$charset, $params);
        $result = json_decode($response, true);
        return $result;
    }

    // 生成签名
    protected static function makeSign($data) {
        $data = self::getSignContent($data);
        $priKey = self::$config['private_key'];
        $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($priKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";
        ($res) or die('您使用的私钥格式错误，请检查RSA私钥配置');
        if (self::$signType == "RSA2") {
            //OPENSSL_ALGO_SHA256是php5.4.8以上版本才支持
            openssl_sign($data, $sign, $res, version_compare(PHP_VERSION,'5.4.0', '<') ? SHA256 : OPENSSL_ALGO_SHA256);
        } else {
            openssl_sign($data, $sign, $res);
        }
        $sign = base64_encode($sign);
        return $sign;
    }

}
