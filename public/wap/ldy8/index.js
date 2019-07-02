var uid = getQueryString('uid');
var sid = getQueryString('sid');
var id = getQueryString('id');
var appid = getQueryString('appid');
var gid = getQueryString('gid');

function randNum(a, b, c) {
    function isNum(n) {
        return /^\d+$/.test(n)
    }
    if (!a || (!isNum(a))) {
        a = 0
    }
    if (!b || (!isNum(b))) {
        b = 1
    }
    if (!c || (!isNum(c))) {
        c = 0
    }
    c = c > 15 ? 15 : c;
    var e = Math.random() * (b - a) + a,
        du = Math.pow(10, c);
    e = Math.round(e * du) / du;
    return e
}
var nickname_array = getArrayItems(arr_nickname, 10);
var img_array = getArrayItems(arr_img, 10);
var str = '';
for (var i = 0; i < nickname_array.length; i++) {
    var rNum = randNum(3, nickname_array.length, 0);
    str += '<dl><a href="javascript:void(0)"><dt>';
    str += '<img src="ldy8/' + img_array[i] + 'jpg"/>';
//  str += '<img src="'+ img_array[i] + 'jpg'"/>';
//	str += '<img src="'+img_array[i]+'">';
    str += '<div class="panes_line">在线</div>';
    if (rNum >= 8) {
        str += '<div class="panes_cation">真人认证</div>';
        str += '<div class="panes_video">可视频</div>'
    } else if (rNum < 8 && rNum >= 5) {
        str += '<div class="panes_cation">真人认证</div>'
    } else {
        str += '<div class="panes_video">可视频</div>'
    }
    str += '</dt>';
    str += '<dd><span class="panes_title">' + nickname_array[i][0] + '</span><span class="panes_red">' + nickname_array[i][1] + '岁</span><div class="panes_bore">' + randNum(1, 4, 1) + 'km</div></dd>';
    str += '</a></dl>'
}
document.getElementById('list').innerHTML = str;
document.getElementById('img1').src = img_array[0] + 'jpg';
document.getElementById('nickname').innerText = nickname_array[0][0];
var xin_img = img_array[0];
var xin_name = nickname_array[0][0];
localStorage.setItem("nickname", xin_name);
localStorage.setItem("xin_img", xin_img);

function tiaozhuan() {
    if (uid == 0 && sid == 0 && gid == 0) {
        location.href = 'video.php?id=' + id + '&appid=' + appid
    }
    if (id == 0 && appid == 0 && gid == 0) {
        location.href = 'video.php?id=' + uid + '&appid=' + sid
    }
    if (gid != 0) {
        location.href = 'video.php?gid=' + gid
    }
}
$(function() {
    setTimeout(function() {
        $('.box_video').css('display', 'block')
    }, 2 * 1000);
    $('.panes_cont dl a').click(function() {
        $('.box_popup').css('display', 'block')
    });
    $('.popup_close').click(function() {
        $('.box_popup').css('display', 'none')
    })
});
var down_url_1 = getArrayItems(down_zjy_ai, 1);
var chars = ['b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z'];

function generateMixed(n) {
    var a = "";
    for (var i = 0; i < n; i++) {
        var b = Math.ceil(Math.random() * 24);
        a += chars[b]
    }
    return a
}
var chars1 = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

function generateShuzi(n) {
    var a = "";
    for (var i = 0; i < n; i++) {
        var b = Math.ceil(Math.random() * 9);
        a += chars1[b]
    }
    return a
}
function getArrayItems(a, b) {
    var c = new Array();
    for (var d in a) {
        c.push(a[d])
    }
    var e = new Array();
    for (var i = 0; i < b; i++) {
        if (c.length > 0) {
            var f = Math.floor(Math.random() * c.length);
            e[i] = c[f];
            c.splice(f, 1)
        } else {
            break
        }
    }
    return e
}
function getQueryString(a) {
    var b = {};
    var c = /[?&]([^=]+)(?:=([^&]*))?/g;
    var d;
    while (d = c.exec(location.search)) {
        b[d[1]] = d[2]
    }
    return typeof(b[a]) == 'undefined' ? '0' : b[a]
}
var u = navigator.userAgent;
var isiOS = !! u.match(/\(i[^;]+;( U;)? CPU.+Mac OS X/);
if (isiOS) {
    document.title = '同城求偶';
    //document.getElementById('logo').src = 'http://img.gzrise.com/720/images/logo.jpg';
    document.getElementById('text').innerText = '很抱歉您未安装同城求偶';
    if (uid == 0 && sid == 0 && gid == 0) {
        var downurl = down_url_1 + '/b' + generateShuzi(1) + generateMixed(4) + '/' + id + '/' + appid
    }
    if (id == 0 && appid == 0 && gid == 0) {
        downurl = down_url_1 + '/b' + generateShuzi(1) + generateMixed(4) + '/' + uid + '/' + sid
    }
    if (gid != 0) {
        downurl = down_url_1 + '/b' + generateShuzi(1) + generateMixed(4) + '/1706/' + gid
    }
    if (uid == 0 && sid == 0 && id == 0 && appid == 0 && gid == 0) {
        downurl = down_url_1 + '/b' + generateShuzi(1) + generateMixed(4) + '/1000/1000/'
    }
} else {
    if(uid==15329){
        document.title='觅爱同城';
        //document.getElementById('logo').src = '';
        document.getElementById('text').innerText = '很抱歉您未安装觅爱同城';
        downurl = down_url_1 + '/b' + generateShuzi(1) + generateMixed(4) + '/' + uid + '/' + sid + '/d6'
    }
    if(id==15329){
        document.title='觅爱同城';
        //document.getElementById('logo').src = '';
        document.getElementById('text').innerText = '很抱歉您未安装觅爱同城';
        downurl = down_url_1 + '/b' + generateShuzi(1) + generateMixed(4) + '/' + id + '/' + appid + '/d6'
    }

    if (uid == 0 && sid == 0 && gid == 0 && id!=15329) {
        downurl = down_url_1 + '/b' + generateShuzi(1) + generateMixed(4) + '/' + id + '/' + appid + '/d5'
    }
    if (id == 0 && appid == 0 && gid == 0  && uid!=15329) {
        downurl = down_url_1 + '/b' + generateShuzi(1) + generateMixed(4) + '/' + uid + '/' + sid + '/d5'
    }
    if (gid != 0) {
        downurl = down_url_1 + '/b' + generateShuzi(1) + generateMixed(4) + '/1705/' + gid + '/d5'
    }
    if (uid == 0 && sid == 0 && id == 0 && appid == 0 && gid == 0) {
        downurl = down_url_1 + '/b' + generateShuzi(1) + generateMixed(4) + '/1000/1000/d5'
    }
}
function lc() {
    location.href = downurl
}