var $ = require('../../common/helper.js');
Page({
    data: {
		title: '',
		bg: '',
		trans_placeholder_hide: '',
		data: [],
        review: 0,
        offset: 0,
        loadmore: '',
		category_id: 0,
		count: 0,
        clicks: '',
		trans: null,
		listBg: '',
		nav_shown: '',
		nav_x: '',
		header_x: '',
		view_x: ''
	},
	isShareClose: false,
    onLoad: function(options) {
		if (options.appId && options.appId.length) {
			getApp().globalData.appId = options.appId;
		}
        var _this = this,
            category_id = options.category_id || 0;
		category_id = Number(category_id);
		
		if ($.isX()) {
			this.setData({
				nav_x: 'nav-x',
				header_x: 'header-x',
				view_x: 'view-x'
			});
		}
		$.get('/api/v3/buddhaaudio/category?id=' + category_id, function(json){
			$.setTitle(json.data.category.name);
			_this.setData({
				title: json.data.category.name,
				bg: $.urldecode(json.data.category.pic),
				category_id: category_id
			});
			wx.startPullDownRefresh();
		});
    },
	onShow: function () {
		if ($.storage('check_share_group')) {
			$.shareGroup();
			if (this.isShareClose) {
				if (this.data.shareView && this.data.shareView.show) $.alert('请点击刚才发送到群的链接，即可立即播放');
				getApp().showShare(false);
			}
		} else {
			if (this.isShareClose) {
				getApp().showShare(false);
				getApp().getAudio($.storage('current_id'));
			}
		}
		this.isShareClose = false;
	},
	onPageScroll: function (e) {
		let _this = this;
		if (e.scrollTop > $.toPx(75)) {
			_this.setData({
				trans_placeholder_hide: 'trans-placeholder-hide'
			});
		} else {
			_this.setData({
				trans_placeholder_hide: ''
			});
		}
		if (e.scrollTop > $.toPx(220)) {
			_this.setData({
				nav_shown: 'nav-shown'
			});
		} else {
			_this.setData({
				nav_shown: ''
			});
		}
	},
	handleGoHome: function (e) {
		let pages = getCurrentPages();
		if (pages.length == 1) {
			wx.reLaunch({
				url: '/pages/index/category'
			});
		} else {
			$.popView();
		}
	},
	handleShowDetail: function (e) {
		let url = e.currentTarget.dataset.url;
		if (url) {
			$.pushView(url);
			return;
		}
		let id = e.currentTarget.dataset.id, type = e.currentTarget.dataset.type;
		if (type == 5) {
			$.pushView('/pages/index/video?id=' + id);
		} else {
			$.pushView('/pages/index/detail?id=' + id);
		}
	},
	handlePlayAll: function (e) {
		let url = e.currentTarget.dataset.url;
		if (url) {
			$.storage(['current_id', 'current_title', 'current_music', 'next_id'], null);
			getApp().globalData.player.stop();
			$.pushView(url);
			return;
		}
		let id = e.currentTarget.dataset.id, type = e.currentTarget.dataset.type;
		if (type == 5) {
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
		$.get('/api/v3/buddhaaudio', {
            offset: offset,
            category_id: this.data.category_id
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
				data: data,
				review: json.data.review,
				count: json.data.count,
				//clicks: json.data.clicks + '',
				clicks: '',
				listBg: json.data.bg,
				trans: json.data.trans,
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
		this.isShareClose = true;
        return {
            title: title,
            imageUrl: imageUrl,
            path: path
        }
    }
})