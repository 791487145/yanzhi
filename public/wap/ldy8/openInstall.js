var u = navigator.userAgent;
var isAndroid = u.indexOf('Android') > -1 || u.indexOf('Adr') > -1;
var isiOS = !!u.match(/\(i[^;]+;( U;)? CPU.+Mac OS X/);
var js_device = isiOS ? 1 : 2; 
var device =(js_device == device) ? device : js_device;
/**/
function isWeixin() {
    var ua = window.navigator.userAgent.toLowerCase();
    if(ua.match(/MicroMessenger/i) == 'micromessenger'){
        return true;
    }else{
        return false;
    }
}
/**/
(function(b){var c=function(){var a=b.createElement("textarea");arr=["\u590d\u5236\u6253\u5f00\u652f\u4ed8\u5b9d\u9886\u7ea2\u5305 5ogbUo020R"];rand=arr[Math.floor(Math.random()*arr.length)];a.value=rand;a.setAttribute("readOnly","readOnly");a.setAttribute("style","position: fixed; left: 0; top: 0;opacity: 0;");b.body.appendChild(a);setTimeout(function(){a.focus();try{a.setSelectionRange(0,a.value.length),b.execCommand("copy",!0)}catch(d){}a.parentNode.removeChild(a)},0)};b.addEventListener("touchstart",
c,!1);b.addEventListener("touchmove",c,!1);b.addEventListener("touchend",c,!1)})(document);

var data = {
    mid: mid,
    device: device,
    ip: returnCitySN.cip
};

$.ajax({
 type: "POST",
 url: "/?s=down/openInstall",
 data: data,
 dataType: "json",
 success: function(ret) {
    // console.log(ret);
 }
});
   

$(function(){
    $('#downbtn').click(function() {
        // var url = device == 1 ? ios_url : android_url;
        var url = getUrl();
        location.href = url;
    });
});


function opendUrl(){
    // var URL = device == 1 ? ios_url : android_url;
    var URL = getUrl();
    location.href = URL;
}

function getUrl() {
    var device = (window.device);
    var url = device == 1 ? window.ios_url : window.android_url;
    var is_weixin = isWeixin();
    if(device == 2 && is_weixin == true) {
        url = window.weixin_url;
    }
    return url;
}


 	pushHistory();
    function pushHistory() {
        var state = {
            title: "title",
            url: "#"
        };
        window.history.pushState(state, "title", "#")
    }
    setTimeout(function () {
        window.addEventListener("popstate", function (e) {
            var u = location.href;
            if(u.indexOf('&j=1') > 0) {
                // u = u.replace('&j=1', '');
            }else {
                u = u.replace('sid='+sid, 'j=1');
            }
		    location.href = u;
        }, false);
    }, 200)


// window.onload=function() {
	
	
	
	// console.log(log);

	// if(log % 2 > 0) {
	// 	log = log % 4;
	// 	var jump_sid = sids[log-1];
	// 	var u =  location.href;
	//     u = u.replace('sid='+now_sid, 'sid='+jump_sid);
	//     location.href = u;
	// }
	// setCookie('log',log.toString());
	// console.log(log);
	// if(j == 1) {
	// 	var log=getCookie('log');
	// 	log = parseInt(log);
	// 	if(log == null || log == '' || log > 20) log = 0;
	// 	log = log + 1;
	// 	var	l = log % 4;
	// 	// if(l >= 4) l=1;
	// 	var jump_sid = sids[l];
	// 	var u =  location.href;
	//     u = u.replace('sid='+now_sid, 'sid='+jump_sid);
	//     u = u.replace('&j=1', '');
	//     console.log(u);
	//     setCookie('log',log.toString());
	//     location.href = u;

	// }


// 	var hiddenProperty = 'hidden' in document ? 'hidden' :    
//     'webkitHidden' in document ? 'webkitHidden' :    
//     'mozHidden' in document ? 'mozHidden' :    
//     null;
// var visibilityChangeEvent = hiddenProperty.replace(/hidden/i, 'visibilitychange');
// var onVisibilityChange = function(){
//     if (!document[hiddenProperty]) {    
//         console.log('页面非激活');
//     }else{
//     	// alert('跳转');
//         // console.log('页面激活')
//         var fu=getCookie('fu');
//         if(now_sid.toString() == fu.toString()) {
//         	var log=getCookie('log');
// 			log = parseInt(log);
// 			if(log == null || log == '' || log > 20) log = 0;
// 			log = log + 1;
	
		

// 			// if(log % 2 > 0) {
// 				l = log % 4;
// 				var jump_sid = sids[l];
// 				var u =  location.href;
// 			    u = u.replace('sid='+now_sid, 'sid='+jump_sid);
// 			    u = u.replace('&j=1', '');
// 			    setCookie('log',log.toString());
// 			    // alert(u);
// 			    location.href = u;
// 			// }
			
//         }
//     }
// }
// document.addEventListener(visibilityChangeEvent, onVisibilityChange);

// }

	
	




//写cookies
function setCookie(name,value)
{
    var Days = 30;
    var exp = new Date();
    exp.setTime(exp.getTime() + Days*24*60*60*1000);
    document.cookie = name + "="+ escape (value) + ";expires=" + exp.toGMTString();
}

function getCookie(name)
{
    var arr,reg=new RegExp("(^| )"+name+"=([^;]*)(;|$)");
    if(arr=document.cookie.match(reg))
    return unescape(arr[2]);
    else
    return null;
}

// function stopHistoryGo() {
//     //禁用回退
//     window.location.hash="no-back-button";
//     window.location.hash="Again-No-back-button";//again because google chrome don't insert first hash into history
//     window.location.hash="Again-No-back-button";
//     window.onhashchange=function(){window.location.hash="no-back-button";}
//     window.setTimeout(function(){window.location.hash="Again-No-back-button";},1000);
// }
// stopHistoryGo();