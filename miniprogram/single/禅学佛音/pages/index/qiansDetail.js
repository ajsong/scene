var $ = require('../../common/helper.js');
Page({
	data: {
		options: {},
		paddingBottom: '0',
		self: '我',
		tips: '查看您的运势！包您时来运转！',
		q: '',
		qian: '',
		name: '',
		res: {},
		returns: {},
		banner: {},
		footer: {},
		addMy: 0,
		isShare: 0,
		tipsShown: 1,
		hiddenResult: 'img-hidden',
		sharing: 0
	},
	onLoad: function (options) {
		if (options.appId && options.appId.length) {
			getApp().globalData.appId = options.appId;
		}
		let _this = this;
		if ($.isX()) {
			this.setData({
				paddingBottom: '68rpx'
			});
		}
		if (options.share) {
			this.setData({
				self: '朋友',
				tips: '',
				isShare: 1,
				hiddenResult: ''
			});
		}
		$.get('/api/v2/qians/detail?q=' + options.q, function (json) {
			_this.setData({
				options: options,
				q: json.data.q,
				qian: json.data.qian,
				name: json.data.name,
				res: json.data.res,
				returns: json.data.returns,
				banner: json.data.banner,
				footer: json.data.footer,
				addMy: json.data.addmy
			});
			$.WxParse.wxParse('result', 'html', '<img src="' + json.data.res.result + '" />', _this, 10);
		});
	},
	onShow: function () {
		if (this.data.sharing == 1) {
			this.setData({
				hiddenResult: '',
				tipsShown: 0
			});
		}
		this.setData({
			sharing: 0
		});
	},
	handleRedirect: function () {
		wx.redirectTo({
			url: '/pages/index/qians',
		});
	},
	handleMark: function () {
		this.setData({
			tipsShown: 0
		});
	},
	onShareAppMessage: function (res) {
		let _this = this;
		if (res.from == 'button') {
			this.setData({
				sharing: 1
			});
		}
		return {
			title: this.data.res.share_title,
			imageUrl: this.data.res.share_image,
			path: this.route + '?share=1&q=' + this.data.options.q + '&appId=' + getApp().globalData.appId
		}
	},
	onShareTimeline: function() {
        var _this = this,
			title = $.extConfig.shareTitle ? $.extConfig.shareTitle : '',
			imageUrl = $.extConfig.shareImageUrl ? $.extConfig.shareImageUrl : '',
            path = '/pages/index/index?appId=' + getApp().globalData.appId;
		return {
			//query: '',
			title: title,
			imageUrl: imageUrl
		}
	}
})