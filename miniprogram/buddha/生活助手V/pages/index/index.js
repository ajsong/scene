var $ = require('../../common/helper.js');
Page({
    data: {
		data: [],
		flashes: [],
        offset: 0,
        loadmore: '',
		category_id: -1,
		categories: [],
		returnEnable: 0,
		everyBody: 0,
		originUrls: [],
		returnUrls: [],
		isShowed: false,
		originId: 0,
		addMy: 0,
		addMyed: 0,
		isReview: 0
	},
    onLoad: function(options) {
		if (options.appId && options.appId.length) {
			getApp().globalData.appId = options.appId;
		}
        var _this = this,
            category_id = options.category_id || -1;
        category_id = Number(category_id);
        this.setData({
            category_id: category_id
        });
        $.overload();
		//wx.startPullDownRefresh();
		this.getData();
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
		$.post('/api/v2/buddha/like', {
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
		if ($.isNumber(id)) {
			if (this.data.isReview) {
				$.pushView('/pages/index/detail?id=' + id);
			} else {
				$.pushView('/pages/index/video?id=' + id);
			}
		} else {
			$.pushView('/pages/index/gather?main=' + id);
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
        if (this.data.isReview) this.getData(true);
    },
    //获取数据开始
    getData: function(isReachBottom, callback) {
        wx.showNavigationBarLoading();
		var _this = this,
			data = this.data.data,
			flashes = this.data.flashes,
            offset = this.data.offset;
        if (!isReachBottom) {
            data = [];
            offset = 0;
		}
		let categories = $.storage('categories');
		$.get('/api/v2/buddha', {
			pagesize: 999999,
            offset: offset,
			category_id: this.data.category_id,
			special: 1
        }, function(json) {
            //wx.stopPullDownRefresh();
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
				originUrls: json.data.res.urls
			});
			/*//
			if (!_this.data.categories.length && $.isArray(json.data.categories)) {
				let category_id = Number(json.data.categories[0].id);
				_this.setData({
					categories: json.data.categories,
					//category_id: category_id
				});
				let list = [];
				$.each(json.data.categories, function () {
					list.push({
						name: this.name,
						value: this.id
					});
				});
				$.switchView({
					list: list,
					selected: category_id,
					click: function (value) {
						if (/^\/pages\//.test(value) || /^https?:\/\//.test(value)) {
							_this.handleShowAd({
								currentTarget: {
									dataset: {
										url: value
									}
								}
							});
							return false;
						}
						if (isNaN(value)) return false;
						wx.pageScrollTo({
							scrollTop: 0,
							duration: 0
						});
						_this.setData({
							category_id: value,
							offset: 0
						});
						_this.getData();
					}
				});
			}
			/*/

			if ($.isArray(json.data.list)) {
				let list = [], listName = [], isReview = 0;
				if (json.data.list.length && typeof json.data.list[0].main === 'undefined') isReview = 1;
				_this.setData({
					isReview: isReview
				});
				if (!isReview) {
					$.each(json.data.list, function() {
						if ($.inArray(this.main, listName) === -1) {
							list.push({
								id: this.main.length ? this.main : this.id,
								title: this.main.length ? this.main : this.title,
								pic: this.main.length ? this.main_pic : this.pic
							});
							if (this.main.length) listName.push(this.main);
						}
					});
					list.reverse();
				} else {
					list = json.data.list;
				}
                data = data.concat(list);
				offset += list.length;
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
			if ($.isArray(flashes) && !flashes.length) {
				_this.setData({
					flashes: json.data.flashes
				});
			}
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