var $ = require('../../common/helper.js');
Page({
    data: {
		data: [],
        offset: 0,
        loadmore: '',
		category_id: 0,
		returnEnable: 0,
		everyBody: 0,
		originUrls: [],
		returnUrls: [],
		isShowed: false,
		originId: 0
    },
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
        //$.overload();
		wx.startPullDownRefresh();
    },
	onShow: function () {
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
		let id = e.currentTarget.dataset.id, data = this.data.data[e.currentTarget.dataset.index];
		if (this.data.everyBody && this.data.originId != id) {
			this.setData({
				returnUrls: this.data.originUrls
			});
		}
		this.setData({
			originId: id
		});
		if (data.type == 5) {
			$.pushView('/pages/index/video?id=' + id);
		} else {
			$.pushView('/pages/index/detail?id=' + id);
		}
	},
	handleShowAd: function (e) {
		let url = e.currentTarget.dataset.url;
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
			//flashes = this.data.flashes,
            offset = this.data.offset;
        if (!isReachBottom) {
            data = [];
            offset = 0;
        }
		$.get('/api/v2/buddha/buddhaList', {
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
			// if (!flashes.length) {
			// 	_this.setData({
			// 		flashes: json.data.flashes
			// 	});
			// }
            if ($.isFunction(callback)) callback();
        });
    },
    onShareAppMessage: function(res) {
		var _this = this,
			title = $.extConfig.shareTitle ? $.extConfig.shareTitle : '',
			imageUrl = $.extConfig.shareImageUrl ? $.extConfig.shareImageUrl : '',
            path = '/pages/index/index?appId=' + getApp().globalData.appId;
        if (res.from == 'button') {
            title = res.target.dataset.title;
            imageUrl = res.target.dataset.pic;
            path = '/pages/index/detail?id=' + res.target.dataset.id + '&appId=' + getApp().globalData.appId;
        }
        return {
            title: title,
            imageUrl: imageUrl,
            path: path
        }
    }
})