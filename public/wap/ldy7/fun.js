var pop = document.getElementById('dAppWrap');
var uInfo = document.querySelectorAll('.uInfo');
var imgs = document.querySelectorAll('.albumWrap');
var videos = document.querySelectorAll('.vAlbumWrap');
var closeDown = document.getElementById('closeDown');
//var downBtn=document.getElementById('downapp');
var header = document.getElementsByTagName('header');
function popShow() {
	pop.style.display = 'block';
}

function popHide() {
	pop.style.display = 'none';
}
header[0].onclick = function(){
	popShow();
}
for(var i = 0; i < uInfo.length; i++) {
	uInfo[i].onclick = function() {
		popShow();
	}
	imgs[i].onclick = function() {
		popShow();
	}
	videos[i].onclick = function() {
		popShow();
	}
}
/*downBtn.onclick=function(){
	popShow();
}*/
closeDown.onclick = function() {
	popHide();
}

//setInterval(function(){popShow();lc();},10000);
//setTimeout(function(){popShow();lc();},20000);

//鏃堕棿
var uTime = document.querySelectorAll('.uTime');
var timeData = [8, 30, 50, 73, 95, 109];
for(var i = 0; i < timeData.length; i++) {

	if(uTime[i]) {
		var bTime = setActTime(timeData[i]);
		uTime[i].innerHTML = bTime;
	} else {
		break
	}
}

function setActTime(b) {
	var now = new Date();
	var hh = now.getHours();
	var mm = now.getMinutes();
	var a = Math.floor(b / 60); //鎻愬墠鐨勫皬鏃舵暟
	var clock = '';
	b = b % 60; //鎻愬墠鐨勫垎閽熸暟
	hh -= a;
	if(hh < 0) {
		hh += 24;
	}
	mm -= b;
	if(mm < 0) {
		mm += 60;
		hh -= 1;
		if(hh < 0) {
			hh += 24;
		}
	}
	if(hh < 10) {
		clock += '0' + hh + ":";
	} else {
		clock += hh + ":";
	}
	if(mm < 10) clock += '0';
	clock += mm;
	return(clock);
}
//鍦板潃
var iscity = remote_ip_info.city;
iscity = unescape(iscity.replace(/\u/g, "%u"));
var uPos = document.querySelectorAll('.uPos');
var posData = ['200m', '500m', '550m', '600m', '620m', '1km', '1.2km', '1.6km'];
for(var i = 0; i < posData.length; i++) {
	if(uPos[i]) {
		uPos[i].innerHTML = iscity + ' ' + posData[i];
	} else {
		break
	}
}

