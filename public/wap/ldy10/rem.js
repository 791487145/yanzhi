(function(doc, win) { ///////////页面自适应尺寸控制，基于rem,根据屏幕不同的宽度对html的font-size大小进行换算设置
	var docEl = doc.documentElement,
		resizeEvt = 'orientationchange' in window ? 'orientationchange' : 'resize',
		recalc = function() {
			var clientWidth = docEl.clientWidth;
			if(!clientWidth) return;
			/////////考虑chrome对font-size大小的限制，元素的rem尺寸=效果图(750px宽标准)中元素的 px尺寸/40 
			docEl.style.cssText = 'font-size:' + 20 * (clientWidth / 375) + 'px !important';

		};
	if(!doc.addEventListener) return;
	win.addEventListener(resizeEvt, recalc, false);
	doc.addEventListener('DOMContentLoaded', recalc, false);
})(document, window);