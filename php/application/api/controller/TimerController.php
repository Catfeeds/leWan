<?php
/**
 * Created by PhpStorm.
 * User: Admini
 * Date: 2018/11/13
 * Time: 11:36
 */

namespace app\api\controller;
use app\api\model\HelpModel;
use app\api\model\JpushModel;
use app\api\model\OpenTmModel;
use app\api\model\UserModel;
use app\common\BaseController;
use app\api\model\UserwithdrawModel;
use app\common\model\AccountRecordModel;
use app\common\model\CurrencyAction;
use think\Db;

/**
 * 定时任务
 * Class TimerController
 * @package app\api\controller
 */
class TimerController extends BaseController
{

    /**
     * 刷新全局token
     */
    public function refreshAccessToken(){
        $hm = new HelpModel();
        $hm->getAccessToken();
        $this->ajaxReturn('刷新成功', 1);
    }

    /**
     * 每分钟执行获取提现队列的数据，进行转账
     */
    public function userWithdraw(){
        $condition["w.withdraw_status"] = 3; //提现队列
        $condition["w.withdraw_type"] = 1; //暂时只获取微信的待提现的数据
//      $Condition["w.withdraw_type"] = array('in','1,3');//获取支付宝微信待提现的数据
        $field = "w.withdraw_id,w.withdraw_amount,w.withdraw_type,u.user_id";
        $list  =  Db::name("user_withdraw")
            ->alias("w")
            ->field($field)
            ->Join("user u","u.user_id = w.user_id","left")
            ->where($condition)
            ->limit(0, 20)
            ->order('w.withdraw_addtime desc')
            ->select();
        if(!empty($list)){
            GLog('微信提现数据',count($list));
            foreach ($list as $val){ //提现数据
                if(!empty($val)){
                    if($val['withdraw_type']==3){//申请支付宝提现

                    }else{ //申请微信提现
                        $map['user_id'] =  $val['user_id'];
                        $map['platform'] = array('in','wechat,wxapp');
                        $ucinfo =  Db::name("user_connect")->field('platform,openid')->where($map)->order('platform asc')->find();
                        if(!empty($ucinfo)){
                            if ($ucinfo['platform'] == 'wechat') { //公众号授权
                                $appid = WX_APPID;
                                $uwData['openid'] = $ucinfo['openid'];
                            } else { //微信APP授权
                                $appid = WXAPP_ID;
                                $uwData['openid'] = $ucinfo['openid'];
                            }
                            $uwData['order_on'] = md5($val['withdraw_id']);
                            if (DEPLOY_ENV!='pro'){//非生产环境(测试专用)
                                $uwData['amount'] = 0.3; //最低3毛起提
                                $appid = 'wxa6f9e099761010a5';
                                $mchid = '1516956861';
                            }else{
                                $mchid=WX_MCHID;
                                $uwData['amount'] = $val['withdraw_amount'];
                            }
                            $uwData['desc'] = '佣金提现';
                            $rs = UserwithdrawModel::wxWithdrawal($appid,$mchid, $uwData);
                            GLog('微信提现',$rs);
                            $newRes = xmlToArray($rs);
                            if ($newRes['return_code'] == 'SUCCESS'&&$newRes['result_code'] == 'SUCCESS') {
                                $updata['withdraw_code'] = 'success';
                                $updata['withdraw_status'] = 6;
                            } else {
                                $updata['withdraw_status'] = 7;
                                $updata['withdraw_code'] = 'fail';
                                $updata['withdraw_reason'] = $newRes['err_code_des'];
                            }
                            Db::name("user_withdraw")->where(['withdraw_id' => $val['withdraw_id']])->update($updata);
                        }
                    }
                }
            }
        }
        $this->ajaxReturn('执行成功', 1);
    }

    /**
     *中奖名单通知
     */
    public function directSellingNotice(){
        if(date('H')<15){
            return '还未开始';
        }
        $actionname = '每日冲锋奖(15点前3单)'.date('Ymd');
        $timeAction = Db::name('timer_action')->field('id,action_name,progress,results')->where(['action_name'=>$actionname])->find();
        if(empty($timeAction) || $timeAction['results'] ==1){ //没有或没执行玩
            if(empty($timeAction)){//
                $page = 1;
                $data['action_name'] = $actionname;
                $data['progress'] = $page;
                $data['correlation_id'] = 0;
                $data['results'] = 1;
                $data['addtime'] = time();
                Db::name('timer_action')->insertGetId($data);
            }else{
                $page= $timeAction['progress'];
            }
            $list = $this->getDirectSellingUser($page);
            if(!empty($list)){
                foreach ($list as $val){
                    if($val){
                        $title = '尊敬的乐玩用户，恭喜您成功参与“每日冲锋奖(15点前3单)”的活动';
                        $openid = Db::name('user_connect')->where(['user_id'=>$val['user_id']])->value('openid');
                        if($openid){
                            $data['title'] = $title;
                            $data['keyword1'] = '每日冲锋奖(15点前3单)'."\r\n中奖金额：".$val['record_amount'].'元';
                            $data['keyword2'] = '每天下午3点';
                            $data['remark'] = '点击查看详情';
                            $host =  $_SERVER['HTTP_HOST']?$_SERVER['HTTP_HOST']: $_SERVER['SERVER_NAME'];
                            $url = $host. '/wechat_html/page/homePage/productDetails.html';
                            $accessToken =  Db::name('access_token')->value('access_token');
                            OpenTmModel::sendTplmsg11($openid,$data,$accessToken,$url);
                        }
                        if($val['deviceid']){ //极光推送
                            $data['title'] = '活动中奖通知';
                            $data['alert'] = $title.',分得'.$val['record_amount'].'元奖金';
                            $option['type'] = JpushModel::JPUSH_MSG_COMMISSION;
                            $data['platform'] =  'all';
                            JpushModel::sendMsgSpecial($val['deviceid'],$data,$option);
                        }
                    }
                }

                $page++;
                $update['progress'] = $page;
                Db::name('timer_action')->where(array('id'=>$timeAction['id']))->update($update);
                return '操作成功3333';
            }else{
                if(!empty($timeAction)){
                    //循环完毕
                    Db::name('timer_action')->where(array('id'=>$timeAction['id']))->update(array('results'=>2));
                }
                return '操作成功2';
            }
            return '暂无数据';
        }else{
            return '已结束';
        }

    }

    /**
     * 获取中奖名单
     * @param int $page
     * @param int $psize
     * @return array|false|\PDOStatement|string|\think\Collection
     */
    private function getDirectSellingUser($page=1,$psize=30){
        $suffix = date('Ym');
        $tabel = 'account_commission'. $suffix;
        if(Db::query("SHOW TABLES LIKE 'jay_{$tabel}'")){ //判表是否存在
            $startime = strtotime(date('Y-m-d'));
            $endtime = strtotime(date('Y-m-d').' 15:00:00');
            $where['ac.record_action'] = CurrencyAction::CommissionActivesReward;
            $where = $this->TimeContrast($startime,$endtime,'ac.record_addtime',$where);
            $list = Db::name($tabel.' ac')
                ->field('u.deviceid,u.user_id,ac.record_amount,ac.record_remark,ac.record_addtime')
                ->join('user u','u.user_id=ac.user_id','left')
                ->where($where)
                ->page($page,$psize)
                ->select();
            return $list;
        }else{
            return [];
        }
    }

    /**
     * @param $StartTime  开始时间
     * @param $EndTime    结束时间
     * @param $Key        字段
     * @param $Condition  返回组合
     * @return mixed
     */
    protected  function TimeContrast($StartTime,$EndTime,$Key,$Condition){

        if (!empty($StartTime) && empty($EndTime)) {
            parent::Tpl_NotGtTime($StartTime,"开始时间不能大于当前时间"); //开始时间不为空和当前时间对比
            $Condition[$Key] = array(array('egt', $StartTime));
        } else if (empty($StartTime) && !empty($EndTime)) {
            parent::Tpl_NotGtTime($EndTime,"结束时间不能大于当前时间"); //结束时间不为空和当前时间对比
            $Condition[$Key] = array(array('lt', $EndTime));
        } else if (!empty($StartTime) && !empty($EndTime)) {
            parent::Tpl_TimeContrast1($StartTime,$EndTime); //开始和结束时间都不为空进行判断
            $Condition[$Key] = array(array('egt', $StartTime), array('elt', $EndTime));
        }

        return $Condition;
    }

    /**
     *清除失效的新人直卖活动
     * 分钱/自动失效等
     * @return bool
     */
    public function clearFirstOrderDirectSelling(){
       //直卖/入围的超过24小时没有完成邀请好友注册/领钱的全部失效
        $failure['status'] = array('in','1,4');
        $flist = Db::table('view_xinrenzhimai')->where($failure)->select();
        foreach ($flist as $val){
            if($val){
                if($val['status'] ==1 && $val['firsttime']+86400<=time()){  //仅第一步且超过24
                    Db::table('view_xinrenzhimai')->where(['id'=>$val['id']])->update(['status'=>0,'uptime'=>time()]);
                }
                if($val['status'] ==4 && $val['uptime']+86400<=time()){  //完成第二步且超过24没有进入活动领钱
                    Db::table('view_xinrenzhimai')->where(['id'=>$val['id']])->update(['status'=>0,'uptime'=>time()]);
                }
            }
        }
        return true;
    }
    /**
     *新人直卖活动
     * @return bool
     */
    public function firstOrderDirectSelling(){
        //入围的发通知
        $failure['status']  = 2;
        $list = Db::table('view_xinrenzhimai')->where($failure)->select();
        if(!empty($list)){
            $count = count($list);
            $config = Db::name('parameter')->column('value', 'key');
            if(!isset($config['hd_xrzm']) || !$config['hd_xrzm']){
                return false;
            }
            //保留两位小数并且不四舍五入
            $amount = sprintf("%.2f",substr(sprintf("%.3f", $config['hd_xrzm']/$count), 0, -2));
            foreach ($list as $val){
                if(!empty($val)){
                    //已发通知且存入当时统计所分得奖金
                    Db::table('view_xinrenzhimai')->where(['id'=>$val['id']])->update(['uptime'=>time(),'status'=>4,'amount'=>$amount]);
                    $senddata['title'] = "恭喜您入围新人直卖活动并获得{$amount}活动奖金！请在收到消息的24小时内点进详情领取,过期失效！！！";
                    $userpush =UserModel::getPushObj( $val['user_id']);
                    if($userpush['deviceid']){ //极光推送
                        $data['title'] = '恭喜您获得新人直卖活动资格';
                        $jpush['alert'] = $senddata['title'];
                        $option['type'] = JpushModel:: JPUSH_MSG_ACTIVITY;
                        $jpush['platform'] =  'all';
                        JpushModel::sendMsgSpecial($userpush['deviceid'],$jpush,$option);
                    }
                    if($userpush['openid']) { //微信推送
                        $accessToken =  Db::name('access_token')->value('access_token');
                        $senddata['keyword1'] = $val['user_id'];
                        $senddata['keyword2'] = date("Y-m-d H:s");
                        $data['remark'] = '点击查看详情！！';
                        $host = (isset($_SERVER['REQUEST_SCHEME'])?$_SERVER['REQUEST_SCHEME']:'http').'://'.$_SERVER['SERVER_NAME'];
                        $url = $host.'/wechat_html/page/lewanActivity/inviteNewPerson.html';
                        OpenTmModel::sendTplmsg12($userpush['openid'], $data, $accessToken,$url);
                    }
                }
            }
        }
    }
}