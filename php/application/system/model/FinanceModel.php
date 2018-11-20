<?php
/**
 * Created by PhpStorm.
 * User: Admini
 * Date: 2018/11/16
 * Time: 14:48
 */

namespace app\system\model;


use app\common\model\PayMethod;
use app\common\model\Paymodel;
use think\Db;

class FinanceModel
{

    public function getList($map=array(), $pagenow=1, $pagesize=10){
        //查询总记录
        $count = Db::name('manage_finance')->where($map)->count();

        $list = Db::name('manage_finance')->where($map)
            ->page($pagenow, $pagesize)
            ->order(' total_tag desc')
            ->select();
        $return['heji'] = [];
        foreach ($list as $k=>$v){
            $list[$k]['total_tag'] = substr($v['total_tag'],0,4).'-'.substr($v['total_tag'],4,2).'-'.substr($v['total_tag'],6,2);
            $return['heji']['total_order_business'] += $v['total_order_business'];
            $return['heji']['total_order_payfee'] += $v['total_order_payfee'];
            $return['heji']['total_order_coupon'] += $v['total_order_coupon'];
            $return['heji']['total_order_settle'] += $v['total_order_settle'];
            $return['heji']['total_order_productnum'] += $v['total_order_productnum'];
            $return['heji']['total_order_addfee'] += $v['total_order_addfee'];
            $return['heji']['total_order_commission'] += $v['total_order_commission'];
            $return['heji']['total_jiesuan_commission'] += $v['total_jiesuan_commission'];
            $return['heji']['total_withdraw'] += $v['total_withdraw'];
            $return['heji']['total_taxfee'] += $v['total_taxfee'];
            $return['heji']['total_merchant_settle'] += $v['total_merchant_settle'];
            $return['heji']['total_business_user'] += $v['total_business_user'];
            $return['heji']['total_active_user'] += $v['total_active_user'];
            $return['heji']['total_level2_user'] += $v['total_level2_user'];
            $return['heji']['total_level3_user'] += $v['total_level3_user'];
            $return['heji']['total_level4_user'] += $v['total_level4_user'];
            $return['heji']['total_level2_user'] += $v['total_level5_user'];
        }
        $return['list'] = $list;
        //总计
        $return['zongji'] = Db::name('manage_finance')->field(
            "sum(total_order_business) total_order_business,".
            "sum(total_order_payfee) total_order_payfee,".
            "sum(total_order_coupon) total_order_coupon,".
            "sum(total_order_settle) total_order_settle,".
            "sum(total_order_productnum) total_order_productnum,".
            "sum(total_order_addfee) total_order_addfee,".
            "sum(total_order_commission) total_order_commission,".
            "sum(total_jiesuan_commission) total_jiesuan_commission,".
            "sum(total_withdraw) total_withdraw,".
            "sum(total_taxfee) total_taxfee,".
            "sum(total_merchant_settle) total_merchant_settle,".
            "sum(total_business_user) total_business_user,".
            "sum(total_active_user) total_active_user,".
            "sum(total_level2_user) total_level2_user,".
            "sum(total_level3_user) total_level3_user,".
            "sum(total_level4_user) total_level4_user,".
            "sum(total_level5_user) total_level5_user"
        )->where($map)->find();
        $pagination = new PaginationModel();
        $return['page'] = $pagination->getPage($count, $pagenow, $pagesize);
        return $return;
    }




    public function getOrderList($map=array(), $pagenow=1, $pagesize=10){
        //查询总记录
        $count = Db::table('view_orderFinance')->where($map)->count();

        $list = Db::table('view_orderFinance')
            ->where($map)
            ->page($pagenow, $pagesize)
            ->order('order_id desc')
            ->select();
        foreach ($list as $k=>$v){
            $list[$k]['order_payment'] = PayMethod::getLabelBynumber($v['order_payment']);
            $list[$k]['order_addtime'] = date('Y-m-d H:i:s', $v['order_addtime']);
            $list[$k]['order_paytime'] = date('Y-m-d H:i:s', $v['order_paytime']);

        }
        $return['list'] = $list;
        $return['heji'] = [];
        foreach ($list as $k=>$v){
            $return['heji']['order_totalfee'] += $v['order_totalfee'];
            $return['heji']['payamount'] += $v['payamount'];
            $return['heji']['coupon'] += $v['coupon'];
            $return['heji']['num'] += $v['num'];
            $return['heji']['totalsettle'] += $v['totalsettle'];
            $return['heji']['totalmoney'] += $v['totalmoney'];
            $return['heji']['commis_first'] += $v['commis_first'];
            $return['heji']['commis_second'] += $v['commis_second'];
            $return['heji']['commis_operations'] += $v['commis_operations'];
            $return['heji']['commis_operations_child'] += $v['commis_operations_child'];
            $return['heji']['commis_playerhost_child'] += $v['commis_playerhost_child'];
        }

        $pagination = new PaginationModel();
        $return['page'] = $pagination->getPage($count, $pagenow, $pagesize);
        return $return;
    }


    /**
     * 商家消单统计
     */
    public function getMerchantOrderList($oType, $map=array(), $pagenow=1, $pagesize=10){
        //查询总记录
        if($oType == 1){//到店订单
            $table = 'view_orderMerchantDaodianFinance';
        }else{  //快递订单
            $table = 'view_orderMerchantKuaidiFinance';
        }
        $count = Db::table($table)->where($map)->count();

        $list = Db::table($table)
            ->where($map)
            ->page($pagenow, $pagesize)
            ->order('order_id desc')
            ->select();
        foreach ($list as $k=>$v){
            $list[$k]['order_payment'] = PayMethod::getLabelBynumber($v['order_payment']);
            $list[$k]['order_addtime'] = date('Y-m-d H:i:s', $v['order_addtime']);
            $list[$k]['order_paytime'] = date('Y-m-d H:i:s', $v['order_paytime']);
            $list[$k]['comsumeaddtime'] = date('Y-m-d H:i:s', $v['comsumeaddtime']);
            if($v['order_reservation'] == 1){
                $list[$k]['order_reservation'] = '预约制';
            }elseif($v['order_reservation'] == 2){
                $list[$k]['order_reservation'] = '免预约';
            }
        }
        $return['list'] = $list;
        $pagination = new PaginationModel();
        $return['page'] = $pagination->getPage($count, $pagenow, $pagesize);
        return $return;
    }
}