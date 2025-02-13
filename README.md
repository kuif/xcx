<h1 align="center">Xcx</h1>

[![Latest Stable Version](https://poser.pugx.org/fengkui/xcx/v)](//packagist.org/packages/fengkui/xcx) [![Total Downloads](https://poser.pugx.org/fengkui/xcx/downloads)](//packagist.org/packages/fengkui/xcx) [![Latest Unstable Version](https://poser.pugx.org/fengkui/xcx/v/unstable)](//packagist.org/packages/fengkui/xcx) [![License](https://poser.pugx.org/fengkui/xcx/license)](//packagist.org/packages/fengkui/xcx)

开发了多次小程序，每次都要翻文档、找之前的项目复制过来，费时费事，  
为了便于小程序的开发，干脆自己去造轮子，整合小程序（微信、QQ、百度、字节跳动、钉钉、支付宝）相关开发。

**！！请先熟悉 相关小程序 说明文档！！请具有基本的 debug 能力！！**

欢迎 Star，欢迎 PR！

## 特点
- 丰富的扩展，支持微信、QQ、百度、字节跳动、钉钉、支付宝小程序
- 符合 PSR 标准，方便的与你的框架集成
- 文件结构清晰，每个类单独封装扩展，便于单独使用

## 运行环境
- PHP 7.0+
- composer

## 使用文档
- [https://docs.fengkui.net/xcx/](https://docs.fengkui.net/xcx/)

## 支持的小程序
### 1、微信（Wechat）

|  method  |  描述  |
|  :------:  |  :------:  |
|  openid  |  获取小程序 openid  |
|  userPhone  |  获取用户手机号  |
|  accessToken  |  获取 access_token  |
|  send  | 微信小程序发送订阅消息  |
|  qrcode  | 获取小程序码或小程序二维码，图片 Buffer  |
|  decrypt  | 检验数据的真实性，并且获取解密后的明文  |
|  request  | 同城配送，封装加密请求  |

### 2、QQ（QQ）

|  method  |  描述  |
|  :------:  |  :------:  |
|  openid  |  获取小程序 openid  |
|  accessToken  |  获取 access_token  |
|  send  | 小程序发送订阅消息  |
|  qrcode  | 获取小程序二维码，图片 Buffer  |
|  decrypt  | 检验数据的真实性，并且获取解密后的明文  |

### 3、百度（Baidu）

|  method  |  描述  |
|  :------:  |  :------:  |
|  openid  |  获取小程序 openid  |
|  accessToken  |  获取 access_token  |
|  send  | 小程序发送订阅消息  |
|  qrcode  | 获取小程序二维码，图片 Buffer  |
|  decrypt  | 检验数据的真实性，并且获取解密后的明文  |

### 4、字节跳动（Bytedance）

|  method  |  描述  |
|  :------:  |  :------:  |
|  openid  |  获取小程序 openid  |
|  accessToken  |  获取 access_token  |
|  send  | 小程序发送订阅消息  |
|  qrcode  | 获取小程序二维码，图片 Buffer  |
|  decrypt  | 检验数据的真实性，并且获取解密后的明文  |

### 5、钉钉（Dingtalk）
|  method  |  描述  |
|  :------:  |  :------:  |
|  userid  |  获取userid  |
|  accessToken  | 获取 access_token  |
|  userInfo  | 获取用户信息  |
|  asyncSend  | 发送工作通知  |

### 6、支付宝（Alipay）
|  method  |  描述  |
|  :------:  |  :------:  |
|  token  |  获取小程序用户user_id及access_token  |
|  userInfo  | 获取用户信息  |
|  send  | 小程序发送模板消息  |
|  qrcode  | 小程序推广码，链接地址  |

## 安装
```shell
composer require fengkui/xcx
```

## 完善相关配置
```php
# 微信小程序配置
$wechatConfig = [
    'appid' => '',
    'secret' => '',
];
# QQ小程序配置
$qqConfig = [
    'appid' => '',
    'secret' => '',
];
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
# 钉钉小程序配置
$dingtalkConfig = [
    'agentid'   => '', // agentid
    'appkey'    => '', // appkey
    'secret'    => '', // secret
    'robot_appkey'    => '', // robot_appkey
    'robot_secret'    => '', // robot_secret
];
# 支付宝小程序配置
$alipayConfig = [
    'app_id' => '', // 支付宝分配给开发者的应用ID
    'public_key' => '', // 请填写支付宝公钥
    'private_key' => '', // 请填写开发者私钥去头去尾去回车
];
```

## 使用说明

### 单独使用
```php
$xcx = new \fengkui\Xcx\Wechat($wechatConfig); // 微信
$xcx = new \fengkui\Xcx\Qq($qqConfig); // QQ
$xcx = new \fengkui\Xcx\Baidu($baiduConfig); // 百度
$xcx = new \fengkui\Xcx\Bytedance($bytedanceConfig); // 字节跳动
$xcx = new \fengkui\Xcx\Dingtalk($dingtalkConfig); // 钉钉
$xcx = new \fengkui\Xcx\Alipay($alipayConfig); // 支付宝
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

        if (in_array($type, ['wechat', 'qq', 'baidu', 'bytedance', 'dingtalk', 'alipay'])) {
            $config = $type . "Config";
            self::$config = $config;
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
}
```

## 一起喝可乐
<div style="text-align:center">
    <img src="https://raw.githubusercontent.com/kuif/common/master/images/support.jpg" style="width:500px"/>
</div>

**请备注一起喝可乐，以便感谢支持**

## LICENSE
MIT
