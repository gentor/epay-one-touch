<?php


namespace Gentor\EpayOneTouch;


/**
 * Class Client
 * @package Gentor\EpayOneTouch
 */
class Client
{
    const API_BASE_DEMO = 'https://demo.epay.bg/xdev/api';
    const API_BASE_WEB_DEMO = 'https://demo.epay.bg/xdev/mobile';
    const API_BASE = 'https://www.epay.bg/v3/api';
    const API_BASE_WEB = 'https://www.epay.bg/v3/mob';

    protected $appId;
    protected $secret;
    protected $kin;
    protected $endpoint;
    protected $endpointWeb;
    protected $http;

    /**
     * Client constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        if ($config['live_mode']) {
            $this->endpoint = static::API_BASE;
            $this->endpointWeb = static::API_BASE_WEB;
        } else {
            $this->endpoint = static::API_BASE_DEMO;
            $this->endpointWeb = static::API_BASE_WEB_DEMO;
        }

        $this->appId = $config['app_id'];
        $this->secret = $config['secret'];
        $this->kin = $config['kin'];
        $this->http = new \GuzzleHttp\Client();
    }

    /**
     * @param $id
     * @param $amount
     * @param $description
     * @param $device
     * @param null $reason
     * @return array
     */
    public function createPaymentRequest($id, $amount, $description, $device, $reason = null)
    {
        $request = [
            'APPID' => $this->appId,
            'DEVICEID' => $device,
            'ID' => $id,
            'AMOUNT' => $amount,
            'RCPT' => $this->kin,
            'RCPT_TYPE' => 'KIN',
            'DESCRIPTION' => $description,
            'REASON' => $reason ?? $id,
//            'SAVECARD' => 1,
        ];

        return $this->signRequest($request);
    }

    /**
     * @param $token
     * @param $device
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function initSendPayment($token, $device)
    {
        $request = [
            'APPID' => $this->appId,
            'DEVICEID' => $device,
            'TOKEN' => $token,
            'TYPE' => 'send',
        ];

        $response = $this->http->post($this->endpoint . '/payment/init', [
            'json' => $request,
        ]);

        return json_decode($response->getBody());
    }

    /**
     * @param $token
     * @param $device
     * @param $id
     * @param $amount
     * @param $pins
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function checkSendPayment($token, $device, $id, $amount, $pins)
    {
        $request = [
            'APPID' => $this->appId,
            'DEVICEID' => $device,
            'TOKEN' => $token,
            'TYPE' => 'send',
            'ID' => $id,
            'AMOUNT' => $amount,
            'RCPT' => $this->kin,
            'RCPT_TYPE' => 'KIN',
            'PINS' => $pins,
        ];

        $response = $this->http->post($this->endpoint . '/payment/check', [
            'json' => $request,
        ]);

        return json_decode($response->getBody());
    }

    /**
     * @param $token
     * @param $device
     * @param $id
     * @param $amount
     * @param $pins
     * @param $description
     * @param null $reason
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendPayment($token, $device, $id, $amount, $pins, $description, $reason = null)
    {
        $request = [
            'APPID' => $this->appId,
            'DEVICEID' => $device,
            'TOKEN' => $token,
            'TYPE' => 'send',
            'ID' => $id,
            'AMOUNT' => $amount,
            'RCPT' => $this->kin,
            'RCPT_TYPE' => 'KIN',
            'PINS' => $pins,
            'DESCRIPTION' => $description,
            'REASON' => $reason,
        ];

        $response = $this->http->post($this->endpoint . '/payment/send/user', [
            'json' => $request,
        ]);

        return json_decode($response->getBody());
    }

    /**
     * @param $token
     * @param $device
     * @param $id
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function checkSendPaymentStatus($token, $device, $id)
    {
        $request = [
            'APPID' => $this->appId,
            'DEVICEID' => $device,
            'TOKEN' => $token,
            'ID' => $id,
        ];

        $response = $this->http->post($this->endpoint . '/payment/send/status', [
            'json' => $request,
        ]);

        return json_decode($response->getBody());
    }

    /**
     * @param array $request
     * @return string
     */
    public function getRedirectUrl(array $request)
    {
        return $this->endpointWeb . '/api/payment/noreg/send?' . http_build_query($request);
    }

    /**
     * @param $id
     * @param $device
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function checkPaymentStatus($id, $device)
    {
        $request = [
            'APPID' => $this->appId,
            'DEVICEID' => $device,
            'ID' => $id,
            'RCPT' => $this->kin,
        ];

        $response = $this->http->get($this->endpoint . '/api/payment/noreg/send/status', [
            'query' => $request,
        ]);

        return json_decode($response->getBody());
    }

    /**
     * @param $key
     * @param $device
     * @return string
     */
    public function getAuthRedirectUrl($key, $device)
    {
        $request = [
            'APPID' => $this->appId,
            'KEY' => $key,
            'DEVICEID' => $device,
        ];

        return $this->endpointWeb . '/api/start?' . http_build_query($request);
    }

    /**
     * @param array $request
     * @return array
     */
    protected function signRequest(array $request)
    {
        ksort($request);
        $checksum = '';

        foreach ($request as $name => $value) {
            $checksum .= $name . $value . "\n";
        }

        $request['checksum'] = hash_hmac('sha1', $checksum, $this->secret);

        return $request;
    }
}