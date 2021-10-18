var $ = require('common/helper.js');
App({
	onLaunch: function () {
		wx.getAccountInfoSync && (this.globalData.appId = wx.getAccountInfoSync().miniProgram.appId);
		$.storage(['current_id', 'next_id', 'audio_id', 'current_title', 'current_music'], null);
		$.checkUpdate();
	},
	onShow: function (res) {
		let _this = this;
		if ($.storage('check_share_group')) {
			$.shareGroup(res, 1044, function() {
				$.storage('first_share', 1);
				$.storage('mod_count', 0);
				let apps = getCurrentPages();
				if (!apps || !$.isArray(apps) || !apps.length) return;
				let app = apps[apps.length - 1];
				if (app.route.indexOf('/detail') > -1) {
					app.handleLike();
					_this.audioInit(app.data.data.id, app.data.data.title, app.data.data.music);
				} else {
					_this.getAudio($.storage('current_id'));
				}
			});
		}
	},
	getParam: function () {
		let version = wx.getExtConfigSync ? wx.getExtConfigSync().version : '';
		let param = {
			version: ($.envVersion == 'develop' || $.envVersion == 'trial') ? $.envVersion : (version ? version : '')
		};
		if (this.globalData.appId.length && !wx.getAccountInfoSync) param['appId'] = this.globalData.appId;
		return param;
	},
	globalData: {
		reseller: 0,
		player: wx.getBackgroundAudioManager(),
		originReturnChanged: false,
		originReturnClass: '',
		appId: ''
	},
	haveShare: function() {
		let first_share_count = $.storage('first_share_count'), mod_share_count = $.storage('mod_share_count');
		if (first_share_count <= 0) return true;
		let first_count = $.storage('first_count'), first_share = $.storage('first_share');
		if (!first_count) first_count = 0;
		if (!first_share) first_share = 0;
		if (first_count >= first_share_count) {
			if (first_share == 0) {
				this.showShare();
				return false;
			}
			if (mod_share_count <= 0) return true;
			let mod_count = $.storage('mod_count');
			if (!mod_count) mod_count = 0;
			if (mod_count >= mod_share_count) {
				this.showShare();
				return false;
			}
			//mod_count++;
			//$.storage('mod_count', mod_count);
		} else {
			//first_count++;
			//$.storage('first_count', first_count);
		}
		return true;
	},
    showShare: function(shown) {
		if (!$.storage('current_id')) return;
		let apps = getCurrentPages();
        if (!apps || !$.isArray(apps) || !apps.length) return;
		let app = apps[apps.length - 1];
        if (typeof shown == 'boolean' && !shown) {
			if (!$.storage('check_share_group')) {
				$.storage('first_share', 1);
				$.storage('mod_count', 0);
			}
			app.setData({
				shareView: {
					show: false
				}
			});
            return;
        }
		app.setData({
			shareView: {
				show: true,
				bg: $.storage('share_bg')
			}
		});
		if ($.isX()) {
			app.setData({
				'shareView.x': 'share-x'
			});
		}
		if (app.route.indexOf('/detail') > -1) {
			this.globalData.originReturnClass = app.data.returnWhite;
			app.setData({
				originReturnChanged: true,
				returnWhite: 'return-white'
			});
		}
    },
	getAudio: function (id) {
		if (!$.storage('current_id')) return;
		let _this = this;
		let apps = getCurrentPages();
		if (!apps || !$.isArray(apps) || !apps.length || !id) return;
		$.get('/api/v3/buddhaaudio/getMusic?id=' + id, function (json) {
			$.storage('current_id', Number(json.data.id));
			$.storage('current_title', json.data.title);
			$.storage('current_music', json.data.music);
			$.storage('next_id', Number(json.data.next_id));
			let cateApp = apps[0];
			if (cateApp.route.indexOf('/category') > -1) {
				cateApp.setData({
					isPlaying: ''
				});
				if (cateApp.data.audio) cateApp.data.audio.pause();
			}
			_this.audioInit(json.data.id, json.data.title, json.data.music);
		});
	},
	//初始化背景音乐
	audioInit: function (id, title, music) {
		if (!$.storage('current_id')) return;
		let _this = this;
		let apps = getCurrentPages();
		if (!apps || !$.isArray(apps) || !apps.length || !id) return;
		let app = apps[apps.length - 1];
		if (app.route.indexOf('/detail') > -1) {
			app.setData({
				originReturnChanged: false,
				audioEnded: false
			});
		}
		let audioId = $.storage('audio_id');
		if (audioId && Number(audioId) == Number(id) && this.globalData.player.src) {
			this.globalData.player.play();
			return;
		}
		if (audioId && Number(audioId) != Number(id)) {
			app.setData({
				audioTime: '00:00',
				audioOffset: 0,
				audioDuration: '00:00',
				audioMax: 0
			});
		}
		let first_share_count = $.storage('first_share_count'), mod_share_count = $.storage('mod_share_count');
		if (first_share_count <= 0) return;
		let first_count = $.storage('first_count'), first_share = $.storage('first_share');
		if (!first_count) first_count = 0;
		if (!first_share) first_share = 0;
		if (first_count >= first_share_count) {
			if (mod_share_count <= 0) return;
			let mod_count = $.storage('mod_count');
			if (!mod_count) mod_count = 0;
			mod_count++;
			$.storage('mod_count', mod_count);
		} else {
			first_count++;
			$.storage('first_count', first_count);
		}
		$.storage('audio_id', Number(id));
		let player = this.globalData.player;
		player.title = title; //iOS必须加title,否则会报错导致音乐不播放
		player.epname = title;
		player.src = music;
		player.seek(0);
		player.onTimeUpdate(() => {
			apps = getCurrentPages();
			app = apps[apps.length - 1];
			if (parseInt(player.currentTime) / parseInt(player.duration) >= parseInt($.storage('play_percent'))/100) {
				if (!_this.haveShare()) {
					player.pause();
					return;
				}
			}
			if (app.route.indexOf('/detail') > -1) {
				if ($.storage('current_id') == app.data.data.id) {
					let offset = parseInt(player.currentTime), minute = parseInt(offset / 60), second = offset % 60;
					if (minute < 10) minute = '0' + minute;
					if (second < 10) second = '0' + second;
					let max = parseInt(player.duration), _minute = parseInt(max / 60), _second = max % 60;
					if (_minute < 10) _minute = '0' + _minute;
					if (_second < 10) _second = '0' + _second;
					app.setData({
						audioTime: minute + ':' + second,
						audioOffset: offset,
						audioDuration: _minute + ':' + _second,
						audioMax: max,
						audioPlay: true,
						audioGoOn: true
					});
				}
			}
		});
		player.onPlay(() => {
			apps = getCurrentPages();
			app = apps[apps.length - 1];
			if (app.route.indexOf('/detail') > -1) {
				if ($.storage('current_id') == app.data.data.id) {
					app.setData({
						audioPlay: true
					});
				}
				app.setData({
					audioGoOn: true
				});
			}
		});
		player.onPause(() => {
			apps = getCurrentPages();
			app = apps[apps.length - 1];
			if (app.route.indexOf('/detail') > -1) {
				setTimeout(function(){
					app.setData({
						audioPlay: false,
						audioGoOn: false
					});
				}, 300);
			}
		});
		player.onEnded(() => {
			apps = getCurrentPages();
			app = apps[apps.length - 1];
			player.seek(0);
			if (apps[apps.length-1].route.indexOf('/detail') > -1) {
				app.setData({
					audioTime: '00:00',
					audioOffset: 0,
					audioEnded: true
				});
				setTimeout(function(){
					app.setData({
						audioPlay: false
					});
				}, 300);
			}
			let nextId = $.storage('next_id');
			if (nextId) {
				if (apps[apps.length-1].route.indexOf('/detail') > -1) {
					wx.redirectTo({
						url: '/pages/index/detail?id=' + nextId
					});
				} else {
					setTimeout(function () {
						if (_this.haveShare()) {
							_this.getAudio(nextId);
						}
					}, 500);
				}
			}
		});
	},
	onPageNotFound: function (res) {
		$.alert('该功能正在开发中');
	}
})