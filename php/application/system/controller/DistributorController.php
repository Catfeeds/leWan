<?php
namespace app\system\controller;

use app\common\AdminBaseController;
use think\Request;
use think\Db;
use think\Session;
use app\common\RegExpression;
use app\common\Md5Help;
use app\common\SysHelp;
use app\system\model\AdminModel;

/**
 * 管理员模块
 * Enter description here ...
 * @author Administrator
 *
 */
class DistributorController extends AdminBaseController
{
    
    /**
     * 管理员列表
     * Enter description here ...
     */
    public function index(){
        //设置添加信息按钮
        $this->assign('addbtn',  $this->returnAddbtn('添加分销商', 'system/distributor/add', 1, '600px', '550px'));
        
        //获取参数
        $pn = $this->get('page', 1);
        
        $data = Db::table('fx_sys_merchant')->order('merchant_id desc')->select();
        
        $this->assign('data',  $data);
        return $this->display('index', true);
    }
    
    
    /**
     * 添加操作
     * Enter description here ...
     */
    public function add(){
        if (Request::instance()->isGet()){
            $this->assign('rolelist',  Db::table('fx_sys_roles')->select());
            return $this->display('add');
        }else{
            $item['loginname'] = $this->post('loginname', '', RegExpression::REQUIRED, '登录账号');
            $pwd = $this->post('password', '', RegExpression::MIN5, '登录密码');
            $item['merchant_name'] = $this->post('merchant_name', '', RegExpression::REQUIRED, '商家名称');
            $item['role_id'] = $this->post('role_id', '', RegExpression::REQUIRED, '角色');
            $item['dllkey'] = Md5Help::getDllKey();
            $item['password'] = Md5Help::getMd5Pwd($pwd, $item['dllkey']);
            $item['addtime'] = time();

            $res = Db::table('fx_sys_merchant')->insert($item);
            
            $this->log('添加分销商账号：'.$item['merchant_name']);
            if($res !== false){
                $this->toSuccess('添加成功', '', 2);
            }else{
                $this->toError('添加失败');
            }
        }
    }
    
    
    /**
     * 修改
     * Enter description here ...
     */
    public function edit(){
        if (Request::instance()->isGet()){
            $item = Db::table('fx_sys_merchant')->where('merchant_id', Request::instance()->param('id', 0))->find();
            $this->assign('obj', $item);
            $this->assign('rolelist',  Db::table('fx_sys_roles')->select());
            return $this->display();
        }else{
            $item['loginname'] = $this->post('loginname', '', RegExpression::REQUIRED, '登录账号');
            $pwd = $this->post('password', '');
            $item['merchant_name'] = $this->post('merchant_name', '', RegExpression::REQUIRED, '商家名称');
            $item['role_id'] = $this->post('role_id', '', RegExpression::REQUIRED, '角色');
            //如果密码不为空，表示修改密码
            if($pwd != ''){
                $item['dllkey'] = Md5Help::getDllKey();
                $item['jpass'] = Md5Help::getMd5Pwd($pwd, $item['dllkey']);
            }
            $item['merchant_id'] = $this->post('id', 0);
            
            $res = Db::table('fx_sys_merchant')->update($item);

            $this->log('修改分销商账号：'.$item['merchant_name']);
            if($res !== false){
                $this->toSuccess('编辑成功', '', 2);
            }else{
                $this->toError('编辑失败');
            }
        }
    }
    
    /**
     * 删除账号
     * Enter description here ...
     */
    public function delete(){
        $id = Request::instance()->param('id', 0);
        if($id == 100){
            $this->toError('你没有权限删除超级用户');
        }
        $this->log('删除分销商账号：'.Db::table('fx_sys_merchant')->where('merchant_id', $id)->value('merchant_name'));
        $res = Db::table('fx_sys_merchant')->delete($id);
        if($res !== false){
            $this->toSuccess('删除成功');
        }else{
            $this->toError('删除失败');
        }
    }
    
    
    /**
     * 操作日志管理
     * Enter description here ...
     */
    public function logs(){
        $where = array();
        //获取参数
        $pn = $this->get('page', 1);
        $admin_id = $this->get('admin_id', 0);
        $title = $this->get('title', '');
        if($admin_id > 0){
            $where['admin_id'] = $admin_id;
        }
        if($title != ''){
            $where['intro'] = ['like',"%$title%"];
        }

        $this->assign('admin_id',  $admin_id);
        $this->assign('title',  $title);
        $this->assign('adminlist',  Db::name('sys_admin')->field('id, jname')->select());
        
        //获取分页列表数据
        $am = new AdminModel();
        $data = $am->getLogsList($where, $pn, 30);
        
        $this->assign('data',  $data);
        
        return $this->display('logs', true);
    }
    
    
    
    /**
     * 删除日志
     * Enter description here ...
     */
    public function deleteLogs(){
        $id = Request::instance()->param('id', 0);
        $idstr = Request::instance()->post('idstr', '');
        if($id > 0){
            //$res = Db::name('sys_nodes')->delete($id);
        }else if($idstr != ''){
            //批量删除
            $idarray = explode(',', $idstr);
            foreach ($idarray as $k=>$v){
                if(!(empty($v))){
                    Db::name('sys_adminlogs')->delete($v);
                }
            }
            
            $this->log('批量删除日志');
        }
        $this->toSuccess('删除成功');
    }


    /**
     * 分销发码下单
     * @return string
     */
    public function createorder(){
        if (Request::instance()->isGet()){
            return $this->display('create-order', true);
        }else{
            $product_id = $this->post('product_id', 0);
            $user_id = $this->post('user_id', 0);
            $tmp_file = $_FILES['excel']['tmp_name'];
            $file_types = explode(".", $_FILES ['excel'] ['name']);
            $file_type = $file_types [count($file_types) - 1];
            $price_id = $this->post('price_id', 0);
            //销售价
            $sale_settle = $this->post('price', 0);
            /*判别是不是.xls文件，判别是不是excel文件*/
            if (strtolower($file_type) != "xlsx") {
                $this->error('不支持的Excel文件'.$file_type.'，请重新上传');
            }
            vendor('phpexcel.PHPExcel');
            vendor('phpexcel.PHPExcel.IOFactory');
            $objReader = \PHPExcel_IOFactory::createReader('Excel2007');
            $obj_PHPExcel = $objReader->load($tmp_file, $encode = 'utf-8');//加载文件内容,编码utf-8
            $excel_array = $obj_PHPExcel->getsheet(0)->toArray();//转换为数组格式
            array_shift($excel_array);//删除第一个数组(标题);
            $datas = [];
            $arr = array();
            $total = 0;
            foreach ($excel_array as $k => $v) {
                if(!empty($v)){
                    $datas[$k]['concat'] = $v[0];
                    $datas[$k]['mobile'] = $v[1];
                    $datas[$k]['buynum'] = $v[2];
                    $datas[$k]['price_id'] = $price_id;
                    $datas[$k]['price'] = $sale_settle;

                    $arr[$v[3]]['price_id'] = $price_id;
                    $arr[$v[3]]['buynum'] += $v[2];
                }
            }
            //检查每个规格的库存等
            foreach ($arr as $v){
                $res = $this->verfiyProduct($product_id,$v['price_id'],$v['buynum']);
                if($res['code'] == 400){
                    $this->error($res['msg']);
                }
            }
            $data['product_id'] =  $product_id;
            $data['remark'] = $this->post('remark', '分销商发码下单');
            $data['user_id'] =  $user_id;//下单用户
            $data['time'] = time();
            $data['key'] = getSelfSignStr($data);
            $data['list'] = json_encode($datas);
            $host =  $_SERVER['HTTP_HOST']?$_SERVER['HTTP_HOST']: $_SERVER['SERVER_NAME'];
            $url =  $host.'/api/Order/createBatchOrderByDistributor';
            curlPost($url,$data); //curl创建订单
            $this->success('创建成功,',url('system/order/orderlist'));
        }
    }

    /**
     * @param $product_id
     * @param $price_id
     * @param $buynum
     * @return array|false|null|\PDOStatement|string|\think\Model
     */
    private function verfiyProduct($product_id, $price_id, $buynum){
        $product = Db::name('product p')
            ->field('c.*, p.product_name, p.product_status, p.product_del, p.product_reviewstatus,
            p.price_type, p.product_returnall, p.product_reservation, p.product_isexpress, 
            p.product_timelimit, p.product_numlimit, p.product_numlimit_num, p.product_starttime, p.product_endtime, 
            p.product_startusetime, p.product_endusetime, p.merchant_id, p.sold_out')
            ->join('product_price c', 'c.product_id = p.product_id', 'left')
            ->join('merchant m', 'm.merchant_id = p.merchant_id', 'left')
            ->where(['p.product_id'=>$product_id, 'c.price_id'=>$price_id, 'm.merchant_status'=>2, 'c.price_status'=>1])
            ->find();
        if(!$product){
            return array('code'=>400, 'msg'=>'商品不存在');
        }
//        if($product['sold_out'] == 1){
//            return array('code'=>400, 'msg'=>'商品已售罄');
//        }
//        if($product['product_isexpress'] == 2 && $product['product_reservation'] == 2){
//            return array('code'=>400,'快递类产品不支持后台下单');
//        }
        if($product['product_del'] == 1 || $product['product_status'] == 0){
            return array('code'=>400, 'msg'=>'商品售罄已下架');
        }
        if($product['product_reviewstatus'] != 2){
            return array('code'=>400, 'msg'=>'商品未审核通过');
        }
//        if($product['product_buynum'] >= $product['product_totalnum']){
//            return array('code'=>400, 'msg'=>'商品已售罄');
//        }
        if($product['product_buynum']+$buynum > $product['product_totalnum']){
            return array('code'=>400, 'msg'=>'商品库存不足');
        }
        if($product['price_type'] == 2){
            return array('code'=>400, 'msg'=>'商品价格类型异常');
        }
        return $product;
    }
}
