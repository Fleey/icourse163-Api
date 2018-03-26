<?php

namespace Mooc;

use Util\Curl;

class Video extends WebUtil
{
    protected $cookie;
    protected $csrf;
    protected $baseUrl;

    /**
     * Video constructor.
     * @param $cookie
     * @param $CSRF
     * @param $baseUrl
     */
    public function __construct($cookie, $CSRF, $baseUrl)
    {
        $this->baseUrl = $baseUrl;
        $this->cookie  = $cookie;
        $this->csrf    = $CSRF;
    }

    /**
     * 设置视频为已经观看完毕状态
     * @param $schoolID
     * @param $unitID
     * @param $termID
     * @param $videoID
     * @param $lessonID
     * @return bool
     */
    public function setVideoView($schoolID, $unitID, $termID, $videoID, $lessonID)
    {
        $videoTimeLength = self::getVideoTimeLength($videoID, $lessonID);
        $videoRoundCount = intval($videoTimeLength / 20);
        if ($videoTimeLength / 20 != 0) {
            $videoRoundCount++;
        }
        $curl = new Curl($this->baseUrl . '/dwr/call/plaincall/CourseBean.saveMocContentLearn.dwr');
        $curl->addHeader(['Cookie:' . $this->cookie]);
        $curl->setRequestType('post');
        $data = [
            'callCount'       => 1,
            'scriptSessionId' => '${scriptSessionId}190',
            'httpSessionId'   => $this->csrf,
            'c0-scriptName'   => 'CourseBean',
            'c0-methodName'   => 'saveMocContentLearn',
            'c0-id'           => 0,
            'c0-e1'           => 'number:' . $unitID,
            'c0-e2'           => 'number:' . $videoTimeLength,
            'c0-e3'           => 'boolean:true',
            'c0-e4'           => 'number:' . $videoRoundCount,
            'c0-e5'           => 'number:2000',
            'c0-e6'           => 'number:' . $schoolID,
            'c0-e7'           => 'number:' . $lessonID,
            'c0-e8'           => 'number:' . $videoID,
            'c0-e9'           => 'number:' . $termID,
            'c0-e10'          => 'null:null',
            'c0-param0'       => 'Object_Object:{unitId:reference:c0-e1,videoTime:reference:c0-e2,finished:reference:c0-e3,index:reference:c0-e4,duration:reference:c0-e5,courseId:reference:c0-e6,lessonId:reference:c0-e7,videoId:reference:c0-e8,termId:reference:c0-e9,resolutionType:reference:c0-e10}',
            'batchId'         => time()
        ];
        $result = $curl->send(self::structRequestData($data));
        return true;
    }

    /**
     * @param $courseID
     * @return array
     */
    public function getVideoList($courseID)
    {
        $result = self::getBeanList($courseID);
        preg_match_all('/contentId=(\d*);.*contentType=1;.*id=(\d*);.*lessonId=(\d*);.*name="(.*)";.*termId=(\d*);/', $result, $data);
        if (empty($result)) {
            return [];
        }
        $result     = [];
        $dataLength = count($data[1]);
        for ($i = 0; $i < $dataLength; $i++) {
            $result[] = ['videoId' => $data[1][$i], 'unitId' => $data[2][$i], 'lessonId' => $data[3][$i], 'termId' => $data[5][$i], 'name' => self::unicode_decode($data[4][$i])];
        }
        return $result;
    }

    /**
     * @param $lessonId
     * @param $unitIdID
     * @return int
     */
    public function getVideoTimeLength($lessonId, $unitIdID)
    {
        $curl = new Curl($this->baseUrl . '/dwr/call/plaincall/CourseBean.getLessonUnitLearnVo.dwr');
        $curl->addHeader(['Cookie:' . $this->cookie]);
        $curl->setRequestType('post');
        $result = $curl->send(self::structRequestData([
            'callCount'       => 1,
            'scriptSessionId' => '${scriptSessionId}190',
            'httpSessionId'   => $this->csrf,
            'c0-scriptName'   => 'CourseBean',
            'c0-methodName'   => 'getLessonUnitLearnVo',
            'c0-id'           => '0',
            'c0-param0'       => 'number:' . $lessonId,
            'c0-param1'       => 'number:1',
            'c0-param2'       => 'number:0',
            'c0-param3'       => 'number:' . $unitIdID,
            'batchId'         => time()
        ]));

        $Pattern = '/duration=(\d{1,});.*flvCaption=null;/';
        preg_match($Pattern, $result, $data);
        return empty($data) ? 0 : intval($data[1]);
    }

    /**
     * @param $courseID
     * @return string
     */
    protected function getBeanList($courseID)
    {
        $curl = new Curl($this->baseUrl . '/dwr/call/plaincall/CourseBean.getLastLearnedMocTermDto.dwr');
        $curl->addHeader(['Cookie:' . $this->cookie]);
        $curl->setRequestType('post');
        $result = $curl->send(self::structRequestData([
            'callCount'       => 1,
            'scriptSessionId' => '${scriptSessionId}190',
            'httpSessionId'   => $this->csrf,
            'c0-scriptName'   => 'CourseBean',
            'c0-methodName'   => 'getLastLearnedMocTermDto',
            'c0-id'           => 0,
            'c0-param0'       => 'number:' . $courseID,
            'batchId'         => time()
        ]));
        return $result;
    }
}