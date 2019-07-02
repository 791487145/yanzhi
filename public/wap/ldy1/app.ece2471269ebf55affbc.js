webpackJsonp([10], {
	"+KzP": function(n, e) {},
	"1PnO": function(n, e) {},
	NHnr: function(n, e, t) {
		"use strict";
		Object.defineProperty(e, "__esModule", {
			value: !0
		});
		var o = t("7+uW"),
			i = t("mtWM"),
			a = t.n(i),
			c = {
				name: "App",
				created: function() {
					var n = new RegExp("(^|&)invite=([^&]*)(&|$)"),
						e = window.location.search.substr(1).match(n),
						t = e ? unescape(e[2]) : "",
						o = new RegExp("(^|&)bpId=([^&]*)(&|$)"),
						i = window.location.search.substr(1).match(o),
						c = i ? unescape(i[2]) : "",
						r = new RegExp("(^|&)t=([^&]*)(&|$)"),
						u = window.location.search.substr(1).match(r),
						p = "",
						s = navigator.userAgent,
						l = s.indexOf("Android") > -1 || s.indexOf("Adr") > -1,
						h = "";
					(h = !!s.match(/\(i[^;]+;( U;)? CPU.+Mac OS X/) ? 2 : l ? 1 : 3, u) ? p = "t=" + unescape(u[2]) + "&p=" + h + "&invite=" + t + "&bpId=" + c + "&ts=" + (new Date).getTime(): p = "t=y_yiyue_1&p=" + h + "&invite=" + t + "&bpId=" + c + "&ts=" + (new Date).getTime();
					a.a.get("/api/promoter/set?" + p).then(function(n) {
						console.log("请求成功")
					}).catch(function(n) {
						console.log("请求失败")
					})
				}
			},
			r = {
				render: function() {
					var n = this.$createElement,
						e = this._self._c || n;
					return e("div", {
						attrs: {
							id: "app"
						}
					}, [e("router-view")], 1)
				},
				staticRenderFns: []
			};
		var u = t("VU/8")(c, r, !1, function(n) {
				t("1PnO")
			}, null, null).exports,
			p = t("/ocq");
		o.a.use(p.a);
		var s = new p.a({
			mode: "history",
			routes: [{
				path: "/download",
				name: "download",
				component: function(n) {
					return t.e(8).then(function() {
						var e = [t("7RVW")];
						n.apply(null, e)
					}.bind(this)).catch(t.oe)
				}
			}, {
				path: "/download-2",
				name: "download-2",
				component: function(n) {
					return Promise.all([t.e(0), t.e(7)]).then(function() {
						var e = [t("N8F2")];
						n.apply(null, e)
					}.bind(this)).catch(t.oe)
				}
			}, {
				path: "/site-one",
				name: "site-one",
				component: function(n) {
					return t.e(2).then(function() {
						var e = [t("3x/I")];
						n.apply(null, e)
					}.bind(this)).catch(t.oe)
				}
			}, {
				path: "/site-two",
				name: "site-two",
				component: function(n) {
					return t.e(1).then(function() {
						var e = [t("uNqz")];
						n.apply(null, e)
					}.bind(this)).catch(t.oe)
				}
			}, {
				path: "/site-three",
				name: "site-three",
				component: function(n) {
					return Promise.all([t.e(0), t.e(5)]).then(function() {
						var e = [t("WsLA")];
						n.apply(null, e)
					}.bind(this)).catch(t.oe)
				}
			}, {
				path: "/site-four",
				name: "site-four",
				component: function(n) {
					return Promise.all([t.e(0), t.e(6)]).then(function() {
						var e = [t("60Eh")];
						n.apply(null, e)
					}.bind(this)).catch(t.oe)
				}
			}, {
				path: "/site-five",
				name: "site-five",
				component: function(n) {
					return Promise.all([t.e(0), t.e(4)]).then(function() {
						var e = [t("5aYu")];
						n.apply(null, e)
					}.bind(this)).catch(t.oe)
				}
			}, {
				path: "/site-six",
				name: "site-six",
				component: function(n) {
					return Promise.all([t.e(0), t.e(3)]).then(function() {
						var e = [t("m62w")];
						n.apply(null, e)
					}.bind(this)).catch(t.oe)
				}
			}]
		});
		t("+KzP");
		o.a.config.productionTip = !1, o.a.directive("title", {
			inserted: function(n, e) {
				document.title = n.innerText
			}
		}), new o.a({
			el: "#app",
			router: s,
			components: {
				App: u
			},
			template: "<App/>"
		})
	}
}, ["NHnr"]);
//# sourceMappingURL=app.ece2471269ebf55affbc.js.map