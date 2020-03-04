<?php
/**
 * 农历公历互转,八字排盘,星座,日历,支持从-1000到3000年间的排盘,二十四节气
 *
 * 我们现在所使用的以西元年月日表示的格里高利历(Gregorian calendar)
 * 儒略历(Julian calendar)，于公元前45年1月1日起执行的取代旧罗马历法的一种历法,以西元前4713年(或-4712年)1月1日12时为起点
 * liujiawm@163.com
 *
 * 该版要求php7
 *
 * 项目原作者 szargv@wo.cn
 * 此日历转换类完全源于以下项目,感谢这两个项目作者的无私分享:
 * https://github.com/nozomi199/qimen_star (八字排盘,JS源码)
 * http://www.bieyu.com/ (详尽的历法转换原理,JS源码)
 */
declare (strict_types = 1);

namespace paipan;

class Paipan
{
    /**
     * 是否区分 早晚子 时,true则23:00-24:00算成上一天
     * @var bool
     */
    public $zwz = false;

    /**
     * 中文数字
     */
    public $chinese_number = ['日','一','二','三','四','五','六','七','八','九','十'];

    /**
     * 农历月份常用称呼
     * @var array
     */
    public $chinese_month = ['正','二','三','四','五','六','七','八','九','十','冬','腊'];

    /**
     * 农历日期常用称呼
     * @var array
     */
    public $chinese_day = ['初','十','廿','卅'];

    /**
     * 十天干
     * @var array
     */
    public $ctg = ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸']; // char of TianGan
    /**
     * 五行
     */
    public $cwx = ['金', '木', '水', '火', '土']; // char of WuXing
    /**
     * 天干对应五行
     * @var array
     */
    public $tgwx = [1, 1, 3, 3, 4, 4, 0, 0, 2, 2];
    /**
     * 十二地支
     * @var array
     */
    public $cdz = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥']; //char of DiZhi
    /**
     * 地支对应五行
     * @var array
     */
    public $dzwx = [2, 4, 1, 1, 4, 3, 3, 4, 0, 0, 4, 2];
    /**
     * 地支藏干
     * @var array
     */
    public $dzcg = [[9], [5,9,7], [0,2,4], [1], [4,1,9], [2,4,6], [3,5], [5,3,1], [6,8,4], [7], [4,7,3], [8,0]];
    /**
     * 十二生肖
     * @var array
     */
    public $csa = ['鼠', '牛', '虎', '兔', '龙', '蛇', '马', '羊', '猴', '鸡', '狗', '猪']; // char of symbolic animals
    /**
     * 十二星座
     * @var array
     */
    public $cxz = ['水瓶座', '双鱼座', '白羊座', '金牛座', '双子座', '巨蟹座',
        '狮子座', '处女座', '天秤座', '天蝎座', '射手座', '摩羯座']; // char of XingZuo
    /**
     * 星期
     * @var array
     */
    public $wkd = ['日', '一', '二', '三', '四', '五', '六']; // week day
    /**
     * 廿四节气(从春分开始)
     * @var array
     */
    public $jq = ['春分', '清明', '谷雨', '立夏', '小满', '芒种', '夏至', '小暑', '大暑', '立秋', '处暑', '白露', '秋分',
        '寒露', '霜降', '立冬', '小雪', '大雪', '冬至', '小寒', '大寒', '立春', '雨水', '惊蛰']; //JieQi
    /**
     * 均值朔望月长(mean length of synodic month)
     * @var float
     */
    private $synmonth = 29.530588853;
    /**
     * 因子
     * @var array
     */
    private $ptsa = [485, 203, 199, 182, 156, 136, 77, 74, 70, 58, 52, 50, 45, 44, 29, 18, 17, 16, 14, 12, 12, 12, 9, 8];

    private $ptsb = [324.96, 337.23, 342.08, 27.85, 73.14, 171.52, 222.54, 296.72, 243.58, 119.81, 297.17, 21.02, 247.54,
        325.15,60.93, 155.12, 288.79, 198.04, 199.76, 95.39, 287.11, 320.81, 227.73, 15.45];

    private $ptsc = [1934.136, 32964.467, 20.186, 445267.112, 45036.886, 22518.443, 65928.934, 3034.906, 9037.513,
        33718.147, 150.678, 2281.226, 29929.562, 31555.956, 4443.417, 67555.328, 4562.452, 62894.029, 31436.921,
        14577.848, 31931.756, 34777.259, 1222.114, 16859.074];


    /**
     * 计算指定年(公历)的春分点(vernal equinox),
     * 但因地球在绕日运行时会因受到其他星球之影响而产生摄动(perturbation),必须将此现象产生的偏移量加入.
     * @param int $yy
     * @return boolean|number 返回儒略日历格林威治时间
     */
    private function VE(int $yy) {
        if($yy < -8000){
            return false;
        }
        if($yy > 8001){
            return false;
        }
        if ($yy >= 1000 && $yy <= 8001) {
            $m = ($yy - 2000) / 1000;
            return 2451623.80984 + 365242.37404 * $m + 0.05169 * $m * $m - 0.00411 * $m * $m * $m - 0.00057 * $m * $m * $m * $m;
        }
        if ($yy >= -8000 && $yy < 1000) {
            $m = $yy / 1000;
            return 1721139.29189 + 365242.1374 * $m + 0.06134 * $m * $m + 0.00111 * $m * $m * $m - 0.00071 * $m * $m * $m * $m;
        }

        return false;
    }

    /**
     * 地球在绕日运行时会因受到其他星球之影响而产生摄动(perturbation)
     * @param float $jd
     * @return number 返回某时刻(儒略日历)的摄动偏移量
     */
    private function Perturbation($jd) {
        $t = ($jd - 2451545) / 36525;
        $s = 0;
        for ($k = 0; $k <= 23; $k++) {
            $s = $s + $this->ptsa[$k] * cos($this->ptsb[$k] * 2 * pi() / 360 + $this->ptsc[$k] * 2 * pi() / 360 * $t);
        }
        $w = 35999.373 * $t - 2.47;
        $l = 1 + 0.0334 * cos($w * 2 * pi() / 360) + 0.0007 * cos(2 * $w * 2 * pi() / 360);
        return 0.00001 * $s / $l;
    }

    /**
     * 求∆t
     *
     * @param int $yy 年份
     * @param int $mm 月份
     * @return number
     *
     * (充分验证我了我对学计算机语言的观点：“学好if和for，写遍天下程序”，这个方法我看着都头疼)
     */
    private function DeltaT(int $yy, int $mm) {

        $y = $yy + ($mm - 0.5) / 12;

        if ($y <= -500) {
            $u = ($y - 1820) / 100;
            $dt = (-20 + 32 * $u * $u);
        } else {
            if ($y < 500) {
                $u = $y / 100;
                $dt = (10583.6 - 1014.41 * $u + 33.78311 * $u * $u - 5.952053 * $u * $u * $u - 0.1798452 * $u * $u * $u * $u + 0.022174192 * $u * $u * $u * $u * $u + 0.0090316521 * $u * $u * $u * $u * $u * $u);
            } else {
                if ($y < 1600) {
                    $u = ($y - 1000) / 100;
                    $dt = (1574.2 - 556.01 * $u + 71.23472 * $u * $u + 0.319781 * $u * $u * $u - 0.8503463 * $u * $u * $u * $u - 0.005050998 * $u * $u * $u * $u * $u + 0.0083572073 * $u * $u * $u * $u * $u * $u);
                } else {
                    if ($y < 1700) {
                        $t = $y - 1600;
                        $dt = (120 - 0.9808 * $t - 0.01532 * $t * $t + $t * $t * $t / 7129);
                    } else {
                        if ($y < 1800) {
                            $t = $y - 1700;
                            $dt = (8.83 + 0.1603 * $t - 0.0059285 * $t * $t + 0.00013336 * $t * $t * $t - $t * $t * $t * $t / 1174000);
                        } else {
                            if ($y < 1860) {
                                $t = $y - 1800;
                                $dt = (13.72 - 0.332447 * $t + 0.0068612 * $t * $t + 0.0041116 * $t * $t * $t - 0.00037436 * $t * $t * $t * $t + 0.0000121272 * $t * $t * $t * $t * $t - 0.0000001699 * $t * $t * $t * $t * $t * $t + 0.000000000875 * $t * $t * $t * $t * $t * $t * $t);
                            } else {
                                if ($y < 1900) {
                                    $t = $y - 1860;
                                    $dt = (7.62 + 0.5737 * $t - 0.251754 * $t * $t + 0.01680668 * $t * $t * $t - 0.0004473624 * $t * $t * $t * $t + $t * $t * $t * $t * $t / 233174);
                                } else {
                                    if ($y < 1920) {
                                        $t = $y - 1900;
                                        $dt = (-2.79 + 1.494119 * $t - 0.0598939 * $t * $t + 0.0061966 * $t * $t * $t - 0.000197 * $t * $t * $t * $t);
                                    } else {
                                        if ($y < 1941) {
                                            $t = $y - 1920;
                                            $dt = (21.2 + 0.84493 * $t - 0.0761 * $t * $t + 0.0020936 * $t * $t * $t);
                                        } else {
                                            if ($y < 1961) {
                                                $t = $y - 1950;
                                                $dt = (29.07 + 0.407 * $t - $t * $t / 233 + $t * $t * $t / 2547);
                                            } else {
                                                if ($y < 1986) {
                                                    $t = $y - 1975;
                                                    $dt = (45.45 + 1.067 * $t - $t * $t / 260 - $t * $t * $t / 718);
                                                } else {
                                                    if ($y < 2005) {
                                                        $t = $y - 2000;
                                                        $dt = (63.86 + 0.3345 * $t - 0.060374 * $t * $t + 0.0017275 * $t * $t * $t + 0.000651814 * $t * $t * $t * $t + 0.00002373599 * $t * $t * $t * $t * $t);
                                                    } else {
                                                        if ($y < 2050) {
                                                            $t = $y - 2000;
                                                            $dt = (62.92 + 0.32217 * $t + 0.005589 * $t * $t);
                                                        } else {
                                                            if ($y < 2150) {
                                                                $u = ($y - 1820) / 100;
                                                                $dt = (-20 + 32 * $u * $u - 0.5628 * (2150 - $y));
                                                            } else {
                                                                $u = ($y - 1820) / 100;
                                                                $dt = (-20 + 32 * $u * $u);
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        if ($y < 1955 || $y >= 2005){
            $dt = $dt - (0.000012932 * ($y - 1955) * ($y - 1955));
        }
        return $dt / 60; // 将秒转换为分
    }

    /**
     * 获取指定年的春分开始的24节气,另外多取2个确保覆盖完一个公历年
     * 大致原理是:先用此方法得到理论值,再用摄动值(Perturbation)和固定参数DeltaT做调整
     * @param int $yy
     * @return array
     */
    private function MeanJQJD(int $yy):array {
        if(! $jd = $this->VE($yy)){ // 该年的春分點
            return [];
        }
        $ty = $this->VE($yy + 1) - $jd; // 该年的回归年长

        $num = 24 + 2; //另外多取2个确保覆盖完一个公历年

        $ath = 2 * pi() / 24;
        $tx = ($jd - 2451545) / 365250;
        $e = 0.0167086342 - 0.0004203654 * $tx - 0.0000126734 * $tx * $tx + 0.0000001444 * $tx * $tx * $tx - 0.0000000002 * $tx * $tx * $tx * $tx + 0.0000000003 * $tx * $tx * $tx * $tx * $tx;
        $tt = $yy / 1000;
        $vp = 111.25586939 - 17.0119934518333 * $tt - 0.044091890166673 * $tt * $tt - 4.37356166661345E-04 * $tt * $tt * $tt + 8.16716666602386E-06 * $tt * $tt * $tt * $tt;
        $rvp = $vp * 2 * pi() / 360;
        $peri = array();
        for ($i = 0; $i < $num; $i++) {
            $flag = 0;
            $th = $ath * $i + $rvp;
            if ($th > pi() && $th <= 3 * pi()) {
                $th = 2 * pi() - $th;
                $flag = 1;
            }
            if ($th > 3 * pi()) {
                $th = 4 * pi() - $th;
                $flag = 2;
            }
            $f1 = 2 * atan((sqrt((1 - $e) / (1 + $e)) * tan($th / 2)));
            $f2 = ($e * sqrt(1 - $e * $e) * sin($th)) / (1 + $e * cos($th));
            $f = ($f1 - $f2) * $ty / 2 / pi();
            if ($flag == 1){
                $f = $ty - $f;
            }
            if ($flag == 2){
                $f = 2 * $ty - $f;
            }
            $peri[$i] = $f;
        }
        $jqjd = [];
        for ($i = 0; $i < $num; $i++) {
            $jqjd[$i] = $jd + $peri[$i] - $peri[0];
        }

        return $jqjd;
    }

    /**
     * 获取指定年的春分开始作Perturbaton调整后的24节气,可以多取2个
     * @param int $yy
     * @param int $start 0-25
     * @param int $end 0-25
     * @return array
     */
    private function GetAdjustedJQ(int $yy, int $start, int $end):array {
        if($start<0 || $start>25){
            return [];
        }
        if($end<0 || $end>25){
            return [];
        }

        $jq = [];

        $jqjd = $this->MeanJQJD($yy); // 获取该年春分开始的24节气时间点
        foreach ($jqjd as $k => $jd){
            if($k < $start){
                continue;
            }
            if($k > $end){
                continue;
            }
            $ptb = $this->Perturbation($jd); // 取得受perturbation影响所需微调
            $dt = $this->DeltaT($yy, intval(floor(($k+1) / 2) + 3)); // 修正dynamical time to Universal time
            $jq[$k] = $jd + $ptb - $dt / 60 / 24; // 加上摄动调整值ptb,减去对应的Delta T值(分钟转换为日)
            $jq[$k] = $jq[$k] + 1 / 3; // 因中国(北京、重庆、上海)时间比格林威治时间先行8小时，即1/3日
        }
        return $jq;
    }
    /**
     * 求出以某年立春点开始的节(注意:为了方便计算起运数,此处第0位为上一年的小寒)
     * @param int $yy
     * @return array jq[(2*$k+21)%24]
     */
    private function GetPureJQsinceSpring(int $yy):array {
        $jdpjq = [];

        $dj = $this->GetAdjustedJQ($yy - 1, 19, 23); // 求出含指定年立春开始之3个节气JD值,以前一年的年值代入
        foreach ($dj as $k => $v){
            if($k < 19){
                continue;
            }
            if($k > 23){
                continue;
            }
            if($k % 2 == 0){
                continue;
            }
            $jdpjq[] = $dj[$k]; // 19小寒;20大寒;21立春;22雨水;23惊蛰
        }

        $dj = $this->GetAdjustedJQ($yy, 0, 25); // 求出指定年节气之JD值,从春分开始,到大寒,多取两个确保覆盖一个公历年,也方便计算起运数
        foreach ($dj as $k => $v){
            if($k % 2 == 0){
                continue;
            }
            $jdpjq[] = $dj[$k];
        }

        return $jdpjq;
    }
    /**
     * 求出自冬至点为起点的连续15个中气
     * @param int $yy
     * @return array jq[(2*$k+18)%24]
     */
    private function GetZQsinceWinterSolstice(int $yy):array {
        $jdzq = array();

        $dj = $this->GetAdjustedJQ($yy - 1, 18, 23); // 求出指定年冬至开始之节气JD值,以前一年的值代入
        $jdzq[0] = $dj[18]; //冬至
        $jdzq[1] = $dj[20]; //大寒
        $jdzq[2] = $dj[22]; //雨水

        $dj = $this->GetAdjustedJQ($yy, 0, 23); // 求出指定年节气之JD值
        foreach ($dj as $k => $v){
            if($k%2 != 0){
                continue;
            }
            $jdzq[] = $dj[$k];
        }

        return $jdzq;
    }

    /**
     * 求出实际新月点
     * 以2000年初的第一个均值新月点为0点求出的均值新月点和其朔望月之序數 k 代入此副程式來求算实际新月点
     * @param unknown $k
     * @return number
     */
    private function TrueNewMoon($k) {
        $jdt = 2451550.09765 + $k * $this->synmonth;
        $t = ($jdt - 2451545) / 36525; // 2451545为2000年1月1日正午12时的JD
        $t2 = $t * $t; // square for frequent use
        $t3 = $t2 * $t; // cube for frequent use
        $t4 = $t3 * $t; // to the fourth
        // mean time of phase
        $pt = $jdt + 0.0001337 * $t2 - 0.00000015 * $t3 + 0.00000000073 * $t4;
        // Sun's mean anomaly(地球绕太阳运行均值近点角)(从太阳观察)
        $m = 2.5534 + 29.10535669 * $k - 0.0000218 * $t2 - 0.00000011 * $t3;
        // Moon's mean anomaly(月球绕地球运行均值近点角)(从地球观察)
        $mprime = 201.5643 + 385.81693528 * $k + 0.0107438 * $t2 + 0.00001239 * $t3 - 0.000000058 * $t4;
        // Moon's argument of latitude(月球的纬度参数)
        $f = 160.7108 + 390.67050274 * $k - 0.0016341 * $t2 - 0.00000227 * $t3 + 0.000000011 * $t4;
        // Longitude of the ascending node of the lunar orbit(月球绕日运行轨道升交点之经度)
        $omega = 124.7746 - 1.5637558 * $k + 0.0020691 * $t2 + 0.00000215 * $t3;
        // 乘式因子
        $es = 1 - 0.002516 * $t - 0.0000074 * $t2;
        // 因perturbation造成的偏移：
        $apt1 = -0.4072 * sin((pi() / 180) * $mprime);
        $apt1 += 0.17241 * $es * sin((pi() / 180) * $m);
        $apt1 += 0.01608 * sin((pi() / 180) * 2 * $mprime);
        $apt1 += 0.01039 * sin((pi() / 180) * 2 * $f);
        $apt1 += 0.00739 * $es * sin((pi() / 180) * ($mprime - $m));
        $apt1 -= 0.00514 * $es * sin((pi() / 180) * ($mprime + $m));
        $apt1 += 0.00208 * $es * $es * sin((pi() / 180) * (2 * $m));
        $apt1 -= 0.00111 * sin((pi() / 180) * ($mprime - 2 * $f));
        $apt1 -= 0.00057 * sin((pi() / 180) * ($mprime + 2 * $f));
        $apt1 += 0.00056 * $es * sin((pi() / 180) * (2 * $mprime + $m));
        $apt1 -= 0.00042 * sin((pi() / 180) * 3 * $mprime);
        $apt1 += 0.00042 * $es * sin((pi() / 180) * ($m + 2 * $f));
        $apt1 += 0.00038 * $es * sin((pi() / 180) * ($m - 2 * $f));
        $apt1 -= 0.00024 * $es * sin((pi() / 180) * (2 * $mprime - $m));
        $apt1 -= 0.00017 * sin((pi() / 180) * $omega);
        $apt1 -= 0.00007 * sin((pi() / 180) * ($mprime + 2 * $m));
        $apt1 += 0.00004 * sin((pi() / 180) * (2 * $mprime - 2 * $f));
        $apt1 += 0.00004 * sin((pi() / 180) * (3 * $m));
        $apt1 += 0.00003 * sin((pi() / 180) * ($mprime + $m - 2 * $f));
        $apt1 += 0.00003 * sin((pi() / 180) * (2 * $mprime + 2 * $f));
        $apt1 -= 0.00003 * sin((pi() / 180) * ($mprime + $m + 2 * $f));
        $apt1 += 0.00003 * sin((pi() / 180) * ($mprime - $m + 2 * $f));
        $apt1 -= 0.00002 * sin((pi() / 180) * ($mprime - $m - 2 * $f));
        $apt1 -= 0.00002 * sin((pi() / 180) * (3 * $mprime + $m));
        $apt1 += 0.00002 * sin((pi() / 180) * (4 * $mprime));

        $apt2 = 0.000325 * sin((pi() / 180) * (299.77 + 0.107408 * $k - 0.009173 * $t2));
        $apt2 += 0.000165 * sin((pi() / 180) * (251.88 + 0.016321 * $k));
        $apt2 += 0.000164 * sin((pi() / 180) * (251.83 + 26.651886 * $k));
        $apt2 += 0.000126 * sin((pi() / 180) * (349.42 + 36.412478 * $k));
        $apt2 += 0.00011 * sin((pi() / 180) * (84.66 + 18.206239 * $k));
        $apt2 += 0.000062 * sin((pi() / 180) * (141.74 + 53.303771 * $k));
        $apt2 += 0.00006 * sin((pi() / 180) * (207.14 + 2.453732 * $k));
        $apt2 += 0.000056 * sin((pi() / 180) * (154.84 + 7.30686 * $k));
        $apt2 += 0.000047 * sin((pi() / 180) * (34.52 + 27.261239 * $k));
        $apt2 += 0.000042 * sin((pi() / 180) * (207.19 + 0.121824 * $k));
        $apt2 += 0.00004 * sin((pi() / 180) * (291.34 + 1.844379 * $k));
        $apt2 += 0.000037 * sin((pi() / 180) * (161.72 + 24.198154 * $k));
        $apt2 += 0.000035 * sin((pi() / 180) * (239.56 + 25.513099 * $k));
        $apt2 += 0.000023 * sin((pi() / 180) * (331.55 + 3.592518 * $k));
        return $pt + $apt1 + $apt2;
    }
    /**
     * 对于指定日期时刻所属的朔望月,求出其均值新月点的月序数
     * @param float $jd
     * @return array
     */
    private function MeanNewMoon($jd):array {
        // $kn为从2000年1月6日14时20分36秒起至指定年月日之阴历月数,以synodic month为单位
        $kn = floor(($jd - 2451550.09765) / $this->synmonth); // 2451550.09765为2000年1月6日14时20分36秒之JD值.
        $jdt = 2451550.09765 + $kn * $this->synmonth;
        // Time in Julian centuries from 2000 January 0.5.
        $t = ($jdt - 2451545) / 36525; // 以100年为单位,以2000年1月1日12时为0点
        $thejd = $jdt + 0.0001337 * $t * $t - 0.00000015 * $t * $t * $t + 0.00000000073 * $t * $t * $t * $t;
        // 2451550.09765为2000年1月6日14时20分36秒,此为2000年后的第一个均值新月
        return [$kn, $thejd];
    }
    /**
     * 将儒略日历时间转换为公历(格里高利历)时间
     * @param float $jd
     * @return array(年,月,日,时,分,秒)
     */
    private function Julian2Solar($jd):array {
        $jd = (float)$jd;

        if ($jd >= 2299160.5) { //1582年10月15日,此日起是儒略日历,之前是儒略历
            $y4h = 146097;
            $init = 1721119.5;
        } else {
            $y4h = 146100;
            $init = 1721117.5;
        }
        $jdr = floor($jd - $init);
        $yh = $y4h / 4;
        $cen = floor(($jdr + 0.75) / $yh);
        $d = floor($jdr + 0.75 - $cen * $yh);
        $ywl = 1461 / 4;
        $jy = floor(($d + 0.75) / $ywl);
        $d = floor($d + 0.75 - $ywl * $jy + 1);
        $ml = 153 / 5;
        $mp = floor(($d - 0.5) / $ml);
        $d = floor(($d - 0.5) - 30.6 * $mp + 1);
        $y = (100 * $cen) + $jy;
        $m = ($mp + 2) % 12 + 1;
        if ($m < 3){
            $y = $y + 1;
        }
        $sd = floor(($jd + 0.5 - floor($jd + 0.5)) * 24 * 60 * 60 + 0.00005);
        $mt = floor($sd / 60);
        $ss = $sd % 60;
        $hh = floor($mt / 60);
        $mt = $mt % 60;
        $yy = floor($y);
        $mm = floor($m);
        $dd = floor($d);

        return [$yy, $mm, $dd, $hh, $mt, $ss];
    }

    /**
     * 以比较日期法求算冬月及其余各月名称代码,包含闰月,冬月为0,腊月为1,正月为2,其余类推.闰月多加0.5
     * @param int $yy
     * @return array
     */
    private function GetZQandSMandLunarMonthCode(int $yy):array {
        $mc = [];

        $jdzq = $this->GetZQsinceWinterSolstice($yy); // 取得以前一年冬至为起点之连续15个中气
        $jdnm = $this->GetSMsinceWinterSolstice($yy, $jdzq[0]); // 求出以含冬至中气为阴历11月(冬月)开始的连续16个朔望月的新月點
        $yz = 0; // 设定旗标,0表示未遇到闰月,1表示已遇到闰月
        if (floor($jdzq[12] + 0.5) >= floor($jdnm[13] + 0.5)) { // 若第13个中气jdzq(12)大于或等于第14个新月jdnm(13)
            for ($i = 1; $i <= 14; $i++) { // 表示此两个冬至之间的11个中气要放到12个朔望月中,
                // 至少有一个朔望月不含中气,第一个不含中气的月即为闰月
                // 若阴历腊月起始日大於冬至中气日,且阴历正月起始日小于或等于大寒中气日,则此月为闰月,其余同理
                if (floor(($jdnm[$i] + 0.5) > floor($jdzq[$i - 1 - $yz] + 0.5) && floor($jdnm[$i + 1] + 0.5) <= floor($jdzq[$i - $yz] + 0.5))) {
                    $mc[$i] = $i - 0.5;
                    $yz = 1; //标示遇到闰月
                } else {
                    $mc[$i] = $i - $yz; // 遇到闰月开始,每个月号要减1
                }
            }
        } else { // 否则表示两个连续冬至之间只有11个整月,故无闰月
            for ($i = 0; $i <= 12; $i++) { // 直接赋予这12个月月代码
                $mc[$i] = $i;
            }
            for ($i = 13; $i <= 14; $i++) { //处理次一置月年的11月与12月,亦有可能含闰月
                // 若次一阴历腊月起始日大于附近的冬至中气日,且阴历正月起始日小于或等于大寒中气日,则此月为腊月,次一正月同理.
                if (floor(($jdnm[$i] + 0.5) > floor($jdzq[$i - 1 - $yz] + 0.5) && floor($jdnm[$i + 1] + 0.5) <= floor($jdzq[$i - $yz] + 0.5))) {
                    $mc[$i] = $i - 0.5;
                    $yz = 1; // 标示遇到闰月
                } else {
                    $mc[$i] = $i - $yz; // 遇到闰月开始,每个月号要减1
                }
            }
        }
        return [$jdzq, $jdnm, $mc];
    }
    /**
     * 求算以含冬至中气为阴历11月开始的连续16个朔望月
     * @param int $yy 年份
     * @param float $jdws 冬至的儒略日历时间
     * @return array
     */
    private function GetSMsinceWinterSolstice(int $yy, $jdws):array {
        $tjd = [];
        $jd = $this->Solar2Julian($yy - 1, 11, 1, 0, 0, 0); //求年初前兩個月附近的新月點(即前一年的11月初)
        list($kn, $thejd) = $this->MeanNewMoon($jd); //求得自2000年1月起第kn個平均朔望日及其JD值
        for ($i = 0; $i <= 19; $i++) { //求出連續20個朔望月
            $k = $kn + $i;
            $mjd = $thejd + $this->synmonth * $i;
            $tjd[$i] = $this->TrueNewMoon($k) + 1 / 3; //以k值代入求瞬時朔望日,因中國比格林威治先行8小時,加1/3天
            //下式為修正dynamical time to Universal time
            $tjd[$i] = $tjd[$i] - $this->DeltaT($yy, $i - 1) / 1440; //1為1月,0為前一年12月,-1為前一年11月(當i=0時,i-1=-1,代表前一年11月)
        }
        for ($j = 0; $j <= 18; $j++) {
            if (floor($tjd[$j] + 0.5) > floor($jdws + 0.5)) {
                break;
            } // 已超過冬至中氣(比較日期法)
        }

        $jdnm = [];
        for ($k = 0; $k <= 15; $k++) { // 取上一步的索引值
            $jdnm[$k] = $tjd[$j - 1 + $k]; // 重排索引,使含冬至朔望月的索引為0
        }
        return $jdnm;
    }
    /**
     * 將公历时间转换为儒略日历时间
     * @param int $yy
     * @param int $mm
     * @param int $dd
     * @param int $hh [0-23]
     * @param int $mt [0-59]
     * @param int $ss [0-59]
     * @return boolean|number
     */
    private function Solar2Julian($yy, $mm, $dd, $hh=0, $mt=0, $ss=0) {
        if(! $this->ValidDate($yy, $mm, $dd)){
            return false;
        }
        if($hh < 0 || $hh >= 24){
            return false;
        }
        if($mt < 0 || $mt >= 60){
            return false;
        }
        if($ss < 0 || $ss >= 60){
            return false;
        }

        $yp = $yy + floor(($mm - 3) / 10);
        if (($yy > 1582) || ($yy == 1582 && $mm > 10) || ($yy == 1582 && $mm == 10 && $dd >= 15)) { //这一年有十天是不存在的
            $init = 1721119.5;
            $jdy = floor($yp * 365.25) - floor($yp / 100) + floor($yp / 400);
        }
        if (($yy < 1582) || ($yy == 1582 && $mm < 10) || ($yy == 1582 && $mm == 10 && $dd <= 4)) {
            $init = 1721117.5;
            $jdy = floor($yp * 365.25);
        }
        if(! $init){
            return false;
        }
        $mp = floor($mm + 9) % 12;
        $jdm = $mp * 30 + floor(($mp + 1) * 34 / 57);
        $jdd = $dd - 1;
        $jdh = ($hh + ($mt + ($ss / 60))/60) / 24;
        return $jdy + $jdm + $jdd + $jdh + $init;
    }

    /**
     * 判断公历日期是否有效
     * @param int $yy
     * @param int $mm
     * @param int $dd
     * @return boolean
     */
    public function ValidDate($yy, $mm, $dd):bool {
        if ($yy < -1000 || $yy > 3000) { //适用于西元-1000年至西元3000年,超出此范围误差较大
            return false;
        }

        if ($mm < 1 || $mm > 12) { //月份超出範圍
            return false;
        }

        if ($yy == 1582 && $mm == 10 && $dd >= 5 && $dd < 15) { //这段日期不存在.所以1582年10月只有20天
            return false;
        }

        $ndf1 = -($yy % 4 == 0); //可被四整除
        $ndf2 = (($yy % 400 == 0) - ($yy % 100 == 0)) && ($yy > 1582);
        $ndf = $ndf1 + $ndf2;
        $dom = 30 + ((abs($mm - 7.5) + 0.5) % 2) - intval($mm == 2) * (2 + $ndf);
        if ($dd <= 0 || $dd > $dom) {
            if ($ndf == 0 && $mm == 2 && $dd == 29) { //此年無閏月

            } else { //日期超出範圍

            }
            return false;
        }

        return true;
    }
    /**
     * 获取公历某个月有多少天
     * @param int $yy
     * @param int $mm
     * @return number
     */
    public function GetSolarDays(int $yy, int $mm):int{
        if ($yy < -1000 || $yy > 3000) { //适用于西元-1000年至西元3000年,超出此范围误差较大
            return 0;
        }

        if ($mm < 1 || $mm > 12) { //月份超出範圍
            return 0;
        }
        $ndf1 = -($yy % 4 == 0); //可被四整除
        $ndf2 = (($yy % 400 == 0) - ($yy % 100 == 0)) && ($yy > 1582);
        $ndf = $ndf1 + $ndf2;
        return 30 + ((abs($mm - 7.5) + 0.5) % 2) - intval($mm == 2) * (2 + $ndf);
    }
    /**
     * 获取农历某个月有多少天
     * @param int $yy
     * @param int $mm
     * @param int $isLeap
     * @return number
     */
    public function GetLunarDays(int $yy, int $mm, int $isLeap):int{
        if ($yy < -1000 || $yy > 3000) { //适用于西元-1000年至西元3000年,超出此范围误差较大
            return 0;
        }
        if ($mm < 1 || $mm > 12){ //輸入月份必須在1-12月之內
            return 0;
        }
        list($jdzq, $jdnm, $mc) = $this->GetZQandSMandLunarMonthCode($yy);

        $leap = 0; //若閏月旗標為0代表無閏月
        for ($j = 1; $j <= 14; $j++) { //確認指定年前一年11月開始各月是否閏月
            if ($mc[$j] - floor($mc[$j]) > 0) { //若是,則將此閏月代碼放入閏月旗標內
                $leap = floor($mc[$j] + 0.5); //leap=0對應阴曆11月,1對應阴曆12月,2對應阴曆隔年1月,依此類推.
                break;
            }
        }

        $mm = $mm + 2; //11月對應到1,12月對應到2,1月對應到3,2月對應到4,依此類推

        for ($i = 0; $i <= 14; $i++) { //求算阴曆各月之大小,大月30天,小月29天
            $nofd[$i] = floor($jdnm[$i + 1] + 0.5) - floor($jdnm[$i] + 0.5); //每月天數,加0.5是因JD以正午起算
        }

        $dy = 0; //当月天数
        $er = 0; //若輸入值有錯誤,er值將被設定為非0

        if ($isLeap){ //若是閏月
            if ($leap < 3) { //而旗標非閏月或非本年閏月,則表示此年不含閏月.leap=0代表無閏月,=1代表閏月為前一年的11月,=2代表閏月為前一年的12月
                $er = 1; //此年非閏年
            } else { //若本年內有閏月
                if ($leap != $mm) { //但不為輸入的月份
                    $er = 2; //則此輸入的月份非閏月,此月非閏月
                } else { //若輸入的月份即為閏月
                    $dy = $nofd[$mm];
                }
            }
        } else { //若沒有勾選閏月則
            if ($leap == 0) { //若旗標非閏月,則表示此年不含閏月(包括前一年的11月起之月份)
                $dy = $nofd[$mm - 1];
            } else { //若旗標為本年有閏月(包括前一年的11月起之月份) 公式nofd(mx - (mx > leap) - 1)的用意為:若指定月大於閏月,則索引用mx,否則索引用mx-1
                $dy = $nofd[$mm + ($mm > $leap) - 1];
            }
        }
        return (int)$dy;
    }
    /**
     * 获取农历某年的闰月,0为无闰月
     * @param int $yy
     * @return number
     */
    public function GetLeap(int $yy):int{
        list($jdzq, $jdnm, $mc) = $this->GetZQandSMandLunarMonthCode($yy);

        $leap = 0; //若閏月旗標為0代表無閏月
        for ($j = 1; $j <= 14; $j++) { //確認指定年前一年11月開始各月是否閏月
            if ($mc[$j] - floor($mc[$j]) > 0) { //若是,則將此閏月代碼放入閏月旗標內
                $leap = floor($mc[$j] + 0.5); //leap=0對應阴曆11月,1對應阴曆12月,2對應阴曆隔年1月,依此類推.
                break;
            }
        }
        return (int)max(0, $leap-2);
    }
    /**
     * 根据公历月日计算星座下标
     * @param int $mm
     * @param int $dd
     * @return int|false
     */
    public function GetZodiac(int $mm, int $dd) {
        if($mm < 1 || $mm > 12){
            return false;
        }
        if($dd < 1 || $dd > 31){
            return false;
        }

        $dds = array(20,19,21,20,21,22,23,23,23,24,22,22); //星座的起始日期

        $kn = $mm - 1; //下标从0开始

        if ($dd < $dds[$kn]){ //如果早于该星座起始日期,则往前一个
            $kn = (($kn + 12) - 1) % 12; //确保是正数
        }
        return (int)$kn;
    }
    /**
     * 计算公历的某天是星期几(PHP中的date方法,此处演示儒略日历的转换作用)
     * @param int $yy
     * @param int $mm
     * @param int $dd
     * @return boolean|number
     */
    public function GetWeek(int $yy, int $mm, int $dd){
        if(! $jd = $this->Solar2Julian($yy, $mm, $dd, 12)){ //当天12点计算(因为儒略日历是中午12点为起始点)
            return false;
        }

        return (((floor($jd+1) % 7)) + 7) % 7; //模數(或餘數)為0代表星期日(因为西元前4713年1月1日12時为星期一).jd加1是因起始日為星期一
    }

    /**
     * 将农历时间转换成公历时间
     * @param int $yy
     * @param int $mm
     * @param int $dd
     * @param int $isLeap 是否闰月
     * @return false/array(年,月,日)
     */
    public function Lunar2Solar(int $yy, int $mm, int $dd, int $isLeap) {
        if ($yy < -7000 || $yy > 7000) { //超出計算能力
            return false;
        }
        if ($yy < -1000 || $yy > 3000) { //适用于西元-1000年至西元3000年,超出此范围误差较大
            return false;
        }
        if ($mm < 1 || $mm > 12){ //輸入月份必須在1-12月之內
            return false;
        }
        if ($dd < 1 || $dd > 30) { //輸入日期必須在1-30日之內
            return false;
        }

        list($jdzq, $jdnm, $mc) = $this->GetZQandSMandLunarMonthCode($yy);

        $leap = 0; //若閏月旗標為0代表無閏月
        for ($j = 1; $j <= 14; $j++) { //確認指定年前一年11月開始各月是否閏月
            if ($mc[$j] - floor($mc[$j]) > 0) { //若是,則將此閏月代碼放入閏月旗標內
                $leap = floor($mc[$j] + 0.5); //leap=0對應阴曆11月,1對應阴曆12月,2對應阴曆隔年1月,依此類推.
                break;
            }
        }

        $mm = $mm + 2; //11月對應到1,12月對應到2,1月對應到3,2月對應到4,依此類推

        for ($i = 0; $i <= 14; $i++) { //求算阴曆各月之大小,大月30天,小月29天
            $nofd[$i] = floor($jdnm[$i + 1] + 0.5) - floor($jdnm[$i] + 0.5); //每月天數,加0.5是因JD以正午起算
        }

        $jd = 0; //儒略日历时间
        $er = 0; //若輸入值有錯誤,er值將被設定為非0

        if ($isLeap){ //若是閏月
            if ($leap < 3) { //而旗標非閏月或非本年閏月,則表示此年不含閏月.leap=0代表無閏月,=1代表閏月為前一年的11月,=2代表閏月為前一年的12月
                $er = 1; //此年非閏年
            } else { //若本年內有閏月
                if ($leap != $mm) { //但不為輸入的月份
                    $er = 2; //則此輸入的月份非閏月,此月非閏月
                } else { //若輸入的月份即為閏月
                    if ($dd <= $nofd[$mm]) { //若輸入的日期不大於當月的天數
                        $jd = $jdnm[$mm] + $dd - 1; //則將當月之前的JD值加上日期之前的天數
                    } else { //日期超出範圍
                        $er = 3;
                    }
                }
            }
        } else { //若沒有勾選閏月則
            if ($leap == 0) { //若旗標非閏月,則表示此年不含閏月(包括前一年的11月起之月份)
                if ($dd <= $nofd[$mm - 1]) { //若輸入的日期不大於當月的天數
                    $jd = $jdnm[$mm - 1] + $dd - 1; //則將當月之前的JD值加上日期之前的天數
                } else { //日期超出範圍
                    $er = 4;
                }
            } else { //若旗標為本年有閏月(包括前一年的11月起之月份) 公式nofd(mx - (mx > leap) - 1)的用意為:若指定月大於閏月,則索引用mx,否則索引用mx-1
                if ($dd <= $nofd[$mm + ($mm > $leap) - 1]) { //若輸入的日期不大於當月的天數
                    $jd = $jdnm[$mm + ($mm > $leap) - 1] + $dd - 1; //則將當月之前的JD值加上日期之前的天數
                } else { //日期超出範圍
                    $er = 4;
                }
            }
        }

        return $er ? false : array_slice($this->Julian2Solar($jd), 0, 3);
    }
    /**
     * 将公历时间转换成农历时间
     * @param int $yy
     * @param int $mm
     * @param int $dd
     * @return bool|array(年,月,日,是否闰月)
     */
    public function Solar2Lunar(int $yy, int $mm, int $dd) {
        if (! $this->ValidDate($yy, $mm, $dd)) { // 验证输入的日期是否正确
            return false;
        }

        $prev = 0; //是否跨年了,跨年了则减一
        $isLeap = 0;//是否闰月

        list($jdzq, $jdnm, $mc) = $this->GetZQandSMandLunarMonthCode($yy);

        $jd = $this->Solar2Julian($yy, $mm, $dd, 12, 0, 0); //求出指定年月日之JD值
        if (floor($jd) < floor($jdnm[0] + 0.5)) {
            $prev = 1;
            list($jdzq, $jdnm, $mc) = $this->GetZQandSMandLunarMonthCode($yy - 1);
        }
        for ($i = 0; $i <= 14; $i++) { //指令中加0.5是為了改為從0時算起而不從正午算起
            if (floor($jd) >= floor($jdnm[$i] + 0.5) && floor($jd) < floor($jdnm[$i + 1] + 0.5)) {
                $mi = $i;
                break;
            }
        }

        if ($mc[$mi] < 2 || $prev == 1) { //年
            $yy = $yy - 1;
        }

        if (($mc[$mi] - floor($mc[$mi])) * 2 + 1 != 1) { //因mc(mi)=0對應到前一年阴曆11月,mc(mi)=1對應到前一年阴曆12月,mc(mi)=2對應到本年1月,依此類推
            $isLeap = 1;
        }
        $mm = intval((floor($mc[$mi] + 10) % 12) + 1); //月

        $dd = intval(floor($jd) - floor($jdnm[$mi] + 0.5) + 1); //日,此處加1是因為每月初一從1開始而非從0開始

        return array($yy, $mm, $dd, $isLeap);
    }
    /**
     * 求出含某公历年立春点开始的24节气
     * @param int $yy
     * @return array jq[($k+21)%24]
     */
    public function Get24JieQi(int $yy):array {

        $jq = [];

        $dj = $this->GetAdjustedJQ($yy - 1, 21, 23); //求出含指定年立春開始之3個節氣JD值,以前一年的年值代入
        foreach ($dj as $k => $v){
            if($k < 21){
                continue;
            }
            if($k > 23){
                continue;
            }
            $jq[] = $this->Julian2Solar($dj[$k]); //21立春;22雨水;23惊蛰
        }

        $dj = $this->GetAdjustedJQ($yy, 0, 20); //求出指定年節氣之JD值,從春分開始
        foreach ($dj as $k => $v){
            $jq[] = $this->Julian2Solar($dj[$k]);
        }

        return $jq;
    }
    /**
     * 四柱計算,分早子时晚子时,传公历
     * @param int $yy
     * @param int $mm
     * @param int $dd
     * @param int $hh 时间(0-23)
     * @param int $mt 分钟数(0-59),在跨节的时辰上会需要,有的排盘忽略了跨节
     * @param int $ss 秒数(0-59)
     * @return array(天干, 地支, 对应的儒略日历时间, 对应年的12节+前后N节, 对应时间所处节的索引)
     */
    public function GetGanZhi(int $yy, int $mm, int $dd, int $hh, int $mt=0, int $ss=0):array{
        if(! $jd = $this->Solar2Julian($yy, $mm, $dd, $hh, $mt, max(1, $ss))){ //多加一秒避免精度问题
            return array();
        }

        $tg = $dz = array();

        $jq = $this->GetPureJQsinceSpring($yy); //取得自立春開始的节,该数组长度固定为16
        if ($jd < $jq[1]) { //jq[1]為立春,約在2月5日前後,
            $yy = $yy - 1; //若小於jq[1],則屬於前一個節氣年
            $jq = $this->GetPureJQsinceSpring($yy); //取得自立春開始的节
        }

        $ygz = (($yy + 4712 + 24) % 60 + 60) % 60;
        $tg[0] = $ygz % 10; //年干
        $dz[0] = $ygz % 12; //年支

        for ($j = 0; $j <= 15; $j++) { //比較求算節氣月,求出月干支
            if ($jq[$j] >= $jd) { //已超過指定時刻,故應取前一個節氣
                $ix = $j-1;
                break;
            }
        }

        $tmm = (($yy + 4712) * 12 + ($ix - 1) + 60) % 60; //数组0为前一年的小寒所以这里再减一
        $mgz = ($tmm + 50) % 60;
        $tg[1] = $mgz % 10; //月干
        $dz[1] = $mgz % 12; //月支

        $jda = $jd + 0.5; //計算日柱之干支,加0.5是將起始點從正午改為從0點開始.
        $thes = (($jda - floor($jda)) * 86400) + 3600; //將jd的小數部份化為秒,並加上起始點前移的一小時(3600秒),取其整數值
        $dayjd = floor($jda) + $thes / 86400; //將秒數化為日數,加回到jd的整數部份
        $dgz = (floor($dayjd + 49) % 60 + 60) % 60;
        $tg[2] = $dgz % 10; //日干
        $dz[2] = $dgz % 12; //日支
        if($this->zwz && ($hh >= 23)){ //区分早晚子时,日柱前移一柱
            $tg[2] = ($tg[2] + 10 - 1) % 10;
            $dz[2] = ($dz[2] + 12 - 1) % 12;
        }

        $dh = $dayjd * 12; //計算時柱之干支
        $hgz = (floor($dh + 48) % 60 + 60) % 60;
        $tg[3] = $hgz % 10; //時干
        $dz[3] = $hgz % 12; //時支

        return [$tg, $dz, $jd, $jq, $ix];
    }

    /**
     * 公历年排盘
     * @param int $gd 0男1女
     * @param int $yy
     * @param int $mm
     * @param int $dd
     * @param int $hh 时间(0-23)
     * @param int $mt 分钟数(0-59),在跨节的时辰上会需要,有的排盘忽略了跨节
     * @param int $ss 秒数(0-59)
     * @return array
     */
    public function GetInfo(int $gd, int $yy, int $mm, int $dd, int $hh, int $mt=0, int $ss=0):array{
        if(! in_array($gd, array(0,1))){
            return [];
        }

        $ret = [];
        $big_tg = $big_dz = []; //大运

        list($tg, $dz, $jd, $jq, $ix) = $this->GetGanZhi($yy, $mm, $dd, $hh, $mt, $ss);

        $pn = $tg[0] % 2; //起大运.阴阳年干:0阳年1阴年

        if(($gd == 0 && $pn == 0) || ($gd == 1 && $pn == 1)) { //起大运时间,阳男阴女顺排
            $span = $jq[$ix + 1] - $jd; //往后数一个节,计算时间跨度

            for($i = 1; $i <= 12; $i++){ //大运干支
                $big_tg[] = ($tg[1] + $i) % 10;
                $big_dz[] = ($dz[1] + $i) % 12;
            }
        } else { // 阴男阳女逆排,往前数一个节
            $span = $jd - $jq[$ix];

            for($i = 1; $i <= 12; $i++){ //确保是正数
                $big_tg[] = ($tg[1] + 20 - $i) % 10;
                $big_dz[] = ($dz[1] + 24 - $i) % 12;
            }
        }

        $days = intval($span * 4 * 30); //折合成天数:三天折合一年,一天折合四个月,一个时辰折合十天,一个小时折合五天,反推得到一年按360天算,一个月按30天算
        $y = intval($days / 360); //三天折合一年
        $m = intval($days % 360 / 30); //一天折合四个月
        $d = intval($days % 360 % 30); //一个小时折合五天

        $ret['tg'] = $tg;
        $ret['dz'] = $dz;
        $ret['big_tg'] = $big_tg;
        $ret['big_dz'] = $big_dz;
        $ret['start_desc'] = "{$y}年{$m}月{$d}天起运";
        $start_jdtime = $jd + $span * 120; //三天折合一年,一天折合四个月,一个时辰折合十天,一个小时折合五天,反推得到一年按360天算
        $ret['start_time'] = $this->Julian2Solar($start_jdtime); //转换成公历形式,注意这里变成了数组

        $ret['bazi'] = $ret['big'] = $ret['years'] = ''; //八字,大运,流年的字符表示
        $ret['big_start_time'] = array(); //各步大运的起始时间

        $ret['xz'] = $this->cxz[$this->GetZodiac($mm, $dd)]; //星座
        $ret['sx'] = $this->csa[$dz[0]]; //生肖

        for($i = 0; $i <= 3; $i++){
            $ret['bazi'] .= $this->ctg[$tg[$i]];
            $ret['bazi'] .= $this->cdz[$dz[$i]];
        }

        for($i = 0; $i < 12; $i++){
            $ret['big'] .= $this->ctg[$big_tg[$i]];
            $ret['big'] .= $this->cdz[$big_dz[$i]];
            $ret['big_start_time'][] = $this->Julian2Solar($start_jdtime + $i*10*360);
        }

        for($i=1,$j=0; ;$i++){
            if(($yy + $i) < $ret['start_time'][0]){ //还没到起运年
                continue;
            }
            if($j++ >= 120){
                break;
            }

            $t = ($tg[1] + $i) % 10;
            $d = ($dz[1] + $i) % 12;

            $ret['years'] .= $this->ctg[$t];
            $ret['years'] .= $this->cdz[$d];
            if($j%10 == 0){
                $ret['years'] .= "\n";
            }
        }

        return $ret;
    }

    /**
     * 农历月份常用名称
     * @param int $mm
     * @return string
     */
    public function MonthChinese(int $mm):string{
        $k = $mm-1;
        return $this->chinese_month[$k];
    }

    /**
     * 农历日期数字返回汉字表示法
     * @param int $lunar_day 农历日
     * @return string (getChineseDay(8) return '初八')
     */
    Public function DayChinese(int $dd):string{
        $daystr = '';

        switch ($dd)
        {
            case 10:
                $daystr = $this->chinese_day[0].$this->chinese_number[10];
                break;
            case 20:
                $daystr = $this->chinese_day[2].$this->chinese_number[10];
                break;
            case 30:
                $daystr = $this->chinese_day[3].$this->chinese_number[10];
                break;
            default:
                $k = intval(floor($dd / 10));
                $m = $dd % 10;
                $daystr = $this->chinese_day[$k].$this->chinese_number[$m];
        }

        return $daystr;
    }
}