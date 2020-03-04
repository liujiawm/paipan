<?php
declare (strict_types = 1);
namespace paipan;

include_once "Paipan.php";

class TestPaipan extends Paipan {

    public function TestGetSolarDays(int $yy,int $mm){
        print "公历{$yy}年{$mm}月有".$this->GetSolarDays($yy,$mm)."天";
    }

    public function TestGetLunarDays(int $yy,int $mm){
        $isLeap = 0;
        // 简单测式，如果该月为闰月，则按闰月算，实际使用时，需要自行给定月份是否为闰月
        if ($this->GetLeap($yy) == $mm ) $isLeap = 1;

        $mmstr = $this->MonthChinese($mm);

        print "农历{$yy}年{$mmstr}月有多少".$this->GetLunarDays($yy,$mm,$isLeap)."天";
    }

    public function TestLunar2Solar(int $yy, int $mm, int $dd){
        $isLeap = 0;
        // 简单测式，如果该月为闰月，则按闰月算，实际使用时，需要自行给定月份是否为闰月
        if ($this->GetLeap($yy) == $mm ) $isLeap = 1;

        $mmstr = $this->MonthChinese($mm);
        $ddstr = $this->DayChinese($dd);

        $symd = $this->Lunar2Solar($yy,$mm,$dd,$isLeap);
        if ($symd !== false) {
            list($sy,$sm,$sd) = $symd;
            print "农历{$yy}年{$mmstr}月{$ddstr}是公历的{$sy}年{$sm}月{$sd}日";
        }
    }

    public function TestSolar2Lunar(int $yy, int $mm, int $dd){
        $lymd = $this->Solar2Lunar($yy,$mm,$dd);
        if ($lymd != false) {
            list($ly,$lm,$ld,$isLeap) = $lymd;
            $leap = "";
            if ($isLeap != 0) $leap = "（闰）";
            $mmstr = $this->MonthChinese($lm);
            $ddstr = $this->DayChinese($ld);
            print "公历{$yy}年{$mm}月{$dd}日是农历的{$ly}年{$mmstr}{$leap}月{$ddstr}";
        }

    }

    public function TestGet24JieQi(int $yy){

        $jq = $this->Get24JieQi($yy);
        if (!empty($jq)) {
            print $yy."年从立春开始的节气如下：\n";
            foreach($jq as $k=>$day){
                $kk = ($k+21)%24;
                print "[".$this->jq[$kk]."]的时间是：".$day[0]."年".$day[1]."月".$day[2]."日".$day[3]."时".$day[4]."分".$day[5]."秒\n";
            }
        }


    }

}

$p = new TestPaipan;

print '公历某年某月有多少天GetSolarDays($yy, $mm):int'."\n";
$p->TestGetSolarDays(2020,2);
print "\n".'---------------------------------'."\n";
print '农历某年某月有多少天($yy, $mm, $isLeap):int'."\n";
$p->TestGetLunarDays(2020,1);
print "\n".'---------------------------------'."\n";
print '农历某年某月某日是公历的某年某月某日Lunar2Solar(int $yy, int $mm, int $dd, int $isLeap)'."\n";
$p->TestLunar2Solar(2020,2,11); // 2020-3-4
print "\n".'---------------------------------'."\n";
print '公历某年某月某日是农历的某年某月某日Solar2Lunar(int $yy, int $mm, int $dd)'."\n";
$p->TestSolar2Lunar(2020,3,4); // 2020年二月十一
print "\n".'---------------------------------'."\n";
print '公历某年立春点开始的24节气Get24JieQi(int $yy):array'."\n";
$p->TestGet24JieQi(2020);


$pp = $p->GetInfo(0, 1979, 11, 21, 4, 59, 0);
var_dump($pp);

