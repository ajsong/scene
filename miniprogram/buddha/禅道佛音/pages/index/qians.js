var $ = require('../../common/helper.js');
Page({
	data: {
		res: {},
		addMy: 0,
		backBtnMargin: '20px',
		fontSize: '28rpx',
		addMyMargin: '64px',
		audio: null,
		tipsShown: 1,
		qianClass: ''
	},
	isStarted: false,
	onLoad: function (options) {
		if (options.appId && options.appId.length) {
			getApp().globalData.appId = options.appId;
		}
		let _this = this;
		if ($.isX()) {
			this.setData({
				backBtnMargin: '44px',
				fontSize: '28rpx',
				addMyMargin: '88px'
			});
		}
		$.get('/api/v2/qians', function (json) {
			_this.setData({
				res: json.data.res,
				addMy: json.data.addmy
			});

			if (json.data.res.music.length) {
				let audio = wx.createInnerAudioContext();
				audio.src = json.data.res.music;
				_this.setData({
					audio: audio
				});
			}

			if (json.data.wxpositionad.enable == 1) {
				// 在页面中定义插屏广告
				let interstitialAd = null;
				// 在页面onLoad回调事件中创建插屏广告实例
				if (wx.createInterstitialAd) {
					interstitialAd = wx.createInterstitialAd({
						adUnitId: json.data.wxpositionad.adunit
					});
					interstitialAd.onLoad(() => { });
					interstitialAd.onError((err) => { });
					interstitialAd.onClose(() => { });
				}
				// 在适合的场景显示插屏广告
				if (interstitialAd) {
					interstitialAd.show().catch((err) => {
						console.log(err);
					});
				}
			}
		});
	},
	onShow: function () {
		let _this = this;
		if (this.data.tipsShown == 0) {
			this.handleAccelerometer();
		}
	},
	onHide: function () {
		this.setData({
			qianClass: ''
		});
	},
	onUnload: function () {
		wx.stopAccelerometer();
		wx.offAccelerometerChange();
		if (this.data.audio) {
			this.data.audio.stop();
			this.data.audio.destroy();
		}
	},
	handleAddMy: function () {
		this.setData({
			addMy: 0
		});
	},
	handleAccelerometer: function () {
		let _this = this;
		//监听距离
		wx.onAccelerometerChange(function (res) {
			if (res.x > 0.7) {
				if (wx.vibrateLong) wx.vibrateLong();
				_this.handleStart();
			}
		});
		//开启监听
		wx.startAccelerometer();
	},
	handleTips: function () {
		this.setData({
			tipsShown: 0
		});
		this.handleAccelerometer();
	},
	handleStart: function () {
		let _this = this;
		if (this.isStarted) return true;
		this.isStarted = true;
		if (this.data.audio) this.data.audio.play();
		this.setData({
			qianClass: 'qian-fast'
		});
		wx.stopAccelerometer();
		wx.offAccelerometerChange();
		setTimeout(function () {
			$.get('/api/v2/qians/getresult', function (json) {
				if (_this.data.audio) _this.data.audio.stop();
				_this.isStarted = false;
				$.pushView('/pages/index/qiansDetail?q=' + json.data);
			});
		}, 500);
	},
	handleBack: function () {
		wx.reLaunch({
			url: '/pages/index/index',
		});
	},
	onShareAppMessage: function () {
		return {
			title: this.data.res.share_title,
			imageUrl: this.data.res.share_image,
			path: this.route + '?appId=' + getApp().globalData.appId
		}
	}
})