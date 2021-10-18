var $ = require('../../common/helper.js');
Page({
    data: {
		data: [],
        offset: 0,
        loadmore: ''
    },
    options: null,
    onLoad: function(options) {
        if (options.reseller) {
            getApp().globalData.reseller = options.reseller;
            delete options.reseller;
        }
		this.options = options;
		$.setTitle(options.main);
        $.overload();

		let _this = this, data = [], offset = this.data.offset;
		$.get('/api/v2/buddha/gather', {
			main: options.main,
			pagesize: 999999,
			offset: offset,
			special: 1
        }, function(json) {
			wx.hideNavigationBarLoading();
			if ($.isArray(json.data.list)) {
                data = json.data.list;
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
        });
    },
	handleShowDetail: function (e) {
		let id = e.currentTarget.dataset.id, data = this.data.data[e.currentTarget.dataset.index];
		if ($.isNumber(id)) {
			$.pushView('/pages/index/video?id=' + id);
		} else {
			$.pushView('/pages/index/gather?main=' + id);
		}
	},
    onShareAppMessage: function(res) {
        var person = $.storage('person'),
            querystring = person ? '?reseller=' + person.id : '';
        if (this.options) {
			$.each(this.options, function (key) {
				if (key != 'reseller') {
					querystring += (querystring.length ? '&' : '?') + key + '=' + this;
				}
            });
        }
        return {
            title: $.config.shareTitle,
            imageUrl: $.config.shareImageUrl,
            path: this.route + querystring
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