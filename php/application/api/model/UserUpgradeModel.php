<?php
/**
 * Created by PhpStorm.
 * User: Admini
 * Date: 2018/11/3
 * Time: 13:50
 */

namespace app\api\model;
use think\Db;

/**
 * 会员升级[超级会员 升级成为 营销达人； 营销达人 升级成为 运营达人]
 * Class UserUpgradeModel
 * @package app\api\model
 */
class UserUpgradeModel
{

    /**
     * 检测用户升级
     * @param $user_id
     * @param $event  1注册；2购买
     */
    public function check($user_id, $event){
        $user = Db::name('user')->find($user_id);
        $userlist = self::getParentUserList($user, $event);
        $config = Db::name('parameter')->column('value', 'key');
        //升级营销达人参数
        $config_2_3 = ['amount'=>$config['distributor_com_amount'], 'count1'=>$config['distributor_com_firstpeople'], 'count2'=>$config['distributor_com_secondpeople'],];
        //升级运营达人参数
        $config_3_4 = ['amount'=>$config['operations_com_amount'], 'count1'=>$config['operations_com_firstpeople'], 'count2'=>$config['operations_com_secondpeople'],];
        foreach ($userlist as $user){
            $tag = false;
            if($user['level'] == 2){
                $tag = self::compareData($user, $config_2_3);
            }elseif($user['level'] == 3){
                $tag = self::compareData($user, $config_3_4);
            }
            if($tag){
                //升级
                self::upgrade($user);
            }
        }
    }



    private function compareData($user, $param){
        //1.验证自购金额
        $yongjin = Db::name('account_finance')->field('finance_first+finance_second+finance_operations+finance_operationchilds+finance_playerhost as amount')->where(['user_id'=>$user['user_id'], 'finance_tag'=>0])->find();
        $today = date('Y-m-d');
        $todayyongjin = Db::name('account_commission'.date('Ym'))->field('ifnull(sum(record_amount),0) as amount')->where(['user_id'=>$user['user_id'], 'record_action'=>['in', '601,602,603,604,606'], 'record_addtime'=>['between', [strtotime($today), strtotime($today.' 23:59:59')]]])->find();
        if($yongjin['amount'] + $todayyongjin['amount'] < $param['amount']){
            return false;
        }
        //2.验证一级好友数量
        $childcount1 = Db::name('user')->where(['reid'=>$user['user_id']])->count();
        if($childcount1 < $param['count1']){
            return false;
        }
        //2.验证一级好友数量
        $childcount2 = Db::name('user')->where(['path'=>['like', $user['path'].$user['user_id'].',%'], 'floor'=>($user['floor']+2)])->count();
        if($childcount2 < $param['count2']){
            return false;
        }
        return true;
    }


    private function upgrade($user){
        if($user['level'] == 2 || $user['level'] == 3){
            $vo['user_id'] = $user['user_id'];
            $vo['remark'] = $user['level']==2?'超级会员 升级 营销达人':'营销达人 升级 运营达人';
            $vo['addtime'] = time();
            Db::name('user_upgrade')->insertGetId($vo);
            Db::name('user')->where(['user_id'=>$user['user_id']])->update(['level'=>['exp', '`level`+1']]);
        }
    }

    /**
     * 获取上2层用户列表
     * @param $user_id
     * @param $event  1注册；2购买
     */
    private function getParentUserList($user, $event){
        $data = [];
        $where= '';
        if($event == 1 && ($user['level'] == 2 || $user['level'] == 3)){
            $where = "FIND_IN_SET(user_id, '".$user['path']."')";
        }elseif($event == 2){
            //普通会员主动购买，不分佣金，不触发升级
            if($user['level'] == 1 && $user['sid'] == 0){
                return $data;
            }
            //普通会员购买,点击分享链接后随时购买
            if($user['level'] == 1 && $user['sid'] > 0){
                $suser = Db::name('user')->where(['user_id'=>$user['sid']])->find();
                if(!$suser){
                    return $data;
                }
                if($suser['level'] == 1){
                    //一级也是普通会员，不发佣金,不触发升级
                    return $data;
                }else{
                    $repath = $suser['path'].$suser['user_id'].',';
                    $where = "FIND_IN_SET(user_id, '".$repath."')";
                }
            }
            //超级达人、营销达人
            if($user['level'] ==2 ||$user['level'] ==3){
                $where = "FIND_IN_SET(user_id, '".$user['path'].$user['user_id'].",')";
            }
        }else{
            return $data;
        }
        if(!$where){
            return $data;
        }
        $data = Db::name('user')->field('user_id, `path`, `reid`, `level`, `floor`')->where($where)->order('floor desc')->limit(3)->select();
        return $data;
    }
}