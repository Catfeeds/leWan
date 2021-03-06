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
 * 后台分组模块
 * Enter description here ...
 * @author Administrator
 *
 */
class GroupController extends AdminBaseController
{
    
    /**
     * 分组列表
     * Enter description here ...
     */
    public function index(){
        //设置添加信息按钮
        $this->assign('addbtn',  $this->returnAddbtn('添加分组', 'system/group/add'));
        
        //获取分页列表数据
        $data = Db::name('sys_group')->order('sort asc')->select();
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
            return $this->display();
        }else{
            $item['title'] = $this->post('title', '', RegExpression::REQUIRED, '分组名称');
            $item['code'] = $this->post('code', '', RegExpression::STRING, '分组代码');
            $item['status'] = $this->post('status', 0);
            $item['sort'] = $this->post('sort', 0);
            $res = Db::name('sys_group')->insert($item);
            if($res !== false){
                $this->log("添加后台分组导航:[导航名称:".$item['title']."]");
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
            $item = Db::name('sys_group')->where('id', Request::instance()->param('id', 0))->find();
            $this->assign('obj', $item);
            return $this->display();
        }else{
            $item['title']  = $this->post('title', '', RegExpression::REQUIRED, '分组名称');
            $item['code']   = $this->post('code', '', RegExpression::STRING, '分组代码');
            $item['status'] = $this->post('status', 0);
            $item['sort']   = $this->post('sort', 0);
            $item['id']     = $this->post('id', 0);

            $Condition["id"] = $item['id'];

            $PreRevision = Db::name("sys_group")->where($Condition)->find();
            $res         = Db::name('sys_group')->update($item);

            if($res !== false){
                $this->log("修改后台分组导航信息：[导航ID:".$item['id']."]","sys_group",$Condition,$PreRevision);
                $this->toSuccess('编辑成功', '', 2);
            }else{
                $this->toError('编辑失败');
            }
        }
    }
    
    
    public function delete(){
        $id = Request::instance()->param('id', 0);
        if($id == 1){
            $this->toError('你没有权限删除系统模块');
        }
        $res = Db::name('sys_group')->delete($id);
        //同时删除下面对应的菜单
        $res = Db::name('sys_nodes')->where('group_id', $id)->delete();
        if($res !== false){
            $this->log("删除分组导航:[导航ID:".$id."]");
            $this->toSuccess('删除成功');
        }else{
            $this->toError('删除失败');
        }
    }
    
    
}
