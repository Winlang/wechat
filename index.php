<?php
	define('TOKEN','zhangwenliang');

	if(!isset($_GET['echostr']))
	{
		//调用响应消息函数
		responseMsg();
	}
	else
	{
		//实现网址接入
		valid();
	}


	function valid(){
		if(checkSignature())
		{
			//针对网站接入的判断
			$echostr = $_GET['echostr'];
			if($echostr)
			{
				echo $echostr;
				exit();
			}
		
		}
	}

	//封装函数
	function checkSignature(){
		//获取微信服务器传递的四个参数
		$signature = $_GET['signature'];
		$timestamp = $_GET['timestamp'];
		$nonce = $_GET['nonce'];

		//定义一个数组 存储三个参数 分别是timestamp,nonce,token
		$tempArr = array($nonce,$timestamp,TOKEN);
		
		//进行排序
		sort($tempArr,SORT_STRING);

		//将数组转换成字符串
		$tmpStr = implode($tempArr);

		//进行sha1加密算法
		$tmpStr = sha1($tmpStr);

		//判断请求是否来自微信   对比$signature,和$tmpStr
		if($signature == $tmpStr){
			return true;
		}else{
			return false;
		}
	}

	//响应消息
	function responseMsg(){
		//获取post数据  （以下两种获取post数据方式都可以）
		//全局
		$postData = $GLOBALS["HTTP_RAW_POST_DATA"];
		
		if(!$postData)
		{
			echo 'error';
			exit();
		}
		
		//2,解析xml数据包
		$object = simplexml_load_string($postData, 'SimpleXMLElement',LIBXML_NOCDATA); 

		//获取消息类型
		$MsgType = $object->MsgType;
		switch ($MsgType) {
			case 'event':
				switch ($object->Event) {
					//关注事件
					case 'subscribe':
					//扫描带参数的二维码，用户未关注时，进行关注后的事件
						if(!empty($object->EventKey)){
							//做相关处理
						}
						$content = "欢迎您关注我的公众账号,这里我也没想清楚到底要干什么。^_^";
						echo replyText($object,$content);
						break;
					
					case 'unsubscribe':
						# code...
						break;
					case 'SCAN':
						break;

					//自定义菜单事件
					case 'CLICK':
						break;
				}
			case 'text':
				echo receiveText($object);
				break;
			case 'image':
				echo receiveImage($object);
				break;
			case 'location':
				$locationArr = receiveLocation($object);
				
				//curl处理GET数据
				//$ch = curl_init();
				//$url ="http://api100.duapp.com/joke/?appkey=0020130430&appsecert=fa6095e113cd28fd";
				//$url ="http://api.map.baidu.com/telematics/v3/weather?location=".$locationArr['Location_Y'].",".$locationArr['Location_X']."&output=json&ak=l1W6mpW1c5x1m1RKjDkoYqpj";
					//curl处理GET数据
				$ch = curl_init();
				//$url ="http://api100.duapp.com/joke/?appkey=0020130430&appsecert=fa6095e113cd28fd";
				//$url ="http://api.map.baidu.com/telematics/v3/weather?location=".$locationArr['Location_X'].",".$locationArr['Location_Y']."&output=json&ak=l1W6mpW1c5x1m1RKjDkoYqpj";
				$url = "http://api.map.baidu.com/telematics/v3/weather?location=北京&output=json&ak=l1W6mpW1c5x1m1RKjDkoYqpj";
				curl_setopt($ch,CURLOPT_URL,$url);
				curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
				$outopt = curl_exec($ch);
				curl_close($ch);
				$weather_arr = json_decode($outopt,true);

				$dataArr = array();
				$tempArr = $weather_arr['results'][0]['weather_data'];
				foreach($tempArr as $v){
					$dataArr[] = array(
						"Title" => $v['date'],
						"Desctiption" => $v['weather']." ".$v['wind']." ".$v['temperature'],
						"PicUrl" => $v['dayPictureUrl'],
						"Url" => ""
						);
				}
				echo replyNews($object,$dataArr);
				break;
			case 'voice':
				echo receiveVoice($object);
				break;
			case 'video':
				echo receiveVideo($object);
				break;
			case 'link':
				echo receiveLink($object);
				break;
		}
	}

	//接受文本消息	
	function receiveText($obj){
		//获取消息的文本内容
		$content = $obj->Content;
		//截取后两个汉字是否为天气
		//发送文本消息
		return replyText($obj,$content);
	}

	//接受图片消息
	function receiveImage($obj){
		//接受图片消息的内容
		$imageArr = array(
			"PicUrl" => $obj->PicUrl,
			"MediaId" => $obj->MediaId
			);
		//发送图片消息
		return replyImage($obj,$imageArr);
	}
	//接受地理位置
	function receiveLocation($obj){
		//获取地理位置消息的内容
		$locationArr = array(
			'Location_X' => $obj->Location_X,
			'Location_Y' => $obj->Location_Y,
			'Label' => "当前地址为:".$obj->Label
			);
		//回复地理消息
		return $locationArr;
	}

	//接受语音消息
	function receiveVoice($obj){
		//获取语音消息内容
		$voiceArr = array(
			'MediaId' => $obj->MediaId,
			'Format' => $obj->Format 
			);
		//回复语音消息
		return replyVoice($obj,$voiceArr);
	}

	function receiveVideo($obj){
		//获取视频消息的内容
		$videoArr = array(
			'MediaId' => $obj->MediaId
			);
		//回复视频消息
		return replyVideo($obj,$videoArr);
	}

	//接受链接消息
	function receiveLink($obj){
		//接受链接的消息内容
		$linkArr = array(
			'Title' => $obj->Title,
			'Description' => $obj->Description,
			'Url' => $obj->Url
			);
		//回复链接消息
		return replyText($obj,"链接地址是:{$linkArr['Title']}");
	}

	//发送文本消息
	function replyText($obj,$content){
		$replyXml = "<xml>
						<ToUserName><![CDATA[%s]]></ToUserName>
						<FromUserName><![CDATA[%s]]></FromUserName>
						<CreateTime>%s</CreateTime>
						<MsgType><![CDATA[text]]></MsgType>
						<Content><![CDATA[%s]]></Content>
					</xml>";
		$resultStr = sprintf($replyXml,$obj->FromUserName,$obj->ToUserName,time(),$content);
		return $resultStr;
	}

	//发送图片信息
	function replyImage($obj,$imageArr){
		$replyXml = "<xml>
						<ToUserName><![CDATA[%s]]></ToUserName>
						<FromUserName><![CDATA[%s]]></FromUserName>
						<CreateTime>%s</CreateTime>
						<MsgType><![CDATA[image]]></MsgType>
						<Image>
						<MediaId><![CDATA[%s]]></MediaId>
						</Image>
					</xml>";
		$resultStr = sprintf($replyXml,$obj->FromUserName,$obj->ToUserName,time(),$imageArr['MediaId']);
		return $resultStr;
	}

	//回复语音消息
	function replyVoice($obj,$voiceArr){
		$replyXml = "<xml>
						<ToUserName><![CDATA[%s]]></ToUserName>
						<FromUserName><![CDATA[%s]]></FromUserName>
						<CreateTime>%s</CreateTime>
						<MsgType><![CDATA[voice]]></MsgType>
						<Voice>
						<MediaId><![CDATA[%s]]></MediaId>
						</Voice>
					</xml>";	
		$resultStr = sprintf($replyXml,$obj->FromUserName,$obj->ToUserName,time(),$voiceArr['MediaId']);
		return $resultStr;
	}

	function replyVideo($obj,$videoArr){
		$replyXml = "<xml>
						<ToUserName><![CDATA[%s]]></ToUserName>
						<FromUserName><![CDATA[%s]]></FromUserName>
						<CreateTime>%s</CreateTime>
						<MsgType><![CDATA[video]]></MsgType>
						<Video>
							<MediaId><![CDATA[%s]]></MediaId>
						</Video> 
					</xml>";
		$resultStr = sprintf($replyXml,$obj->FromUserName,$obj->ToUserName,time(),$videoArr['MediaId']);
		return $resultStr;
	}

	//回复音乐消息   (无接受 只有回复)
	function replyMusic($obj,$musicArr){
		$replyXml = "<xml>
						<ToUserName><![CDATA[%s]]></ToUserName>
						<FromUserName><![CDATA[%s]]></FromUserName>
						<CreateTime>%s</CreateTime>
						<MsgType><![CDATA[music]]></MsgType>
						<Music>
						<Title><![CDATA[%s]]></Title>
						<Description><![CDATA[%s]]></Description>
						<MusicUrl><![CDATA[%s]]></MusicUrl>
						<HQMusicUrl><![CDATA[%s]]></HQMusicUrl>
						<ThumbMediaId><![CDATA[%s]]></ThumbMediaId>
						</Music>
					</xml>";
		$resultStr = sprintf($replyXml,$obj->FromUserName,$obj->ToUserName,time(),$musicArr['Title'],$musicArr['Description'],$musicArr['MusicUrl'],$musicArr['HQMusicUrl'],$musicArr['MediaId']);
		return $resultStr;
	}

	//回复图文消息 (未完成)
	function replyNews($obj,$newsArr){
		$itemStr = "";
		if(is_array($newsArr)){
			foreach ($newsArr as $item) {
					$itemXml = "<item>
						<Title><![CDATA[%s]]></Title>
						<Description><![CDATA[%s]]></Description>
						<PicUrl><![CDATA[%s]]></PicUrl>
						<Url><![CDATA[%s]]></Url>
						</item>";
					$itemStr .= sprintf($itemXml,$item['Title'],$item['Description'],$item['PicUrl'],$item['Url']);
		
			}
		}
	
		$replyXml = "<xml>
						<ToUserName><![CDATA[%s]]></ToUserName>
						<FromUserName><![CDATA[%s]]></FromUserName>
						<CreateTime>%s</CreateTime>
						<MsgType><![CDATA[news]]></MsgType>
						<ArticleCount>%s</ArticleCount>
						<Articles>
							{$itemStr}
						</Articles>
					</xml> ";
		$resultStr = sprintf($replyXml,$obj->FromUserName,$obj->ToUserName,time(),count($newsArr));
		return $resultStr;
	}