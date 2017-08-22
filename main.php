<?php
ini_set("memory_limit","-1"); //取消内存限制(渣代码占内存太多)
error_reporting(E_ALL); //参数为0则不输出报错信息, 输出全部报错信息请将参数改为 E_ALL

include('library/Requests.php');
include("library/ActiveRecord.php"); //包含库

$dbms='mysql';     //数据库类型
$host='127.0.0.1:3306'; //数据库主机名
$dbName='draw';    //使用的数据库
$user='bbleae';      //数据库连接用户名
$pass='003800';          //对应的密码
$dsn="$dbms:host=$host;dbname=$dbName";
ActiveRecord::setDb(new PDO($dsn, $user, $pass, array(PDO::ATTR_PERSISTENT => true)));

class user extends ActiveRecord {
	public $table = 'user';
	public $primaryKey = 'id';
}

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

//$mark = []; //标记该cookie在本次执行中是否被使用过

function draw($x,$y,$color){
    Requests::register_autoloader();
    global $tpl;
    $url = 'https://api.live.bilibili.com/activity/v1/SummerDraw/draw';
    $data = array('x_min' => $x,'y_min' => $y,'x_max' => $x,'y_max' => $y,'color' => $color);
	$co_r = new user();
	$co_r = $co_r->select('*')->lt('lastdraw',time()-180)->find();
	//var_dump($co_r);
	if($co_r){
		$tpl['Cookie'] = $co_r->cookie;
		$repo = Requests::post($url, $tpl, $data);
		$co_r->lastdraw = time();
		$co_r->update();
		if (json_decode($repo->body)->code == 0) {
			echo "\n".$co_r->id.": ".$repo->body.": $x, $y, $color";
			unset($co_r);
			return 0;
		}else{
			echo "\n".$co_r->id.": Error in one pixel: $x, $y, $color";
			unset($co_r);
			return 1;
		}
	}else{
		unset($co_r);
		return 2;
	}
}

/*将一副图画到绘板上
$p为一个hash map 键x,y,w,h对应的值均为整数 描述图像的坐标和高宽 键bitmap对应的值为一个字符串,描述了图像的内容 包含字符([0-9A-V]|Z), Z表示对该像素不做检测
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
        if ($p['bitmap'][$i] == 'Z')
            continue;
        elseif ($paint_str[$i]!=$p['bitmap'][$i]) {
            $c = $p['bitmap'][$i];
			switch(draw($i%1280, floor($i/1280),$c)){
				case 2:
					die("\nSome pixel(s) left and colding down...");
				case 1:
				case 0:
					break;
				default:
					echo "\nUncaugt error!";
			}
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
exec_draw(get_img_from_URL('http://www.3Shain.me/data/all.json'));
