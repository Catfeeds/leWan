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
use app\system\model\ContentModel;

/**
 * 周边管理
 * Enter description here ...
 * @author Administrator
 *
 */
class NearController extends AdminBaseController
{
    //大分类id
	private $section = 5;
	
    /**
     * 活动列表
     * Enter description here ...
     */
    public function index(){
        //设置添加信息按钮
        $this->assign('addbtn',  $this->returnAddbtn('添加活动', 'system/near/add', 2));
        
        $cm = new ContentModel();
        //类型
        $this->assign('parents',  $cm->getCatesById($this->section));
        
        //获取参数
        $pn = $this->get('page', 1);
        $kws = $this->get('kws', '');
        $cat_id = $this->get('cat_id', 0);
        $starttime = $this->get('starttime', '');
        $endtime = $this->get('endtime', '');
        //组合where
        if($kws != ''){
        	$where['a.title'] = array('like', '%'.$kws.'%');
        	$this->assign('kws', $kws);
        }
        if($cat_id > 0){
        	$where['a.cat_id'] = $cat_id;
        	$this->assign('cat_id', $cat_id);
        }else{
        	$where['a.section'] = $this->section;
        }
        if($starttime != ''){
        	$where['a.addtime'] = array('egt', $starttime);
        	$this->assign('starttime', $starttime);
        }
        if($endtime != ''){
        	$where['a.addtime'] = array('elt', $endtime);
        	$this->assign('endtime', $endtime);
        }

        //获取分页列表数据
        $data = $cm->getActivesList($where, $pn);

        $this->assign('data',  $data);
        return $this->display('index', true);
    }
    
    
    /**
     * 添加操作
     * Enter description here ...
     */
    public function add(){
        if (Request::instance()->isGet()){
            //类型
            $cm = new ContentModel();
        	$this->assign('parents',  $cm->getCatesById($this->section));
	        $this->assign('action',  url('system/near/add'));
            return $this->display('edit', true);
        }else{
        	$item['title'] = $this->post('title', '', RegExpression::REQUIRED, '活动名称');
        	$item['en_title'] = $this->post('en_title', '');
        	$item['cat_id'] = $this->post('cat_id',0 , RegExpression::REQUIRED, '分类');
        	$item['section'] = $this->section;
        	$item['content'] = $this->post('content', '', RegExpression::REQUIRED, '活动内容');
        	$item['en_content'] = $this->post('en_content', '');
        	$item['price'] = $this->post('price', '');
        	$item['pic'] = $this->post('pic', '');
        	$item['starttime'] = $this->post('starttime', '');
        	$item['status'] = 0;
        	$item['addtime'] = SysHelp::getTimeString();
        	$item['enrollnum'] = $this->post('enrollnum', 0);
        	
        	$res = Db::name('actives')->insert($item);
        	//删除图片
        	$this->deleteUploaded('uploads', $item['pic']);
        	
            $this->log('添加活动账号：'.$item['title']);
            if($res !== false){
                $this->toSuccess('添加成功', 'near/index');
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
            $item = Db::name('actives')->where('id', Request::instance()->param('id', 0))->find();
            $this->assign('obj', $item);
            //类型
	        $cm = new ContentModel();
        	$this->assign('parents',  $cm->getCatesById($this->section));
	        $this->assign('action',  url('system/near/edit'));
            return $this->display('edit', true);
        }else{
            $item['title'] = $this->post('title', '', RegExpression::REQUIRED, '活动名称');
            $item['en_title'] = $this->post('en_title', '');
            $item['cat_id'] = $this->post('cat_id',0 , RegExpression::REQUIRED, '分类');
            $item['section'] = $this->section;
            $item['content'] = $this->post('content', '', RegExpression::REQUIRED, '活动内容');
            $item['en_content'] = $this->post('en_content', '');
            $pic = $this->post('pic', '');
            $item['starttime'] = $this->post('starttime', '');
            $item['status'] = 0;
            $item['addtime'] = SysHelp::getTimeString();
            $item['id'] = $this->post('id', 0);
            $item['enrollnum'] = $this->post('enrollnum', 0);
            if($pic != ''){
            	$item['pic'] = $pic;
            }
            $res = Db::name('actives')->update($item);
            //删除图片
            $this->deleteUploaded('uploads', $item['pic']);
            
            $this->log('修改活动：'.$item['title']);
            if($res !== false){
                $this->toSuccess('编辑成功', url('near/index'));
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
        $idstr = Request::instance()->post('idstr', '');
        if($id > 0){
	        $obj = Db::name('actives')->where('id', $id)->find();
	        $this->log('删除：'.$obj['title']);
	        $this->deletefile('uploads', $obj['pic']);
	        $res = Db::name('actives')->delete($id);
        }else{
        	//批量删除
        	$idarray = explode(',', $idstr);
        	foreach ($idarray as $k=>$v){
        		if(!(empty($v))){
        			$obj = Db::name('actives')->where('id', $v)->find();
			        $this->log('删除：'.$obj['title']);
			        $this->deletefile('uploads', $obj['pic']);
			        $res = Db::name('actives')->delete($v);
        		}
        	}
        }
        $this->toSuccess('删除成功');
    }
    
}
