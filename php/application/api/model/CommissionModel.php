<?php
/**
 * Created by PhpStorm.
 * User: Admini
 * Date: 2018/10/29
 * Time: 16:12
 */

namespace app\api\model;
use app\common\model\AccountRecordModel;
use app\common\model\Currency;
use app\common\model\CurrencyAction;
use think\Db;

/**
 * 佣金计算
 * Class CommissionModel
 * @package app\api\model
 */
class CommissionModel
{


    public function test(){
        $userId = intval($_POST['userId']);
        $end = $userId+20;
        for($i = $userId; $i < $end; $i++){
            $user = Db::name('user')->find($i);
            if(!$user){
                continue;
            }
            $order['order_id'] = $i;
            $order['user_id'] = $user['user_id'];
            $order['order_fullname'] = 'name'.$i;
            $order['order_mobile'] = 'mobile'.$i;
            $order['product_name'] = 'product'.$i;
            $order['commission'] = 100 + $i;
            $order['path'] = $user['path'];
            $order['reid'] = $user['reid'];
            $order['sid'] = $user['sid'];
            $order['level'] = $user['level'];
            $pathUser = self::getParentUserList($order);
            $reward = self::getReward($order['commission']);
            $rewardUserData = self::getRewardUser($pathUser, $order['level'], $reward);
            self::execReward($rewardUserData, $order);
        }
    }


    /**
     * 佣金主函数
     * @param $orderId
     */
    public function build($orderId){
        //1.验证订单数据
        $where['o.order_id'] = $orderId;
        $where['o.order_status'] = 2;
        $where['o.order_refundstatus'] = 0;
        $where['o.order_exchangestatus'] = 0;
        $where['o.order_del'] = 1;
        $order = Db::name('order o')
                ->field('o.order_id, o.order_no, o.user_id, o.order_status,o.order_payfee,o.order_fullname,o.order_mobile,o.order_no,p.product_name,p.product_property,p.num,p.commission,p.product_returnall,o.order_reservation as product_reservation,p.op_id,u.path, u.reid, u.sid, u.level')
                ->join('jay_order_product p', 'p.order_id = o.order_id', 'left')
                ->join('jay_user u', 'u.user_id = o.user_id', 'left')
                ->where($where)
                ->find();
        if(!$order){
            file_put_contents('./runtime/commisstion_error.log', "\n订单不存在id=$orderId", FILE_APPEND);
            return false;
        }
        if($order['product_returnall'] == 1){
            //新人免单，不分佣金
            return true;
        }
        $pathUser = self::getParentUserList($order);
        $reward = self::getReward($order['commission']);
        $rewardUserData = self::getRewardUser($pathUser, $order['level'], $reward);
        $res1 = self::execReward($rewardUserData, $order);
        if($res1){
            $accessToken = Db::name('access_token')->find(1);
            $this->tplReward($rewardUserData, $order, $accessToken['access_token']);
            $this->tplself($order, $accessToken, $accessToken['access_token']);
            return true;
        }else{
            return false;
        }
    }

    /**
     * 上线佣金推送
     * @param $rewardUserData
     * @param $order
     */
    private function tplReward($rewardUserData, $order, $accessToken){
        $otm = new OpenTmModel();
        foreach ($rewardUserData as $k=>$v){
            $data['title'] = '您的店铺有客户成功下单';
            $data['keyword1'] = $order['product_name'];
            $data['keyword2'] = $order['order_no'];
            $data['keyword3'] = $v['reward']."元\r\n客户姓名：".$order['order_fullname']."\r\n客户电话：".$order['order_mobile'];
            $data['remark'] = '店铺佣金的实际到账实际以返佣记录为准';
            $otm->sendTplmsg5($v['openid'], $data, $accessToken);
        }
    }

    /**
     * 支付成功通知
     * @param $rewardUserData
     * @param $order
     */
    private function tplself($order, $accessToken){
        $userconect = Db::name('user_connect')->where(['user_id'=>$order['user_id'], 'platform'=>'wechat'])->find();
        $otm = new OpenTmModel();
        $data['title'] = '您的订单已支付成功';
        $data['keyword1'] = $order['product_name'];
        $data['keyword2'] = $order['order_no'];
        if(order['consume_code'] != ''){
            $data['keyword2'] = $order['order_no']."\r\n电子码：".$order['consume_code'];
        }
        $data['keyword3'] = $order['order_payfee'];
        $data['keyword4'] = date('Y-m-d H:i:s');
        $data['remark'] = '欢迎再次购买';
        $otm->sendTplmsg3($userconect['openid'], $data, $accessToken);
    }

    /**
     * 发放佣金
     * @param $rewardUserData
     */
    private function execReward($rewardUserData, $order){
        $arm = new AccountRecordModel();
        $opdata = [];
        foreach ($rewardUserData as $k=>$reward){
            $res = $arm->add($reward['user_id'], $order['order_id'], Currency::Commission, $reward['action'], $reward['reward'], $arm->getRecordAttach($reward['user_id'], $order['order_fullname'], $order['order_no']), $reward['rewardlabel'], 1);
            switch ($reward['action']){
                case CurrencyAction::CommissionFirst:
                    $opdata['commis_first'] = $reward['reward'];
                    $opdata['userid_first'] = $reward['user_id'];
                    break;
                case CurrencyAction::CommissionSecond:
                    $opdata['commis_second'] = $reward['reward'];
                    $opdata['userid_second'] = $reward['user_id'];
                    break;
                case CurrencyAction::CommissionOperations:
                    $opdata['commis_operations'] = $reward['reward'];
                    $opdata['userid_operations'] = $reward['user_id'];
                    break;
                case CurrencyAction::CommissionOperationsChilds:
                    $opdata['commis_operations_child'] = $reward['reward'];
                    $opdata['userid_operations_child'] = $reward['user_id'];
                    break;
                case CurrencyAction::CommissionPlayerhostChild:
                    $opdata['commis_playerhost_child'] = $reward['reward'];
                    $opdata['userid_playerhost_child'] = $reward['user_id'];
            }
        }
        //2.更新订单分佣记录
        if($opdata){
            $res = Db::name('order_product')->where(['op_id'=>$order['op_id']])->update($opdata);
            if($res !== false){
                return true;
            }
            return false;
        }else{
            return true;
        }
    }

    private function getRewardUser($pathUser, $buyerLevel=1, $reward){
        $rewardUser = [];
        $hasYunying = false; //运营佣金标志
        $hashoster = false; //玩主奖金标志
        foreach ($pathUser as $i=>$user){
            //1.一级佣金第一个人拿
            if($i == 0){
                $vo = $user;
                $vo['action'] = CurrencyAction::CommissionFirst;
                $vo['reward'] = $reward['first'];
                $vo['user_id'] = $pathUser[0]['user_id'];
                $rewardUser[] = $vo;
            }
            //2.上级佣金
            if($i == 1){
                if($buyerLevel == 1){
                    //2.1购买者是普通会员，上级佣金=第一个人拿
                    $vo = $user;
                    $vo['action'] = CurrencyAction::CommissionSecond;
                    $vo['reward'] = $reward['second'];
                    $vo['user_id'] = $pathUser[0]['user_id'];
                    $rewardUser[] = $vo;
                }elseif($pathUser[0]['level']==2){
                    //2.2一级是会员，上级佣金=第二个人拿
                    $vo = $user;
                    $vo['action'] = CurrencyAction::CommissionSecond;
                    $vo['reward'] = $reward['second'];
                    $vo['user_id'] = $pathUser[1]['user_id'];
                    $rewardUser[] = $vo;
                }elseif($pathUser[0]['level']>=3){
                    //2.3一级是分销达 及 级别以上人，上级佣金=一级的人拿
                    $vo = $user;
                    $vo['action'] = CurrencyAction::CommissionSecond;
                    $vo['reward'] = $reward['second'];
                    $vo['user_id'] = $pathUser[0]['user_id'];
                    $rewardUser[] = $vo;
                }
            }
            //3.运营佣金
            if(!$hasYunying && ($user['level'] == 4 || $user['level'] == 5)){
                $hasYunying = true;
                if($user['level'] == 5){//如果上层第一个是玩主，则玩主拿运营佣金，不拿玩主奖金
                    $hashoster = true;
                }
                $vo = $user;
                $vo['action'] = CurrencyAction::CommissionOperations;
                $vo['reward'] = $reward['operations'];
                $vo['user_id'] = $user['user_id'];
                $rewardUser[] = $vo;
                //4.运营奖金
                if($user['level'] == 4 && $pathUser[$i+1]['level'] == 4){
                    $vo = $user;
                    $vo['action'] = CurrencyAction::CommissionOperationsChilds;
                    $vo['reward'] = $reward['operationschild'];
                    $vo['user_id'] = $pathUser[$i+1]['user_id'];
                    $rewardUser[] = $vo;
                }
            }
            //5.玩主奖金
            if(!$hashoster && $hasYunying && $user['level'] == 5){
                $hashoster = true;
                $vo = $user;
                $vo['action'] = CurrencyAction::CommissionPlayerhostChild;
                $vo['reward'] = $reward['playerhost'];
                $vo['user_id'] = $user['user_id'];
                $rewardUser[] = $vo;
            }

        }
        foreach ($rewardUser as $k=>$v){
            $rewardUser[$k]['rewardlabel'] = CurrencyAction::getLabel($v['action']);
        }
        return $rewardUser;
    }


    /**
     * 计算佣金
     * @param $commission
     */
    public function getReward($commission){
        $config = Db::name('parameter')->column('value', 'key');
        //1.一级佣金
        $return['first'] = sprintf("%.2f", $commission * $config['first_com_per']/100);
        //2.上级佣金
        $return['second'] = sprintf("%.2f", $commission * $config['second_com_per']/100);
        //3.运营佣金
        $return['operations'] = sprintf("%.2f", $commission * $config['operations_com_per']/100);
        //4.运营奖金
        $return['operationschild'] = sprintf("%.2f", $return['operations'] * $config['operations_child_per']/100);
        //5.玩主奖金
        $return['playerhost'] = sprintf("%.2f", $return['operations'] * $config['playerhost_child_per']/100);

        return $return;
    }

    /**
     * @param $commission  商品佣金
     * @param $userlevel   用户等级数
     * @param $params      表参数
     * @return int
     */
    public function getRewardByUser($commission, $userlevel, $params){

         return 0;
    }


    /**
     * 获取上级用户列表
     * @param $order
     */
    private function getParentUserList($order){
        $data = [];
        //普通会员主动购买，不分佣金
        if($order['level'] == 1 && $order['sid'] == 0){
            return $data;
        }
        $where = ' 1=1 ';
        //普通会员购买,点击分享链接后随时购买
        if($order['level'] == 1 && $order['sid'] > 0){
            $suser = Db::name('user')->where(['user_id'=>$order['sid']])->find();
            if(!$suser){
                return $data;
            }
            if($suser['level'] == 1){
                //一级也是普通会员，不发佣金
                return $data;
            }else{
                $repath = $suser['path'].$suser['user_id'].',';
                $where = "FIND_IN_SET(u.user_id, '".$repath."')";
            }
        }
        //超级达人、营销达人、运营，玩主购买
        if($order['level'] > 1){
            $where = "FIND_IN_SET(u.user_id, '".$order['path'].$order['user_id'].",')";
        }
        $where.= " and c.platform = 'wechat'";
        $data = Db::name('user u')
                ->field('u.user_id, u.recode, u.mobile, u.`path`, u.`reid`, u.`sid`, u.`level`, u.`floor`, u.`status`, c.openid')
                ->join('jay_user_connect c', 'c.user_id = u.user_id', 'left')
                ->where($where)->order('u.floor desc')->select();
        return $data;
    }

}