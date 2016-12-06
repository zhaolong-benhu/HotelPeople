<?php
/**
 * 微信常用接口汇总
 * @author Andy.gao@veryeast.cn
 * @since 2015-5-28
 * @see application.components.WeixinComponent.php
 */
class WechatApi{
	public $ApiMap = array(
		'accessToken'=>'https://api.weixin.qq.com/cgi-bin/token?',
		'jsapiTicket'=>'https://api.weixin.qq.com/cgi-bin/ticket/getticket?',
		'oAuth2'=>'https://open.weixin.qq.com/connect/oauth2/authorize?',
		'grantBaseinfo'=>'https://api.weixin.qq.com/sns/oauth2/access_token?',
		'grantUserinfo'=>'https://api.weixin.qq.com/sns/userinfo?',
		'buildCustomMenu'=>'https://api.weixin.qq.com/cgi-bin/menu/create?',
		'getSubscribe'=>'https://api.weixin.qq.com/cgi-bin/user/info?',
		'getMedia'=>'http://file.api.weixin.qq.com/cgi-bin/media/get?',
		'getUserList'=>'https://api.weixin.qq.com/cgi-bin/user/get?',
		'addTempMedia'=>'https://api.weixin.qq.com/cgi-bin/media/upload?',
		'getTempMedia'=>'https://api.weixin.qq.com/cgi-bin/media/get?',
		'addPermMedia'=>'https://api.weixin.qq.com/cgi-bin/material/add_material?',
		'getPermMediaCount'=>'https://api.weixin.qq.com/cgi-bin/material/get_materialcount?',
		'getPermMedia'=>'https://api.weixin.qq.com/cgi-bin/material/get_material?',
		'addPermNews'=>'https://api.weixin.qq.com/cgi-bin/material/add_news?',
		'getPermMediaList'=>'https://api.weixin.qq.com/cgi-bin/material/batchget_material?',
	);
	public $appId = '';
	public $appSecret = '';
	private static $_instance;
	
	public function __construct($config=''){
		if(is_string($config)){
			$config = $this->getConfig($config);
		}
		$this->appId = $config['appId'];
		$this->appSecret = $config['appSecret'];
	}
	
	public function getConfig($configPath=''){
		static $config = array();
		if(!empty($config)){
			return $config;
		}
		if(!is_file($configPath)){
			exit('the config file '.$configPath.' could not be found.');
		}
		$config = require $configPath;
		return $config;
	}
	
	public static function &getInstance($config=''){
		if(self::$_instance===null){
			self::$_instance = new WechatApi($config);
			return self::$_instance;
		}else{
			return self::$_instance;
		}
	}
	
	/**
	 * 非缓存方式获取access_token
	 * 仅限内部使用
	 * @return string
	 */
	public function _getAccessToken(){
		$param = array(
			'grant_type'=>'client_credential',
			'appid'=>$this->appId,
			'secret'=>$this->appSecret,
		);
		$url = $this->ApiMap['accessToken'];
		$url.= http_build_query($param);
		$json = file_get_contents($url);
		$array = json_decode($json,true);
		return $array['access_token'];
	}
	
	/**
	 * 非缓存方式获取ticket
	 * 仅限内部使用
	 * @return string
	 */
	private function _getJsapiTicket(){
		$param = array(
				'access_token'=>$this->_getAccessToken(),
				'type'=>'jsapi',
		);
		$url = $this->ApiMap['jsapiTicket'];
		$url.= http_build_query($param);
		$json = file_get_contents($url);
		$array = json_decode($json,true);
		return $array['ticket'];
	}
	
	/**
	 * 获取公众号的access_token
	 * @param string $cacheFile
	 * @param number $ttl
	 * @return string
	 */
	public function getAccessTokenCache($cacheFile='accessToken',$ttl=7200){
		if(file_exists($cacheFile) && filemtime($cacheFile)>time()-$ttl){
			return file_get_contents($cacheFile);
		}else{
			$accessToken = $this->_getAccessToken();
			file_put_contents($cacheFile,$accessToken);
			return $accessToken;
		}
	}
	
	/**
	 * 获取jssdk配置生成使用的ticket
	 * @param string $cacheFile
	 * @param number $ttl
	 * @return string|mixed
	 */
	public function getJsapiTicketCache($cacheFile='jsapiTicket',$ttl=7200){
		if(file_exists($cacheFile) && filemtime($cacheFile)>time()-$ttl){
			return file_get_contents($cacheFile);
		}else{
			$jsapiTicket = $this->_getJsapiTicket();
			file_put_contents($cacheFile,$jsapiTicket);
			return $jsapiTicket;
		}
	}
	
	/**
	 * 生成jssdk接口配置
	 * @param string $imgUrl展示图片的完整url
	 * @return array
	 */
	public function getSignature($imgUrl=''){
		$jsapiTicket = $this->getJsapiTicketCache();
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$nonceStr = "";
		for($i=0;$i<16;$i++) {
			$nonceStr .= substr($chars,mt_rand(0,strlen($chars)-1),1);
		}
		$timestamp = time();
		$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
		$shareUrl = $protocol.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		$rawString = 'jsapi_ticket='.$jsapiTicket.'&noncestr='.$nonceStr.'&timestamp='.$timestamp.'&url='.$shareUrl;
		$return = array(
			"appId"=>$this->appId,
			"nonceStr"=>$nonceStr,
			"timestamp"=>$timestamp,
			"url"=>$shareUrl,
			"signature"=>sha1($rawString),
			"rawString"=>$rawString,
			'imgUrl'=>$imgUrl,
		);
		return $return;
	}
	
	/**
	 * 发起微信授权请求
	 * @param string $redirectUrl回调地址必须在公众号后台设置好
	 * @param string $scope如果snsapi_base静默请求如果snsapi_userinfo呈现页面让用户选择是否授权
	 * @param string $state
	 */
	public function oAuth2Request($redirectUrl,$scope='snsapi_base',$state=''){
		$param = array(
			'appid'=>$this->appId,
			'redirect_uri'=>$redirectUrl,
			'response_type'=>'code',
			'scope'=>$scope,
		);
		if(!empty($state)){
			$param['state'] = $state;
		}
		$url = $this->ApiMap['oAuth2'];
		$url .= http_build_query($param).'#wechat_redirect';
		header('Location:'.$url);
	}
	
	/**
	 * 微信获取获取当前访问用户的基本信息
	 * 仅包含openid和进一步获取更多信息的access_token
	 * @param string $code微信回调参数
	 * @return mixed
	 */
	public function getGrantedBaseinfo($code){
		$param = array(
			'appid'=>$this->appId,
			'secret'=>$this->appSecret,
			'code'=>$code,
			'grant_type'=>'authorization_code',
		);
		$url = $this->ApiMap['grantBaseinfo'];
		$url .= http_build_query($param);
		$json = file_get_contents($url);
		return $json;
	}
	
	/**
	 * 微信授权获取指定用户的个人信息
	 * 包括没有关注公众号的用户
	 * 只有认证服务号有获取权限
	 * @param unknown $openId
	 * @param unknown $authAccessToken
	 * @return string
	 */
	public function getGrantedUserinfo($openId,$authAccessToken){
		$param = array(
			'access_token'=>$authAccessToken,
			'openid'=>$openId,
			'lang'=>'zh_CN',
		);
		$url = $this->ApiMap['grantUserinfo'];
		$url .= http_build_query($param);
		$json = file_get_contents($url);
		return $json;
	}
	
	/**
	 * 创建自定义菜单
	 * @param unknown $menuData
	 */
	public function buildCustomMenu($menuData){
		$param = array(
			'access_token'=>$this->_getAccessToken(),
		);
		$url = $this->ApiMap['buildCustomMenu'];
		$url .= http_build_query($param);
		$array = json_decode($this->curlPost($url,$menuData),true);
		if($array['errcode']){
			exit($array['errmsg']);
		}
		echo "Successfully built at ".date("Y-m-d h:i:s");
	}
	
	/**
	 * curl post方式上传本地临时素材
	 * 其中media参数为本地文件路径如@E:\image\1.jpg
	 * @param string/array $postData
	 * @param string $type
	 * @return mixed
	 */
	public function addTempMedia($postData,$type='image'){
		$validType = array('image','voice','video','thumb');
		if(!in_array($type, $validType)){
			exit('the media type '.$type.' is invalid.');
		}
		$url = $this->ApiMap['addTempMedia'];
		$url.= 'access_token='.$this->_getAccessToken();
		$url.= '&type='.$type;
		$json = $this->curlPost($url,$postData);
		return $json;
	}
	
	/**
	 * 获取临时素材metadata
	 * @param unknown $mediaId
	 * @return string
	 */
	public function getTempMedia($mediaId){
		$url = $this->ApiMap['getTempMedia'];
		$param = array(
			'access_token'=>$this->_getAccessToken(),
			'media_id'=>$mediaId
		);
		$url.= http_build_query($param);
		$content = file_get_contents($url);
		return $content;
	}
	
	/**
	 * curl post方式上传本地永久素材
	 * @param string/array @E:\image\1.jpg
	 * @param string $type
	 * @return mixed
	 */
	public function addPermMedia($postData,$type='image'){
		$url = $this->ApiMap['addPermMedia'];
		$url.= 'access_token='.$this->_getAccessToken();
		if(is_array($postData) && isset($postData['media'])){
			$postData = array_merge(array('type'=>$type),$postData);
		}elseif(is_string($postData)){
			$postData = array(
				'type'=>$type,
				'media'=>$postData,
			);
		}else{
			exit('the media data is invalid.');
		}
		$json = $this->curlPost($url,$postData);
		return $json;
	}
	
	public function getPermMediaCount(){
		$url = $this->ApiMap['getPermMediaCount'];
		$url.= 'access_token='.$this->_getAccessToken();
		$json = file_get_contents($url);
		return $json;
	}
	
	/**
	 * 获取已关注用户的个人信息
	 * 如果未关注返回{'subscribe':0,'openid':'XXX'}
	 * @param unknown $openid
	 * @return string
	 */
	public function getSubscribe($openid){
		$url = $this->ApiMap['getSubscribe'];
		$url .= 'access_token='.$this->_getAccessToken();
		$url .= '&openid='.$openid;
		$json = file_get_contents($url);
		return $json;
	}
	
	/**
	 * 获取图片素材的metadata
	 * @param unknown $mediaId
	 * @return string
	 */
	public function getMedia($mediaId){
		$url = $this->ApiMap['getMedia'];
		$param = array(
				'access_token'=>$this->_getAccessToken(),
				'media_id'=>$mediaId,
		);
		$url .= http_build_query($param);
		$content = file_get_contents($url);
		return $content;
	}
	
	/**
	 * 获取10000个用户的openid
	 * @param string $next_openid
	 * @return string
	 */
	public function getUserList($next_openid=''){
		$url = $this->ApiMap['getUserList'];
		$param = array(
				'access_token'=>$this->_getAccessToken(),
				'next_openid'=>$next_openid,
		);
		$url .= http_build_query($param);
		$json = file_get_contents($url);
		return $json;
	}
	
	public function arrayRecursive(&$array,$function,$applyToKeysAlso=false){
		static $recursiveCounter = 0;
		if (++$recursiveCounter > 1000) {
			die('possible deep recursion attack');
		}
		foreach ($array as $key => $value) {
			if (is_array($value)) {
				$this->arrayRecursive($array[$key], $function, $applyToKeysAlso);
			} else {
				$array[$key] = $function($value);
			}
			if ($applyToKeysAlso && is_string($key)) {
				$newKey = $function($key);
				if ($newKey != $key) {
					$array[$newKey] = $array[$key];
					unset($array[$key]);
				}
			}
		}
		$recursiveCounter--;
	}
	
	/**
	 * post方式发起请求获取返回值
	 * @param unknown $url
	 * @param string $data
	 * @return mixed
	 */
	public function curlPost($url,$data=''){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
		$output = curl_exec($ch);
		if(curl_errno($ch)>0){
			exit(curl_error($ch));
		}
		curl_close($ch);
		return $output;
	}
	
}