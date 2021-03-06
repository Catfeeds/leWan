<?php

namespace app\system\controller;

use app\common\AdminBaseController;
use app\system\model\MerchantModel;
use think\Request;
use think\Db;
use think\Session;
use app\common\RegExpression;
use app\common\Md5Help;
use app\common\SysHelp;
use app\system\model\AdminModel;
use app\system\model\ContentModel;

use app\system\model\UserModel;

/**
 * 商家管理
 * Enter description here ...
 * @author Administrator
 *
 */
class MerchantController extends AdminBaseController {

    /**
     * 待审核列表
     * Enter description here ...
     */
    public function review() {
        $where['m.merchant_status'] = 0;
        $where['m.merchant_del'] = 0;
        $where['m.merchant_id'] = ['gt', 1];
        $data = $this->loadList($where);
        $this->assign('data', $data);
        return $this->display('merchant/review', true);
    }

    /**
     * 驳回商家列表
     * @return string
     */
    public function reback() {
        $where['m.merchant_status'] = 1;
        $where['m.merchant_del'] = 0;
        $where['m.merchant_id'] = ['gt', 1];
        $data = $this->loadList($where);
        $this->assign('data', $data);
        return $this->display('merchant/reback', true);
    }

    /**
     * @return string
     * 商家列表
     */
    public function index() {
        $where['m.merchant_status'] = 2;
        $where['m.merchant_del'] = 0;
        $where['m.merchant_id'] = ['gt', 1];
        $where['m.parent_id'] = 0;
        $data = $this->loadList($where);
        $this->assign('data', $data);
        return $this->display('merchant/index', true);
    }

    /**
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 查看商家详情
     */
    public function view(){
        $id = $this->get('id', 0);
        $merchant = Db::name('merchant')->find($id);
        if($merchant){
            $this->assign('merchant',  $merchant);
            $this->assign('action', url('system/merchant/inview'));
            return $this->display('merchant/view', true);
        }else{
            $this->error('商家不存在');
        }
    }

    /**
     * @return string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * 审核商家
     */
    public function inview(){
        if(Request::instance()->isPost()){
            $id = $this->post('id', 0);
            $status = $this->post('status', 1);
            $reason = $this->post('reason', '');
            if($status == 1 && $reason==''){
                $this->error('请填写驳回原因');
            }
            $merchant = Db::name('merchant')->find($id);

            if($merchant){
                $res = Db::name('merchant')->where('merchant_id', $id)->update(['merchant_status'=>$status, 'merchant_remark'=>$reason, 'merchant_uptime'=>time()]);

                if ($res){
                    $this->log("审核入驻商家:[ID:".$id."]","merchant",array("merchant_id"=>$id),$merchant);
                }

                $this->success('操作成功', url('merchant/review'), 2);
            }
        }
        $id = $this->get('id', 0);
        $merchant = Db::name('merchant')->find($id);
        if($merchant){
            $this->assign('merchant',  $merchant);
            $this->assign('action', url('system/merchant/inview'));
            return $this->display('merchant/inview', false);
        }else{
            $this->error('商家不存在');
        }
    }

    /**
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * 批量删除商家
     */
    public function delete(){
        $id = Request::instance()->param('id', 0);
        $idstr = Request::instance()->post('idstr', '');
        if ($id > 0) {
            $obj = Db::name('merchant')->where('merchant_id', $id)->find();
            $res = Db::name('merchant')->where('merchant_id', $id)->update(['merchant_del'=>1]);

            if ($res){
                $this->log("软删除商家,更改商家信息:[ID:".$id."]","merchant",array("merchant_id"=>$id),$obj);
            }
        } else {
            //批量删除
            $idarray = explode(',', $idstr);
            foreach ($idarray as $k => $v) {
                if (!(empty($v))) {
                    $obj = Db::name('merchant')->where('merchant_id', $v)->find();
                    $res = Db::name('merchant')->where('merchant_id', $v)->update(['merchant_del'=>1]);

                    if ($res){
                        $this->log("软删除商家,更改商家信息:[ID:".$v."]","merchant",array("merchant_id"=>$v),$obj);
                    }
                }
            }
        }
        $this->toSuccess('删除成功');
    }

    /**
     * @param $map
     * @return mixed
     * 获取商家列表
     */
    private function loadList($map){
        $where = $map;
        //获取参数
        $pn = $this->get('page', 1);
        $kws = $this->get('kws', '');
        $provence_id = $this->get('provence_id', '');
        $city_id = $this->get('city_id', '');
        $area_id = $this->get('area_id', '');
        $starttime = $this->get('starttime', '');
        $endtime = $this->get('endtime', '');
        //组合where
        if($kws != ''){
            $where['m.merchant_name|m.merchant_contactmobile|m.merchant_contact'] = array('like', '%'.$kws.'%');
            $this->assign('kws', $kws);
        }
        if($starttime != ''){
            $where['m.merchant_addtime'] = array('egt', strtotime($starttime));
            $this->assign('starttime', $starttime);
        }
        if($endtime != ''){
            $where['m.merchant_addtime'] = array('elt', strtotime($endtime)+86400);
            $this->assign('endtime', $endtime);
        }
        if($provence_id != ''){
            $where['m.merchant_pcode'] = $provence_id;
            $this->assign('provence_id', $provence_id);
        }else{
            $this->assign('provence_id', 0);
        }
        if($city_id != ''){
            $where['m.merchant_ccode'] = $city_id;
            $this->assign('city_id', $city_id);
        }else{
            $this->assign('city_id', 0);
        }
        if($area_id != ''){
            $where['m.merchant_acode'] = $area_id;
            $this->assign('area_id', $area_id);
        }else{
            $this->assign('area_id', 0);
        }

        return MerchantModel::getList($where, $pn);
    }


    /**
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 添加商家
     */
    public function add(){
        if(Request::instance()->isPost()){
            $data = $this->createData();
            $data['parent_id']        = intval($this->post('parent_id'));
            $data['merchant_addtime'] = time();
            $data['dllkey']           = Md5Help::getDllKey();
            $password                 = $this->post('password', '', RegExpression::MIN5, '登录密码');
            $data['password']         = Md5Help::getMd5Pwd($password, $data['dllkey']);

            $res = Db::name('merchant')->insertGetId($data);

            if($res){
                $this->log("入驻新商家:[ID:".$res."]");
                $this->success('添加成功');
            }else{
                $this->error('添加失败');
            }
        }
        $obj = array();
        $Id  = $this->get("id",0);
        if ($Id){
            $Main = Db("merchant")->where("merchant_id","=",$Id)->value("merchant_name as main_name");
            $obj["parent_id"] = $Id;
            $obj["main_name"] = $Main;
        }
        $dboss = Db("merchant_dboss")->order("id asc")->select();
        $this->assign('dboss', $dboss);
        $this->assign('obj', $obj);
        $this->assign('action', url('system/merchant/add'));
        $this->assign('provence', $this->getProvenceList(1));
        return $this->display('merchant/add', true);
    }

    /**
     * @return string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * 编辑商家信息
     */
    public function edit(){
        if(Request::instance()->isPost()){
            $id = $this->post('id', 0);
            $parent_id = $this->post('parent_id','');

            $merchant = Db::name('merchant')->find($id);
            if(!$merchant){
                $this->error('商家不存在');
            }
            if($id == 1){
                $this->error('超级商户不能更改');
            }
            $data = $this->createData();
            $password = $this->post('password', '');
            if($password != ''){
                if(strlen($password) < 5){
                    $this->error('密码至少5位字符串');
                }
                $data['password'] = Md5Help::getMd5Pwd($password, $merchant['dllkey']);
            }
            $Condition["merchant_id"] = $id;

            $PreRevision = Db::name("merchant")->where($Condition)->find();
            $res         = Db::name('merchant')->where($Condition)->update($data);

            if($res){
                $this->log("更改商家信息:[ID:".$id."]","merchant",$Condition,$PreRevision);
                $this->success('更新成功');
            }else{
                $this->error('更新失败');
            }
        }

        $merchant = Db::name('merchant')
            ->alias("m")
            ->field("m.*,mc.merchant_name as main_name")
            ->join("merchant mc","mc.merchant_id = m.parent_id","left")
            ->find($this->get('id', 0));
        $dboss = Db("merchant_dboss")->order("id asc")->select();
        $this->assign('dboss', $dboss);
        $this->assign('obj', $merchant);
        $this->assign('action', url('system/merchant/edit'));
        $this->assign('provence', $this->getProvenceList(1));
        $this->assign('city', $this->getCityList($merchant['merchant_pcode'],1));
        $this->assign('area', $this->getAreaList($merchant['merchant_ccode']));
        return $this->display('merchant/add', true);
    }

    /**
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取商家信息
     */
    private function createData(){
        $id      = $this->post('id', 0);
        $oldchar = array(" ","　","\t","\n","\r");

        $vo['merchant_name'] = $this->post('merchant_name', '', RegExpression::REQUIRED, '商家名称');
        $vo['loginname'] = $this->post('loginname', '', RegExpression::REQUIRED, '登录账号');
        $vo['merchant_contact'] = $this->post('merchant_contact', '', RegExpression::REQUIRED, '联系人姓名');
        $vo['merchant_contactmobile'] = $this->post('merchant_contactmobile', '', RegExpression::MOBILE, '联系人手机号');

        $vo['merchant_alias'] = $this->post('merchant_alias', '');
        $vo['dboss_id'] = $this->post('dboss_id', 1);
        $vo['merchant_400tel'] = $this->post('merchant_400tel', '');
        $vo['merchant_pcode'] = $this->post('provence_id', '');
        $vo['merchant_ccode'] = $this->post('city_id', '');
        $vo['merchant_acode'] = $this->post('area_id', '');
        $vo['merchant_ssq'] = str_replace($oldchar,"",$this->post('ssq', ''));
        $vo['merchant_address'] = $this->post('address', '');
        $vo['merchant_lat'] = $this->post('merchant_lat', '');
        $vo['merchant_lng'] = $this->post('merchant_lng', '');
        $vo['merchant_logo'] = $this->post('merchant_logo', '');
        $vo['merchant_license'] = $this->post('merchant_license', '');
        $vo['merchant_slogan'] = $this->post('merchant_slogan', '');
        $vo['merchant_description'] = $this->post('merchant_description', '');
        $vo['merchant_remark'] = $this->post('merchant_remark', '');
        $vo['merchant_envimgs'] = implode(',', $_POST['batchimg1']);
        $vo['merchant_uptime'] = time();

        if ($id){
            $Condition["merchant_id"] = array("neq",$id);
            $Condition["loginname"]   = array("eq",$vo['loginname']);
        }else{
            $Condition["loginname"]   = array("eq",$vo['loginname']);

            parent::Tpl_Empty($vo['merchant_name'],"请输入商家名");
            parent::Tpl_FullSpace($vo['merchant_name'],"请输入商家名");
            parent::Tpl_Empty($vo['merchant_contact'],"请输入联系人姓名");
            parent::Tpl_FullSpace($vo['merchant_name'],"请输入商家名");
            parent::Tpl_Phone($vo['merchant_contactmobile'],"请输入正确登录账号手机号");
            parent::Tpl_Empty($vo['merchant_pcode'],"请选择省份");
            parent::Tpl_Empty($vo['merchant_ccode'],"请选择城市");
            parent::Tpl_Empty($vo['merchant_acode'],"请选择区/县");
            parent::Tpl_Empty($vo['merchant_lng'],"请进行商家定位");
            parent::Tpl_Empty($vo['merchant_lat'],"请进行商家定位");
            parent::Tpl_Empty($vo['merchant_400tel'],"请输入门店电话");
        }

        $mobile = Db::name("merchant")->where($Condition)->find();

        if ($mobile){
            $this->error('登录账号已经存在,请重新输入');
        }

        return $vo;
    }
}
