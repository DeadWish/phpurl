<?php
	
	$argv_level = $argv[1];
	if(!$argv_level)
		$argv_level = 0;
	//连接Mysql
	$db_host = "localhost";
	$db_user = "root";
	$db_password = "1028";
	$db_name = "url_caoyi";
	$conn = new mysqli($db_host,$db_user,$db_password,$db_name);
	if ($conn) {
		echo "Connect success!'\n'";
	} else {
		die("Error:Connect failed!");
	}
	//get redis obj;
	$redis = new Redis();
	$redis->connect('127.0.0.1',6379);
	$redis->FLUSHALL();
	$redis->set("maxScriptCount",10);
	$redis->set("urlScriptCount",$redis->get("maxScriptCount"));
	//获取需要检查的key
	$query = "SELECT url FROM url_post";
	$result = $conn->query($query);
	if ($result) {
		if ($result->num_rows > 0) {
			while ($row = $result->fetch_array()) {
				while ($redis->get("urlScriptCount") <= 0) {
					sleep(0.1);
				}
				exec("screen -d -m php hi.php $row[0] $argv_level");
			}
		}
	}
?>
