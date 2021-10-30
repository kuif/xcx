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
    // 调用的接口版本
    private static $apiVersion = '1.0';

    // 商户生成签名字符串所使用的签名算法类型，目前支持RSA2和RSA，推荐使用RSA2
    private static $signType = 'RSA2';

    // 请求使用的编码格式
    private static $charset='utf-8';

    // 	仅支持JSON
    private static $format='json';

    // 支付宝网关
    private static $gatewayUrl = 'https://openapi.alipay.com/gateway.do';
    // private static $token = 'https://api.weixin.qq.com/cgi-bin/token';

    // 换取授权访问令牌接口
    private static $tokenMethod = 'alipay.system.oauth.token';

    // 小程序发送模板消息接口
    private static $sendMethod = 'alipay.open.app.mini.templatemessage.send';

    // 小程序生成推广二维码接口
    private static $qrcodeMethod = 'alipay.open.app.qrcode.create';

    private static $config = array(
        'app_id' => '', // 支付宝分配给开发者的应用ID
        'public_key' => '', // 请填写支付宝公钥，一行字符串
        'private_key' => '', // 请填写开发者私钥去头去尾去回车，一行字符串
        'access_token' => '', // access_token
    );

    /**
     * [__construct 构造函数]
     * @param [type] $config [传递相关配置]
     */
    public function __construct($config=NULL){
        $config && self::$config = $config;
    }

    public static function unified($data)
    {
        empty($data['method']) && die('接口名称缺失');
        $params = [
            'app_id'    => self::$config['app_id'], // 支付宝分配给开发者的应用ID	2014072300007148
            // 'method'    => '', // 接口名称
            'format'    => self::$format, // 仅支持JSON	JSON
            'charset'   => self::$charset, // 请求使用的编码格式，如utf-8,gbk,gb2312等	utf-8
            'sign_type' => self::$signType, // 商户生成签名字符串所使用的签名算法类型，目前支持RSA2和RSA，推荐使用RSA2	RSA2
            // 'sign'      => '', // 商户请求参数的签名串，详见签名	详见示例
            'timestamp' => date('Y-m-d H:i:s'), // 发送请求的时间，格式"yyyy-MM-dd HH:mm:ss"	2014-07-24 03:07:50
            'version'   => self::$apiVersion, // 调用的接口版本，固定为：1.0	1.0
            'app_auth_token' => '', // 详见应用授权概述
        ];
        $params = array_merge($data, $params);
        $params['sign'] = self::makeSign($params);

        $response = Http::get(self::$gatewayUrl, $params);
        strtolower(self::$charset) == 'gbk' && $response = mb_convert_encoding($response, "utf8", "gbk");

        $result = json_decode($response, true);
        return $result;
    }

    /**
     * [token 获取小程序用户user_id及access_token]
     * @param  [type] $code          [code]
     * @param  string $refresh_token [description]
     * @return [type]                [description]
     */
    public static function token($code, $refresh_token='')
    {
        $params = [
            'method'        => self::$tokenMethod, // 接口名称
            'grant_type'    => $refresh_token ? 'refresh_token' : 'authorization_code', // authorization_code时，用code换取；refresh_token时，用refresh_token换取
            'code'          => $code, // 授权码，用户对应用授权后得到。
            'refresh_token' => $refresh_token, // 刷新令牌，上次换取访问令牌时得到。见出参的refresh_token字段
        ];
        $result = self::unified($params);
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
        $bizContent = [
            'to_user_id'        => $user_id, // 接收模板消息的用户 user_id
            'user_template_id'  => $template_id, // 消息模板ID
            'page'              => $page, // 小程序的跳转页面
            'data'              => $dataArr, // 模板消息内容
        ];
        // form_id:支付消息模板（trade_no）;表单提交模板（表单号）;刷脸消息模板（ftoken）;说明：订阅消息模板无需传入本参数。
        $form_id && $bizContent['form_id'] = $form_id;
        $params = [
            'method'        => self::$sendMethod, // 接口名称
            'biz_content' => json_encode($bizContent),
        ];
        $result = self::unified($params);
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
        $params = [
            'method'        => self::$qrcodeMethod, // 接口名称
            'biz_content' => json_encode([ // 请求参数的集合，最大长度不限，除公共参数外所有请求参数都必须放在这个参数中传递，具体参照各产品快速接入文档
                'url_param'     => $url, // 小程序中能访问到的页面路径
                'query_param'   => $query, // 小程序的启动参数，打开小程序的query ，在小程序 onLaunch的方法中获取
                'describe'      => $describe, // 对应的二维码描述	二维码描述
            ]),
        ];
        $result = self::unified($params);
        return $result;
    }

    /**
     * [makeSign 生成签名]
     * 本方法不覆盖sign成员变量，如要设置签名需要调用SetSign方法赋值
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    public static function makeSign($data)
    {
        // 去空
        $data = array_filter($data);
        //签名步骤一：按字典序排序参数
        ksort($data);
        //签名步骤二：使用 & = 拼接成字符串
        $content = http_build_query($data);
        $content = urldecode($content);

        $config = self::$config;
        $priKey = $config['private_key'];
        $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($priKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";
        ($res) or die('您使用的私钥格式错误，请检查RSA私钥配置');

        //签名步骤三：openssl_sign生成签名
        openssl_sign($content, $sign, $res, OPENSSL_ALGO_SHA256);

        //签名步骤四：base64编码
        $sign = base64_encode($sign);
        // $sign = urlencode($sign);
        return $sign;
    }

}
