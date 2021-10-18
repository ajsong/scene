var $ = require('../../common/helper.js');
Page({
	data: {
		data: {},
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
		commentHidden: 1,
		trans: null,
		btn: {},
		tips: {},
		content_next: 0,
		content_offset: 0,
		addMy: 0,
		feedback: 0,
		feedbackShown: false,
		feedbackItems: ['垃圾营销', '涉黄信息', '有害信息', '违法信息', '不实消息', '侵犯人身权益'],
		feedbackContent: '',
		position: {},
		cover: 'hidden',
		options: {},
		offset: 0,
		loadmore: '',
		newyear: '',
		newyearHeight: 0,
		newyearMusic: '',
		newyearMusic2: '',
		bgmusic: '',
		audio: null,
		audio2: null,
		isPlaying: 'isPlaying',
		list: [],
		nextPage: null,
		paddingBottom: '',
		comment: [],
		commentTransform: '',
		commentLayerShow: '',
		commentContent: '',
		isContentBottomShare: 0,
		contentTop: 0,
		contentBottom: 99999,
		contentBottomShareUrl: '',
		contentBottomShareShow: 0,
		shareLayerShow: '',
		shareViewShow: '',
		sharing: 0,
		player: null,
		audioPlay: false,
		audioTime: '00:00',
		audioDuration: '00:00',
		audioOffset: 0,
		audioMax: 0,
		audioEnded: false
	},
	adHiddenTimer: null,
	bannerAdFixedScrolled: false,
	footerAdFixedScrolled: false,
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
		$.overload();
		$.get('/api/v2/buddha/detail?id=' + options.id, function (json) {
			let category_id = json.data.buddha.category_id;
			let categories = $.storage('categories');
			if (categories) {
				let index = $.inArray(category_id, categories);
				if (index > -1) {
					categories.splice(index, 1);
				}
				categories.push(category_id);
				$.storage('categories', categories);
			} else {
				$.storage('categories', [category_id]);
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
					$.find('.list', function (res) {
						if (!res.length) return;
						let listTop = res[0].top;
						_this.setData({
							listTop: listTop
						});
					});
				}, 2000);
				setTimeout(function () {
					$.find('.bannerBox', function (res) {
						if (!res.length) return;
						let bannerHeight = res[0].height;
						if (bannerHeight <= 0) bannerHeight = 100;
						_this.setData({
							bannerHeight: bannerHeight,
							bannerHidden: 'top:-' + (bannerHeight * json.data.ad_fixed_percent / 100) + 'px'

						});
					});
					$.find('.footerBox', function (res) {
						if (!res.length) return;
						let footerHeight = res[0].height;
						if (footerHeight <= 0) footerHeight = 100;
						_this.setData({
							footerHeight: footerHeight,
							footerHidden: 'top:-' + (footerHeight * json.data.ad_fixed_percent / 100) + 'px'
						});
					});
				}, 500);
			}

			_this.setData({
				data: json.data.buddha,
				content_next: json.data.content_next,
				banner: json.data.banner,
				footer: json.data.footer,
				bannerClass: bannerClass,
				footerClass: footerClass,
				adFixed: json.data.ad_fixed,
				commentHidden: json.data.comment_hidden,
				trans: json.data.trans,
				btn: json.data.btn,
				tips: json.data.tips,
				addMy: json.data.addmy,
				feedback: json.data.feedback,
				position: json.data.position,
				options: options,
				list: json.data.list,
				nextPage: json.data.next_page,
				comment: json.data.comment
			});

			if (Number(json.data.comment_hidden) == 0) {
				if ($.isArray(json.data.comment)) {
					_this.setData({
						offset: json.data.comment.length
					});
				}
			} else {
				if ($.isArray(json.data.list)) {
					_this.setData({
						offset: json.data.list.length
					});
				}
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

			if (Number(json.data.newyear.enable) == 1) {
				let newyear = '';
				for (let i = 0; i < 10; i++) {
					newyear += '<div class="firework">';
					for (let j = 0; j < 40; j++) {
						newyear += '<div class="c"></div>';
					}
					newyear += '</div>';
				}
				_this.setData({
					newyearHeight: $.toRpx($.screen().height),
					newyearMusic: json.data.newyear.music,
					newyearMusic2: json.data.newyear.music2
				});
				$.WxParse.wxParse('newyear', 'html', newyear, _this, 0);
				if (json.data.newyear.music.length) {
					let audio = wx.createInnerAudioContext();
					audio.src = json.data.newyear.music;
					audio.loop = true;
					audio.autoplay = true;
					_this.setData({
						audio: audio
					});
					_this.data.audio.play();
				}
				if (json.data.newyear.music2.length) {
					let audio = wx.createInnerAudioContext();
					audio.src = json.data.newyear.music2;
					audio.loop = true;
					audio.autoplay = true;
					_this.setData({
						audio2: audio
					});
					_this.data.audio2.play();
				}
			}
			if (json.data.newyear.bgmusic.length && !_this.data.newyearMusic.length) {
				/*_this.setData({
					bgmusic: json.data.newyear.bgmusic
				});
				let audio = wx.createInnerAudioContext();
				audio.src = json.data.newyear.bgmusic;
				audio.loop = true;
				audio.autoplay = true;
				_this.setData({
					audio: audio
				});
				_this.data.audio.play();*/
				_this.setData({
					bgmusic: json.data.newyear.bgmusic
				});
				_this.audioInit(json.data.buddha.title, json.data.newyear.bgmusic, true);
			}
			
			if (Number(json.data.tips.enable) == 1) {
				if (json.data.tips.url.length) {
					_this.setData({
						contentBottomShareUrl: json.data.tips.url
					});
				}
				if (Number(json.data.tips.position) == 1) {
					setTimeout(function () {
						$.find('.content', function (res) {
							let contentTop = res[0].bottom - res[0].height;
							_this.setData({
								contentTop: contentTop,
								contentBottom: res[0].height + contentTop
							});
						});
					}, 2000);
				}
			}
		});
	},
	onShow: function () {
		if (this.data.audio) this.data.audio.play();
		if (this.data.audio2) this.data.audio2.play();
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
				if (e.scrollTop >= this.data.bannerHeight) {
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
		if (Number(this.data.tips.position) == 1) {
			$.find('.content', function (res) {
				_this.setData({
					contentBottom: res[0].height + _this.data.contentTop
				});
			});
			if (e.scrollTop + $.screen().height / 2 >= _this.data.contentBottom && _this.data.contentBottomShareShow == 0) {
				_this.setData({
					isContentBottomShare: 1,
					contentBottomShareShow: 1,
					shareLayerShow: 'shareLayerShow',
					shareViewShow: 'shareViewShow'
				});
			}
		}
	},
	onReachBottom: function () {
		this.getData(true);
	},
	//获取数据开始
	getData: function (isReachBottom, callback) {
		wx.showNavigationBarLoading();
		var _this = this,
			data = [],
			offset = this.data.offset;
		if (_this.data.commentHidden == 0) {
			data = this.data.comment;
		} else {
			data = this.data.list;
		}
		if (!isReachBottom) {
			data = [];
			offset = 0;
		}
		$.get('/api/v2/buddha/detail?id=' + this.data.options.id, {
			offset: offset
		}, function (json) {
			wx.stopPullDownRefresh();
			wx.hideNavigationBarLoading();
			if (_this.data.commentHidden == 0) {
				if ($.isArray(json.data.comment)) {
					data = data.concat(json.data.comment);
					offset += json.data.comment.length;
				}
				_this.setData({
					comment: data
				});
			} else {
				if ($.isArray(json.data.list)) {
					data = data.concat(json.data.list);
					offset += json.data.list.length;
				}
				_this.setData({
					list: data
				});
			}
			var loadmore = '';
			if (_this.data.offset == offset || data.length < 6) {
				loadmore = 'loadmore-nomore';
			}
			_this.setData({
				comment: data,
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
		if (this.data.audio2) {
			this.data.audio2.stop();
			this.data.audio2.destroy();
		}
		if (this.data.player) {
			this.audioStop();
		}
	},
	//初始化背景音乐
	audioInit: function (title, src, autoplay) {
		let _this = this;
		let player = wx.getBackgroundAudioManager();
		player.title = title; //iOS必须加title,否则会报错导致音乐不播放
		player.epname = title;
		player.src = src;
		player.onTimeUpdate(() => {
			let offset = parseInt(player.currentTime), minute = '0' + parseInt(offset / 60), second = offset % 60;
			if (second < 10) second = '0' + second;
			let max = parseInt(player.duration), _minute = '0' + parseInt(max / 60), _second = max % 60;
			if (_second < 10) _second = '0' + _second;
			_this.setData({
				audioTime: minute + ':' + second,
				audioOffset: offset,
				audioDuration: _minute + ':' + _second,
				audioMax: max
			});
		});
		player.onPlay(() => {
			_this.setData({
				audioPlay: true
			});
		});
		player.onPause(() => {
			_this.setData({
				audioPlay: false
			});
		});
		player.onEnded(() => {
			player.seek(0);
			_this.setData({
				audioPlay: false,
				audioTime: '00:00',
				audioOffset: 0,
				audioEnded: true
			});
			if (_this.data.nextPage) {
				if (_this.data.nextPage.type == 5) {
					wx.redirectTo({
						url: '/pages/index/video?id=' + _this.data.nextPage.id
					});
				} else {
					wx.redirectTo({
						url: '/pages/index/detail?id=' + _this.data.nextPage.id
					});
				}
			}
		});
		player.play();
		_this.setData({
			player: player,
			audioEnded: false
		});
		if (autoplay) {
			player.play();
			_this.setData({
				audioPlay: true
			});
		}
	},
	//播放
	audioPlay: function () {
		if (this.data.audioEnded) {
			this.audioInit(this.data.data.title, this.data.bgmusic, true);
		} else {
			this.data.player.play();
		}
	},
	//暂停播放
	audioPause: function () {
		this.data.player.pause();
	},
	//播放结束
	audioStop: function () {
		this.data.player.stop();
	},
	//进度条拖拽
	audioSlider: function (e) {
		let player = this.data.player;
		let offset = parseInt(e.detail.value);
		player.play();
		player.seek(offset);
		this.setData({
			audioPlay: true
		});
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
	handleShareView: function () {
		this.setData({
			shareLayerShow: '',
			shareViewShow: ''
		});
	},
	handleContentNext: function () {
		$.overload();
		let _this = this,
			data = this.data.data,
			content_offset = this.data.content_offset + 1;
		$.get('/api/v2/buddha/detail?id=' + this.data.options.id + '&content_offset=' + content_offset, function (json) {
			data.content += json.data.buddha.content;
			_this.setData({
				data: data,
				content_next: json.data.content_next,
				content_offset: content_offset
			});
		});
	},
	handleShowDetail: function (e) {
		let data = this.data.list[e.currentTarget.dataset.index];
		if (data.type == 5) {
			wx.redirectTo({
				url: '/pages/index/video?id=' + data.id
			});
		} else {
			wx.redirectTo({
				url: '/pages/index/detail?id=' + data.id
			});
		}
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
	handleTop: function () {
		if (wx.pageScrollTo) {
			wx.pageScrollTo({
				scrollTop: 0
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
		$.post('/api/v2/buddha/like', {
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
	handleCommentShow: function () {
		if (this.data.commentLayerShow.length) {
			this.setData({
				commentTransform: '',
				commentLayerShow: ''
			});
		} else {
			this.setData({
				commentTransform: 'commentTransform',
				commentLayerShow: 'commentLayerShow'
			});
		}
	},
	handleCommentLike: function (e) {
		let _this = this,
			comment = _this.data.comment,
			index = e.currentTarget.dataset.index,
			item = comment[index];
		$.post('/api/v2/buddha/comment_like', {
			id: item.id
		}, function () {
			item.likes = Number(item.likes) + 1;
			comment.splice(index, 1, item);
			_this.setData({
				comment: comment
			});
		});
	},
	handleCommentInput: function (e) {
		this.setData({
			commentContent: e.detail.value
		});
	},
	handleCommentSend: function () {
		let _this = this;
		if (!this.data.commentContent.length) {
			$.overloadError('请输入评论内容');
			return;
		}
		$.post('/api/v2/buddha/comment', { buddha_id: this.data.data.id, content: this.data.commentContent }, function (json) {
			if (json.data.status == 1) {
				let comment = _this.data.comment;
				comment.unshift(json.data);
				_this.setData({
					comment: comment
				});
			} else {
				$.overloadSuccess('提交成功，我们将尽快审核');
			}
			wx.hideKeyboard();
			_this.setData({
				commentTransform: '',
				commentLayerShow: ''
			});
			setTimeout(function () {
				_this.setData({
					commentContent: ''
				});
			}, 400);
		});
	},
	wxalert: function (content, btnTitle, openType) {
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
	onShareAppMessage: function (res) {
		let _this = this;
		if (this.data.isContentBottomShare == 1) {
			this.setData({
				shareLayerShow: '',
				shareViewShow: '',
				sharing: 1
			});
		}
		this.setData({
			shareLayerShow: '',
			shareViewShow: '',
			sharing: 1,
			isContentBottomShare: 0
		});
		this.handleLike();
		return {
			title: this.data.data.title,
			imageUrl: this.data.data.pic,
			path: this.route + '?id=' + this.data.options.id + '&appId=' + getApp().globalData.appId
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