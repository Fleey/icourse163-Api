<?php

namespace Mooc;

class WebUtil
{
    /**
     * get Mooc CSRF
     * @param string $cookie
     * @return string
     */
    protected static function getCSRF($cookie = '')
    {
        if (empty($cookie)) {
            return '';
        }
        preg_match("/NTESSTUDYSI=(.*);/isU", $cookie, $result);
        if (empty($result)) {
            return '';
        }
        return $result[1];
    }

    /**
     * 结构化Request数据
     * @param array $data
     * @return array|string
     */
    protected static function structRequestData($data = [])
    {
        if (!is_array($data)) {
            return $data;
        }
        $result = '';
        foreach ($data as $key => $value) {
            if ($result != '') {
                $result .= '&' . $key . '=' . $value;
            } else {
                $result .= $key . '=' . $value;
            }
        }
        return $result;
    }

    /**
     * 顾名思义
     * @param $name
     * @return string
     */
    protected static function unicode_decode($name)
    {
        $json = '{"str":"'.$name.'"}';
        $arr = json_decode($json,true);
        if(empty($arr)) return '';
        return $arr['str'];
    }
}