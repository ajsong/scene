var $ = require('../../common/helper.js');
Page({
    data: {
		data: null,
		review: 1,
		bgimg: '',
		bg: '',
		addMy: 0,
		addMyed: 0,
		backgroundPosition: '',
		audio: null,
		isPlaying: 'isPlaying',
		musicPosition: '',
		manualStop: false
    },
	options: null,
	isShareClose: false,
    onLoad: function(options) {
		let _this = this;
        if (options.reseller) {
            getApp().globalData.reseller = options.reseller;
            delete options.reseller;
        }
		this.options = options;
		if ($.isX()) {
			this.setData({
				backgroundPosition: 'background-position:center bottom;',
				musicPosition: 'music-position'
			});
		}
		$.get('/api/v3/buddhaaudio/categories', function(json){
			wx.loadFontFace({
				family: 'diyfont',
				source: 'url("' + json.data.font + '")'
			});

			$.storage('share_bg', json.data.share);
			$.storage('play_percent', json.data.play_percent);
			$.storage('check_share_group', json.data.check_share_group);

			_this.setData({
				data: json.data.categories,
				review: json.data.review,
				bgimg: json.data.bgimg,
				bg: json.data.bg
			});

			let addMy = json.data.addmy;
			if (!_this.data.addMyed) {
				_this.setData({
					addMy: addMy
				});
			}

			if (json.data.audio.length) {
				let audio = wx.createInnerAudioContext();
				audio.src = json.data.audio;
				audio.loop = true;
				audio.autoplay = true;
				_this.setData({
					audio: audio
				});
				_this.data.audio.play();
			}

			$.storage('first_share_count', Number(json.data.first_share_count));
			$.storage('mod_share_count', Number(json.data.mod_share_count));
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
		if (this.data.audio) {
			if (!getApp().globalData.player.src && !this.data.manualStop) {
				this.data.audio.play();
				this.setData({
					isPlaying: 'isPlaying'
				});
			}
		}
	},
	onHide: function () {
		if (this.data.audio) {
			//this.data.audio.stop();
			//this.data.audio.destroy();
			this.data.audio.pause();
			this.setData({
				isPlaying: ''
			});
		}
	},
	handleAddMy: function() {
		this.setData({
			addMy: 0,
			addMyed: 1
		});
	},
	handleMusic: function () {
		if (getApp().globalData.player.src) return;
		if (this.data.isPlaying.length) {
			this.setData({
				isPlaying: '',
				manualStop: true
			});
			if (this.data.audio) this.data.audio.pause();
		} else {
			this.setData({
				isPlaying: 'isPlaying',
				manualStop: false
			});
			if (this.data.audio) this.data.audio.play();
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
    onShareAppMessage: function(res) {
        var _this = this,
			title = $.extConfig.shareTitle ? $.extConfig.shareTitle : '',
			imageUrl = $.extConfig.shareImageUrl ? $.extConfig.shareImageUrl : '',
            path = this.route + '?appId=' + getApp().globalData.appId;
		this.isShareClose = true;
        return {
            title: title,
            imageUrl: imageUrl,
            path: path
        }
    }
})