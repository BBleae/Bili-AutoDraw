<?php
include('library/Requests.php'); //包含库
ini_set("memory_limit","-1"); //取消内存限制(渣代码占内存太多)
error_reporting(E_ALL); //参数为0则不输出报错信息, 输出全部报错信息请将参数改为 E_ALL

/*获取Cookies*/
$hs = json_decode(file_get_contents("cookies.json"),true)['cookies'];

/*http请求Header模板*/
$tpl = array(
    'Origin' => 'https://live.bilibili.com',
    'Accept-Encoding' => 'gzip, deflate, br',
    'Accept-Language' => 'zh-CN,zh;q=0.8,en-US;q=0.6,en;q=0.4',
    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/59.0.3071.115 Safari/537.36',
    'Content-Type' => 'application/x-www-form-urlencoded',
    'Accept' => 'application/json, text/plain, */*',
    'Referer' => 'https://live.bilibili.com/pages/1702/pixel-drawing',
    'Connection' => 'keep-alive',
);

$mark = []; //标记该cookie在本次执行中是否被使用过

//$hs = ['cookie 1','cookie 2'......]; //$hs数组每一个元素存放一个账号的Cookie头

/*将字符串分割成数组 by Komeiji Satori
function mbStrSplit($string, $len = 1) {
    $start = 0;
    $strlen = mb_strlen($string);
    while ($strlen) {
        $array[] = mb_substr($string, $start, $len, "utf8");
        $string = mb_substr($string, $len, $strlen, "utf8");
        $strlen = mb_strlen($string);
    }
    return $array;
}
*/

/*在绘板指定的坐标上色 x,y为整数 color为[0-9A-V]的一个字符*/
function draw($x,$y,$color){
    Requests::register_autoloader();
    global $hs;
    global $tpl;
    $url = 'https://api.live.bilibili.com/activity/v1/SummerDraw/draw';
    $data = array(
        'x_min' => $x,
        'y_min' => $y,
        'x_max' => $x,
        'y_max' => $y,
        'color' => $color
    );
    $header_count = count($hs);
    for ($i = 0;$i<$header_count;$i++) {
        if (@$GLOBALS['mark'][$i] == 1)
            continue;
        else {
            $tpl['Cookie'] = $hs[$i];
            /*
			$tmp_bitmap = json_decode(file_get_contents("https://api.live.bilibili.com/activity/v1/SummerDraw/bitmap"), true)['data']['bitmap'];
			if ($tmp_bitmap[$y*1280+$x]==$color){
				unset($tmp_bitmap);
				return true;
			}
            */
            $repo = Requests::post($url, $tpl, $data);
            if (json_decode($repo->body, true)['code'] == 0) {
                echo "\n[$i]: ".$repo->body.": $x, $y, $color";
                $GLOBALS['mark'][$i] = 1;
                unset($GLOBALS[$i]);
                return true;
            }
        }
    }
    return false;
}

/*将一副图画到绘板上
$p为一个hash map 键x,y,w,h对应的值均为整形 描述图像的坐标和高宽 键bitmap对应的值为一个字符串,描述了图像的内容 包含字符([0-9A-V]|Z), Z表示对该像素不做检测
TODO: 目前只支持w=1280 h=720的图像
bitmap示例:
若bitmap为114514191981089364, w为6,h为3 则该图像为:
114514
191981
089364
*/
function exec_draw($p)
{
    /*获取当前绘板图像*/
    $paint_str = json_decode(file_get_contents("https://api.live.bilibili.com/activity/v1/SummerDraw/bitmap"), true)['data']['bitmap'];

    for ($i=0;$i<921600;$i++){
        if ($p['bitmap'][$i] == 'Z'/*||$p['bitmap'][$i] == '1'*//*忽略白色*/)
            continue;
        elseif ($p['bitmap'][$i] != $paint_str[$i]) {
            $c = $p['bitmap'][$i];
            if (!draw($i%1280, floor($i/1280), $c))
                die("\nSome pixel(s) left and colding down...");
        }
    }
    die("\n All clear.");
}

//从url获取图像的hash map 请求需要返回JSON格式
function get_img_from_URL($url){
    $dataStr = file_get_contents($url);

    //削除UTF8-BOM
    if (strpos($dataStr, "\xEF\xBB\xBF") === 0)
        $dataStr = substr($dataStr, 3);  
    return json_decode($dataStr,true);
/*
    //初始化curl对象
　　$ch = curl_init();
　　//设置选项，包括URL
　　curl_setopt($ch, CURLOPT_URL, $url);
　　curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
　　curl_setopt($ch, CURLOPT_HEADER, 0);
　　//发送请求并解析获取的JSON
　　$output = json_decode(curl_exec($ch),true);
　　//释放curl句柄
　　curl_close($ch);
　　//返回数据
　　return $output;
*/
}

//从3Shain.me获取东方势力的像素画图纸并绘制
exec_draw(get_img_from_URL('http://www.3shain.me/data/all2.json'));
