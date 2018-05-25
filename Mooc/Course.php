<?php

namespace Mooc;

use think\Exception;
use Util\Curl;

class Course extends Video
{

    /**
     * Course constructor.
     * @param $cookie
     * @param $CSRF
     * @param $baseUrl
     */
    public function __construct($cookie, $CSRF, $baseUrl)
    {
        parent::__construct($cookie, $CSRF, $baseUrl);
    }

    /**
     * 查询问题列表
     * @param $courseID
     * @return array
     */
    public function getQuizList($courseID)
    {
        $resultStr = self::getBeanList($courseID);
        preg_match_all('/contentId=(\d*);.*contentType=2;.*name="(.*)"/', $resultStr, $data);
        $result     = [];
        $dataLength = count($data[1]);
        if ($dataLength != 0) {
            for ($i = 0; $i < $dataLength; $i++) {
                $result['bigQuiz'][] = ['id' => [$data[1][$i]], 'name' => self::unicode_decode($data[2][$i])];
            }
        }
        //获取单元测试问题列表
        preg_match_all('/examId=\d*;.*id=(\d*);.*name="(.*)";/',$resultStr,$data);
        if(count($data[1]) == 2){
            $result['bigQuiz'][] = ['id'=>[$data[1][1]],'name' => self::unicode_decode($data[2][1])];
        }
        //获取期末考试问题
        preg_match_all('/contentId=\d*;.*contentType=1;.*jsonContent="(.*)";.*name="(.*)"/', $resultStr, $data);
        $dataLength = count($data[1]);
        if ($dataLength != 0) {
            for ($i = 0; $i < $dataLength; $i++) {
                $json                  = str_replace('\\', '', $data[1][$i]);
                $json                  = json_decode($json, true);
                $result['smallQuiz'][] = ['id' => [$json], 'name' => self::unicode_decode($data[2][$i])];
            }
        }
        //获取课堂小测列表
        return $result;
    }

    /**
     * 返回评论ID列表
     * @param $courseID
     * @return array //commentID
     */
    public function getCommentsList($courseID)
    {
        $resultStr = self::getBeanList($courseID);
        preg_match_all('/contentId=(\d*);.*contentType=6;.*id=(\d*);/', $resultStr, $data);

        return [
            'contentId' => $data[1],
            'id'        => $data[2]
        ];
    }

    /**
     * 返回需要查看的文档列表
     * @param $courseID
     * @return array
     */
    public function getDocsList($courseID)
    {
        $resultStr = self::getBeanList($courseID);
        preg_match_all('/contentId=(\d*);.*contentType=3;.*id=(\d*);/', $resultStr, $data);
        return [
            'contentId' => $data[1],
            'id'        => $data[2]
        ];
    }

    /**
     * 返回文档页数
     * @param $docsContentId
     * @param $docsId
     * @return int
     */
    public function getDocsPageCount($docsContentId, $docsId)
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
            'c0-id'           => 0,
            'c0-param0'       => 'number:' . $docsContentId,
            'c0-param1'       => 'number:3',
            'c0-param2'       => 'number:0',
            'c0-param3'       => 'number:' . $docsId,
            'batchId'         => time()
        ]));
        preg_match('/textPages:(\d+?),/', $result, $data);

        if (empty($data)) {
            return 0;
        }
        return intval($data[1]);
    }

    /**
     * 保存查看文档记录
     * @param $docsId
     * @param $docsPageCount
     * @return bool
     */
    public function saveDocsRecord($docsId, $docsPageCount)
    {
        return $this->saveContentLean([
            'unitId'   => 'number:' . $docsId,
            'pageNum'  => $docsPageCount,
            'finished' => 'boolean:true'
        ]);
    }

    /**
     * 保存回复记录
     * @param $replyId
     * @return bool
     */
    public function saveReplyRecord($replyId)
    {
        return $this->saveContentLean([
            'unitId'   => 'number:' . $replyId,
            'finished' => 'boolean:true'
        ]);
    }

    /**
     * 查询随堂测验答案
     * @param array $issueID
     * @return array
     */
    public function getSmallQuizAnswer($issueID = [])
    {
        if (empty($issueID)) {
            return [];
        }
        $sendData = '';
        if (is_array($issueID)) {
            foreach ($issueID as $value) {
                $sendData .= (empty($sendData) ? '' : ',') . 'Object_Object:{questionId:number:' . $value . '}';
            }
        } else if (is_string($issueID)) {
            $sendData = 'Object_Object:{questionId:number:' . $issueID . '}';
        } else {
            return [];
        }
        $sendData = 'Array:[' . $sendData . ']';
        //序列化数据
        $curl = new Curl($this->baseUrl . '/dwr/call/plaincall/MocQuizBean.fetchQuestions.dwr');
        $curl->addHeader(['Cookie:' . $this->cookie]);
        $curl->setRequestType('post');
        $result = $curl->send(self::structRequestData([
            'callCount'       => 1,
            'scriptSessionId' => '${scriptSessionId}190',
            'httpSessionId'   => $this->csrf,
            'c0-scriptName'   => 'MocQuizBean',
            'c0-methodName'   => 'fetchQuestions',
            'c0-id'           => 0,
            'c0-e3'           => $sendData,
            'c0-param0'       => 'Object_Object:{anchorQuestions:reference:c0-e3}',
            'batchId'         => time()
        ]));
        return $this->analyzeQuizAnswer($result);
    }

    /**
     * 查询单元测验答案
     * @param $issueID
     * @return array
     */
    public function getBigQuizAnswer($issueID)
    {
        $issueAID = self::getIssueAID($issueID);
        $curl     = new Curl($this->baseUrl . '/dwr/call/plaincall/MocQuizBean.getQuizPaperDto.dwr');
        $curl->addHeader(['Cookie:' . $this->cookie]);
        $curl->setRequestType('post');
        $result = $curl->send(self::structRequestData([
            'callCount'       => 1,
            'scriptSessionId' => '${scriptSessionId}190',
            'httpSessionId'   => $this->csrf,
            'c0-scriptName'   => 'MocQuizBean',
            'c0-methodName'   => 'getQuizPaperDto',
            'c0-id'           => 0,
            'c0-param0'       => 'string:' . $issueID,
            'c0-param1'       => 'number:' . $issueAID,
            'c0-param2'       => 'boolean:true',
            'batchId'         => time()
        ]));
        return $this->analyzeQuizAnswer($result);
    }

    /**
     * 回复章节内容
     * @param $replyContentId
     * @param $replyID
     * @param $replyContent
     * @return bool
     */
    public function commentReply($replyContentId,$replyID, $replyContent)
    {
        $curl = new Curl($this->baseUrl . '/dwr/call/plaincall/MocForumBean.addReply.dwr');
        $curl->addHeader(['Cookie:' . $this->cookie]);
        $curl->setRequestType('post');
        $curl->send(self::structRequestData([
            'callCount'       => 1,
            'scriptSessionId' => '${scriptSessionId}190',
            'httpSessionId'   => $this->csrf,
            'c0-scriptName'   => 'MocForumBean',
            'c0-methodName'   => 'addReply',
            'c0-id'           => 0,
            'c0-e1'           => 'number:' . $replyContentId,
            'c0-e2'           => 'string:' . urlencode($replyContent),
            'c0-e3'           => 'number:0',
            'c0-param0'       => 'Object_Object:{postId:reference:c0-e1,content:reference:c0-e2,anonymous:reference:c0-e3}',
            'c0-param1='      => 'Array:[]',
            'batchId'         => time()
        ]));
        $this->saveReplyRecord($replyID);
        return true;
    }

    /**
     * 保存学习记录
     * @param array $data
     * @return bool
     */
    private function saveContentLean($data = [])
    {
        $curl = new Curl($this->baseUrl . '/dwr/call/plaincall/CourseBean.saveMocContentLearn.dwr');
        $curl->addHeader(['Cookie:' . $this->cookie]);
        $curl->setRequestType('post');
        $saveObj = '';
        foreach ($data as $key => $value) {
            $saveObj .= $key . ':' . $value . ',';
        }
        $saveObj = substr($saveObj, 0, strlen($saveObj) - 1);
        $curl->send(self::structRequestData([
            'callCount'       => 1,
            'scriptSessionId' => '${scriptSessionId}190',
            'httpSessionId'   => $this->csrf,
            'c0-scriptName'   => 'CourseBean',
            'c0-methodName'   => 'saveMocContentLearn',
            'c0-id'           => 0,
            'c0-param0'       => 'Object_Object:{' . $saveObj . '}',
            'batchId'         => time()
        ]));
        return true;
    }

    /**
     * 获取单元测验AID
     * @param $issueID
     * @return int
     */
    private function getIssueAID($issueID)
    {
        $curl = new Curl($this->baseUrl . '/dwr/call/plaincall/MocQuizBean.getQuizInfo.dwr');
        $curl->addHeader(['Cookie:' . $this->cookie]);
        $curl->setRequestType('post');
        $result = $curl->send(self::structRequestData([
            'callCount'       => 1,
            'scriptSessionId' => '${scriptSessionId}190',
            'httpSessionId'   => $this->csrf,
            'c0-scriptName'   => 'MocQuizBean',
            'c0-methodName'   => 'getQuizInfo',
            'c0-id'           => 0,
            'c0-param0'       => 'string:' . $issueID,
            'c0-param1'       => 'null:null',
            'c0-param2'       => 'boolean:false',
            'batchId'         => time()
        ]));
        preg_match('/aid:(\d+?),/', $result, $data);
        //获取当前在做的试卷id 也有可能不存在
        if(!empty($data[1])){
            return $data[1];
        }
        //优先返回正在做的试卷id
        preg_match_all('/aid=(\d*)/', $result, $data);
        //获取所有试卷id列表
        if (empty($data)) {
            return 0;
        }
        return $data[1][count($data[1]) - 1];
    }

    /**
     * 结构化答案
     * @param $data
     * @return array
     */
    private function analyzeQuizAnswer($data)
    {
        preg_match_all('/plainTextTitle="(.*)";.*type=(\d+?);/isU', $data, $title);
        preg_match_all('/answer=(.+?);.+?content="(.+?)";/U', $data, $answer);
        if (empty($answer) || empty($title)) {
            return [];
        }
        $titleLength = count($title[1]);;
        if (($titleLength * 4) != count($answer[1])) {
            preg_match_all('/type=4;[\s\S]{0,120}answer=(.*);[\s\S]*content="(.*)";[\s\S]*answer=(.*);[\s\S]*content="(.*)";/U', $data, $answer2);
            $answer2[1] = array_merge($answer2[1], $answer2[3]);
            $answer2[2] = array_merge($answer2[2], $answer2[4]);
            array_splice($answer2, 3, 2);
        }
        //处理判断题
        $answerCounted  = 0;
        $answer2Counted = 0;
        $data           = [];
        for ($i = 0; $i < $titleLength; $i++) {
            $answerList = [];
            switch ($title[2][$i]) {
                case 1:
                    $answerLength = 4;
                    break;
                case 2:
                    $answerLength = 4;
                    break;
                case 4:
                    $answerLength = 2;
                    break;
                default:
                    $answerLength = 0;
                    break;
            }
            for ($o = 0; $o < $answerLength; $o++) {
                if ($answerLength == 2) {
                    $baseAddress = $answer2Counted + $o;
                    if ($o == 1 && $titleLength != 1 && $answer2Counted != 0) {
                        $baseAddress++;
                    }
                    $answerList[] = [self::unicode_decode(strip_tags($answer2[2][$baseAddress])), $answer2[1][$baseAddress]];
                    //一般为判断题两个答案选项
                }else{
                    $baseAddress  = $answerCounted + $o;
                    $answerList[] = [self::unicode_decode(strip_tags($answer[2][$baseAddress])), $answer[1][$baseAddress]];
                    //一般为4个答案 的单选题或多选题
                }
            }
            if ($answerLength == 2) {
                $answer2Counted++;
            } else {
                $answerCounted += $answerLength;
            }
            $data[] = [
                'title'      => self::unicode_decode($title[1][$i]),
                'answerList' => $answerList
            ];
        }
        return $data;
    }

}
