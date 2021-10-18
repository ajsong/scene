var $ = require('../../common/helper.js');
Page({
    data: {
        data: ''
    },
    options: null,
    onLoad: function(options) {
        if (options.appId && options.appId.length) {
            getApp().globalData.appId = options.appId;
        }
        this.options = options;
    },
    inputEmail: function(e) {
        this.setData({
            data: e.detail.value
        });
    },
    sendEmail: function() {
        if (!this.data.data.length) {
            $.overloadError('Please input the email address');
            return;
        }
        $.overload();
        setTimeout(()=>{
            $.overloadSuccess('Send Success');
            this.setData({
                data: ''
            });
        }, 1000);
    },
    onShareAppMessage: function(res) {
        return {
            title: $.wxConfig.accountInfo.nickname,
            imageUrl: $.wxConfig.accountInfo.icon,
            path: this.route + '?appId=' + getApp().globalData.appId
        }
    },
	onShareTimeline: function() {
		return {
			//query: '',
			title: $.extConfig.shareTitle ? $.extConfig.shareTitle : $.wxConfig.accountInfo.nickname,
			imageUrl: $.extConfig.shareImageUrl ? $.extConfig.shareImageUrl : $.wxConfig.accountInfo.icon
		}
	}
})