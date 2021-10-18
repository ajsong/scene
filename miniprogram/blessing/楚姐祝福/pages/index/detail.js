var $ = require('../../common/helper.js');
Page({
    data: {
		data: {},
		content: '',
		list: [],
		banner: {},
		footer: {},
	    bannerClass: '',
		footerClass: '',
		bannerHeight: 0,
		footerHeight: 0,
		bannerHidden: '',
		footerHidden: '',
	    listTop: 99999,
		adFixed: 0,
		tips: {},
		addMy: 0,
		material: {},
	    feedback: 0,
	    feedbackShown: false,
	    feedbackItems: ['垃圾营销', '涉黄信息', '有害信息', '违法信息', '不实消息', '侵犯人身权益'],
	    feedbackContent: '',
		position: {},
		options: {},
		offset: 0,
		loadmore: '',
		leaf: 0,
		leafs: [],
		bgmusic: '',
		audio: null,
		isPlaying: 'isPlaying',
		detailPaddingBottom: '180rpx',
		paddingBottom: '',
		contentBottomShareUrl: '',
		contentBottomShareShow: 0,
		shareLayerShow: '',
		shareViewShow: '',
		sharing: 0
    },
	adHiddenTimer: null,
	bannerAdFixedScrolled: false,
	footerAdFixedScrolled: false,
    onLoad: function(options) {
		if (options.appId && options.appId.length) {
			getApp().globalData.appId = options.appId;
		}
        let _this = this;
		if ($.isX()) {
			this.setData({
				detailPaddingBottom: '248rpx',
				paddingBottom: '68rpx'
			});
		}
		$.overload();
		$.get('/api/v2/blessing/detail?id=' + options.id, function(json) {
			let blessing = json.data.blessing, avatar = '', content = blessing.content;
			if (/\[_avatar_img_\]/.test(content)) {
				avatar = '<div class="avatar avatar-rotate"><div><open-data type="userAvatarUrl"></open-data></div><span style="background-image:url(' + blessing.top_avatar_pic + ');"></span></div>';
				content = content.replace(/\[_avatar_img_\]/, avatar);
				content = content.replace(/\[_name_\]/g, '<open-data type="userNickName"></open-data>');
				content = content.replace(/\[_hello_\]/g, '<div class="hello" style="background-image:url(' + blessing.hello + ');"></div>');
				content = content.replace(/\[_avatar_img_\]/, '<div class="avatar avatar-rotate"><div><open-data type="userAvatarUrl"></open-data></div><span style="background-image:url(' + blessing.bottom_avatar_pic + ');"></span></div>');
			} else {
				content = '<p style="text-align:center;"><div class="avatar avatar-rotate"><div><open-data type="userAvatarUrl"></open-data></div><span style="background-image:url(' + blessing.top_avatar_pic + ');"></span></div></p><div style="text-align:center;"><strong><span style="font-size:23px;line-height:2;"><open-data type="userNickName"></open-data>' + json.data.texts.top_name_text + '</span></strong></div><p><div class="hello" style="background-image:url(' + blessing.hello + ');"></div></p><div style="text-align:center;"><strong><span style="font-size:23px;line-height:2;">' + json.data.texts.top_text + '</span></strong></div>' + content + '<p style="text-align:center;"><div class="avatar avatar-rotate"><div><open-data type="userAvatarUrl"></open-data></div><span style="background-image:url(' + blessing.bottom_avatar_pic + ');"></span></div></p><div style="text-align:center;"><strong><span style="font-size:23px;line-height:2;"><open-data type="userNickName"></open-data>' + json.data.texts.bottom_name_text + '</span></strong></div><div style="text-align:center;"><strong><span style="font-size:23px;line-height:2;">' + json.data.texts.bottom_text + '</span></strong></div>';
			}
			
			let bannerClass = '', footerClass = '';
			if (json.data.ad_fixed > 0) {
				bannerClass = 'banner-fixed';
				footerClass = 'footer-fixed';
				_this.adHiddenTimer = setTimeout(function () {
					_this.setData({
						bannerClass: ''
					});
				}, json.data.ad_fixed * 1000);
				setTimeout(function () {
					$.find('.bannerBox', function (res) {
						let bannerHeight = res[0].height;
						if (bannerHeight <= 0) bannerHeight = 100;
						_this.setData({
							bannerHeight: bannerHeight,
							bannerHidden: 'top:-' + (bannerHeight * json.data.ad_fixed_percent / 100) + 'px'

						});
					});
					$.find('.footerBox', function (res) {
						let footerHeight = res[0].height;
						if (footerHeight <= 0) footerHeight = 100;
						_this.setData({
							footerHeight: footerHeight,
							footerHidden: 'top:-' + (footerHeight * json.data.ad_fixed_percent / 100) + 'px'
						});
					});
				}, 700);
			}
			
			_this.setData({
				data: blessing,
				banner: json.data.banner,
				footer: json.data.footer,
				bannerClass: bannerClass,
				footerClass: footerClass,
				adFixed: json.data.ad_fixed,
				addMy: json.data.addmy,
				material: json.data.material,
				tips: json.data.tips,
				feedback: json.data.feedback,
				options: options,
				list: json.data.list
			});
			$.WxParse.wxParse('content', 'html', content, _this, 25);

			if ($.isArray(json.data.list)) {
				_this.setData({
					offset: json.data.list.length
				});
			}

			if (json.data.wxpositionad.enable == 1) {
				// 在页面中定义插屏广告
				let interstitialAd = null
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
					})
				}
			}

			if (Number(json.data.leaf.enable) == 1) {
				_this.setData({
					leaf: 1
				});
				setInterval(function () {
					let left = Math.random() * $.screen().width;
					let width = Math.random() * ($.screen().width / 3);
					let height = Math.random() * ($.screen().height / 2) + $.screen().height / 2;
					let src = json.data.leaf['position_pic' + Math.floor(Math.random() * 2 + 1)];
					let wh = Math.floor(Math.random() * 30 + 30);
					let leafs = _this.data.leafs;
					leafs.push({
						left: $.toRpx(left) + 'rpx',
						width: $.toRpx(width) + 'rpx',
						height: $.toRpx(height) + 'rpx',
						src: src,
						wh: $.toRpx(wh) + 'rpx'
					});
					_this.setData({
						leafs: leafs
					});
				}, 2000);

			}

			if (json.data.blessing.bg_music.length) {
				_this.setData({
					bgmusic: json.data.blessing.bg_music
				});
				let audio = wx.createInnerAudioContext();
				audio.src = json.data.blessing.bg_music;
				audio.loop = true;
				audio.autoplay = true;
				_this.setData({
					audio: audio
				});
				_this.data.audio.play();
			}

			if (Number(json.data.tips.enable) == 1) {
				if (json.data.tips.url.length) {
					_this.setData({
						contentBottomShareUrl: json.data.tips.url
					});
				}
			}
        });
    },
    onShow: function() {
		if (this.data.audio) this.data.audio.play();
		if (this.data.sharing == 1) {
			if (this.data.contentBottomShareUrl.length) {
				this.handleShowAd({
					currentTarget: {
						dataset: {
							url: this.data.contentBottomShareUrl
						}
					}
				});
			}
		}
		this.setData({
			sharing: 0
		});
	},
	onPageScroll: function (e) {
		let _this = this;
		if (this.data.adFixed > 0) {
			if (!this.bannerAdFixedScrolled) {
				if (this.adHiddenTimer) clearTimeout(this.adHiddenTimer);
				this.adHiddenTimer = null;
				if (e.scrollTop >= $.toPx(234)) {
					this.bannerAdFixedScrolled = true;
					_this.adHiddenTimer = setTimeout(function () {
						_this.setData({
							bannerHidden: 'transform:translateY(-' + _this.data.bannerHeight + 'px)'
						});
						clearTimeout(_this.adHiddenTimer);
						_this.adHiddenTimer = null;
						setTimeout(function () {
							_this.setData({
								bannerClass: '',
								bannerHidden: ''
							});
						}, 310);
					}, this.data.adFixed * 1000);
				}
			}
			if (!this.footerAdFixedScrolled) {
				if (e.scrollTop >= this.data.listTop) {
					this.footerAdFixedScrolled = true;
					_this.adHiddenTimer = setTimeout(function () {
						_this.setData({
							footerHidden: 'transform:translateY(-' + _this.data.footerHeight + 'px)'
						});
						clearTimeout(_this.adHiddenTimer);
						_this.adHiddenTimer = null;
						setTimeout(function () {
							_this.setData({
								footerClass: '',
								footerHidden: ''
							});
						}, 310);
					}, this.data.adFixed * 1000);
				}
			}
		}
	},
	onReachBottom: function () {
		this.getData(true);
		let _this = this;
		if (Number(this.data.tips.position) == 1) {
			if (this.data.contentBottomShareShow == 0) {
				_this.setData({
					contentBottomShareShow: 1
				});
				_this.setData({
					shareLayerShow: 'shareLayerShow',
					shareViewShow: 'shareViewShow'
				});
			}
		}
	},
	//获取数据开始
	getData: function (isReachBottom, callback) {
		wx.showNavigationBarLoading();
		var _this = this,
			data = this.data.list,
			offset = this.data.offset;
		if (!isReachBottom) {
			data = [];
			offset = 0;
		}
		$.get('/api/v2/blessing/detail?id=' + this.data.options.id, {
			offset: offset
		}, function (json) {
			wx.stopPullDownRefresh();
			wx.hideNavigationBarLoading();
			if ($.isArray(json.data.list)) {
				data = data.concat(json.data.list);
				offset += json.data.list.length;
			}
			_this.setData({
				list: data
			});
			var loadmore = '';
			if (_this.data.offset == offset || data.length < 6) {
				loadmore = 'loadmore-nomore';
			}
			_this.setData({
				offset: offset,
				loadmore: loadmore
			});
			if ($.isFunction(callback)) callback();
		});
	},
	onUnload: function () {
		clearTimeout(this.adHiddenTimer);
		this.adHiddenTimer = null;
		if (this.data.audio) {
			this.data.audio.stop();
			this.data.audio.destroy();
		}
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
		$.post('/api/v2/other/feedback', { content: this.data.feedbackContent, parent_id: this.data.data.id }, function () {
			_this.handleFeedbackShow();
			$.toast('举报成功，感谢您的反馈！');
		});
	},
	handleShareViewClose: function () {
		this.setData({
			shareLayerShow: '',
			shareViewShow: ''
		});
	},
	handleShareView: function () {
		this.setData({
			shareLayerShow: '',
			shareViewShow: ''
		});
		// if (this.data.contentBottomShareUrl.length) {
		// 	this.handleShowAd({
		// 		currentTarget: {
		// 			dataset: {
		// 				url: this.data.contentBottomShareUrl
		// 			}
		// 		}
		// 	});
		// }
	},
	handleShowDetail: function (e) {
		let data = this.data.list[e.currentTarget.dataset.index];
		wx.redirectTo({
			url: '/pages/index/detail?id=' + data.id
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
	handleAddMy: function () {
		this.setData({
			addMy: 0
		});
	},
	handleMusic: function () {
		if (this.data.isPlaying.length) {
			this.setData({
				isPlaying: ''
			});
			if (this.data.audio) this.data.audio.pause();
			if (this.data.audio2) this.data.audio2.pause();
		} else {
			this.setData({
				isPlaying: 'isPlaying'
			});
			if (this.data.audio) this.data.audio.play();
			if (this.data.audio2) this.data.audio2.play();
		}
	},
	handleLike: function () {
		let _this = this,
			data = _this.data.data;
		$.post('/api/v2/blessing/like', {
			id: data.id
		}, function () {
			if (!isNaN(data.likes)) {
				data.likes = Number(data.likes) + 1;
				_this.setData({
					data: data
				});
			}
		});
	},
    onShareAppMessage: function(res) {
		var _this = this;
		this.setData({
			shareLayerShow: '',
			shareViewShow: '',
			sharing: 1
		});
		this.handleLike();
        return {
            title: this.data.data.title,
			imageUrl: this.data.data.share_pic,
			path: this.route + '?id=' + this.data.options.id + '&appId=' + getApp().globalData.appId
        }
    }
})