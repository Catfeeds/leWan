<?php
/**
 * Created by PhpStorm.
 * User: Admini
 * Date: 2018/11/10
 * Time: 17:05
 */

namespace app\system\controller;


use app\common\AdminBaseController;
use app\common\model\Levelconst;
use app\common\model\ProcedureModel;
use app\system\model\PaginationModel;
use think\Db;

/**
 * 商品销售分析
 * Class FinanceController
 * @package app\system\controller
 */
class ProductfxController extends AdminBaseController
{

    public function index(){
        $page = $this->get('page', 1);
        $pagesize = 20;
        $key = $this->get('key', '');
        $where['p.product_returnall'] = 0;
        $where['p.product_addtime'] = ['gt', '1542816000'];
        $where['pp.pf_amount'] = ['gt', 0];
        if($key != ''){
            $where['p.product_name'] = ['like', '%'.$key.'%'];
            $this->assign('key', $key);
        }
        //查询总记录
        $count = Db::table('jay_product p')->join('jay_product_performance pp', 'pp.product_id = p.product_id and pp.tag=0', 'left')->where($where)->count();

        $list = Db::table('jay_product p')
            ->field('p.product_id, p.product_name, p.sold_out, p.product_status, p.product_addtime, m.merchant_name')
            ->where($where)
            ->join('jay_merchant m', 'm.merchant_id = p.merchant_id', 'left')
            ->join('jay_product_performance pp', 'pp.product_id = p.product_id and pp.tag=0', 'left')
            ->page($page, $pagesize)
            ->order('p.product_id desc')
            ->select();
        $return['list'] = $list;
        $pagination = new PaginationModel();
        $return['page'] = $pagination->getPage($count, $page, $pagesize);
        $this->assign('data', $return);
        return $this->display('index', true);
    }



    public function data(){
        $type = $this->get('type', 1);
        $id = $this->get('id', 0);
        $product = Db::name('product')->find($id);
        if ($type == 1){
            $sql = "select t.*, u.nickname, u.mobile, u.`level`, pu.nickname as parentnickname, pu.mobile as parentmobile from ( ".
                " select p.userid_second, sum(p.num) num, sum(o.order_payfee) order_payfee from jay_order_product p ".
                " left join jay_order o on o.order_id = p.order_id ".
                "   where o.order_status>1 and p.product_id = ".$id." and p.product_returnall=0 and o.user_id != p.userid_second and p.userid_second>0 ".
                "   group by p.userid_second order by num desc limit 100 ".
                "   ) t ".
                "   left join jay_user u on u.user_id = t.userid_second ".
                "   left join jay_user pu on pu.user_id = u.reid order by num desc";
            $this->assign('title', '直接分享人数据分析');
        }else{
//            $sql = "select t.*, u.nickname, u.mobile, u.`level`, pu.nickname as parentnickname, pu.mobile as parentmobile from ( ".
//                " select p.userid_operations, sum(p.num) num, sum(o.order_payfee) order_payfee from jay_order_product p ".
//                " left join jay_order o on o.order_id = p.order_id ".
//                "   where o.order_status>1 and p.product_id = ".$id." and p.product_returnall=0 and o.user_id != p.userid_operations and p.userid_operations>0 ".
//                "   group by p.userid_operations order by num desc limit 100 ".
//                "   ) t ".
//                "   left join jay_user u on u.user_id = t.userid_operations ".
//                "   left join jay_user pu on pu.user_id = u.reid order by null";

                $sql = "select kk.`level`, kk.mobile, kk.nickname, kk.parentmobile, kk.parentnickname, sum(kk.num) num, sum(kk.order_payfee) order_payfee, kk.userid_operations from (".
                    "  select t.*, u.nickname, u.mobile, u.`level`, pu.nickname as parentnickname, pu.mobile as parentmobile from (  ".
                    "   select p.userid_operations_child userid_operations, sum(p.num) num, sum(o.order_payfee) order_payfee from jay_order_product p  ".
                    "   left join jay_order o on o.order_id = p.order_id    ".
                    "   where o.order_status>1 and p.product_id = ".$id." and p.product_returnall=0 and o.user_id != p.userid_operations_child and p.userid_operations_child>0  ".
                    "   group by p.userid_operations_child order by num desc limit 100    ".
                    "   ) t    ".
                    "   left join jay_user u on u.user_id = t.userid_operations    ".
                    "   left join jay_user pu on pu.user_id = u.reid ".
                    "   union ALL".
                    "   select t.*, u.nickname, u.mobile, u.`level`, pu.nickname as parentnickname, pu.mobile as parentmobile from ( ".
                    "   select p.userid_operations, sum(p.num) num, sum(o.order_payfee) order_payfee from jay_order_product p  ".
                    "   left join jay_order o on o.order_id = p.order_id    ".
                    "   where o.order_status>1 and p.product_id = ".$id." and p.product_returnall=0 and o.user_id != p.userid_operations and p.userid_operations>0    ".
                    "   group by p.userid_operations order by num desc limit 100    ".
                    "   ) t    ".
                    "   left join jay_user u on u.user_id = t.userid_operations    ".
                    "   left join jay_user pu on pu.user_id = u.reid".
                    "   ) kk".
                    "   group by userid_operations order by num desc";
            $this->assign('title', '会员团队数据分析');
        }
        $list = Db::query($sql);
        foreach ($list as $k=>$v){
            $list[$k]['level'] = Levelconst::getName($v['level']);
        }
        $this->assign('list', $list);
        $this->assign('product', $product);
        return $this->display('data', true);
    }

    /**
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取商品销量分析
     * 肖亚子
     */
    public function ProductOrder(){
        $Id        = $this->get("id");
        $StartTime = strtotime($this->get("starttime"));
        $EndTime   = strtotime($this->get("endtime"));

        $Condition["op.product_id"] = array("eq",$Id);
        $Condition[] = array("exp","o.order_status > 1 and o.order_status < 5");
        $Condition   = self::TimeContrast($StartTime,$EndTime,"o.order_paytime",$Condition);


        $Field = "o.order_paytime,p.product_name,pu.nickname,pu.username,pu.avatar,pu.userthumb,
        ma.nickname as manickname,ma.username as mausername,ma.avatar as maavatar,ma.userthumb as mauserthumb,
        sum(o.order_totalfee) as totalfee,sum(op.num) as num,(sum(o.order_totalfee)-sum(op.settle)) as surplus,
        ((sum(o.order_totalfee)-sum(op.settle)) - sum(op.commission)) as profit,FROM_UNIXTIME(o.order_paytime,'%Y-%m-%d')as date";

        $List = Db::name("order")
                ->alias("o")
                ->field($Field)
                ->join("order_product op","op.order_id = o.order_id","left")
                ->join("product p","p.product_id = op.product_id","left")
                ->join("user pu","pu.user_id = p.purchase_id","left")
                ->join("user ma","ma.user_id = p.product_id","left")
                ->where($Condition)
                ->group("date")
                ->order("date desc")
                ->select();

        $Price = Db::name("product_price")
                 ->field("product_totalnum,product_buynum")
                 ->where(array("product_id"=>$Id,"price_status"=>1))
                 ->select();

        $Totalfee = array_sum(array_map(function($Val){return $Val['totalfee'];}, $List));
        $Num      = array_sum(array_map(function($Val){return $Val['num'];}, $List));
        $Surplus  = array_sum(array_map(function($Val){return $Val['surplus'];}, $List));
        $Profit   = array_sum(array_map(function($Val){return $Val['profit'];}, $List));

        $Totalnum = array_sum(array_map(function($Val){return $Val['product_totalnum'];}, $Price));
        $Buynum   = array_sum(array_map(function($Val){return $Val['product_buynum'];}, $Price));

        $Count["totalnum"] = $Totalnum - $Buynum;
        $Count["totalfee"] = $Totalfee;
        $Count["num"]      = $Num;
        $Count["surplus"]  = $Surplus;
        $Count["profit"]   = $Profit;

        $this->assign('id', $Id);
        $this->assign('list', $List);
        $this->assign('count', $Count);

        return $this->display('order', true);
    }

    /**
     * @param $StartTime  开始时间
     * @param $EndTime    结束时间
     * @param $Key        字段
     * @param $Condition  返回组合
     * @return mixed
     */
    public  function TimeContrast($StartTime,$EndTime,$Key,$Condition){

        if (!empty($StartTime) && empty($EndTime)) {
            parent::Tpl_NotGtTime($StartTime,"开始时间不能大于当前时间"); //开始时间不为空和当前时间对比
            $Condition[$Key] = array(array('egt', $StartTime));
        } else if (empty($StartTime) && !empty($EndTime)) {
            parent::Tpl_NotGtTime($EndTime,"结束时间不能大于当前时间"); //结束时间不为空和当前时间对比
            $Condition[$Key] = array(array('elt', $EndTime));
        } else if (!empty($StartTime) && !empty($EndTime)) {
            parent::Tpl_TimeContrast1($StartTime,$EndTime); //开始和结束时间都不为空进行判断
            $Condition[$Key] = array(array('egt', $StartTime), array('elt', $EndTime));
        }

        return $Condition;
    }
}