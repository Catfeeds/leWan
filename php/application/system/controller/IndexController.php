<?php
namespace app\system\controller;

use app\common\AdminBaseController;
use app\common\model\AccountModel;
use app\common\model\AccountRecordModel;
use app\common\model\Currency;
use app\common\model\ManageFinanceModel;
use app\common\model\Tag;
use think\Request;
use think\Db;
use think\Log;
use think\Cache;
use think\Session;
use app\common\RegExpression;
use app\common\Md5Help;
use app\common\SysHelp;
use think\Template;
use app\system\model\NodesModel;

/**
 * 系统入口
 * Enter description here ...
 * @author Administrator
 *
 */
class IndexController extends AdminBaseController
{
    
    /**
     * 系统首页
     * Enter description here ...
     */
    public function index(){
        $tag  = $this->get('tag','day' );
        if($tag =='day'){
            $day = Tag::getDay();
            $dayArr = array();
            $timeArr = array();
            $orderTit = '最近7天';
            for($i=6;$i>=0;$i--){
                $str = strtotime($day);
                $curDay = date('Ym',$str-($i*86400));
                $count = ManageFinanceModel::getManageFinanceCount(array('total_tag'=>$curDay));
                if($count) { //判断是否有当日数据
                    $dayArr[] = date('Ymd',$str-($i*86400)); //获取获取几天日期
                    $timeArr[] = date('m月d日',$str-($i*86400)); //获取获取几天日期
                }
            }
            $pieChartTag  = $day;
            $dstr = join(",", $dayArr);
            $condition =  "FIND_IN_SET(total_tag, '".$dstr."')";
        }else{
            $month = Tag::getMonth();
            $monthArr = array();
            $timeArr = array();
            for($i=6;$i>=0;$i--){
                $str = strtotime($month);
                $curMonth = date('Ym',$str-($i*86400*30));
                $count = ManageFinanceModel::getManageFinanceCount(array('total_tag'=>$curMonth));
                if($count){ //判断是否有当月数据
                    $monthArr[] = $curMonth; //获取获取几个月
                    $timeArr[] = date('Y年m月',$str-($i*86400*30)); //获取获取几个月
                }
            }
            $pieChartTag  = $month;
            $orderTit = '最近7个月';
            $mstr = join(",", $monthArr);
            $condition =  "FIND_IN_SET(total_tag, '".$mstr."')";
        }
        $this->assign('timeArr', json_encode($timeArr));
        $total  = ManageFinanceModel::getManageFinanceByTag(Tag::get())  ;//总数据
        $data = ManageFinanceModel::getManageFinanceByTag($pieChartTag)  ;//饼状图
        $list = ManageFinanceModel::getManageFinanceList($condition)  ;//统计曲线图
        $this->assign('orderTit', $orderTit);
        $this->assign('data', $data);
        $this->assign('total', $total);
        $this->assign('list', json_encode($list));
        return $this->display('finance:index', true);
    }
    
    
    /**
     * 管理员登录
     * Enter description here ...
     */
    public function login(){
        if(Request::instance()->isAjax()){
            $username = $this->post('loginid', '', RegExpression::MIN5, '登录账号');
            $password = $this->post('pwdid', '', RegExpression::MIN5, '密码');
            $code = $this->post('code', '', RegExpression::CAPTCHA, '验证码');
            
            //查询账号
            $user = Db::name('sys_admin')->where('jname=:name')->bind(['name'=>$username])->find();
            if($user){
                if(Md5Help::getMd5Pwd($password, $user['dllkey']) != $user['jpass']){
                    $this->ajaxReturn('密码错误', 0);
                }else{
                	unset($user['jpass']);
                	unset($user['dllkey']);

                    $ipurl    = 'http://ip.taobao.com/service/getIpInfo.php?ip='.Request::instance()->ip();
                    $ipdata   = curlPost($ipurl);
                    $ipdata   = json_decode($ipdata,true);
                    $location = $ipdata["data"];
                    $country  = !$location["country"]?"XX":$location["country"];
                    $region   = !$location["region"]?"XX":$location["region"];
                    $city     = !$location["city"]?"XX":$location["city"];
                    $county   = !$location["county"]?"XX":$location["county"];
                    $isp      = !$location["isp"]?"XX":$location["isp"];
                    $location = $country."/".$region."/".$city."/".$county."/".$isp."/";
                    $location = Request::instance()->ip()." 所在地/".$location;
                    $user["location"] = $location;
                    Session::set('admin', $user);
                    Db::name('sys_admin')->where('id', $user['id'])->update(['last_login_time'=>SysHelp::getTimeString()]);

                    $this->log("管理员登录");
                    
                    //登录系统首页面
                    $nm = new NodesModel();
                    $groups = $nm->getGroup();
                    $node = $nm->secondMenu(intval($groups[0]['id']));
                    $url = empty($node)?url('index/index'):$node[0]['url'];
                    $this->ajaxReturn('登录成功', 1, $url);
                }
            }else{
                $this->ajaxReturn('账号不存在', 0);
            }
            
        }else{
            return $this->displaySingle('index/login');
        }
    }
    
    
    /**
     * 退出登录
     * Enter description here ...
     */
    public function logout(){
        $this->log("管理员退出");
        Session::delete('admin');
        $this->redirect('system/index/index');
    }
    
    /**
     * 清除缓存
     * Enter description here ...
     */
    public function clearCache(){
        Cache::clear();
        Log::clear();
       // rrmdir(RUNTIME_PATH.'log');
        //rrmdir(RUNTIME_PATH.'temp');
        //生成常量
        $config = Db::name('sys_config')->where("`type`='text'")->order('sort asc')->column('value','field');
        $wechatConstContent = file_get_contents(CONF_PATH.'extra/const.tpl');
        $wechatConstContent = str_replace('{domain}', $config['domain'], $wechatConstContent);
        $wechatConstContent = str_replace('{WX_APPID}', $config['wx_appid'], $wechatConstContent);
        $wechatConstContent = str_replace('{WX_APPSECRET}', $config['wx_appsecret'], $wechatConstContent);
        $wechatConstContent = str_replace('{WX_MCHID}', $config['mchid'], $wechatConstContent);
        $wechatConstContent = str_replace('{WX_KEY}', $config['wx_key'], $wechatConstContent);
        $wechatConstContent = str_replace('{WX_HTTPADDR}', $config['wx_http'], $wechatConstContent);
        $wechatConstContent = str_replace('{WX_VIEWURL}', $config['wx_viewurl'], $wechatConstContent);
        $wechatConstContent = str_replace('{WX_TOKEN}', $config['wx_token'], $wechatConstContent);
        $res = file_put_contents(CONF_PATH.'extra/const.php', $wechatConstContent);
        if($res === false){
            $this->ajaxReturn('清除失败', 0);
        }
        $this->ajaxReturn('清除成功', 1);
    }
    
    
}
