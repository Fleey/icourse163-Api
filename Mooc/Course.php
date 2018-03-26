<?php

namespace Mooc;

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
        preg_match_all('/contentId=(\d*);.*contentType=6;/', $resultStr, $data);

        if (count($data[1]) != 0) {
            return $data[1];
        }
        return [];
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
     * @param $commentID
     * @param $replyContent
     * @return bool
     */
    public function commentReply($commentID, $replyContent)
    {
        $curl = new Curl($this->baseUrl . '/dwr/call/plaincall/MocForumBean.addReply.dwr');
        $curl->addHeader(['Cookie:' . $this->cookie]);
        $curl->setRequestType('post');
        $result = $curl->send(self::structRequestData([
            'callCount'       => 1,
            'scriptSessionId' => '${scriptSessionId}190',
            'httpSessionId'   => $this->csrf,
            'c0-scriptName'   => 'MocForumBean',
            'c0-methodName'   => 'addReply',
            'c0-id'           => 0,
            'c0-e1'=>'number:'.$commentID,
            'c0-e2'=>'string:'.urlencode($replyContent),
            'c0-e3'=>'number:0',
            'c0-param0'=>'Object_Object:{postId:reference:c0-e1,content:reference:c0-e2,anonymous:reference:c0-e3}',
            'c0-param1='=>'Array:[]',
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
        preg_match_all('/aid=(\d*)/', $result, $data);
        if (empty($data)) {
            return 0;
        }
        if(empty($data[1])){
            preg_match('/aid:(\d+?),/', $result, $data);
            return $data[1];
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
        preg_match_all('/answer=(.+?);.+?content="\<p(?:.*|)\>(.+?)\<\/p\>";/U', $data, $answer);
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
                    if ($o == 1) {
                        $baseAddress++;
                    }
                    $answerList[] = [self::unicode_decode($answer2[2][$baseAddress]), $answer2[1][$baseAddress]];
                } else {
                    $baseAddress  = $answerCounted + $o;
                    $answerList[] = [self::unicode_decode($answer[2][$baseAddress]), $answer[1][$baseAddress]];
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