# Javascript:

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
# PHP:

略...
