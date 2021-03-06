<?php
namespace app\system\controller;

use Admin\Model\UserModel;
use app\common\AdminBaseController;
use app\www\model\AdminMenu;
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
class AdminsController extends AdminBaseController
{
    
    /**
     * 管理员列表
     * Enter description here ...
     */
    public function index(){
        //设置添加信息按钮
        $this->assign('addbtn',  $this->returnAddbtn('添加管理员', 'system/admins/add', 1, '600px', '550px'));
        
        //获取参数
        $pn = $this->get('page', 1);
        
        //获取分页列表数据
        $am = new AdminModel();
        $data = $am->getList([], $pn);
        //fuck($data, Request::instance());
        
        $this->assign('data',  $data);
        return $this->display('index', true);
    }
    
    
    /**
     * 添加操作
     * Enter description here ...
     */
    public function add(){
        if (Request::instance()->isGet()){
            $this->assign('rolelist',  Db::name('sys_roles')->select());
            return $this->display('add');
        }else{
            $item['jname'] = $this->post('jname', '', RegExpression::REQUIRED, '登录账号');
            $pwd = $this->post('jpass', '', RegExpression::MIN5, '登录密码');
            $item['nickname'] = $this->post('nickname', '', RegExpression::REQUIRED, '昵称');
            $item['role_id'] = $this->post('role_id', '', RegExpression::REQUIRED, '角色');
            $item['head'] = $this->post('head', '');
            $item['dllkey'] = Md5Help::getDllKey();
            $item['jpass'] = Md5Help::getMd5Pwd($pwd, $item['dllkey']);
            $item['addtime'] = SysHelp::getTimeString();
            
            $am = new AdminModel();
            $res = $am->add($item);
            
            //删除图片
            $this->deleteUploaded('uploads', $item['head']);

            if($res !== false){
                $this->log('添加管理员账号：'.$item['jname']);
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
            $item = Db::name('sys_admin')->where('id', Request::instance()->param('id', 0))->find();
            $this->assign('obj', $item);
            $this->assign('rolelist',  Db::name('sys_roles')->select());
            return $this->display();
        }else{
            $item['jname'] = $this->post('jname', '', RegExpression::REQUIRED, '登录账号');
            $pwd = $this->post('jpass', '');
            $item['nickname'] = $this->post('nickname', '', RegExpression::REQUIRED, '昵称');
            $item['role_id'] = $this->post('role_id', '', RegExpression::REQUIRED, '角色');
            $item['head'] = $this->post('head', '');
            //更新session
            if(!(empty($item['head']))){
                Session::set('admin.head', $item['head']);
            }
            //如果密码不为空，表示修改密码
            if($pwd != ''){
                $item['dllkey'] = Md5Help::getDllKey();
                $item['jpass'] = Md5Help::getMd5Pwd($pwd, $item['dllkey']);
            }
            $item['id'] = $this->post('id', 0);

            $Condition["id"] =  $item['id'];

            $PreRevision = Db::name("sys_admin")->where($Condition)->find();
            $res = Db::name('sys_admin')->update($item);
            //删除图片
            $this->deleteUploaded('uploads', $item['head']);
            

            if($res !== false){
                $this->log('修改管理员账号:[管理员ID:'.$item['id']."]","sys_admin",$Condition,$PreRevision);
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
        $res = Db::name('sys_admin')->delete($id);

        if($res !== false){
            $this->log('删除管理员账号:'.Db::name('sys_admin')->where('id', $id)->value('jname'));
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
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取日志详情
     */
    public function LogsInfo(){
        $Id = $this->get("id");
        $Am = New AdminModel();

        $Data = $Am->LogsFind(array("id"=>$Id));
        $Data["notes"]      = unserialize($Data["notes"]);
        $Data["prerevised"] = unserialize($Data["prerevised"]);
        $Data["revised"]    = unserialize($Data["revised"]);

        $Regroup = array_merge_recursive($Data["prerevised"],$Data["revised"]);
        $Regroup = array_merge_recursive($Data["notes"],$Regroup);

        $this->assign("notes",array_values($Regroup));
        return $this->display('logsinfo', true);
    }
    
    
    /**
     * 删除日志
     * Enter description here ...
     */
    public function deleteLogs(){
        $id    = Request::instance()->param('id', 0);
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
            
            $this->log("批量删除日志:[日志ID:".$idstr."]");
        }
        $this->toSuccess('删除成功');
    }
    
}
