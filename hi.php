<?php
include 'simple_html_dom.php';
include 'sunday.php';
//get argv level
$argv_url = $argv[1];
$argv_level = $argv[2];
$argv_keytimes = $argv[3];
//echo "get argv : $argv_url\n";
//echo "get argv_level: $argv_level\n";
if(!$argv_level)
	$argv_level = 0;
if(!$argv_keytimes)
	$argv_keytimes = 1;
//记录redis中的url数量
$keyValueCount = 0;

//定义判断url是否重复的函数，使用redis
function is_new_url(&$redis, $url)
{
//	echo "chuli::!!$url\n";
	$okValue = $redis->get($url);
	if($okValue == '1'){	
		return false;
	}
	else{
		$redis->set($url,'1');
		return true;
	}
}

//定义解析url函数
function parse_myurl($url, $level,&$keyArray, &$redis){
	Global $keyValueCount;
	Global $argv_keytimes;
	//echo $keyValueCount;
	if($level<0){
		return 1;
	}
	if (is_new_url($redis,$url)) {
		++$keyValueCount;
	}
	else {
		return 1;
	}
	//解析html并获取html内容
	//$html = file_get_html($url,$contents);
	$use_include_path = false;
	$context=null;
	$offset = -1;
	$maxLen=-1;
	$lowercase = true;
	$forceTagsClosed=true;
	$target_charset = DEFAULT_TARGET_CHARSET;
	$stripRN=true;
	$defaultBRText=DEFAULT_BR_TEXT;
	$defaultSpanText=DEFAULT_SPAN_TEXT;
//	echo "start shdphp\n";
	$html = new simple_html_dom(null, $lowercase, $forceTagsClosed, $target_charset, $stripRN, $defaultBRText, $defaultSpanText);
	//$contents = file_get_contents($url, $use_include_path, $context, $offset);
	//curl GET html
    $ch = curl_init();//设置选项，包括URL
   // curl_setopt($ch, CURLOPT_URL, "http://ugc.qpic.cn/baikepic/16871/cut-20140124154747-95061476.jpg/0");
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION,true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 2.0.50727;');
    curl_setopt($ch, CURLOPT_TIMEOUT, 50);
    //执行并获取HTML文档内容
    $contents = curl_exec($ch);
    $info = curl_getinfo($ch);
    $contenttype = $info["content_type"];
    //echo $contenttype."\n";
   // foreach($info as $key=>$value)
    //    echo $key."=>".$value."\n";
    $regex = '/([.]*text[.]*)|([.]*javascript[.]*)/';
    $count = preg_match($regex, $contenttype, $matches);
//    echo "regex count:".$count;
    if ($count > 0) {
    	curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_NOBODY, false);
    }
    else {
        echo "Content-Type is wrong!\n";
	return 1;
    }
    $contents = curl_exec($ch);
    curl_close($ch);
//    echo "内容的长度：".strlen($contents)."\n";
    if (empty($contents) || strlen($contents) > MAX_FILE_SIZE)
	{
		return false;
	}
	$html->load($contents, $lowercase, $stripRN);

	//匹配key
	$mark = true;
	foreach ($keyArray as $patt) {
		if(!sunday($patt,$contents,$argv_keytimes)){
			$mark = false;
			//break;
		}
	}
	if ($mark) {
		$redis->rpush ("urlList", $url."----".$level);
//		echo $url."----".$level."'\n'";  //完全匹配才输出url
	}
	else{
		//echo $url."----".$level."----找不到关键词"."'\n'";  //完全匹配才输出url
//		echo ".";
	} 

	//递归解析子链接
	$level_low = $level - 1;
	if($html){
		$allElement = $html->find('a');
		foreach($allElement as $element){
			$newUrl = $element->href;
			//echo "get a url:".$newUrl;
			
				if (strncasecmp("http",$newUrl,4) == 0) {
					parse_myurl($newUrl,$level_low,$keyArray,$redis);
				}
				elseif (strncasecmp("//",$newUrl,2) == 0) {
					$newUrl = "http:".$newUrl;
					parse_myurl($newUrl,$level_low,$keyArray,$redis);
				}
				elseif (strncasecmp("/",$newUrl,1) == 0) {
					$newUrl = $url.$newUrl;
					parse_myurl($newUrl,$level_low,$keyArray,$redis);
				}

		}
		$html->clear();
	}
}
//链接redis
$redis = new Redis();
$redis->connect('127.0.0.1',6379);
$redis->decr("urlScriptCount");
//连接数据库
$db_host = "localhost";
$db_user = "root";
$db_password = "1028";
$db_name = "url_caoyi";
$conn = new mysqli($db_host,$db_user,$db_password,$db_name);
if ($conn) {
//	echo "Connect success!'\n'";
} else {
	die("Error:Connect failed!");
}

//获取需要检查的key
$query = "SELECT keychar FROM keytable";
$result = $conn->query($query);
$keyArray = array();
if ($result) {
	if ($result->num_rows > 0) {
		$row = null;
		$i = 0;
		while ($row = $result->fetch_array()) {
			$keyArray[$i] = $row[0];
			echo "关键词有：".$keyArray[$i]."'\n'";
			++$i;
		}
	} else {
		die ("Error:no keychar in TABLE keytable!");
	}
} else {
	die ("Error:query is wrong!");
}
$result->free();

//获取需要解析的url
// $query = "SELECT url FROM url_post";
// $result = $conn->query($query);
// if ($result) {
// 	if ($result->num_rows > 0) {
// 		$row = null;
// 		while ($row = $result->fetch_array()) {
// 			if (strncasecmp("http",$row[0],4) == 0) {
// 				parse_myurl($row[0],$argv_level,$keyArray,$redis);
// 			}
// 		}
// 	} else {
// 		die ("Error:no url in TABLE url_post!");
// 	}
// } else {
// 	die ("Error:query is wrong!");
// }
if (strncasecmp("http",$argv_url,4) == 0) {
 	parse_myurl($argv_url, $argv_level, $keyArray, $redis);
}
$redis->incr("urlScriptCount");
// $result->free();
$maxC = $redis->get("maxScriptCount");
$curC = $redis->get("urlScriptCount");
if ($maxC == $curC) {
	$usefulUrlList = $redis->lrange("urlList",0,-1);
	$myfile = fopen("usefulUrl.txt", 'w');
	foreach ($usefulUrlList as $value) {
		fwrite($myfile, "$value\n");
	}
}
//关闭数据库链接
$conn->close();
?>

