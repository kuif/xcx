<h1 align="center">Xcx</h1>

[![Latest Stable Version](https://poser.pugx.org/fengkui/xcx/v)](//packagist.org/packages/fengkui/xcx) [![Total Downloads](https://poser.pugx.org/fengkui/xcx/downloads)](//packagist.org/packages/fengkui/xcx) [![Latest Unstable Version](https://poser.pugx.org/fengkui/xcx/v/unstable)](//packagist.org/packages/fengkui/xcx) [![License](https://poser.pugx.org/fengkui/xcx/license)](//packagist.org/packages/fengkui/xcx)

开发了多次小程序，每次都要翻文档、找之前的项目复制过来，费时费事，为了便于小程序的开发，干脆自己去造轮子，整合小程序（微信、QQ、百度、字节跳动）相关开发。

**！！请先熟悉 相关小程序 说明文档！！请具有基本的 debug 能力！！**

欢迎 Star，欢迎 PR！

## 特点
- 丰富的扩展，支持微信、QQ、百度、字节跳动、支付宝（待完善）小程序
- 符合 PSR 标准，方便的与你的框架集成
- 文件结构清晰，每个类单独封装扩展，便于单独使用

## 运行环境
- PHP 7.0+
- composer

## 支持的小程序
### 1、微信（Wechat）

|  method  |  描述  |
| :-------: | :-------:   |
|  openid  |  获取小程序 openid  |
|  accessToken  |  获取 access_token  |
|  send  | 微信小程序发送订阅消息  |
|  uniformSend  | 下发小程序和公众号统一的服务消息  |
|  qrcode  | 获取小程序码或小程序二维码，图片 Buffer  |
|  decrypt  | 检验数据的真实性，并且获取解密后的明文  |

### 2、QQ（QQ）

|  method  |  描述  |
| :-------: | :-------:   |
|  openid  |  获取小程序 openid  |
|  accessToken  |  获取 access_token  |
|  send  | 小程序发送订阅消息  |
|  qrcode  | 获取小程序码或小程序二维码，图片 Buffer  |
|  decrypt  | 检验数据的真实性，并且获取解密后的明文  |

### 3、百度（Baidu）

|  method  |  描述  |
| :-------: | :-------:   |
|  openid  |  获取小程序 openid  |
|  accessToken  |  获取 access_token  |
|  send  | 小程序发送订阅消息  |
|  qrcode  | 获取小程序码或小程序二维码，图片 Buffer  |
|  decrypt  | 检验数据的真实性，并且获取解密后的明文  |


### 4、字节跳动（Bytedance）

|  method  |  描述  |
| :-------: | :-------:   |
|  openid  |  获取小程序 openid  |
|  accessToken  |  获取 access_token  |
|  send  | 小程序发送订阅消息  |
|  qrcode  | 获取小程序码或小程序二维码，图片 Buffer  |
|  decrypt  | 检验数据的真实性，并且获取解密后的明文  |

### 5、支付宝（Ali）

***待完善***

## 安装
```shell
composer require fengkui/xcx
```

## 完善相关配置
```php
# 百度小程序配置
$baiduConfig = [
    'appid' => '',
    'appkey' => '',
    'secret' => '',
];
# 字节跳动小程序配置
$bytedanceConfig = [
    'appid' => '',
    'secret' => '',
];
# QQ小程序配置
$qqConfig = [
    'appid' => '',
    'secret' => '',
];
# 微信小程序配置
$wechatConfig = [
    'appid' => '',
    'secret' => '',
];
```

## 使用说明

### 单独使用
```php
$xcx = new \fengkui\Xcx\Wechat($wechatConfig); // 微信
$xcx = new \fengkui\Xcx\Qq($qqConfig); // QQ
$xcx = new \fengkui\Xcx\Baidu($baiduConfig); // 百度
$xcx = new \fengkui\Xcx\Bytedance($bytedanceConfig); // 字节跳动
```

### 公共使用
```php
<?php
/**
 * @Author: [FENG] <1161634940@qq.com>
 * @Date:   2021-05-01T14:55:21+08:00
 * @Last Modified by:   [FENG] <1161634940@qq.com>
 * @Last Modified time: 2021-05-30 15:39:01
 */

require_once('./vendor/autoload.php');

/**
 * 通用小程序
 */
class Xcx
{
    // 小程序相关信息获取
    protected static $xcx = '';
    // 小程序类型
    protected static $type = '';
    // 小程序相关配置
    protected static $config = [];

    /**
     * [_initialize 构造函数(获取小程序类型与初始化配置)]
     * @return [type] [description]
     */
    public function _initialize()
    {
        self::$type = $_GET['type'] ?? 'wechat';
        self::config();
    }

    /**
     * [config 获取配置]
     * @param  string $type [description]
     * @return [type]       [description]
     */
    protected static function config($type='')
    {
        $type = $type ?: self::$type;

        // 相关配置
        $wechatConfig = [
            'appid' => '',
            'secret' => '',
        ];

        if (in_array($type, ['wechat', 'qq', 'baidu', 'bytedance'])) {
            $config = $type . "Config";
            self::$config = $$config;
        } else {
            die('当前类型配置不存在');
        }

        $type && self::$xcx =(new \fengkui\Xcx())::$type(self::$config);
    }

    /**
     * [fastLogin 获取openid，快速登录]
     * @return [type] [description]
     */
    public function fastLogin($code=null)
    {
        if(!$code)
            die('参数缺失');

        $data = self::$xcx->openid($code);
        if (empty($data['openid']))
            die('获取数据失败');
    }

    /**
     * [decrypt 检验数据的真实性，并且获取解密后的明文]
     */
    public function decrypt()
    {
        $sessionKey = ''; // session_key 随openid一起获取到的东东
        $encryptedData = ''; // 加密的用户数据
        $iv = ''; // 与用户数据一同返回的初始向量

        if(!$sessionKey || !$encryptedData || !$iv)
            die('参数缺失');

        $re = self::$xcx->decrypt($sessionKey, $encryptedData, $iv);
        if (!$re)
            die('获取数据失败');
    }

    /**
     * [send 发送模板消息]
     */
    public static function send()
    {
        $openid = $openid; // 用户openid
        $template_id = ''; // 模板ID
        $data = []; // 发送消息数据格式
        $page = 'pages/index/index'; // 进入小程序页面

        $re = self::$xcx->send($openid, $template_id, $data, $page);
        if (!$re)
            die('获取数据失败');
    }

    /**
     * [qrcode 获取小程序码]
     */
    public static function qrcode()
    {
        $path = 'pages/index/index'; // 进入小程序页面
        $width = 430; // 小程序码宽度 px (默认430)
        $type = 2; // 获取类型 1:createwxaqrcode 2:getwxacode 3:getwxacodeunlimit  (默认2)
        $is_hyaline = true; // 是否需要透明底色 (默认true)

        $re = self::$xcx->qrcode($path, $width, $type, $is_hyaline);
        if (!$re)
            die('获取数据失败');

        // file_put_contents('qrcode.png', $re); // 直接保存文件

        // 直接显示
        $im = imagecreatefromstring($re);
        if ($im !== false) {
            header('Content-Type: image/png');
            imagepng($im);
            imagedestroy($im);
    }
}
```

## 赏一杯咖啡吧
<center class="half">
    <img src="https://fengkui.net/uploads/images/ali.jpg" width="200px"/><img src="https://fengkui.net/uploads/images/wechat.png" width="200px"/>
</center>

## LICENSE
MIT
