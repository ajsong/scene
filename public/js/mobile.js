window.$ = jQuery;
let system = {win:false, mac:false, xll:false, ipad:false}, p = navigator.platform.toLowerCase();
system.win = p.indexOf('win') > -1;
system.mac = p.indexOf('mac') > -1;
system.x11 = (p === 'x11') || (p.indexOf('linux') > -1 && p.indexOf('linux aarch') === -1 && p.indexOf('linux arm') === -1);
system.ipad = navigator.userAgent.match(/iPad/i) !== null;
if (system.win || system.mac || system.x11 || system.ipad) {
	//location.href = '/url';
}

function wxShareInit(jssdk, timelineCallback, friendCallback, debug){
	if($(document.body).data('wxShareInit'))return;
	$(document.body).data('wxShareInit', true);
	if(typeof jssdk === 'string')jssdk = jssdk.substr(0, 5) === 'Mario' ? JSON.parse($.base64Decode(jssdk.substr(5))) : JSON.parse(jssdk);
	if(debug)console.log(jssdk);
	wx.config({
		debug: !!debug,
		appId: jssdk.appId,
		timestamp: jssdk.timestamp,
		nonceStr: jssdk.nonceStr,
		signature: jssdk.signature,
		jsApiList: [
			'checkJsApi',
			'hideAllNonBaseMenuItem',
			'showMenuItems',
			//'updateTimelineShareData',
			//'updateAppMessageShareData',
			'onMenuShareTimeline',
			'onMenuShareAppMessage'
		]
	});
	wx.ready(function(){
		//wx.hideAllNonBaseMenuItem();
		//wx.showMenuItems({menuList:['menuItem:share:timeline', 'menuItem:share:appMessage']});
		let config = {
			title: jssdk.share.title,
			desc: jssdk.share.desc,
			link: jssdk.share.link,
			imgUrl: jssdk.share.img,
			type: 'link',
			dataUrl: '',
			cancel: function(){ }
		};
		let timelineConfig = $.extend({}, config, {
			success: function(res){
				if($.isFunction(timelineCallback))timelineCallback(res);
			}
		});
		let appmessageConfig = $.extend({}, config, {
			success: function(res){
				if(!$.isFunction(friendCallback))friendCallback = timelineCallback;
				if($.isFunction(friendCallback))friendCallback(res);
			}
		});
		//wx.updateTimelineShareData(timelineConfig);
		//wx.updateAppMessageShareData(appmessageConfig);
		wx.onMenuShareTimeline(timelineConfig);
		wx.onMenuShareAppMessage(appmessageConfig);
	});
}
