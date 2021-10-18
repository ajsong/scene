var $ = require('common/helper.js');
App({
	onLaunch: function() {
		wx.getAccountInfoSync && (this.globalData.appId = wx.getAccountInfoSync().miniProgram.appId);
		$.checkUpdate();
	},
	getParam: function() {
		let version = wx.getExtConfigSync ? wx.getExtConfigSync().version : '';
		let param = {
			version: ($.envVersion == 'develop' || $.envVersion == 'trial') ? $.envVersion : (version ? version : '')
		};
		if (this.globalData.appId.length && !wx.getAccountInfoSync) param['appId'] = this.globalData.appId;
		return param;
	},
	globalData: {
		reseller: 0,
		appId: __wxConfig.accountInfo.appId
	},
	onPageNotFound: function(res) {
		$.alert('该功能正在开发中');
	}
})