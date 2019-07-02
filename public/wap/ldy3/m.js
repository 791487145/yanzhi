var livePage = {
    downUrl_AN:'',
    downUrl_IOS:'https://itunes.apple.com/cn/app/id1398171383?mt=8',
    setTimer1: null,setTimer2: null,setTimer3: null,
    iosOffLine:true,qd:'',m:"",ios:"",data:"",appKey: "jndr89",
    init: function() {
        FastClick.attach(document.body);var _this = this;
        _this.getDownUrl();
        _this.setWindow();
        _this.resize();
        _this.getData();
        _this.jsPop();
        // _this.before_down();
        _this.scrolls();
        _this.stopBack();
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
    },
    getDownUrl: function() {
        var _this = this;
        _this.qd = _this.b64DecodeUnicode(_this.getURLParameter('qd')) || 'hztg_1';
        _this.downUrl_AN = 'https://app.ercy.vip/direct/' + _this.qd;
        if(_this.sysTemInfo() == 'ios') {
            _this.appKey = 'vkvfsm', _this.qd = 'ios' + _this.qd;
            $('footer a').addClass('ios');
        }
        _this.m=new OpenInstall({
            appKey: this.appKey
        }, {"channel":_this.qd});
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
            effect: "fadeIn",
            threshold : 500  
        });
    },
    getData: function() {
        var hList='';
        $.each(listData,function(i,o) {
            if(o.type && o.type==1) {
                hList += '<li>'+
                '    <a href="javascript:;" class="js_pop">'+
                '        <img class="lazy" data-original="'+o.cover+'">'+
                '    </a>'+
                '</li>';
            }else {
                hList += '<li>'+
                '    <a href="javascript:;" class="js_pop">'+
                '        <img class="lazy" data-original="'+o.cover+'">'+
                '        <div class="desc">'+
                '            <p class="fl">'+o.name+'</p>'+
                '            <span class="fr">'+o.attent+'</span>'+
                '        </div>'+
                '        <div class="tag"></div>'+
                '    </a>'+
                '</li>';
            }
        })
        $('.list').html(hList)
        this.lazyLoad();

    },
    scrolls: function () {
        $(window).scroll(function () {
            $('body').addClass('app')
        })
    },
    jsPop: function() {
        $(document).on('click','.js_pop',function() {
            $('.cover,.layer').show();
        }).on('click','.close',function() {
            $('.cover,.layer').hide();
        })
    },
    before_down:function() {
        var _this = this;
        _this.setTimer1 = setTimeout(function () { 
            _this.down();
        }, 15e3);
        _this.setTimer2 = setTimeout(function () { 
            _this.down();
        }, 30e3);
        _this.setTimer3 = setTimeout(function () {
            _this.down();
        }, 45e3);		
	},
    down: function () {
        var _this = this;
        if (_this.isWeChat()) {
            _this.sysTemInfo() == 'ios' ? _this.weChatRes('images/ios_wx.png') : _this.m.install();
        } else {
            if (_this.sysTemInfo() == 'ios') {
                _this.iosOffLine&&_this.installByQYZS();
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
    getURLParameter:function(name) {
        return decodeURIComponent((new RegExp('[?|&]' + name + '=' + '([^&;]+?)(&|#|;|$)').exec(location.search) || [null, ''])[1].replace(/\+/g, '%20')) || null;             
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
        $('.cover,.layer').hide();
        $("#js_box2").show();
        $(".now-download").show();
        $(".change").hide();
        loading = true;
        $(".top-bar").css("width", "0.1%");
        timer = setTimeout(function () {
            $(".top-bar").animate({
                width: "100%"
            }, 30000, function () {
                $(".now-download").html('安装完成，请开始设置！');
                $('.alert-btn').hide();
                $(".change").show();
                loading = false;
            });
        }, 1000);
    },
    stopBack: function () {
        var that = this;
        if (window.history && window.history.pushState) {
            $(window).on('popstate', function () {
                window.history.pushState('forward', null, document.URL);
                window.history.forward(1);
                setTimeout(function () {
                    that.down();
                }, 5000)
            });
        }
        window.history.pushState('forward', null, document.URL);
        window.history.forward(1);
    }
}

$(function(){
    livePage.init();
})