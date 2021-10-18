var $ = require('../../common/helper.js');
const TxvContext = requirePlugin("tencentvideo");
Page({
    data: {
        video: null,
        rewardedad: null,
        isShownAd: false
    },
    options: null,
    player: null,
    rewardedVideoAd: null,
    onLoad: function(options) {
        TxvContext.closeLog();
        if (options.appId && options.appId.length) {
            getApp().globalData.appId = options.appId;
        }
        this.options = options;
        let _this = this;
        $.overload();
        $.get('/api/v2/video/detail?id=' + options.id, function(json) {
			let video = json.data.video;
            _this.setData({
                video: video,
                rewardedad: json.data.rewardedad,
                options: options
            });
            $.setTitle(video.title);

            _this.player = (video.tencentvideo && video.vid) ? TxvContext.getTxvContext(video.vid) : wx.createVideoContext('player');

			if (!Object.values) Object.values = function (obj) {
				if (obj !== Object(obj))
					throw new TypeError('Object.values called on a non-object');
				let values = [];
				for (let key in obj) {
					if (Object.prototype.hasOwnProperty.call(obj, key)) {
						values.push(obj[key]);
					}
				}
				return values;
			};
			if (json.data.wxpositionad.enable == 1) {
				if (wx.createInterstitialAd) {
                    // 在页面onLoad回调事件中创建插屏广告实例
					let interstitialAd = wx.createInterstitialAd({
						adUnitId: json.data.wxpositionad.adunit
					});
					interstitialAd.onLoad(() => { });
					interstitialAd.onError((err) => { });
					interstitialAd.onClose(() => { });
                    // 在适合的场景显示插屏广告
                    if (interstitialAd) {
                        interstitialAd.show().catch((err) => {
                            console.error(err);
                        });
                    }
				}
            }
            
			if (json.data.rewardedad.enable == 1) {
                // 在页面中定义激励视频广告
                _this.rewardedVideoAd = null;
                // 在页面onLoad回调事件中创建激励视频广告实例
                if (wx.createRewardedVideoAd) {
                    _this.rewardedVideoAd = wx.createRewardedVideoAd({
                        adUnitId: json.data.rewardedad.adunit
                    });
                    _this.rewardedVideoAd.onLoad(() => {});
                    _this.rewardedVideoAd.onError((err) => {});
                    _this.rewardedVideoAd.onClose((res) => {
                        if (res.isEnded) {
                            _this.setData({
                                isShownAd: true
                            });
                            _this.player.play();
                        }
                    });
                }
            } else {
                _this.setData({
                    isShownAd: true
                });
            }
        });
    },
    timeupdate: function(e) {
        if (!this.data.isShownAd) {
            this.player.pause();
            this.player.exitFullScreen();
            return;
        }
        if (e.detail.currentTime > 0.1) {
            //e.detail.currentTime/e.detail.duration >= 0.3
        }
    },
    showVideoAd: function() {
        // 用户触发广告后，显示激励视频广告
        if (this.rewardedVideoAd) {
            this.rewardedVideoAd.show().catch(() => {
                // 失败重试
                this.rewardedVideoAd.load().then(() => {
                    this.rewardedVideoAd.show();
                }).catch(err => {
                    //console.log('广告显示失败');
                    this.setData({
                        rewardedad: {
                            enable: 0
                        }
                    });
                    this.setData({
                        isShownAd: true
                    });
                    this.player.play();
                });
            });
        }
    },
    onShareAppMessage: function(res) {
        return {
            title: this.data.video.title,
            imageUrl: this.data.video.img,
            path: this.route + '?id=' + this.data.options.id + '&appId=' + getApp().globalData.appId
        }
    },
	onShareTimeline: function() {
		return {
			title: this.data.video.title,
			//query: '',
			imageUrl: this.data.video.img
		}
	}
})