webpackJsonp([3],{TPBf:function(s,i){},"k/7T":function(s,i,e){"use strict";i.a={open:function(){return function(){var s=navigator.userAgent,i=(s.indexOf("Android")>-1||s.indexOf("Adr"),!!s.match(/\(i[^;]+;( U;)? CPU.+Mac OS X/)),e=window.navigator.userAgent.toLowerCase();if(i)window.location.href="https://itunes.apple.com/cn/app/id1443980090";else{if("micromessenger"==e.match(/MicroMessenger/i)||" qq"==e.match(/ qq/i))return"showTips";window.location.href="https://img.siyewenhua.com/apk/yuehui-guanwang.apk"}}()}}},m62w:function(s,i,e){"use strict";Object.defineProperty(i,"__esModule",{value:!0});var t=e("k/7T"),a={data:function(){return{listLeft:[{bgImg:"https://img.siyewenhua.com/landing-page/images/site-two/user-img/1.png",userName:"孤独的路人",level:4,stateImg:"https://img.siyewenhua.com/landing-page/images/location.png",stateText:"100米以内",isPosition:!0,isVideo:!1},{bgImg:"https://img.siyewenhua.com/landing-page/images/site-two/user-img/3.png",userName:"执念girl",level:2,stateImg:"https://img.siyewenhua.com/landing-page/images/online.png",stateText:"在线",isPosition:!1,isVideo:!0},{bgImg:"https://img.siyewenhua.com/landing-page/images/site-two/user-img/5.png",userName:"茉莉花儿",level:1,stateImg:"https://img.siyewenhua.com/landing-page/images/location.png",stateText:"200米以内",isPosition:!0,isVideo:!1},{bgImg:"https://img.siyewenhua.com/landing-page/images/site-two/user-img/7.png",userName:"Mgicgirl",level:2,stateImg:"https://img.siyewenhua.com/landing-page/images/location.png",stateText:"150米以内",isPosition:!0,isVideo:!1},{bgImg:"https://img.siyewenhua.com/landing-page/images/site-two/user-img/9.png",userName:"快乐小可爱",level:3,stateImg:"https://img.siyewenhua.com/landing-page/images/off.png",stateText:"2分钟前",isPosition:!1,isVideo:!0},{bgImg:"https://img.siyewenhua.com/landing-page/images/site-two/user-img/11.png",userName:"崔艾",level:2,stateImg:"https://img.siyewenhua.com/landing-page/images/location.png",stateText:"300米以内",isPosition:!0,isVideo:!1}],listRight:[{bgImg:"https://img.siyewenhua.com/landing-page/images/site-two/user-img/2.png",userName:"小明明",level:1,stateImg:"https://img.siyewenhua.com/landing-page/images/online.png",stateText:"在线",isPosition:!1,isVideo:!0},{bgImg:"https://img.siyewenhua.com/landing-page/images/site-two/user-img/4.png",userName:"伊尔倩",level:4,stateImg:"https://img.siyewenhua.com/landing-page/images/off.png",stateText:"2分钟前",isPosition:!1,isVideo:!1},{bgImg:"https://img.siyewenhua.com/landing-page/images/site-two/user-img/6.png",userName:"弯弯齐宁",level:3,stateImg:"https://img.siyewenhua.com/landing-page/images/online.png",stateText:"在线",isPosition:!1,isVideo:!0},{bgImg:"https://img.siyewenhua.com/landing-page/images/site-two/user-img/8.png",userName:"糖朵",level:5,stateImg:"https://img.siyewenhua.com/landing-page/images/location.png",stateText:"250米以内",isPosition:!0,isVideo:!0},{bgImg:"https://img.siyewenhua.com/landing-page/images/site-two/user-img/10.png",userName:"lala海奇",level:2,stateImg:"https://img.siyewenhua.com/landing-page/images/location.png",stateText:"100米以内",isPosition:!0,isVideo:!1},{bgImg:"https://img.siyewenhua.com/landing-page/images/site-two/user-img/12.png",userName:"明媚阳光",level:3,stateImg:"https://img.siyewenhua.com/landing-page/images/location.png",stateText:"450米以内",isPosition:!0,isVideo:!0}],playVideo:!1,isTips:!1}},methods:{appDwonload:function(){"showTips"===t.a.open()&&(this.isTips=!0)},requestFullScreen:function(){this.$refs.video.mozRequestFullScreen?this.$refs.video.mozRequestFullScreen():this.$refs.video.msRequestFullScreen?this.$refs.video.msRequestFullScreen():this.$refs.video.webkitRequestFullScreen?this.$refs.video.webkitRequestFullScreen():this.$refs.video.oRequestFullscreen?this.$refs.video.oRequestFullscreen():this.$refs.video.requestFullScreen()},stopVideo:function(){this.$refs.video.pause()}},mounted:function(){this.$nextTick(function(){this.$refs.video.addEventListener("play",this.requestFullScreen)})}},n={render:function(){var s=this,i=s.$createElement,t=s._self._c||i;return t("div",{staticClass:"site-two"},[t("div",{directives:[{name:"title",rawName:"v-title"}],staticStyle:{display:"none"}},[s._v("玫瑰约会聊天一对一视频")]),s._v(" "),t("div",{directives:[{name:"show",rawName:"v-show",value:s.isTips,expression:"isTips"}],staticClass:"tips-dialog",on:{click:function(i){s.isTips=!1}}},[t("img",{staticClass:"tips-img",attrs:{src:e("1Ebi"),alt:""}})]),s._v(" "),t("div",{staticClass:"suspension"},[t("img",{staticClass:"suspension-img",attrs:{src:"https://img.siyewenhua.com/sucai/meigui_logo1.png",alt:""}}),s._v(" "),t("button",{staticClass:"suspension-btn",on:{click:s.appDwonload}},[s._v("下载APP")])]),s._v(" "),t("div",{staticClass:"video-box"},[t("video",{ref:"video",staticClass:"video",attrs:{controls:"",loop:"",poster:"https://img.siyewenhua.com/landing-page/images/site-two/cover.jpg"}},[t("source",{attrs:{src:"https://img.siyewenhua.com/landing-page/images/site-two/video.mp4",type:"video/mp4"}})])]),s._v(" "),s._m(0),s._v(" "),t("div",{staticClass:"photo-wall"},[s._m(1),s._v(" "),t("div",{staticClass:"photo",on:{click:s.appDwonload}},[t("ul",{staticClass:"list"},s._l(s.listLeft,function(i){return t("li",{key:i.id,staticClass:"item"},[t("div",{staticClass:"item-position"},[t("img",{staticClass:"item-bgImg",attrs:{src:i.bgImg,alt:""}}),s._v(" "),t("div",{staticClass:"li-box"},[t("div",{staticClass:"info-item"},[t("p",{staticClass:"info-name"},[s._v(s._s(i.userName))]),s._v(" "),t("div",{staticClass:"info-grade"},[t("img",{staticClass:"grade-icon",attrs:{src:"https://img.siyewenhua.com/landing-page/images/charm@2x.png",alt:""}}),s._v(" "),t("span",{staticClass:"grade-level"},[s._v(s._s(i.level))])])]),s._v(" "),t("div",{staticClass:"info-item info-bottom"},[t("img",{class:[i.isPosition?"position-img":"online-img"],attrs:{src:i.stateImg,alt:""}}),s._v(" "),t("span",{staticClass:"bottom-state"},[s._v(s._s(i.stateText))])])]),s._v(" "),t("img",{directives:[{name:"show",rawName:"v-show",value:i.isVideo,expression:"item.isVideo"}],staticClass:"video-play",attrs:{src:"https://img.siyewenhua.com/landing-page/images/site-two/video.png",alt:""}})])])}),0),s._v(" "),t("ul",{staticClass:"list"},s._l(s.listRight,function(i){return t("li",{key:i.id,staticClass:"item"},[t("div",{staticClass:"item-position"},[t("img",{staticClass:"item-bgImg",attrs:{src:i.bgImg,alt:""}}),s._v(" "),t("div",{staticClass:"li-box"},[t("div",{staticClass:"info-item"},[t("p",{staticClass:"info-name"},[s._v(s._s(i.userName))]),s._v(" "),t("div",{staticClass:"info-grade"},[t("img",{staticClass:"grade-icon",attrs:{src:"https://img.siyewenhua.com/landing-page/images/charm@2x.png",alt:""}}),s._v(" "),t("span",{staticClass:"grade-level"},[s._v(s._s(i.level))])])]),s._v(" "),t("div",{staticClass:"info-item info-bottom"},[t("img",{class:[i.isPosition?"position-img":"online-img"],attrs:{src:i.stateImg,alt:""}}),s._v(" "),t("span",{staticClass:"bottom-state"},[s._v(s._s(i.stateText))])])]),s._v(" "),t("img",{directives:[{name:"show",rawName:"v-show",value:i.isVideo,expression:"item.isVideo"}],staticClass:"video-play",attrs:{src:"https://img.siyewenhua.com/landing-page/images/site-two/video.png",alt:""}})])])}),0)])]),s._v(" "),t("div",{staticClass:"footer"},[s._v("- 与附近的人先视频，再约会 -")])])},staticRenderFns:[function(){var s=this,i=s.$createElement,e=s._self._c||i;return e("div",{staticClass:"dynamic"},[e("div",{staticClass:"dynamic-top"},[e("img",{staticClass:"dynamic-user-img",attrs:{src:"https://img.siyewenhua.com/landing-page/images/site-two/header_img.png",alt:""}}),s._v(" "),e("div",{staticClass:"dynamic-info"},[e("div",{staticClass:"info-item"},[e("span",{staticClass:"user-name"},[s._v("小玲珑")]),s._v(" "),e("img",{staticClass:"user-state-img",attrs:{src:"https://img.siyewenhua.com/landing-page/images/site-two/online.png",alt:""}})]),s._v(" "),e("div",{staticClass:"info-item info-bottom"},[e("img",{staticClass:"position-img",attrs:{src:"https://img.siyewenhua.com/landing-page/images/location_2.png",alt:""}}),s._v(" "),e("span",{staticClass:"distance"},[s._v("1.2km")])])])]),s._v(" "),e("p",{staticClass:"dynamic-mag"},[s._v("过来会不会遇到有眼缘的，最好也住在附近的，有空出去玩也方便不是吗......")]),s._v(" "),e("div",{staticClass:"dynamic-like"},[e("span",[s._v("发表于12分钟前")]),e("span",[s._v("362次喜欢")])])])},function(){var s=this.$createElement,i=this._self._c||s;return i("div",{staticClass:"title"},[i("span"),this._v(" "),i("p",[this._v("更多推荐")]),this._v(" "),i("span")])}]};var o=e("VU/8")(a,n,!1,function(s){e("TPBf")},"data-v-449ecaa1",null);i.default=o.exports}});
//# sourceMappingURL=3.29645637336d5e5499a4.js.map