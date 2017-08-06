<?php
include('library/Requests.php');
ini_set("memory_limit","-1");
error_reporting(0);
$hs = [
    array(
        'Origin' => 'https://live.bilibili.com',
        'Accept-Encoding' => 'gzip, deflate, br',
        'Accept-Language' => 'zh-CN,zh;q=0.8,en-US;q=0.6,en;q=0.4',
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/59.0.3071.115 Safari/537.36',
        'Content-Type' => 'application/x-www-form-urlencoded',
        'Accept' => 'application/json, text/plain, */*',
        'Referer' => 'https://live.bilibili.com/pages/1702/pixel-drawing',
        'Connection' => 'keep-alive',
        'Cookie' => 【ENTER YOUR COOKIE HERE】
    ), array(
        'Origin' => 'https://live.bilibili.com',
        'Accept-Encoding' => 'gzip, deflate, br',
        'Accept-Language' => 'zh-CN,zh;q=0.8,en-US;q=0.6,en;q=0.4',
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/59.0.3071.115 Safari/537.36',
        'Content-Type' => 'application/x-www-form-urlencoded',
        'Accept' => 'application/json, text/plain, */*',
        'Referer' => 'https://live.bilibili.com/pages/1702/pixel-drawing',
        'Connection' => 'keep-alive',
        'Cookie' => 【ENTER YOUR COOKIE HERE】
    ), array(
        'Origin' => 'https://live.bilibili.com',
        'Accept-Encoding' => 'gzip, deflate, br',
        'Accept-Language' => 'zh-CN,zh;q=0.8,en-US;q=0.6,en;q=0.4',
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/59.0.3071.115 Safari/537.36',
        'Content-Type' => 'application/x-www-form-urlencoded',
        'Accept' => 'application/json, text/plain, */*',
        'Referer' => 'https://live.bilibili.com/pages/1702/pixel-drawing',
        'Connection' => 'keep-alive',
        'Cookie' => 【ENTER YOUR COOKIE HERE】
    )];
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
function draw($x,$y,$color){
    Requests::register_autoloader();
    global $hs;
    $url = 'https://api.live.bilibili.com/activity/v1/SummerDraw/draw';
    $data = array(
        'x_min' => $x,
        'y_min' => $y,
        'x_max' => $x,
        'y_max' => $y,
        'color' => $color
    );
    $header_count = count($hs);
    for ($i = 0;$i<$header_count;$i++){
        $repo = Requests::post($url, $hs[$i], $data);
        if (json_decode($repo->body,true)['code']==0)
            return true;
    }
    return (json_decode($repo->body,true)['code']==0)?true:false;
}
function get_pixel_rgb($img, $x, $y) {
    $im = imagecreatefrompng($img);
    $rgb = imagecolorat($im, $x, $y);
    $r = ($rgb >> 16) & 0xFF;
    $g = ($rgb >> 8) & 0xFF;
    $b = $rgb & 0xFF;
    $pixel = $r . $g . $b;
    switch ($pixel){
        case '000':
            return '0';
        case '255255255':
            return 1;
        case '252222107':
            return 2;
        case '255246209':
            return 3;
        case '125149145':
            return 4;
        case '113190214':
            return 5;
        case '59229219':
            return 6;
        case '254211199':
            return 7;
        case '1846339':
            return 8;
        case '250172142':
            return 9;
        case '070112':
            return 'A';
        case '5113151':
            return 'B';
        case '6820195':
            return 'C';
        case '11984255':
            return 'D';
        case '25500':
            return 'E';
        case '2551520':
            return 'F';
        case '151253220':
            return 'G';
        case '248203140':
            return 'H';
        case '46143175':
            return 'I';
        default:
            return 'X';
    }
}
function exec_draw($p){
    $img = $p[0];
    $info = getimagesize($img);
    $paint_raw = json_decode(file_get_contents("https://api.live.bilibili.com/activity/v1/SummerDraw/bitmap"), true)['data']['bitmap'];
    $paint_array = mbStrSplit($paint_raw, 1280);
    $th_logo = array_slice($paint_array, $p[2], $info[1]);
    foreach ($th_logo as $i => $v) {
        $th_logo[$i] = mb_substr($v, $p[1], $info[0], "utf8");
    }

    $img_str = '';
    for ($x = 0; $x < $info[1]; $x++) {
        for ($y = 0; $y < $info[0]; $y++) {
            $v = get_pixel_rgb($img, $y, $x);
            $img_str .= $v;
        }
    }
    $img_arr = mbStrSplit($img_str, $info[0]);
    //print_r($info) ;

/*    print_r($img_arr);
    echo "\n";
    print_r($th_logo);*/

    for ($i = 0; $i < count($img_arr); $i++) {
        for ($j = 0; $j < mb_strlen($img_arr[$i]); $j++) {
            if ($img_arr[$i][$j] == 'X')
                continue;
            elseif ($img_arr[$i][$j] != $th_logo[$i][$j]) {
                $c = $img_arr[$i][$j];
                if(draw($j + $p[1], $i + $p[2], $c))
                    echo "x=$j, y=$i, color=$c, img=$img\n";
                else die('Please wait for 3 minutes.');
            }
        }
    }
    return;
}


$pic_arr = [
    ['ym.png', 60, 51],
    ['mrs.png', 1193, 0],
    ['flan.png', 100, 61],
    ['z.png', 139, 58],
    ['shalc.png', 814, 470],
    ['skk.png', 0, 264],
    ['gsk.png', 1229, 9],
    ['reimu.png', 502, 636],
    ['sakata.png', 1253, 94],
    ['9.png', 1115, 386],
    ['ykr.png', 217, 69],
    ['rmy.png', 1184, 386],
    ['sgm.png', 378, 403],
    ['sne.png', 465, 567],
    ['aya.png', 500, 579],
    ['ts.png', 876, 423],
    ['rm.png', 813, 429],
    ['sj.png', 362, 441],
    ['mnrk.png', 390, 312],
    ['r.png', 866, 649],
    ['9d.png', 271, 618],
    ['uuz.png', 451, 485],
    ['yy.png', 876, 1],
];

$pic_count = count($pic_arr);
for($i=0;$i<$pic_count;$i++){
    exec_draw($pic_arr[$i]);
}
