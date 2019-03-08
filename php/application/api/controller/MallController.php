<?php
/**
 * Created by PhpStorm.
 * User: Admini
 * Date: 2018/11/6
 * Time: 10:22
 */

namespace app\api\controller;
use app\api\model\HelpModel;
use app\api\model\MallOrderModel;
use app\api\model\OrderModel;
use app\api\model\UserAddressModel;
use app\common\model\Paymodel;
use think\Db;

/**
 * 商城流程
 * Class MallController
 * @package app\api\controller
 */
class MallController extends ApiBaseController
{


    /**
     * 支付确认页面
     */
    public function confirmPay(){
        $product_id  = $this->post('product_id', 0);
        $price_id    = $this->post('price_id', 0);
        $calendar_id = $this->post('calendar_id', '');//支持散购
        $user        = getUserByToken();
        //1.验证商品
        if($price_id > 0){
            $product = $this->verfiyProduct1($product_id, $price_id, $user);
        }else{
            $product = $this->verfiyProduct2($product_id, $calendar_id, $user);
        }

        if ($product["dboss_id"] > 1){
            $product["eden"] = 2;
        }else{
            $product["eden"] = 1;
        }

        $Condition["type"]    = 2;//预约类订单用户信息
        $Condition["user_id"] = $user['user_id'];
        $Condition["status"]  = 1;
        $userAddr             = UserAddressModel::UserAddressFind($Condition);
        $product['concat']    = $userAddr['contact'];
        $product['mobile']    = $userAddr['mobile'];

        if($product["product_numlimit"] == 1){
            $Restrict["op.product_id"] = $product_id;
            $Restrict["o.user_id"]     = $user["user_id"];
            $Restrict[]                = array("exp","o.order_status > 1 ");

            $RestrictCount = OrderModel::UserOrderRestrictCount($Restrict);

            $NumlimitNum = $product["product_numlimit_num"] - $RestrictCount["num"];

            $product["product_numlimit_num"] = $NumlimitNum < 1?0:$NumlimitNum;
        }

        $productdata["price_id"] = $product["price_id"];
        $productdata["product_pic"] = $product["product_pic"];
        $productdata["product_property"] = $product["product_property"];
        $productdata["price_market"] = $product["price_market"];
        $productdata["price_sale"] = $product["price_sale"];

        $productdata["product_id"] = $product["product_id"];
        $productdata["product_name"] = $product["product_name"];
        $productdata["product_returnall"] = $product["product_returnall"];
        $productdata["product_explosion"] = $product["product_explosion"];
        $productdata["product_reservation"] = $product["product_reservation"];
        $productdata["product_isexpress"] = $product["product_isexpress"];
        $productdata["product_timelimit"] = $product["product_timelimit"];
        $productdata["is_shengxian"] = $product["is_shengxian"];
        $productdata["product_numlimit"] = $product["product_numlimit"];
        $productdata["product_numlimit_num"] = $product["product_numlimit_num"];
        $productdata["product_starttime"] = $product["product_starttime"];
        $productdata["product_endtime"] = $product["product_endtime"];
        $productdata["product_startusetime"] = $product["product_startusetime"];
        $productdata["product_endusetime"] = $product["product_endusetime"];
        $productdata["product_sku"] = $product["product_sku"];
        $productdata["price_type"] = $product["price_type"];
        $productdata["price_commission"] = $product["price_commission"];
        $productdata["coupon"] = $product["coupon"];
        $productdata["eden"] = $product["eden"];
        $productdata["concat"] = $product["concat"];
        $productdata["mobile"] = $product["mobile"];

        $this->returnApiData('获取成功', 200, ['product'=>$productdata]);
    }


    /**
     * 提交订单
     */
    public function submitOrder(){
        //检测系统是否关闭
        $config = Db::name('sys_config')->order('sort asc')->column('value', 'field');
        if($config['sys_switch'] == 2){
            $this->returnApiData($config['sys_closeinfo'], 400);
        }
        $product_id           = $this->post('product_id', 0);
        $price_id             = $this->post('price_id', 0);
        $calendar_id          = $this->post('calendar_id', '');//支持散购
        $attach['address_id'] = $this->post('address_id', 0);
        $attach['buynum']     = $this->post('buynum', 1);
        $attach['concat']     = $this->post('concat', '');
        $attach['mobile']     = $this->post('mobile', '');
        $attach['order_idcard']   = $this->post('idcard', '');
        $attach['order_plainday'] = strtotime(date("Y-m-d",$this->post('appointment_date', '')));
        $attach['remark']     = $this->post('remark', '');

        $user = getUserByToken();
        //1.填写信息
        self::Tpl_Chinese($attach['concat'], '联系人姓名格式不正确', 2);
        self::Tpl_Phone($attach['mobile'], '手机号格式不正确', 2);
        if( $attach['buynum']>10){
            $this->returnApiData('该商品一人最多只能购买10份', 400);
        }
        /* 特殊处理 -峨眉山大酒店 必须购买双份 */
        if($product_id == 201){
            if($attach['buynum'] % 2 != 0){
                $this->returnApiData('该商品只能购买偶数份', 400);
            }
        }
        //2.验证商品
        if($price_id > 0){
            //平日价
            $product = $this->verfiyProduct1($product_id, $price_id, $user, $attach['buynum']);

            $product["product_sku"] += $this->verfiyProduct3($product_id, $price_id)["totalnum"];

            if($product['sold_out'] == 1){
                $this->returnApiData('商品已售罄', 400);
            }

            if ($product["dboss_id"] > 1){
                parent::Tpl_Empty($attach["order_idcard"],"请填写你的身份证号码",2);
                parent::Tpl_IdCard($attach["order_idcard"],2);
                parent::Tpl_Empty($attach['order_plainday'],"请选择预约时间",2);

                if ($attach['order_plainday'] < $product["product_startusetime"] && $attach['order_plainday'] > $product["product_endusetime"] ){
                    $this->returnApiData('消费日期不能小于大于商品有效期', 400);
                }
            }

            if($product['product_isexpress'] == 2 && $product['product_reservation'] == 2){
                $addr = Db::name('user_address')->where(['user_id'=>$user['user_id'], 'address_id'=>$attach['address_id']])->find();
                if(!$addr){
                    $this->returnApiData('请选择收货地址', 400);
                }

                if($product["is_shengxian"] === 1 && ($attach['order_plainday'] <= strtotime(date("Y-m-d",time())))){
                     $this->returnApiData('发货时间不能小于等于今日', 400);
                }

            }else{
                $Condition["type"] = 2;//预约类订单用户信息
                $Condition["user_id"]    = $user['user_id'];
                $Condition["status"]     = 1;
                $userAddrData['contact']  = $attach['concat'];
                $userAddrData['mobile']  = $attach['mobile'];
                if($userAddr = UserAddressModel::UserAddressFind($Condition)){
                    $upCondition["user_id"]    = $user['user_id'];
                    $upCondition["status"]     = 1;
                    UserAddressModel::UserAddressUpdate($upCondition,$userAddrData); //信息存在直接覆盖
                }else{
                    $userAddrData['user_id'] = $user['user_id'];
                    $userAddrData['status']  = 1;
                    $userAddrData['type']    = 2;
                    UserAddressModel::UserAddressAdd($userAddrData); //信息不存在记录；
                }
            }
            $order_no = HelpModel::makeOrderNumber();
            Db::startTrans();
            //锁表
            Db::name('product_price')->lock(true)->find($product['price_id']);
            $mo = new MallOrderModel();
            $res = $mo->buildOrder1($order_no, $product, $attach, $user);
            if($res){
                Db::commit();
                $order_id = Db::name('order')->where(['order_no'=>$order_no])->value('order_id');
                $this->returnApiData('下单成功，请立即支付!', 200, ['order_no'=>$order_no, 'order_id'=>$order_id]);
            }else{
                Db::rollback();
                $this->returnApiData('订单提交失败', 400);
            }
        }else{
            //选日期散购
            $product = $this->verfiyProduct2($product_id, $calendar_id, $user);
        }

    }

    /**
     * 立即支付订单
     */
    public function paynow(){
        //检测系统是否关闭
        $config = Db::name('sys_config')->order('sort asc')->column('value', 'field');
        if($config['sys_switch'] == 2){
            $this->returnApiData($config['sys_closeinfo'], 400);
        }
        $order_no = $this->post('order_no', '');
        $payway = $this->post('payway', 1); //1微信公众号支付 2支付宝app；3银行卡；4微信APP
        $user = getUserByToken();
        $where['o.user_id'] = $user['user_id'];
        $where['o.order_no'] = $order_no;
        $where['o.order_status'] = 1;
        $order = Db::name('order o')
            ->field('o.*, p.product_returnall, p.product_id, p.num, pm.product_numlimit, pm.product_numlimit_num')
            ->join('jay_order_product p', 'p.order_id = o.order_id', 'left')
            ->join('jay_product pm', 'pm.product_id = p.product_id', 'left')
            ->where($where)->find();
        if($order){
            //是否过期
            if($order['order_addtime'] < time()-1800){
                $this->returnApiData('订单已经过期，请重新下单', 400);
            }
            if($order['product_returnall'] == 1){
                //已买过新人免单，不能再购买
                $hasOrder = Db::name('order o')
                    ->join('jay_order_product op', 'op.order_id = o.order_id', 'left')
                    ->where(['o.user_id'=>$user['user_id'], 'op.product_returnall'=>1, 'o.order_status'=>['gt', 1], 'op.product_id'=>$order['product_id']])
                    ->find();
                if($hasOrder){
                    $this->returnApiData('该商品的一次新人免单机会您已经使用完了噢', 400);
                }
            }
            //限购
            if($order['product_numlimit'] == 1){
                $OrderNum[] = array("exp","o.user_id = {$user["user_id"]} and op.product_id = {$order['product_id']} and o.order_status > 1");
                $buycount = Db::name('order o')->field('sum(op.num) num')
                            ->join('jay_order_product op', 'op.order_id = o.order_id', 'left')
                            ->where($OrderNum)
                            ->find();
                if($buycount['num']+$order['num'] > $order['product_numlimit_num']){
                    $this->returnApiData('该商品一人只能购买'.$order['product_numlimit_num'].'份', 400);
                }
            }
            Db::name('order')->where(['order_no'=>$order_no])->update(['order_payment'=>$payway]);

            if($payway == 1){
                $openId = Db::name('user_connect')->where(['user_id'=>$user['user_id'], 'platform'=>'wechat'])->value('openid');
                if(!$openId){
                    $this->returnApiData('账号未授权登录服务号', 400);
                }
                $pm = new Paymodel();
                $res = $pm->wxJsPay($openId, $order_no, $order['order_payfee'], 'Notify/mall');
                $this->returnApiData('获取成功', 200, ['jsApiParameters'=>$res]);
            }elseif($payway == 2){
                $this->returnApiData('支付宝支付尚未开通', 400);
            }elseif($payway == 3){
                $this->returnApiData('银行卡支付尚未开通', 400);
            }elseif($payway == 4){
                $pm  = new Paymodel();
                $res = $pm->wxAPPPay($order_no, $order['order_payfee'], 'Notify/mall');
                $this->returnApiData('获取成功', 200, ['jsApiParameters'=>$res]);
            }
        }else{
            $this->returnApiData('订单不存在', 400);
        }
    }

    private function verfiyProduct2($product_id, $price_id, $user){

    }

    private function verfiyProduct1($product_id, $price_id, $user, $buynum=1){
        $product = Db::name('product p')
            ->field('c.*, p.product_name, p.product_status, p.product_del, p.product_reviewstatus,
            p.price_type, p.product_returnall, p.product_reservation, p.product_isexpress, 
            p.product_timelimit,p.is_shengxian,p.product_numlimit, p.product_numlimit_num, p.product_starttime, p.product_endtime, 
            p.product_startusetime, p.product_endusetime, p.merchant_id, p.product_sold, p.sold_out,m.dboss_id')
            ->join('product_price c', 'c.product_id = p.product_id', 'left')
            ->join('merchant m', 'm.merchant_id = p.merchant_id', 'left')
            ->where(['p.product_id'=>$product_id, 'c.price_id'=>$price_id, 'm.merchant_status'=>2, 'c.price_status'=>1])
            ->find();

        if(!$product){
            $this->returnApiData('商品不存在', 400);
        }
        if($product['product_del'] == 1 || $product['product_status'] == 0){
            $this->returnApiData('商品售罄或已下架', 400);
        }
        if ($product['price_status'] == 0){
            $this->returnApiData('商品规格已下架,请重新选择', 400);
        }
        if($product['product_reviewstatus'] != 2){
            $this->returnApiData('商品未上架,请敬请期待', 400);
        }
        if($product['product_buynum']+$product['product_sold'] >= $product['product_totalnum']){
            $this->returnApiData('商品规格已售罄,请选择其它规格', 400);
        }
        if($product['product_buynum']+$product['product_sold']+$buynum > $product['product_totalnum']){
            $this->returnApiData('商品规格库存不足,请重新选择购买数量', 400);
        }
        if($product['price_type'] == 2){
            $this->returnApiData('商品价格类型异常', 400);
        }
        $product['product_sku'] = $product['product_totalnum']-$product['product_buynum']; //剩余库存
        if($product['product_returnall'] == 1){
            if($buynum > 1){
                $this->returnApiData('新人免单商品1人只能买1份', 400);
            }
            //已买过新人免单，不能再购买
            $hasOrder = Db::name('order o')
                ->field("o.order_status,o.order_refundstatus")
                ->join('jay_order_product op', 'op.order_id = o.order_id', 'left')
                ->where(['o.user_id'=>$user['user_id'], 'op.product_returnall'=>1, 'o.order_status'=>['gt', 1], 'op.product_id'=>$product_id])
                ->find();

            if($hasOrder){
                if (($hasOrder["order_status"] == 6 && $hasOrder["order_refundstatus"] != 3) || in_array($hasOrder["order_status"],array(1,2,3,4,7))){
                    $this->returnApiData('该商品新人免单机会您已使用', 400);
                }

                $this->returnApiData('该商品新人免单机会您已使用', 400);
            }
        }
        //限购
        if($product['product_numlimit'] == 1){
            $OrderNum[] = array("exp","o.user_id = {$user["user_id"]} and op.product_id = {$product_id} and o.order_status > 1");

            $buycount = Db::name('order o')->field('sum(op.num) num')
                        ->join('jay_order_product op', 'op.order_id = o.order_id', 'left')
                        ->where($OrderNum)
                        ->find();

            if($buycount['num']+$buynum > $product['product_numlimit_num']){
                $this->returnApiData('该商品一人只能购买'.$product['product_numlimit_num'].'份', 400);
            }
        }
        //限时抢购
        if($product['product_timelimit'] == 1){
            if($product['product_starttime'] > time()){
                $this->returnApiData('该商品'.date('Y-m-d H:i:s',$product['product_starttime']).'开抢，敬请期待！', 400);
            }
            if($product['product_endtime'] < time()){
                $this->returnApiData('该商品'.date('Y-m-d H:i:s',$product['product_endtime']).'抢购时间已结束！', 400);
            }
        }

        $product['coupon'] = $this->verifyCoupon($user, $product_id);

        return $product;
    }

    /**
     * @param $product_id  商品id
     * @param $price_id    规格id
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取商品除购买规格的其它启用规格的剩余库存
     */
    private function verfiyProduct3($product_id, $price_id){
        $condition[] = array("exp","product_id = {$product_id} and price_id <> $price_id and price_status = 1");

        $totalnum = Db::name("product_price")->field("sum(product_totalnum)-sum(product_buynum) as totalnum")->where($condition)->find();

        return $totalnum;
    }

    private function verifyCoupon($user, $product_id){
        $where['pc.product_id'] = $product_id;
        $where['uc.user_id'] = $user['user_id'];
        $where['uc.endtime'] = ['gt', time()];
        $where['uc.status'] = 1;
        $coupon = Db::name('user_coupon uc')->field('uc.*, pc.coupon_money')
                    ->join('jay_product_coupon pc', 'pc.coupon_id = uc.coupon_id', 'left')
                    ->where($where)
                    ->find();
        return $coupon?$coupon:[];
    }

}