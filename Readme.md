#慕课平台接口

致力于简便操作，至于能干啥你懂得~

##使用教程


1.初始化Mooc类

```php
use Mooc\Mooc;
class main
{
    private $cookie = 'Mooc Cookie'; // cookie
    public mainFun(){
        $mooc = new Mooc($this->cookie);
        //init mooc
    }
}
```

2.获取用户信息

```php
$mooc = new Mocc($cookie);
$userInfo = $mooc->getUserInfo();
```
```json
{
	"id": "用户ID",
	"nickName": "用户名称",
	"roles": [],
	"passport": "878EA*****F413AE961AB3F74C341E",
	"personalUrlSuffix": "mooc1505290402352",
	"smallFaceUrl": "用户头像地址",
	"largeFaceUrl": "用户头像地址",
	"loginId": "登录ID",
	"loginType": "登录类型 4为扣扣登录 （其他慢慢查吧。懒得放出来）",
	"email": "用户绑定邮箱",
	"schoolId": "学校ID",
	"end_key": "end_value"
}
```
3.获取用户订阅课程列表
```php
$mooc = new Mocc($cookie);
$courseList = $mooc->getCourseList();
//获取课程列表
```
> 课程ID为 $courseList['result']['result'][课程序列]['termPanel']['id']
```json
{
	返回信息太长了就不放出来了
}
```
4.获取课程提问与测验列表
```php
$courseID = 课程ID;
$mooc     = new Mooc($cookie);
$quizList = $mooc->getQuizList($courseID);
```
```php
$quizList['bigQuiz'] //数组内的为测验列表与考试
$quizList['smallQuiz'] //数组内的为随堂提问列表
```
5.获取问题答案
```php
$mooc = new Mooc($cookie);
if($questionType == 'big_quiz'){
	$answerList = $mooc->getBigQuizAnswer($questionId);
}
if($questionType == 'small_quiz'){
    $answerList = $mooc->getSmallQuizAnswer($questionId);
}
foreach ($answerList as $value){
    echo '<li>'.$value['title'].'<ul>';
    foreach ($value['answerList'] as $value2){
	    echo '<li style="'.($value2[1] == 'true'?'color:#63B931;':'').'">'.$value2[0].'</li>';
    }
    echo '</ul></li>';
}
echo '</ol>';
//注意此处我已经自动html处理过答案颜色与样式，当然你也能自行更改。
```
> 一键设置视频观看记录与一键回复评论等有趣功能请自行挖掘，或私信我进行有偿服务
