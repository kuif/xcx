<?php
/**
 * @Author: [FENG] <1161634940@qq.com>
 * @Date:   2021-10-27 12:53:48
 * @Last Modified by:   [FENG] <1161634940@qq.com>
 * @Last Modified time: 2021-10-27 15:30:19
 */
namespace fengkui\Xcx;

use app\common\controller\Api;

use Exception;
use fengkui\Supports\Http;

/**
 * 钉钉小程序
 */
class Dingtalk
{
    // 获取小程序全局唯一后台接口调用凭据（access_token）
    private static $gettokenUrl = 'https://oapi.dingtalk.com/gettoken';
    // 获取用户基本信息
    private static $getUserInfoUrl = 'https://oapi.dingtalk.com/user/getuserinfo';
    // 获取用户信息
    private static $userGetUrl = 'https://oapi.dingtalk.com/user/get';
    // 发送工作通知
    private static $asyncSendUrl = 'https://oapi.dingtalk.com/topapi/message/corpconversation/asyncsend_v2';
    // 获取工作通知结果
    private static $getSendResultUrl = 'https://oapi.dingtalk.com/topapi/message/corpconversation/getsendresult';

    private static $config = array(
        'agentid'   => '', // agentid
        'appkey'    => '', // appkey
        'secret'    => '', // secret
        'robot_appkey'    => '', // robot_appkey
        'robot_secret'    => '', // robot_secret
    );

    /**
     * [__construct 构造函数]
     * @param [type] $config [传递相关配置]
     */
    public function __construct($config=NULL){
        $config && self::$config = $config;
    }

    /**
     * [getUser 根据userid获取用户详细信息]
     * @param  [type] $userid [description]
     * @return [type]         [description]
     */
    public static function userInfo($userid)
    {
        $params = [
            'access_token' => self::getAccessToken(),
            'userid' => $userid,
        ];

        $response = Http::get(self::$userGetUrl, $params);
        $result = json_decode($response, true);

        return $result;
    }

    /**
     * [userid 根据code获取用户userid]
     * @param  string $code [code]
     * @return [type]       [description]
     */
    public function userid($code)
    {
        $params = [
            'access_token' => self::getAccessToken(),
            'code' => $code,
        ];

        $response = Http::get(self::$getUserInfoUrl, $params);
        $result = json_decode($response, true);

        return $result;
    }

    /**
     * [asyncSend 发送工作通知]
     * @param  string $userid [description]
     * @param  array  $data   [description]
     * @param  string $path   [description]
     * @return [type]         [description]
     */
    public static function asyncSend($userid, $data = [], $path = 'pages/index/index')
    {
        $title = $data['title'];
        unset($data['title']);
        $form = [];
        foreach ($data as $key => $value) {
            $form[] = ['key' => $key . '：', 'value' => $value];
        }

        $url = self::$asyncSendUrl . '?access_token=' . self::getAccessToken();
        $params = [
            'agent_id'  => self::$config['agentid'],
            'userid_list' => $userid,
            'msg' => json_encode([
                "msgtype" => "oa",
                "oa" => [
                    "message_url" => strstr($path, 'pages') ? "eapp://" . ltrim($path, '/') : $path,
                    "head" => [
                        "text" => $title
                    ],
                    "body" => [
                        "title" => $title,
                        "form" => $form
                    ]
                ]
            ])
        ];

        $response = Http::post($url, $params);
        $result = json_decode($response, true);

        return $result;
    }

    /**
     * [getSendResult 获取工作通知消息的发送结果]
     * @param  string $taskid [发送消息时钉钉返回的任务ID]
     * @return [type]         [description]
     */
    public function getSendResult($taskid = '')
    {
        self::getAccessToken();
        $url = self::$getSendResultUrl . '?access_token=' . self::getAccessToken();

        $params = [
            'agent_id' => self::$config['agentid'],
            'task_id'  => $taskid,
        ];

        $response = Http::post($url, $params);
        $result = json_decode($response, true);

        return $result;
    }

    /**
     * [accessToken 获取 access_token]
     * @param  string  $type   [类型 dingtalk 钉钉  robot 钉钉机器人 ]
     * @return [type]          [description]
     */
    public static function accessToken($type = 'dingtalk')
    {
        $config = self::$config;

        $params = [
            'appkey' => $type == 'robot' ? $config['robot_appkey'] : $config['appkey'],
            'appsecret' => $type == 'robot' ? $config['robot_secret'] : $config['secret'],
        ];

        $response = Http::get(self::$tokenUrl, $params);
        $result = json_decode($response, true);
        return $result;
    }

    // 获取access_token
    public static function getAccessToken($type = 'dingtalk')
    {
        if (empty(self::$config['access_token'])) {
            $access_token = self::accessToken($type);
            $access_token = $access_token['access_token'];
        } else {
            $access_token = self::$config['access_token'];
        }
        return $access_token;
    }
}
