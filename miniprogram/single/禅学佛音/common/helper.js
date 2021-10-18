/*
Developed by @jsong v1.7.20200404
*/
let config = require('config.js'),
    WxParse = require('libs/wxParse/wxParse.js'),
    MD5 = require('libs/md5.js');
module.exports = {
    //配置参数
    config: config,
	extConfig: wx.getExtConfigSync ? wx.getExtConfigSync() : {},
    wxConfig: __wxConfig ? __wxConfig : {},
	envVersion: __wxConfig.envVersion ? __wxConfig.envVersion : '', //develop开发版，trial体验版，release正式版
    wxSDKVersion: wx.getSystemInfoSync().SDKVersion,
    log: console.log,
    WxParse: WxParse, //使用 rich-text 代替性能会更好
    md5: function(str) {
        return MD5.hexMD5(str)
    },
    //网络请求回调(成功)
    callbackSuccess: function(json, type) {
        let app = getApp();
        if (this.isFunction(app.callbackSuccess)) app.callbackSuccess(json, type);
    },
    //网络请求回调(失败)
    callbackError: function(json, type) {
		let app = getApp();
        if (this.isFunction(app.callbackError)) app.callbackError(json, type);
    },
    //GET网络请求
    get: function(url, data, callback) {
	    if (!config.apiUrl.length) {
		    let $ = this;
		    setTimeout(function(){
			    $.get(url, data, callback);
		    }, 300);
		    return;
	    }
	    let $ = this,
		    person = $.storage('person'),
		    success = null,
		    error = null,
		    complete = null,
		    header = {
			    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
		    };
        if (!/^http/.test(url)) {
            if (config.apiUrl.substr(-1, 1) != '/' && url.substr(0, 1) != '/') url = '/' + url;
            url = config.apiUrl + url;
	        let param = {};
	        if (url.indexOf('sign=') == -1 && person && person.sign) {
		        param['sign'] = person.sign;
	        }
	        if (config.aesKey.length) {
	        	let timestamp = Date.parse(new Date()) / 1000;
		        param['timestamp'] = timestamp;
		        header['Token'] = $.md5(config.aesKey+$.md5(timestamp+''));
	        }
	        if ($.isPlainObject(config.getParam) && !$.isEmptyObject(config.getParam)) {
		        for (let key in config.getParam) {
			        param[key] = config.getParam[key];
		        }
	        }
	        let _app = getApp();
	        if (_app) {
		        let getParam = _app.getParam;
		        if (getParam) {
			        if ($.isFunction(getParam)) {
				        getParam = getParam();
			        }
			        if ($.isPlainObject(getParam) && !$.isEmptyObject(getParam)) {
				        for (let key in getParam) {
					        param[key] = getParam[key];
				        }
			        }
		        }
	        }
	        if ($.isPlainObject(param) && !$.isEmptyObject(param)) {
		        for (let key in param) {
			        url += (url.indexOf('?') > -1 ? '&' : '?') + key + '=' + param[key];
		        }
	        }
        }
        let session_id = $.storage('session_id');
        if (!session_id || !session_id.length) session_id = "PHPSESSID=''; JSESSIONID=''";
	    if (session_id && session_id.length) header['Cookie'] = session_id;
	    if (wx.getAccountInfoSync) header['Appid'] = wx.getAccountInfoSync().miniProgram.appId;
        if ($.isFunction(data) && typeof (callback) === 'undefined') {
            success = data;
            data = null;
        } else if ($.isPlainObject(data) && typeof (callback) === 'undefined') {
            if ($.isFunction(data.success)) success = data.success;
            if ($.isFunction(data.error)) error = data.error;
            if ($.isFunction(data.complete)) complete = data.complete;
            data = null;
        } else if ($.isFunction(callback)) {
            success = callback;
        } else if ($.isPlainObject(callback)) {
            if ($.isFunction(callback.success)) success = callback.success;
            if ($.isFunction(callback.error)) error = callback.error;
            if ($.isFunction(callback.complete)) complete = callback.complete;
	        if ($.isPlainObject(callback.header) && !$.isEmptyObject(callback.header)) {
		        for (let key in callback.header) {
			        header[key] = callback.header[key];
		        }
	        }
        }
		let timer = setTimeout(function(){$.overload()}, 800);
		let task = wx.request({
            url: url,
            method: 'GET',
            data: data,
            dataType: 'STRING',
            header: header,
            success: function(res) {
	            if (typeof res.header['Set-Cookie'] != 'undefined') {
		            $.storage('session_id', res.header['Set-Cookie']);
	            }
                $.overload(false);
				let json = res.data.trim();
                try {
                    json = JSON.parse(json);
                    if (typeof json.error != 'undefined' && json.error != 0) {
                        if (typeof json.msg != 'undefined') {
                            setTimeout(function() {
                                $.overloadError(json.msg);
                            }, 350);
                        }
                        if ($.isFunction(error)) error();
                        $.callbackError(json, 'GET');
                        return;
                    }
                    if ($.isFunction(success)) success(json);
                    $.callbackSuccess(json, 'GET');
                } catch (e) {
                    console.log(e);
                    console.log(url);
                    console.log(res);
                    if ($.isFunction(error)) error();
                }
            },
            fail: function(res) {
                console.log(res);
                if ($.isFunction(error)) {
	                error();
                } else {
                    $.overloadError('数据错误');
                }
            },
            complete: function() {
                clearTimeout(timer); timer = null;
                if ($.isFunction(complete)) complete();
            }
        });
        return task;
        //task.abort(); //中断任务
    },
    //POST网络请求
    post: function(url, data, callback) {
	    if (!config.apiUrl.length) {
		    let $ = this;
		    setTimeout(function(){
			    $.post(url, data, callback);
		    }, 300);
		    return;
	    }
		let $ = this,
            person = $.storage('person'),
            success = null,
			error = null,
            complete = null,
			header = {
			    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
		    };
        if (!/^http/.test(url)) {
            if (config.apiUrl.substr(-1, 1) != '/' && url.substr(0, 1) != '/') url = '/' + url;
            url = config.apiUrl + url;
	        let param = {};
	        if (url.indexOf('sign=') == -1 && person && person.sign) {
		        param['sign'] = person.sign;
	        }
	        if (config.aesKey.length) {
		        let timestamp = Date.parse(new Date()) / 1000;
		        param['timestamp'] = timestamp;
		        header['Token'] = $.md5(config.aesKey+$.md5(timestamp+''));
	        }
	        if ($.isPlainObject(config.getParam) && !$.isEmptyObject(config.getParam)) {
		        for (let key in config.getParam) {
			        param[key] = config.getParam[key];
		        }
	        }
	        let _app = getApp();
	        if (_app) {
		        let getParam = _app.getParam;
		        if (getParam) {
			        if ($.isFunction(getParam)) {
				        getParam = getParam();
			        }
			        if ($.isPlainObject(getParam) && !$.isEmptyObject(getParam)) {
				        for (let key in getParam) {
					        param[key] = getParam[key];
				        }
			        }
		        }
	        }
	        if ($.isPlainObject(param) && !$.isEmptyObject(param)) {
		        for (let key in param) {
			        url += (url.indexOf('?') > -1 ? '&' : '?') + key + '=' + param[key];
		        }
	        }
        }
        //为兼容数组提交
	    let postData = [];
	    let param = {};
	    if ($.isPlainObject(config.postParam) && !$.isEmptyObject(config.postParam)) {
		    for (let key in config.postParam) {
			    let item = config.postParam[key];
			    if ($.isArray(item)) {
				    $.each(item, function () {
					    postData.push(key + '[]=' + this);
				    });
			    } else {
				    param[key] = item;
			    }
		    }
	    }
	    let _app = getApp();
	    if (_app) {
		    let postParam = _app.postParam;
		    if (postParam) {
			    if ($.isFunction(postParam)) {
				    postParam = postParam();
			    }
			    if ($.isPlainObject(postParam) && !$.isEmptyObject(postParam)) {
				    for (let key in postParam) {
					    let item = postParam[key];
					    if ($.isArray(item)) {
						    $.each(item, function () {
							    postData.push(key + '[]=' + this);
						    });
					    } else {
						    param[key] = item;
					    }
				    }
			    }
		    }
	    }
	    for (let key in data) {
		    let item = data[key];
		    if ($.isArray(item)) {
			    $.each(item, function() {
				    postData.push(key + '[]=' + this);
			    });
		    } else {
			    param[key] = item;
		    }
	    }
	    if ($.isPlainObject(param) && !$.isEmptyObject(param)) {
		    for (let key in param) {
			    postData.push(key + '=' + param[key]);
		    }
	    }
        data = postData.join('&');
	    let session_id = $.storage('session_id');
        if (!session_id || !session_id.length) session_id = "PHPSESSID=''; JSESSIONID=''";
	    if (session_id && session_id.length) header['Cookie'] = session_id;
	    if (wx.getAccountInfoSync) header['Appid'] = wx.getAccountInfoSync().miniProgram.appId;
        if ($.isFunction(callback)) {
            success = callback;
        } else if ($.isPlainObject(callback)) {
            if ($.isFunction(callback.success)) success = callback.success;
            if ($.isFunction(callback.error)) error = callback.error;
            if ($.isFunction(callback.complete)) complete = callback.complete;
	        if ($.isPlainObject(callback.header) && !$.isEmptyObject(callback.header)) {
		        for (let key in callback.header) {
			        header[key] = callback.header[key];
		        }
	        }
        }
		let timer = setTimeout(function(){$.overload()}, 800);
		let task = wx.request({
            url: url,
            method: 'POST',
            data: data,
            dataType: 'STRING',
            header: header,
            success: function(res) {
	            if (typeof res.header['Set-Cookie'] != 'undefined') {
		            $.storage('session_id', res.header['Set-Cookie']);
	            }
                $.overload(false);
				let json = res.data.trim();
                try {
                    json = JSON.parse(json);
                    if (typeof json.error != 'undefined' && json.error != 0) {
                        if (typeof json.msg != 'undefined') {
                            setTimeout(function() {
                                $.overloadError(json.msg);
                            }, 350);
                        }
                        if ($.isFunction(error)) error();
                        $.callbackError(json, 'POST');
                        return;
                    }
                    if ($.isFunction(success)) success(json);
                    $.callbackSuccess(json, 'POST');
                } catch (e) {
                    console.log(e);
                    console.log(url);
                    console.log(res);
                    if ($.isFunction(error)) error();
                }
            },
            fail: function(res) {
                console.log(res);
                if ($.isFunction(error)) {
	                error();
                } else {
                    $.overloadError('数据错误');
                }
            },
            complete: function() {
                clearTimeout(timer); timer = null;
                if ($.isFunction(complete)) complete();
            }
        });
        return task;
    },
    //UPLOAD网络请求
    upload: function(url, options, callback, progress) {
	    if (!config.apiUrl.length) {
		    let $ = this;
		    setTimeout(function(){
			    $.upload(url, options, callback, progress);
		    }, 300);
		    return;
	    }
        if (!this.isJson(options) || typeof(options.path) == 'undefined') {
            this.overloadError('缺少要上传文件资源的路径');
            return;
        }
		let $ = this,
            person = $.storage('person'),
            success = null,
			error = null,
            complete = null,
			header = {
			    'Content-Type': 'multipart/form-data; charset=UTF-8'
		    };
        if (!/^http/.test(url)) {
            if (config.apiUrl.substr(-1, 1) != '/' && url.substr(0, 1) != '/') url = '/' + url;
            url = config.apiUrl + url;
	        let param = {};
	        if (url.indexOf('sign=') == -1 && person && person.sign) {
		        param['sign'] = person.sign;
	        }
	        if (config.aesKey.length) {
		        let timestamp = Date.parse(new Date()) / 1000;
		        param['timestamp'] = timestamp;
		        header['Token'] = $.md5(config.aesKey+$.md5(timestamp+''));
	        }
	        if ($.isPlainObject(config.getParam) && !$.isEmptyObject(config.getParam)) {
		        for (let key in config.getParam) {
			        param[key] = config.getParam[key];
		        }
	        }
	        let _app = getApp();
	        if (_app) {
		        let getParam = _app.getParam;
		        if (getParam) {
			        if ($.isFunction(getParam)) {
				        getParam = getParam();
			        }
			        if ($.isPlainObject(getParam) && !$.isEmptyObject(getParam)) {
				        for (let key in getParam) {
					        param[key] = getParam[key];
				        }
			        }
		        }
	        }
	        if ($.isPlainObject(param) && !$.isEmptyObject(param)) {
		        for (let key in param) {
			        url += (url.indexOf('?') > -1 ? '&' : '?') + key + '=' + param[key];
		        }
	        }
        }
        if (options.field && $.isPlainObject(options.field)) {
			let u = '';
            $.each(options.field, function(key) {
                u += '&' + key + '=' + $.urlencode(this);
            });
            u = $.trim(u, '&');
            url += (url.indexOf('?') > -1 ? '&' : '?') + u;
        }
	    let session_id = $.storage('session_id');
        if (!session_id || !session_id.length) session_id = "PHPSESSID=''; JSESSIONID=''";
	    if (session_id && session_id.length) header['Cookie'] = session_id;
	    if (wx.getAccountInfoSync) header['Appid'] = wx.getAccountInfoSync().miniProgram.appId;
        if ($.isFunction(callback)) {
            success = callback;
        } else if ($.isPlainObject(callback)) {
            if ($.isFunction(callback.success)) success = callback.success;
            if ($.isFunction(callback.error)) error = callback.error;
            if ($.isFunction(callback.complete)) complete = callback.complete;
	        if ($.isPlainObject(callback.header) && !$.isEmptyObject(callback.header)) {
		        for (let key in callback.header) {
			        header[key] = callback.header[key];
		        }
	        }
        }
		let task = wx.uploadFile({
            url: url,
            filePath: options.path,
            name: options.name || 'filename',
            formData: options.data || null,
            dataType: 'STRING',
            header: header,
            success: function(res) {
	            if (typeof res.header['Set-Cookie'] != 'undefined') {
		            $.storage('session_id', res.header['Set-Cookie']);
	            }
                $.overload(false);
				let json = res.data.trim();
                try {
                    json = JSON.parse(json);
                    if (typeof json.error != 'undefined' && json.error != 0) {
                        if (typeof json.msg != 'undefined') {
                            setTimeout(function() {
                                $.overloadError(json.msg);
                            }, 350);
                        }
                        if ($.isFunction(error)) error();
                        $.callbackError(json, 'UPLOAD');
                        return;
                    }
                    if ($.isFunction(success)) success(json);
                    $.callbackSuccess(json, 'UPLOAD');
                } catch (e) {
                    console.log(e);
                    console.log(url);
                    console.log(res);
                    if ($.isFunction(error)) error();
                }
            },
            fail: function(res) {
                console.log(res);
                if ($.isFunction(error)) {
	                error();
                } else {
                    $.overloadError('数据错误');
                }
            },
            complete: function() {
                if ($.isFunction(complete)) complete();
            }
        });
        if (this.isFunction(progress)) {
            task.onProgressUpdate(function(res) {
                progress(res);
                //res.progress//进度百分比
                //res.totalBytesSent//已上传的数据长度,单位Bytes
                //res.totalBytesExpectedToSend//预期需要上传的数据总长度,单位Bytes
            });
        }
        return task;
    },
    //DOWNLOAD网络请求
    download: function(url, saveFile, callback, progress) {
	    if (!config.apiUrl.length) {
		    let $ = this;
		    setTimeout(function(){
			    $.download(url, saveFile, callback, progress);
		    }, 300);
		    return;
	    }
		let $ = this,
            person = $.storage('person'),
            success = null,
			error = null,
            complete = null,
			header = {
			    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
		    };
        if (!/^http/.test(url)) {
            if (config.apiUrl.substr(-1, 1) != '/' && url.substr(0, 1) != '/') url = '/' + url;
            url = config.apiUrl + url;
		    let param = {};
		    if (url.indexOf('sign=') == -1 && person && person.sign) {
			    param['sign'] = person.sign;
		    }
		    if (config.aesKey.length) {
			    let timestamp = Date.parse(new Date()) / 1000;
			    param['timestamp'] = timestamp;
			    header['Token'] = $.md5(config.aesKey+$.md5(timestamp+''));
		    }
		    if ($.isPlainObject(config.getParam) && !$.isEmptyObject(config.getParam)) {
			    for (let key in config.getParam) {
				    param[key] = config.getParam[key];
			    }
		    }
		    let _app = getApp();
		    if (_app) {
			    let getParam = _app.getParam;
			    if (getParam) {
				    if ($.isFunction(getParam)) {
					    getParam = getParam();
				    }
				    if ($.isPlainObject(getParam) && !$.isEmptyObject(getParam)) {
					    for (let key in getParam) {
						    param[key] = getParam[key];
					    }
				    }
			    }
		    }
		    if ($.isPlainObject(param) && !$.isEmptyObject(param)) {
			    for (let key in param) {
				    url += (url.indexOf('?') > -1 ? '&' : '?') + key + '=' + param[key];
			    }
		    }
	    }
	    let session_id = $.storage('session_id');
        if (!session_id || !session_id.length) session_id = "PHPSESSID=''; JSESSIONID=''";
	    if (session_id && session_id.length) header['Cookie'] = session_id;
	    if (wx.getAccountInfoSync) header['Appid'] = wx.getAccountInfoSync().miniProgram.appId;
        if ($.isFunction(saveFile)) {
            progress = callback;
            success = saveFile;
            saveFile = false;
        } else if ($.isPlainObject(saveFile) && !$.isFunction(callback) && !$.isPlainObject(callback)) {
            progress = callback;
            if ($.isFunction(saveFile.success)) success = saveFile.success;
            if ($.isFunction(saveFile.error)) error = saveFile.error;
            if ($.isFunction(saveFile.complete)) complete = saveFile.complete;
            saveFile = false;
        } else if ($.isFunction(callback)) {
            success = callback;
            saveFile = false;
        } else if ($.isPlainObject(callback)) {
            if ($.isFunction(callback.success)) success = callback.success;
            if ($.isFunction(callback.error)) error = callback.error;
            if ($.isFunction(callback.complete)) complete = callback.complete;
	        if ($.isPlainObject(callback.header) && !$.isEmptyObject(callback.header)) {
		        for (let key in callback.header) {
			        header[key] = callback.header[key];
		        }
	        }
        }
		let task = wx.downloadFile({
            url: url,
            header: header,
            success: function(res) {
                $.overload(false);
                if (res.statusCode !== 200) {
                    if ($.isFunction(error)) error();
                    $.callbackError(res, 'DOWNLOAD');
                } else {
                    if (saveFile) {
                        wx.saveFile({
                            tempFilePath: res.tempFilePath,
                            success: function(res) {
								let savedFilePath = res.savedFilePath;
                                if ($.isFunction(success)) success(savedFilePath);
                            }
                        });
                    } else {
                        if ($.isFunction(success)) success(res.tempFilePath);
                    }
                    $.callbackSuccess(res, 'DOWNLOAD');
                }
            },
            fail: function(res) {
                console.log(res);
                if ($.isFunction(error)) {
	                error();
                } else {
                    $.overloadError('数据错误');
                }
            },
            complete: function() {
                if ($.isFunction(complete)) complete();
            }
        });
        if (this.isFunction(progress)) {
            task.onProgressUpdate(function(res) {
                progress(res);
                //res.progress//进度百分比
                //res.totalBytesWritten//已下载的数据长度,单位Bytes
                //res.totalBytesExpectedToWrite//预期需要下载的数据总长度,单位Bytes
            });
        }
        return task;
    },
    //设置各个tabBar角标
    setTabBarBadge: function(json, tabIndexs) {
        if (typeof tabIndexs == 'undefined') return;
        if (json.red_dot) {
            if (typeof tabIndexs.cart != 'undefined') {
                if (json.member_cart) {
                    wx.showTabBarRedDot({
                        index: tabIndexs.cart
                    });
                } else {
                    wx.hideTabBarRedDot({
                        index: tabIndexs.cart,
                    });
                }
            }
            if (typeof tabIndexs.member != 'undefined') {
                if (json.member_notify) {
                    wx.showTabBarRedDot({
                        index: tabIndexs.member
                    });
                } else {
                    wx.hideTabBarRedDot({
                        index: tabIndexs.member,
                    });
                }
            }
        } else {
            if (typeof tabIndexs.cart != 'undefined') {
                if (json.member_cart) {
                    wx.setTabBarBadge({
                        index: tabIndexs.cart,
                        text: json.member_cart.toString(),
                    });
                } else {
                    wx.removeTabBarBadge({
                        index: tabIndexs.cart,
                    });
                }
            }
            if (typeof tabIndexs.member != 'undefined') {
                if (json.member_notify) {
                    wx.setTabBarBadge({
                        index: tabIndexs.member,
                        text: json.member_notify.toString(),
                    });
                } else {
                    wx.removeTabBarBadge({
                        index: tabIndexs.member,
                    });
                }
            }
        }
    },
    //检测小程序更新，一般在 onLaunch 调用
    checkUpdate: function() {
        if (wx.canIUse('getUpdateManager')) {
			let updateManager = wx.getUpdateManager();
			updateManager.onCheckForUpdate(function (res) {
				if (res.hasUpdate) {
					updateManager.onUpdateReady(function () {
						wx.showModal({
							title: '更新提示',
							content: '新版本已准备好，是否重启小程序？',
							success: function (res) {
								if (res.confirm) {
									//新的版本已经下载好，调用 applyUpdate 应用新版本并重启
									updateManager.applyUpdate();
								}
							}
						});
					});
					updateManager.onUpdateFailed(function () {
						//新的版本下载失败
						wx.showModal({
							title: '已有新版本',
							content: '新版本已上线，请删除当前小程序，重新搜索 “' + __wxConfig.accountInfo.nickname + '” 打开'
						});
					});
				}
			});
		}
	    return this;
    },
    //检测是否已登录
    checkLogin: function(url) {
		let person = this.storage('person');
        //console.log(person)
        if (person) {
            if (typeof url != 'undefined' && url.length) wx.navigateTo({
                url: url
            });
            return true;
        } else {
            wx.navigateTo({
                url: '/pages/global/login'
            });
            return false;
        }
    },
    //清除首尾指定字符串
    trim: function(str, separate) {
        if (str.length) {
            if (typeof separate == 'undefined') {
                return str.replace(/^\s+|\s+$/, '');
            } else if (separate.length) {
				let re = new RegExp('^(' + separate + ')+|(' + separate + ')+$');
                return str.replace(re, '');
            }
        }
        return '';
    },
    //保留n位小数
    round: function(str, num) {
        return this.numberFormat(str, num);
    },
    numberFormat: function(str, num) {
        if (typeof(num) == 'undefined') num = 2;
        return parseFloat(str).toFixed(num);
    },
    //对网址编码
    urlencode: function(url) {
        if (!url.length) return '';
        return encodeURIComponent(url).replace(/!/g, '%21').replace(/'/g, '%27').replace(/\(/g, '%28').replace(/\)/g, '%29').replace(/\*/g, '%2A').replace(/%20/g, '+');
    },
	//对网址解密
	urldecode: function(url) {
		if (!url.length) return '';
		url = url.replace(/%25/g, '%').replace(/%21/g, '!').replace(/%27/g, "'").replace(/%28/g, '(').replace(/%29/g, ')').replace(/%2A/g, '*');
		return decodeURIComponent(url);
	},
	//是否iPhoneX
	isX: function() {
		try {
            let res = wx.getSystemInfoSync();
			if (res.model.toLowerCase().indexOf('iphone x') > -1 || res.safeArea.top > 20) {
				return true;
			} else {
				return false;
			}
		} catch (e) { }
		return false;
	},
    //是否在数组里
    inArray: function(obj, arrayObj) {
		let index = -1;
        if (arrayObj && (arrayObj instanceof Array) && arrayObj.length) {
			for (let i = 0; i < arrayObj.length; i++) {
                if (obj == arrayObj[i]) {
                    index = i;
                    break;
                }
            }
        }
        return index;
    },
    //是否数组
    isArray: function(obj) {
        if (!obj) return false;
        return (obj instanceof Array);
    },
    //是否数字字面量
    isPlainObject: function(obj) {
        if (!obj) return false;
        if (obj && typeof(obj) == 'object' && Object.prototype.toString.call(obj).toLowerCase() == '[object object]' && !obj.length) return true;
        return false;
    },
	//是否空对象
	isEmptyObject: function (obj) {
		return JSON.stringify(obj) == "{}";
	},
    //是否函数
    isFunction: function(func) {
        if (!func) return false;
        return (func instanceof Function);
    },
    //是否数字
    isNumber: function(str) {
        return !isNaN(str);
    },
    //是否中文
    isCN: function(str) {
        return /^[\u4e00-\u9fa5]+$/.test(str);
    },
    //是否固话
    isTel: function(str) {
        return /^((\d{3,4}-)?\d{8}(-\d+)?|(\(\d{3,4}\))?\d{8}(-\d+)?)$/.test(str);
    },
    //是否手机
    isMobile: function(str) {
        return /^(\+?86)?1[3-8]\d{9}$/.test(str);
    },
    //是否邮箱
    isEmail: function(str) {
        return /^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/.test(str);
    },
    //是否日期字符串
    isDate: function(str) {
        return /^(?:(?!0000)[0-9]{4}[\/-](?:(?:0?[1-9]|1[0-2])[\/-](?:0?[1-9]|1[0-9]|2[0-8])|(?:0?[13-9]|1[0-2])[\/-](?:29|30)|(?:0?[13578]|1[02])[\/-]31)|(?:[0-9]{2}(?:0[48]|[2468][048]|[13579][26])|(?:0[48]|[2468][048]|[13579][26])00)[\/-]0?2[\/-]29)$/.test(str);
    },
    //是否身份证(严格)
    isIdCard: function(str) {
		let Wi = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2, 1], //加权因子
            ValideCode = [1, 0, 10, 9, 8, 7, 6, 5, 4, 3, 2]; //身份证验证位值,10代表X
        function idCardValidate(idCard) {
            if (idCard.length == 15) {
                return is15IdCard(idCard); //进行15位身份证的验证
            } else if (idCard.length == 18) {
                return is18IdCard(idCard) && isTrue18IdCard(idCard.split('')); //进行18位身份证的基本验证和第18位的验证
            } else {
                return false;
            }
        }
        function isTrue18IdCard(idCard) {
			let sum = 0;
            if (idCard[17].toLowerCase() == 'x') idCard[17] = 10; //将最后位为x的验证码替换为10方便后续操作
			for (let i = 0; i < 17; i++) sum += Wi[i] * idCard[i]; //加权求和
			let valCodePosition = sum % 11; //得到验证码所位置
            if (idCard[17] == ValideCode[valCodePosition]) {
                return true;
            } else {
                return false;
            }
        }
        function is18IdCard(idCard) {
			let year = idCard.substring(6, 10),
                month = idCard.substring(10, 12),
                day = idCard.substring(12, 14),
                date = new Date(year, parseInt(month) - 1, parseInt(day));
            if (date.getFullYear() != parseInt(year) || date.getMonth() != parseInt(month) - 1 || date.getDate() != parseInt(day)) {
                return false;
            } else {
                return true;
            }
        }
        function is15IdCard(idCard) {
			let year = idCard.substring(6, 8),
                month = idCard.substring(8, 10),
                day = idCard.substring(10, 12),
                date = new Date(year, parseInt(month) - 1, parseInt(day));
            if (date.getYear() != parseInt(year) || date.getMonth() != parseInt(month) - 1 || date.getDate() != parseInt(day)) {
                return false;
            } else {
                return true;
            }
        }
        return idCardValidate(str);
    },
    //检测JSON对象
    isJson: function(obj) {
        return this.isPlainObject(obj);
    },
    //JSON字符串转JSON对象
    json: function(str) {
        return JSON.parse(str);
    },
    //JSON对象转JSON字符串
    jsonString: function(obj) {
        return JSON.stringify(obj);
    },
    //使用对象扩展另一个对象
    extend: function() {
		let $ = this;
        if ($.isArray(arguments[0])) {
			for (let i = arguments.length - 1; i > 0; i--) {
                arguments[i - 1] = arguments[i - 1].concat(arguments[i]);
            }
        } else if ($.isPlainObject(arguments[0])) {
			for (let i = arguments.length; i > 0; i--) {
				for (let k in arguments[i]) {
                    arguments[i - 1][k] = this.clone(arguments[i][k]);
                }
            }
        }
        return arguments[0];
    },
    //数组循环
    each: function(arr, callback) {
		let $ = this;
        if (!$.isFunction(callback)) return this;
        if ($.isArray(arr)) {
			for (let i = 0; i < arr.length; i++) {
				let result = callback.call(arr[i], i, arr[i]);
                if (typeof result == 'boolean') {
                    if (result) continue;
                    else break;
                }
            }
        } else if ($.isPlainObject(arr)) {
			for (let key in arr) {
				let result = callback.call(arr[key], key, arr[key]);
                if (typeof result == 'boolean') {
                    if (result) continue;
                    else break;
                }
            }
        }
        return this;
    },
    //克隆对象或数组
    clone: function(obj) {
        if (!obj) return obj;
		let $ = this;
        if (obj instanceof Date) {
            return new Date(obj.valueOf());
        } else if ($.isArray(obj)) {
			let arr = obj.valueOf(),
                newArr = [];
			for (let i = 0; i < arr.length; i++) {
                newArr.push($.clone(arr[i]));
            }
            return newArr;
        } else if ($.isPlainObject(obj)) {
            return $.extend({}, obj);
        }
        return obj;
    },
    //查询节点信息
    el: function(expr, callback) {
		let $ = this;
        wx.createSelectorQuery().select(expr).boundingClientRect(function(res) {
            if ($.isFunction(callback)) callback(res);
        }).exec();
        return this;
    },
    //查询节点组信息
    find: function(expr, callback) {
		let $ = this;
        wx.createSelectorQuery().selectAll(expr).boundingClientRect(function(res) {
            if ($.isFunction(callback)) callback(res);
            /*res.forEach(function (rect) {
            	rect.id        //ID
            	rect.dataset   //dataset
            	rect.left      //左边界坐标
            	rect.right     //右边界坐标
            	rect.top       //上边界坐标
            	rect.bottom    //下边界坐标
            	rect.width     //宽度
            	rect.height    //高度
            });*/
        }).exec();
        return this;
    },
	//屏幕宽高
	screen: function() {
		let res = wx.getSystemInfoSync();
		return {width: res.windowWidth, height: res.windowHeight};
	},
	//px转rpx
	toRpx: function(px) {
		try {
			let res = wx.getSystemInfoSync(),
				width = res.windowWidth;
			return Math.floor((750 / width) * px);
		} catch (e) {
			return 0;
		}
	},
    //rpx转px
    toPx: function(rpx) {
        try {
			let res = wx.getSystemInfoSync(),
                width = res.windowWidth;
            return Math.ceil(rpx * width / 750);
        } catch (e) {
            return 0;
        }
    },
    //设置当前页面标题
    setTitle: function(title) {
        wx.setNavigationBarTitle({
            title: title
        });
    },
    //获取当前页面路由列表
    getApps: function() {
        return getCurrentPages();
    },
    //获取页面,参数为空即当前页面
    getPage: function(route) {
		let pages = this.getApps(),
            page = null;
        if (typeof route == 'undefined' || !route.length) {
            if (pages.length) {
                page = pages[pages.length - 1];
                page.path = page.route;
            }
        } else {
            this.each(pages, function() {
                if (('/' + this.route).indexOf(route) > -1) {
                    page = this;
                    page.path = page.route;
                    return false;
                }
            });
        }
        return page;
    },
	//关闭所有页面,打开到应用内的某个页面
	relaunchView: function(url) {
		if (typeof url != 'undefined' && url.length) wx.reLaunch({
			url: url
		});
	},
	//关闭当前页面,跳转到应用内的某个页面
	redirectView: function(url) {
		if (typeof url != 'undefined' && url.length) wx.redirectTo({
			url: url
		});
	},
    //跳转页面
    pushView: function(url) {
	    if (typeof url != 'undefined' && url.length) wx.navigateTo({
            url: url
        });
    },
    //返回上一页面或多级页面
    popView: function(delta) {
        if (typeof delta == 'undefined') delta = 1;
        wx.navigateBack({
            delta: delta
        });
    },
    //返回到指定页面
    popToView: function(route) {
		let pages = this.getApps(),
            delta = 0;
        if (typeof route == 'undefined' || !route.length) return;
		for (let i = pages.length - 1; i >= 0; i--) {
            if (('/' + pages[i].route).indexOf(route) > -1) break;
            delta++;
        }
        console.log(delta)
        wx.navigateBack({
            delta: delta
        });
    },
    //返回到页面栈第一个页面
    popToRoot: function() {
		let pages = this.getApps();
        wx.navigateBack({
            delta: pages.length - 1
        });
    },
    //设置本地存储
    storage: function(key, data) {
		let $ = this;
        if (typeof(data) == 'undefined') {
            try {
				let value = wx.getStorageSync(key);
                if (value) return value;
                return null;
            } catch (e) {
                return null;
            }
        } else if (data === false || data === null) {
            try {
                if (!$.isArray(key)) key = [key];
                for (let i in key) wx.removeStorageSync(key[i]);
            } catch (e) {
                console.log(e);
            }
        } else {
            try {
                if (!$.isArray(key)) key = [key];
                for (let i in key) wx.setStorageSync(key[i], data);
            } catch (e) {
                console.log(e);
            }
        }
        return this;
    },
    //清除本地存储
    clearStorage: function() {
		let $ = this;
        try {
            wx.clearStorageSync()
        } catch (e) {
            console.log(e);
        }
        return this;
    },
    //获取当前地理位置
    getLocation: function(callback, fail, type) {
        if (!this.isFunction(callback)) return;
		let $ = this;
        if (typeof(type) == 'undefined') type = 'wgs84';
        wx.getLocation({
            type: type, //wgs84返回gps坐标，gcj02返回可用于wx.openLocation的坐标
            success: function(res) {
                $.baiduAddress({
                    latitude: res.latitude,
                    longitude: res.longitude,
                    callback: function(result) {
                        result.latitude = res.latitude;
                        result.longitude = res.longitude;
                        res.detail = result;
                        res.address = result.formatted_address;
                        callback(res);
                    }
                }, true);
                /*
                latitude 纬度，浮点数，范围为 - 90~90，负数表示南纬
                longitude 经度，浮点数，范围为 - 180~180，负数表示西经
                speed 速度，浮点数，单位m/s
                accuracy 位置的精确度
                altitude 高度，单位m
                verticalAccuracy 垂直精度，单位m（Android 无法获取，返回0）
                horizontalAccuracy 水平精度，单位 m
                */
            },
            fail: function(res) {
                if ($.isFunction(fail)) fail();
            }
        });
        return this;
    },
    //选择微信收货地址
    chooseAddress: function(callback) {
        if (!this.isFunction(callback)) return;
        wx.chooseAddress({
            success: function(res) {
                callback(res);
                /*
                name 位置名称
                address 详细地址
                latitude 纬度，浮点数，范围为 - 90~90，负数表示南纬
                longitude 经度，浮点数，范围为 - 180~180，负数表示西经
                */
            }
        });
    },
    //打开地图选择位置
    openMap: function(callback) {
        if (!this.isFunction(callback)) return;
		let $ = this;
        wx.chooseLocation({
            success: function(res) {
                $.baiduAddress({
                    latitude: res.latitude,
                    longitude: res.longitude,
                    callback: function(result) {
                        //result.latitude = res.latitude;
                        //result.longitude = res.longitude;
                        //res.detail = result;
                        //res.address = result.formatted_address;
                        //res.name = result.sematic_description;
                        res.province = result.province;
                        res.city = result.city;
                        res.district = result.district;
                        callback(res);
                    }
                });
            }
        });
    },
    //调起微信内置地图查看位置, 需使用gcj02类型的经纬度
    openLocation: function(options) {
        wx.getLocation({
            type: 'gcj02',
            success: function(res) {
                wx.openLocation({
                    latitude: res.latitude || 0, //纬度，范围为 - 90~90，负数表示南纬
                    longitude: res.longitude || 0, //经度，范围为-180~180，负数表示西经
                    scale: options.scale || 18, //缩放比例，范围5~18，默认为18
                    name: options.name || '', //位置名
                    address: options.address || '' //详细地址
                });
            }
        });
    },
    //地图,context为map标签id
    //https://developers.weixin.qq.com/miniprogram/dev/component/map.html#map
    //<map id="map" longitude="中心经度" latitude="中心纬度" scale="缩放级别，取值范围为5-18，默认16" markers="标记点Array" polyline="路线Array" show-location="true，显示带有方向的当前定位点"></map>
    createMap: function(context) {
        return wx.createMapContext(context);
    },
    moveToLocation: function(map) {
        map.moveToLocation();
    },
    //百度API,手机经纬度转百度坐标
    baiduGeo: function(options) {
        if (!this.isFunction(options.callback)) return;
		let $ = this;
        $.get('https://api.map.baidu.com/geoconv/v1/?ak=' + $.config.baidu_ak + '&from=3&to=5&coords=' + options.longitude + ',' + options.latitude, function(json) {
            if (parseInt(json.status) != 0) {
                $.overloadError(json.message);
                return;
            }
			let data = {
                longitude: json.result[0].x,
                latitude: json.result[0].y
            };
            if ($.isFunction(options.callback)) options.callback(data);
        });
    },
    baiduAddress: function(options, needChangeGeo) {
        if (!this.isFunction(options.callback)) return;
		let $ = this;
		let getAddress = function(latitude, longitude) {
            $.get('https://api.map.baidu.com/geocoder/v2/?ak=' + $.config.baidu_ak + '&location=' + latitude + ',' + longitude + '&ret_coordtype=bd09ll&output=json', function(json) {
                if (parseInt(json.status) != 0) {
                    $.overloadError(json.message);
                    return;
                }
				let data = {
                    country: json.result.addressComponent.country,
                    country_code_iso: json.result.addressComponent.country_code_iso,
                    province: json.result.addressComponent.province,
                    city: json.result.addressComponent.city,
                    district: json.result.addressComponent.district,
                    adcode: json.result.addressComponent.adcode,
                    street: json.result.addressComponent.street,
                    street_number: json.result.addressComponent.street_number,
                    formatted_address: json.result.formatted_address,
                    sematic_description: json.result.sematic_description
                };
                if ($.isFunction(options.callback)) options.callback(data);
            });
        };
        if (needChangeGeo) {
            $.baiduGeo({
                latitude: options.latitude,
                longitude: options.longitude,
                callback: function(result) {
                    getAddress(result.latitude, result.longitude);
                }
            });
        } else {
            getAddress(options.latitude, options.longitude);
        }
    },
    //调起拨打电话
    openCall: function(mobile) {
		let $ = this;
        if (mobile.length && mobile.indexOf('*') == -1) {
            wx.makePhoneCall({
                phoneNumber: mobile
            });
        } else {
            $.dialogView({
                content: '<div style="line-height:117rpx;text-align:center;">该电话号码未公开</div>',
                btns: [{
                    cls: 'confirm',
                    title: '确定'
                }]
            });
        }
    },
    //调起扫描
    scan: function(options) {
        options = this.extend({
            camera: false, //是否只能从相机扫码，不允许从相册选择图片
            success: null,
            fail: null
        }, options);
        wx.scanCode({
            onlyFromCamera: options.camera,
            success: options.success,
            fail: options.fail
        });
        /*
        success返回参数
        result 所扫码的内容
        scanType 所扫码的类型
        charSet 所扫码的字符集
        path 当所扫的码为当前小程序的合法二维码时，会返回此字段，内容为二维码携带的path
        */
    },
    //密码框
    passwordView: function(options) {
        options = this.extend({
            cls: 'ring', //附加样式
            placeholder: '●', //占位符,为空即显示字符串
            length: 6, //位数
            empty: null, //值为空时执行
            input: null, //值不为空且未输入所有位数时执行
            callback: null //输入所有位数后执行
        }, options);
		let $ = this,
            apps = getCurrentPages();
        if (!apps || !this.isArray(apps) || !apps.length) return;
		let app = apps[apps.length - 1],
            passwordView = (app.data && app.data.passwordView) ? app.data.passwordView : {};
        app.setData({
            passwordView: $.extend(passwordView, {
                cls: options.cls,
                style: '',
                string: [],
                length: options.length
            })
        });
        app.changePasswordView = function(e) {
			let value = e.detail.value,
                string = [];
            if (value.length) {
				let values = value.split('');
				for (let i = 0; i < values.length; i++) {
					let v = options.placeholder.length ? options.placeholder : values[i];
                    string.push(v);
                }
            }
            app.setData({
                passwordView: $.extend(app.data.passwordView, {
                    string: string
                })
            });
            if (!value.length && $.isFunction(options.empty)) {
                options.empty(value);
            }
            if (value.length && value.length < options.length && $.isFunction(options.input)) {
                options.input(value);
            }
            if (value.length == options.length && $.isFunction(options.callback)) {
                options.callback(value);
            }
        };
        app.setPasswordViewStyle = function(e) {
            app.setData({
                passwordView: $.extend(app.data.passwordView, {
                    style: 'left:-9999px;top:-9999px;'
                })
            });
        };
        app.removePasswordViewStyle = function(e) {
            app.setData({
                passwordView: $.extend(app.data.passwordView, {
                    style: ''
                })
            });
        };
    },
    //滚动选项卡
    switchView: function(options) {
		let $ = this,
            apps = getCurrentPages();
        if (!apps || !this.isArray(apps) || !apps.length) return;
		let app = apps[apps.length - 1],
            switchView = (app.data && app.data.switchView) ? app.data.switchView : {};
        switchView = $.extend(switchView, {
            list: [], //列表,格式:[{name:'选项名',value:'选项值',cls:'附加样式'}]
            selected: '', //默认选中项,值为选项值
            click: null, //点击后执行,三个参数:选项值、选项名、选项索引
            switchViewWidth: 0
        }, options);
        app.setData({
            switchView: switchView
        });
        if (switchView.list.length > 4) setTimeout(function() {
			let switchViewWidth = 0;
            $.find('.switchView .li', function(res) {
                res.forEach(function(rect) {
                    switchViewWidth += rect.width;
                });
                switchView.switchViewWidth = switchViewWidth;
                app.setData({
                    switchView: switchView
                });
            });
        }, 100);
        app.switchViewHandler = function(e) {
            if (!$.isFunction(switchView.click)) return;
			let index = e.currentTarget.dataset.index,
                name = switchView.list[index].name,
                value = switchView.list[index].value;
	        let result = switchView.click(value, name, index);
	        if (typeof result == 'boolean' && !result) return;
	        switchView.selected = value;
	        app.setData({
		        switchView: switchView
	        });
        };
    },
    //获取Openid
    getOpenid: function(callback) {
		let $ = this;
        if (!$.isFunction(callback)) return;
        wx.login({
            success: function(res) {
                if (res.errMsg == 'login:ok' && res.code) {
                    $.get('wx_interface?act=get_session_key', {
                        code: res.code
                    }, function(json) {
                        callback(json.openid);
                    });
                } else {
                    console.log('获取用户登录状态失败！' + res.errMsg);
                }
            }
        });
    },
    //弹出登录授权界面
    loginAuth: function(cls, callback) {
		let $ = this,
            apps = getCurrentPages();
        if (!apps || !this.isArray(apps) || !apps.length) return;
		if ($.isFunction(cls)) {
			callback = cls;
			cls = 'image-wxauth';
		}
		let app = apps[apps.length - 1],
            loginAuth = (app.data && app.data.loginAuth) ? app.data.loginAuth : {};
        app.setData({
            loginAuth: $.extend(loginAuth, {
                show: true,
				cls: cls
            })
        });
        setTimeout(function() {
            app.setData({
                loginAuth: $.extend(app.data.loginAuth, {
                    showIn: 'login-auth-in'
                })
            });
        }, 0);
        //获取用户的授权设置
        app.loginAuthUserInfo = function(e) {
            $.loginAuthApi(e.detail.userInfo, function(person) {
                $.storage('person', person);
                app.setData({
                    loginAuth: $.extend(app.data.loginAuth, {
                        showOut: 'login-auth-out'
                    })
                });
                setTimeout(function() {
                    app.setData({
                        loginAuth: $.extend(app.data.loginAuth, {
                            show: false,
                            showOut: ''
                        })
                    });
                    if ($.isFunction(callback)) callback(person);
                }, 400);
                if (app.onShow) app.onShow();
            });
        };
    },
    //获取openid后调用网站api得到会员资料
    loginAuthApi: function(userInfo, callback) {
		let $ = this,
            reseller = getApp().globalData.reseller;
        $.getOpenid(function(openid) {
            $.overload();
            userInfo.openid = openid;
            $.post('/api/v3/core/weixin_auth' + (reseller ? '?reseller=' + reseller : ''), {
                userinfo: $.jsonString(userInfo)
            }, function(json) {
				let person = json.data;
                person.openid = openid;
                if ($.isFunction(callback)) callback(person);
            });
        });
    },
    //提示框
    overload: function(text, icon, delay) {
		let $ = this,
            apps = getCurrentPages();
        if (!apps || !this.isArray(apps) || !apps.length) return;
		let app = apps[apps.length - 1],
            overload = (app.data && app.data.overload) ? app.data.overload : {};
        if (typeof text == 'boolean' && !text) {
            if (overload.delay > 0) return;
            if (overload.timeout) clearTimeout(overload.timeout);
			let view = (overload.view && overload.view.length) ? overload.view : '';
			let timeout = setTimeout(function() {
                if (app.data.overload.timeout) clearTimeout(app.data.overload.timeout);
                app.setData({
                    overload: $.extend(overload, {
                        view: view + ' load-view-out'
                    })
                });
                setTimeout(function() {
                    app.setData({
                        overload: $.extend(overload, {
                            show: false,
                            showDelay: false,
                            view: ''
                        })
                    });
                }, 300);
            }, 10);
            app.setData({
                overload: $.extend(overload, {
                    timeout: timeout
                })
            });
            return;
        }
        if (typeof text != 'string') text = '';
        if (typeof icon != 'string') icon = '';
        if (typeof delay != 'number') delay = 0;
		let showDelay = false;
        if (overload.timeout) clearTimeout(overload.timeout);
        app.setData({
            overload: $.extend(overload, {
                show: true,
                showDelay: showDelay,
                view: text.length ? '' : 'nontext',
                icon: icon,
                text: text,
                delay: delay,
                timeout: null
            })
        });
        setTimeout(function() {
            app.setData({
                overload: $.extend(app.data.overload, {
                    view: app.data.overload.view + ' load-view-in'
                })
            });
            if (delay > 0) {
				let overload = app.data.overload;
				let timeout = setTimeout(function() {
                    if (overload.timeout) clearTimeout(overload.timeout);
					let view = (overload.view && overload.view.length) ? overload.view : '';
                    app.setData({
                        overload: $.extend(overload, {
                            view: view + ' load-view-out'
                        })
                    });
                    setTimeout(function() {
                        app.setData({
                            overload: $.extend(overload, {
                                show: false,
                                showDelay: false,
                                view: ''
                            })
                        });
                    }, 300);
                }, delay);
                app.setData({
                    overload: $.extend(overload, {
                        timeout: timeout
                    })
                });
            }
        }, 0);
    },
    overloadSuccess: function(text) {
		let delay = 4000;
        if (typeof getApp().overloadDelay != 'undefined') {
            delay = getApp().overloadDelay;
        }
        if (typeof this.getPage().overloadDelay != 'undefined') {
            delay = this.getPage().overloadDelay;
        }
        this.overload(text, 'load-success', delay);
    },
    overloadError: function(text) {
        if (!text.length) return;
		let delay = 4000;
        if (typeof getApp().overloadDelay != 'undefined') {
            delay = getApp().overloadDelay;
        }
        if (typeof this.getPage().overloadDelay != 'undefined') {
            delay = this.getPage().overloadDelay;
        }
        this.overload(text, 'load-error', delay);
    },
    overloadWarning: function(text) {
		let delay = 4000;
        if (typeof getApp().overloadDelay != 'undefined') {
            delay = getApp().overloadDelay;
        }
        if (typeof this.getPage().overloadDelay != 'undefined') {
            delay = this.getPage().overloadDelay;
        }
        this.overload(text, 'load-warning', delay);
    },
	//自定义内容弹框
	dialogView: function(options) {
		if (typeof options == 'boolean' && !options) {
			let apps = getCurrentPages();
			if (!apps || !this.isArray(apps) || !apps.length) return;
			let app = apps[apps.length - 1]
			app.dialogViewCancel();
			return;
		}
		options = this.extend({
			cls: 'wx', //附加样式
			showIn: 'dialog-scale-in', //动画样式
			showOut: 'dialog-scale-out', //关闭动画样式
			title: '', //标题,为空即不显示
			content: '', //内容
			btns: [], //按钮组,{cls:'样式名', title:'按钮文字'}
			bgClose: true, //点击背景关闭
			callback: null //点击按钮执行
		}, options);
		let $ = this,
			apps = getCurrentPages();
		if (!apps || !this.isArray(apps) || !apps.length) return;
		if (options.cls == 'wxauth' || options.cls == 'image-wxauth') {
			$.loginAuth(options.cls);
			return;
		}
		if (options.cls == 'dialog-wxauth' && $.storage('WxauthCancel')) return;
		let app = apps[apps.length - 1],
			dialogView = (app.data && app.data.dialogView) ? app.data.dialogView : {};
		let btns = options.btns,
			btnArray = [];
		if (options.cls == 'dialog-wxauth') {
			if (!options.title.length) options.title = '授权登录';
			if (!options.content.length) options.content = '获得你的微信公开信息(昵称、头像等)进行登录，而无需账号与密码。';
			options.bgClose = false;
			if (!$.isArray(btns) || !btns.length) btns = [{
				cls: 'wxauth',
				title: '进行授权'
			}];
		} else {
			if (!$.isArray(btns) || !btns.length) btns = [{
				cls: 'cancel',
				title: '确定'
			}];
		}
		$.each(btns, function() {
			if (typeof this == 'boolean') {
				let btn = {
					cls: 'cancel',
					title: '取消',
					boolean: true
				};
				if (this) {
					btn = {
						title: '确定'
					};
					if (options.cls == 'wx' || options.cls == 'dialog-wxauth') {
						btn.cls = 'green';
					} else {
						btn.cls = 'blue';
					}
				}
				btnArray.push(btn);
			} else {
				if (!this.cls || !this.cls.length) {
					if (options.cls == 'wx' || options.cls == 'dialog-wxauth') {
						this.cls = 'green';
					} else {
						this.cls = 'blue';
					}
				}
				//this.openType = 'contact|share|getPhoneNumber|getUserInfo|launchApp|openSetting|feedback';
				btnArray.push(this);
			}
		});
		$.WxParse.wxParse('dialogContent', 'html', options.content, app, 0);
		app.setData({
			dialogView: $.extend(dialogView, {
				show: true,
				cls: options.cls,
				showIn: options.showIn,
				showOut: '',
				title: options.title,
				content: options.content,
				bgClose: options.bgClose,
				dialogContent: app.data.dialogContent,
				btns: btnArray
			})
		});
		setTimeout(function() {
			app.setData({
				dialogView: $.extend(app.data.dialogView, {
					showIn: ''
				})
			});
		}, 0);
		app.dialogViewHandler = function(e) {
			let index = e.currentTarget.dataset.index;
			if ($.isFunction(options.callback)) {
				let result = options.callback(index);
				if (typeof result == 'undefined' ||
					(typeof result == 'boolean' && result))
					app.dialogViewCancel();
			} else app.dialogViewCancel();
		};
		app.dialogViewCancel = function(e) {
			if (options.showOut.length) {
				app.setData({
					dialogView: $.extend(app.data.dialogView, {
						showOut: options.showOut
					})
				});
				setTimeout(function() {
					app.setData({
						dialogView: $.extend(app.data.dialogView, {
							show: false
						})
					});
				}, 300);
			} else if (options.showIn.length) {
				app.setData({
					dialogView: $.extend(app.data.dialogView, {
						showIn: options.showIn
					})
				});
				setTimeout(function() {
					app.setData({
						dialogView: $.extend(app.data.dialogView, {
							show: false
						})
					});
				}, 300);
			} else {
				app.setData({
					dialogView: $.extend(app.data.dialogView, {
						show: false
					})
				});
			}
		};
		//获取用户的授权设置
		app.dialogViewUserInfo = function(e) {
			$.loginAuthApi(e.detail.userInfo, function(person) {
				$.storage('person', person);
				app.dialogViewCancel();
				if (app.onShow) app.onShow();
			});
		};
    },
    //宽高等比缩放
    zoom: function(originWidth, originHeight, width, height, fix){
        let targetWidth = 0, targetHeight = 0;
		if(originWidth<=width && originHeight<=height)return;
		if(width>0 && height>0){
			if(originWidth<=width && originHeight<=height){
				targetWidth = originWidth;
				targetHeight = originHeight;
			}else{
				if(originWidth/originHeight >= width/height){
					if(originWidth>width){
						targetWidth = width;
						targetHeight = (originHeight*width)/originWidth;
					}
				}else{
					if(originHeight>height){
						targetWidth = (originWidth*height)/originHeight;
						targetHeight = height;
					}
				}
			}
		}else{
			if(width===0 && height>0){
				targetWidth = (originWidth*height)/originHeight;
				targetHeight = height;
			}else if(width>0 && height===0){
				targetWidth = width;
				targetHeight = (originHeight*width)/originWidth;
			}else if(typeof fix!='undefined' && width===0 && height===0 && fix>0){
				if(originWidth>originHeight){
					targetWidth = (originWidth*fix)/originHeight;
					targetHeight = fix;
				}else{
					targetWidth = fix;
					targetHeight = (originHeight*fix)/originWidth;
				}
			}
		}
		return {width:targetWidth, height:targetHeight};
	},
    //获取图片主色调
    getImageColor: function (url, callback) {
        //<canvas type="2d" id="canvas" style="position:fixed;left:-9999px;top:-9999px;"></canvas>
        let $ = this;
        if(!url.length || !$.isFunction(callback))return;
        let COLOR_SIZE = 40; //单位色块的大小(像素个数,默认40), 以单位色块的平均像素值为作为统计单位
		let LEVEL = 32; //色深, 颜色分区参数(0-255), 总256, 2^8, 即8bit, 4个通道(rgba), 即默认色深4*8bit, 32bit
		let getMainColor = function(imageData) {
			let defRst = {
				rgb: '',
				rgba: '',
				hex: '',
				hexa: '',
				defaultRGB: {}
			};
			if (imageData.length < 4) {
				return defRst;
			} else {
				let mapData = getLevelData(imageData), colors = getMostColor(mapData);
				if (!colors) {
					return defRst;
				} else {
					return colorStrFormat(getAverageColor(colors));
				}
			}
		},
		//获取每段的颜色数据, 根据像素数据, 按单位色块进行切割
		getLevelData = function(imageData) {
			let len = imageData.length, mapData = {};
			for (let i = 0; i < len; i += COLOR_SIZE * 4) {
				let blockColor = getBlockColor(imageData, i); //该区块平均rgba[{r,g,b,a}]数据
				//获取各个区块的平均rgba数据, 将各个通道的颜色进行LEVEL色深降级, 根据r_g_b_a建立map索引
				let key = getColorLevel(blockColor);
				!mapData[key] && (mapData[key] = []);
				mapData[key].push(blockColor);
			}
			return mapData;
		},
		//获取单位块的全部色值, 并根据全部色值, 计算平均色值, 处理最后边界值, 小于COLOR_SIZE
		getBlockColor = function(imageData, start) {
			let data = [], count = COLOR_SIZE, len = COLOR_SIZE * 4;
			imageData.length <= start + len && (count = Math.floor((imageData.length - start - 1) / 4));
			for (let i = 0; i < count; i += 4) {
				data.push({
					r: imageData[start + i + 0],
					g: imageData[start + i + 1],
					b: imageData[start + i + 2],
					a: imageData[start + i + 3]
				});
			}
			return getAverageColor(data);
		},
		//取出各个通道的平均值，即为改色块的平均色值
		getAverageColor = function(colorArr) {
			let len = colorArr.length;
			let sr = 0, sg = 0, sb = 0, sa = 0;
			colorArr.map(function (item) {
				sr += item.r;
				sg += item.g;
				sb += item.b;
				sa += item.a;
			});
			return {
				r: Math.round(sr / len),
				g: Math.round(sg / len),
				b: Math.round(sb / len),
				a: Math.round(sa / len)
			}
		},
		getColorLevel = function(color) {
			return getLevel(color.r) + '_' + getLevel(color.g) + '_' + getLevel(color.b) + '_' + getLevel(color.a);
		},
		//色深降级
		getLevel = function(value) {
			return Math.round(value / LEVEL);
		},
		//根据色块颜色，获取
		getMostColor = function(colorData) {
			let rst = null, len = 0;
			for (let key in colorData) {
				colorData[key].length > len && (
					rst = colorData[key],
					len = colorData[key].length
				);
			}
			return rst;
		},
		//对最终颜色的字符串格式化
		colorStrFormat = function(color) {
			let rgba = 'rgba(' + color.r + ',' + color.g + ',' + color.b + ',' + (color.a / 255).toFixed(4).replace(/\.*0+$/, '') + ')';
			let rgb = 'rgb(' + color.r + ',' + color.g + ',' + color.b + ')';
			let hex = '#' + Num2Hex(color.r) + Num2Hex(color.g) + Num2Hex(color.b);
			let hexa = hex + Num2Hex(color.a);
			return {
				rgba: rgba,
				rgb: rgb,
				hex: hex,
				hexa: hexa,
				defaultRGB: color
			};
		},
		Num2Hex = function(num) {
			let hex = num.toString(16) + '';
			if (hex.length < 2) {
				return '0' + hex;
			} else {
				return hex;
			}
		};
        wx.createSelectorQuery().select('#canvas').fields({node:true, size:true}).exec((res) => {
            let canvas = res[0].node;
            let ctx = canvas.getContext('2d');
            let image = canvas.createImage();
            image.src = url;
            image.onload = function () {
                let o = $.zoom(image.width, image.height, wx.getSystemInfoSync().windowWidth, 0);
                canvas.width = o.width;
                canvas.height = o.height;
                ctx.drawImage(image, 0, 0, image.width, image.height, 0, 0, canvas.width, canvas.height);
                let data = ctx.getImageData(0, 0, canvas.width, canvas.height).data;
                let {defaultRGB} = getMainColor(data);
                let {r, g, b} = defaultRGB;
                callback(r, g, b);
            };
        });
    },
	toast: function (options) {
		let $ = this,
			apps = getCurrentPages(),
			app = apps[apps.length - 1],
			opt = {
				cls: '', //附加样式
				text: '', //提示的内容
				showIn: 'toast-scale-in', //动画样式
				showOut: '' //关闭动画样式
			};
		if (typeof options == 'string') {
			opt.text = options;
		} else {
			opt = $.extend(opt, options);
		}
		app.setData({
			toast: $.extend(opt, {
				show: true
			})
		});
		setTimeout(function () {
			app.setData({
				toast: $.extend(opt, {
					showIn: ''
				})
			});
			setTimeout(function () {
				app.setData({
					toast: $.extend(opt, {
						showOut: 'toast-scale-out'
					})
				});
				setTimeout(function () {
					app.setData({
						toast: $.extend(opt, {
							show: false
						})
					});
				}, 300);
			}, opt.text.length * 200);
		}, 50);
	},
    alert: function(content) {
		let options = {
            content: content,
            showCancel: false
        };
        this.confirm(options);
    },
    confirm: function(options) {
		let $ = this;
        options = this.extend({
            title: '', //提示的标题
            content: '', //提示的内容
            okText: '确定', //确定按钮文字
            cancelText: '取消', //取消按钮文字
            okColor: '#3CC51F', //确定按钮文字颜色
            showCancel: true, //是否显示取消按钮
            success: null, //按下确定按钮后执行
            cancel: null //按下确定按钮后执行
        }, options);
        wx.showModal({
            title: options.title,
            content: options.content,
            confirmText: options.okText,
            cancelText: options.cancelText,
            confirmColor: options.okColor,
            showCancel: options.showCancel,
            success: function(res) {
                if (res.confirm) {
                    if ($.isFunction(options.success)) options.success();
                } else if (res.cancel) {
                    if ($.isFunction(options.cancel)) options.cancel();
                }
            }
        });
    },
    actionSheet: function(options) {
		let $ = this;
        options = this.extend({
            items: [], //按钮的文字数组，数组长度最大为6个
            color: '#000000', //按钮的文字颜色
            success: null, //按下按钮后执行
            fail: null //按下取消按钮后执行
        }, options);
        wx.showActionSheet({
            itemList: options.items,
            success: function(res) {
                if ($.isFunction(options.success)) {
                    options.success(res.tapIndex);
                }
            },
            fail: function() {
                if ($.isFunction(options.fail)) options.fail();
            }
        });
    },
    //调起选择图片
    selectImage: function(success) {
        if (!this.isFunction(success)) return;
        wx.chooseImage({
	        count: 1,
            success: function(res) {
                //res.tempFiles[0].path
                //res.tempFiles[0].size
				let tempFilePaths = res.tempFilePaths;
                success(tempFilePaths[0]);
            }
        });
    },
    //调起支付
    payment: function(data, callback) {
		let $ = this;
        if (typeof(data) == 'string') data = this.json(data);
        wx.requestPayment({
            'timeStamp': data.timeStamp,
            'nonceStr': data.nonceStr,
            'package': data.package,
            'signType': 'MD5',
            'paySign': data.paySign,
            success: function(res) {
                if (res.errMsg == 'requestPayment:ok') {
                    if ($.isFunction(callback)) callback();
                }
                console.log(res);
            },
            fail: function(res) {
                console.log(res);
                console.log(data);
            }
        });
    },
    //配置一个动画
    animation: function(duration) {
        return wx.createAnimation({
            transformOrigin: '50% 50% 0',
            duration: duration,
            timingFunction: 'ease-out',
            delay: 0
        });
    },
    //JSON对象转网址参数
    paramUrl: function(obj) {
        return Object.keys(obj).map(function(k) {
            return encodeURIComponent(k) + '=' + encodeURIComponent(obj[k]);
        }).join('&');
    },
    //转完整网址
    wapUrl: function(url) {
		let person = this.storage('person');
        if (!/^http/.test(url)) {
            if (config.apiUrl.substr(-1, 1) != '/' && url.substr(0, 1) != '/') url = '/' + url;
            url = config.apiUrl + url;
            if (url.indexOf('sign=') == -1 && person && person.sign) {
                url += (url.indexOf('?') > -1 ? '&' : '?') + 'sign=' + person.sign;
            }
        }
        return url;
    },
    //检测分享到群
    shareGroup: function(res, scene, callback) {
        let $ = this;
        if (typeof res == 'undefined') { //Page.onShow调用
            wx.showShareMenu({
                withShareTicket: true
            });
        } else { //App.onShow调用, 检测与callback
            //1044: 带shareTicket的小程序消息卡片
            if (res.scene==scene && $.isFunction(callback)) {
                callback(res);
                /*
                //获取转发详细信息
                wx.getShareInfo({
                    shareTicket: res.shareTicket,
                    success: function(res) {
                        //res.errMsg 错误信息	
                        //res.encryptedData 包括敏感数据在内的完整转发信息的加密数据，详细见加密数据解密算法	
                        //res.iv 加密算法的初始向量，详细见加密数据解密算法	
                        //res.cloudID 敏感数据对应的云 ID，开通云开发的小程序才会返回
                        //解密后得到 {"openGId":"OPENGID"}
                    }
                });
                */
            }
        }
    },
    //调起微信的收货地址
    getAddress: function(callback, fail) {
        if (!this.isFunction(callback)) return;
		let $ = this;
        wx.chooseAddress({
            success: function(res) {
                if (res.errMsg == 'chooseAddress:ok') {
                    //console.log(res.userName) //收货人姓名
                    //console.log(res.postalCode) //邮编
                    //console.log(res.provinceName) //国标收货地址第一级地址
                    //console.log(res.cityName) //国标收货地址第二级地址
                    //console.log(res.countyName) //国标收货地址第三级地址
                    //console.log(res.detailInfo) //详细收货地址信息
                    //console.log(res.nationalCode) //收货地址国家码
                    //console.log(res.telNumber) //收货人手机号码
                    callback({
                        name: res.userName,
                        zip: res.postalCode,
                        province: res.provinceName,
                        city: res.cityName,
                        district: res.countyName,
                        address: res.detailInfo,
                        mobile: res.telNumber
                    });
                } else {
                    if ($.isFunction(fail)) fail(res);
                }
            },
            fail: function(res) {
                if ($.isFunction(fail)) fail(res);
            }
        });
    },
    //省市区地址自动组合
    comboCity: function(province, city, district, address, apart) {
        apart = typeof(apart) != 'undefined' ? apart : '';
		let html = province;
        if (typeof(city) != 'undefined' && province != city) html += apart + city;
        if (typeof(district) != 'undefined') html += apart + district;
        if (typeof(address) != 'undefined') html += apart + address;
        return html;
    },
    //加上天数得到日期
    dateAdd: function(date, t, number) {
        number = parseInt(number);
        switch (t) {
            case 's':
                return new Date(Date.parse(date) + (1000 * number));
                break;
            case 'n':
                return new Date(Date.parse(date) + (60000 * number));
                break;
            case 'h':
                return new Date(Date.parse(date) + (3600000 * number));
                break;
            case 'd':
                return new Date(Date.parse(date) + (86400000 * number));
                break;
            case 'w':
                return new Date(Date.parse(date) + ((86400000 * 7) * number));
                break;
            case 'q':
                return new Date(date.getFullYear(), (date.getMonth()) + number * 3, date.getDate(), date.getHours(), date.getMinutes(), date.getSeconds());
                break;
            case 'm':
                return new Date(date.getFullYear(), (date.getMonth()) + number, date.getDate(), date.getHours(), date.getMinutes(), date.getSeconds());
                break;
            case 'y':
                return new Date((date.getFullYear() + number), date.getMonth(), date.getDate(), date.getHours(), date.getMinutes(), date.getSeconds());
                break;
        }
        return date;
    },
    //减去天数得到日期
    dateDiff: function(date, t, number) {
		let d = date,
            k = {
                'd': 24 * 60 * 60 * 1000,
                'h': 60 * 60 * 1000,
                'n': 60 * 1000,
                's': 1000
            };
        d = d.getTime();
        d = d - number * k[t];
        return new Date(d);
    },
    //日期格式化
    formatDate: function(date, formatStr, callback) {
        if (!isNaN(date)) date = new Date(date * 1000);
		let $ = this,
            format = formatStr ? formatStr : 'yyyy-mm-dd hh:nn:ss',
            monthName = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            monthFullName = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
            weekName = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
            weekFullName = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
            monthNameCn = ['一月', '二月', '三月', '四月', '五月', '六月', '七月', '八月', '九月', '十月', '十一月', '十二月'],
            monthFullNameCn = monthNameCn,
            weekNameCn = ['日', '一', '二', '三', '四', '五', '六'],
            weekFullNameCn = ['星期天', '星期一', '星期二', '星期三', '星期四', '星期五', '星期六'],
            getYearWeek = function(y, m, d) {
				let dat = new Date(y, m, d),
                    firstDay = new Date(y, 0, 1),
                    day = Math.round((dat.valueOf() - firstDay.valueOf()) / 86400000);
                return Math.ceil((day + ((firstDay.getDay() + 1) - 1)) / 7);
            },
            year = date.getFullYear() + '',
            month = (date.getMonth() + 1) + '',
            day = date.getDate() + '',
            week = date.getDay(),
            hour = date.getHours() + '',
            minute = date.getMinutes() + '',
            second = date.getSeconds() + '',
            yearWeek = getYearWeek(date.getFullYear(), date.getMonth(), date.getDate()) + '';
        format = format.replace(/yyyy/g, year);
        format = format.replace(/yy/g, (date.getYear() % 100) > 9 ? (date.getYear() % 100) + '' : '0' + (date.getYear() % 100));
        format = format.replace(/Y/g, year);
        format = format.replace(/mme/g, monthFullName[month - 1]);
        format = format.replace(/me/g, monthName[month - 1]);
        format = format.replace(/mmc/g, monthFullNameCn[month - 1]);
        format = format.replace(/mc/g, monthNameCn[month - 1]);
        format = format.replace(/mm/g, month.length < 2 ? '0' + month : month);
        format = format.replace(/m/g, month);
        format = format.replace(/dd/g, day.length < 2 ? '0' + day : day);
        format = format.replace(/d/g, day);
        format = format.replace(/hh/g, hour.length < 2 ? '0' + hour : hour);
        format = format.replace(/h/g, hour);
        format = format.replace(/H/g, hour);
        format = format.replace(/G/g, hour);
        format = format.replace(/nn/g, minute.length < 2 ? '0' + minute : minute);
        format = format.replace(/n/g, minute);
        format = format.replace(/ii/g, minute.length < 2 ? '0' + minute : minute);
        format = format.replace(/i/g, minute);
        format = format.replace(/ss/g, second.length < 2 ? '0' + second : second);
        format = format.replace(/s/g, second);
        format = format.replace(/wwe/g, weekFullName[week]);
        format = format.replace(/we/g, weekName[week]);
        format = format.replace(/ww/g, weekFullNameCn[week]);
        format = format.replace(/w/g, weekNameCn[week]);
        format = format.replace(/WW/g, yearWeek.length < 2 ? '0' + yearWeek : yearWeek);
        format = format.replace(/W/g, yearWeek);
        format = format.replace(/a/g, hour < 12 ? 'am' : 'pm');
        format = format.replace(/A/g, hour < 12 ? 'AM' : 'PM');
        if ($.isFunction(callback)) callback.call(date, {
            year: year,
            month: month,
            day: day,
            hour: hour,
            minute: minute,
            second: second,
            week: week
        });
        return format;
    },
    fillZero: function(num, length) {
        num = num + '';
        if (num.length >= length) return num;
		let str = '';
		for (let i = 0; i < length; i++) str += '0';
        str += num;
        return str.substr(str.length - length);
    }
}