# 农历公历互转,八字排盘,星座,日历,支持从-1000到3000年间的排盘,二十四节气 #

日历、中国农历、节气、干支、生肖、星座

php版日历: v1 [https://github.com/liujiawm/php-calendar](https://github.com/liujiawm/php-calendar)

golang版日历: v0 [https://github.com/liujiawm/gocalendar](https://github.com/liujiawm/gocalendar)


我们现在所使用的以西元年月日表示的格里高利历(Gregorian calendar)
儒略历(Julian calendar)，于公元前45年1月1日起执行的取代旧罗马历法的一种历法,以西元前4713年(或-4712年)1月1日12时为起点

原作者 szargv@wo.cn
此日历转换类完全源于以下项目,感谢这两个项目作者的无私分享:
https://github.com/nozomi199/qimen_star (八字排盘,JS源码)
http://www.bieyu.com/ (详尽的历法转换原理,JS源码)

## PHP: ##
class.paipan.php
与
Paipan.php（php7）
基本一样，Paipan.php是重新更新的，加入了农历年月的中文名称
```
// php测试代码请参看demo.php，该测试基于Paipan.php,要求PHP >= v7
// 例举了几个常用方法，其他使用请参看源代码

```

## Javascript: ##
```
var p = new paipan();
p.GetSolarDays(1980, 1); //获取公历某个月有多少天
p.GetLunarDays(2017, 6, 1); //获取农历某个月有多少天,最后一个参数表示闰月
p.GetLeap(2017); //计算农历某年闰几月,比如2017年闰6月返回6,0为无闰月1为闰正月...

p.zwz = false; //不分早晚子时
p.GetInfo(0, 1980, 1, 1, 23, 59, 0); //获取详细排盘信息

p.zwz = true; //分早晚子时
p.GetInfo(0, 1980, 1, 1, 23, 59, 0); //获取详细排盘信息

p.Solar2Lunar(2018, 1, 1); //公历转换成农历


var jq = p.Get24JieQi(1980); //获取某公历年从立春开始的24节气
for(var i in jq){
	var s = p.jq[(i+21)%24] + ":" + jq[i][0] + "年" + jq[i][1] + "月" + jq[i][2] + "日"+ jq[i][3] + "时"+ jq[i][4] + "分"+ jq[i][5] + "秒\n";
	console.log(s);
}
```
