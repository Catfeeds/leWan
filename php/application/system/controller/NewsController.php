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
 * 活动管理
 * Enter description here ...
 * @author Administrator
 *
 */
class NewsController extends AdminBaseController
{
    
	//大分类id
	private $section = 3;
	
	
    /**
     * 活动列表
     * Enter description here ...
     */
    public function index(){
        //设置添加信息按钮
        $this->assign('addbtn',  $this->returnAddbtn('发布新闻', 'system/news/add', 2));
        
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
        $data = $cm->getArticleList($where, $pn);
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
	        $this->assign('action',  url('system/news/add'));
            return $this->display('edit', true);
        }else{
        	$item['title'] = $this->post('title', '', RegExpression::REQUIRED, '活动名称');
        	$item['cat_id'] = $this->post('cat_id',0 , RegExpression::REQUIRED, '分类');
        	$item['section'] = $this->section;
        	//$item['descp'] = $this->post('descp', '', RegExpression::REQUIRED, '摘要');
			$item['content'] = $this->post('content', '', RegExpression::REQUIRED, '活动内容');
        	$item['tag'] = $this->post('tag', '');
        	$item['pic'] = $this->post('pic', '');
        	$item['add_time'] = strtotime($this->post('add_time', date('Y-m-d')));
        	//$item['addtime'] = SysHelp::getTimeString();
        	
        	$res = Db::name('article')->insert($item);
        	//删除图片
        	$this->deleteUploaded('uploads', $item['pic']);

            if($res !== false){
                $this->log('添加最新新闻：'.$item['title']);
                $this->toSuccess('发布成功', 'news/index');
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
            $item = Db::name('article')->where('id', Request::instance()->param('id', 0))->find();
            $this->assign('obj', $item);
            //类型
            $cm = new ContentModel();
        	$this->assign('parents',  $cm->getCatesById($this->section));
	        $this->assign('action',  url('system/news/edit'));
            return $this->display('edit', true);
        }else{
            $item['title'] = $this->post('title', '', RegExpression::REQUIRED, '活动名称');
        	$item['cat_id'] = $this->post('cat_id',0 , RegExpression::REQUIRED, '分类');
        	$item['section'] = $this->section;
        	//$item['descp'] = $this->post('descp', '', RegExpression::REQUIRED, '摘要');
			$item['content'] = $this->post('content', '', RegExpression::REQUIRED, '活动内容');
        	$pic = $this->post('pic', '');
            $item['tag'] = $this->post('tag', '');
            $item['add_time'] = strtotime($this->post('add_time', date('Y-m-d')));
            $item['id'] = $this->post('id', 0);
            if($pic != ''){
            	$item['pic'] = $pic;
            }

            $Condition["id"] = $item['id'];

            $PreRevision = Db::name("article")->where($Condition)->find();
            $res         = Db::name('article')->update($item);
            //删除图片
            $this->deleteUploaded('uploads', $item['pic']);

            if($res !== false){
                $this->log("修改新闻信息：[新闻ID:".$item['id']."]","article",$Condition,$PreRevision);
                $this->toSuccess('编辑成功', url('news/index'));
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
	        $obj = Db::name('article')->where('id', $id)->find();
	        $this->log('删除活动：'.$obj['title']);
	        $this->deletefile('uploads', $obj['pic']);
	        $res = Db::name('article')->delete($id);

            $this->log('删除新闻:[新闻ID:'.$id);
        }else{
        	//批量删除
        	$idarray = explode(',', $idstr);
        	foreach ($idarray as $k=>$v){
        		if(!(empty($v))){
        			$obj = Db::name('article')->where('id', $v)->find();
			        $this->log('删除活动：'.$obj['title']);
			        $this->deletefile('uploads', $obj['pic']);
			        $res = Db::name('article')->delete($v);
        		}
        	}

            $this->log('批量删除新闻:[新闻ID:'.$idstr);
        }
        $this->toSuccess('删除成功');
    }
    
}
