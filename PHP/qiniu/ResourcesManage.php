<?php
namespace cloud\qiniu;

use Qiniu\Auth;
use Qiniu\Config;
use Qiniu\Storage\BucketManager;
use Qiniu\Processing\PersistentFop;
use Qiniu\Storage\UploadManager;
use think\facade\Log;

/**
 * 七牛云资源管理类
 * Class ResourcesManage
 * @package app\models
 */
class ResourcesManage
{
    const QINIU_ACCESS_KEY = "xxxxxxxxxxxxxx";
    const QINIU_SECRET_KEY = "xxxxxxxxxxxxxx";
    const QINIU_BUCKET = "xxxx";     //空间
    const QINIU_OUTSIDE_LINK = "https://xxxxx.com";     //空间外链

    public static function getFileInfo($key){
        $auth = new Auth(self::QINIU_ACCESS_KEY,self::QINIU_SECRET_KEY);
        $config = new Config();
        $bucketManager = new BucketManager($auth,$config);
        list($fileInfo, $err) = $bucketManager->stat(self::QINIU_BUCKET, $key);
        if ($err) {
            return $err;
        } else {
            return $fileInfo;
        }
    }

    /**
     * 获取上传凭证uploadToken
     * @return string
     */
    public static function getUploadToken(){
        $auth = new Auth(self::QINIU_ACCESS_KEY,self::QINIU_SECRET_KEY);
        return $auth->uploadToken(self::QINIU_BUCKET);
    }

    /**
     * 压缩要下载的文件
     * @param $key_arr
     * @return bool|mixed
     */
    public static function PackUpFiles($key_arr){
        $auth = new Auth(self::QINIU_ACCESS_KEY,self::QINIU_SECRET_KEY);
        $config = new Config();
        $pfop = new PersistentFop($auth,$config);

        $mkzip_arr = [];
        foreach ($key_arr as $k => $v) {
            //获取需要下载的文件链接
            $mkzip_arr[] = self::DownLoadFile($v);
        }
        //上传索引文件到七牛云
        $key = self::UploadIndexFiles($mkzip_arr);
        if(!$key){
            Log::error("上传索引文件到七牛云失败：".json_encode($key));
            return false;
        }

        $fops = "mkzip/4/";
        $entry = \Qiniu\entry(self::QINIU_BUCKET,uniqid().'.zip');
        $fops .= '|saveas/'.$entry.'/deleteAfterDays/1';//deleteAfterDays为一天后七牛云自动删除压缩文件
        $res = $pfop->execute(self::QINIU_BUCKET,$key,$fops);

        return $res[0];
    }

    public function objList($marker=null, $limit=50) {
        $auth = new Auth(self::QINIU_ACCESS_KEY,self::QINIU_SECRET_KEY);
        $config = new Config();
        $res = (new \Qiniu\Storage\BucketManager($auth, $config))->listFiles('kqy', null, $marker,$limit);
		return ['msg'=>$res[1],'data'=>$res[0]];
	}

    /**
     * 上传到七牛云的索引文件
     * @param $keys_arr
     * @return string
     */
    public static function UploadIndexFiles($keys_arr){
        $txt = "";
        foreach ($keys_arr as $k => $v) {
            $txt .= "/url/".\Qiniu\base64_urlSafeEncode($v)."\r\n";
        }
        ltrim($txt,"\r\n");
        $file_name = uniqid().'.txt';//生成文件名唯一的一个索引文件
        $path = root_path().'public/uploads/qiniu_index_files/';
        $file_path = $path.$file_name;
        if(!is_dir($path)){
            mkdir($path,0777,true);
        }
        //将索引文件保存在本地
        if(file_put_contents($file_path,$txt)){
            //开始上传到七牛云
            return self::UploadToQiniu($file_name,$file_path);
        }else{
            Log::error("UploadIndexFiles 将索引文件保存到本地失败：".json_encode($keys_arr));
            return false;
        }
    }

    /**
     * 上传文件到七牛云
     * @param $key
     * @param $file_path
     * @return mixed
     */
    public static function UploadToQiniu($key,$file_path){
        $auth = new Auth(self::QINIU_ACCESS_KEY,self::QINIU_SECRET_KEY);
        $token = $auth->uploadToken(self::QINIU_BUCKET);
        $uploadMgr = new UploadManager();
        list($ret,$err) = $uploadMgr->putFile($token, $key, $file_path);
        if($err !== null){
            return $err;
        }else{
            return $key;
        }
    }

    /**
     * 获取压缩进度
     * @param $id
     * @return array
     */
    public static function ZipStatus($id){
        $auth = new Auth(self::QINIU_ACCESS_KEY,self::QINIU_SECRET_KEY);
        $config = new Config();
        $pfop = new PersistentFop($auth,$config);
        return $pfop->status($id);
    }

    /**
     * 获取文件下载地址
     * @param $key
     * @return string
     */
    public static function DownLoadFile($key){
        $auth = new Auth(self::QINIU_ACCESS_KEY,self::QINIU_SECRET_KEY);
        // 私有空间中的外链 http://<domain>/<file_key>
        $baseUrl = self::QINIU_OUTSIDE_LINK.'/'.$key;
        // 对链接进行签名
        $signedUrl = $auth->privateDownloadUrl($baseUrl, 259200);
        return $signedUrl;
    }

    /**
     * 从七牛云空间中删除文件
     * @param $key
     * @return mixed
     */
    public static function DeleteFile($key){
        $auth = new Auth(self::QINIU_ACCESS_KEY,self::QINIU_SECRET_KEY);
        $config = new Config();
        $bucketManager = new BucketManager($auth,$config);
        if (is_array($key)) {
        	return $bucketManager->deleteMany(self::QINIU_BUCKET, $key);
		} else {
			return $bucketManager->delete(self::QINIU_BUCKET, $key);
		}
    }

    /**
	 * 视频转码
	 */
    public static function transcoding($key, $style, $save_name, $notifyUrl='', $pipeline=0) {
        $auth = new Auth(self::QINIU_ACCESS_KEY,self::QINIU_SECRET_KEY);
        $config = new Config();
		$pfop = new PersistentFop($auth, $config);

		// 转码是使用的队列名称。
		if ($pipeline == 0) {
			$pipeline = 'default.sys';
		} else {
			$pipeline = 'video_transcoding';
		}
		$force = false;

		$bucket = self::QINIU_BUCKET;

		//要进行转码的转码操作。
		$fops = $style . \Qiniu\base64_urlSafeEncode($bucket . ":" . $save_name);

		list($id, $err) = $pfop->execute($bucket, $key, $fops, $pipeline, $notifyUrl, $force);
		if ($err != null) {
			return $err;
		}

		//查询转码的进度和状态
		list($ret, $err) = $pfop->status($id);
		if ($err != null) {
			return $err;
		} else {
			return $ret;
		}
	}

	/**
	 * 获取音视频元信息
	 */
}