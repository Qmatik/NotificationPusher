<?php

namespace Sly\NotificationPusher\Adapter;

use Exception;
use Illuminate\Support\Facades\Cache;
use N2boost\LaravelHuaweiPush\HuaweiPush;
use Sly\NotificationPusher\Exception\AdapterException;
use Sly\NotificationPusher\Model\PushInterface;

class Hms extends BaseAdapter
{
    protected const CACHE_TOKEN_NAME = 'hms_token';

    public function push(PushInterface $push)
    {
        $huaweiPush = new HuaweiPush();

        $pushMessage = $push->getMessage();
        $pushOptions = $pushMessage->getOptions();

        $accessToken = $this->getAccessTokenUseCache($huaweiPush);

        $huaweiPush = $huaweiPush->setTitle($pushOptions['title'])
            ->setMessage($pushOptions['body'])
            ->setAccessToken($accessToken)
            ->setCustomize($pushOptions['custom']);

        foreach ($push->getDevices()->getTokens() as $token) {
            $huaweiPush->addDeviceToken($token);
        }

        $this->response = $huaweiPush->sendMessage(); // 执行推送消息。

        if($huaweiPush->isSendFail()) {
            $responseContent = $this->response->getResponseArray();
            throw new AdapterException($responseContent['msg'], $responseContent['code']);
        }
        /*sdump($huaweiPush->isSendSuccess()); // 是否推送成功
        dump($huaweiPush->isSendFail()); // 是否推送失败
        dump($huaweiPush->getAccessTokenExpiresTime()); // 获取 AccessToken 过期时间
        dump($huaweiPush->getSendSuccessRequestId()); // 获取推送成功后接口返回的请求 id */
    }

    public function supports($token)
    {
        return is_string($token) && $token != '';
    }

    public function getDefinedParameters()
    {
        return [];
    }

    public function getDefaultParameters()
    {
        return [];
    }

    public function getRequiredParameters()
    {
        return ['appId', 'appSecret', 'appPkgName'];
    }

    /**
     * Кешировать токен
     * @param HuaweiPush $push
     * @return string
     * @throws Exception
     */
    protected function getAccessTokenUseCache(HuaweiPush $push): string
    {
        if (Cache::has(self::CACHE_TOKEN_NAME)) {
            $accessToken = Cache::get(self::CACHE_TOKEN_NAME);
        } else {
            $accessToken = $push->getAccessToken();
            $tokenExpiresTime = $push->getAccessTokenExpiresTime();
            Cache::put(self::CACHE_TOKEN_NAME, $accessToken, $tokenExpiresTime);
        }
        return $accessToken;
    }
}
