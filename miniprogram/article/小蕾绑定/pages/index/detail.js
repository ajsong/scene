var $ = require('../../common/helper.js');
Page({
    data: {
		data: {},
		banner: {},
		footer: {},
        bgcolor: '',
		bannerClass: '',
		footerClass: '',
		bannerHeight: 0,
		footerHeight: 0,
		bannerHidden: '',
		footerHidden: '',
		listTop: 99999,
		adFixed: 0,
		mp: null,
	    subscribe_id: '',
		subscribe_img: '',
		trans: null,
		openid: '',
		commentHidden: 1,
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
        posad: '',
		listType: ''
	},
	subscribing: false,
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
				paddingBottom: '0'
			});
		}
		$.overload();
		$.get('/api/v2/article/detail?id=' + options.id, function(json) {
			let category_id = json.data.article.category_id;
			let categories = $.storage('categories');
			if (categories) {
				let index = $.inArray(category_id, categories);
				if (index>-1) {
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
							bannerHidden: 'top:-' + (bannerHeight*json.data.ad_fixed_percent/100) + 'px'

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
				}, 700);
			}

			if (json.data.subscribe_id.length) {
				$.getOpenid(function(openid) {
					_this.setData({
						openid: openid
					});
				});
			}

			_this.setData({
				data: json.data.article,
				content_next: json.data.content_next,
				bgcolor: json.data.bgcolor,
				banner: json.data.banner,
				footer: json.data.footer,
				bannerClass: bannerClass,
				footerClass: footerClass,
				adFixed: json.data.ad_fixed,
				mp: json.data.mp,
				subscribe_id: json.data.subscribe_id,
				subscribe_img: json.data.subscribe_img,
				trans: json.data.trans,
				commentHidden: json.data.comment_hidden,
				btn: json.data.btn,
				tips: json.data.tips,
				addMy: json.data.addmy,
				feedback: json.data.feedback,
				position: json.data.position,
				options: options,
				list: json.data.list,
				comment: json.data.comment,
				posad: json.data.posad,
				listType: json.data.list_type
			});

			if (Number(json.data.article.wxparse) == 1) {
				$.WxParse.wxParse('content', 'html', json.data.article.content, _this, 12);
			}

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
				_this.setData({
					bgmusic: json.data.newyear.bgmusic
				});
				let audio = wx.createInnerAudioContext();
				audio.src = json.data.newyear.bgmusic;
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
    onShow: function() {
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
		$.get('/api/v2/article/detail?id=' + this.data.options.id, {
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
	},
	handleShowSubscribe: function () {
		if (!this.data.openid.length) {
			$.overloadWarning('缺少openid');
			return;
		}
		if (this.subscribing) {
			$.overloadWarning('正在订阅中，请稍候');
			return;
		}
		this.subscribing = true;
		let _this = this;
		let subscribe_id = _this.data.subscribe_id.split(',');
		for (let i=0; i<subscribe_id.length; i++) {
			subscribe_id[i] = $.trim(subscribe_id[i]);
		}
		wx.requestSubscribeMessage({
			tmplIds: subscribe_id,
			success: function (res) {
				if (res.errMsg == 'requestSubscribeMessage:ok') {
					for (let i=0; i<subscribe_id.length; i++) {
						switch (res[subscribe_id[i]]) {
							case 'accept':
								$.post('/api/v2/other/wechat_subscribe_message', {
									openid: _this.data.openid,
									template_id: subscribe_id[i]
								}, function(json){
									$.overloadSuccess('您已成功订阅');
								});
								break;
							case 'reject': //拒绝
							case 'ban': //已被后台封禁
								break;
						}
					}
					_this.subscribing = false;
				}
			},
			fail: function (res) {
				switch (res.errCode) {
					case 10001: $.overloadError('参数传空了'); break;
					case 10002: $.overloadError('网络问题，请求消息列表失败'); break;
					case 10003: $.overloadError('网络问题，订阅请求发送失败'); break;
					case 10004: $.overloadError('参数类型错误'); break;
					case 10005: $.overloadError('无法展示 UI，一般是小程序这个时候退后台了导致的'); break;
					case 20001: $.overloadError('没有模板数据，一般是模板 ID 不存在 或者和模板类型不对应 导致的'); break;
					case 20002: $.overloadError('模板消息类型 既有一次性的又有永久的'); break;
					case 20003: $.overloadError('模板消息数量超过上限'); break;
					case 20004: $.overloadError('用户关闭了主开关，无法进行订阅'); break;
					case 20005: $.overloadError('小程序被禁封'); break;
				}
			}
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
		$.get('/api/v2/article/detail?id=' + this.data.options.id + '&content_offset=' + content_offset, function (json) {
			data.content += json.data.article.content;
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
	handleGoHome: function () {
		let pages = getCurrentPages();
		if (pages.length == 1) {
			wx.reLaunch({
				url: '/pages/index/index'
			});
		} else {
			wx.navigateBack({
				delta: 1
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
		$.post('/api/v2/article/like', {
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
	handleGoMore: function (e) {
		let url = e.currentTarget.dataset.url;
		wx.navigateTo({
			url: url
		});
	},
	handleCommentLike: function (e) {
		let _this = this,
			comment = _this.data.comment,
			index = e.currentTarget.dataset.index,
			item = comment[index];
		$.post('/api/v2/article/comment_like', {
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
		$.post('/api/v2/article/comment', { article_id: this.data.data.id, content: this.data.commentContent }, function (json) {
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
			setTimeout(function(){
				_this.setData({
					commentContent: ''
				});
			}, 400);
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
    onShareAppMessage: function(res) {
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
    }
})