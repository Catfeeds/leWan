<?php
namespace app\system\controller;

use app\common\AdminBaseController;
use think\Request;
use think\Db;
use think\Session;
use think\Exception;
use app\common\RegExpression;
use app\common\Md5Help;
use app\common\SysHelp;
use app\system\model\NodesModel;

/**
 * 系统节点模块
 * Enter description here ...
 * @author Administrator
 *
 */
class NodesController extends AdminBaseController
{
    
    /**
     * 管理员列表
     * Enter description here ...
     */
    public function index(){
        //设置添加信息按钮
        $this->assign('addbtn',  $this->returnAddbtn('添加节点', 'system/nodes/add', 1, '500px', '580px'));
        
        $where = array();
        //获取参数
        $pn = $this->get('page', 1);
        $group_id = $this->get('group_id', 0);
        $type = $this->get('type', 0);
        $title = $this->get('title', '');
        if($group_id > 0){
            $where['n.group_id'] = $group_id;
        }
        if($type > 0){
            $where['n.type'] = ($type==3)?0:$type;
        }
        if($title != ''){
            $where['n.codes|n.title'] = ['like',"%$title%"];
        }
        
        $this->assign('group_id',  $group_id);
        $this->assign('type',  $type);
        $this->assign('title',  $title);
        $this->assign('grouplist',  Db::name('sys_group')->where('status', 1)->select());
        
        //获取分页列表数据
        $nm = new NodesModel();
        $data = $nm->getList($where, $pn, 30);
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
            $this->assign('grouplist',  Db::name('sys_group')->where('status', 1)->order('sort asc')->select());
            return $this->display('add');
        }else{
            $item['title'] = $this->post('title', '', RegExpression::REQUIRED, '节点名称');
            $item['group_id'] = $this->post('group_id', 0, RegExpression::REQUIRED, '分组');
            $item['codes'] = $this->post('codes', '');
            $item['args'] = $this->post('args', '');
            $item['type'] = $this->post('type', 0);
            $item['status'] = $this->post('status', 0);
            $item['sort'] = $this->post('sort', 0);
            
            $res = Db::name('sys_nodes')->insert($item);
            if($res !== false){
                $this->log("添加权限节点");
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
            $item = Db::name('sys_nodes')->where('id', Request::instance()->param('id', 0))->find();
            $this->assign('obj', $item);
            $this->assign('grouplist',  Db::name('sys_group')->where('status', 1)->order('sort asc')->select());
            return $this->display();
        }else{
            $item['title'] = $this->post('title', '', RegExpression::REQUIRED, '节点名称');
            $item['group_id'] = $this->post('group_id', 0, RegExpression::REQUIRED, '分组');
            $item['codes'] = $this->post('codes', '');
            $item['args'] = $this->post('args', '');
            $item['type'] = $this->post('type', 0);
            $item['status'] = $this->post('status', 0);
            $item['sort'] = $this->post('sort', 0);
            $item['id'] = $this->post('id', 0);

            $Condition["id"] = $item['id'];
            $PreRevision = Db::name("sys_nodes")->where($Condition)->find();
            $res         = Db::name('sys_nodes')->update($item);

            if($res !== false){
                $this->log("修改权限节点信息：[节点ID:".$item['id']."]","sys_nodes",$Condition,$PreRevision);
                $this->toSuccess('编辑成功', '', 2);
            }else{
                $this->toError('编辑失败');
            }
        }
    }
    
    
    
    public function delete(){
        $id = Request::instance()->param('id', 0);
        $idstr = Request::instance()->post('idstr', '');
        if($id > 0){
            $res = Db::name('sys_nodes')->delete($id);
            $this->log("删除权限节点:[节点ID:".$id."]");
        }else if($idstr != ''){
            //批量删除
            $idarray = explode(',', $idstr);
            foreach ($idarray as $k=>$v){
                if(!(empty($v))){
                    Db::name('sys_nodes')->delete($v);
                }
            }
            $this->log("批量删除权限权限节点:[节点ID:".$idstr."]");
        }
        $this->toSuccess('删除成功');
    }
    
    
}
