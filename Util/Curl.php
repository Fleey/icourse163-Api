<?php

namespace Util;

class Curl
{
    public $curlUrl;
    public $curlHeader = [
        'User-Agent:Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.186 Safari/537.36',
    ];
    public $curlTimeOut = 5;
    public $isDisplayHeader = false;
    private $requestType = 'get';

    /**
     * @param string $url
     */
    public function __construct($url = '')
    {
        $this->setUrl($url);
    }

    /**
     * @param string $url
     */
    public function setUrl($url = '')
    {
        if (!empty($url)) {
            $this->curlUrl = $url;
        }
    }

    /**
     * @param string $type
     */
    public function setRequestType($type = '')
    {
        $type              = strtolower($type);
        $this->requestType = $type;
    }

    /**
     * @param array $header
     */
    public function addHeader($header = [])
    {
        if (!empty($header)) {
            $this->curlHeader = array_merge($this->curlHeader, $header);
        }
    }

    /**
     * @param string $data
     * @param bool $isReturnJson
     * @return mixed|string
     */
    public function send($data = '', $isReturnJson = false)
    {
        if (empty($this->curlUrl)) {
            return '';
        }
        if ($this->requestType == 'json') {
            $Headers[] = 'Content-Type: application/json; charset=utf-8';
            $data      = is_array($data) ? json_encode($data) : $data;
            $Headers[] = 'Content-Length: ' . strlen($data);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->curlUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->curlHeader);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->curlTimeOut);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        //ssl
        if ($this->requestType == 'get') {
            curl_setopt($ch, CURLOPT_HEADER, false);
        } else if ($this->requestType == 'post') {
            curl_setopt($ch, CURLOPT_POST, 1);
        } else {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($this->requestType));
        }
        if($this->requestType != 'get'){
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        //set request type
        if($this->isDisplayHeader){
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        }
        //display header
        $result = curl_exec($ch);
        curl_close($ch);
        if($isReturnJson){
            $result = json_decode($result,true);
        }
        return $result;
    }
}