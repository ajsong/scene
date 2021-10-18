var $ = require('../../common/helper.js');
Page({
    data: {
		singleScene: false,
        video: {},
		videoHeight: $.toRpx($.screen().height - 42),
		btn: {},
        list: [],
		date: '',
		addMy: 0,
	    feedback: 0,
	    feedbackShown: false,
	    feedbackItems: ['垃圾营销', '涉黄信息', '有害信息', '违法信息', '不实消息', '侵犯人身权益'],
	    feedbackContent: '',
		banner: {},
        cover: 'hidden',
        options: {},
        offset: 0,
        loadmore: '',
    },
    player: null,
    showTime: 0,
    shareTimes: 0,
    onLoad: function(options) {
        if (options.appId && options.appId.length) {
            getApp().globalData.appId = options.appId;
        }
		let d = wx.getLaunchOptionsSync();
		this.setData({
			singleScene: d.scene == 1154
		});
        let _this = this;
        $.overload();
        $.get('/api/v2/video/detail?id=' + options.id, function(json) {
			let video = json.data.video;
			let btn = json.data.btn;
			let addMy = json.data.addmy;
			let banner = json.data.banner;
			//$.log($.screen().height)
			let shareHeight = 42;
			switch (parseInt($.screen().height)) {
				case 603: shareHeight = 52; break;
				case 672: shareHeight = 58; break;
				case 724: shareHeight = 52; break;
				case 808: shareHeight = 58; break;
			}
			let videoHeight = $.screen().height - shareHeight;
            if (video.info) {
                if (Number(video.info.width) < Number(video.info.height)) {
					let videoWidth = ($.screen().height - shareHeight) * Number(video.info.width) / Number(video.info.height);
                    videoHeight = videoWidth * Number(video.info.height) / Number(video.info.width);
                } else {
                    videoHeight = $.screen().width * Number(video.info.height) / Number(video.info.width);
                }
            }
            videoHeight = $.toRpx(videoHeight);
            _this.setData({
                video: video,
				videoHeight: videoHeight,
				btn: btn,
				addMy: addMy,
	            feedback: json.data.feedback,
				banner: banner,
                list: json.data.list,
                date: json.data.date,
                options: options
            });
            $.setTitle(video.title);
            if ($.isArray(json.data.list)) {
                _this.setData({
                    offset: json.data.list.length
                });
            }

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
					interstitialAd.onLoad(() => { })
					interstitialAd.onError((err) => { })
					interstitialAd.onClose(() => { })
                    // 在适合的场景显示插屏广告
                    if (interstitialAd) {
                        interstitialAd.show().catch((err) => {
                            console.error(err)
                        })
                    }
				}
			}
        });
    },
    onShow: function() {
        let player = wx.createVideoContext('player');
        this.player = player;
    },
    onReachBottom: function() {
        this.getData(true);
	},
	handleFeedbackShow: function () {
		if (!this.data.feedbackContent.length) {
			this.setData({
				feedbackContent: this.data.feedbackItems[0]
			});
		}
		if (this.data.feedbackShown) {
			this.setData({
				feedbackShown: false
			});
		} else {
			this.setData({
				feedbackShown: true
			});
		}
	},
	handleFeedbackChange: function (e) {
		this.setData({
			feedbackContent: e.currentTarget.dataset.value
		});
	},
	handleFeedbackSubmit: function () {
		let _this = this;
		$.post('/api/v2/other/feedback', { content: this.data.feedbackContent, parent_id: this.data.video.id }, function () {
			_this.handleFeedbackShow();
			$.toast('举报成功，感谢您的反馈！');
		});
	},
	handleAddMy: function () {
		this.setData({
			addMy: 0
		});
	},
	handleShowAd: function (e) {
		let url = e.currentTarget.dataset.url;
		if (/^\/pages\//.test(url)) {
			wx.redirectTo({
				url: url
			});
		} else if (/^wx\w{16}:pages\//.test(url)) {
			let urls = url.split(':');
			wx.navigateToMiniProgram({
				appId: urls[0],
				path: urls[1]
			});
		} else {
			wx.navigateTo({
				url: '/pages/index/web?url=' + $.urlencode(url)
			});
		}
	},
    //获取数据开始
    getData: function(isReachBottom, callback) {
        wx.showNavigationBarLoading();
        var _this = this,
            data = this.data.list,
            offset = this.data.offset;
        if (!isReachBottom) {
            data = [];
            offset = 0;
        }
        $.get('/api/v2/video/detail?id=' + this.data.options.id, {
            offset: offset
        }, function(json) {
            wx.stopPullDownRefresh();
            wx.hideNavigationBarLoading();
            if ($.isArray(json.data.list)) {
                data = data.concat(json.data.list);
                offset += json.data.list.length;
            }
            var loadmore = '';
            if (_this.data.offset == offset || data.length < 6) {
                loadmore = 'loadmore-nomore';
            }
            _this.setData({
                list: data,
                offset: offset,
                loadmore: loadmore
            });
            if ($.isFunction(callback)) callback();
        });
    },
	handleLike: function (e) {
		let _this = this,
			data = _this.data.video;
		$.post('/api/v2/video/like', {
			id: data.id
		}, function () {
			data.likes = Number(data.likes) + 1;
			_this.setData({
				video: data
			});
		});
	},
    wxalert: function(content, btnTitle, openType) {
        if (typeof btnTitle === 'undefined') btnTitle = '好的';
        if (typeof openType === 'undefined') openType = '';
        $.dialogView({
            cls: 'dialog-tips',
            content: content,
            bgClose: false,
            btns: [{
                cls: 'orangeBtn',
                title: btnTitle,
                openType: openType
            }]
        });
    },
    showShare: function() {
        this.wxalert('<span style="font-size:42rpx;color:#0bb20c;">温馨提示</span><br /><br /><span style="font-size:37rpx;">分享到</span><span style="font-size:37rpx;color:#f00;">微信群</span><br /><span style="font-size:37rpx;">即可继续播放视频</span></span>', '立即分享', 'share');
        //this.wxalert('<span style="font-size:42rpx;color:#0bb20c;">温馨提示</span><br /><br /><span style="font-size:37rpx;">发送</span><span style="font-size:37rpx;color:#f00;">66</span><span style="font-size:37rpx;">给客服</span><br /><span style="font-size:37rpx;">即可继续播放视频</span></span>', '立即联系客服', 'contact');
    },
    timeupdate: function(e) {
        let _this = this;
        if (e.detail.currentTime > 0.1) {
            if (this.data.video.time > 0 && e.detail.currentTime >= this.data.video.time && this.showTime <= 0) {
                this.player.pause();
                this.player.exitFullScreen();
                this.setData({
                    cover: ''
                });
                this.showShare();
            }
        }
    },
    shareCheck: function() {
        this.shareTimes++;
        if (this.shareTimes > 0) {
            this.setData({
                cover: 'hidden'
            });
            $.dialogView(false);
            this.showTime = new Date().getTime();
            this.player.play();
        } else {
            this.setData({
                cover: ''
            });
            this.wxalert('<span style="font-size:0.18rem;color:#f00;">分享失败</span><br /><br /><span style="font-size:0.16rem;">必须公开分享<br />请重新分享到<span style="color:#f00;">微信群</span><br />立即继续播放视频</span>');
        }
    },
    onShareAppMessage: function(res) {
        var _this = this;
        setTimeout(function() {
            _this.shareCheck();
        }, 500);
		this.handleLike();
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