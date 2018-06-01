# Phpurl
此脚本是用来学习php和爬虫基本原理的，其中使用到了redis，mysql以及php的库simplehtmldom.

## 准备
* mysqli
* redis
* Mysql

## Mysql
___
**database**:url_caoyi

Tables_in_url_caoyi|
-------------------|
keytable           |
url_post           |
url_useful         |

keytable|
--------|
keychar |

url_post  |
----------|
url       |

url_useful|
----------|
url       |

----

## 使用
1.把需要爬的网址存入url_post中

	insert into url_post values("...");

2.把需要监控的关键字存入ketable中

	insert into keytable values("...");

3.运行hiscript.php

	php hiscript.php argv1
	//argv 是爬虫深度参数，不输入时为0，即不会进行爬虫处理

##结果
结果将实时地储存在redis的urList中，脚本最后也会写入脚本路径下的usefulUrl.txt文件中


##说明
hiscript.php中会运行screen -d -m 来运行hi.php，如果因为时间太长，只能使用kill来处理相关进程。



注：原版在git.oschina.net上
