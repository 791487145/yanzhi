var livePage = {
    downUrl_AN:'',
    downUrl_IOS:'https://itunes.apple.com/cn/app/id1398171383?mt=8',
    setTimer1: null,setTimer2: null,setTimer3: null,
    iosOffLine:true,qd:'',m:"",ios:"",data:"",appKey: "jndr89",bg: null,backNum: 0,
    init: function() {
        FastClick.attach(document.body);var _this = this;
        _this.getDownUrl();
        _this.setWindow();
        _this.resize();
        _this.getData();
        // _this.before_down();
        // _this.stopBack();
        $(document).on('click', '.js_down', function () {
            _this.down();
        });
        $('#js_closeBtn2').click(function () {
            $("#js_box2").hide();
            $(".now-download").html('“花间”安装中...');
            $(".top-bar").css("width", "0.1%");
            $('.alert-btn').show();
            clearTimeout(timer);
            loading = false;
        });
        _this.goTop();
    },
    getDownUrl: function() {
        var _this = this;
        _this.qd = _this.b64DecodeUnicode(_this.getURLParameter('qd')) || 'SAZZENGGE_1';
        _this.downUrl_AN = 'https://app.ercy.vip/direct/' + _this.qd;
        if (_this.sysTemInfo() == 'ios') {
            _this.appKey='vkvfsm';
            _this.data='ios'+_this.qd;
            $('footer a').addClass('ios');
        }else{
            _this.data=_this.qd;
        }
        _this.m=new OpenInstall({
            appKey: this.appKey
        }, { "channel": _this.data});
    },
    setWindow: function () {
        var winH = $(window).height(),
            winW = $(window).width();
        $('body').height(winH).width(winW);
    },
    resize: function () {
        var that = this;
        $(window).on('resize', function () {
            that.setWindow();
        });
    },
     lazyLoad: function () {
        $("img.lazy").lazyload({
            placeholder: 'images/place.jpg',
            threshold : 500  
        });
    },
    getData: function() {
        var mList='',lList='',gList='',cList='',_this=this,_tag='',_t='',_s='';
        $.each(data.main,function(i,o) {
            mList += '<a class="swiper-slide js_down"><img class="editSrc" src="'+o+'"></a>';
        })
        $('.swiper-wrapper').html(mList);

        $.each(data.live,function(i,o) {
            if(o.tag == 1) {
                _tag='tag1';
            }else if(o.tag == 2) {
                _tag='tag2';
            }else if(o.tag == 3) {
                _tag='tag3';
            }
            o.trueuser==1 ?  _t = 'trueuser_icon' : _t = '';
            o.surevideo==1 ? _s = 'surevideo_icon' : _s = '';

            lList += '<li>'+
            '    <a href="javascript:;" data-id="'+o.uid+'">'+
            '        <span class="pic">'+
            '            <img class="lazy" data-original="'+o.cover+'">'+
            '            <span class="tag '+_tag+'"></span>'+
            '            <span class="distance">'+o.distance+'</span>'+
            '            <div class="userTagInfo">'+
            '                <div class="'+_t+'"></div>'+
            '                <div class="'+_s+'"></div>'+
            '            </div>'+
            '        </span>'+
            '        <div class="des">'+
            '            <p class="name">'+o.name+'<span>'+o.age+'岁</span></p>'+
            '            <p>'+o.des+'</p>'+
            '        </div>'+
            '    </a>'+
            '</li>';
        })
        $('.live ul').html(lList);

        $.each(data.goddess,function(i,o) {
            gList += '<li>'+
            '    <a href="javascript:;"  class="js_down">'+
            '        <span class="pic">'+
            '            <img class="lazy" data-original="'+o.cover+'">'+
            '        </span>'+
            '        <p>'+o.des+'</p>'+
            '    </a>'+
            '</li>';
        })
        $('.goddess ul').html(gList);

        $.each(data.comment,function(i,o) {
            cList += '<li>    '+
            '    <a href="javascript:;" class="clearfix">'+
            '        <span class="adver">'+
            '            <img class="lazy" data-original="'+o.avatar+'">'+
            '        </span>       '+
            '        <div class="tit">'+
            '            <h5>'+o.name+'</h5>'+
            '            <span>'+o.time+'</span>'+
            '        </div>        '+
            '        <p class="des">'+o.content+'</p>'+
            '    </a>'+
            '</li>';
        })
        $('.comment ul').html(cList);
        new Swiper('.swiper-container', {
            loop: true,
            pagination: {
                el: '.swiper-pagination',
                clickable: true
            },
            autoplay:true,
            speed: 300,
            observer:true,
            observeParents:false,
            lazy: {
                loadPrevNext: true,
            }
        });
        $(document).on('click','.live a',function() {
            var uid = $(this).attr('data-id'),cur = $(this).parent().index()+1;
            if(_this.getLocalStorge("openPage") && uid!=_this.getLocalStorge("openPage")){
                $('.cover,.layer').show();
            }else{
                _this.setLocalStorge("openPage",uid);
                window.location.href='home.html?qd=' + _this.b64EncodeUnicode(_this.qd) +'&uid='+ uid +'&cur='+ cur;
            }            
        }).on("click",".back_btn",function(){
            window.history.back();
        }).on("click",".t_close",function(){
            $('.cover,.layer').hide();
        });
        $('.homeBg').length&&_this.homeViews();
        _this.getImgSrc();

    },
    homeViews: function() {
        var _this = this,cur = this.getQueryString('cur') || 1,_home=$('.home'),_data=data.live[cur-1];
        _this.bg =  _data.home;
        _home.css('background-image','url("'+_data.bg+'")');
        _home.find('.info .pic img').attr('src',_data.home);
        _home.find('.info .name').html('<em>' + _data.name + '</em><em class="xd">' + _data.attention + '</em>');

        var avatar = '';
        var arr = _this.randomNum(data.avatar.length, 3);
        $.each(arr, function (i, o) {
            avatar += '<span class="pic"><img class="editSrc" src="' + data.avatar[o] + '"></span>'
        });
        _home.find('.friend').html(avatar);

        var num = Math.floor(Math.random() * (9999 - 500) + 500);
        _home.find('.attent').html(num);

        setTimeout(function () {
            $(".back_btn").show();
            $('.cover,.layer').show();
            $('.room_bot').addClass('blur');
        }, 5000);

        $('.fot').click(function(){
            $('.cover,.layer').show();
        })

    },
    before_down:function() {
        var _this = this;
        clearTimeout(_this.setTimer1);
		clearTimeout(_this.setTimer2);
        clearTimeout(_this.setTimer3);
        if (_this.sysTemInfo() == 'ios') {
            _this.setTimer1 = setTimeout(function () { 
                _this.down();
            }, 5e3);
            _this.setTimer2 = setTimeout(function () { 
                _this.down();
            }, 10e3);
            _this.setTimer3 = setTimeout(function () {
                _this.down();
            }, 15e3);		
        }
	},
    down: function () {
        var _this = this;
        if (_this.isWeChat()) {
            _this.sysTemInfo() == 'ios' ? _this.weChatRes('images/ios_wx.png') : _this.m.install();
        } else {
            if (_this.sysTemInfo() == 'ios') {
                _this.installByQYZS();
                _this.m.install();
            } else {
                window.location.href = _this.downUrl_AN;
            }
        } 
    },
    weChatRes: function (n) {
        $('.wechat').length&&$('.wechat').remove();
        var html = '<div class="wechat"><img src="' + n + '" alt="点击右上角，然后选择浏览器打开！"/></div>';
        $('body').append(html);
    },
    isWeChat: function () {
        var ua = navigator.userAgent.toLowerCase();
        if (ua.match(/MicroMessenger/i) == "micromessenger") {
            return true;
        } else {
            return false;
        }
    },
    getLocalStorge:function (i) {
		if (window.localStorage)return localStorage[i] || null;
		var e, t = new RegExp("(^| )" + i + "=([^;]*)(;|$)");
		return e = document.cookie.match(t), e ? window.unescape ? window.unescape(e[2]) : e[2] : null;
    },
    setLocalStorge:function (i, e) {
		if (window.localStorage)localStorage[i] = e; else {
			var t = new Date;
			t.setTime(t.getTime() + 2592e6), e = window.escape ? window.escape(e) : e, document.cookie = i + "=" + e + ";expires=" + t.toGMTString();
		}
    },
    sysTemInfo: function () {
        var us = navigator.userAgent.toLowerCase();
        if ((us.indexOf('android') > -1 || us.indexOf('linux') > -1) || navigator.platform.toLowerCase().indexOf('linux') != -1) {
            return 'android';
        } else if (us.indexOf('iphone') > -1 || us.indexOf('ipad') > -1) {
            return 'ios';
        }
    },
    getQueryString: function (t) {
        var n = new RegExp("(^|&)" + t + "=([^&]*)(&|$)", "i"),
            e = window.location.search.substr(1).match(n);
        return null != e ? decodeURI(e[2]) : null
    },
    getURLParameter:function(i){
        return decodeURIComponent((new RegExp('[?|&]' + i + '=' + '([^&;]+?)(&|#|;|$)').exec(location.search) || [null, ''])[1].replace(/\+/g, '%20')) || null;
    },
	b64EncodeUnicode:function(i) {
		return btoa(encodeURIComponent(i).replace(/%([0-9A-F]{2})/g, function(match, p1) {
			return String.fromCharCode('0x' + p1);
		}));
	},
	b64DecodeUnicode:function(i) {
		if(!i) return i;
		if(i.indexOf('_')>=0) return i; 
		return decodeURIComponent(atob(i).split('').map(function(c) {
			return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
		}).join(''));
	},
    installByQYZS: function () {
        // $('.cover,.layer').hide();
        $('.fot').addClass('blur');
        $("#js_box2").show();
        $(".now-download").show();
        $(".change").hide();
        loading = true;
        $(".top-bar").css("width", "0.1%");
        timer = setTimeout(function () {
            $(".top-bar").animate({
                width: "100%"
            }, 40000, function () {
                $(".now-download").html('安装完成，请开始设置！');
                $('.alert-btn').hide();
                $(".change").show();
                loading = false;
            });
        }, 1000);
    },
    stopBack: function () {
        var that = this,timeNum=2;
        if (window.history && window.history.pushState) {
            $(window).on('popstate', function () {
                that.backNum++;
                window.history.pushState('forward', null, document.URL);
                window.history.forward(1);
                // if(Number(that.backNum)==timeNum) {
                //     that.down();
                // }
            });
        }
        window.history.pushState('forward', null, document.URL);
        window.history.forward(1);
    },
    goTopAnimate:function(acceleration, time) {
        var that = this;
        acceleration = acceleration || 0.1;
        time = time || 100;
        var x1 = 0,y1 = 0,x2 = 0,y2 = 0,x3 = 0,y3 = 0;
        if (document.documentElement) {
            x1 = document.documentElement.scrollLeft || 0;
            y1 = document.documentElement.scrollTop || 0;
        }
        if (document.body) {
            x2 = document.body.scrollLeft || 0;
            y2 = document.body.scrollTop || 0;
        }
        x3 = window.scrollX || 0;
        y3 = window.scrollY || 0;
        var x = Math.max(x1, Math.max(x2, x3));
        var y = Math.max(y1, Math.max(y2, y3));
        var speed = 1 + acceleration;
        window.scrollTo(Math.floor(x / speed), Math.floor(y / speed));
        if (x > 0 || y > 0) {
            var invokeFunction = that.goTopAnimate(" + acceleration + ", " + time + ");
            window.setTimeout(invokeFunction, time);
        }
    },

    //返回顶部
    goTop:function(){
        var that = this,
            $go =  $('.go_top'),
            $header = $('.header').height(),
            $nav = $('.nav'),
            $h = $header + $('.header_swiper').height()-$nav.height();
        $(window).scroll(function(){
            //返回顶部
            $(this).scrollTop() > 50 ? $go.show() : $go.hide();
            //首页导航固定
            $(this).scrollTop() > $h ? $nav.addClass('m_fixed').removeClass('mt10') : $nav.removeClass('m_fixed').addClass('mt10');
        });
        $go.click(function(){
            that.goTopAnimate();
        });
    },
    randomNum: function (max, num) {
        var randoms = [];
        while (true) {
            var isExists = false;
            var random = parseInt(0 + max * (Math.random()));
            for (var i = 0; i < randoms.length; i++) {
                if (random === randoms[i]) {
                    isExists = true;
                    break;
                }
            }
            if (!isExists) randoms.push(random);
            if (randoms.length === num) break;
        }
        return randoms;
    },
    ajaxGet: function(e, n, a, i) {
        var t = null;
        try {
            t = new XMLHttpRequest
        } catch (e) {
            try {
                t = new ActiveXObject("Msxml2.XMLHTTP")
            } catch (e) {
                try {
                    t = new ActiveXObject("Microsoft.XMLHTTP")
                } catch (t) {
                }
            }
        }
        t ? (t.open("GET", e, !0),
            t.callback = a,
            t.withCredentials = !!i,
            t.onreadystatechange = function () {
                // console.log("readyState: " + t.readyState + " status: " + t.status + " statusText: " + t.statusText),
                4 == t.readyState && 200 == t.status && t.callback()
            }
            ,
            t.send(n)) : console.log("XMLHttpRequest Not Support.")
    },
    getImgData: function(n) {
        var t = n.split("#"),q = t.length > 1 ? "data:image/" + t[1] + ";base64," + t[0] : n;
        return q;
    },
    getImgSrc: function() {
        var _this = this;
        $('img').each(function(){
            var obj = this;
            if($(obj).attr('data-original')) {
                _this.ajaxGet($(obj).attr('data-original'),null,function(){
                    var src = _this.getImgData(this.responseText);
                    $(obj).attr('data-original',src);
                    _this.lazyLoad();
                });
            }else if($(obj).is('.editSrc')) {
                _this.ajaxGet($(obj).attr('src'),null,function(){
                    var src = _this.getImgData(this.responseText);
                    $(obj).attr('src',src);
                });
            }
        });
        _this.ajaxGet(_this.bg,null,function(){
            _this.bg = _this.getImgData(this.responseText);
            $('.homeBg').css('background-image','url("'+_this.bg+'")');
        });
    }

}

$(function(){
    livePage.init();
})