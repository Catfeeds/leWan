<?php
namespace app\api\controller;

use app\common\BaseController;
use OSS\Core\OssException;
use OSS\OssClient;
use think\Config;

/**
 * 图片文件类
 * Enter description here ...
 * @author yihong
 *
 */
class UploadController
{
    private $fileName;
    private $filepath;

    public function __construct(){
        vendor('ossphpsdk.autoload');
    }

    /**
     *
     * UploadController constructor.
     * @param string $fileName 保存路径
     * @param string $filepath 源文件
     */
    public function upUserAuthenticatePicToOss(){
        $scope =  input("post.scope",'idcard').'/';
        $positive = input("file.positive");
        $this->getInfoByFile($positive,$scope);
    }

    /**
     *文件流上传
     * UploadController constructor.
     * @param string $fileName 保存路径
     * @param string $filepath 源文件
     */
    public function upStreamUserAuthenticatePicToOss(){
        $base64_img =  input("post.stream");
        $scope =  input("post.scope",'idcard').'/';
        $folder = date('Ymd');
        $up_dir = ROOT_PATH .'' . DS .'uploads'.DS. $folder.DS;//存放在当前目录的upload文件夹下
        if(!file_exists($up_dir)){
            mkdir($up_dir,0777);
        }
        if(preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_img, $result)){
            $type = $result[2];
            if(in_array($type,array('pjpeg','jpeg','jpg','gif','bmp','png'))){
                $new_file_name = date('YmdHis_').'.'.$type;
                $new_file = $up_dir.$new_file_name;
                if(file_put_contents($new_file, base64_decode(str_replace($result[1], '', $base64_img)))){
                    $this->fileName = 'uploads/'.($scope?$scope:''). date("Ymd") . '/' . sha1(date('YmdHis', time()) . uniqid()) . $type;
                    $this->filepath =  $new_file;
                    $this->ossUpload();
                    unlink($new_file);
                }else{
                    return $this->ajaxReturn( '文件格式不在允许范围内哦', 400);
                }
            }else{
                return $this->ajaxReturn('文件错误',400);
            }
        }
    }

    /**
     * 上传图片到OSS，已确定文件源与上传目录
     */
    public function upPicToOss(){
        $this->fileName =input('fileName');
        $this->filepath =  input('filepath');
        $this->ossUpload();
    }

    public function getInfoByFile($file,$scope=''){
        if ($file) {
            $name = $file->getInfo()['name'];
            $format = strrchr($name, '.');//截取文件后缀名如 (.jpg)
            /*判断图片格式*/
            $allow_type = ['.jpg', '.jpeg', '.gif', '.bmp', '.png'];
            if (! in_array($format, $allow_type)) {
                return $this->ajaxReturn( '文件格式不在允许范围内哦', 400);
            }
            $this->fileName = 'uploads/'.($scope?$scope:''). date("Ymd") . '/' . sha1(date('YmdHis', time()) . uniqid()) . $format;
            $this->filepath =  $file->getInfo()['tmp_name'];
            $this->ossUpload();
        }else{
            return $this->ajaxReturn( '文件为空', 400);
        }
    }

    /**
     * 上传到OSS服务器
     * @return mixed
     */
    public function ossUpload(){
        // 尝试执行
        try {
            //区别环境，不同环境不同文件夹保存
            if(DEPLOY_ENV != 'pro'){
                $this->fileName = DEPLOY_ENV.'/'. $this->fileName;
            }

            $config =  Config::get('aliyun_oss');
            //实例化对象 将配置传入
            $ossClient = new OssClient($config['KeyId'], $config['KeySecret'], $config['Endpoint']);
            //这里是有sha1加密 生成文件名 之后连接上后缀
            //执行阿里云上传
            $result = $ossClient->uploadFile($config['Bucket'],  $this->fileName, $this->filepath);
            /*组合返回数据*/
            return $this->ajaxReturn('ok', 200,array('url'=>Config::get('OSS_HOST').$this->fileName));
        } catch (OssException $e) {
            GLog( 'OSS UPload',$e->getMessage());
            return $this->ajaxReturn($e->getMessage(), 400);
        }
    }

    /**
     * 接口返回数据
     * @param $msg
     * @param int $status
     * @param array $data
     */
    protected function ajaxReturn($msg, $status=200, $data=[]){
        $res['code']    = $status;
        $res['message'] = $msg;
        $res['data']    = $data;
        header('content-type:application/json;charset=utf8');
        ob_clean();
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        exit;
    }


}
