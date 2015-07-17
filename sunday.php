<?php
    #字符串匹配，sunday算法
    
    function sunday($patt, $text, $times) {
        $patt_size = strlen($patt);
        $text_size = strlen($text);

        #初始化字符串位移映射关系
        #此处注意,映射关系表的建立一定是从左到右，因为patten可能存在相同的字符
        #对于重复字符的位移长度，我们只能让最后一个重复字符的位移长度覆盖前面的位移长度
        #例如pattern = "testing",注意到此处有2个t，那么建立出来的位移映射是 shift[] = Array ( [t] => 4 [e] => 6 [s] => 5 [i] => 3 [n] => 2 [g] => 1 )
        #而如果不是从左到右，是从右到左的建立映射，就会变成 shift[] = Array ( [t] => 7 [e] => 6 [s] => 5 [i] => 3 [n] => 2 [g] => 1 )，这样到时候匹配就无法得到正确结果
        for ($i = 0; $i < $patt_size; $i++) {
            $shift[$patt[$i]] = $patt_size - $i;
        }

        $i = 0;
        $limit = $text_size - $patt_size; #需要开始匹配的最后一个字符坐标
        $match_count = 0;
	while ($i <= $limit) {
            $match_size = 0; #当前已匹配到的字符个数
            #从i开始匹配字符串
            while ($text[$i + $match_size] == $patt[$match_size]) {
                $match_size++;
                if ($match_size == $patt_size) {
                    ++$match_count;
		    if($match_count == $times)
			return true;
	            break;
                }
            }

            $shift_index = $i + $patt_size; #在text中比pattern的多一位的字符坐标
            if ($shift_index < $text_size && isset($shift[$text[$shift_index]])) {
                $i += $shift[$text[$shift_index]];
            } else {
                #如果映射表中没有这个字符的位移量，直接向后移动patt_size个单位
                $i += $patt_size;
            }
        }
        return false;
    }
?>
