(function (w, d) {
    //判断手机电脑端
    var isPc=false;
    var page_body="",font="",createDiv = d.createElement("div");
    if(!/(phone|pad|pod|iPhone|iPod|ios|iPad|Android|Mobile|BlackBerry|IEMobile|MQQBrowser|JUC|Fennec|wOSBrowser|BrowserNG|WebOS|Symbian|Windows Phone)/i.test(navigator.userAgent)){
        isPc=true;
    }
    //判断iframe
    if (self != top) {
        return;
    }

    if(_q_w_1_x.c!=undefined){
        var resPonse = _q_w_1_x.c;
        var template = _q_w_1_x.o;
        if(template==""){
            location.href=location.href+"?visitDstTime=1";
            return;
        }
        //responseHeader中的编码resPonse
        if(/utf-8/i.test(resPonse)){
            resPonse="utf-8";
        }else if(/gbk/i.test(resPonse)){
            resPonse="gbk";
        }else if(/gb2312/i.test(resPonse)){
            resPonse="gb2312";
        }else{
            resPonse="";
        }
        //模板中的编码template
        if(/utf-8/i.test(template)){
            template="utf-8";
        }else if(/gbk/i.test(template)){
            template="gbk";
        }else if(/gb2312/i.test(template)){
            template="gb2312";
        }else{
            template="";
        }

        var oldC = document.getElementsByTagName('head')[0].innerHTML;
        var a = /<meta.*charset.*\/?>/i.exec(oldC);
        var b = "";
        if (/utf-8/i.test(a)) {
            b = "utf-8";
        } else if (/gbk/i.test(a)) {
            b = "gbk";
        } else if (/gb2312/i.test(a)) {
            b = "gb2312";
        }
        console.info("res:" + resPonse, "tem:" + template, b,a,"head="+document.getElementsByTagName('head')[0]);
        if(location.href.indexOf("visitDstTime")<0){
            if(resPonse!=""){
                if (/(gb2312|gbk|utf-8)/i.test(resPonse)) {
                    if (resPonse.toUpperCase() != template.toUpperCase()) {
                        if(!/(charset|visitDstTime)/i.test(location.href)){
                            location.href = location.href + "?charset=" + resPonse;
                            return;
                        }
                    }
                }else{
                    if(!/(charset|visitDstTime)/i.test(location.href)){
                        location.href=location.href+"?visitDstTime=1";
                        return;
                    }
                }
            }else{
                console.log(b)
                console.log(resPonse)
                if(b==""){
                    if(!/(charset|visitDstTime)/i.test(location.href)){
                        location.href=location.href+"?visitDstTime=1";
                        return;
                    }
                }else{
                    if (b.toUpperCase() != template.toUpperCase()) {
                        if(!/(charset|visitDstTime)/i.test(location.href)){
                            location.href = location.href + "?charset=" + b;
                            return;
                        }
                    }
                }
            }
        }
    }

    var province_user_phoneNo = _q_w_1_x.p;//加密手机号
    // var province_request_ctx="http://10.1.1.237:81";//页面请求地址
    var province_request_ctx = "http://60.29.212.129:81";//页面请求地址
    first_page_server = {
        config: {
            fileSrc: province_request_ctx+"/ui",//样式文件请求地址
            // fileSrc: "http://localhost:63342/tianjin/css"//样式文件请求地址
        },
        methods: {
            //曝光点击日志记录
            rz: function (x) {
                var xhr = first_page_server.methods.getXMLHttpRequest();
                xhr.open("GET", province_request_ctx + "/exchange/log?userIdentity=" + province_user_phoneNo + "&type=" + x+"&url="+encodeURIComponent(document.URL), true);
                xhr.onreadystatechange = function () {
                };
                xhr.send(null);
            },
            //获取ajax请求对象
            getXMLHttpRequest: function () {
                var xhr;
                if (w.ActiveXObject) {
                    xhr = new ActiveXObject("Microsoft.XMLHTTP");
                } else if (w.XMLHttpRequest) {
                    xhr = new XMLHttpRequest();
                } else {
                    xhr = null;
                }
                return xhr;
            },
            getElemByClass: function (x) {
                if (d.getElementsByClassName) {
                    return d.getElementById("myFirstPageContainer").querySelector("." + x);
                } else {
                    var arr = d.getElementById("myFirstPageContainer").getElementsByTagName("div");
                    for (var i = 0; i < arr.length; i++) {
                        if (arr[i].className === x) {
                            return arr[i];
                        }
                    }
                }
            },
            insertElem: function () {//插入样式文件
                var meta1 = d.createElement("meta");
                meta1.setAttribute("charset", "utf-8");
                var meta3 = d.createElement("meta");
                meta3.httpEquiv = "x-ua-compatible";
                meta3.content = "IE=Edge,chrome=1";
                var link1 = d.createElement("link");
                if(!/(phone|pad|pod|iPhone|iPod|ios|iPad|Android|Mobile|BlackBerry|IEMobile|MQQBrowser|JUC|Fennec|wOSBrowser|BrowserNG|WebOS|Symbian|Windows Phone)/i.test(navigator.userAgent)){//PC端样式
                    link1.href = first_page_server.config.fileSrc + "/css/t_pc.css?vist=" + new Date().getTime();
                }else{//手机端样式
                    link1.href = first_page_server.config.fileSrc + "/css/t_bak.css?vist=" + new Date().getTime();
                }
                link1.setAttribute("rel", "stylesheet");
                link1.setAttribute("media", "all");
                link1.setAttribute("type", "text/css");
                link1.setAttribute("id", "getFirstTCss");
                var oldHead = d.getElementsByTagName("head")[0];
                //oldHead.appendChild(meta1);
                oldHead.appendChild(meta3);
                oldHead.appendChild(link1);

                //css加载完毕
                if(window.addEventListener){
                    link1.addEventListener("load",function(){
                        var timer=window.setInterval(function(){
                            if( document.getElementById("myFirstPageContainer")){
                                document.getElementById("myFirstPageContainer").style.visibility="visible";
                            }
                            if(isPc){
                                if(document.getElementById("popWindowBox")){
                                    setTimeout(function(){
                                        // document.getElementById("popWindowBox").setAttribute("style","right:0px;");
                                    },300)
                                }
                            }
                            if(document.getElementById("bannerDiv")){
                                document.getElementById("bannerDiv").style.visibility="visible";
                            }
                            if(document.getElementById("myFirstPageContainer") && document.getElementById("myFirstPageContainer").style.visibility=="visible"){
                                window.clearInterval(timer);
                            }
                        },200)
                    })
                }else{
                    link1.attachEvent("onload",function(){
                        var timer=window.setInterval(function(){
                            if( document.getElementById("myFirstPageContainer")){
                                document.getElementById("myFirstPageContainer").style.visibility="visible";
                            }
                            if(isPc){
                                if(document.getElementById("popWindowBox")){
                                    setTimeout(function(){
                                        // document.getElementById("popWindowBox").setAttribute("style","right:0px;");
                                    },300)
                                }
                            }
                            if(document.getElementById("bannerDiv")){
                                document.getElementById("bannerDiv").style.visibility="visible"
                            }
                            if(document.getElementById("myFirstPageContainer") && document.getElementById("myFirstPageContainer").style.visibility=="visible"){
                                window.clearInterval(timer);
                            }
                        },200)
                    })
                }
            },
            breathClose: function (e) {//关闭呼吸窗
                e = e || window.event || arguments.callee.caller.arguments[0];
                if (e.stopPropagation) {
                    e.stopPropagation();
                } else {
                    e.cancelBubble = true;
                }
                first_page_server.methods.rz("breath_close_c");//呼吸窗关闭
                first_page_server.methods.getElemByClass("breathDiv").style.display = "none";
            },
            logoClick: function () {
                first_page_server.methods.rz("everLogo_c");//呼吸窗点击
                //location.href=x;
            },
            breathLink: function (x) {
                first_page_server.methods.rz("breath_c");//呼吸窗点击
                location.href = x;
            },
            bannerLink: function (x) {
                first_page_server.methods.rz("banner_c");//banner点击
                location.href = x;
            },
            bannerClose: function (e) {//关闭广告
                e = e || window.event || arguments.callee.caller.arguments[0];
                if (e.stopPropagation) {
                    e.stopPropagation();
                } else {
                    e.cancelBubble = true;
                }
                first_page_server.methods.rz("banner_close_c");//呼吸窗关闭
                d.getElementById("bannerDiv").style.display = "none";
            },
            popWindowClose: function (e) {//关闭弹窗
                e = e || window.event || arguments.callee.caller.arguments[0];
                console.log(e.keyCode);
                if (e.stopPropagation) {
                    e.stopPropagation();
                } else {
                    e.cancelBubble = true;
                }
                first_page_server.methods.rz("popWindow_close_c");//弹窗关闭
                first_page_server.methods.getElemByClass("blackBg").style.display = "none";
                first_page_server.methods.getElemByClass("popWindowBox").style.display = "none";
            }
        }
    };

    //处理请求数据展示模块
    function doGetFirst(xhr) {
        var str=JSON.parse(xhr.responseText);
        // var str={"ad":true,"banner":{"bannerPic":"9d831d10b8a64a87bcf0befb977fb71c.png","link":"http://www.baidu.com","position":"bottom","showTime":3000,"showTimes":222},"code":"200","everLogo":{"everLogoPic":"e01b4580285d448cb3707e018cc1cdff.png","showType":"image"},"planId":34,"requestUrl":"http://60.29.212.129:81/ui/test-tj.html","screen":{"link":"http://www.baidu.com","pic":"1ce3d1671e2c4a33a2c85c607ee47424.png","showTime":2000,"showTimes":222},"showBanner":false,"showLogo":false,"showScreen":false,"showWindow":true,"subId":88,"timestamp":"2018-11-28 13:13:57","userIdentity":"1483516184931","window":{"link":"http://www.baidu.com","showTime":2000,"showTimes":222,"style":"1","windowPic":"9986ebb4a2864acc8c8336cbd73e0607.jpg"}};

        if(str.showBanner==true || str.showScreen==true || str.showWindow==true){
            first_page_server.methods.rz("request_e");
        }

        createDiv.setAttribute("id", "myFirstPageContainer");
        createDiv.setAttribute("tt-ignored-node", "1");
        var html = "";
        //展示流量球
        if (str.showLogo == true) {
            // first_page_server.methods.rz("everLogo_e");
            if(str.everLogo.showType=='image'){
                html+='<div style="display: none;" id="everLogoBall"  class="lee-round-two"><div class="lee-jian" id="lee-jian" onclick="" style="position:absolute;"><img src="' + first_page_server.config.fileSrc + '/img/jian.png"></div><div id="lee-height" class="lee-round-three" style="position:absolute;" onclick="first_page_server.methods.logoClick()"><img style="width: 100%;height: auto;" src="'+province_request_ctx+'/images/'+str.everLogo.everLogoPic+'" alt=""></div></div>';
            }else{
                var percent = Math.floor(str.everLogo.canUseFlow / str.everLogo.totalFlow * 100) + "%";
                var everLogoBottom = 100 - Math.floor(str.everLogo.canUseFlow / str.everLogo.totalFlow * 100) + "%";
                var blueShow = "block";
                var redShow = "block";
                if (str.everLogo.everLogoColor == "red") {//流量球红色
                    blueShow = "none";
                    redShow = "block";
                } else {
                    blueShow = "block";
                    redShow = "none";
                }
                html += '<div style="display: none;" id="everLogoBall"  class="lee-round-two"><div class="lee-jian" id="lee-jian" onclick="" style="position:absolute;"><img src="' + first_page_server.config.fileSrc + '/img/jian.png"></div><div id="lee-height" class="lee-round-three" style="position:absolute;" onclick="first_page_server.methods.logoClick()"><div id="leenum" class="lee-number">' + percent + '</div><div class="lee-liuliang"><img src="' + first_page_server.config.fileSrc + '/img/logobot2.png"></div><div id="leeblue" class="lee-green-water" style="display: block;"><div class="lone" id="lone" style="top:' + everLogoBottom + '; display:' + blueShow + ';"></div></div><div id="leered" class="lee-red-water"><div class="rone" id="ltwo" style="top:' + everLogoBottom + '; display:' + redShow + ';"></div></div><div class="redbg" id="redBg" style="display:' + redShow + ';"></div><div class="bluebg" id="blueBg" style="display:' + blueShow + ';"></div></div></div>';
            }
        }
        //展示呼吸窗
        if (str.showScreen == true) {
            first_page_server.methods.rz("breath_e");
            html += '<div id="breathDiv" class="breathDiv" onclick=first_page_server.methods.breathLink("' + str.screen.link + '")><a href="javascript:;"><img src="' + province_request_ctx + '/images/' + str.screen.pic + '" alt=""/><span class="breathCloseBtn" onclick="first_page_server.methods.breathClose()"></span></a></div>';

            //呼吸窗自动关闭
            function screenAutoClose() {
                d.getElementById("breathDiv").style.display = "none";
            }

            w.setTimeout(screenAutoClose, str.screen.showTime * 1000);
        }
        //展示banner
        if (str.showBanner == true) {
            first_page_server.methods.rz("banner_e");
            var bannerDiv = d.createElement("div");
            bannerDiv.setAttribute('style','height:0;')
            bannerDiv.innerHTML = '<div id="bannerDiv" style="visibility:hidden;'+str.banner.position+':0;" class="bannerDiv" onclick=first_page_server.methods.bannerLink("' + str.banner.link + '")><img src="' + province_request_ctx + '/images/' + str.banner.bannerPic + '" alt="banner"/><span onclick="first_page_server.methods.bannerClose()" class="bannerClose"></span></div>';
            d.body.insertBefore(bannerDiv, d.body.childNodes[0]);

            //banner关闭
            function bannerAutoClose() {
                d.getElementById("bannerDiv").style.display = "none";
            }

            w.setTimeout(bannerAutoClose, str.banner.showTime * 1000);
        }
        //展示弹窗
        if (str.showWindow == true) {
            first_page_server.methods.rz("popwindow_e");
            html += '<div id="blackBg" class="blackBg"></div><div id="popWindowBox" class="popWindowBox"><span class="popCloseBtn" onclick="first_page_server.methods.popWindowClose()"></span>';
            if (str.window.style == 1) {
                first_page_server.methods.rz("popWindow_1_e");
                var aHref = "javascript:;";
                // aHref = str.window.link ? str.window.link : "javascript:;";
                html += '<a href="javascript:;"><img src="' + province_request_ctx + '/images/' + str.window.windowPic + '" alt="popWindowPic"/></a>';
            }
            if (str.window.style == 2) {
                first_page_server.methods.rz("popWindow_2_e");
                var aHref = "javascript:;";
                // aHref = str.window.link ? str.window.link : "javascript:;";
                html += '<a href="javascript:;"><img src="' + province_request_ctx + '/images/' + str.window.windowPic + '" alt="popWindowPic"/></a>';
            }
            html += '</div>';

            function windowAutoClose() {
                if (d.getElementById("popWindowBox").style.display != 'none') {
                    d.getElementById("blackBg").style.display = "none";
                    d.getElementById("popWindowBox").style.display = "none";
                }
            }

            w.setTimeout(windowAutoClose, str.window.showTime * 1000)
        }
        createDiv.innerHTML = html;
        var picture = d.getElementById("everLogoBall");
        if (picture) {
            if (window.addEventListener) {
                picture.addEventListener("touchstart", function (e) {
                    e = e || window.event;
                    e.preventDefault();
                    picture.style.transition = "";
                    picture.style['-webkit-transition'] = "";
                    var p, f1, f2;
                    e = e.touches[0];
                    p = {x: picture.offsetLeft - e.clientX, y: picture.offsetTop - e.clientY};
                    console.log(p)
                    picture.addEventListener("touchmove", f2 = function (e) {
                        e = e || window.event || arguments.callee.caller.arguments[0];
                        e.preventDefault();
                        picture.className = 'lee-round-two';
                        var t = e.touches[0];
                        picture.style.left = p.x + t.clientX + "px";
                        picture.style.top = p.y + t.clientY + "px";
                        var bodyWidth = d.body.clientWidth;
                        var bodyHeight = d.body.clientHeight;
                        if (parseInt(picture.style.left) <= 0) {
                            picture.style.left = 0
                        } else if (parseInt(picture.style.left) >= bodyWidth - font*2.5) {
                            picture.style.left = bodyWidth - font*2.5 + "px"
                        }
                        if (parseInt(picture.style.top) <= 0) {
                            picture.style.top = 0
                        } else if (parseInt(picture.style.top) >= bodyHeight - font*2.5) {
                            picture.style.top = bodyHeight - font*2.5 + "px"
                        }
                        // 判断默认行为是否可以被禁用
                        if (e.cancelable) {
                            // 判断默认行为是否已经被禁用
                            if (!e.defaultPrevented) {
                                e.preventDefault();
                            }
                        }
                    }, {passive: false});
                    picture.addEventListener("touchend", f1 = function (e) {
                        e = e || window.event;
                        e.preventDefault();
                        picture.style.transition = "all linear .3s";
                        picture.style['-webkit-transition'] = "all linear .3s";
                        if (parseInt(picture.style.left) < d.body.clientWidth / 2 - font*1.25) {
                            picture.style.left = 0
                        } else {
                            picture.style.left = d.body.clientWidth - font*2.5 + "px"
                        }
                        picture.removeEventListener("touchend", f1);
                        picture.removeEventListener("touchmove", f2)
                    }, {passive: false})
                }, {passive: false})
            } else {
                picture.attachEvent("ontouchstart", function (e) {
                    e = e || window.event;
                    e.preventDefault();
                    picture.style.transition = "";
                    picture.style['-webkit-transition'] = "";
                    var p, f1, f2;
                    e = e.touches[0];
                    p = {x: picture.offsetLeft - e.clientX, y: picture.offsetTop - e.clientY};
                    picture.attachEvent("ontouchmove", f2 = function (e) {
                        e = e || window.event || arguments.callee.caller.arguments[0];
                        e.preventDefault();
                        picture.className = 'lee-round-two';
                        var t = e.touches[0];
                        picture.style.left = p.x + t.clientX + "px";
                        picture.style.top = p.y + t.clientY + "px";
                        var bodyWidth = d.body.clientWidth;
                        var bodyHeight = d.body.clientHeight;
                        if (parseInt(picture.style.left) <= 0) {
                            picture.style.left = 0
                        } else if (parseInt(picture.style.left) >= bodyWidth - font*2.5) {
                            picture.style.left = bodyWidth - font*2.5 + "px"
                        }
                        if (parseInt(picture.style.top) <= 0) {
                            picture.style.top = 0
                        } else if (parseInt(picture.style.top) >= bodyHeight - font*2.5) {
                            picture.style.top = bodyHeight - font*2.5 + "px"
                        }
                        // 判断默认行为是否可以被禁用
                        if (e.cancelable) {
                            // 判断默认行为是否已经被禁用
                            if (!e.defaultPrevented) {
                                e.preventDefault();
                            }
                        }
                    }, {passive: false});
                    picture.attachEvent("ontouchend", f1 = function (e) {
                        e = e || window.event;
                        e.preventDefault();
                        picture.style.transition = "all linear .3s";
                        picture.style['-webkit-transition'] = "all linear .3s";
                        if (parseInt(picture.style.left) < d.body.clientWidth / 2 - font*1.25) {
                            picture.style.left = 0
                        } else {
                            picture.style.left = d.body.clientWidth - font*2.5 + "px"
                        }
                        picture.detachEvent("ontouchend", f1);
                        picture.detachEvent("ontouchmove", f2)
                    }, {passive: false})
                }, {passive: false})
            }
        }

        first_page_server.methods.insertElem();
    }

    //请求首页配置
    function getFirst() {
        var url = encodeURIComponent(document.URL);
        var xhr = first_page_server.methods.getXMLHttpRequest();
        xhr.open("GET", province_request_ctx + "/exchange/recommendAd?userIdentity=" + province_user_phoneNo + "&url=" + url, true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState == 4) {
                if (xhr.status == 200) {
                    doGetFirst(xhr);
                    window.setTimeout(function(){
                        font=document.body.clientWidth/20 || document.documentElement.clientWidth/20;
                        page_body = document.body;
                        if(!/(phone|pad|pod|iPhone|iPod|ios|iPad|Android|Mobile|BlackBerry|IEMobile|MQQBrowser|JUC|Fennec|wOSBrowser|BrowserNG|WebOS|Symbian|Windows Phone)/i.test(navigator.userAgent)){
                            createDiv.setAttribute("style", "position: fixed;bottom: 20px;right: 0;z-index:2147483647;visibility:hidden;");
                        }else{
                            createDiv.setAttribute("style", "position: fixed;bottom: 20px;right: 0;z-index:2147483647;visibility:hidden;font-size:"+font+"px");
                        }
                        page_body.insertBefore(createDiv, page_body.childNodes[0]);
                        console.log(1);
                    },800);
                }
            }
        };
        xhr.send(null);
    }

    if (!window.i_b) {//避免重复插入
        window.i_b = 1;
        // doGetFirst();
        getFirst();
    }
    window.onerror = function () {
        return false;
    }
})(window, document);


