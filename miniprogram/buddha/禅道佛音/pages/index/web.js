var $ = require('../../common/helper.js');
Page({
	data: {
		url: '',
		payParam: null
	},
	options: null,
	onLoad: function (options) {
		if (options.reseller) {
			getApp().globalData.reseller = options.reseller;
			delete options.reseller;
		}
		this.options = options;
		let _this = this,
			url = options.url ? decodeURIComponent(options.url) : '',
			payParam = options.payParam ? JSON.parse(decodeURIComponent(options.payParam)) : null;
		this.setData({
			url: url,
			payParam: payParam
		});
		if (payParam) {
			setTimeout(function(){
				wx.requestPayment({
					appId: payParam.appId,
					timeStamp: payParam.timeStamp,
					nonceStr: payParam.nonceStr,
					package: payParam.package,
					signType: payParam.signType,
					paySign: payParam.paySign,
					success: function (res) {
						$.overloadSuccess('支付成功');
						setTimeout(function(){
							wx.redirectTo({
								url: payParam.url ? payParam.url : '/pages/member/index'
							});
						}, 2000);
					},
					fail: function (res) {
						$.overloadError('支付失败');
					}
				});
			}, 500);
		}
	},
	onShareAppMessage: function (res) {
		if (this.options.payParam) return false;
		let person = $.storage('person'),
			querystring = person ? '?reseller=' + person.id : '';
		if (this.options) {
			$.each(this.options, function (key) {
				if (key != 'reseller') {
					querystring += (querystring.length ? '&' : '?') + key + '=' + this;
				}
			});
		}
		return {
			title: $.config.shareTitle,
			imageUrl: $.config.shareImageUrl,
			path: this.route + querystring
		}
	}
})