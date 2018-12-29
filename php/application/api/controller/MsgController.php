<?php
namespace app\api\controller;

use app\api\model\OpenTmModel;
use app\common\BaseController;
use think\Db;

/**
 * 消息类
 * Enter description here ...
 * @author yihong
 *
 */
class MsgController extends BaseController
{

    /**
     * 接口返回数据
     * @param $msg
     * @param int $status
     * @param array $data
     */
    protected function returnApiData($msg, $status=200, $data=[]){
        $res['code']    = $status;
        $res['message'] = $msg;
        $res['data']    = $data;
        header('content-type:application/json;charset=utf8');
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        Db::rollback();
        exit;
    }

    /**
     * 推送新产品给用户w
     * @return bool
     */
    public function sendMsgToWechat(){
        $product = Db::name('timer_action')->field('id,progress,correlation_id')->where(['results'=>1,'type'=>1])->find();
        if(!empty($product)){
            $progress = $product['progress'];
            $product_id = $product['correlation_id'];
            if($product_id){
                $productName = Db::name('product')->where(['product_id'=>$product_id])->value('product_name');
                if($productName){
                    $data['title'] = '最新爆品推荐';
                    $data['keyword1'] = '爆品推荐';
                    $data['keyword2'] = $productName;
                    $data['keyword3'] = date('Y-m-d H:i:s');
                    $data['keyword4'] = '爆品推荐';
                    $data['remark'] = '点击查看详情';
                }else{
                    GLog('Share Proudct To Wecaht','分享产品不存在');
                    $this->returnApiData('empty',400);
                }
                $host =  $_SERVER['HTTP_HOST']?$_SERVER['HTTP_HOST']: $_SERVER['SERVER_NAME'];
                $url = $host. '/wechat_html/page/homePage/productDetails.html?productId='.$product_id;
                $accessToken =  Db::name('access_token')->value('access_token');
//                OpenTmModel::sendTplmsg6('oRSVB5uyiww45nudzxB1ZBF9qGZM',$data,$accessToken,$url);
                //获取所有微信用户openid
                $openidList = Db::name("user_connect")->where(array('platform'=>'wechat'))->field('openid')->page($product['progress'],100)->select();
                if(count($openidList)){
                    foreach ($openidList as $val){
                        if(isset($val['openid']) && $val['openid']){
                            //发送消息给每个微信用户
                            $res = OpenTmModel::sendTplmsg6($val['openid'],$data,$accessToken,$url);
                            // todo 更新用户关注状态(1月初删除)
                            if(intval($res['errcode'])===43004){ //微信返回提示用户未关注
                                Db::name('user_connect')->where(array('platform'=>'wechat','openid'=>$val['openid']))->update(array('subscribe'=>0));
                            }else{
                                Db::name('user_connect')->where(array('platform'=>'wechat','openid'=>$val['openid']))->update(array('subscribe'=>1));
                            }
                        }
                    }
                    if($progress==1){
                        $update['starttime'] = time();
                    }
                    $progress++;
                    $update['progress'] = $progress;
                    Db::name('timer_action')->where(array('id'=>$product['id']))->update($update);
                }else{ //循环完毕
                    GLog('Share Proudct To Wecaht','推送完毕，最后SQL为：'.Db::name("user_connect")->getLastSql());
                    Db::name('timer_action')->where(array('id'=>$product['id']))->update(array('results'=>2));
                }
                $this->returnApiData('ok',200);
            }
        }
        $this->returnApiData('empty',400);
    }

}
