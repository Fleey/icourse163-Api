<?php

namespace Mooc;

use Util\Curl;

class User extends Course
{
    /**
     * User constructor.
     * @param $cookie
     * @param $CSRF
     * @param $baseUrl
     */
    public function __construct($cookie, $CSRF, $baseUrl)
    {
        parent::__construct($cookie,$CSRF,$baseUrl);
    }

    /**
     * 获取学校课程ID
     * @param $courseID
     * @return int
     */
    public function getSchoolID($courseID){
        $courseList = $this->getCourseList();
        if(empty($courseList)){
            return 0;
        }
        foreach ($courseList['result']['result'] as $value){
            if($value['termPanel']['id'] == $courseID){
                return $value['termPanel']['courseId'];
            }
        }
        return 0;
    }

    /**
     * 获取用户信息
     * @return array
     */
    public function getUserInfo()
    {
        $curl = new Curl($this->baseUrl . '/home.htm');
        $curl->addHeader(['Cookie:' . $this->cookie]);
        $result = $curl->send();
        // request data
        preg_match("/window.webUser = {\n([\s\S]*)}/isU", $result, $Arr);
        if (empty($Arr)) {
            return [];
        }
        $result = '{' . preg_replace_callback('/(.*):/U', function ($matches) {
                if ($matches[1] == '"https:' || $matches[1] == '"https') {
                    return $matches[1];
                } else {
                    return '"' . $matches[1] . '":';
                }
            }, $Arr[1]) . '}';
        return json_decode($result, true);
    }

    /**
     * 获取用户报名的课程列表
     * @return array
     */
    public function getCourseList()
    {
        $curl = new Curl($this->baseUrl.'/web/j/learnerCourseRpcBean.getMyLearnedCoursePanelList.rpc?csrfKey='.$this->csrf);
        $curl->addHeader(['Cookie:'.$this->cookie]);
        $curl->setRequestType('post');
        $result = $curl->send('type=30&p=1&psize=32',true);
        return $result;
    }
}