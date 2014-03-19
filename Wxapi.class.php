<?php
class OAuthException extends Exception {
     // pass
}
/**
* @package wx
* @author Jianjun Deng
* @version 1.0
*/
class WxAuthV2 {
     public $access_token;
     public $host = "https://api.weixin.qq.com/cgi-bin/";
     public $timeout = 30;
     public $connecttimeout = 30;
     public $ssl_verifypeer = FALSE;
     public $format = '?';
     public $decode_json = TRUE;
     public $http_info;
     public static $boundary = '';
     function __construct($access_token = NULL) {
          $this->access_token = $access_token;
     }
     function base64decode($str) {
          return base64_decode(strtr($str.str_repeat('=', (4 - strlen($str) % 4)), '-_', '+/'));
     }
     /**
     * GET wrappwer for oAuthRequest.
     *
     * @return mixed
     */
     function get($url, $parameters = array()) {
          $response = $this->oAuthRequest($url, 'GET', $parameters);
          if ($this->format === '?' && $this->decode_json) {
               return json_decode($response, true);
          }
          return $response;
     }

     /**
     * POST wreapper for oAuthRequest.
     *
     * @return mixed
     */
     function post($url, $parameters = array(), $multi = false) {
          $response = $this->oAuthRequest($url, 'POST', $parameters, $multi );
          if ($this->format === '?' && $this->decode_json) {
               return json_decode($response, true);
          }
          return $response;
     }

     /**
     * DELTE wrapper for oAuthReqeust.
     *
     * @return mixed
     */
     function delete($url, $parameters = array()) {
          $response = $this->oAuthRequest($url, 'DELETE', $parameters);
          if ($this->format === 'json' && $this->decode_json) {
               return json_decode($response, true);
          }
          return $response;
     }

     /**
     * Format and sign an OAuth / API request
     *
     * @return string
     * @ignore
     */
     function oAuthRequest($url, $method, $parameters, $multi = false) {

          if (strrpos($url, 'http://') !== 0 && strrpos($url, 'https://') !== 0) {
               $url = "{$this->host}{$url}{$this->format}"."access_token=".$this->access_token;
     }

     switch ($method) {
          case 'GET':
               $url = $url . '&' . http_build_query($parameters);
               return $this->http($url, 'GET');
          default:
               $headers = array();
               if (!$multi && (is_array($parameters) || is_object($parameters)) ) {
                    $body = $this->ch_json_encode($parameters);
               } else {
                    $body = self::build_http_query_multi($parameters);
                    $headers[] = "Content-Type: multipart/form-data; boundary=" . self::$boundary;
               }
               return $this->http($url, $method, $body, $headers);
     }
     }

     /**
     * Make an HTTP request
     *
     * @return string API results
     * @ignore
     */
     function http($url, $method, $postfields = NULL, $headers = array()) {
          $this->http_info = array();
          $ci = curl_init();
          /* Curl settings */
          curl_setopt($ci, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
          curl_setopt($ci, CURLOPT_RETURNTRANSFER, TRUE);
          curl_setopt($ci, CURLOPT_ENCODING, "");
          curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, $this->ssl_verifypeer);
          curl_setopt($ci, CURLOPT_HEADERFUNCTION, array($this, 'getHeader'));
          curl_setopt($ci, CURLOPT_HEADER, FALSE);

          switch ($method) {
               case 'POST':
                    curl_setopt($ci, CURLOPT_POST, TRUE);
                    if (!empty($postfields)) {
                         curl_setopt($ci, CURLOPT_POSTFIELDS, $postfields);
                         $this->postdata = $postfields;
                    }
                    break;
          }
          curl_setopt($ci, CURLOPT_URL, $url );
          curl_setopt($ci, CURLOPT_HTTPHEADER, $headers );
          curl_setopt($ci, CURLINFO_HEADER_OUT, TRUE );
          $response = curl_exec($ci);
          $this->http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
          $this->http_info = array_merge($this->http_info, curl_getinfo($ci));
          $this->url = $url;
          curl_close ($ci);
          return $response;
     }

     /**
     * Get the header info to store.
     *
     * @return int
     * @ignore
     */
     function getHeader($ch, $header) {
          $i = strpos($header, ':');
          if (!empty($i)) {
               $key = str_replace('-', '_', strtolower(substr($header, 0, $i)));
               $value = trim(substr($header, $i + 2));
               $this->http_header[$key] = $value;
          }
          return strlen($header);
     }

     /**
     * @ignore
     */
     public static function build_http_query_multi($params) {
          if (!$params) return '';

          uksort($params, 'strcmp');

          $pairs = array();

          self::$boundary = $boundary = uniqid('------------------');
          $MPboundary = '--'.$boundary;
          $endMPboundary = $MPboundary. '--';
          $multipartbody = '';

          foreach ($params as $parameter => $value) {

               if( in_array($parameter, array('pic', 'image')) && $value{0} == '@' ) {
                    $url = ltrim( $value, '@' );
                    $content = file_get_contents( $url );
                    $array = explode( '?', basename( $url ) );
                    $filename = $array[0];

                    $multipartbody .= $MPboundary . "\r\n";
                    $multipartbody .= 'Content-Disposition: form-data; name="' . $parameter . '"; filename="' . $filename . '"'. "\r\n";
                    $multipartbody .= "Content-Type: image/unknown\r\n\r\n";
                    $multipartbody .= $content. "\r\n";
               } else {
                    $multipartbody .= $MPboundary . "\r\n";
                    $multipartbody .= 'content-disposition: form-data; name="' . $parameter . "\"\r\n\r\n";
                    $multipartbody .= $value."\r\n";
               }

          }

          $multipartbody .= $endMPboundary;
          return $multipartbody;
     }
     /**
      * 对数组和标量进行 urlencode 处理
      * 通常调用 wphp_json_encode()
      * 处理 json_encode 中文显示问题
      * @param array $data
      * @return string
      */
     function wphp_urlencode($data) {
     	if (is_array($data) || is_object($data)) {
     		foreach ($data as $k => $v) {
     			if (is_scalar($v)) {
     				if (is_array($data)) {
     					$data[$k] = urlencode($v);
     				} else if (is_object($data)) {
     					$data->$k = urlencode($v);
     				}
     			} else if (is_array($data)) {
     				$data[$k] = $this->wphp_urlencode($v); //递归调用该函数
     			} else if (is_object($data)) {
     				$data->$k = $this->wphp_urlencode($v);
     			}
     		}
     	}
     	return $data;
     }
     /**
      * json 编码
      *
      * 解决中文经过 json_encode() 处理后显示不直观的情况
      * 如默认会将“中文”变成"\u4e2d\u6587"，不直观
      * 如无特殊需求，并不建议使用该函数，直接使用 json_encode 更好，省资源
      * json_encode() 的参数编码格式为 UTF-8 时方可正常工作
      *
      * @param array|object $data
      * @return array|object
      */
     public function ch_json_encode($data) {
     	$ret = $this->wphp_urlencode($data);
     	$ret = json_encode($ret);
     	return urldecode($ret);
     }
}

class WxApi
{
	 var $oauth;
     /**
     * 构造函数
     *
     * @access public
     * @param mixed $access_token OAuth认证返回的token
     * @return void
     */
     function __construct($access_token)
     {
          $this->oauth = new WxAuthV2($access_token);
     }
     /**
      * 查询分组 API：http://mp.weixin.qq.com/wiki/index.php?title=%E5%88%86%E7%BB%84%E7%AE%A1%E7%90%86%E6%8E%A5%E5%8F%A3
      * 
     */
     function groups_get()
     {
          $params = array();
          return $this->oauth->get('groups/get', $params);//可能是接口的bug不能补全
     }
     /**
      * 创建分组  API：http://mp.weixin.qq.com/wiki/index.php?title=%E5%88%86%E7%BB%84%E7%AE%A1%E7%90%86%E6%8E%A5%E5%8F%A3#.E5.88.9B.E5.BB.BA.E5.88.86.E7.BB.84
      * $name  分组名字（30个字符以内）
      */
     function groups_create($name){
     	  $params = array("");
     	  $params['group']['name'] = trim($name);
     	  return $this->oauth->post('groups/create',$params );
     }
     /**
      * 修改分组名   API：http://mp.weixin.qq.com/wiki/index.php?title=%E5%88%86%E7%BB%84%E7%AE%A1%E7%90%86%E6%8E%A5%E5%8F%A3#.E5.88.9B.E5.BB.BA.E5.88.86.E7.BB.84
      * POST数据例子：{"group":{"id":108,"name":"test2_modify2"}}
      * $id 分组id，由微信分配
      * $name 分组名字（30个字符以内）
      */
     function groups_update($id,$name){
     	$params = array("");
     	$params['group']['id'] = $this->id_format($id);
     	$params['group']['name'] = trim($name);
     	return $this->oauth->post('groups/update',$params);
     }
     /**
      * 移动用户分组   API：http://mp.weixin.qq.com/wiki/index.php?title=%E5%88%86%E7%BB%84%E7%AE%A1%E7%90%86%E6%8E%A5%E5%8F%A3#.E5.88.9B.E5.BB.BA.E5.88.86.E7.BB.84
      * {"openid":"oDF3iYx0ro3_7jD4HFRDfrjdCM58","to_groupid":108}
      *  $openid 用户唯一标识符  
      *  $to_groupid 分组id
      * $name 分组名字（30个字符以内）
      */
     function groups_members_update($openid,$to_groupid){
     	$params = array("");
     	$params['openid'] =$openid;
     	$params['to_groupid'] =$to_groupid;
     	return $this->oauth->post('groups/members/update',$params);
     }
     /**
      * 获取关注列表 一次最多返回1万
      * http://mp.weixin.qq.com/wiki/index.php?title=%E8%8E%B7%E5%8F%96%E5%85%B3%E6%B3%A8%E8%80%85%E5%88%97%E8%A1%A8
      * 
      * 
      */
     function user_get($next_openid="")
     {
     	$params = array();
     	$params['next_openid']=$next_openid;
     	return $this->oauth->get('user/get', $params);//可能是接口的bug不能补全
     }
     /**
      * 获取用户基本信息
      *Array ( [subscribe] => 1 
      *[openid] => of76zt-k-bMZZaCip16MKfGAigec 
      *[nickname] => _魏什么。 
      *[sex] => 1 
      *[language] => zh_TW 
      *[city] => 福州 
      *[province] => 福建
      *[country] => 中国
      *[headimgurl] => http://wx.qlogo.cn/mmopen/kaTUtbf9iaBY32aSBLkcxWVDicjlhHSiapLWDcia1ic948tYdmhJQLQa8FpB7MdqpDNQHOVUWoVfPHjlymJ1z1fyStAUyduicvAbmh/0 
      *[subscribe_time] => 1381576837 )
      */
     function user_info($openid="")
     {
     	$params = array();
     	$params['openid']=$openid;
     	return $this->oauth->get('user/info', $params);//可能是接口的bug不能补全
     }
     /*
      * 发送客服文本消息 
      * http://mp.weixin.qq.com/wiki/index.php?title=%E5%8F%91%E9%80%81%E5%AE%A2%E6%9C%8D%E6%B6%88%E6%81%AF
      */
     function message_custom_send_text($touser,$content){
     	$params = array("");
     	$params['touser']=trim($touser);
     	$params['msgtype']="text";
     	$params['text']["content"]=$content;
     	return $this->oauth->post('message/custom/send',$params);
     }
     /*
      * 发送客服图片消息
     * http://mp.weixin.qq.com/wiki/index.php?title=%E5%8F%91%E9%80%81%E5%AE%A2%E6%9C%8D%E6%B6%88%E6%81%AF
     *  access_token	 是	 调用接口凭证
		touser	 是	 普通用户openid
		msgtype	 是	 消息类型，image
		media_id	 是	 发送的图片的媒体ID
     */
     function message_custom_send_image($touser,$media_id){
     	$params = array("");
     	$params['touser']=trim($touser);
     	$params['msgtype']="image";
     	$params['image']["media_id"]=$media_id;
     	return $this->oauth->post('message/custom/send',$params);
     }
     /*
      * 发送客服语音消息
     * http://mp.weixin.qq.com/wiki/index.php?title=%E5%8F%91%E9%80%81%E5%AE%A2%E6%9C%8D%E6%B6%88%E6%81%AF
     *  access_token	 是	 调用接口凭证
     touser	 是	 普通用户openid
     msgtype	 是	 消息类型，image
     media_id	 是	 发送的图片的媒体ID
     */
     function message_custom_send_voice($touser,$media_id){
     	$params = array("");
     	$params['touser']=trim($touser);
     	$params['msgtype']="voice";
     	$params['voice']["media_id"]=$media_id;
     	return $this->oauth->post('message/custom/send',$params);
     }
     /*
      * 发送客服图文消息 图文消息条数限制在10条以内。
     * http://mp.weixin.qq.com/wiki/index.php?title=%E5%8F%91%E9%80%81%E5%AE%A2%E6%9C%8D%E6%B6%88%E6%81%AF
     *  access_token	 是	 调用接口凭证
     *  touser	 是	 普通用户openid
     *  msgtype	 是	 消息类型，image
     *  media_id	 是	 发送的图片的媒体ID
     *  $newsArray[0]=array(
         "title"=>"",
         "description"=>"",
         "url"=>"",
         "picurl"=>""
     )
     */
     function message_custom_send_news($touser,$newsArray){
     	$params = array("");
     	$params['touser']=trim($touser);
     	$params['msgtype']="news";
     	$params['news']["articles"]=$newsArray;
     	return $this->oauth->post('message/custom/send',$params);
     }
     
     /*
      * 创建临时二维码ticket 
      * http://mp.weixin.qq.com/wiki/index.php?title=%E7%94%9F%E6%88%90%E5%B8%A6%E5%8F%82%E6%95%B0%E7%9A%84%E4%BA%8C%E7%BB%B4%E7%A0%81
      * expire_seconds	 该二维码有效时间，以秒为单位。 最大不超过1800。
	  *	action_name	 二维码类型，QR_SCENE为临时,QR_LIMIT_SCENE为永久
	  *	action_info	 二维码详细信息
	  *	scene_id	 场景值ID，临时二维码时为32位整型，永久二维码时最大值为1000
      */
     function qrcode_create_scene($expire_seconds="900",$scene_id){
     	$params = array("");
     	$params['expire_seconds']=$expire_seconds;
     	$params['action_name']="QR_SCENE";
     	$params['action_info']["scene"]["scene_id"]=$scene_id;
     	return $this->oauth->post('qrcode/create',$params);
     	
     }
     /*
      * 创建永久二维码ticket
     * http://mp.weixin.qq.com/wiki/index.php?title=%E7%94%9F%E6%88%90%E5%B8%A6%E5%8F%82%E6%95%B0%E7%9A%84%E4%BA%8C%E7%BB%B4%E7%A0%81
     * expire_seconds	 该二维码有效时间，以秒为单位。 最大不超过1800。
     *	action_name	 二维码类型，QR_LIMIT_SCENE为永久
     *	action_info	 二维码详细信息
     *	scene_id	 场景值ID，临时二维码时为32位整型，永久二维码时最大值为1000
     */
     function qrcode_create_forever($action_name="",$scene_id){
     	$params = array("");
     	$params['action_name']="QR_LIMIT_SCENE";
     	$params['action_info']["scene"]["scene_id"]=$scene_id;
     	return $this->oauth->post('qrcode/create',$params);
     }
     /*
      * 通过ticket换取二维码
      * 
      */
     function showqrcode($ticket){
     	return file_get_contents("https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket={$ticket}");
     }
     
     protected function id_format(&$id) {
          if ( is_float($id) ) {
               $id = number_format($id, 0, '', '');
          } elseif ( is_string($id) ) {
               $id = trim($id);
          }
     }

     
     /**
	  *
	  * @param type 包括(image, voice, video, thumb)
	  *
	  */
     public function upload_media($fileContent, $fileName, $access_token, $type, $contentType="application/octet-stream") {
     	$s = new Wxupload();
     	$result = $s->upload($fileContent, $fileName, $access_token, $type, $contentType);
     	return $result;
     }
     
     
}