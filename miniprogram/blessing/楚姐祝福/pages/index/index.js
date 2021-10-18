var $ = require('../../common/helper.js');
Page({
    data: {
		data: [],
        offset: 0,
        loadmore: '',
		returnEnable: 0,
		everyBody: 0,
		originUrls: [],
		returnUrls: [],
		isShowed: false,
		originId: 0,
		addMy: 0,
		addMyed: 0,
		material: {}
    },
    onLoad: function(options) {
		if (options.appId && options.appId.length) {
			getApp().globalData.appId = options.appId;
		}
        $.overload();
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
	handleLike: function (e) {
		let _this = this,
			index = e.currentTarget.dataset.index,
			item = _this.data.data[index];
		$.post('/api/v2/blessing/like', {
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
	handleAddMy: function() {
		this.setData({
			addMy: 0,
			addMyed: 1
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
            offset = this.data.offset;
        if (!isReachBottom) {
            data = [];
            offset = 0;
		}
		$.get('/api/v2/blessing', {
            offset: offset
        }, function(json) {
            wx.stopPullDownRefresh();
			wx.hideNavigationBarLoading();

			let addMy = json.data.addmy;
			if (!_this.data.addMyed) {
				_this.setData({
					addMy: addMy
				});
			}

			if (!_this.data.originUrls.length) {
				_this.setData({
					returnUrls: json.data.res.urls
				});
			}
			_this.setData({
				returnEnable: json.data.res.enable,
				everyBody: json.data.res.everybody,
				originUrls: json.data.res.urls,
				material: json.data.material
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