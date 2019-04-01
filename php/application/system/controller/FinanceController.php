<?php
/**
 * Created by PhpStorm.
 * User: Admini
 * Date: 2018/11/10
 * Time: 17:05
 */

namespace app\system\controller;


use app\common\AdminBaseController;
use app\common\model\ManageFinanceModel;
use app\common\model\PayMethod;
use app\common\model\Tag;
use app\system\model\ExcelModel;
use app\system\model\FinanceModel;
use app\system\model\UserModel;
use Think\Db;

/**
 * 平台的财务统计
 * Class FinanceController
 * @package app\system\controller
 */
class FinanceController extends AdminBaseController
{

    public function index(){

    }


    /**
     * 平台收支统计
     */
    public function table(){
        $page = $this->get('page', 1);
        $starttime = $this->get('starttime', '');
        $endtime = $this->get('endtime', '');
        $where['total_tag'] = ['gt', 20181001];
        if($starttime != ''){
            $where['total_tag'] = array('between', str_replace('-','',$starttime).',20301201');
            $this->assign('starttime', $starttime);
        }
        if($endtime != ''){
            $where['total_tag'] = array('between', '20181001,'.str_replace('-','',$endtime));
            $this->assign('endtime', $endtime);
        }
        if($starttime != '' && $endtime != ''){
            $where['total_tag'] = array('between', str_replace('-','',$starttime).','.str_replace('-','',$endtime));
        }
        $fm = new FinanceModel();
        $data = $fm->getList($where, $page);
        $this->assign('data', $data);
        $this->assign('now', date('Y-m-d'));

        //统计
        $um = new UserModel();
        $total = $um->getBalanceCount();
        $this->assign("total", $total);
        //今日结算佣金
        $jrjs_amount = Db::name('account_cash'.date('Ym'))->where(['record_action'=>['in','802,803'], 'record_addtime'=>['gt', strtotime(date('Y-m-d'))]])->sum('record_amount');
        $this->assign("jrjs_amount", $jrjs_amount);
        //提现中
        $txz_amount = Db::name('user_withdraw')->where(['withdraw_status'=>['in', '0,2,3']])->sum('withdraw_amount');
        $this->assign("txz_amount", $txz_amount);
        //提现成功
        $txcg_amount = Db::name('user_withdraw')->where(['withdraw_status'=>6])->sum('withdraw_amount');
        $this->assign("txcg_amount", $txcg_amount);

        //最近5个月的数据
        $lw['total_tag'] = ['between', '1,'.date('Ym')];
        $lear5month = Db::name('manage_finance')->where($lw)->order('total_tag desc')->limit(0,5)->select();
        $this->assign("lear5month", $lear5month);

        return $this->display('table', true);
    }

    /**
     * 根据指定日期计算统计数据
     */
    public function jisuan(){
        $day = $this->post('day', '');
        $starttime = strtotime($day);
        $endtime = $starttime+86400;
        $this->refreshData($starttime, $endtime, $day);
    }


    public function jisuan2(){
        $tag = $this->post('day', '');
        $month = substr($tag, 0, 4).'-'.substr($tag,4,2);
        $month1 = $month.'-01';
        $month2 = $month.'-31';
        $starttime = strtotime($month1);
        $endtime = strtotime($month2);
        //统计订单数据
        $this->refreshData($starttime, $endtime, $tag);
    }


    private function refreshData($starttime, $endtime, $day){
        //统计订单数据，包含全部状态
        $sql1 ="select ".
            " sum(o.order_totalfee) total_order_business,".
            "   sum(a.payamount) total_order_payfee,".
            "   sum(a.coupon) total_order_coupon,".
            "   sum(p.totalsettle) total_order_settle,".
            "   sum(p.num) total_order_productnum,".
            "   sum(p.commis_free+p.commis_first+p.commis_second+p.commis_operations+p.commis_operations_child+p.commis_playerhost_child+p.commis_playerhost_zhishu) total_order_commission ".
            "    from jay_order o".
            "   left join jay_order_product p on p.order_id = o.order_id".
            "   left join jay_order_affiliated a on a.order_id = o.order_id".
            "   where  o.order_status>1 and o.order_paytime BETWEEN ".$starttime." and ".$endtime;
        $data = Db::query($sql1);
        $data[0]['total_order_commission'] = $data[0]['total_order_commission'];
        //总预约加价金额
        $wr['reservation_status'] = ['gt', 0];
        $wr['reservation_addtime'] = ['between', $starttime.','.$endtime];
        $data[0]['total_order_addfee'] = Db::name('order_user_reservation')->where($wr)->sum('reservation_addprice');

        $wj['record_addtime'] = ['between', $starttime.','.$endtime];
        $wj['record_action'] = ['in', '651,652'];
        //已结算
        $yjs = Db::name('account_commission'.date('Ym',$starttime))->where($wj)->sum('record_amount');
        $data[0]['total_jiesuan_commission'] = abs($yjs);
        //已手续费
        $wj['record_action'] = ['in', '853,854,855,856'];
        $data[0]['total_taxfee'] = Db::name('account_cash'.date('Ym',$starttime))->where($wj)->sum('record_amount');
        //商家结算
        $mwj['record_action'] = 952;
        $mwj['record_addtime'] = ['between', $starttime.','.$endtime];
        $sjjs = Db::name('merchant_account'.date('Ym',$starttime))->where($mwj)->sum('record_amount');
        $data[0]['total_merchant_settle'] = abs($sjjs);
        //提现
        $uwt['withdraw_uptime'] = ['between', $starttime.','.$endtime];
        $uwt['withdraw_status'] = 6;
        $uwt['withdraw_code'] = 'success';
        $data[0]['total_withdraw'] = Db::name('user_withdraw')->where($uwt)->sum('withdraw_amount');
        //后台扣除佣金
        $kcwr['record_action'] = 654;
        $kcwr['record_addtime'] = ['between', $starttime.','.$endtime];
        $deccom = Db::name('account_commission'.date('Ym',$starttime))->where($kcwr)->sum('record_amount');
        $data[0]['total_deduction_commission'] = abs($deccom);
        //后台扣除现金
        $kcwr['record_action'] = 859;
        $kcwr['record_addtime'] = ['between', $starttime.','.$endtime];
        $deccom = Db::name('account_cash'.date('Ym',$starttime))->where($kcwr)->sum('record_amount');
        $data[0]['total_deduction_cash'] = abs($deccom);
        //升级自动奖励
        $kcwr['record_action'] = ['in','610'];
        $kcwr['record_addtime'] = ['between', $starttime.','.$endtime];
        $deccom = Db::name('account_commission'.date('Ym',$starttime))->where($kcwr)->sum('record_amount');
        $data[0]['total_reward'] = abs($deccom);
        //单品销售奖励
        $kcwr['record_action'] = ['in','608'];
        $kcwr['record_addtime'] = ['between', $starttime.','.$endtime];
        $deccom = Db::name('account_commission'.date('Ym',$starttime))->where($kcwr)->sum('record_amount');
        $data[0]['total_rewardback'] = abs($deccom);
        //10万每日奖励
        $kcwr['record_action'] = ['in','611'];
        $kcwr['record_addtime'] = ['between', $starttime.','.$endtime];
        $deccom = Db::name('account_commission'.date('Ym',$starttime))->where($kcwr)->sum('record_amount');
        $data[0]['total_reward10'] = abs($deccom);
        $up['total_tag'] =str_replace('-','',$day);
        Db::name('manage_finance')->where($up)->update($data[0]);
    }



    /**
     * 按订单
     */
    public function order(){
        $page = $this->get('page', 1);
        $where = $this->orderWhere();
        $fm = new FinanceModel();
        $data = $fm->getOrderList($where['where'], $page);
        $this->assign('data', $data);

        $cates = Db::name('product_categoryfinance')->select();
        $this->assign('cates', $cates);
        return $this->display('order', true);
    }


    /**
     * 导出数据
     */
    public function exportOrder(){
        $where = $this->orderWhere();
        $fm = new FinanceModel();
        $data = $fm->getOrderList($where['where'], 1, 9000000);
        $column = ['订单id', '订单编号', '第三方交易流水号', '客户姓名', '客户电话', '订单总金额', '到店/快递', '现金抵扣金额', '优惠券抵扣金额', '实际付款金额', '商家名称', '支付方式', '下单时间', '付款时间', '商品id', '商品名称', '型号','抢购开始时间','抢购结束时间', '数量', '单价', '成本', '订单商品总金额', '订单商品成本','新人免单佣金', '一级佣金', '上级佣金', '运营佣金', '运营奖金', '玩主奖金', '直属玩主奖', '退款状态', '退款时间', '退款方式','退款数量','退款总成本','退款佣金','退款金额'];
        foreach ($data['list'] as $k=>$v){
            $data['list'][$k]['colorstyle'] = '';
            if($v['commis_free'] > 0){
                $data['list'][$k]['colorstyle'] = 'blue';
            }
            if($v['order_refundstatus'] == 3){
                $data['list'][$k]['order_refundstatus'] = '已退款';
                $data['list'][$k]['colorstyle'] = 'red';
            }
            unset($data['list'][$k]['catefinance_id']);
            if($v['refund_type'] == 1){
                $data['list'][$k]['refund_type'] = '退款并退佣金';
            }elseif($v['refund_type'] == 2){
                $data['list'][$k]['refund_type'] = '退佣金不退款';
            }elseif($v['refund_type'] == 3){
                $data['list'][$k]['refund_type'] = '退款不退佣金';
            }elseif($v['refund_type'] == 4){
                $data['list'][$k]['refund_type'] = '退指定金额(不退佣金)';
            }
            $data['list'][$k]['product_starttime'] = date2('Y-m-d H:i:s', $v['product_starttime']);
            $data['list'][$k]['product_endtime'] = date2('Y-m-d H:i:s', $v['product_endtime']);
        }
        $em = new ExcelModel();
        $this->log("导出平台订单统计");
        $em->export($column, $data['list'], $where['title'], '订单报表', $where['title']);
    }


    /**
     * 导出统计
     */
    public function exporttable(){
        $starttime = $this->get('starttime', '');
        $endtime = $this->get('endtime', '');
        $where['total_tag'] = ['gt', 20181001];
        if($starttime != ''){
            $where['total_tag'] = array('between', str_replace('-','',$starttime).',20301201');
            $this->assign('starttime', $starttime);
        }
        if($endtime != ''){
            $where['total_tag'] = array('between', '20181001,'.str_replace('-','',$endtime));
            $this->assign('endtime', $endtime);
        }
        if($starttime != '' && $endtime != ''){
            $where['total_tag'] = array('between', str_replace('-','',$starttime).','.str_replace('-','',$endtime));
        }
        $list = Db::name('manage_finance')->where($where)->order('total_tag desc')->select();
        foreach ($list as $k=>$v){
            unset($list[$k]['finance_id']);
            unset($list[$k]['total_active_user']);
            unset($list[$k]['total_level2_user']);
            unset($list[$k]['total_level3_user']);
            unset($list[$k]['total_level4_user']);
            unset($list[$k]['total_level5_user']);
            $list[$k]['total_tag'] = substr($v['total_tag'],0,4).'-'.substr($v['total_tag'],4,2).'-'.substr($v['total_tag'],6,2);
        }
        $column = ['代收总额', '代收总额', '优惠券总面额', '结算总额', '推广产品销售数量', '预约加价总额', '佣金总额', '退款总额', '退款扣除会员佣金', '退款扣除会员现金', '已结算佣金总额', '提现总额', '手续费总额', '商家结算总金额', '交易用户量', '升级奖励佣金', '单品奖励佣金', '单日奖励佣金', '日期'];
        $em = new ExcelModel();

        $this->log("导出平台统计报告");
        $em->export($column, $list, '导出统计数据', '统计报表', '统计报表');
    }

    private function orderWhere(){
        $where=array();
        $catId = $this->get('catId', 0);
        $payType = $this->get('payType', 0);
        $orefund = $this->get('orefund', 0);
        $ofee = $this->get('ofee', 0);
        $mname = $this->get('mname', '');
        $pid = $this->get('pid', 0);
        $orderNo = $this->get('orderNo', '');
        $starttime = $this->get('starttime', '');
        $endtime = $this->get('endtime', '');
        $return['title'] = '筛选条件：';
        //2.下单时间
        if($starttime != '' && $endtime != ''){
            $where['order_addtime'] = array('between', strtotime($starttime).','.strtotime($endtime));
            $return['title'] .= '时间范围['.$starttime.'至'.$endtime.']&';
            $this->assign('starttime', $starttime);
            $this->assign('endtime', $endtime);
        }else{
            if($starttime != ''){
                $where['order_addtime'] = array('between', strtotime($starttime).','.time());
                $this->assign('starttime', $starttime);
                $return['title'] .= '开始时间:'.$starttime.'&';
            }
            if($endtime != ''){
                $where['order_addtime'] = array('between', '0,'.strtotime($endtime));
                $this->assign('endtime', $endtime);
                $return['title'] .= '截止时间:'.$endtime.'&';
            }
        }

        //3.订单号
        if($orderNo != ''){
            $where['order_no'] = $orderNo;
            $this->assign('orderNo', $orderNo);
            $return['title'] .= '订单号:'.$orderNo.'&';
        }
        //4.商家名称
        if($mname != ''){
            $where['merchant_name'] = $mname;
            $this->assign('mname', $mname);
            $return['title'] .= '商家:'.$mname.'&';
        }
        //5.指定商品
        if($pid > 0){
            $where['product_id'] = $pid;
            $this->assign('pid', $pid);
            $return['title'] .= '商品编号：'.$pid.'&';
        }
        //6.分类
        if($catId > 0){
            $where['catefinance_id'] = $catId;
            $this->assign('catId', $catId);
            $catName = Db::name('product_categoryfinance')->where(['category_id'=>$catId])->value('category_name');
            $return['title'] .= '分类:'.$catName.'&';
        }
        //7.支付方式
        if($payType > 0){
            $where['order_payment'] = $payType;
            $this->assign('payType', $payType);
            $return['title'] .= '支付方式：'.PayMethod::getLabelBynumber($payType);
        }
        //8.新人免单
        $this->assign('ofee', $ofee);
        if($ofee ==2){
            $where['commis_free'] = 0;
        }elseif($ofee ==1){
            $where['commis_free'] = ['gt',0];
        }
        $this->assign('orefund', $orefund);
        if($orefund ==1){
            $where['order_refundstatus'] = 3;
        }elseif($orefund ==2){
            $where['order_refundstatus'] = 0;
        }
        $return['title'] = trim($return['title'] , '&');
        $return['where'] = $where;
        return $return;
    }


    /**
     * 统计商家消单
     */
    public function merchant(){
        $page = $this->get('page', 1);
        $oType = $this->get('oType', 1);
        $where = $this->orderWhere();
        $fm = new FinanceModel();
        $data = $fm->getMerchantOrderList($oType, $where['where'], $page);
        $this->assign('data', $data);

        $cates = Db::name('product_categoryfinance')->select();
        $this->assign('cates', $cates);
        return $this->display('merchant'.$oType, true);
    }

    public function exportMerchant(){
        $oType = $this->get('oType', 1);
        $where = $this->orderWhere();
        $fm = new FinanceModel();
        $data = $fm->getMerchantOrderList($oType, $where['where'], 1, 9000000);
        if($oType == 1){
            $column = ['消单id', '消单时间', '商家备注', '预约支付方式', '预约加价', '商城订单id', '订单号', '电子码', '客户姓名', '客户电话', '预约类型', '订单总金额', '订单支付方式', '付款时间','下单时间', '现金抵扣', '优惠券面额', '支付金额', '商品id', '商品名称', '属性', '数量', '单价', '成本', '订单商品总金额', '订单商品成本', '新人免单佣金','一级佣金', '上级佣金', '运营佣金', '运营奖金', '玩主奖金', '直属玩主奖', '分类','商家名称','退款状态','退款时间'];
        }else{
            $column = ['订单id', '订单编号', '客户姓名', '客户电话', '订单总金额', '订单支付方式', '付款时间', '到店/快递', '预约类型', '下单时间', '现金抵扣金额', '优惠券抵扣金额', '实际付款金额', '商品id', '商品名称', '型号', '数量', '单价', '成本', '订单商品总金额', '订单商品成本','新人免单佣金', '一级佣金', '上级佣金', '运营佣金', '运营奖金', '玩主奖金', '直属玩主奖', '分类','商家名称','退款状态','退款时间'];
        }
        foreach ($data['list'] as $k=>$v){
            if($v['order_refundstatus'] == 3){
                $data['list'][$k]['order_refundstatus'] = '已退款';
            }
        }
        $em = new ExcelModel();
        $this->log("导出商家订单统计");
        $em->export($column, $data['list'], $where['title'], '商家订单报表', ($oType==1?'到店':'快递').$where['title']);
    }
}