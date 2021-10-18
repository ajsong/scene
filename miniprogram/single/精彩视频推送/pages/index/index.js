var $ = require('../../common/helper.js');
Page({
    data: {
        data: [],
        offset: 0,
		loadmore: '',
		categories: [],
		category_id: 0,
		returnEnable: 0,
		everyBody: 0,
		originUrls: [],
		returnUrls: [],
		isShowed: false,
		originId: 0
	},
	showdWxPositionAd: false,
    onLoad: function(options) {
		if (options.appId && options.appId.length) {
			getApp().globalData.appId = options.appId;
		}
        var _this = this,
            category_id = options.category_id || 0;
        category_id = Number(category_id);
        this.setData({
            category_id: category_id
        });
		$.overload();
		wx.startPullDownRefresh();
    },
    onShow: function() {
		if (this.data.isShowed && this.data.returnEnable) {
			if (this.data.returnUrls.length) {
				let urls = this.data.returnUrls;
				let url = urls.shift();
				this.setData({
					returnUrls: urls
				});
				$.pushView('/pages/index/web?url=' + $.urlencode(url));
			}
		}
		this.setData({
			isShowed: true
		});
	},
	handleShowDetail: function (e) {
		let id = e.currentTarget.dataset.id;
		if (this.data.everyBody && this.data.originId != id) {
			this.setData({
				returnUrls: this.data.originUrls
			});
		}
		this.setData({
			originId: id
		});
		//$.pushView('/pages/index/detail?id=' + id);
		$.pushView('/pages/index/video?id=' + id);
	},
	handleShowAd: function (e) {
		let url = e.currentTarget.dataset.url;
		this.setData({
			returnUrls: [],
			originId: 0
		});
		if (/^\/pages\//.test(url)) {
			wx.navigateTo({
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
	handleLike: function (e) {
		let _this = this,
			index = e.currentTarget.dataset.index,
			item = _this.data.data[index];
		$.post('/api/v2/video/like', {
			id: item.id
		}, function () {
			item.likes = Number(item.likes) + 1;
			let data = _this.data.data;
			data.splice(index, 1, item)
			_this.setData({
				data: data
			});
		});

	},
    onPullDownRefresh: function() {
        this.getData();
    },
    onReachBottom: function() {
        this.getData(true);
    },
    //获取数据开始
    getData: function(isReachBottom, callback) {
        wx.showNavigationBarLoading();
        var _this = this,
            data = this.data.data,
            offset = this.data.offset;
        if (!isReachBottom) {
            data = [];
            offset = 0;
        }
        $.get('/api/v2/video', {
            offset: offset,
            category_id: this.data.category_id
        }, function(json) {
            wx.stopPullDownRefresh();
			wx.hideNavigationBarLoading();

			if (!_this.data.originUrls.length) {
				_this.setData({
					returnUrls: json.data.res.urls
				});
			}
			_this.setData({
				returnEnable: json.data.res.enable,
				everyBody: json.data.res.everybody,
				originUrls: json.data.res.urls
			});

			if (!_this.data.categories.length && $.isArray(json.data.category)) {
				let category_id = _this.data.category_id>0 ? _this.data.category_id : Number(json.data.category[0].id);
				_this.setData({
					categories: json.data.category,
					category_id: category_id
				});
				// let list = [{
				//     name: '全部',
				//     value: 0
				// }];
				let list = [];
				$.each(json.data.category, function () {
					list.push({
						name: this.name,
						value: Number(this.id)
					});
				});
				$.switchView({
					list: list,
					selected: category_id,
					click: function (value) {
						_this.setData({
							category_id: value
						});
						_this.getData();
					}
				});
			}

            if ($.isArray(json.data.list)) {
				data = data.concat(json.data.list);
				offset += json.data.list.length;
            }
            var loadmore = '';
            if (_this.data.offset == offset || data.length < 6) {
                loadmore = 'loadmore-nomore';
            }
            _this.setData({
                data: data,
                offset: offset,
                loadmore: loadmore
			});

			if (!_this.showdWxPositionAd && json.data.wxpositionad.enable == 1) {
				_this.showdWxPositionAd = true;
				// 在页面中定义插屏广告
				let interstitialAd = null
				// 在页面onLoad回调事件中创建插屏广告实例
				if (wx.createInterstitialAd) {
					interstitialAd = wx.createInterstitialAd({
						adUnitId: json.data.wxpositionad.adunit
					});
					interstitialAd.onLoad(() => { })
					interstitialAd.onError((err) => { })
					interstitialAd.onClose(() => { })
				}
				// 在适合的场景显示插屏广告
				if (interstitialAd) {
					interstitialAd.show().catch((err) => {
						console.error(err)
					})
				}
			}
			
            if ($.isFunction(callback)) callback();
        });
    },
    onShareAppMessage: function(res) {
        var _this = this,
            title = $.config.shareTitle,
            imageUrl = $.config.shareImageUrl,
            path = '/pages/index/index?appId=' + getApp().globalData.appId;
        if (res.from == 'button') {
            title = res.target.dataset.title;
            imageUrl = res.target.dataset.img;
            path = '/pages/index/detail?id=' + res.target.dataset.id + '&appId=' + getApp().globalData.appId;
        }
        return {
            title: title,
            imageUrl: imageUrl,
            path: path
        }
    }
})