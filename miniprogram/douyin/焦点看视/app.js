var $ = require('common/helper.js');

App({
  onLaunch: function () {
    //tt.getAccountInfoSync && (this.globalData.appId = tt.getAccountInfoSync().miniProgram.appId);
    //$.checkUpdate();
  },
  getParam: function () {
    let version = $.envVersion;
    let param = {
      version: $.envVersion == 'development' || $.envVersion == 'preview' ? $.envVersion : version ? version : ''
    };
    //if (this.globalData.appId.length && !tt.getAccountInfoSync) param['appId'] = this.globalData.appId;
    //param['appId'] = 'wx809d8c22c240fcd1';
    return param;
  },
  headerParam: function () {
    let param = {
      'Appid': 'wx809d8c22c240fcd1'
    };
    return param;
  },
  globalData: {
    reseller: 0,
    appId: 'wx809d8c22c240fcd1'
  },
  onPageNotFound: function (res) {
    $.alert('该功能正在开发中');
  }
});