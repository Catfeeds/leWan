<?php
/**
 * Created by PhpStorm.
 * User: Admini
 * Date: 2019/2/28
 * Time: 17:25
 * 用户社区控制器
 * 肖亚子
 */

namespace app\api\controller;
use app\common\model\Tag;
use think\Db;
use Think\Exception;
use app\api\model\UserModel;
use app\api\model\UsercommunityleaderModel;


class UsercommunityleaderController extends ApiBaseController{
    /**
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * 小区查询
     * 肖亚子
     */
    public function LeaderSearch(){
        try{
            $Token    = input("post.token","","htmlspecialchars,strip_tags");
            $AreaCode = intval(input("post.areacode","","htmlspecialchars,strip_tags"));//城市code
            $Title    = input("post.title","","htmlspecialchars,strip_tags");
            $Paging   = intval(input("post.paging",1));

            $Punfu = new PubfunController();
            $Punfu->UserLoginStatus($Token,$this->headerData);//用户判断
           // self::LeaderProving($Token);

            if (!$AreaCode){
                $this->returnApiData("请选择城市区域", 400);
            }
            if (!$Title){
                $this->returnApiData("请输入搜索小区", 400);
            }

            $Condition["district_id"]    = $AreaCode;
            $Condition["community_name"] = array("like","%$Title%");
            $Condition["status"]         = 1;
            $Condition["del"]            = 0;

            $List = UsercommunityleaderModel::LeaderList($Condition,$Paging,10);

            $this->returnApiData("获取成功", 200,$List);
        }catch (Exception $e) {
            parent::Tpl_Abnormal($e->getMessage());  //数据库异常抛出
        }
    }

    /**
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * 用户申请小区盟主
     * 肖亚子
     */
    public function LeaderApply(){
        try{
            $Token     = input("post.token","","htmlspecialchars,strip_tags");
            $CityCode  = intval(input("post.citycode","","htmlspecialchars,strip_tags"));
            $AreaCode  = intval(input("post.areacode","","htmlspecialchars,strip_tags"));
            $Title     = input("post.title","","htmlspecialchars,strip_tags");
            $Id        = intval(input("post.community_id"));
            $Realname  = input("post.realname","","htmlspecialchars,strip_tags");
            $Phone     = input("post.phone","","htmlspecialchars,strip_tags");
            $Introduce = input("post.introduce","","htmlspecialchars,strip_tags");

            $Punfu = new PubfunController();
            $Punfu->UserLoginStatus($Token,$this->headerData);//用户判断
            $Uid   = UserModel::UserFindUid($Token);
            self::LeaderProving($Token);

            parent::Tpl_Empty($CityCode,"请选择城市",2);
            parent::Tpl_Empty($AreaCode,"请选择区/县",2);

            if (!$Id){
                parent::Tpl_Empty($Title,"请输入您要申请的小区名",2);
                parent::Tpl_FullSpace($Title,"请输入您要申请的小区名",2);
                parent::Tpl_StringLength($Title,"小区名2-20字",3,2,20,2);
            }

            parent::Tpl_Empty($Realname,"请输入您的真实姓名",2);
            parent::Tpl_FullSpace($Realname,"请输入您的真实姓名",2);
            parent::Tpl_StringLength($Realname,"姓名2-10字",3,2,10,2);
            parent::Tpl_Phone($Phone,"请输入联系电话",2);

            if ($Introduce){
                parent::Tpl_FullSpace($Introduce,"请输入正确的自我介绍",2);
                parent::Tpl_StringLength($Introduce,"自我介绍不能大于50字",2,0,50,2);
            }

            if ($Id){
                $Count = UsercommunityleaderModel::LeaderCount(array("community_id"=>$Id,"status"=>2));

                if ($Count+1 > Applicant){
                    $this->returnApiData("此小区盟主已满,请重新申请其它小区", 400);
                }
            }

            $Data["user_id"]      = $Uid;
            $Data["city_id"]      = $CityCode;
            $Data["district_id"]  = $AreaCode;
            $Data["community_id"] = $Id;
            $Data["realname"]     = $Realname;
            $Data["status"]       = 0;
            $Data["phone"]        = $Phone;
            $Data["introduce"]    = $Introduce;
            $Data["addtime"]      = time();

            if (!$Id){
                $ComFind = UsercommunityleaderModel::LeaderCommunityFind(array("community_name"=>$Title));

                if (!$ComFind){
                    $Cash = UsercommunityleaderModel::TableName();
                    $Cash->startTrans();//开启事务

                    $CommunityData["district_id"]    = $AreaCode;
                    $CommunityData["community_name"] = $Title;
                    $CommunityData["del"]            = 0;
                    $CommunityData['status'] = 0;
                    $Com_ID = UsercommunityleaderModel::LeaderCommunityAdd($CommunityData);

                    if ($Com_ID === false){
                        $Cash->rollback();//失败回滚exit;
                        $this->returnApiData("申请失败", 400);
                    }
                    $Data["community_id"] = $Com_ID;
                }else{
                    $Count = UsercommunityleaderModel::LeaderCount(array("community_id"=>$ComFind,"status"=>2));

                    if ($Count+1 > Applicant){
                        $this->returnApiData("此小区盟主已满,请重新申请其它小区", 400);
                    }

                    $Data["community_id"] = $ComFind;
                }
            }

            $ApplyAdd = UsercommunityleaderModel::LeaderAdd($Data);

            if (!$Id && !$ComFind){
                if ($ApplyAdd === false){
                    $Cash->rollback();//失败回滚exit;
                    $this->returnApiData("申请失败", 400);
                }else{
                    $Cash->commit();//成功提交事务
                }
            }else{
                if ($ApplyAdd === false){
                    $this->returnApiData("申请失败", 400);
                }
            }

            $this->returnApiData("申请成功", 200);

        }catch (Exception $e) {
            parent::Tpl_Abnormal($e->getMessage());  //数据库异常抛出
        }
    }

    /**
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * 获取用户小区盟主状态
     * 肖亚子
     */
    public function LeaderQuery(){
        try{
            $Token = input("post.token","","htmlspecialchars,strip_tags");
            $Punfu = new PubfunController();
            $Punfu->UserLoginStatus($Token,$this->headerData);//用户判断

            $UserProving = UserModel::UserDataFind(array("u.token"=>$Token),"u.user_id,u.level");

            if ($UserProving["level"] < 3){
                $this->returnApiData("权限不足,不能进行小区盟主申请", 400);
            }

            $ApplyFind = UsercommunityleaderModel::LeaderFind(array("a.user_id"=>$UserProving["user_id"]),"c.community_name,a.status,a.remark,a.uptime");

            if (empty($ApplyFind)){
                $ApplyFind["community_name"] = "";
                $ApplyFind["status"] = 3;
                $ApplyFind["remark"] = "";
                $ApplyFind["uptime"] = "";
            }

            $this->returnApiData("获取成功", 200,$ApplyFind);
        }catch (Exception $e) {
            parent::Tpl_Abnormal($e->getMessage());  //数据库异常抛出
        }
    }

    /**
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * 获取盟主排行信息
     * 肖亚子
     */
    public function LeaderRanking(){
        $Token = input("post.token","","htmlspecialchars,strip_tags");
        $Punfu = new PubfunController();
        $Punfu->UserLoginStatus($Token,$this->headerData);//用户判断

        self::LeaderProving($Token,2);
        $Uid = UserModel::UserFindUid($Token);

        $Ranking = UsercommunityleaderModel::LeaderlRankingFind(array("u.user_id"=>$Uid));

        if ($Ranking["thismonthranking"] != 1 && !empty($Ranking["thismonthranking"])){
            $Commission = UsercommunityleaderModel::LeaderThisMonthFind(array("id" => 1));

            $Multiple   = bcdiv($Ranking["commission"]/100,1,2);
            $Difference = bcdiv($Commission - $Ranking["commission"],1,2);

            $Ranking["percentage"] = sprintf('%.2f%%',bcdiv($Difference/$Multiple,1,2));
        }else{
            $Condition["user_id"]     = $Uid;
            $Condition["account_tag"] = array("gt",date("Ym00",time()));
            $Ranking["commission"]    = UsercommunityleaderModel::LeaderUserAccount($Condition);
            $Ranking["percentage"]    = "";
        }

        if (empty($Ranking["thismonthranking"])){
            $Ranking["thismonthranking"] = "";
            $Ranking["addtime"]          = "";
            $Ranking["lastmonthranking"] = "";
            $Ranking["ranking"]          = "";
        }else{
            if ($Ranking["thismonthranking"] == $Ranking["lastmonthranking"]){
                $Ranking["ranking"] = 1;
            }elseif ($Ranking["thismonthranking"] > $Ranking["lastmonthranking"]){
                $Ranking["ranking"] = 3;
            }elseif ($Ranking["thismonthranking"] < $Ranking["lastmonthranking"]){
                $Ranking["ranking"] = 2;
            }
        }

        $this->returnApiData("获取成功", 200,$Ranking);

    }

    /**
     * @param $Token  用户token
     * @param $Type   1全验证 2部分验证
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 用户申请小区盟主权限验证
     * 肖亚子
     */
    private function LeaderProving($Token,$Type = 1){

        $TokenCondition["u.token"] = array("eq",$Token);

        $UserProving = UserModel::UserDataFind($TokenCondition,"u.user_id,u.level,u.ownerstatus");
        $UserApply   = UsercommunityleaderModel::LeaderFind(array("a.user_id" => $UserProving["user_id"]),"a.status");

        if ($UserProving["level"] < 3){
            $this->returnApiData("权限不足", 400);
        }

        if ($Type == 1){
            if ($UserProving["ownerstatus"] > 0){
                $this->returnApiData("你已是小区盟主,请勿重新申请", 400);
            }
        }

        if ($UserApply && $UserApply["status"] == 0){
            $this->returnApiData("您的小区盟主正在审核中,请耐心等待.", 400);
        }

    }


    /**
     * 会员排名
     */
    public function top300(){
        $user = getUserByToken();
        //本月佣金金额
        $jrtime = strtotime(date('Y-m-d'));
        $toay = Db::name('account_commission'.Tag::getMonth())->where(['user_id'=>$user['user_id'], 'record_addtime'=>['between',$jrtime.','.($jrtime+86400)], 'record_action'=>['in', '601,602,603,604,606,607,608,609,610,611']])->sum('record_amount');
        $thismonth = Db::name('account_finance')->where(['user_id'=>$user['user_id'], 'finance_tag'=>Tag::getMonth()])->field('finance_xrmd+finance_first+finance_second+finance_operations+finance_operationchilds+finance_playerhost+finance_playerhostzhishu+finance_reward+finance_rewardback+finance_reward10+finance_rewardxrzm as amount')->find();
        $data["commission_benyue"] = sprintf('%.2f', $thismonth['amount']+($toay*1));
        //本月排名
        $by = Db::name('user300month')->where(['user_id'=>$user['user_id']])->find();
        $data['addtime'] = date2('Y-m-d',time()).' 00:00';
        if($by){
            $data['benyue_paiming'] = $by['id'].'位';
            //获取第一名佣金
            $dym = Db::name('user300month')->order('id asc')->find();
            if($by['id'] == 1){
                $data['chaju'] = '您就是第1名';
            }else{
                $bl = intval(($dym['commission']-$by['commission'])/$by['commission']*100);
                $data['chaju'] = '相差'.$bl.'%';
                if($bl == 0){
                    //$data['chaju'] = '并驾齐驱';
                }
            }
            $data['addtime'] = date2('Y-m-d H:i',$by['addtime']);
        }else{
            $data['benyue_paiming'] = '暂未进入排名';
            $data['chaju'] = '暂无';
        }
        //上月排名
        $sy = Db::name('user300lastmonth')->where(['user_id'=>$user['user_id']])->find();
        if($sy){
            $data['shangyue_paiming'] = $sy['id'].'位';
        }else{
            $data['shangyue_paiming'] = '暂未进入排名';
        }
        $data['bianhua'] = $sy['id']-$by['id']; //大于0 上升1名；小于0，下降名词；

        $data['level'] = $user['level'];
        $data['nickname'] = $user['nickname'];
        $data['avatar'] = $user['avatar'];
        $data['top'] = $by['id'];
        $this->returnApiData('查询成功', 200, $data);
    }


}