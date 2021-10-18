var $ = require('../../common/helper.js');
Page({
    data: {
        data: null
    },
    options: null,
    onLoad: function(options) {
        if (options.reseller) {
            getApp().globalData.reseller = options.reseller;
            delete options.reseller;
        }
        this.options = options;
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
    }
})