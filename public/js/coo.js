/*
Developed by @mario v12.6.20211012
*/
window.BAIDU_AK = 'iaDZrNldobQVbG7L357j8fIPKxIj8A1i';
window.ua = navigator.userAgent.toLowerCase();
window.isApp = window.ua.indexOf('laokema') > -1;
window.isMario = window.ua.indexOf('@mario') > -1;
window.isWX = (window.ua.toLowerCase().indexOf('micromessenger') > -1 || window.ua.toLowerCase().indexOf('wechatdevtools') > -1);
window.eventType = 'click';

if(window.self === window.top)try{window.console && window.console.log && (console.log('Developed by @mario'), console.log("%cWelcome to: http://www.laokema.com/index/home/login", 'color:red'), console.log("%cUsername: test, Password: test", 'color:orange'), console.log("%cWechat: lwf000001", 'color:green'), console.log("%cQQ: 172403414", 'color:blue'))}catch(e){}

(function($){

//AJAX上传, master为非file控件时的对象,不用指定
/*
function ajaxUploadFn(){
	return {
		success: function(json, status){
			if(typeof json.error !== 'undefined'){
				if(json.error !== 0){$.overloadError(json.message);return}
				$.overloadSuccess(json.url[0]);
			}
		},
		error: function(data, status, e){
			$.overloadError('Upload error<br />'+e);
		},
		complete: function(data, status){
			$.overloadSuccess('Upload complete');
		}
	};
}
*/
$.fn.ajaxupload = function(options, master){
	options = $.extend({
		url: '/upload', //上传提交的目标网址
		name: 'filename', //非file控件上传时指定的提交控件名称
		loading: '', //上传中在右边显示的图片路径
		innerFile: $.browser.mobile, //直接在内部生成input:file,为兼容某些浏览器不支持模拟点击
		fileType: ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg'], //允许上传文件类型,后缀名,数组或字符串(逗号隔开)
		dataType: 'json', //请求类型
		data: null, //上传时一同提交的数据
		rightnow: false, //不需要经过change之类的操作,立刻上传(控件已选择了文件)
		target: '', //表单提交目标
		multiple: false, //多文件选择(只支持HTML5浏览器)
		beforeSelect: null, //选择前执行, 非file控件有效, 若返回false即终止选择
		before: null, //上传前执行, 若返回false即终止上传, 接受三个参数:e,选择的文件数量,选择的文件
		cancel: null, //终止上传后执行
		uploading: null, //正在上传时执行
		callback: null, //上传操作完毕后返回的回调函数(函数(即success函数)|对象字面量)
		error: null, //上传操作失败后执行(即error函数,比callback.error优先)
		complete: null, //上传操作完毕后执行(即complete函数,比callback.complete优先)
		replace: null, //替换上传操作, 例如使用又拍云替换ajaxupload的默认上传, 接受一个参数:file控件
		debug: false //保留iframe,form
	}, $('body').data('ajaxupload.options'), options);
	return this.each(function(){
		let _this = $(this), loading = [], dat = {}, s = {}, result,
			url = _this.attr('data-url')||_this.attr('url')||options.url, load = _this.attr('loading')||options.loading, fileType = _this.attr('data-fileType')||_this.attr('fileType')||options.fileType,
			dataType = _this.attr('data-dataType')||_this.attr('dataType')||options.dataType, data = _this.attr('data')||options.data, target = _this.attr('target')||options.target,
			multiple = _this.attr('multiple')||options.multiple, debug = _this.attr('debug')||options.debug,
			beforeSelect = _this.attr('beforeSelect')||options.beforeSelect, before = _this.attr('before')||options.before,
			cancel = _this.attr('cancel')||options.cancel, uploading = _this.attr('uploading')||options.uploading,
			callback = _this.attr('callback')||options.callback, error = _this.attr('error')||options.error,
			complete = _this.attr('complete')||options.complete, replace = _this.attr('replace')||options.replace;
		if(!!!url && !$.isFunction(replace))return true;
		if(!!_this.data('ajaxupload') && !options.rightnow)return true;
		_this.data('ajaxupload', true);
		let toJSONStr = function(str){
			return str.replace(/(\w+)\s*:/g, '"$1":').replace(/("\w+")\s*:\s*'/g, '$1:"').replace(/'(\s*,\s*"|\s*}$)/g, '"$1');
		};
		if(typeof data === 'string')data = $.json(toJSONStr(data));
		if(!_this.is(':file')){
			if(!options.name.length && !$.isFunction(replace))return true;
			let mul = multiple ? 'multiple' : '', opt = $.extend({}, options, {url:url, loading:load, fileType:fileType, dataType:dataType, data:data,
				target:target, multiple:multiple, before:before, cancel:cancel, callback:callback, error:error, complete:complete, replace:replace, debug:debug});
			if(options.innerFile){
				let width = _this.innerWidth(), height = _this.innerHeight();
				_this.css({position:'relative', overflow:'hidden'}).find(':file[name="'+options.name.replace(/([\[\]])/g,'\\$1')+'"]').remove();
				let file = $('<input type="file" id="file_'+parseInt(String(Math.random() * 1000))+'" name="'+options.name+'" '+mul+' />');
				_this.append(file);
				file.css({position:'absolute', 'z-index':9999, top:0, right:0, opacity:0, margin:'0', width:width, height:height, 'font-size':height+'px', cursor:'pointer'});
				file.ajaxupload(opt, _this);
				if(options.rightnow)file.trigger('click');
			}else{
				if(options.rightnow){
					_this.prevAll(':file[name="'+options.name.replace(/([\[\]])/g, '\\$1')+'"]').remove();
					let file = $('<input type="file" name="'+options.name+'" '+mul+' style="display:none;" />');
					_this.before(file);
					file.ajaxupload(opt, _this);
					file.trigger('click');
				}else{
					_this.tapper(function(){
						if($.isFunction(beforeSelect)){
							result = beforeSelect.call(_this);
							if(typeof(result) === 'boolean' && !result){
								if($.isFunction(cancel))cancel.call(_this);
								return false;
							}
						}
						_this.prevAll(':file[name="'+options.name.replace(/([\[\]])/g, '\\$1')+'"]').remove();
						let file = $('<input type="file" name="'+options.name+'" '+mul+' style="display:none;" />');
						_this.before(file);
						file.ajaxupload(opt, _this);
						file.trigger('click');
					});
				}
			}
		}else{
			let _ths = _this, isMaster = true;
			if(!$.isArray(fileType) && fileType.length)fileType = fileType.split(',');
			if(typeof master !== 'undefined'){
				_ths = master;
				isMaster = false;
			}
			if(options.rightnow && isMaster)change.call(_this[0], null);
			else{
				_this.on('change', function(e){
					change.call(this, e);
				});
			}
			function restore(){
				let _t = $(this);
				_t.val('').after(_t.outerHTML());
				let newElement = _t.next().attr('id', 'file_'+parseInt(String(Math.random() * 1000)));
				if(!options.rightnow){
					if(isMaster){
						newElement.ajaxupload(options);
					}else{
						if(options.innerFile)setTimeout(function(){_this.remove()},100);
						newElement.ajaxupload(options, _ths);
					}
				}
			}
			function change(event){
				if(!$(this).val().length){
					restore.call(this);
					return false;
				}
				if(fileType.length && !RegExp('\.('+fileType.join('|')+')$', 'i').test($(this).val().toLowerCase())){
					$.overloadError('请选择'+fileType.join(',')+'类型的文件');
					restore.call(this);
					return false;
				}
				if($.isFunction(before)){
					result = before.call(_ths, event, this.files.length, this.files);
					if(typeof(result) === 'boolean' && !result){
						if($.isFunction(cancel))cancel.call(_ths);
						restore.call(this);
						return false;
					}
				}
				if($.isFunction(replace)){
					replace.call(_ths, $(this));
					return false;
				}
				if(!!load){
					if(/^[.#]\w+$/.test(load)){
						loading = $(load);
					}else if(!loading.length){
						loading = $('<img src="'+load+'" border="0" align="absmiddle" style="margin-left:5px;" />');
						_ths.after(loading);
					}
				}
				if(!!data){
					if($.isPlainObject(data)){
						dat = data;
					}else if($.isFunction(data)){
						dat = data();
					}else{
						s = evil(data);
						if($.isPlainObject(s)){
							dat = s;
						}else if($.isFunction(s)){
							dat = s();
						}else{
							dat = {};
						}
					}
				}
				$.event.trigger('ajaxStart');
				let frameId = 'frame_'+parseInt(String(Math.random() * 1000)),
					iframe = $('<iframe name="'+frameId+'" id="'+frameId+'" style="position:absolute;top:-9999px;left:-9999px;" src="javascript:void(0)"></iframe>'),
					form = $('<form style="position:absolute;top:-9999px;left:-9999px;" action="" method="post" enctype="multipart/form-data"></form>');
				for(let i in dat)form.append('<input type="hidden" name="'+i+'" value="'+dat[i]+'" />');
				_this.parents('body').append(iframe);
				let io = iframe[0], xml = {};
				_this.parents('body').append(form);
				if(!options.rightnow){
					_this.after(_this.outerHTML());
					let newElement = _this.next().attr('id', 'file_'+parseInt(String(Math.random() * 1000)));
					if(isMaster){
						newElement.ajaxupload(options);
					}else{
						if(options.innerFile)setTimeout(function(){_this.remove()}, 100);
						newElement.ajaxupload(options, _ths);
					}
				}
				if(!!!_this.attr('name'))_this.attr('name', options.name);
				form.append(_this);
				$.event.trigger('ajaxSend', [xml, options]);
				if(!!callback){
					if($.isPlainObject(callback)){
						s = callback;
					}else if($.isFunction(callback)){
						s = {
							success: callback,
							error: function(data, status, e){
								$.overloadError('Upload error<br />'+e);
							}
						};
					}else{
						s = evil(callback);
						if($.isPlainObject(s)){
							//s = s;
						}else if($.isFunction(s)){
							s = {
								success: s,
								error: function(data, status, e){
									$.overloadError('Upload error<br />'+e);
								}
							};
						}else{
							s = {};
						}
					}
				}
				if($.isFunction(error))s.error = error;
				if($.isFunction(complete))s.complete = complete;
				let uploadHttpData = function(r, type){
						let target = !type;
						target = (type === 'xml' || target) ? r.responseXML : r.responseText;
						if(!target || !target.length)return '';
						// If the type is 'script', eval it in global context
						if(type === 'script')$.globalEval(target);
						// Get the JavaScript object, if JSON is used.
						if(type === 'json'){
							target = target.replace(/<pre.+?>(.+)<\/pre>/g, '$1');
							target = JSON.parse(target);
						}
						// evaluate scripts within html
						if(type === 'html')$('<div></div>').html(target).evalScripts();
						return target;
					},
					uploadCallback = function(){
						if(!isMaster && _ths.prev().is(':file'))_ths.prev().remove();
						try{
							if(io.contentWindow){
								xml.responseText = io.contentWindow.document.body ? io.contentWindow.document.body.innerHTML : null;
								xml.responseXML = io.contentWindow.document.XMLDocument ? io.contentWindow.document.XMLDocument : io.contentWindow.document;
							}else if(io.contentDocument){
								xml.responseText = io.contentDocument.document.body ? io.contentDocument.document.body.innerHTML : null;
								xml.responseXML = io.contentDocument.document.XMLDocument ? io.contentDocument.document.XMLDocument : io.contentDocument.document;
							}
						}catch(e){
							//if($.isFunction(s.error))s.error.call(_this, options, xml, null, e);
						}
						if(xml){
							let status;
							try{
								status = 'success';
								let data = uploadHttpData(xml, dataType);
								if($.isFunction(s.success))s.success.call(_ths, data, status);
								$.event.trigger('ajaxSuccess', [xml, options]);
								if(!!_this.data('checkform-checkHandle'))_this.data('checkform-checkHandle').call(_this);
							}catch(e){
								status = 'error';
								if($.isFunction(s.error))s.error.call(_ths, xml, status, e);
							}
							$.event.trigger('ajaxComplete', [xml, options]);
							$.event.trigger('ajaxStop');
							if($.isFunction(s.complete))s.complete.call(_ths, xml, status);
							$(io).off();
							setTimeout(function(){
								try{
									if(!options.debug){$(io).remove();form.remove()}
								}catch(e){
									if($.isFunction(s.error))s.error.call(_ths, xml, null, e);
								}
							}, 100);
							xml = null;
						}
						if(loading.length)loading.hide();
						$.overload(false);
					};
				try{
					form.attr('action', url).attr('target', target.length?target:frameId).submit();
					if($.isFunction(uploading))uploading.call(_ths);
				}catch(e){
					//$.handleError(xml, null, e);
					$.overloadError(e);
				}
				let loadIframe = function(callback){
					if(io.contentWindow){
						if(io.contentWindow.document.body && io.contentWindow.document.body.innerHTML.length){
							callback();
							return;
						}
					}else if(io.contentDocument){
						if(io.contentDocument.document.body && io.contentDocument.document.body.innerHTML.length){
							callback();
							return;
						}
					}
					setTimeout(function(){
						loadIframe(callback);
					}, 0);
				};
				$(io).on('load', function(){
					loadIframe(uploadCallback);
				});
			}
		}
	});
};

//HTML5拖拽上传
$.fn.html5upload = function(options){
	options = $.extend({
		url: '/upload', //文件数据处理URL
		name: 'filename', //上传字段名,如果字符串末尾加上叹号,即强制使用该字符串
		change: true, //选择文件后立即上传(不支持非file控件)
		rightnow: false, //不需要经过change之类的操作,立刻上传(控件已选择了文件)(不支持非file控件)
		data: null, //附加参数
		splitSize: 0, //把文件切片上传,例如切为5M:5*1024*1000,0为不切片(后端需自行处理切片组合,自动增加附加参数split_name:options.name+'_'+index,split_total:总数)
		fileType: ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg'], //允许上传文件类型,后缀名,数组或字符串(逗号隔开)
		dataType: 'json', //请求类型
		dragover: null, //拖动到对象上时执行
		dragleave: null, //拖动到对象外面时执行
		select: null, //选择文件后执行
		before: null, //上传之前执行,返回false即取消上传, 接受三个参数:e,选择的文件数量,选择的文件
		progress: null, //数据正在被post的过程中周期性执行,接受三个参数 e, e.loaded, e.total
		success: null, //post操作成功时执行
		error: null //失败时执行,例如选择了不允许的文件类型
	}, $('body').data('html5upload.options'), options);
	let ie8 = $.browser.ie8;
	return this.each(function(){
		let _this = $(this), url = _this.attr('data-url')||options.url, name = _this.attr('data-id')||_this.attr('data-name')||options.name, fileType = _this.attr('data-fileType')||options.fileType,
			data = {}, crlf = '\r\n', dashes = '--', boundary = 'WebKitFormBoundary'+$.randomCode(16), uploadCount = 0, loadedArray = [], res = [];
		if(!!_this.data('html5upload') && !options.rightnow)return true;
		_this.data('html5upload', true);
		if(options.name.substr(-1) === '!')name = options.name.substr(0, options.name.length-1);
		if(!$.isArray(fileType) && fileType.length)fileType = fileType.split(',');
		if(!!options.data){
			if($.isPlainObject(options.data)){
				data = options.data;
			}else if($.isFunction(options.data)){
				data = options.data();
			}else{
				let s = evil(options.data);
				if($.isPlainObject(s)){
					data = s;
				}else if($.isFunction(s)){
					data = s();
				}else{
					data = {};
				}
			}
		}
		if(_this.is(':file')){
			if(options.rightnow){
				if(_this[0].files.length)rightnow.call(_this[0]);
			}else{
				_this.on('change', function(e){
					if($.isFunction(options.select))options.select.call(_this);
					if(options.change)rightnow.call(this, e);
				});
			}
		}else{
			_this.on('dragover', function(e){
				e.preventDefault();
				if($.isFunction(options.dragover))options.dragover.call(_this);
				return false;
			}).on('dragleave', function(e){
				e.preventDefault();
				if($.isFunction(options.dragleave))options.dragleave.call(_this);
				return false;
			}).on('drop', function(e){
				e.preventDefault();
				if(!!_this.data('uploading'))return false;
				_this.data('uploading', true);
				let files = e.originalEvent.dataTransfer.files;
				if($.isFunction(options.before)){
					let result = options.before.call(_this, e, files.length, files);
					if(typeof result === 'boolean' && !result)return false;
				}
				res = [];
				loadedArray = [];
				uploadCount = files.length;
				for(let i=0; i<files.length; i++){
					if(fileType.length && !RegExp('\.('+fileType.join('|')+')$', 'i').test(files[i].name.toLowerCase())){
						if(files.length === 1){
							_this.removeData('uploading');
							$.overloadError('请选择'+fileType.join(',')+'类型的文件');
							if($.isFunction(options.error))options.error.call(_this, files[i]);
							return false;
						}
						continue;
					}
					if(options.splitSize>0){
						let start = 0, end = 0, splitTotal = Math.ceil(files[i].size/options.splitSize);
						for(let j=0; j<splitTotal; j++){
							loadedArray.push(0);
						}
						for(let j=0; j<splitTotal; j++){
							data['split_name'] = name + '_' + j;
							data['split_total'] = splitTotal;
							start = end;
							end = start + options.splitSize;
							fileHandler(files[i].slice(start, end), files[i].name, files[i].size, j);
						}
					}else{
						fileHandler(files[i]);
					}
				}
				return false;
			});
		}
		_this.data('upload', function(){
			rightnow.call(_this[0]);
		});
		function rightnow(e){
			if(!!_this.data('uploading'))return false;
			_this.data('uploading', true);
			if(ie8){_this.ajaxupload({url:url, name:name, data:dat, before:options.before, callback:options.success});return false}
			if($.isFunction(options.before)){
				let result = options.before.call(_this, e, this.files.length, this.files);
				if(typeof result === 'boolean' && !result)return false;
			}
			res = [];
			loadedArray = [];
			let files = this.files;
			uploadCount = files.length;
			for(let i=0; i<files.length; i++){
				if(fileType.length && !RegExp('\.('+fileType.join('|')+')$', 'i').test(files[i].name.toLowerCase())){
					if(files.length === 1){
						_this.removeData('uploading');
						$.overloadError('请选择'+fileType.join(',')+'类型的文件');
						if($.isFunction(options.error))options.error.call(_this, files[i]);
						return false;
					}
					continue;
				}
				if(options.splitSize>0){
					let start = 0, end = 0, splitTotal = Math.ceil(files[i].size/options.splitSize);
					for(let j=0; j<splitTotal; j++){
						loadedArray.push(0);
					}
					for(let j=0; j<splitTotal; j++){
						data['split_name'] = name + '_' + j;
						data['split_total'] = splitTotal;
						start = end;
						end = start + options.splitSize;
						fileHandler(files[i].slice(start, end), files[i].name, files[i].size, j);
					}
				}else{
					fileHandler(files[i]);
				}
			}
		}
		function uploadHttpData(text, type){
			let target = text;
			if(type === 'xml')target = text;
			// If the type is 'script', eval it in global context
			if(type === 'script')$.globalEval(target);
			// Get the JavaScript object, if JSON is used.
			if(type === 'json'){
				target = target.replace(/<pre.+?>(.+)<\/pre>/g, '$1');
				target = JSON.parse(target);
			}
			// evaluate scripts within html
			if(type === 'html')$('<div></div>').html(target).evalScripts();
			return target;
		}
		function fileHandler(file, fileName, fileSize, j){
			let xml = new XMLHttpRequest();
			xml.addEventListener('load', function(e){
				if(e.target.responseText.length)res.push(uploadHttpData(e.target.responseText, options.dataType));
				if(res.length === uploadCount){
					_this.removeData('uploading');
					if($.isFunction(options.success))options.success.call(_this, res.length===1 ? res[0] : res);
					if(_this.is(':file')){
						_this.after(_this.outerHTML());
						_this.next().html5upload(options);
						_this.remove();
					}
				}
			}, false);
			xml.upload.addEventListener('progress', function(e){
                if(e.lengthComputable){
	                if($.isFunction(options.progress)){
		                let d = $.extend({}, e), loaded = e.loaded, total = e.total;
		                if(typeof fileName !== 'undefined'){
			                loaded = 0;
			                total = fileSize;
			                loadedArray[j] = e.loaded;
			                for(let i=0; i<loadedArray.length; i++){
				                loaded += loadedArray[i];
			                }
			                d.loaded = loaded;
			                d.total = total;
		                }
		                options.progress.call(_this, d, loaded, total);
		                //let percent = Math.round((e.loaded / e.total) * 100, 1);
		                //$(expr).css({width:percent+'%'});
	                }
                }
			}, false);
			xml.open('POST', url, true);
			if(window.FormData){
				let formData = new FormData();
				formData.append(name, file);
				$.each(data, function(k, g){ formData.append(k, g) });
				xml.send(formData);
			}else if(file.getAsBinary){
				let formData = dashes + boundary + crlf + 'Content-Disposition: form-data;' + 'name="' + name + '";' + 'filename="' + unescape(encodeURIComponent(fileName)) + '"' + crlf + 'Content-Type: application/octet-stream' + crlf+crlf + file.getAsBinary() + crlf;
				for(let d in data)formData += dashes + boundary + crlf + 'Content-Disposition: form-data;' + 'name="' + d + '"' + crlf+crlf + data[d] + crlf;
				formData += dashes + boundary + dashes;
				xml.setRequestHeader('Content-Type', 'multipart/form-data;boundary=' + boundary);
				xml.sendAsBinary(formData);
			}else{
				$.overloadError('浏览器不支持html5');
			}
			return false;
		}
	});
};

//颜色选择器
$.fn.colorpicker = function(options){
	options = $.extend({
		size: 15, //色块大小, 单位像素
		type: '', //small:36色, big:228色, panel:调色板, 默认:small
		target: '', //选择颜色后填写颜色的目标元素(为空即代表当前点击对象,function)
		always: false, //平板模式, 直接显示在调用者的html内, 一般与target参数同时使用
		readonly: true, //可否填写
		transparent: false, //是否显示透明色
		position: true, //使用postion还是offset获取xy
		partner: '', //设置点击不隐藏的伙伴
		reverseTarget: '', //到容器边缘反转显示, 不设置即根据window
		useClick: true, //使用click绑定useClick, 否则自行调用 .trigger('colorpicker.click') 且设置 partner 参数
		sep: 4, //y相隔, 负数在上面显示
		over: null, //鼠标移到颜色上执行, 两个参数:颜色代码,目标对象
		out: null, //鼠标移出颜色后执行, 一个参数:目标对象
		shown: null, //显示后执行, 两个参数:colorpicker,目标对象
		hidden: null, //隐藏后执行, 两个参数:colorpicker,目标对象
		callback: null, //选择后执行, 两个参数:颜色代码,目标对象
		table: [
			['#E53333', '#E56600', '#FF9900', '#64451D', '#DFC5A4', '#FFE500'],
			['#009900', '#006600', '#99BB00', '#B8D100', '#60D978', '#00D5FF'],
			['#337FE5', '#003399', '#4C33E5', '#9933E5', '#CC33E5', '#EE33EE'],
			['#DDDDDD', '#DC0431', '#00BE14', '#0095D9', '#FFC700', '#A35DB5'],
			['transparent', '#3F51B5', '#FF4081', '#E51C23', '#259B24', '#FF9800'],
			['#FFFFFF', '#CCCCCC', '#999999', '#666666', '#333333', '#000000']
		],
		bigTable: [
			['#000000', '#CCFFFF', '#CCFFCC', '#CCFF99', '#CCFF66', '#CCFF33', '#CCFF00', '#66FF00', '#66FF33', '#66FF66', '#66FF99', '#66FFCC', '#66FFFF', '#00FFFF', '#00FFCC', '#00FF99', '#00FF66', '#00FF33', '#00FF00'],
			['#333333', '#CCCCFF', '#CCCCCC', '#CCCC99', '#CCCC66', '#CCCC33', '#CCCC00', '#66CC00', '#66CC33', '#66CC66', '#66CC99', '#66CCCC', '#66CCFF', '#00CCFF', '#00CCCC', '#00CC99', '#00CC66', '#00CC33', '#00CC00'],
			['#666666', '#CC99FF', '#CC99CC', '#CC9999', '#CC9966', '#CC9933', '#CC9900', '#669900', '#669933', '#669966', '#669999', '#6699CC', '#6699FF', '#0099FF', '#0099CC', '#009999', '#009966', '#009933', '#009900'],
			['#999999', '#CC66FF', '#CC66CC', '#CC6699', '#CC6666', '#CC6633', '#CC6600', '#666600', '#666633', '#666666', '#666699', '#6666CC', '#6666FF', '#0066FF', '#0066CC', '#006699', '#006666', '#006633', '#006600'],
			['#CCCCCC', '#CC33FF', '#CC33CC', '#CC3399', '#CC3366', '#CC3333', '#CC3300', '#663300', '#663333', '#663366', '#663399', '#6633CC', '#6633FF', '#0033FF', '#0033CC', '#003399', '#003366', '#003333', '#003300'],
			['#FFFFFF', '#CC00FF', '#CC00CC', '#CC0099', '#CC0066', '#CC0033', '#CC0000', '#660000', '#660033', '#660066', '#660099', '#6600CC', '#6600FF', '#0000FF', '#0000CC', '#000099', '#000066', '#000033', '#000000'],
			['#FF0000', '#FF00FF', '#FF00CC', '#FF0099', '#FF0066', '#FF0033', '#FF0000', '#990000', '#990033', '#990066', '#990099', '#9900CC', '#9900FF', '#3300FF', '#3300CC', '#330099', '#330066', '#330033', '#330000'],
			['#00FF00', '#FF33FF', '#FF33CC', '#FF3399', '#FF3366', '#FF3333', '#FF3300', '#993300', '#993333', '#993366', '#993399', '#9933CC', '#9933FF', '#3333FF', '#3333CC', '#333399', '#333366', '#333333', '#333300'],
			['#0000FF', '#FF66FF', '#FF66CC', '#FF6699', '#FF6666', '#FF6633', '#FF6600', '#996600', '#996633', '#996666', '#996699', '#9966CC', '#9966FF', '#3366FF', '#3366CC', '#336699', '#336666', '#336633', '#336600'],
			['#FFFF00', '#FF99FF', '#FF99CC', '#FF9999', '#FF9966', '#FF9933', '#FF9900', '#999900', '#999933', '#999966', '#999999', '#9999CC', '#9999FF', '#3399FF', '#3399CC', '#339999', '#339966', '#339933', '#339900'],
			['#00FFFF', '#FFCCFF', '#FFCCCC', '#FFCC99', '#FFCC66', '#FFCC33', '#FFCC00', '#99CC00', '#99CC33', '#99CC66', '#99CC99', '#99CCCC', '#99CCFF', '#33CCFF', '#33CCCC', '#33CC99', '#33CC66', '#33CC33', '#33CC00'],
			['transparent', '#FF00FF', '#FF00CC', '#FF0099', '#FF0066', '#FF0333', '#FF0000', '#99FF00', '#99FF33', '#99FF66', '#99FF99', '#99FFCC', '#99FFFF', '#33FFFF', '#33FFCC', '#33FF99', '#33FF66', '#33FF33', '#33FF00'],
		]
	}, $('body').data('colorpicker.options'), options);
	return this.each(function(){
		let _this = $(this), el = _this, colorpicker = null, ie6 = $.browser.ie6, hsb = {h:0, s:0, b:100},
			regColor = function(color){
				if(/^#?([0-9a-fA-f]{3}|[0-9a-fA-f]{6})$/.test(color))return hexToRgb(color);
				let reg = /^rgba?\((\d+)\s*,\s*(\d+)\s*,\s*(\d+)(?:\s*,\s*([\d.]+))?\)$/;
				if(!reg.test(color))return {r:0, g:0, b:0, a:1};
				let arr = reg.exec(color);
				let r = Number(arr[1]), g = Number(arr[2]), b = Number(arr[3]), a = (typeof arr[4] === 'undefined' ? 1 : Number(arr[4]));
				return {
					r: Math.min(255, Math.max(0, r)),
					g: Math.min(255, Math.max(0, g)),
					b: Math.min(255, Math.max(0, b)),
					a: Math.min(1, Math.max(0, a))
				};
			},
			hexToRgb = function(hex){
				hex = hex.indexOf('#')>-1 ? hex.substring(1) : hex;
				hex.length === 3 && (hex = hex.split(''), hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2]);
				hex = parseInt(hex, 16);
				return {
					r: hex >> 16,
					g: (hex & 65280) >> 8,
					b: hex & 255,
					a: 1
				};
			},
			rgbToHex = function(rgb){
				let _rgb = [rgb.r.toString(16), rgb.g.toString(16), rgb.b.toString(16)];
				$.each(_rgb, function(i){
					this.length === 1 && (_rgb[i] = '0'+this);
				});
				return '#' + _rgb.join('').toUpperCase();
			},
			hsbToRgb = function(hsb){
				let rgb = {};
				let h = Math.round(hsb.h);
				let s = Math.round(hsb.s*255/100);
				let v = Math.round(hsb.b*255/100);
				if(s === 0) {
					rgb.r = rgb.g = rgb.b = v;
				} else {
					let t1 = v;
					let t2 = (255-s)*v/255;
					let t3 = (t1-t2)*(h%60)/60;
					if(h === 360) h = 0;
					if(h<60) {rgb.r=t1;	rgb.b=t2; rgb.g=t2+t3}
					else if(h<120) {rgb.g=t1; rgb.b=t2;	rgb.r=t1-t3}
					else if(h<180) {rgb.g=t1; rgb.r=t2;	rgb.b=t2+t3}
					else if(h<240) {rgb.b=t1; rgb.r=t2;	rgb.g=t1-t3}
					else if(h<300) {rgb.b=t1; rgb.g=t2;	rgb.r=t2+t3}
					else if(h<360) {rgb.r=t1; rgb.g=t2;	rgb.b=t1-t3}
					else {rgb.r=0; rgb.g=0;	rgb.b=0}
				}
				return {r:Math.round(rgb.r), g:Math.round(rgb.g), b:Math.round(rgb.b), a:1};
			},
			rgbToHsb = function(rgb){
				let hsb = {h:0, s:0, b:0},
					min = Math.min(rgb.r, rgb.g, rgb.b),
					max = Math.max(rgb.r, rgb.g, rgb.b),
					between = max - min;
				hsb.b = max;
				hsb.s = max !== 0 ? 255 * between / max : 0;
				hsb.h = hsb.s !== 0 ? rgb.r === max ? (rgb.g - rgb.b) / between : rgb.g === max ? 2 + (rgb.b - rgb.r) / between : 4 + (rgb.r - rgb.g) / between : -1;
				between === min && (hsb.h = 0);
				hsb.h *= 60;
				hsb.h < 0 && (hsb.h += 360);
				hsb.s *= 100 / 255;
				hsb.b *= 100 / 255;
				return {h:Math.round(hsb.h), s:Math.round(hsb.s), b:Math.round(hsb.b)};
			},
			hsbToHex = function(hsb){
				return rgbToHex(hsbToRgb(hsb));
			},
			/*hexToHsb = function(hex){
				return rgbToHsb(hexToRgb(hex));
			},*/
			setRgbInput = function(rgb){
				colorpicker.find('.r').val(rgb.r);
				colorpicker.find('.g').val(rgb.g);
				colorpicker.find('.b').val(rgb.b);
				colorpicker.find('.a').val(rgb.a*100);
			},
			setHexInput = function(hex){
				hex = hex.indexOf('#')>-1 ? hex.substr(1) : hex;
				colorpicker.find('.hex').val(hex);
			},
			setSection = function(hsb){
				let section = colorpicker.find('section'), sectionWidth = section.outerWidth(false), sectionHeight = section.outerHeight(false);
				colorpicker.find('section div:eq(0)').css('background-color', hsbToHex({h:hsb.h, s:100, b:100}));
				colorpicker.find('section i').css({left:parseInt(String(sectionWidth*hsb.s/100)), top:parseInt(String(sectionHeight*(100-hsb.b)/100))});
			},
			setBandColor = function(rgb, hsb){
				let parentWidth = colorpicker.find('.band-color').outerWidth(false);
				colorpicker.find('.band-color span').css('left', parseInt(String(parentWidth*hsb.h/360)));
			},
			setBandAlpha = function(rgb){
				let parentWidth = colorpicker.find('.band-alpha').outerWidth(false), _rgb = rgb.r + ',' + rgb.g + ',' + rgb.b;
				colorpicker.find('.band-alpha span').css('left', Number(rgb.a)*parentWidth);
				colorpicker.find('.band-alpha div').css('background', 'linear-gradient(to right, rgba('+_rgb+',0) 0%, rgba('+_rgb+',1) 100%)');
			},
			setPreview = function(rgb){
				let _rgb = rgb.r + ',' + rgb.g + ',' + rgb.b + ',' + rgb.a;
				colorpicker.find('.preview-color span').css('background-color', 'rgba('+_rgb+')');
			},
			initPanel = function(color){
				if(!color)return {rgb:{r:0, g:0, b:0, a:100}, hex:'#000000', hsb:{h:0, s:0, b:0}};
				let rgb = regColor(color), hex = rgbToHex(rgb);
				hsb = rgbToHsb(rgb);
				setRgbInput(rgb);
				setHexInput(hex);
				setSection(hsb);
				setBandColor(rgb, hsb);
				setBandAlpha(rgb);
				setPreview(rgb);
				return {rgb:rgb, hex:hex, hsb:hsb};
			};
		if(!!_this.attr('target')){
			el = $(_this.attr('target'));
		}else if(options.target){
			if($.isFunction(options.target)){
				el = options.target.call(_this);
			}else{
				el = $(options.target);
			}
		}
		if(!_this.is('input')){
			if(!!(_this.attr('size')*1)){let tmp=evil(_this.attr('size'));options = $.extend(options, {size:tmp})}
			if(_this.attr('type'))options = $.extend(options, {type:_this.attr('type')});
		}
		el.attr('readonly', options.readonly);
		if(options.always){
			initColor();
		}else{
			if(options.useClick)_this.on('click', initColor);
			_this.on('colorpicker.click', initColor);
		}
		function initColor(){
			colorpicker = _this.data('colorpicker');
			if(!!!colorpicker){
				if(options.type !== 'panel'){
					let div, w, size = options.size, colors = options.type === 'big' ? options.bigTable : options.table;
					colorpicker = $('<div class="colorpickerView" style="position:absolute;z-index:888;left:0;top:0;width:auto;height:auto;"><div style="font-size:12px;background:#f5f3e5;padding:1px;border-radius:3px;box-shadow:0 0 5px rgba(0,0,0,0.3);"></div></div>');
					_this.data('colorpicker', colorpicker);
					if(options.always){
						_this.append(colorpicker.css('position', 'static').attr('always', 'true'));
						colorpicker.children('div').css('position', 'static')
					}else{
						if(options.position){
							_this.after(colorpicker);
						}else{
							$('body').append(colorpicker);
						}
					}
					div = colorpicker.children('div');
					w = colors[0].length*(size+2);
					div.width(w);
					for (let i = 0; i < colors.length; i++) {
						if (colors[i]) {
							let row = $('<div style="padding:1px 0;width:auto;height:'+size+'px;overflow:hidden;"></div>');
							div.append(row);
							for (let j = 0; j < colors[i].length; j++) {
								let c = colors[i][j];
								if (!options.transparent && c === 'transparent') {
									if (options.type === 'big') {c = '#000000'} else {c = '#F3F3F3'}
								}
								let cell = $('<div style="float:left;margin:0 1px;display:inline;cursor:pointer;width:'+size+'px;height:'+size+'px;overflow:hidden;box-sizing:border-box;background-color:'+c+';'+(c === 'transparent'?'background-image:linear-gradient(45deg, #ccc 26%, transparent 26%), linear-gradient(-45deg, #ccc 26%, transparent 26%), linear-gradient(45deg, transparent 73%, #ccc 73%), linear-gradient(-45deg, transparent 73%, #ccc 73%);background-size:6px 6px;background-position:0 0, 0 3px, 3px -3px, -3px 0;':'')+';" color="'+c+'" title="'+(c === 'transparent'?'透明':c)+'"></div>');
								row.append(cell);
								cell.click(function(){
									let fn, color = $(this).attr('color');
									if(options.target !== null)el.val(color);
									if(!options.always)removeControl();
									if($.isFunction(options.callback))options.callback.call(_this, color, el);
									fn = el.attr('fn');
									if(!!fn){
										let func = evil(fn);
										if($.isFunction(func))func(color, el);
									}
									if(!!el.data('checkform-checkHandle'))el.data('checkform-checkHandle').call(el);
								}).hover(function(){
									if($.isFunction(options.over))options.over.call(_this, $(this).attr('color'), el);
								},function(){
									if($.isFunction(options.out))options.out.call(_this, el);
								});
							}
							row.append('<div tyle="clear:both;font-size:0;width:auto;height:0;overflow:hidden;"></div>');
						}
					}
					if(ie6){
						let iframe = $('<iframe frameborder="0"></iframe>');
						colorpicker.append(iframe);
						iframe.css({
							width: colorpicker.children('div').outerWidth(false),
							height: colorpicker.children('div').outerHeight(false)
						});
					}
					let win = $.window(), reverseTarget = options.reverseTarget.length ? $(options.reverseTarget) : '',
						position = options.position ? _this.position() : _this.offset(),
						cl = reverseTarget.length ? reverseTarget.scrollLeft() : win.scrollLeft, ct = reverseTarget.length ? reverseTarget.scrollTop() : win.scrollTop,
						cw = reverseTarget.length ? reverseTarget.width() : win.width, ch = reverseTarget.length ? reverseTarget.height() : win.height,
						posl = cl + position.left, post = 0;
					if(options.sep>=0){
						post = ct + position.top + _this.outerHeight(false) + options.sep;
						if(position.left+colorpicker.children('div').outerWidth(false)>win.scrollLeft+cw)posl = cl + position.left - colorpicker.children('div').outerWidth(false);
						if(position.top+colorpicker.children('div').outerHeight(false)>win.scrollTop+ch)post = ct + position.top - colorpicker.children('div').outerHeight(false) - options.sep;
					}else{
						post = ct + position.top - colorpicker.children('div').outerHeight(false) + options.sep;
					}
					colorpicker.css({left:posl, top:post});
				}else{
					colorpicker = $('<div class="colorpickerView colorpickerPanel" style="position:absolute;z-index:888;left:0;top:0;width:262px;height:auto;overflow:hidden;background:#fff;border-radius:4px;padding:10px;box-sizing:border-box;box-shadow:0 0 5px rgba(0,0,0,0.3);user-select:none;"></div>');
					_this.data('colorpicker', colorpicker);
					if(options.always){
						_this.append(colorpicker.css({position:'static', 'box-shadow':'0 0 5px rgba(0,0,0,0.3)'}).attr('always', 'true'));
					}else{
						if(options.position){
							_this.after(colorpicker);
						}else{
							$('body').append(colorpicker);
						}
					}
					let callback = function(color){
						let rgba = color, rgb = {};
						if(color === 'transparent'){
							rgb = {
								r: Math.max(0, Math.min(255, parseInt(colorpicker.find('.r').val()))),
								g: Math.max(0, Math.min(255, parseInt(colorpicker.find('.g').val()))),
								b: Math.max(0, Math.min(255, parseInt(colorpicker.find('.b').val()))),
								a: Math.max(0, Math.min(1, parseInt(colorpicker.find('.a').val())/100))
							};
							rgba = 'rgba('+rgb.r+','+rgb.g+','+rgb.b+',0)';
						}
						if(/^#[0-9a-fA-f]{6}$/.test(color)){
							rgb = regColor(color);
							rgba = 'rgba('+rgb.r+','+rgb.g+','+rgb.b+','+rgb.a+')';
						}else{
							rgb = regColor(color);
							color = rgbToHex(rgb);
						}
						if(rgb.a === 0)color = 'transparent';
						if(options.target !== null){
							el.val(color);
						}
						_this.attr('color', rgba);
						if($.isFunction(options.callback))options.callback.call(_this, color, el, rgba);
						let fn = el.attr('fn');
						if(!!fn){
							let func = evil(fn);
							if($.isFunction(func))func(color, el, rgba);
						}
						if(!!el.data('checkform-checkHandle'))el.data('checkform-checkHandle').call(el);
					};
					let header = $('<ul style="width:100%;height:18px;margin:0 0 10px 0;">\
						<li color="#FFFFFF"></li>\
						<li color="#3F51B5"></li>\
						<li color="#FF4081"></li>\
						<li color="#E51C23"></li>\
						<li color="#009688"></li>\
						<li color="#259B24"></li>\
						<li color="#8BC34A"></li>\
						<li color="#FF9800"></li>\
						<li color="#F8E71C"></li>\
					</ul>');
					colorpicker.append(header);
					header.find('li').css({'float':'left', 'list-style':'none', 'box-sizing':'border-box', width:18, height:18, 'margin-right':'10px', cursor:'pointer', 'border-radius':'2px', border:'1px solid rgba(0, 0, 0, 0.12)'});
					header.find('li:last').css('margin-right', 0);
					header.find('li').each(function(){
						let color = $(this).attr('color');
						if(color === 'transparent'){
							$(this).css({
								'background-image':'linear-gradient(45deg, #ccc 26%, transparent 26%), linear-gradient(-45deg, #ccc 26%, transparent 26%), linear-gradient(45deg, transparent 73%, #ccc 73%), linear-gradient(-45deg, transparent 73%, #ccc 73%)',
								'background-size':'6px 6px',
								'background-position':'0 0, 0 3px, 3px -3px, -3px 0'
							});
						}else{
							$(this).css('background-color', color);
						}
					}).on('click', function(){
						let color = $(this).attr('color');
						if(!options.always){
							removeControl();
						}else{
							colorpicker.find('section i, .band-color span, .band-alpha span').css({
								'-webkit-transition':'all 0.1s ease-out', transition:'all 0.1s ease-out'
							});
							let r = initPanel(color);
							hsb = r.hsb;
							setTimeout(function(){
								colorpicker.find('section i, .band-color span, .band-alpha span').css({'-webkit-transition':'', transition:''});
							}, 300);
						}
						callback(color);
					});
					let hub = $('<div style="width:100%;height:154px;margin-bottom:10px;">\
						<section style="position:relative;width:100%;height:120px;margin-bottom:10px;cursor:default;">\
							<div style="position:absolute;left:0;top:0;width:100%;height:100%;pointer-events:none;background:rgb(0, 110, 255);"></div>\
							<div style="position:absolute;left:0;top:0;width:100%;height:100%;pointer-events:none;background:linear-gradient(to right, white 0%, transparent 100%);"></div>\
							<div style="position:absolute;left:0;top:0;width:100%;height:100%;pointer-events:none;background:linear-gradient(to bottom, transparent 0%, black 100%);"></div>\
							<i style="position:absolute;display:block;box-sizing:border-box;width:6px;height:6px;box-shadow:0 0 2px 0 rgba(0,0,0,0.5);border:1px solid #fff;border-radius:50%;pointer-events:none;-webkit-transform:translate(-50%,-50%);transform:translate(-50%,-50%);"></i>\
						</section>\
						<div class="row" style="height:24px;padding:0 32px;">\
							<div class="preview-color" style="float:right;box-sizing:border-box;width:24px;height:24px;margin-right:-32px;border:1px solid #e0e0e0;border-radius:2px;background-image:linear-gradient(45deg, #ccc 25%, transparent 25%), linear-gradient(-45deg, #ccc 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #ccc 75%), linear-gradient(-45deg, transparent 75%, #ccc 75%);background-size:6px 6px;background-position:0 0, 0 3px, 3px -3px, -3px 0;">\
								<span style="display:block;width:100%;height:100%;"></span>\
							</div>\
							<div class="outside-color" style="float:left;box-sizing:border-box;width:24px;height:24px;margin-left:-32px;cursor:pointer;border:1px solid #e0e0e0;border-radius:2px;background:url(data:image/svg+xml;charset=utf-8,%3Csvg%20viewBox%3D%220%200%201024%201024%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Cpath%20d%3D%22M988.16%2092.16L926.72%2030.72c-40.96-40.96-102.4-40.96-143.36%200l-143.36%20143.36L573.44%20102.4%20440.32%20240.64l56.32%2056.32-389.12%20394.24c-25.6%2025.6-40.96%2061.44-40.96%2092.16l-20.48%2020.48c-46.08%2051.2-46.08%20128%200%20179.2%2020.48%2025.6%2051.2%2040.96%2081.92%2040.96s66.56-15.36%2087.04-35.84l20.48-20.48c35.84%200%2066.56-15.36%2092.16-40.96l389.12-394.24%2056.32%2056.32%20138.24-138.24-66.56-71.68%20143.36-143.36c40.96-40.96%2040.96-102.4%200-143.36m-716.8%20768c-10.24%2010.24-25.6%2015.36-35.84%2015.36-10.24%200-15.36%200-25.6-5.12L153.6%20921.6c-5.12%205.12-15.36%2010.24-25.6%2010.24s-20.48-5.12-25.6-10.24c-15.36-15.36-15.36-35.84%200-51.2l56.32-51.2c-10.24-20.48-5.12-46.08%2010.24-61.44l389.12-394.24%20102.4%20102.4-389.12%20394.24z%20m0%200%22%20fill%3D%22%238d9ea7%22%3E%3C%2Fpath%3E%3C%2Fsvg%3E) no-repeat center center;background-size:12px 12px;">\
								<input type="color" style="float:left;cursor:pointer;width:100%;height:100%;border:none;opacity:0;" />\
							</div>\
							<div style="width:100%;height:24px;">\
								<div class="band-color" style="width:100%;height:10px;cursor:pointer;margin-bottom:4px;background-image:linear-gradient(to left, red, #ff0080, magenta, #8000ff, blue, #0080ff, cyan, #00ff80, lime, #80ff00, yellow, #ff8000, red);">\
									<div style="position:relative;width:100%;height:100%;">\
										<span style="display:block;position:absolute;top:-1px;width:5px;height:12px;margin-left:-2.5px;background:#f6f7f8;box-shadow:0 0 1px 0 rgba(0,0,0,0.34);border-radius:3px;z-index:2;pointer-events:none;"></span>\
									</div>\
								</div>\
								<div class="band-alpha" style="width:100%;height:10px;cursor:pointer;background-image:linear-gradient(45deg, #ccc 25%, transparent 25%), linear-gradient(-45deg, #ccc 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #ccc 75%), linear-gradient(-45deg, transparent 75%, #ccc 75%);background-size:6px 6px;background-position:0 0, 0 3px, 3px -3px, -3px 0;">\
									<div style="position:relative;width:100%;height:100%;">\
										<span style="display:block;position:absolute;top:-1px;width:5px;height:12px;margin-left:-2.5px;background:#f6f7f8;box-shadow:0 0 1px 0 rgba(0,0,0,0.34);border-radius:3px;z-index:2;pointer-events:none;"></span>\
									</div>\
								</div>\
							</div>\
						</div>\
					</div>');
					colorpicker.append(hub);
					let section = hub.find('section'), sectionWidth = section.outerWidth(false), sectionHeight = section.outerHeight(false),
						sectionStart = function(e){
							e.preventDefault();
							sectionMove(e);
							$(document).on('mousemove', sectionMove);
							$(document).on('mouseup', sectionEnd);
							if(window.addEventListener){
								window.addEventListener('touchmove', sectionMove, {passive:false});
								window.addEventListener('touchend', sectionEnd, {passive:false});
							}
						},
						sectionMove = function(e){
							e.preventDefault();
							let sectionOffset = section.offset(), touches = $.touches(e);
							hsb.s = parseInt(String(100*(Math.max(0,Math.min(sectionWidth,(touches.x - sectionOffset.left))))/sectionWidth), 10);
							hsb.b = parseInt(String(100*(sectionHeight - Math.max(0,Math.min(sectionHeight,(touches.y - sectionOffset.top))))/sectionHeight), 10);
							hsb = {
								h: Math.min(360, Math.max(0, parseInt(hsb.h))),
								s: Math.min(100, Math.max(0, parseInt(hsb.s))),
								b: Math.min(100, Math.max(0, parseInt(hsb.b)))
							};
							let rgb = hsbToRgb(hsb), hex = hsbToHex(hsb);
							rgb.a = footer.find('.a').val()/100;
							setRgbInput(rgb);
							setHexInput(hex);
							setSection(hsb);
							setBandColor(rgb, hsb);
							setBandAlpha(rgb);
							setPreview(rgb);
							let color = 'rgba('+rgb.r+','+rgb.g+','+rgb.b+','+rgb.a+')';
							callback(color);
						},
						sectionEnd = function(e){
							e.preventDefault();
							$(document).off('mousemove', sectionMove);
							$(document).off('mouseup', sectionEnd);
							if(window.addEventListener){
								window.removeEventListener('touchmove', sectionMove, {passive:false});
								window.removeEventListener('touchend', sectionEnd, {passive:false});
							}
						};
					section.on('mousedown', sectionStart);
					if(window.addEventListener){
						section[0].addEventListener('touchstart', sectionStart, true);
						section[0].addEventListener('touchcancel', sectionEnd, true);
					}
					let bandColor = hub.find('.band-color'), bandWidth = bandColor.outerWidth(false),
						bandColorStart = function(e){
							e.preventDefault();
							bandColorMove(e);
							$(document).on('mousemove', bandColorMove);
							$(document).on('mouseup', bandColorEnd);
							if(window.addEventListener){
								window.addEventListener('touchmove', bandColorMove, {passive:false});
								window.addEventListener('touchend', bandColorEnd, {passive:false});
							}
						},
						bandColorMove = function(e){
							e.preventDefault();
							let bandOffset = bandColor.offset(), touches = $.touches(e);
							hsb.h = 360 - parseInt(String(360*(bandWidth - Math.max(0,Math.min(bandWidth,(touches.x - bandOffset.left))))/bandWidth), 10);
							hsb = {
								h: Math.min(360, Math.max(0, parseInt(hsb.h))),
								s: Math.min(100, Math.max(0, parseInt(hsb.s))),
								b: Math.min(100, Math.max(0, parseInt(hsb.b)))
							};
							let rgb = hsbToRgb(hsb), hex = hsbToHex(hsb);
							rgb.a = footer.find('.a').val()/100;
							setRgbInput(rgb);
							setHexInput(hex);
							setSection(hsb);
							setBandColor(rgb, hsb);
							setBandAlpha(rgb);
							setPreview(rgb);
							let color = 'rgba('+rgb.r+','+rgb.g+','+rgb.b+','+rgb.a+')';
							callback(color);
						},
						bandColorEnd = function(e){
							e.preventDefault();
							$(document).off('mousemove', bandColorMove);
							$(document).off('mouseup', bandColorEnd);
							if(window.addEventListener){
								window.removeEventListener('touchmove', bandColorMove, {passive:false});
								window.removeEventListener('touchend', bandColorEnd, {passive:false});
							}
						};
					bandColor.on('mousedown', bandColorStart);
					if(window.addEventListener){
						bandColor[0].addEventListener('touchstart', bandColorStart, true);
						bandColor[0].addEventListener('touchend', bandColorEnd, true);
					}
					let bandAlpha = hub.find('.band-alpha'),
						bandAlphaStart = function(e){
							e.preventDefault();
							bandAlphaMove(e);
							$(document).on('mousemove', bandAlphaMove);
							$(document).on('mouseup', bandAlphaEnd);
							if(window.addEventListener){
								window.addEventListener('touchmove', bandAlphaMove, {passive:false});
								window.addEventListener('touchend', bandAlphaEnd, {passive:false});
							}
						},
						bandAlphaMove = function(e){
							e.preventDefault();
							let bandOffset = bandAlpha.offset(), touches = $.touches(e);
							let a = Number((Math.max(0,Math.min(bandWidth,(touches.x - bandOffset.left))))/bandWidth);
							a = Math.floor(a*100)/100;
							hsb = {
								h: Math.min(360, Math.max(0, parseInt(hsb.h))),
								s: Math.min(100, Math.max(0, parseInt(hsb.s))),
								b: Math.min(100, Math.max(0, parseInt(hsb.b)))
							};
							let rgb = hsbToRgb(hsb);
							rgb.a = a;
							footer.find('.a').val(Math.floor(a*100));
							setBandAlpha(rgb);
							setPreview(rgb);
							let color = 'rgba('+rgb.r+','+rgb.g+','+rgb.b+','+rgb.a+')';
							callback(color);
						},
						bandAlphaEnd = function(e){
							e.preventDefault();
							$(document).off('mousemove', bandAlphaMove);
							$(document).off('mouseup', bandAlphaEnd);
							if(window.addEventListener){
								window.removeEventListener('touchmove', bandAlphaMove, {passive:false});
								window.removeEventListener('touchend', bandAlphaEnd, {passive:false});
							}
						};
					if(options.transparent){
						bandAlpha.on('mousedown', bandAlphaStart);
						if(window.addEventListener){
							bandAlpha[0].addEventListener('touchstart', bandAlphaStart, true);
							bandAlpha[0].addEventListener('touchcancel', bandAlphaEnd, true);
						}
					}
					hub.find('.outside-color input').on('change', function(){
						let rgb = hexToRgb(this.value), hex = this.value;
						rgb.a = footer.find('.a').val()/100;
						hsb = rgbToHsb(rgb);
						setRgbInput(rgb);
						setHexInput(hex);
						setSection(hsb);
						setBandColor(rgb, hsb);
						setBandAlpha(rgb);
						setPreview(rgb);
						callback('rgba('+rgb.r+','+rgb.g+','+rgb.b+','+rgb.a+')');
					});
					let footer = $('<div style="height:38px;display:flex;justify-content:space-between;padding-right:1px;">\
						<label style="width:58px;"><input class="hex" maxlength="6" /><span>#</span></label>\
						<label style="width:36px;"><input class="r" maxlength="3" /><span>R</span></label>\
						<label style="width:36px;"><input class="g" maxlength="3" /><span>G</span></label>\
						<label style="width:36px;"><input class="b" maxlength="3" /><span>B</span></label>\
						<label style="width:36px;"><input class="a" maxlength="3"'+(options.transparent?'':' readonly')+' /><span>A</span></label>\
					</div>');
					colorpicker.append(footer);
					footer.find('input').css({'float':'left', width:'100%', height:'22px', padding:'0 4px', background:'#f6f7f8', border:'1px solid #f2f2f2', 'border-radius':'2px', color:'#415058', overflow:'hidden', 'text-align':'center', 'font-size':'12px', 'box-shadow':'none', 'box-sizing':'border-box', outline:'none', '-webkit-transition':'border-color 0.3s ease-out', transition:'border-color 0.3s ease-out'})
					.on('focus', function(){
						$(this).attr('status', 'focus').select();
					}).on('mouseover', function(){
						$(this).css('border-color', '#08a1ef');
					}).on('mouseout', function(){
						if(!!!$(this).attr('status'))$(this).css('border-color', '#f2f2f2');
					}).on('blur', function(){
						$(this).removeAttr('status').css('border-color', '#f2f2f2');
					}).on('keyup', function(){
						let _input = $(this), val = _input.val();
						if(!val.length)return true;
						if(_input.is('.hex')){
							if(/^[0-9a-fA-f]{6}$/.test(val)){
								let rgb = hexToRgb(val), hex = val;
								hsb = rgbToHsb(rgb);
								setRgbInput(rgb);
								setHexInput(hex);
								setSection(hsb);
								setBandColor(rgb, hsb);
								setBandAlpha(rgb);
								setPreview(rgb);
								callback('rgba('+rgb.r+','+rgb.g+','+rgb.b+','+rgb.a+')');
							}
						}else{
							if(/^[0-9]{1,3}$/.test(val)){
								let rgb = {
									r: Math.max(0, Math.min(255, parseInt(colorpicker.find('.r').val()))),
									g: Math.max(0, Math.min(255, parseInt(colorpicker.find('.g').val()))),
									b: Math.max(0, Math.min(255, parseInt(colorpicker.find('.b').val()))),
									a: Math.max(0, Math.min(1, parseInt(colorpicker.find('.a').val())/100))
								}, hex = rgbToHex(rgb);
								hsb = rgbToHsb(rgb);
								setRgbInput(rgb);
								setHexInput(hex);
								setSection(hsb);
								setBandColor(rgb, hsb);
								setBandAlpha(rgb);
								setPreview(rgb);
								callback('rgba('+rgb.r+','+rgb.g+','+rgb.b+','+rgb.a+')');
							}
						}
						return true;
					});
					footer.find('span').css({display:'block', 'float':'left', width:'100%', 'line-height':'12px', 'margin-top':'4px', color:'#8d9ea7', 'text-align':'center', 'font-size':'12px'});
					if(ie6){
						let iframe = $('<iframe frameborder="0"></iframe>');
						colorpicker.append(iframe);
						iframe.css({
							width: colorpicker.children('div').outerWidth(false),
							height: colorpicker.children('div').outerHeight(false)
						});
					}
					let win = $.window(), reverseTarget = options.reverseTarget.length ? $(options.reverseTarget) : '',
						position = options.position ? _this.position() : _this.offset(),
						cl = reverseTarget.length ? reverseTarget.scrollLeft() : 0, ct = reverseTarget.length ? reverseTarget.scrollTop() : 0,
						cw = reverseTarget.length ? reverseTarget.width() : win.scrollLeft + win.width, ch = reverseTarget.length ? reverseTarget.height() : win.scrollTop + win.height,
						posl = cl + position.left, post = 0;
					if(options.sep>=0){
						post = ct + position.top + _this.outerHeight(false) + options.sep;
						if(position.left+colorpicker.children('div').outerWidth(false)>cw)posl = cl + position.left - colorpicker.children('div').outerWidth(false);
						if(position.top+colorpicker.children('div').outerHeight(false)>ch)post = ct + position.top - colorpicker.children('div').outerHeight(false) - options.sep;
					}else{
						post = ct + position.top - colorpicker.children('div').outerHeight(false) + options.sep;
					}
					colorpicker.css({left:posl, top:post});
					initPanel(_this.attr('initcolor')||_this.attr('color')||'rgba(0,0,0,1)');
				}
				$(document.body).on(window.eventType, removeControlReg);
				if($.isFunction(options.shown))options.shown.call(_this, colorpicker, el);
			}else{
				if(colorpicker.css('display') === 'none'){
					colorpicker.css('display', 'block');
					if(options.type === 'panel'){
						initPanel(_this.attr('initcolor')||_this.attr('color')||'rgba(0,0,0,1)');
					}
					if($.isFunction(options.shown))options.shown.call(_this, colorpicker, el);
				}else{
					removeControl();
				}
			}
		}
		function removeControlReg(e){
			let o = $.etarget(e);
			do{
				if($(o).is(colorpicker) || $(o).is(_this))return true;
				if(options.partner.length && $(o).is(options.partner))return true;
				if((/^(html|body)$/i).test(o.tagName)){
					removeControl();
					return true;
				}
				o = o.parentNode;
			}while(o.parentNode);
		}
		function removeControl(){
			if(!!!colorpicker.attr('always')){
				colorpicker.hide();
				if($.isFunction(options.hidden))options.hidden.call(_this, colorpicker, el);
			}
		}
	});
};

//日期选择器, 需引入datepicker.css
$.fn.datepicker = function(options){
	options = $.extend({
		initDate: new Date(), //默认选定日期
		parent: 'body', //生成的选择器html代码插入到指定容器里的最后, 平板模式无效
		target: '', //选择日期后填写日期的目标对象(不指定即为当前点击对象)
		cls: 'datepicker', //使用指定class
		sep: 4, //y相隔
		just: '', //只显示选择年份或月份(参数只支持两种:year、month)
		next: '', //选择日期后直接跳到下个日期控件, 格式为expr
		always: false, //平板模式, 直接显示在调用者的html内, 一般与target参数同时使用
		fullscreen: false, //全屏展示
		hiddenNavBar: false, //隐藏导航栏
		partner: '', //设置点击不隐藏的伙伴
		reverseTarget: '', //到容器边缘反转显示, 不设置即根据window
		useClick: true, //使用click绑定点击显示, 否则自行调用 .trigger('datepicker.click') 且设置 partner 参数
		breakClick: false, //不执行插件默认的点击选择日期操作
		disable: false, //不执行所有点击操作
		readonly: true, //可否填写
		range: false, //范围选择
		multiple: false, //日期多选, 设置后next无效
		showCal: true, //false即直接显示时分秒, showTime自动变为true
		showTime: false, //显示时分, 返回的日期格式(format)需自己设定
		showHour: true, //显示时, 需设置showTime
		showMinute: true, //显示分, 需设置showTime,showHour
		changeYear: true, //可否更改年份
		changeMonth: true, //可否更改月份
		touchMove: true, //拖曳切换年月
		enText: false, //将所有文字改为英文
		yearText: '年', //一般以英文显示才需修改
		monthText: '月', //同上
		weekText: [], //同上,以星期天起始, 留空即使用默认
		minYear: 1949, //最小年份(数值型)(字符型:this为今年|[+-]数字(以今天作为界限))
		maxYear: new Date().getFullYear()+15, //最大年份(数值型)(字符型:this为今年|[+-]数字(以今天作为界限))
		disMonths: '', //禁用月份(逗号隔开)
		disDays: '', //禁用每月的某些日(逗号隔开)
		disWeeks: '', //禁用每月的某些星期(逗号隔开)(格式:0,1,2,3,4,5,6,星期日为0)
		disDates: '', //禁用某些日(逗号隔开)(today:使用今天作为日期)(格式:年-月-日)
		minDate: '', //只能选择该日以后的日期(格式:年-月-日)(today:使用今天作为日期)([+-]数字[y|m|d](以今天作为界限))
		maxDate: '', //只能选择该日以前的日期(格式:年-月-日)(today:使用今天作为日期)([+-]数字[y|m|d](以今天作为界限))
		format: 'yyyy-m-d', //以逗号分隔的日期数组内每个日期的格式, range需设置为:(#yyyy-m-d)~(#yyyy-m-d),会自动正则替换(#yyyy-m-d)
		mark: {}, //设定日子说明, {'0-8-18':'生日','2017-8-31':''} //年为0即每年,如果为空字符,则只增加标识
		date: null, //每个日期生成后执行
		prevMonth: null, //外部切换到上个月的控件
		nextMonth: null, //外部切换到下个月的控件
		prevMonthCallback: null, //点击上个月箭头后执行
		nextMonthCallback: null, //点击下个月箭头后执行
		shown: null, //显示后执行, 两参数:datepicker,目标对象
		hidden: null, //隐藏后执行
		change: null, //改变年月后执行
		complete: null //插件加载后执行
		//自定义函数名: function(dates){...} //可加入多个自定义函数(只要是函数都会被执行), dates参数为[多个日期元素的数组|range两个日期元素的数组]
	}, $('body').data('datepicker.options'), options);
	return this.each(function(){
		$(this).removePlug('datepicker');
		let _this = $(this).data('datepicker', true), body = _this.parents('body');
		_this.on('makepicker', function(){
			let el = !!_this.attr('target') ? $(_this.attr('target')) : (options.target?$(options.target):_this), mark = el.attr('id'), tmp;
			if(!!!mark){mark = el.attr('name');el.attr('id', mark)}
			if(!!!mark){mark = $.datetimeAndRandom();el.attr('id', mark)}
			if(el.is('input'))el.attr('autocomplete', 'off');
			_this.data({mark:mark, el:el});
			//if(_this.is('input'))_this.addClass('datepicker');
			if(!!_this.attr('parent'))options = $.extend(options, {parent:_this.attr('parent')});
			if(!!_this.attr('cls'))options = $.extend(options, {cls:_this.attr('cls')});
			if(!!_this.attr('just') && (_this.attr('just') === 'year' || _this.attr('just') === 'month'))options = $.extend(options, {just:_this.attr('just')});
			if(!!el.attr('@') || !!_this.attr('@'))options = $.extend(options, {next:el.attr('@')||_this.attr('@')});
			if(!!_this.attr('next'))options = $.extend(options, {next:_this.attr('next')});
			if(options.readonly){el.attr('readonly','readonly').css('cursor','default')}else{el.removeAttr('readonly')}
			if($.inArray(_this.attr('fullscreen'),['true','false'])>-1){tmp=evil(_this.attr('fullscreen'));options = $.extend(options, {fullscreen:tmp})}
			if($.inArray(_this.attr('hiddenNavBar'),['true','false'])>-1){tmp=evil(_this.attr('hiddenNavBar'));options = $.extend(options, {hiddenNavBar:tmp})}
			if($.inArray(_this.attr('breakClick'),['true','false'])>-1){tmp=evil(_this.attr('breakClick'));options = $.extend(options, {breakClick:tmp})}
			if($.inArray(_this.attr('range'),['true','false'])>-1){tmp=evil(_this.attr('range'));options = $.extend(options, {range:tmp})}
			if(!!_this.attr('multiple'))options = $.extend(options, {multiple:true});
			if($.inArray(_this.attr('showTime'),['true','false'])>-1){tmp=evil(_this.attr('showTime'));options = $.extend(options, {showTime:tmp})}
			if($.inArray(_this.attr('showHour'),['true','false'])>-1){tmp=evil(_this.attr('showHour'));options = $.extend(options, {showHour:tmp})}
			if($.inArray(_this.attr('showMinute'),['true','false'])>-1){tmp=evil(_this.attr('showMinute'));options = $.extend(options, {showMinute:tmp})}
			if($.inArray(_this.attr('showCal'),['true','false'])>-1){tmp=evil(_this.attr('showCal'));options = $.extend(options, {showCal:tmp})}
			if($.inArray(_this.attr('changeYear'),['true','false'])>-1){tmp=evil(_this.attr('changeYear'));options = $.extend(options, {changeYear:tmp})}
			if($.inArray(_this.attr('changeMonth'),['true','false'])>-1){tmp=evil(_this.attr('changeMonth'));options = $.extend(options, {changeMonth:tmp})}
			if($.inArray(_this.attr('touchMove'),['true','false'])>-1){tmp=evil(_this.attr('touchMove'));options = $.extend(options, {touchMove:tmp})}
			if($.inArray(_this.attr('enText'),['true','false'])>-1){tmp=evil(_this.attr('enText'));options = $.extend(options, {enText:tmp})}
			if(!!Number(_this.attr('minYear'))){tmp=evil(_this.attr('minYear'));options = $.extend(options, {minYear:tmp})}
			if(!!Number(_this.attr('maxYear'))){tmp=evil(_this.attr('maxYear'));options = $.extend(options, {maxYear:tmp})}
			if(!!_this.attr('disMonths'))options = $.extend(options, {disMonths:_this.attr('disMonths')});
			if(!!_this.attr('disDays'))options = $.extend(options, {disDays:_this.attr('disDays')});
			if(!!_this.attr('disWeeks'))options = $.extend(options, {disWeeks:_this.attr('disWeeks')});
			if(!!_this.attr('disDates'))options = $.extend(options, {disDates:_this.attr('disDates')});
			if(!!_this.attr('minDate') || !!_this.data('minDate'))options = $.extend(options, {minDate:_this.data('minDate')||_this.attr('minDate')});
			if(!!_this.attr('maxDate'))options = $.extend(options, {maxDate:_this.attr('maxDate')});
			if(!!_this.attr('format'))options = $.extend(options, {format:_this.attr('format')});
			if(!!_this.attr('mark')){let marker = evil(_this.attr('mark'));options = $.extend(options, {mark:marker})}
			if(options.enText)options = $.extend(options, {yearText:'', monthText:''});
			if(options.multiple)options = $.extend({}, options, {next:''});
			if(!options.showCal)options = $.extend(options, {showTime:true, format:options.format.indexOf('hh') === -1?'hh:nn:ss':options.format});
			if(options.showTime && !options.showHour)options = $.extend({}, options, {showHour:true});
			if(options.range && options.format.indexOf('(#') === -1)options = $.extend(options, {format:'(#yyyy-m-d)~(#yyyy-m-d)'});
			if(!options.always){
				if(options.useClick)_this.on('click', function(){
					if(!body.find('#' + mark + '_datepicker').length)makepicker();
					else removeControl();
				});
				_this.on('datepicker.click', function(){
					if(!body.find('#' + mark + '_datepicker').length)makepicker();
					else removeControl();
				});
			}else{
				makepicker();
			}
		});
		_this.addClass(options.cls).data('datepicker-options', options).on('datepicker.hidden', function(){
			removeControl();
		}).trigger('makepicker');
		let mark = _this.data('mark');
		function makepicker(){
			let mark = _this.data('mark'), el = _this.data('el'), initdate = [],
				datepicker = $('<div id="' + mark + '_datepicker" class="datepickerView" style="position:relative;font-family:Arial,\'Microsoft YaHei\';"><div class="childrenDIV" style="position:relative;font-size:12px;overflow:hidden;"><div></div></div></div>');
			if(!options.always)datepicker.css({position:'absolute', 'z-index':'888', left:0, top:0}).children('div').css({position:'absolute', left:0, top:0});
			else datepicker.addClass('datepickerViewNotHide');
			if(options.fullscreen)datepicker.addClass('datepickerViewFullscreen');
			if(!!el.data('curYear') || !!el.attr('data-curYear') || !!_this.data('curYear')){
				let curYear = el.data('curYear')||el.attr('data-curYear')||_this.data('curYear'),
					curMonth = el.data('curMonth')||el.attr('data-curMonth')||_this.data('curMonth')||0,
					curDay = el.data('curDay')||el.attr('data-curDay')||_this.data('curDay')||1,
					curHour = el.data('curHour')||el.attr('data-curHour')||_this.data('curHour')||0,
					curMinute = el.data('curMinute')||el.attr('data-curMinute')||_this.data('curMinute')||0,
					curSecond = el.data('curSecond')||el.attr('data-curSecond')||_this.data('curSecond')||0;
				initdate = [new Date(curYear, curMonth, curDay, curHour, curMinute, curSecond)];
			}
			let reg = /^(?:(\d{4})-(\d{1,2})(?:-(\d{1,2}))?)?(?: ?(\d{1,2}))?(?::(\d{1,2}))?(?::(\d{1,2}))?$/;
			if( !!el.attr('initdate') || (el.val().length && reg.test(el.val())) || (el.html().length && reg.test(el.html())) ){
				let val = el.attr('initdate');
				if(!!!val && reg.test(el.val()))val = el.val();
				if(!!!val && reg.test(el.html()))val = el.html();
				if(!!!val)return;
				let datas = val.split(',');
				if(options.range)datas = [datas[0], datas[datas.length-1]];
				initdate = [];
				for(let i=0; i<datas.length; i++){
					initdate.push((datas[i]).date());
				}
			}
			let option = $.extend(options, {
				obj: el,
				initDate: initdate,
				click: function(dates){
					let fn, arr = [], arrFormat = [];
					if(options.range){
						let count = 0, fmt = options.format.replace(/\(#([^)]+)\)/g, function(m, format){
							format = dates[count].formatDate(format, function(date){
								let showTime = '';
								if(options.showTime){
									if(options.showHour)showTime += ' '+date.hour;
									if(options.showHour && options.showMinute)showTime += ':'+date.minute;
								}
								if(options.showCal){
									arr.push(date.year+'-'+date.month+'-'+date.day+showTime);
								}else{
									arr.push($.trim(showTime));
								}
							});
							count++;
							return format;
						});
						arrFormat.push(fmt);
					}else{
						for(let i=0; i<dates.length; i++){
							let format = (dates[i]).formatDate(options.format, function(date){
								let showTime = '';
								if(options.showTime){
									if(options.showHour)showTime += ' '+date.hour;
									if(options.showHour && options.showMinute)showTime += ':'+date.minute;
								}
								if(options.showCal){
									arr.push(date.year+'-'+date.month+'-'+date.day+showTime);
								}else{
									arr.push($.trim(showTime));
								}
							});
							arrFormat.push(format);
						}
					}
					el.removeData('curYear curMonth curDay curHour curMinute curSecond');
					let val = arrFormat.join(',');
					if(el.is('input, textarea'))el.val(val);
					if(el.is('select'))el.selected(val);
					if(!el.is('input, textarea, select'))el.html(val);
					if(!options.always && !options.multiple)removeControl();
					el.attr('initdate', arr.join(','));
					fn = el.attr('fn');
					if(!!fn){
						let func = evil(fn);
						if($.isFunction(func))func.call(el, dates);
					}
					if(!!el.data('checkform-checkHandle'))el.data('checkform-checkHandle').call(el);
					if(!options.multiple && options.next){
						let next = $(options.next), date = dates[0];
						if(!next.length || next.is(el))return;
						next.data({'curYear':date.getFullYear(), 'curMonth':date.getMonth(), 'curDay':date.getDate(), 'curHour':date.getHours(), 'curMinute':date.getMinutes(), 'curSecond':date.getSeconds()});
						next.data('minDate', date.getFullYear()+'-'+(date.getMonth()+1)+'-'+date.getDate());
						if(!!!next.data('makedatepicker')){
							next.data('makedatepicker', true);
							if(!!!next.data('datepicker'))next.datepicker($.extend({}, options, {next:''}));
						}
						if(!options.always){
							setTimeout(function(){next.click()}, 0);
						}else{
							next.trigger('makepicker');
						}
					}
				}
			});
			datepicker.calendar(option);
			if(options.cls.length)datepicker.addClass(options.cls);
			if(options.next && options.always){
				let next = $(options.next);
				if(next.length && !next.is(el)){
					next.data('makedatepicker', true);
					if(!!!next.data('datepicker'))next.datepicker($.extend({}, options, {next:''}));
					setTimeout(function(){
						if(!!next.data('datepicker')){
							let ar = next.children().eq(0);
							if(!ar.find('.datepickerMark').length)ar.prepend('<div class="datepickerMark"></div>');
						}
					}, 100);
				}
			}
			if(!options.always){
				let position = {};
				if(options.parent === 'body'){
					body.append(datepicker);
					position = _this.offset();
				}else{
					$(options.parent).append(datepicker);
					position = _this.position();
				}
				if(options.fullscreen){
					setTimeout(function(){datepicker.addClass('datepickerViewFullscreen-x')}, 50);
				}else{
					if($.browser.ie6){
						let iframe = $('<iframe frameborder="0"></iframe>');
						datepicker.append(iframe);
						iframe.css({width:datepicker.children('div').outerWidth(false), height:datepicker.children('div').outerHeight(false)});
					}
					let win = $.window(), reverseTarget = options.reverseTarget.length ? $(options.reverseTarget) : '',
						cl = reverseTarget.length ? reverseTarget.scrollLeft() : 0, ct = reverseTarget.length ? reverseTarget.scrollTop() : 0,
						cw = reverseTarget.length ? reverseTarget.width() : win.scrollLeft + win.width, ch = reverseTarget.length ? reverseTarget.height() : win.scrollTop + win.height,
						posl = cl + position.left, post = ct + position.top + _this.outerHeight(false) + options.sep;
					if(position.left+datepicker.children('div').outerWidth(false)>cw)posl = cl + position.left - datepicker.children('div').outerWidth(false);
					if(position.top+datepicker.children('div').outerHeight(false)>ch){
						post = ct + position.top - datepicker.children('div').outerHeight(false) - options.sep;
						datepicker.attr('reverse-y', post);
					}
					datepicker.css({left:posl, top:post});
				}
				_this.on('keydown', removeControl);
				datepicker.data('removeControl', removeControl);
				body.on(window.eventType, removeControlReg);
			}else{
				_this.children().remove();
				_this.append(datepicker);
			}
			let yul = datepicker.find('ul:eq(0)'), yli = yul.find('li');
			yli.eq(1).width(yul.width()-yli.eq(0).outerWidth(true)-yli.eq(2).outerWidth(true)-($.browser.msie?($.browser.version===8?2:1):0));
			if($.isFunction(options.shown))options.shown.call(_this, datepicker, el);
		}
		function removeControlReg(e){
			let o = $.etarget(e);
			do{
				if(o.id === mark+'_datepicker' || o.id === mark || $(o).is(_this))return;
				if(options.partner.length && $(o).is(options.partner))return true;
				if((/^(html|body)$/i).test(o.tagName)){
					removeControl();
					return;
				}
				o = o.parentNode;
			}while(o.parentNode);
		}
		function removeControl(){
			body.find('.datepickerView:not(.datepickerViewNotHide)').each(function(){
				let _datepicker = $(this);
				if(options.fullscreen){
					_datepicker.removeClass('datepickerViewFullscreen-x');
					setTimeout(function(){
						_datepicker.remove();
						if($.isFunction(options.hidden))options.hidden.call(_this);
					}, 300);
				}else{
					setTimeout(function(){
						_datepicker.remove();
						if($.isFunction(options.hidden))options.hidden.call(_this);
					}, 0);
				}
			});
			body.off(window.eventType, removeControlReg);
			//_this.removeAttr('initdate');
		}
	});
};
$.fn.calendar = function(options){
	options = $.extend({
		obj: null,
		initDate: [],
		cls: '',
		just: '',
		fullscreen: false,
		hiddenNavBar: false,
		breakClick: false,
		disable: false,
		range: false,
		multiple: false,
		showCal: true,
		showTime: false,
		showHour: true,
		showMinute: true,
		changeYear: true,
		changeMonth: true,
		touchMove: true,
		enText: false,
		yearText: '年',
		monthText: '月',
		weekText: [],
		minYear: 1949,
		maxYear: new Date().getFullYear()+15,
		disMonths: '',
		disDays: '',
		disWeeks: '',
		disDates: '',
		minDate: '',
		maxDate: '',
		format: 'yyyy-m-d',
		mark: {},
		click: null,
		date: null,
		prevMonth: null,
		nextMonth: null,
		prevMonthCallback: null,
		nextMonthCallback: null,
		change: null,
		complete: null
	}, options);
	let area = null, oriHeight = 0, dates = options.initDate, beginDate = null, endDate = null,
	monthName = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
	weekName = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
	weekClass = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
	if(!options.enText){
		monthName = ['一月', '二月', '三月', '四月', '五月', '六月', '七月', '八月', '九月', '十月', '十一月', '十二月'];
		weekName = ['日', '一', '二', '三', '四', '五', '六'];
	}
	if($.isArray(options.weekText) && options.weekText.length === 7)weekName = options.weekText;
	if(options.range && dates.length === 2){beginDate = dates[0]; endDate = dates[dates.length-1]}
	if(typeof options.minYear === 'string'){
		if(options.minYear === 'this'){
			options.minYear = new Date().getFullYear();
		}else{
			let tdy = options.minYear.match(/^([+-]\d+)$/);
			if(tdy)options.minYear = new Date().getFullYear() + tdy[1]*1;
		}
	}
	if(typeof options.maxYear === 'string'){
		if(options.maxYear === 'this'){
			options.maxYear = new Date().getFullYear();
		}else{
			let tdy = options.maxYear.match(/^([+-]\d+)$/);
			if(tdy)options.maxYear = new Date().getFullYear() + tdy[1]*1;
		}
	}
	function MonthInfo(y, m){
		let monthDays = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31],
		d = (new Date(y, m, 1));
		d.setDate(1);
		if(d.getDate() === 2) d.setDate(0);
		y += 1900;
		return{
			days: m === 1 ? (((y % 4 === 0) && (y % 100 !== 0)) || (y % 400 === 0) ? 29 : 28) : monthDays[m],
			firstDay: d.getDay()
		}
	}
	function InitCalendar(calp, date, position, showYears, showMonths){
		let cal = $('<div style="width:100%;float:left;"></div>'),
		initdate = new Date(), td = new Date(), obj = $(options.obj), attrDate = dates.length ? dates[dates.length-1] : null,
		tdYear = td.getFullYear(), tdMonth = td.getMonth(), tdDay = td.getDate(), tdHour = td.getHours(), tdMinute = td.getMinutes(), tdSecond = td.getSeconds(),
		month = MonthInfo(date.getFullYear(), date.getMonth()), minDate = obj.data('minDate')||options.minDate, maxDate = obj.data('maxDate')||options.maxDate,
		minFullDate = new Date(options.minYear, tdMonth, tdDay, tdHour, tdMinute, tdSecond),
		maxFullDate = new Date(options.maxYear, tdMonth, tdDay, tdHour, tdMinute, tdSecond);
		if(typeof position !== 'undefined'){
			let width = calp.parent().width(), height = calp.parent().height(), speed = 300, easing = 'easeout';
			if(!oriHeight){
				if(calp.find('.dayUL').length === 3){
					oriHeight = calp.find('.yearUL').outerHeight(true) + calp.find('.dayUL').outerHeight(true)*3;
				}else{
					oriHeight = calp.find('.yearUL').outerHeight(true) + calp.find('.weekUL').outerHeight(true) + calp.find('.dayUL').outerHeight(true)*5;
				}
			}
			calp.css({position:'relative', width:width, height:height, overflow:'hidden'}).parent().animate({height:oriHeight}, 200, function(){
				let h = 0;
				switch(position){
					case 0:case 'top':
						calp.append(cal);
						calp.children().width(width);
						calp.css({top:0, left:0, height:height*2}).animate({top:-height}, speed, easing, function(){
							cal.css({width:'100%'}).prev().remove();
							calp.css({position:'', top:'', left:'', width:'', height:''}).parent().css({height:'auto'});
							h = calp.parent().height();
							calp.parent().css({height:oriHeight}).animate({height:h}, 200, function(){
								calp.parent().css({height:'auto'});
								if($.isFunction(options.change))options.change.call(cal.parent(), date);
							});
						});
						break;
					case 1:case 'right':
						calp.prepend(cal);
						calp.children().width(width);
						calp.css({top:0, left:-width, width:width*2}).animate({left:0}, speed, easing, function(){
							cal.css({width:'100%'}).next().remove();
							calp.css({position:'', top:'', left:'', width:'', height:''}).parent().css({height:'auto'});
							h = calp.parent().height();
							calp.parent().css({height:oriHeight}).animate({height:h}, 200, function(){
								calp.parent().css({height:'auto'});
								if($.isFunction(options.change))options.change.call(cal.parent(), date);
							});
						});
						break;
					case 2:case 'bottom':
						calp.prepend(cal);
						calp.children().width(width);
						calp.css({top:-height, left:0, height:height*2}).animate({top:0}, speed, easing, function(){
							cal.css({width:'100%'}).next().remove();
							calp.css({position:'', top:'', left:'', width:'', height:''}).parent().css({height:'auto'});
							h = calp.parent().height();
							calp.parent().css({height:oriHeight}).animate({height:h}, 200, function(){
								calp.parent().css({height:'auto'});
								if($.isFunction(options.change))options.change.call(cal.parent(), date);
							});
						});
						break;
					case 3:case 'left':
						calp.append(cal);
						calp.children().width(width);
						calp.css({top:0, left:0, width:width*2}).animate({left:-width}, speed, easing, function(){
							cal.css({width:'100%'}).prev().remove();
							calp.css({position:'', top:'', left:'', width:'', height:''}).parent().css({height:'auto'});
							h = calp.parent().height();
							calp.parent().css({height:oriHeight}).animate({height:h}, 200, function(){
								calp.parent().css({height:'auto'});
								if($.isFunction(options.change))options.change.call(cal.parent(), date);
							});
						});
						break;
				}
			});
		}else{
			calp.append(cal);
		}
		if(minDate){
			if(typeof minDate === 'string'){
				if(minDate === 'today'){
					minDate = new Date();
				}else{
					let tdn = new Date(), tdt = minDate.match(/^([+-]\d+)\s*(y|year|m|month|d|day)$/);
					if(tdt){
						switch(tdt[2]){
							//case 'y':minDate = new Date(tdn.getTime()+tdt[1]*365*24*3600*1000);break;
							//case 'm':minDate = new Date(tdn.getTime()+tdt[1]*30*24*3600*1000);break;
							//case 'd':minDate = new Date(tdn.getTime()+tdt[1]*24*3600*1000);break;
							case 'y':case 'year':minDate = new Date(tdn.setFullYear(tdn.getFullYear()+Number(tdt[1])));break;
							case 'm':case 'month':minDate = new Date(tdn.setMonth(tdn.getMonth()+Number(tdt[1])));break;
							case 'd':case 'day':minDate = new Date(tdn.setDate(tdn.getDate()+Number(tdt[1])));break;
						}
					}else{
						minDate = (minDate.split(' '))[0];
						minDate = minDate.split('-');
						if(minDate.length === 3)minDate = new Date(minDate[0], minDate[1]-1, minDate[2]);
						else minDate = new Date();
					}
				}
				minDate = new Date(minDate.getFullYear(), minDate.getMonth(), minDate.getDate());
			}
			options.minYear = minDate.getFullYear();
			minFullDate = new Date(minDate.getFullYear(), minDate.getMonth(), minDate.getDate());
		}
		if(maxDate){
			if(typeof maxDate === 'string'){
				if(maxDate === 'today'){
					maxDate = new Date();
				}else{
					let tdn = new Date(), tdt = maxDate.match(/^([+-]\d+)\s*(y|year|m|month|d|day)$/);
					if(tdt){
						switch(tdt[2]){
							case 'y':case 'year':maxDate = new Date(tdn.setFullYear(tdn.getFullYear()+Number(tdt[1])));break;
							case 'm':case 'month':maxDate = new Date(tdn.setMonth(tdn.getMonth()+Number(tdt[1])));break;
							case 'd':case 'day':maxDate = new Date(tdn.setDate(tdn.getDate()+Number(tdt[1])));break;
						}
					}else{
						maxDate = (maxDate.split(' '))[0];
						maxDate = maxDate.split('-');
						if(maxDate.length === 3)maxDate = new Date(maxDate[0], maxDate[1]-1, maxDate[2]);
						else maxDate = new Date();
					}
				}
				maxDate = new Date(maxDate.getFullYear(), maxDate.getMonth(), maxDate.getDate(), 23, 59, 59);
			}
			options.maxYear = maxDate.getFullYear();
			maxFullDate = new Date(maxDate.getFullYear(), maxDate.getMonth(), maxDate.getDate());
		}
		let just = options.just, year = null, week = null;
		if(showYears)just = 'year';
		if(showMonths)just = 'month';
		switch(just){
		case 'year':
			cal.attr('type', 'year');
			tdDay = 1;
			let h, ha;
			if(!!attrDate && $.isDate(attrDate)){
				initdate = new Date(attrDate.getFullYear(), tdMonth,tdDay);
			}
			obj.data({'curYear':initdate.getFullYear(), 'curMonth':initdate.getMonth(), 'curDay':initdate.getDate()});
			h = (initdate.getFullYear() - options.minYear) % 12;
			ha = initdate.getFullYear() - h;
			if(showYears)ha = date.getFullYear();
			let hb = (ha+11>options.maxYear) ? options.maxYear : ha+11;
			year = $('<ul class="yearUL '+(options.hiddenNavBar?'hidden':'')+'"></ul>');
			year.append('<li><a class="pn pnr" href="javascript:void(0)" cal="nextyears" year="' + ha + '"></a><a class="pn pnl" href="javascript:void(0)" cal="prevyears" year="' + ha + '"></a><a class="pc pcy" href="javascript:void(0)" cal="years">' + ha + ' - ' + hb + '</a></li>')
			.append('<p></p>');
			cal.append(year);
			if(options.minYear>=ha){
				let a = year.find('a[cal="prevyears"]');
				a.addClass('disabled');
			}
			if(options.maxYear<=hb){
				let a = year.find('a[cal="nextyears"]');
				a.addClass('disabled');
			}
			if(!options.changeYear)year.find('.pn').addClass('disabled');
			else year.find('li a:eq(2)').addClass('pc');
			for(let i = 0; i <= 2; i++){
				let days = $('<ul class="dayUL dayYearUL"></ul>');
				for(let j = ha+4*i; j <= ha+3+4*i; j++){
					let unDis = true, attr = '', cls = '';
					if((!!!calp.data('curYear') && j===initdate.getFullYear()) || j===calp.data('curYear')){ //current
						attr += ' bg="current"';
						if(j === tdYear)attr += ' today="today"';
						cls = 'current';
					}else{
						if(j === tdYear){ //toyear
							attr += ' bg="today" today="today"';
							cls = 'today';
						}else{ //normal
							attr += '';
							cls = 'normal';
						}
					}
					let curd = new Date(j, tdMonth, tdDay);
					if(options.disDates!==''){
						let disDates = options.disDates, dateSplit = disDates.split('-');
						if(dateSplit.length === 3)disDates = dateSplit[0];
						if(disDates === 'today')disDates = tdYear;
						if((','+disDates+',').indexOf(','+j+',')>-1)unDis = false;
					}
					if(options.minDate.length){
						if(minDate.getFullYear() > curd.getFullYear())unDis = false;
					}
					if(options.maxDate.length){
						if(maxDate.getFullYear() < curd.getFullYear())unDis = false;
					}
					let format = options.format;
					if(showYears || options.range)format = 'yyyy';
					format = curd.formatDate(format);
					if(j>=options.minYear && j<=options.maxYear && unDis === true){
						days.append('<li '+attr+' class="'+cls+'" title="'+format+'" year="' + j + '" month="' + tdMonth + '" date="1"><div>' + j + options.yearText + '</div></li>');
					}else{
						days.append('<li class="disabled '+cls+'" title="'+format+'"><div>' + j + options.yearText + '</div></li>');
					}
				}
				days.append('<p></p>');
				cal.append(days);
			}
			break;

		case 'month':
			cal.attr('type', 'month');
			tdDay = 1;
			if(!!attrDate && $.isDate(attrDate)){
				initdate = new Date(attrDate.getFullYear(), attrDate.getMonth(), tdDay);
			}
			obj.data({'curYear':initdate.getFullYear(), 'curMonth':initdate.getMonth(), 'curDay':initdate.getDate()});
			year = $('<ul class="yearUL '+(options.hiddenNavBar?'hidden':'')+'"></ul>');
			year.append('<li><a class="pn pnr" href="javascript:void(0)" cal="nextyear"></a><a class="pn pnl" href="javascript:void(0)" cal="prevyear"></a><a class="pc" href="javascript:void(0)" cal="year" year="' + date.getFullYear() + '">' + date.getFullYear() + options.yearText + '</a></li>')
			.append('<p></p>');
			cal.append(year);
			if(options.minYear>=date.getFullYear()){
				let a = year.find('a[cal="prevyear"]');
				a.addClass('disabled');
			}
			if(options.maxYear<=date.getFullYear()){
				let a = year.find('a[cal="nextyear"]');
				a.addClass('disabled');
			}
			if(!options.changeYear)year.find('.pn').addClass('disabled');
			else year.find('li a:eq(2)').addClass('pc');
			for(let i = 0; i <= 2; i++){
				let days = $('<ul class="dayUL dayYearUL"></ul>');
				for(let j = 1+4*i; j <= 4+4*i; j++){
					let unDis = true, attr = '', cls = '';
					if((!!!calp.data('curYear') && !!!calp.data('curMonth') && date.getFullYear() === initdate.getFullYear() && (j-1) === initdate.getMonth()) || (date.getFullYear() === calp.data('curYear') && (j-1) === calp.data('curMonth'))){ //current
						attr += ' bg="current"';
						if(date.getFullYear() === tdYear && (j-1) === tdMonth)attr += ' today="today"';
						cls = 'current';
					}else{
						if(date.getFullYear() === tdYear && (j-1) === tdMonth){ //tomonth
							attr += ' bg="today" today="today"';
							cls = 'today';
						}else{ //normal
							attr += '';
							cls = 'normal';
						}
					}
					let curd = new Date(date.getFullYear(), j, tdDay);
					if(options.disMonths.length){
						if((','+options.disMonths+',').indexOf(','+j+',')>-1)unDis = false;
					}
					if(options.disDates!==''){
						let disDates = options.disDates, dateSplit = disDates.split('-');
						if(dateSplit.length === 3)disDates = dateSplit[0]+'-'+dateSplit[1];
						if(disDates === 'today')disDates = tdYear+'-'+(tdMonth+1);
						if((','+disDates+',').indexOf(','+date.getFullYear()+'-'+j+',')>-1)unDis = false;
					}
					if(options.minDate.length){
						if(new Date(minDate.getFullYear(),minDate.getMonth(),1) > new Date(curd.getFullYear(),curd.getMonth(),1))unDis = false;
						minFullDate = new Date(minDate.getFullYear(), minDate.getMonth(), minDate.getDate());
					}
					if(options.maxDate.length){
						if(new Date(maxDate.getFullYear(),maxDate.getMonth(),1) < new Date(curd.getFullYear(),maxDate.getMonth(),1))unDis = false;
						maxFullDate = new Date(maxDate.getFullYear(), maxDate.getMonth(), maxDate.getDate());
					}
					let format = options.format;
					if(showMonths || options.range)format = 'yyyy-m';
					let newDat = new Date(date.getFullYear(), j-1, curd.getDate(), curd.getHours(), curd.getMinutes(), curd.getSeconds());
					format = newDat.formatDate(format);
					if(curd>=minFullDate && curd<=maxFullDate && unDis===true){
						days.append('<li '+attr+' class="'+cls+'" title="'+format+'" year="' + date.getFullYear() + '" month="' + (j-1) + '" date="1"><div>' + monthName[j-1] + '</div></li>');
					}else{
						days.append('<li class="disabled '+cls+'" title="'+format+'"><div>' + monthName[j-1] + '</div></li>');
					}
				}
				days.append('<p></p>');
				cal.append(days);
			}
			break;

		default:
			cal.attr('type', 'day');
			if(!!attrDate && $.isDate(attrDate)){
				initdate = new Date(attrDate.getFullYear(), attrDate.getMonth(), attrDate.getDate(), attrDate.getHours(), attrDate.getMinutes(), attrDate.getSeconds());
			}
			obj.data({'curYear':initdate.getFullYear(), 'curMonth':initdate.getMonth(), 'curDay':initdate.getDate(), 'curHour':initdate.getHours(), 'curMinute':initdate.getMinutes(), 'curSecond':initdate.getSeconds()});
			year = $('<ul class="yearUL '+(options.hiddenNavBar?'hidden':'')+'"></ul>');
			year.append('<li><a class="pn pnr" href="javascript:void(0)" cal="nextmonth"></a><a class="pn pnl" href="javascript:void(0)" cal="prevmonth"></a><a href="javascript:void(0)" cal="month" month="' + date.getFullYear() + '-' + date.getMonth() + '">' + date.getFullYear() + options.yearText + (options.enText ? ' - ' : '') + (date.getMonth()+1) + options.monthText + '</a></li>')
			.append('<p></p>');
			cal.append(year);
			if(new Date(minFullDate.getFullYear(),minFullDate.getMonth(),1) >= new Date(date.getFullYear(),date.getMonth(),1)){
				let a = year.find('a[cal="prevmonth"]');
				a.addClass('disabled');
			}
			if(new Date(maxFullDate.getFullYear(),maxFullDate.getMonth(),1) <= new Date(date.getFullYear(),date.getMonth(),1)){
				let a = year.find('a[cal="nextmonth"]');
				a.addClass('disabled');
			}
			if(!options.changeMonth)year.find('.pn').addClass('disabled');
			else year.find('li a:eq(2)').addClass('pc');
			week = $('<ul class="weekUL"></ul>');
			for(let i = 0; i < 7; i++){
				let worship = weekClass[i];
				week.append('<li class="'+worship+'"><div>' + weekName[i] + '</div></li>');
			}
			week.append('<p></p>');
			cal.append(week);
			for(let i = 0; i < 6; i++){
				let days = $('<ul class="dayUL"></ul>');
				for(let j = 0; j < 7; j++){
					let d = 7 * i - month.firstDay + j + 1, unDis = true, attr = '', cls = '', worship = weekClass[j];
					let inArray = false, everyDate = new Date(date.getFullYear(), date.getMonth(), d, 0, 0, 0);
					if(options.multiple){
						for(let s=0; s<dates.length; s++){
							if(Date.parse(everyDate) === Date.parse(dates[s])){inArray = true;break}
						}
					}else if(options.range){
						if(dates[0]<=Date.parse(everyDate) && Date.parse(everyDate)<=dates[dates.length-1])inArray = true;
					}else{
						inArray = (date.getFullYear() === initdate.getFullYear() && date.getMonth() === initdate.getMonth() && d === initdate.getDate());
					}
					if(inArray){ //current
						attr += ' bg="current"';
						cls = 'current '+worship;
						if(date.getFullYear() === tdYear && date.getMonth() === tdMonth && d === tdDay){
							attr += ' today="today"';
							cls += ' today';
						}
					}else{
						if(date.getFullYear() === tdYear && date.getMonth() === tdMonth && d === tdDay){ //today
							attr += ' bg="today" today="today"';
							cls = 'today '+worship;
						}else{ //normal
							attr += '';
							cls = 'normal '+worship;
						}
					}
					if(d>0 && d<=month.days){
						let curd = new Date(date.getFullYear(), date.getMonth(), d);
						if(options.disMonths.length){
							if((','+options.disMonths+',').indexOf(','+(date.getMonth()+1)+',')>-1)unDis = false;
						}
						if(options.disDays.length){
							if((','+options.disDays+',').indexOf(','+d+',')>-1)unDis = false;
						}
						if(options.disWeeks.length){
							if((','+options.disWeeks+',').indexOf(','+j+',')>-1)unDis = false;
						}
						if(options.disDates.length){
							let disDates = options.disDates;
							if(disDates === 'today')disDates = tdYear+'-'+(tdMonth+1)+'-'+tdDay;
							if((','+disDates+',').indexOf(','+date.getFullYear()+'-'+(date.getMonth()+1)+'-'+d+',')>-1)unDis = false;
						}
						if(options.minDate.length){
							if(minDate > curd)unDis = false;
						}
						if(options.maxDate.length){
							if(maxDate < curd)unDis = false;
						}
						let format = options.format, text = d+'', badger = '';
						if(options.range)format = 'yyyy-m-d';
						let newDat = new Date(curd.getFullYear(), curd.getMonth(), curd.getDate(), initdate.getHours(), initdate.getMinutes(), initdate.getSeconds());
						format = newDat.formatDate(format);
						for(let o in options.mark){
							if(options.mark.hasOwnProperty(o)){
								let s = o.split('-'), sd = null;
								if(s.length === 3){
									if(/^0+$/.test(s[0])){
										sd = new Date(curd.getFullYear(), Number(s[1])-1, Number(s[2]));
									}else{
										sd = o.date();
									}
									if(sd && sd.getFullYear() === curd.getFullYear() && sd.getMonth() === curd.getMonth() && sd.getDate() === curd.getDate()){
										badger += ' badger';
										if(options.mark[o].length){text = options.mark[o];badger += ' mark'}
									}
								}
							}
						}
						if(curd>=minFullDate && curd<=maxFullDate && unDis === true){
							let li = $('<li '+attr+' class="'+cls+badger+'" title="'+format+'" date="'+(curd.getFullYear()+'-'+(curd.getMonth()+1)+'-'+curd.getDate())+'" year="' + date.getFullYear() + '" month="' + date.getMonth() + '" day="' + d + '"><div>' + text + '</div></li>');
							days.append(li);
							if($.isFunction(options.date))options.date.call(li, curd);
						}else{
							let li = $('<li class="disabled '+cls+badger+'" title="'+format+'" date="'+(curd.getFullYear()+'-'+(curd.getMonth()+1)+'-'+curd.getDate())+'"><div>' + text + '</div></li>');
							days.append(li);
							if($.isFunction(options.date))options.date.call(li, curd);
						}
					}else if(d<=month.days || (d>month.days && j>0 && j<=6)){
						days.append('<li class="notcurrent"><div>&nbsp;</div></li>');
					}else{
						break;
					}
				}
				days.append('<p></p>');
				cal.append(days);
			}
			if(!cal.find('ul:last').find('li').length)cal.find('ul:last').remove();
			if(!options.range && !options.multiple && options.showTime && (options.showHour || options.showMinute))showTime();
			if(options.range && dates.length === 2)betweenDate();
			if(options.fullscreen && !calp.parent().parent().find('.datepickerClose').length){
				let p = calp.parent().parent();
				p.append('<a class="datepickerClose" href="javascript:void(0)"></a>');
				p.find('.datepickerClose').on('click', function(){
					p.removeClass('datepickerViewFullscreen-x');
					p.data('removeControl')();
					setTimeout(function(){p.remove()}, 300);
				});
			}
			break;
		}
		if(options.touchMove)touchMove();
		function showTime(){
			if(calp.parent().find('.hnsView').length)return;
			let hnsView = $('<div class="hnsView" style="'+(options.showCal?'display:none;':'')+'"></div>'), hnsNumber = $('<div class="hnsNumber"></div>'), html = '';
			let placeholderZero = function(str){return (str+'').length === 1 ? '0'+str : str};
			if(options.showHour){
				let _hour = initdate.getHours(), hours = [9, 10, 11, 12, 1, 2, 3, 4, 5, 6, 7, 8];
				html = '<div class="number hourNumber numberHidden" data-number="'+_hour+'"><div>';
				$.each(hours, function(i, n){
					let text = n;
					if(_hour>12)text += 12;
					html += '<div class="n '+((n === _hour || (n-12) === _hour || (n+12) === _hour)?'this':'')+'"><span class="m" data-number="'+text+'">'+text+'</span></div>';
				});
				html += '<em class="hnsPointer hnsHour"></em>\
						<span class="t">'+(_hour>12?'PM':'AM')+'</span>\
					</div>\
				</div>';
				hnsNumber.append(html);
				let hourNumber = hnsNumber.find('.hourNumber');
				hourNumber.find('div div').each(function(i){
					$(this).css({'-webkit-transform':'rotate('+(i*30)+'deg)', 'transform':'rotate('+(i*30)+'deg)'});
				});
				hourNumber.find('div div span').each(function(i){
					$(this).css({'-webkit-transform':'rotate('+(i*-30)+'deg)', 'transform':'rotate('+(i*-30)+'deg)'});
				});
				hourNumber.find('.hnsHour').css({'-webkit-transform':'rotate('+((_hour+3)*30)+'deg)', 'transform':'rotate('+((_hour+3)*30)+'deg)'});
				hourNumber.children('div').children('span').on('mouseup touchend', function(){
					let span = $(this), hourSpan = hourNumber.find('div div span');
					if(span.html() === 'AM'){
						span.html('PM');
						let number = Number(hourNumber.attr('data-number'));
						if(number === 12)number = -12;
						hourNumber.attr('data-number', number+12);
						hnsNumber.find('.hourText i').html(number+12);
						hourSpan.each(function(){
							let n = Number($(this).html())+12;
							$(this).attr('data-number', n).html(n);
						});
					}else{
						span.html('AM');
						let number = Number(hourNumber.attr('data-number'));
						if(number === 0)number = 24;
						hourNumber.attr('data-number', number-12);
						hnsNumber.find('.hourText i').html(number-12);
						hourSpan.each(function(){
							let n = Number($(this).html())-12;
							$(this).attr('data-number', n).html(n);
						});
					}
				});
				let isHourStartDrag = false, isHourMoveDrag = false, hourOrigin = {}, hourStartDrag = function(e){
						e.preventDefault();
						let o = e.target;
						if($(o).hasClass('t'))return;
						isHourStartDrag = true;
						hourNumber.on('mousemove', hourMoveDrag);
						if(window.addEventListener)hourNumber[0].addEventListener('touchmove', hourMoveDrag, true);
						let span = hourNumber.children('div').children('span'), spanWidth = span.width(), spanHeight = span.height(), spanOffset = span.offset();
						hourOrigin = {x:spanOffset.left+spanWidth/2, y:spanOffset.top+spanHeight/2}; //当前元素的中心点
						hourMoveDrag(e);
					},
					hourMoveDrag = function(e){
						e.preventDefault();
						isHourMoveDrag = true;
						let isPM = hourNumber.children('div').children('span').html() === 'PM';
						let x = $.touches(e).x, y = $.touches(e).y;
						//计算出当前鼠标相对于元素中心点的坐标
						x = x - hourOrigin.x;
						y = hourOrigin.y - y;
						let unit = Math.PI / 6, radian = Math.atan2(x, y);
						if(radian < 0)radian = Math.PI * 2 + radian;
						let hour = Math.round(radian / unit);
						if(isPM)hour += 12;
						if(!isPM && hour === 0)hour = 12;
						if(hour>=24 || (isPM && hour === 12))hour = 0;
						hourNumber.attr('data-number', hour);
						hnsNumber.find('.hourText i').html(hour);
						hourNumber.find('.hnsHour').css({'-webkit-transform':'rotate('+((hour+3)*30)+'deg)', 'transform':'rotate('+((hour+3)*30)+'deg)'});
						hourNumber.find('div div.this').removeClass('this');
					},
					hourEndDrag = function(e){
						e.preventDefault();
						hourNumber.off('mousemove', hourMoveDrag);
						if(window.addEventListener)hourNumber[0].removeEventListener('touchmove', hourMoveDrag, true);
						if(isHourStartDrag){
							if(options.showMinute){
								setTimeout(function(){
									hnsNumber.find('.hourNumber').addClass('numberFadeOut');
									hnsNumber.find('.minuteNumber').show().removeClass('numberHidden');
									if(!options.showCal)hnsNumber.children('a.ret').fadeIn(200);
								}, 300);
							}
						}
						isHourStartDrag = false;
						isHourMoveDrag = false;
					};
				hourNumber.on('mousedown', hourStartDrag).on('mouseup mouseleave', hourEndDrag);
				if(window.addEventListener){
					hourNumber[0].addEventListener('touchstart', hourStartDrag, true);
					hourNumber[0].addEventListener('touchend', hourEndDrag, true);
					hourNumber[0].addEventListener('touchcancel', hourEndDrag, true);
				}
			}
			if(options.showMinute){
				let _minute = initdate.getMinutes(), minutes = [];
				html = '<div class="number minuteNumber numberHidden" data-number="'+_minute+'" style="display:none;"><div>';
				for(let i=45; i<=59; i+=5)minutes.push(i);
				for(let i=0; i<=44; i+=5)minutes.push(i);
				$.each(minutes, function(i, n){
					let text = n;
					text = placeholderZero(text);
					html += '<div class="n '+(n === _minute?'this':'')+'"><span class="m" data-number="'+n+'">'+text+'</span></div>';
				});
				html += '<em class="hnsPointer hnsMinute"><span></span></em>\
						</div>\
					</div>';
				hnsNumber.append(html);
				let minuteNumber = hnsNumber.find('.minuteNumber');
				minuteNumber.find('div div').each(function(i){
					$(this).css({'-webkit-transform':'rotate('+(i*30)+'deg)', 'transform':'rotate('+(i*30)+'deg)'});
				});
				minuteNumber.find('div div span').each(function(i){
					$(this).css({'-webkit-transform':'rotate('+(i*-30)+'deg)', 'transform':'rotate('+(i*-30)+'deg)'});
				});
				minuteNumber.find('.hnsMinute').css({'-webkit-transform':'rotate('+((_minute+(60-45))*6)+'deg)', 'transform':'rotate('+((_minute+(60-45))*6)+'deg)'});
				let isMinuteStartDrag = false, isMinuteMoveDrag = false, minuteOrigin = {}, minuteStartDrag = function(e){
						e.preventDefault();
						isMinuteStartDrag = true;
						minuteNumber.on('mousemove', minuteMoveDrag);
						if(window.addEventListener)minuteNumber[0].addEventListener('touchmove', minuteMoveDrag, true);
						let span = minuteNumber.find('.hnsMinute span'), spanWidth = span.width(), spanHeight = span.height(), spanOffset = span.offset();
						minuteOrigin = {x:spanOffset.left+spanWidth/2, y:spanOffset.top+spanHeight/2}; //当前元素的中心点
						minuteMoveDrag(e);
					},
					minuteMoveDrag = function(e){
						e.preventDefault();
						isMinuteMoveDrag = true;
						let x = $.touches(e).x, y = $.touches(e).y;
						x = x - minuteOrigin.x;
						y = minuteOrigin.y - y;
						let unit = Math.PI / 30, radian = Math.atan2(x, y);
						if(radian < 0)radian = Math.PI * 2 + radian;
						let minute = Math.round(radian / unit);
						if(minute>=60)minute = 0;
						minuteNumber.attr('data-number', minute);
						hnsNumber.find('.minuteText i').html(placeholderZero(minute));
						//let degree = Math.atan2(y, x) / (Math.PI / 180) + 180;
						//degree = -degree;
						minuteNumber.find('.hnsMinute').css({'-webkit-transform':'rotate('+((minute+(60-45))*6)+'deg)', 'transform':'rotate('+((minute+(60-45))*6)+'deg)'});
						minuteNumber.find('div div.this').removeClass('this');
					},
					minuteEndDrag = function(e){
						e.preventDefault();
						minuteNumber.off('mousemove', minuteMoveDrag);
						if(window.addEventListener)minuteNumber[0].removeEventListener('touchmove', minuteMoveDrag, true);
						if(isMinuteStartDrag){
							let x = $.touches(e).x, y = $.touches(e).y;
							x = x - minuteOrigin.x;
							y = minuteOrigin.y - y;
							let unit = Math.PI / 30, radian = Math.atan2(x, y);
							if(radian < 0)radian = Math.PI * 2 + radian;
							let minute = Math.round(radian / unit);
							if(minute>=60)minute = 0;
							minuteNumber.attr('data-number', minute);
							hnsNumber.find('.minuteText i').html(placeholderZero(minute));
						}
						isMinuteStartDrag = false;
						isMinuteMoveDrag = false;
					};
				minuteNumber.on('mousedown', minuteStartDrag).on('mouseup mouseleave', minuteEndDrag);
				if(window.addEventListener){
					minuteNumber[0].addEventListener('touchstart', minuteStartDrag, true);
					minuteNumber[0].addEventListener('touchend', minuteEndDrag, true);
					minuteNumber[0].addEventListener('touchcancel', minuteEndDrag, true);
				}
			}
			calp.parent().append(hnsView);
			hnsView.append(hnsNumber);
			html = '<a href="javascript:void(0)" class="ret" style="display:none;"></a>\
				<a href="javascript:void(0)" class="ok" style="'+(options.showCal?'display:none;':'')+'"></a>\
				<span style="'+(options.showCal?'display:none;':'')+'">';
			if(options.showMinute)html += '<span class="minuteText"><i>'+placeholderZero(initdate.getMinutes())+'</i></span>';
			if(options.showHour)html += '<span class="hourText"><i>'+initdate.getHours()+'</i></span>';
			html += '</span>';
			hnsNumber.append(html);
			hnsNumber.children('a').on('dragstart', function(e){e.preventDefault()});
			hnsNumber.children('a.ret').on('click', function(){
				let minuteNumber = hnsNumber.find('.minuteNumber');
				if(options.showMinute && minuteNumber.length && !minuteNumber.hasClass('numberHidden')){
					if(!options.showCal)$(this).fadeOut(200);
					hnsNumber.find('.hourNumber').removeClass('numberFadeOut');
					minuteNumber.addClass('numberHidden');
					setTimeout(function(){
						minuteNumber.hide();
					}, 400);
				}else{
					hnsNumber.children('a').hide();
					hnsNumber.children('span').fadeOut(200);
					hnsNumber.find('.number').addClass('numberHidden');
					calp.removeClass('calFadeOut');
					setTimeout(function(){
						hnsView.hide();
					}, 400);
				}
			});
			hnsNumber.children('a.ok').on('click', function(){
				let hourNumber = hnsNumber.find('.hourNumber'), minuteNumber = hnsNumber.find('.minuteNumber');
				let hour = initdate.getHours(), minute = initdate.getMinutes(), second = initdate.getSeconds();
				if(options.showHour)hour = Number(hourNumber.attr('data-number'));
				if(options.showMinute)minute = Number(minuteNumber.attr('data-number'));
				let _obj = calp.find('.dayUL li.current'), toDay = new Date(),
					year = !!_obj.attr('year') ? Number(_obj.attr('year')) : toDay.getFullYear(),
					month = !!_obj.attr('month') ? Number(_obj.attr('month')) : toDay.getMonth(),
					day = !!_obj.attr('day') ? Number(_obj.attr('day')) : toDay.getDate(),
					dates = new Date(year, month, day, hour, minute, second);
				washFunction([dates]);
				if(options.always){
					hnsNumber.children('a').hide();
					hnsNumber.find('.number').removeClass('numberFadeOut').addClass('numberHidden');
					calp.removeClass('calFadeOut');
					setTimeout(function(){
						hnsNumber.find('.minuteNumber').hide();
						hnsView.hide();
					}, 400);
				}
			});
			if(!options.showCal){
				calp.css('opacity', 0);
				setTimeout(function(){
					let hnsWidth = hnsView.outerWidth(false);
					if(hnsWidth > hnsView.outerHeight(false))hnsWidth = hnsView.outerHeight(false);
					hnsView.find('.number').children('div').css({width:hnsWidth-10*2, height:hnsWidth-10*2});
					hnsView.find('.numberHidden:eq(0)').removeClass('numberHidden');
				}, 50);
			}
		}
		function changePrevMonth(){
			if(!options.changeMonth)return false;
			if(options.minYear === date.getFullYear() && minFullDate.getMonth()>date.getMonth()-1)return false;
			date.setDate(1);
			date.setMonth(date.getMonth()-1);
			InitCalendar(cal.parent(), date, 'right');
			if($.isFunction(options.prevMonthCallback))setTimeout(function(){options.prevMonthCallback.call(cal.parent(), date)}, 10);
		}
		function changeNextMonth(){
			if(!options.changeMonth)return false;
			if(options.maxYear === date.getFullYear() && maxFullDate.getMonth()<date.getMonth()+1)return false;
			date.setDate(1);
			date.setMonth(date.getMonth()+1);
			InitCalendar(cal.parent(), date, 'left');
			if($.isFunction(options.nextMonthCallback))setTimeout(function(){options.nextMonthCallback.call(cal.parent(), date)}, 10);
		}
		if(options.prevMonth)$(options.prevMonth).off('click').on('click', changePrevMonth);
		if(options.nextMonth)$(options.nextMonth).off('click').on('click', changeNextMonth);
		cal.find('.yearUL a').on('dragstart', function(e){e.preventDefault()});
		cal.find('.yearUL a, .dayUL li:not(.notcurrent,.disabled)').focus(function(){
			this.blur();
		}).hover(function(){
			if(!$(this).attr('cal') && !$(this).attr('bg'))$(this).addClass('hover');
		}, function(){
			if(!$(this).attr('cal') && !$(this).attr('bg'))$(this).removeClass('hover');
		}).click(function(){
			if($(this).attr('cal') === 'prevyears'){
				if(!options.changeYear)return false;
				if(cal.find('.hns').length && cal.find('.hns').hasClass('cal'))return false;
				let tmpDate = $(this).attr('year')*1-1;
				if(minFullDate.getFullYear()>tmpDate)return false;
				date = new Date($(this).attr('year')*1-12, date.getMonth(), date.getDate());
				InitCalendar(cal.parent(), date, 'right', true);
			}else if($(this).attr('cal') === 'nextyears'){
				if(!options.changeYear)return false;
				if(cal.find('.hns').length && cal.find('.hns').hasClass('cal'))return false;
				let tmpDate = $(this).attr('year')*1+12;
				if(maxFullDate.getFullYear()<tmpDate)return false;
				date = new Date($(this).attr('year')*1+12, date.getMonth(), date.getDate());
				InitCalendar(cal.parent(), date, 'left', true);
			}else if($(this).attr('cal') === 'prevyear'){
				if(!options.changeYear)return false;
				if(cal.find('.hns').length && cal.find('.hns').hasClass('cal'))return false;
				let tmpDate = date.getFullYear()-1;
				if(minFullDate.getFullYear()>tmpDate)return false;
				date.setFullYear(date.getFullYear()-1);
				InitCalendar(cal.parent(), date, 'right', calp.data('selectYear'), calp.data('selectMonth'));
			}else if($(this).attr('cal') === 'nextyear'){
				if(!options.changeYear)return false;
				if(cal.find('.hns').length && cal.find('.hns').hasClass('cal'))return false;
				let tmpDate = date.getFullYear()+1;
				if(maxFullDate.getFullYear()<tmpDate)return false;
				date.setFullYear(date.getFullYear()+1);
				InitCalendar(cal.parent(), date, 'left', calp.data('selectYear'), calp.data('selectMonth'));
			}else if($(this).attr('cal') === 'prevmonth'){
				if(cal.find('.hns').length && cal.find('.hns').hasClass('cal'))return false;
				changePrevMonth();
			}else if($(this).attr('cal') === 'nextmonth'){
				if(cal.find('.hns').length && cal.find('.hns').hasClass('cal'))return false;
				changeNextMonth();
			}else if($(this).attr('cal') === 'years'){
				return false;
			}else if($(this).attr('cal') === 'year'){
				if(!options.changeYear)return false;
				if(cal.find('.hns').length && cal.find('.hns').hasClass('cal'))return false;
				calp.data('curYear', date.getFullYear());
				let h = (date.getFullYear() - options.minYear) % 12, ha = date.getFullYear() - h;
				date.setFullYear(ha);
				calp.data('selectYear', true);
				InitCalendar(cal.parent(), date, 'bottom', true);
			}else if($(this).attr('cal') === 'month'){
				if(!options.changeMonth)return false;
				if(cal.find('.hns').length && cal.find('.hns').hasClass('cal'))return false;
				calp.data('curYear', date.getFullYear());
				calp.data('curMonth', date.getMonth());
				calp.data('selectMonth', true);
				InitCalendar(cal.parent(), date, 'bottom', false, true);
			}else{
				if(options.disable)return false;
				let _obj = $(this), toDay = new Date(), newDate = '',
				year = !!_obj.attr('year') ? Number(_obj.attr('year')) : toDay.getFullYear(),
				month = !!_obj.attr('month') ? Number(_obj.attr('month')) : toDay.getMonth(),
				day = !!_obj.attr('day') ? Number(_obj.attr('day')) : toDay.getDate();
				if(!!calp.data('selectYear')){
					if(!options.changeYear)return false;
					date.setDate(1);
					date.setFullYear(year);
					calp.removeData('selectYear');
					InitCalendar(cal.parent(), date, 'top', false, calp.data('selectMonth'));
					calp.removeData('curYear').removeData('curMonth');
					return false;
				}
				if(!!calp.data('selectMonth')){
					if(!options.changeMonth)return false;
					date.setDate(1);
					date.setMonth(month);
					calp.removeData('selectMonth');
					InitCalendar(cal.parent(), date, 'top');
					calp.removeData('curYear').removeData('curMonth');
					return false;
				}
				if(options.showTime){
					let hnsView = calp.parent().find('.hnsView'), hour = initdate.getHours(), minute = initdate.getMinutes(), second = initdate.getSeconds();
					if(options.showHour)hour = Number(hnsView.find('.hourNumber').attr('data-number'));
					if(options.showMinute)minute = Number(hnsView.find('.minuteNumber').attr('data-number'));
					newDate = new Date(year, month, day, hour, minute, second);
				}else{
					newDate = new Date(year, month, day, 0, 0, 0);
				}
				if(!_obj.hasClass('current') || options.range){
					if(options.range){
						if(endDate){
							dates = [];
							beginDate = null;
							endDate = null;
							calp.find('.current').each(function(){
								$(this).removeAttr('bg').removeClass('current').addClass('normal');
								if(!!$(this).attr('today'))$(this).attr('bg', 'today').addClass('today');
							});
							calp.find('.between').each(function(){
								$(this).removeAttr('bg').removeClass('between').addClass('normal');
								if(!!$(this).attr('today'))$(this).attr('bg', 'today').addClass('today');
							});
						}
						if(!beginDate){
							beginDate = newDate;
							dates.push(newDate);
						}else{
							if(Date.parse(beginDate)>Date.parse(newDate)){
								endDate = beginDate;
								beginDate = newDate;
								dates.unshift(newDate);
							}else{
								endDate = newDate;
								dates.push(newDate);
							}
						}
					}else{
						if(!options.multiple){
							let old = calp.find('.current');
							dates = [];
							if(old.length){
								old.removeAttr('bg').removeClass('current').addClass('normal');
								if(!!old.attr('today'))old.attr('bg', 'today').addClass('today');
							}
						}
						dates.push(newDate);
					}
					_obj.attr('bg', 'current').removeClass('hover').addClass('current');
					if(options.range && dates.length === 2)betweenDate();
				}else if(options.multiple){
					_obj.removeAttr('bg').removeClass('current').addClass('normal');
					if(!!_obj.attr('today'))_obj.attr('bg', 'today').addClass('today');
					for(let d=0; d<dates.length; d++){
						if(dates[d].getTime() === newDate.getTime())dates.splice(d, 1);
					}
				}else{
					dates = [newDate];
				}
				if(!options.range && !options.multiple){
					let hnsView = calp.parent().find('.hnsView');
					if(hnsView.length){
						let hnsWidth = hnsView.outerWidth(false), g = 10;
						if(options.fullscreen)g = 40;
						if(hnsWidth > hnsView.outerHeight(false))hnsWidth = hnsView.outerHeight(false);
						hnsView.find('.number').children('div').css({width:hnsWidth-g*2, height:hnsWidth-g*2});
						hnsView.show();
						calp.addClass('calFadeOut');
						setTimeout(function(){
							hnsView.find('.numberHidden:eq(0)').removeClass('numberHidden');
						}, 0);
						setTimeout(function(){
							hnsView.find('.hnsNumber').children('a').fadeIn(200);
							hnsView.find('.hnsNumber').children('span').fadeIn(200);
						}, 300);
						return false;
					}
				}
				if(options.range){
					if(dates.length === 2)washFunction();
				}else{
					washFunction();
				}
			}
		});
		function washFunction(_dates){
			$.each(options, function(key, fn){
				let isBreakKey = ((key === 'click' && options.breakClick) || $.inArray(key, ['date', 'prevMonthCallback', 'nextMonthCallback', 'shown', 'hidden', 'change', 'complete']) > -1);
				if($.isFunction(fn) && !isBreakKey)fn.call(options.obj, typeof _dates === 'undefined' ? dates : _dates);
			});
		}
		function betweenDate(){
			let current = cal.find('.current');
			if(!current.length){
				let unix = Date.parse(cal.find('.dayUL li[title]').eq(0).attr('date').date());
				if(unix > Date.parse(beginDate) && unix < Date.parse(endDate)){
					cal.find('.dayUL li[title]').each(function(){
						let obj = $(this);
						obj.attr('bg', 'between').removeClass('hover').addClass('between');
					});
				}
			}else{
				let li = current.eq(0), obj = li, liDate = li.attr('date');
				if(Date.parse(liDate.date()) === Date.parse(endDate)){
					if(Date.parse(liDate.date()) !== Date.parse(beginDate)){
						while(obj){
							if(obj.prev().length && !obj.prev().is('.notcurrent')){
								obj = obj.prev();
							}else{
								if(obj.parent().prev().is('.weekUL'))break;
								obj = obj.parent().prev().children().last();
							}
							obj.attr('bg', 'between').removeClass('hover').addClass('between');
						}
					}
				}else{
					while(!obj.is(current.eq(1))){
						if(obj.next().length && obj.next().is('li')){
							obj = obj.next();
						}else{
							if(!obj.parent().next().length)break;
							obj = obj.parent().next().children().eq(0);
						}
						obj.attr('bg', 'between').removeClass('hover').addClass('between');
					}
				}
			}
		}
		function touchMove(){
			if(!options.changeYear || !options.changeMonth)return;
			let type = cal.attr('type'), startX, startY, bindMoveDrag = function(e){
				startX = $.touches(e).x;
				startY = $.touches(e).y;
				cal.on('mousemove touchmove', moveDrag);
			},
			startDrag = function(e){
				let o = $.etarget(e);
				do{
					if($(o).is('.hnsView'))return;
					if($(o).is('.childrenDIV')){bindMoveDrag(e);return}
					o = o.parentNode;
				}while(o.parentNode);
			},
			moveDrag = function(e){
				e.preventDefault();
				return true;
			},
			endDrag = function(e){
				let a, curX, curY, touchX, touchY;
				cal.off('mousemove touchmove', moveDrag);
				curX = $.touches(e).x;
				curY = $.touches(e).y;
				touchX = curX - startX;
				touchY = curY - startY;
				if(touchX>0 && touchY<15 && touchY>-15){ //prev
					switch(type){
						case 'year':a = cal.find('[cal="prevyears"]');break;
						case 'month':a = cal.find('[cal="prevyear"]');break;
						default:a = cal.find('[cal="prevmonth"]');break;
					}
					if(!a.length)return;
					a.click();
				}else if(touchX<0 && touchY<15 && touchY>-15){ //next
					switch(type){
						case 'year':a = cal.find('[cal="nextyears"]');break;
						case 'month':a = cal.find('[cal="nextyear"]');break;
						default:a = cal.find('[cal="nextmonth"]');break;
					}
					if(!a.length)return;
					a.click();
				}else if(touchX<15 && touchX>-15 && touchY>0){ //select
					switch(type){
						case 'year':a = cal.find('[cal="years"]');break;
						case 'month':a = cal.find('[cal="year"]');break;
						default:a = cal.find('[cal="month"]');break;
					}
					if(!a.length)return;
					a.click();
				}else if(touchX<15 && touchX>-15 && touchY<0){
					if(type !== 'day'){
						a = cal.find('.current a');
						if(!a.length)return;
						a.click();
					}
				}
			};
			cal.unselect().on('mousedown touchstart', startDrag).on('mouseup touchend', endDrag);
		}
	}
	return this.each(function(){
		area = $(this);
		let cal = area.children('div').children('div').eq(0), date = dates.length ? (dates[dates.length-1]).Clone() : new Date();
		InitCalendar(cal, date);
		if($.isFunction(options.complete))setTimeout(function(){options.complete.call(cal.parent(), date)}, 10);
	});
};

//滚筒select, options为[数字字符串|数字|数组]即刷新对应索引的component(使用滚筒有效)
//arguments.callee: 正被执行的Function对象
$.fn.selectpicker = function(options){
	if(typeof options === 'string' || typeof options === 'number' || $.isArray(options)){
		if(typeof options === 'string'){
			options = parseInt(options);
			if(isNaN(options))return this;
		}
		if(typeof options === 'number')options = [options];
		for(let i=0; i<options.length; i++){
			let component = this.find('.component'+options[i]);
			if(!component.length || !$.isFunction(component.data('selectpicker.setItems')))continue;
			component.data('selectpicker.setItems')(component);
		}
		return this;
	}
	options = $.extend({
		objs: [], //滚筒列表
		cls: '', //附加类名
		only: '', //只在指定环境使用插件, 有效值[web|mobile]
		isPicker: $.browser.mobile, //使用滚筒
		search: false, //使用搜索框(不使用滚筒时有效)
		elements: '', //下拉列表增加子选项(不使用滚筒时有效)
		click: null, //下拉列表子选项(或列表本身)点击后执行(不使用滚筒时有效), 返回false不执行options.select
		autoWidth: true, //各列自动宽度
		rotate: 25, //滚筒rotateX弧度, 0即直上直下
		select: null, //选择后执行, 不使用滚筒(this:调用对象[select]), 使用滚筒(this:调用对象, 三个参数, component:滚筒, row:行, select:对应的select)
		complete: null //插件运行后执行
	}, $('body').data('selectpicker.options'), options);
	if(!!this.data('selectpicker.options'))options = $.extend(options, this.data('selectpicker.options'));
	if(!options.isPicker && $.window().width>750 && (!options.only.length || options.only === 'web')){ //不是手机且屏幕宽度过大就不使用滚筒
		return this.each(function(){
			let _this = $(this), multiple = _this.attr('multiple'), disabled = _this.attr('disabled'), search = _this.attr('search')||options.search,
				wrap = $('<big class="selectpicker '+options.cls+'"></big>'), span = $('<span></span>'), div = $('<div></div>'), html = '', index = 0;
			if(!!_this.data('selectpicker.wrap')){
				_this.data('selectpicker.wrap').after(_this);
				_this.data('selectpicker.wrap').remove();
			}
			let width = _this.outerWidth(false);
			_this.wrap(wrap);
			wrap = _this.parent();
			wrap.width(width-2);
			if(!!_this.attr('height')){
				wrap.height(_this.attr('height'));
				span.css('line-height', _this.attr('height')+'px');
				div.css('top', Number(_this.attr('height'))+3);
			}
			_this.data('selectpicker.wrap', wrap);
			wrap.append(span);
			let text = [];
			_this.selected().each(function(){text.push($(this).text())});
			span.html(text.join(', '));
			if(!!disabled){wrap.addClass('selectpicker-disabled');return}
			if(search)html = '<span><input type="text" /></span>';
			_this.on('change', function(){
				let selected = $(this).selected();
				let selectedIndex = $(this).find('option').index(selected);
				let _selected = div.find('a').eq(selectedIndex).addClass('this');
				_selected.siblings().removeClass('this');
				span.html(selected.text());
			});
			_this.find('option').each(function(){
				let el = $(this);
				if(el.is('optgroup')){
					html += '<font>'+(el.attr('label')||'')+'</font>';
					el.children().each(function(){
						html += '<a href="javascript:void(0)" index="'+index+'" class="indent'+($(this).prop('selected')?' this':'')+'">'+$(this).html()+'</a>';
						index++;
					});
				}else{
					html += '<a href="javascript:void(0)" index="'+index+'" '+(el.prop('selected')?'class="this"':'')+'>'+options.elements+el.html()+'</a>';
					index++;
				}
			});
			wrap.append(div.hide());
			if(multiple)div.addClass('multiple');
			div.html(html);
			/*setTimeout(function(){
				if( (($.window().scrollTop+$.window().height) - (wrap.offset().top+$.unit(div.css('top'))+div.height()) - 5) <0 ){
					let height = ($.window().scrollTop+$.window().height) - (wrap.offset().top+$.unit(div.css('top'))) - 5;
					height -= div.padding().top + div.padding().bottom;
					div.height(height);
				}
			}, 10);*/
			wrap.control({
				expr: wrap.find('.multiple').add(div.children('span')),
				show: function(){
					wrap.addClass('selectpicker-x');
					div.width(wrap.outerWidth(false)+(search?50:0));
					div.show();
					if($.window().height-div.offset().top-10 < 200){
						div.css({
							top: 'auto',
							bottom: wrap.outerHeight(false)+3,
							'max-height': ''
						});
					}else{
						div.css({
							top: wrap.outerHeight(false)+3,
							bottom: '',
							'max-height': $.window().height-div.offset().top-10
						});
					}
				},
				hide: function(){
					wrap.removeClass('selectpicker-x');
					div.hide();
					div.find('input').val('');
					div.find('a, font').show();
				}
			});
			div.find('input').on('input', function(){
				let val = $.trim($(this).val());
				if(!val.length){
					div.find('a, font').show();
				}else{
					div.find('a, font').hide();
					div.find('a').each(function(){if($(this).text().indexOf(val)>-1)$(this).show()});
				}
			});
			div.find('a').on('click', function(e){
				let o = $.etarget(e);
				if($.isFunction(options.click)){
					let result = options.click.call(o);
					if(typeof result === 'boolean' && !result)return;
				}
				let option = $(this), index = Number(option.attr('index'));
				if(multiple){
					if(option.hasClass('this')){
						option.removeClass('this');
						_this.find('option').eq(index).removeAttr('selected').prop('selected', false);
					}else{
						option.addClass('this');
						_this.find('option').eq(index).attr('selected', 'selected').prop('selected', true);
					}
					if($.isFunction(options.select))options.select.call(_this);
					let text = [];
					_this.selected().each(function(){text.push($(this).text())});
					span.html(text.join(', '));
				}else{
					span.html(option.text());
					_this.selected(index);
					if($.isFunction(options.select))options.select.call(_this);
					option.addClass('this').siblings().removeClass('this');
					wrap.control(false);
				}
			});
			if($.isFunction(options.complete))options.complete.call(_this);
		});
	}
	if( !(!options.only.length || options.only === 'mobile') )return this;
	return this.data('selectpicker.options', options).each(function(){
		let _this = $(this), objs = options.objs, selects = [];
		for(let i=0; i<objs.length; i++){
			let obj = objs[i];
			if(typeof obj !== 'string' && obj.is('select'))selects.push(obj);
		}
		if(!selects.length)return true;
		let selectpicker = _this.find('.selectpicker'), highlight = _this.find('.highlight');
		if(!selectpicker.length){
			selectpicker = $('<div class="selectpicker '+(options.cls.length?options.cls:'')+'"><aside></aside></div>');
			highlight = $('<strong class="highlight"><big></big><big></big><em></em></strong>');
			_this.append(selectpicker.unselect().css('touch-action', 'none'));
			selectpicker.append(highlight);
		}
		let aside = selectpicker.find('aside'), holderHeight = 0, itemHeight = 0;
		$.each(objs, function(k, _obj){
			if(typeof _obj === 'string'){
				let component = _this.find('.component'+k);
				if(!component.length)aside.append('<div><section class="component'+k+'" data-index="'+k+'"><em>'+_obj+'</em></section></div>');
				return true;
			}
			let _select = $(this), component = _this.find('.component'+k), existComponent = true;
			if(!component.length){
				existComponent = false;
				let d = $('<div><section class="component'+k+'" data-index="'+k+'"><ul></ul></section></div>');
				aside.append(d);
				component = d.children().eq(0);
				d.on('touchstart', function(){
					component.data('skip-scrollstop.outside', true);
				}).on('touchmove', function(){
					component.removeData('skip-scrollstop.outside');
				});
				component.data('selectpicker.select', _select);
				component.on('scroll', function(){
					optionRotate();
				}).stopBounces().scrollstop(function(){
					let scrollTop = component.scrollTop(), residue = scrollTop % itemHeight;
					if(residue >= itemHeight / 2){
						scrollTop -= residue;
						scrollTop += itemHeight;
					}else{
						scrollTop -= residue;
					}
					let selectIndex = scrollTop / itemHeight, li = component.find('li:not(.optionholder)').eq(selectIndex);
					selectRow(li, true);
				});
				if(component.inertia)component.inertia({
					lockX: true,
					scroll: true
				});
			}
			setItems(component);
			if(!holderHeight)holderHeight = (selectpicker.height() - highlight.height()) / 2;
			let ul = component.find('ul'), selected = ul.find('.selected');
			if(!selected.length)selected = ul.find('li:not(.optionholder)').eq(0).addClass('selected');
			let selectIndex = ul.find('li:not(.optionholder)').index(selected);
			if(!itemHeight)itemHeight = selected.height();
			_select.data('selectHtml', _select.html().replace(/\sselected="[^"]*"/g, '').replace(/\sselected/g, ''));
			_select.selected(selectIndex, false);
			if(!existComponent){
				selectRow(selected, false, false);
				if(!selectIndex)optionRotate();
			}else{
				if(_select.data('selectIndex') !== selectIndex)component.data('skip-scrollstop', true).animate({scrollTop:selectIndex * itemHeight}, 300, function(){
					setTimeout(function(){component.removeData('skip-scrollstop').removeData('skip-scrollstop.outside')}, 500);
				});
				optionRotate();
			}
			function setItems(component){
				let selectpicker = component.parents('.selectpicker').eq(0), ul = component.find('ul'), _select = component.data('selectpicker.select');
				if(!ul.length || !_select.length)return;
				let selected = _select.selected(), selectIndex = _select.find('option').index(selected), selectText = selected.text(), li = ul.find('li:not(.optionholder)').eq(selectIndex),
					selectHtml = _select.html().replace(/\sselected="[^"]*"/g, '').replace(/\sselected/g, '');
				if(_select.data('selectHtml') === selectHtml && li.length && li.html() === selectText){
					if(_select.data('selectIndex') !== selectIndex)component.data('skip-scrollstop', true).animate({scrollTop:selectIndex * li.height()}, 300, function(){
						setTimeout(function(){component.removeData('skip-scrollstop').removeData('skip-scrollstop.outside')}, 500);
					});
				}else{
					ul.css('transform', 'translateZ(0px)').html('');
					let html = '<li class="optionholder"></li>';
					_select.find('option').each(function(){
						html += '<li'+($(this).prop('selected')?' class="selected"':'')+'>'+$(this).text()+'</li>';
					});
					html += '<li class="optionholder"></li>';
					ul.html(html).find('li:not(.optionholder)').css('transition-timing-function', 'cubic-bezier(0.23,1,0.32,1)');
					let highlight = selectpicker.find('.highlight'), holderHeight = (selectpicker.height() - highlight.height()) / 2;
					highlight.find('big').height(Math.ceil(holderHeight));
					ul.find('.optionholder').height(holderHeight);
					ul.find('li:not(.optionholder)').tapper(function(){
						component.data('selectpicker.selectRow')($(this), true);
					}).each(function(){
						$(this).attr('position-top', $(this).position().top);
					});
					_select.data('selectHtml', selectHtml);
					component.data('skip-scrollstop', true).animate({scrollTop:0}, 300, function(){
						setTimeout(function(){component.removeData('skip-scrollstop').removeData('skip-scrollstop.outside')}, 500);
					});
				}
				_select.data('selectIndex', selectIndex);
			}
			function optionRotate(){
				if(options.rotate <= 0)return;
				let t = component.scrollTop();
				ul.find('li:not(.optionholder)').each(function(){
					let n = options.rotate * ((Number($(this).attr('position-top')) - holderHeight - t) / itemHeight);
					$(this).css('transform', 'rotateX('+n+'deg)');
				});
			}
			function selectRow(row, animate, complete){
				if(!row.length)return;
				if(typeof complete === 'undefined')complete = true;
				row.addClass('selected').siblings().removeClass('selected');
				let selectIndex = ul.find('li:not(.optionholder)').index(row);
				_select.data('selectIndex', selectIndex);
				if(animate){
					component.data('skip-scrollstop', true).animate({scrollTop:selectIndex * itemHeight}, 300, function(){
						setTimeout(function(){component.removeData('skip-scrollstop').removeData('skip-scrollstop.outside')}, 500);
						if(complete)completeRow(selectIndex);
						if($.isFunction(options.select))setTimeout(function(){options.select.call(_this, component, row, _select)}, 100);
					});
				}else{
					component.data('skip-scrollstop', true).animate({scrollTop:selectIndex * itemHeight}, 0);
					setTimeout(function(){component.removeData('skip-scrollstop').removeData('skip-scrollstop.outside')}, 500);
					if(complete)setTimeout(function(){
						completeRow(selectIndex);
						if($.isFunction(options.select))setTimeout(function(){options.select.call(_this, component, row, _select)}, 100);
					}, 0);
				}
			}
			function completeRow(selectIndex){
				_select.find('option').removeAttr('selected');
				_select.selected(Number(selectIndex));
				_select.find('option:eq('+selectIndex+')').attr('selected', 'selected');
			}
			component.data('selectpicker.setItems', setItems);
			component.data('selectpicker.selectRow', selectRow);
		});
		setTimeout(function(){aside.find('section').removeData('skip-scrollstop')}, 500);
		if(options.autoWidth)setWidth();
		if($.isFunction(options.complete))options.complete.call(_this);
		function setWidth(){
			let section = selectpicker.find('section'), totalWidth = 0, parent = selectpicker.children();
			section.each(function(){
				$(this).data('curWidth', $(this).outerWidth(false)-$(this).padding().right);
			}).css('width', 'auto').each(function(){
				$(this).data('autoWidth', $(this).outerWidth(false));
				totalWidth += $(this).outerWidth(false);
			}).each(function(){
				let width = $(this).data('curWidth');
				$(this).width(width).parent().width(width);
			}).each(function(){
				let percent = $(this).data('autoWidth') / totalWidth, width = Math.floor(parent.width() * percent);
				$(this).width(width).parent().width(width);
			});
		}
	});
};

//拖拽
$.fn.drag = function(options){
	options = $.extend({
		target: null, //被移动的对象
		releaseTarget: document.body, //执行mouseup的对象
		exceptEl: 'input, textarea, select', //不会触发移动的对象或函数
		area: null, //只能在area区域移动,否则随意移动
		lockX: false, //锁定水平位置
		lockY: false, //锁定垂直位置
		lockRange: true, //锁定区域(不能移出area区域)
		lockRangeRelax: null, //放松锁定区域的位置,{left:true, top:true, right:true, bottom:true}
		useTransform: false, //使用CSS3特性来移动
		autoCursor: true, //鼠标自动变为move
		suck: '', //吸附边缘,[0|'']不吸附,[1|parent]吸附父元素边缘,[2|all]吸附所有(包括窗口、父元素与兄弟元素)
		suckDistance: 10, //吸附临界点
		suckIgnore: [], //忽略吸附位置,[1上|2右|3下|4左]
		handleCss: {}, //开始拖动时增加的样式
		handleClass: '', //开始拖动时增加的样式名
		debug: false, //显示当前移动的位置数据
		before: null, //按下鼠标前执行
		start: null, //按下鼠标后执行
		move: null, //移动时执行,若返回值为字面量即替换对应left,top
		stop: null, //停止时执行
		click: null, //左点击时执行(没有移动即为点击)
		rightClick: null, //右点击时执行
		complete: null //插件加载后执行
	}, $('body').data('drag.options'), options);
	return this.each(function(){
		if(!!$(this).stopBounces(true).data('drag'))return true;
		let _this = $(this), x, y, right, bottom, debug = [], range = {top:-9999, left:-9999, right:9999, bottom:9999}, startX, startY, dirX = 0, dirY = 0, sl, st, curX, curY,
			target = !options.target ? _this : ($.isFunction(options.target) ? options.target.call(_this) : $(options.target)),
			releaseTarget = !options.releaseTarget ? $(document.body) : ($.isFunction(options.releaseTarget) ? $(options.releaseTarget.call(_this)) : $(options.releaseTarget)),
			doc = options.area ? $(options.area) : $(document), documentPw = $(document).innerWidth(), documentPh = $(document).innerHeight(),
			parentLeft = _this.parent().offset().left, parentTop = _this.parent().offset().top,
			suckArr = {'no':0, 'parent':1, 'all':2}, suck = options.suck, suckPw = doc[0].clientWidth, suckPh = doc[0].clientHeight,
			dis = options.suckDistance, targetWidth = 0, targetHeight = 0, targetBrother = null, started = false, moved = false;
		_this.data('drag', true).data('drag-options', options);
		if($.browser.mozilla)_this.css('-moz-user-select', 'none');
		if(options.debug){
			debug = $('#drag_debug');
			if(!debug.length){
				let transform = target.transform();
				debug = $('<div id="drag_debug" style="position:fixed;margin:0 10px;top:10px;z-index:999;border:1px solid #666;padding:5px;background:rgba(255,255,255,0.95);font-size:12px;"></div>');
				$('body').append(debug);
				debug.html('<b>left:</b>'+target.position().left+', <b>top:</b>'+target.position().top+', <b>right:</b>0, <b>bottom:</b>0, <b>dirX:</b>'+dirX+', <b>dirY:</b>'+dirY+', <b>range.left:</b>'+range.left+', <b>range.top:</b>'+range.top+', <b>translateX:</b>'+transform.x+', <b>translateY:</b>'+transform.y);
				if($.browser.ie6){
					debug.css('position', 'absolute');
					$(window).scroll(function(){debug.css('top', $.scroll().top+10)});
				}
			}
		}
		if(typeof suck === 'string'){
			if(suck.length && suckArr[suck.toLowerCase()])suck = suckArr[suck.toLowerCase()];
			else suck = 0;
		}
		_this.unselect().on('mousedown', dragStart).on('mouseover', function(){if(options.autoCursor)$(this).css('cursor','move')}).on('mouseout', function(){$(this).css('cursor','')});
		if(window.addEventListener)_this[0].addEventListener('touchstart', dragStart, true);
		if($.isFunction(options.complete))options.complete.call(_this);
		function dragStart(e){
			let o = $.etarget(e);
			if(options.exceptEl !== null){
				if($.isFunction(options.exceptEl)){
					let result = options.exceptEl.call(_this, e, $(o));
					if(typeof result === 'boolean' && result)return true;
				}else{
					if($(o).is(options.exceptEl))return true;
				}
			}
			if(e.preventDefault)e.preventDefault();
			if($.isFunction(options.rightClick) && e.button === 2){
				let tx = $.touches(e).x, ty = $.touches(e).y;
				options.rightClick.call(_this, e, {left:tx+$.scroll().left, top:ty+$.scroll().top});
				document.oncontextmenu = function(){return false};
				setTimeout(function(){
					document.oncontextmenu = function(){return true};
				}, 100);
				return true;
			}
			if((e.type === 'mousedown' && e.button!==0) || (e.type === 'touchstart' && e.which !== 0))return true;
			if($.isFunction(options.before))options.before.call(_this, e);
			started = true;
			moved = false;
			sl = doc.scrollLeft();
			st = doc.scrollTop();
			let tmargin = target.margin(), ml = tmargin.left, mt = tmargin.top, mr = tmargin.right, mb = tmargin.bottom,
				left = target.offset().left, top = target.offset().top;
			if(left !== target.position().left)left = target.position().left;
			if(options.area)left += sl;
			if(top !== target.position().top)top = target.position().top;
			if(options.area)top += st;
			target.data({
				transformx: target.transform().x,
				transformy: target.transform().y,
				left: left,
				top: top
			});
			if($.isFunction(options.start))options.start.call(_this, e, {left:left, top:top});
			if( ($.browser.ie7) && (target.is('tr') || target.is('td')) ){
				top = target.parents('table').eq(0).position().top;
				let tar = target.parents('tr').eq(0);
				tar.parent().children().each(function(){
					if($(this).is(tar))return false;
					else top += $(this).height();
				});
			}
			startX = $.touches(e).x;
			startY = $.touches(e).y;
			curX = startX;
			curY = startY;
			x = startX - left + ml;
			y = startY - top + mt;
			if(options.lockRange){
				if(options.area && $(options.area).find(target).length){
					let area = $(options.area), areaTop = 0, areaLeft = 0;
					if(area.css('position') === 'static'){
						areaTop = area.offset().top;
						areaLeft = area.offset().left;
					}
					range.top = areaTop + $.unit(area.css('border-top-width')) - 1;
					range.left = areaLeft + $.unit(area.css('border-left-width')) - 1;
					range.right = range.left + ((area.is('body')&&target.css('position') === 'fixed')?$.window().width+1:area.outerWidth(false)+1) + sl;
					range.bottom = range.top + ((area.is('body')&&target.css('position') === 'fixed')?$.window().height+1:area.outerHeight(false)+1) + st;
				}else{
					/*range = {
						top:target.css('position') === 'fixed'?-1:st-1, left:target.css('position') === 'fixed'?-1:sl-1,
						right:(target.css('position') === 'fixed'?0:sl)+$.window().width+1, bottom:(target.css('position') === 'fixed'?0:st)+$.window().height+1
					};*/
				}
				if($.isPlainObject(options.lockRangeRelax)){
					if(options.lockRangeRelax.top)range.top = -9999;
					if(options.lockRangeRelax.left)range.left = -9999;
					if(options.lockRangeRelax.right)range.right = 9999;
					if(options.lockRangeRelax.bottom)range.bottom = 9999;
				}
			}
			target.data('position', target.css('position'));
			if(target.css('position') === 'static')target.css({position:'absolute'});
			target.css({'transition-duration':'0s', '-webkit-transition-duration':'0s'}).css(options.handleCss).css({left:left, top:top});
			if(options.handleClass.length)target.addClass(options.handleClass);
			if($.inArray(2, options.suckIgnore) === -1)targetWidth = target.outerWidth(false);
			if($.inArray(3, options.suckIgnore) === -1)targetHeight = target.outerHeight(false);
			targetBrother = target.siblings();
			right = range.right - targetWidth - ml - mr;
			bottom = range.bottom - targetHeight - mt - mb;
			if(debug.length){
				let transform = target.transform();
				debug.html('<b>left:</b>'+left+', <b>top:</b>'+top+', <b>right:</b>'+right+', <b>bottom:</b>'+bottom+', <b>dirX:</b>'+dirX+', <b>dirY:</b>'+dirY+', ' +
					'<b>range.left:</b>'+range.left+', <b>range.top:</b>'+range.top+', <b>translateX:</b>'+transform.x+', <b>translateY:</b>'+transform.y);
			}
			if(!!$(o).hasClass('resizable-handler'))return true;
			$(document.body).on('mousemove', dragMove);
			releaseTarget.on('mouseup mouseleave blur', dragEnd);
			if(window.addEventListener){
				document.body.addEventListener('touchmove', dragMove, true);
				releaseTarget[0].addEventListener('touchend', dragEnd, true);
				releaseTarget[0].addEventListener('touchcancel', dragEnd, true);
			}
			target[0].setCapture && target[0].setCapture();
		}
		function dragMove(e){
			if(e.preventDefault)e.preventDefault();
			let dx = $.touches(e).x, dy = $.touches(e).y;
			let left = dx - x, top = dy - y;
			if(dx>curX){dirX = 1}else if(dx<curX){dirX = -1}else{dirX = 0}
			if(dy>curY){dirY = 1}else if(dy<curY){dirY = -1}else{dirY = 0}
			moved = true;
			curX = dx;
			curY = dy;
			if(options.lockX){
				if(options.useTransform){
					left = target.data('transformx');
				}else{
					left = target.data('left');
				}
			}
			if(options.lockY){
				if(options.useTransform){
					top = target.data('transformy');
				}else{
					top = target.data('top');
				}
			}
			left = Math.min(Math.max(left, range.left), right);
			top = Math.min(Math.max(top, range.top), bottom);
			if($.isFunction(options.move)){
				//left:当前水平, top:当前垂直, dirX|dirY:[1(向右|下), -1(向左|上), 0(原位)]
				let result = options.move.call(_this, e, {left:left, top:top, dirX:dirX, dirY:dirY});
				if($.isPlainObject(result)){
					if(typeof(result.top) !== 'undefined')top = result.top;
					if(typeof(result.left) !== 'undefined')left = result.left;
				}
			}
			if(options.lockRange && suck>0){
				switch(suck){
					case 1:
						suckPw = _this.parent().outerWidth(false);
						suckPh = _this.parent().outerHeight(false);
						if(left<dis && left>-dis)left = 0;
						else if(left<-targetWidth+dis && left>-targetWidth-dis)left = -targetWidth;
						else if(left>suckPw-targetWidth-dis && left<suckPw-targetWidth+dis)left = suckPw - targetWidth;
						else if(left>suckPw-dis && left<suckPw+dis)left = suckPw;
						if(top<dis && top>-dis)top = 0;
						else if(top<-targetHeight+dis && top>-targetHeight-dis)top = -targetHeight;
						else if(top>suckPh-targetHeight-dis && top<suckPh-targetHeight+dis)top = suckPh - targetHeight;
						else if(top>suckPh-dis && top<suckPh+dis)top = suckPh;
						break;
					case 2:
						targetBrother.each(function(){
							let brother = $(this), bleft = brother.position().left, btop = brother.position().top,
								bwidth = brother.outerWidth(false), bheight = brother.outerHeight(false);
							if(left<=bleft+bwidth+dis+sl && left>=bleft+bwidth-dis+sl && top+targetHeight>=btop+st && top<=btop+bheight+st){
								left = bleft + bwidth + st;
							}else if(left+targetWidth>=bleft-dis+sl && left+targetWidth<=bleft+dis+sl && top+targetHeight>=btop+st && top<=btop+bheight+st){
								left = bleft - targetWidth + st;
							}
							if(top<=btop+bheight+dis+st && top>=btop+bheight-dis+st && left+targetWidth>=bleft+sl && left<=bleft+bwidth+sl){
								top = btop + bheight + st;
							}else if(top+targetHeight>=btop-dis+st && top+targetHeight<=btop+dis+st && left+targetWidth>=bleft+sl && left<=bleft+bwidth+sl){
								top = btop - targetHeight + st;
							}
						});
						suckPw = _this.parent().outerWidth(false);
						suckPh = _this.parent().outerHeight(false);
						if(left<dis && left>-dis)left = 0;
						else if(left<-targetWidth+dis && left>-targetWidth-dis)left = -targetWidth;
						else if(left>suckPw-targetWidth-dis && left<suckPw-targetWidth+dis)left = suckPw - targetWidth;
						else if(left>suckPw-dis && left<suckPw+dis)left = suckPw;
						else if(left<-(parentLeft-dis))left = -parentLeft;
						else if(left>documentPw-targetWidth-dis)left = documentPw - targetWidth;
						if(top<dis && top>-dis)top = 0;
						else if(top<-targetHeight+dis && top>-targetHeight-dis)top = -targetHeight;
						else if(top>suckPh-targetHeight-dis && top<suckPh-targetHeight+dis)top = suckPh - targetHeight;
						else if(top>suckPh-dis && top<suckPh+dis)top = suckPh;
						else if(top<-(parentTop-dis))top = -parentTop;
						else if(top>documentPh-targetHeight-dis)top = documentPh - targetHeight;
						break;
				}
			}
			if(options.useTransform){
				target.css({transform:'translate('+left+'px, '+top+'px)', '-webkit-transform':'translate('+left+'px, '+top+'px)'});
			}else{
				target.css({left:left, top:top});
			}
			if(debug.length){
				let transform = target.transform();
				debug.html('<b>left:</b>'+left+', <b>top:</b>'+top+', <b>right:</b>'+right+', <b>bottom:</b>'+bottom+', <b>dirX:</b>'+dirX+', <b>dirY:</b>'+dirY+', ' +
					'<b>range.left:</b>'+range.left+', <b>range.top:</b>'+range.top+', <b>translateX:</b>'+transform.x+', <b>translateY:</b>'+transform.y);
			}
			//Prevent drag selected
			if(document.selection){ //IE, Opera
				if(document.selection.empty)document.selection.empty(); //IE
				else document.selection = null; //Opera
			}else if(window.getSelection){ //FF, Chrome, Safari
				window.getSelection().removeAllRanges();
			}
		}
		function dragEnd(e){
			if(!started)return true;
			started = false;
			if(e.preventDefault)e.preventDefault();
			if(!moved && $.isFunction(options.click))options.click.call(_this, e);
			target.css({'transition-duration':'', '-webkit-transition-duration':''});
			$(document.body).off('mousemove', dragMove);
			releaseTarget.off('mouseup mouseleave blur', dragEnd);
			if(window.removeEventListener){
				document.body.removeEventListener('touchmove', dragMove, true);
				releaseTarget[0].removeEventListener('touchend', dragEnd, true);
				releaseTarget[0].removeEventListener('touchcancel', dragEnd, true);
			}
			target[0].releaseCapture && target[0].releaseCapture();
			if(Object.keys(options.handleCss).length){
				$.each(options.handleCss, function(k){
					target.css(k, '');
				});
			}
			if(target.data('position') !== 'fixed' && target.data('position') !== 'absolute')target.css('position', target.data('position'));
			let left = 0, top = 0;
			if(options.useTransform){
				left = target.transform().x;
				top = target.transform().y;
			}else{
				left = target.position().left;
				top = target.position().top;
			}
			if($.isFunction(options.stop))options.stop.call(_this, e, {left:left, top:top}, moved);
		}
	});
};

//浮动提示
$.fn.tips = function(options){
	options = $.extend({
		cls: '', //附加类名
		css: {}, //追加样式
		style: '', //追加内嵌样式
		src: '', //图片路径
		type: 'mouseover', //显示模式, [mouseover|click]
		parent: document.body, //插入位置
		width: 0, //图片宽
		height: 0, //图片高
		fixx: 0, //水平偏移量
		fixy: 0, //垂直偏移量
		color: '', //文字颜色
		bgcolor: '', //背景色
		follow: true, //true:跟随鼠标, [(top|0)|(right|1)|(bottom|2)|(left|3)|(auto|4)]:指定位置
		fade: 0, //渐显速度
		skip: null, //跳过不显示
		show: null, //显示浮动元素后执行
		hide: null, //隐藏浮动元素后执行
		callback: null //生成浮动元素后执行
	}, $('body').data('tips.options'), options);
	return this.each(function(){
		let _this = $(this), parent = _this.attr('tips-parent')||options.parent||document.body,
			cls = _this.attr('tips-cls')||options.cls||'', style = _this.attr('tips-style')||options.style||'', type = _this.attr('tips-type')||options.type,
			width = _this.attr('tips-width')||options.width, height = _this.attr('tips-height')||options.height,
			tipsFixX = _this.attr('tips-fixx')||options.fixx||0, tipsFixY = _this.attr('tips-fixy')||options.fixy||0, follow = _this.attr('tips-follow')||options.follow,
			tipsList = _this.data('tips-list')||_this.attr('tips-list')||null, tipsContent = _this.data('tips-content')||_this.attr('tips-content')||null,
			tipsCallback = _this.data('tips-callback')||_this.attr('tips-callback')||options.callback, skip = _this.attr('tips-skip')||options.skip,
			tipsColor = _this.attr('tips-color')||options.color||'', tipsBgColor = _this.attr('tips-bgcolor')||options.bgcolor||'', arrowColor = '', isNumberFollow = false,
			overTimer = null, showTimer = null, follows = {top:0, right:1, bottom:2, left:3, auto:4}, tips = null, isSkip = false;
		if(typeof follow === 'string')follow = follows[follow];
		isNumberFollow = (typeof follow === 'number' && follow>-1 && follow<5);
		parent = (typeof parent === 'string' && parent === 'parent') ? _this.parent() : $(parent);
		if(!!tipsList){
			//tips-list="[{tag:'a', cls:'className', text:'选项1', style:'color:#999;', attr:'data-id=`1` data-name=`name`', icon:'className', callback:'[clickFunction|this.hide]'}, {text:'选项2', url:'/api/home'}, {text:'选项3', list:[{text:'子选项', url:'/api/home'}]}]"
			//tips-list="returnListFunction"
			if(/\b(\w+)\s*:/.test(tipsList) || /\b'(\w+)'\s*:/.test(tipsList)){
				tipsList = tipsList.replace(/\b(\w+)\s*:/g, '"$1":').replace(/\b'(\w+)'\s*:/g, '"$1":').replace(/^\[\s*,/, '[').replace(/,\s*]$/, ']');
				tipsList = tipsList.replace(/:\s*([^[{]+?|'.+?'|".+?"|`.+?`)(\s*,|\s*}|$)/g, function(_$, $1, $2){
					if(!isNaN($1))return _$;
					if(/^"/.test($1))return _$;
					if(/^`(.+)`$/.test($1))return ':"'+$1.replace(/^`(.+)`$/, '$1').replace(/"/g, '\"')+'"'+$2;
					if(/^'(.+)'$/.test($1))return ':"'+$1.replace(/^'(.+)'$/, '$1').replace(/"/g, '\"')+'"'+$2;
					if(/^''$/.test($1))return ':""'+$2;
					return ':"'+$1.replace(/"/g, '\"')+'"'+$2;
				});
				if($.isJsonString(tipsList))tipsList = $.json(tipsList);
			}else if(/^\w+$/.test(tipsList) || /^function/.test(tipsList)){
				let fn = evil(tipsList);
				if($.isFunction(fn))tipsList = fn.call(_this);
			}
			if(!$.isArray(tipsList))tipsList = null;
		}
		if(typeof tipsCallback === 'string')tipsCallback = evil(tipsCallback);
		let mouseover = function(e){
			if(!!_this.data('disableTips'))return true;
			if(typeof skip === 'string' && skip.length)skip = evil(skip);
			if($.isFunction(skip)){
				let res = skip.call(_this, e);
				if(typeof res === 'boolean' && !res){
					isSkip = true;
					return true;
				}
			}
			let src = _this.attr('tips-src')||options.src, title = _this.attr('tips-title')||_this.attr('title')||_this.attr('alt')||_this.data('title')||'', isDark = false, existTips = true;
			if(!src.length && !title.length && !tipsList && !tipsContent)return true;
			tips = _this.data('tips');
			if(isNumberFollow){
				if(overTimer){clearTimeout(overTimer); overTimer = null}
				if(showTimer){clearTimeout(showTimer); showTimer = null}
				if(!!_this.data('tips.mouseover'))return true;
				_this.data('tips.mouseover', true);
			}
			if(!!!tips){
				existTips = false;
				tips = $('<div class="tips-panel"><section></section></div>').addClass(cls);
				if($.isArray(tipsList))tips.addClass('tips-list');
				if(tipsContent)tips.addClass('tips-content');
				parent.append(tips);
				if(tips.css('position') === 'static'){
					tips.css({
						position:'absolute', 'z-index':888, top:-99999, left:-99999, color:'#555', 'border-radius':'3px',
						padding:'5px', 'font-size':'12px', background:'#fff', 'box-shadow':'0 0 5px rgba(0,0,0,0.5)'
					});
				}
				tips.css('pointer-events', 'none');
				_this.data('tips', tips);
			}
			if($.isFunction(tipsCallback))setTimeout(function(){
				tipsCallback.call(tips, _this, existTips);
			}, 100);
			tips.css($.extend({height:'auto'}, options.css));
			if(tipsBgColor.length)tips.css('background-color', tipsBgColor);
			arrowColor = tips.css('background-color');
			if(/^#([0-9a-fA-f]{3}|[0-9a-fA-f]{6})$/.test(arrowColor)){
				if(arrowColor.length === 4){
					let colorNew = '#';
					for(let i=1; i<4; i+=1){
						colorNew += arrowColor.slice(i, i+1).concat(arrowColor.slice(i, i+1));
					}
					arrowColor = colorNew;
				}
				let colorChange = [];
				for(let i=1; i<7; i+=2){
					colorChange.push(parseInt('0x'+arrowColor.slice(i, i+2)));
				}
				arrowColor = 'rgb(' + colorChange.join(',') + ')';
			}
			let RgbValue = arrowColor.replace('rgb(', '').replace(')', '').replace(/, /g, ','), RgbArray = RgbValue.split(','),
				RgbGrayLevel = RgbArray[0] * 0.299 + RgbArray[1] * 0.587 + RgbArray[2] * 0.114, RgbOpacity = RgbArray.length>3 ? Number(RgbArray[3]) : 1;
			if(RgbGrayLevel < 192 && RgbOpacity > 0.7)isDark = true;
			if(isDark)tips.addClass('tips-dark').css({color:'#fff'});//, 'box-shadow':'none'
			if(tipsColor.length)tips.css('color', tipsColor);
			if(style.length)tips[0].style.cssText += ';'+style;
			let section = tips.children('section').css('display', 'block');
			if($.isArray(tipsList)){
				if(!existTips){
					_this.data('title', true);
					tips.css('pointer-events', 'auto');
					if(type !== 'click'){
						tips.on('mouseover', function(){
							if(isNumberFollow){
								if(overTimer){clearTimeout(overTimer); overTimer = null}
								_this.data('tips.mouseover', true);
							}
						}).on('mouseout', function(){
							_this.data('tips.prev.mouseout')();
						});
					}else{
						$.registControl({
							menu: _this,
							partner: tips,
							outside: function(){
								_this.data('tips.prev.mouseout')();
							}
						});
					}
					section.html('');
					let listRecursive = function(p, item){
						let tag = typeof item.tag !== 'undefined' ? item.tag : 'a', callback = item.callback;
						let list = $('<'+tag+(tag==='a'?' href="'+(item.url?item.url:'javascript:void(0)')+'"':'')+(item.cls?' class="'+item.cls+'"':'')+(item.style?' style="'+item.style+'"':'')+' '+(item.attr?item.attr.replace(/`/g, '"'):'')+'>'+(item.icon?'<i class="'+item.icon+'"></i>':'')+(item.text?item.text.replace(/`/g, '"'):'')+'</'+tag+'>');
						p.append(list);
						if(typeof item.list !== 'undefined' && $.isArray(item.list)){
							let aside = $('<aside class="tips-list"><section></section></aside>').css({
								position:'absolute', 'z-index':888, top:-99999, color:'#555', 'border-radius':'3px', 'white-space':'nowrap',
								display:'none', padding:'5px', margin:'0', 'font-size':'12px', background:'#fff', 'box-shadow':'0 0 5px rgba(0,0,0,0.5)'
							});
							if(tipsBgColor.length)aside.css('background-color', tipsBgColor);
							if(isDark)aside.addClass('tips-dark').css({color:'#fff'});
							if(tipsColor.length)aside.css('color', tipsColor);
							if(style.length)aside[0].style.cssText += ';'+style;
							list.addClass('tips-option').append(aside);
							list.on('mouseover', function(){
								aside.css('display', 'block');
								setTimeout(function(){
									let listWidth = list.outerWidth(false), listHeight = list.outerHeight(false), asideWidth = aside.outerWidth(false), asideHeight = aside.outerHeight(false),
										left = listWidth, top = 0, win = $.window();
									if(list.offset().left + listWidth + asideWidth > win.width)left = -asideWidth;
									if(list.offset().top + asideHeight > win.scrollTop + win.height)top = listHeight - asideHeight;
									aside.css({left:left, top:top});
								}, 10);
							}).on('mouseout', function(){
								aside.css('display', 'none');
							});
							let children = aside.children('section').css('white-space', 'nowrap');
							$.each(item.list, function(){
								listRecursive(children, this);
							});
						}
						if(callback){
							if($.isFunction(callback)){
								list.on('click', function(){
									let res = callback.call(this, e, item);
									if(typeof res === 'boolean')return res;
								});
							}else if(callback.length){
								if(callback === 'this.hide'){
									list.on('click', function(){
										_this.data('tips.prev.mouseout')();
									});
								}else{
									let fn = evil(callback);
									if($.isFunction(fn))list.on('click', function(e){
										let res = fn.call(this, e, item);
										if(typeof res === 'boolean')return res;
									});
								}
							}
						}
					};
					$.each(tipsList, function(){
						listRecursive(section, this);
					});
					if(!!width){
						section.css('width', width);
					}else{
						section.css('white-space', 'nowrap');
						if(tips.outerWidth(false)>$.window().width/2){
							section.css({width:$.window().width/2, 'word-break':'break-all', 'white-space':'pre-wrap'});
						}
					}
					if(!!height)section.css('height', height);
				}
			}else if(tipsContent){
				if(!section.html().length){
					_this.data('title', true);
					let content = tipsContent;
					if(/^\w+$/.test(content) || /^function/.test(content)){
						let fn = evil(content);
						if($.isFunction(fn))content = fn.call(_this, tips);
					}
					tips.css('pointer-events', 'auto');
					section.html(content);
					if(type === 'click'){
						$.registControl({
							menu: _this,
							partner: tips,
							outside: function(){
								_this.data('tips.prev.mouseout')();
							}
						});
					}
				}
			}else if(src.length){
				let image = $('<img src="'+src+'" border="0" />');
				image.css('vertical-align', 'bottom');
				if(!!width)image.css('width', width);
				if(!!height)image.css('height', height);
				if(!!_this.attr('max-width'))image.css('max-width', Number(_this.attr('max-width')));
				if(!!_this.attr('max-height'))image.css('max-height', Number(_this.attr('max-height')));
				section.html(image);
			}else{
				section.html(title);
				if(!!width){
					section.css('width', width);
				}else{
					section.css('white-space', 'nowrap');
					if(tips.outerWidth(false)>$.window().width/2){
						section.css({width:$.window().width/2, 'word-break':'break-all', 'white-space':'pre-wrap'});
					}
				}
				if(!!height)section.css('height', height);
			}
			let fn = _this.attr('fn');
			if(!!fn){
				let func = evil(fn);
				if($.isFunction(func))func.call(_this, tips);
			}
			if(title.length)_this.data('title', title).removeAttr('title').removeAttr('alt');
			if(isNumberFollow){
				tips.children('em').remove();
				tips.css({opacity:0, display:'block'}).prepend('<em></em>');
				let offset = parent.is(document.body) ? _this.offset() : _this.position(), _thisWidth = _this.outerWidth(false), _thisHeight = _this.outerHeight(false),
					tipsWidth = tips.outerWidth(false), tipsHeight = tips.outerHeight(false),
					em = tips.children('em').css({
						position:'absolute', 'z-index':1, 'background-color':'transparent', width:0, height:0, overflow:'hidden',
						'border-style':'solid', 'border-width':'3px', 'border-color':arrowColor+' '+arrowColor+' transparent transparent', 'box-shadow':'2px -2px 3px rgba(0,0,0,0.2)'
					});
				if(isDark)em.css({'box-shadow':'none'});
				switch(follow){
					case 0:
						tips.css({left:offset.left+(_thisWidth/2-tipsWidth/2)+Number(tipsFixX), top:offset.top-tipsHeight-6+Number(tipsFixY),
							'-webkit-transition':'-webkit-transform 0.3s ease-out, opacity 0.3s ease-out',
							'transition':'transform 0.3s ease-out, opacity 0.3s ease-out',
							'-webkit-transform':'translateY(-10px)', 'transform':'translateY(-10px)'});
						em.css({bottom:-3, left:'50%', '-webkit-transform':'translateX(-50%) rotate(135deg)', 'transform':'translateX(-50%) rotate(135deg)'});
						setTimeout(function(){tips.css({'-webkit-transform':'translateY(0)', 'transform':'translateY(0)', opacity:1})}, 100);
						break;
					case 1:
						tips.css({left:offset.left+_thisWidth+6+Number(tipsFixX), top:offset.top+(_thisHeight/2-tipsHeight/2)+Number(tipsFixY),
							'-webkit-transition':'-webkit-transform 0.3s ease-out, opacity 0.3s ease-out',
							'transition':'transform 0.3s ease-out, opacity 0.3s ease-out',
							'-webkit-transform':'translateX(10px)', 'transform':'translateX(10px)'});
						em.css({left:-3, top:'50%', '-webkit-transform':'translateY(-50%) rotate(-135deg)', 'transform':'translateY(-50%) rotate(-135deg)'});
						setTimeout(function(){tips.css({'-webkit-transform':'translateX(0)', 'transform':'translateX(0)', opacity:1})}, 100);
						break;
					case 2:
						tips.css({left:offset.left+(_thisWidth/2-tipsWidth/2)+Number(tipsFixX), top:offset.top+_thisHeight+6+Number(tipsFixY),
							'-webkit-transition':'-webkit-transform 0.3s ease-out, opacity 0.3s ease-out',
							'transition':'transform 0.3s ease-out, opacity 0.3s ease-out',
							'-webkit-transform':'translateY(10px)', 'transform':'translateY(10px)'});
						em.css({top:-3, left:'50%', '-webkit-transform':'translateX(-50%) rotate(-45deg)', 'transform':'translateX(-50%) rotate(-45deg)'});
						setTimeout(function(){tips.css({'-webkit-transform':'translateY(0)', 'transform':'translateY(0)', opacity:1})}, 100);
						break;
					case 3:
						tips.css({left:offset.left-tipsWidth-6+Number(tipsFixX), top:offset.top+(_thisHeight/2-tipsHeight/2)+Number(tipsFixY),
							'-webkit-transition':'-webkit-transform 0.3s ease-out, opacity 0.3s ease-out',
							'transition':'transform 0.3s ease-out, opacity 0.3s ease-out',
							'-webkit-transform':'translateX(-10px)', 'transform':'translateX(-10px)'});
						em.css({right:-3, top:'50%', '-webkit-transform':'translateY(-50%) rotate(45deg)', 'transform':'translateY(-50%) rotate(45deg)'});
						setTimeout(function(){tips.css({'-webkit-transform':'translateX(0)', 'transform':'translateX(0)', opacity:1})}, 100);
						break;
					case 4:
						if(offset.top+_thisHeight+6+tipsHeight+Number(tipsFixY)>$.window().height){
							if(offset.left+(_thisWidth/2-tipsWidth/2)+tipsWidth+Number(tipsFixX)>$.window().width){
								tips.data('tips-position', 'left').css({left:offset.left-tipsWidth-6+Number(tipsFixX), top:offset.top+(_thisHeight/2-tipsHeight/2)+Number(tipsFixY),
									'-webkit-transition':'-webkit-transform 0.3s ease-out, opacity 0.3s ease-out',
									'transition':'transform 0.3s ease-out, opacity 0.3s ease-out',
									'-webkit-transform':'translateX(-10px)', 'transform':'translateX(-10px)'});
								em.css({right:-3, top:'50%', '-webkit-transform':'translateY(-50%) rotate(45deg)', 'transform':'translateY(-50%) rotate(45deg)'});
								setTimeout(function(){tips.css({'-webkit-transform':'translateX(0)', 'transform':'translateX(0)', opacity:1})}, 100);
							}else{
								tips.data('tips-position', 'top').css({left:offset.left+(_thisWidth/2-tipsWidth/2)+Number(tipsFixX), top:offset.top-tipsHeight-6+Number(tipsFixY),
									'-webkit-transition':'-webkit-transform 0.3s ease-out, opacity 0.3s ease-out',
									'transition':'transform 0.3s ease-out, opacity 0.3s ease-out',
									'-webkit-transform':'translateY(-10px)', 'transform':'translateY(-10px)'});
								em.css({bottom:-3, left:'50%', '-webkit-transform':'translateX(-50%) rotate(135deg)', 'transform':'translateX(-50%) rotate(135deg)'});
								setTimeout(function(){tips.css({'-webkit-transform':'translateY(0)', 'transform':'translateY(0)', opacity:1})}, 100);
							}
						}else{
							if(offset.left+(_thisWidth/2-tipsWidth/2)+tipsWidth+Number(tipsFixX)>$.window().width){
								let scrollTop = $(document.body).scrollTop(), offsetTop = offset.top+(_thisHeight/2-tipsHeight/2)+Number(tipsFixY), emTop = '50%';
								if(offsetTop < scrollTop){
									offsetTop = scrollTop+6+Number(tipsFixY);
									emTop = offset.top+_thisHeight/2-offsetTop;
								}
								tips.data('tips-position', 'left').css({left:offset.left-tipsWidth-6+Number(tipsFixX), top:offsetTop,
									'-webkit-transition':'-webkit-transform 0.3s ease-out, opacity 0.3s ease-out',
									'transition':'transform 0.3s ease-out, opacity 0.3s ease-out',
									'-webkit-transform':'translateX(-10px)', 'transform':'translateX(-10px)'});
								em.css({right:-3, top:emTop, '-webkit-transform':'translateY(-50%) rotate(45deg)', 'transform':'translateY(-50%) rotate(45deg)'});
								setTimeout(function(){tips.css({'-webkit-transform':'translateX(0)', 'transform':'translateX(0)', opacity:1})}, 100);
							}else{
								tips.data('tips-position', 'bottom').css({left:offset.left+(_thisWidth/2-tipsWidth/2)+Number(tipsFixX), top:offset.top+_thisHeight+6+Number(tipsFixY),
									'-webkit-transition':'-webkit-transform 0.3s ease-out, opacity 0.3s ease-out',
									'transition':'transform 0.3s ease-out, opacity 0.3s ease-out',
									'-webkit-transform':'translateY(10px)', 'transform':'translateY(10px)'});
								em.css({top:-3, left:'50%', '-webkit-transform':'translateX(-50%) rotate(-45deg)', 'transform':'translateX(-50%) rotate(-45deg)'});
								setTimeout(function(){tips.css({'-webkit-transform':'translateY(0)', 'transform':'translateY(0)', opacity:1})}, 100);
							}
						}
						break;
				}
				if($.isFunction(options.show))setTimeout(function(){options.show.call(tips, _this)}, 300);
			}else{
				if(!follow)followTips(e);
				if(options.fade>0){
					tips.fadeIn(options.fade);
					if($.isFunction(options.show))setTimeout(function(){options.show.call(tips, _this)}, options.fade);
				}else{
					tips.css('display', 'block');
					if($.isFunction(options.show))options.show.call(tips, _this);
				}
			}
		},
		mouseout = function(){
			if(isSkip){
				isSkip = false;
				return true;
			}
			if(!!_this.data('disableTips'))return true;
			if(!!!_this.data('title') || !tips)return true;
			if(isNumberFollow){
				overTimer = setTimeout(function(){
					if(overTimer){clearTimeout(overTimer); overTimer = null}
					_this.removeData('tips.mouseover');
					switch(follow){
						case 0:
							tips.css({'-webkit-transform':'translateY(-10px)', 'transform':'translateY(-10px)', opacity:0});
							break;
						case 1:
							tips.css({'-webkit-transform':'translateX(10px)', 'transform':'translateX(10px)', opacity:0});
							break;
						case 2:
							tips.css({'-webkit-transform':'translateY(10px)', 'transform':'translateY(10px)', opacity:0});
							break;
						case 3:
							tips.css({'-webkit-transform':'translateX(-10px)', 'transform':'translateX(-10px)', opacity:0});
							break;
						case 4:
							if(tips.data('tips-position')==='left'){
								tips.css({'-webkit-transform':'translateX(-10px)', 'transform':'translateX(-10px)', opacity:0});
							}else if(tips.data('tips-position')==='top'){
								tips.css({'-webkit-transform':'translateY(-10px)', 'transform':'translateY(-10px)', opacity:0});
							}else if(tips.data('tips-position')==='bottom'){
								tips.css({'-webkit-transform':'translateY(10px)', 'transform':'translateY(10px)', opacity:0});
							}
							break;
					}
					showTimer = setTimeout(function(){
						tips.css('display', 'none');
						if($.isFunction(options.hide))options.hide.call(tips, _this);
					}, 300);
				}, 100);
			}else{
				if(options.fade>0){
					tips.fadeOut(options.fade);
					if($.isFunction(options.hide))setTimeout(function(){options.hide.call(tips, _this)}, options.fade);
				}else{
					tips.css('display', 'none');
					if($.isFunction(options.hide))options.hide.call(tips, _this);
				}
			}
		},
		mousemove = function(e){
			if(isSkip){
				isSkip = false;
				return true;
			}
			if(!!_this.data('disableTips'))return true;
			if(!!!_this.data('title') || !tips)return true;
			followTips(e);
			return true;
		};
		if(type === 'mouseover'){
			_this.off('mouseover', _this.data('tips.prev.mouseover'))
			.off('mousemove', _this.data('tips.prev.mousemove'))
			.off('mouseout', _this.data('tips.prev.mouseout'));
		}else if(type === 'click'){
			_this.off('click', _this.data('tips.prev.mouseover'));
		}
		_this.data({
			'tips.prev.mouseover': mouseover,
			'tips.prev.mousemove': mousemove,
			'tips.prev.mouseout': mouseout
		});
		if(type === 'mouseover'){
			_this.on('mouseover', _this.data('tips.prev.mouseover')).on('mouseout', _this.data('tips.prev.mouseout'));
			if(typeof follow === 'boolean' && follow)_this.on('mousemove', _this.data('tips.prev.mousemove'));
		}else if(type === 'click'){
			_this.on('click', _this.data('tips.prev.mouseover'));
		}
		function followTips(e){
			let doc = $.document(), sl = $.scroll().left, st = $.scroll().top, x = e.clientX, y = e.clientY, mx = x+10+sl+Number(tipsFixX), my = y+10+st+Number(tipsFixY);
			if((x+10+tips.outerWidth(false)+Number(tipsFixX))>doc.clientWidth)mx = x-tips.outerWidth(false)+sl-Number(tipsFixX);
			if((y+10+tips.outerHeight(false)+Number(tipsFixY))>doc.clientHeight)my = y-tips.outerHeight(false)+st-Number(tipsFixY);
			tips.css({left:mx, top:my});
			if($.browser.ie6){
				let l1 = mx, t1 = my, l2 = mx+tips.outerWidth(false), t2 = my+tips.outerHeight(false);
				$('select').each(function(){
					let _select = $(this), x = _select.position().left, y = _select.position().top, w = _select.width(), h = _select.height();
					if(l2>=x && x+w>=l1 && t2>=y && y+h>=t1){
						_select.css('visibility', 'hidden');
						_select.data('visibility', true);
					}else{
						if(!!_select.data('visibility')){
							_select.css('visibility', '');
							_select.removeData('visibility', true);
						}
					}
				});
			}
			/*x: 设置或得到鼠标相对于目标事件的父元素的外边界在x坐标上的位置
			clientX: 相对于客户区域的x坐标位置, 不包括滚动条
			offsetX: 设置或得到鼠标相对于目标事件的父元素的内边界在x坐标上的位置
			screenX: 相对于屏幕*/
		}
	});
};

//获取选中的radio或checkbox/选中指定值的radio或checkbox(val:[字符|数字(索引选中)|数组|有返回值的函数])(isTrigger:自动执行change操作,默认true)
$.fn.checked = function(val, isTrigger){
	if(typeof val === 'undefined'){
		if(!this.length)return $([]);
		let name = this.attr('name');
		if(!!!name)name = this.attr('id');
		if(!!!name)return $([]);
		let box = this.parents('body').find('[name="'+name.replace(/\[]/,'\\[\\]')+'"]:checked');
		if(!box.length)box = _this.parents('body').find('[id="'+name.replace(/\[]/,'\\[\\]')+'"]:checked');
		if(!box.length)box = _this.parents('body').find('[id="'+name.replace(/\[]/,'\\[\\]')+'"][checked]');
		return box;
	}else{
		if(typeof isTrigger === 'undefined')isTrigger = true;
		if(val === null || (typeof val === 'string' && !val.length))return this;
		return this.each(function(){
			let _this = $(this), vals = [];
			let name = _this.attr('name');
			if(!!!name)name = _this.attr('id');
			//if(!!!name)return true;
			if($.isFunction(val)){
				let s = val.call(_this);
				$.isArray(s) ? vals = s : vals.push(s);
			}else{
				$.isArray(val) ? vals = val : vals.push(val);
			}
			let box = [];
			if(!!name){
				box = _this.parents('body').find('[name="'+name.replace(/\[]/,'\\[\\]')+'"]');
				if(!box.length)box = _this.parents('body').find('[id="'+name.replace(/\[]/,'\\[\\]')+'"]');
			}
			if(!box.length)box = _this;
			box.prop('checked', false);
			$.each(vals, function(i, v){
				if(typeof v === 'number'){
					box.filter(':eq('+v+')').prop('checked', true);
				}else if(typeof v === 'string'){
					box.filter('[value="'+v.replace(/"/g,'\"')+'"]').prop('checked', true);
				}else if(typeof v === 'boolean'){
					if(v)box.prop('checked', true);
					else box.prop('checked', false);
				}
			});
			if(isTrigger)box.trigger('change');
		});
	}
};

//获取选中的option/选中指定值的option(val:[字符|数字(索引选中)|数组|有返回值的函数])(isTrigger:自动执行change操作,默认true)
$.fn.selected = function(val, isTrigger){
	if(typeof val === 'undefined' || val === null || (typeof val === 'string' && !val.length)){
		if(!this.find('option').length)return $([]);
		let option = this.find('option:selected');
		if(!option.length)option = this.find('option[selected]');
		if(!option.length)option = this.find('option:eq(0)');
		return option;
	}else{
		if(typeof isTrigger === 'undefined')isTrigger = true;
		return this.each(function(){
			let _this = $(this), multiple = _this.is('[multiple]'), vals = [];
			if($.isFunction(val)){
				let s = val.call(_this);
				$.isArray(s) ? vals = s : vals.push(s);
			}else{
				$.isArray(val) ? vals = val : vals.push(val);
			}
			$.each(vals, function(i, v){
				if(!multiple)_this.find('option').prop('selected', false);
				if(typeof v === 'number'){
					_this.find('option:eq('+v+')').prop('selected', true);
				}else if(typeof v === 'string'){
					_this.find('option[value="'+v.replace(/"/g,'\"')+'"]').prop('selected', true);
				}
			});
			if(isTrigger)_this.trigger('change');
		});
	}
};

//获取填充
$.fn.padding = function(){
	if(!this.length)return {top:0, left:0, right:0, bottom:0};
	let top = $.unit(this.css('padding-top')), left = $.unit(this.css('padding-left')),
	right = $.unit(this.css('padding-right')), bottom = $.unit(this.css('padding-bottom'));
	return {top:top, left:left, right:right, bottom:bottom};
};

//获取间距
$.fn.margin = function(){
	if(!this.length)return {top:0, left:0, right:0, bottom:0};
	let top = $.unit(this.css('margin-top')), left = $.unit(this.css('margin-left')),
	right = $.unit(this.css('margin-right')), bottom = $.unit(this.css('margin-bottom'));
	return {top:top, left:left, right:right, bottom:bottom};
};

//获取边宽
$.fn.border = function(){
	if(!this.length)return {top:0, left:0, right:0, bottom:0};
	let top = $.unit(this.css('border-top-width')), left = $.unit(this.css('border-left-width')),
	right = $.unit(this.css('border-right-width')), bottom = $.unit(this.css('border-bottom-width'));
	return {top:top, left:left, right:right, bottom:bottom};
};

//获取transform
$.fn.transform = function(property){
	if(typeof property === 'undefined'){
		let x = 0, y = 0, matcher;
		if(this.css('transform') !== 'none'){
			matcher = this.css('transform').match(/matrix\(-?\d+[.\d+]*, -?\d+[.\d+]*, -?\d+[.\d+]*, -?\d+[.\d+]*, (-?\d+(?:\.\d+)?), (-?\d+(?:\.\d+)?)\)/);
			if($.isArray(matcher)){
				x = Number(matcher[1]);
				y = Number(matcher[2]);
			}
		}
		return {x:x, y:y};
	}else{
		return this.each(function(){
			let _this = $(this);
			_this.css({'-webkit-transform':property, 'transform':property});
			/*
			if($.browser.chrome || $.browser.safari){
				//if($.browser.android && property.indexOf('scale')>-1){
				_this.css({'-webkit-transform':property});
			}else{
				_this.css({'transform':property});
			}
			*/
		});
	}
};

//移动端禁止内容区域滚到顶/底后引起页面整体的滚动, remove取消禁止
$.fn.stopBounces = function(remove){
	return this.each(function(){
		let _this = $(this), startX, startY,
		start = function(e){
			startX = $.touches(e).x;
			startY = $.touches(e).y;
		},
		move = function(e){
			//高位表示向上滚动, 底位表示向下滚动, 1容许 0禁止
			let status = '11', ele = this, currentX = $.touches(e).x, currentY = $.touches(e).y;
			if(currentX-startX>=8 || currentX-startX<=-8){e.preventDefault();return false}
			if(ele.scrollTop === 0){ //如果内容小于容器则同时禁止上下滚动
				status = ele.offsetHeight >= ele.scrollHeight ? '00' : '01';
			}else if(ele.scrollTop + ele.offsetHeight >= ele.scrollHeight){ //已经滚到底部了只能向上滚动
				status = '10';
			}
			if(status !== '11'){
				let direction = currentY - startY > 0 ? '10' : '01'; //判断当前的滚动方向
				//操作方向和当前允许状态求与运算, 运算结果为0, 就说明不允许该方向滚动, 则禁止默认事件, 阻止滚动
				if(!(parseInt(status, 2) & parseInt(direction, 2)))e.preventDefault();
			}
		};
		if(remove){
			_this.removeData('stopBounces');
			this.removeEventListener('touchstart', start, true);
			this.removeEventListener('touchmove', move, true);
			return;
		}
		if(!$.browser.mobile || !!_this.data('stopBounces') || !!_this.data('drag') || !!_this.data('dragshow') || !!_this.data('touchmove') || !!_this.data('pullRefresh'))return;
		_this.data('stopBounces', true);
		this.addEventListener('touchstart', start, true);
		this.addEventListener('touchmove', move, true);
	});
};

//scroll开始时执行
$.fn.scrollstart = function(callback){
	if(!$.isFunction(callback))return this;
	return this.each(function(){
		let _this = $(this);
		_this.on('scroll', function(e){
			if(!!_this.data('scrollstart'))return;
			_this.data('scrollstart', true);
			callback.call(_this[0], e);
		});
	});
};

//scroll停止时执行
$.fn.scrollstop = function(callback){
	if(!$.isFunction(callback))return this;
	return this.each(function(){
		let _this = $(this), timer = null,
		touchstart = function(){_this.data('scrollstop', true)},
		touchend = function(e){
			_this.removeData('scrollstop');
			if(!!!_this.data('skip-scrollstop.outside'))scroll(e);
		}, scroll = function(e){
			if(!!_this.data('skip-scrollstop'))return true;
			if(timer){clearTimeout(timer);timer = null}
			if(!!_this.data('scrollstop'))return true;
			timer = setTimeout(function(){
				clearTimeout(timer);timer = null;
				_this.removeData('scrollstart').removeData('scrollstop');
				callback.call(_this[0], e);
			}, 300);
		};
		_this.on('touchstart', touchstart).on('touchend', touchend).on('scroll', scroll);
	});
};

//绑定点击document.body执行操作
$.fn.control = function(options){
	if(!$.isPlainObject(options)){
		return this.each(function(){
			let _this = $(this);
			if($.isFunction(_this.data('hide')))_this.data('hide').call(_this);
		});
	}
	options = $.extend({
		expr: '', //点击不会隐藏的对象
		type: 'click', //执行show操作的触发动作
		before: null, //可执行show操作的条件函数,返回false不执行show操作
		show: null, //显示后执行
		hide: null //关闭后执行
	}, $('body').data('control.options'), options);
	let _ths = this;
	function operateControlReg(e){
		let o = $.etarget(e);
		do{
			if($(o).is(_ths) || (options.expr.length && $(o).is(options.expr)))return;
			if((/^(html|body)$/i).test(o.tagName)){operateControl();return}
			o = o.parentNode;
		}while(o.parentNode);
	}
	function operateControl(){
		let _this = $(document).data('control.element');
		if($.isFunction(options.hide))options.hide.call(_this);
		$(document).removeData('control.element');
		$(document).off('click', operateControlReg);
	}
	return this.each(function(){
		let _this = $(this);
		if(!!_this.data('control'))return true;
		_this.data('control', true).data('hide', options.hide);
		_this.on(options.type, function(e){
			if($.isFunction(options.before)){
				let result = options.before.call(_this, e);
				if(typeof(result) === 'boolean' && !result)return;
			}
			$(document).on('click', operateControlReg);
			if(!_this.is($(document).data('control.element'))){
				$(document).data('control.element', _this);
				if($.isFunction(options.show))options.show.call(_this);
			}else{
				let o = $.etarget(e);
				do{
					if(options.expr.length && $(o).is(options.expr))return;
					if($(o).is(_this)){operateControl();return}
					o = o.parentNode;
				}while(o.parentNode);
			}
		});
	});
};

//获取outerHTML
$.fn.outerHTML = function(){
	return this.prop('outerHTML');
};

//不能选中
$.fn.unselect = function(flag){
	if(typeof flag !== 'undefined' && !flag){
		return this.attr('unselectable', '').css({'-webkit-user-select':'', 'user-select':''}).off('selectstart', this.data('unselect'));
	}else{
		this.data('unselect', function(){ return false });
		return this.attr('unselectable', 'on').css({'-webkit-user-select':'none', 'user-select':'none'}).on('selectstart', this.data('unselect'));
	}
};

//获取按键的code
$.fn.onkey = function(options){
	if(typeof options === 'boolean' && !options){
		return this.removeData('onkey').off('keydown', function(e){
			return keydown.call(this, e);
		});
	}else if($.isFunction(options)){
		options = {
			meta: false, //macOS command key
			ctrl: false,
			alt: false,
			shift: false,
			callback: options
		};
	}else{
		options = $.extend({
			meta: false,
			ctrl: false,
			alt: false,
			shift: false,
			callback: null //接受三个参数:按键的code,key,e
		}, options);
	}
	function keydown(e){
		let code = e.which||e.keyCode, key = e.key, _this = $(this), passDown = true;
		if(!$.isFunction(options.callback)){alert(code);return false}
		if(options.meta && !e.metaKey)passDown = false;
		if(options.ctrl && !e.ctrlKey)passDown = false;
		if(options.alt && !e.altKey)passDown = false;
		if(options.shift && !e.shiftKey)passDown = false;
		e.meta = e.metaKey; e.ctrl = e.ctrlKey; e.alt = e.altKey; e.shift = e.shiftKey;
		let result = null;
		if(passDown)result = options.callback.call(this, code, key, e);
		if(typeof result === 'boolean' && !result){
			setTimeout(function(){_this.val(_this.data('onkey.input-value'))}, 0);
			if(e.preventDefault)e.preventDefault();
			e.returnValue = false;
			return false;
		}
		setTimeout(function(){_this.data('onkey.input-value', _this.val())}, 0);
		return true;
	}
	return this.each(function(){
		let _this = $(this);
		if(_this.data('onkey'))return true;
		_this.data('onkey', true).on('keydown', function(e){
			return keydown.call(this, e);
		});
	});
};

//解决拖曳与点击冲突
$.fn.tapper = function(fn){
	/*
	//模拟点击
	return this.each(function(){
		if($.browser.msie){this.click()}
		else{
			let e = document.createEvent('MouseEvent');
			e.initEvent('click', true, true);
			this.dispatchEvent(e);
		}
	});
	//新建链接并点击
	setTimeout(function(){
		let a = document.createElement('a');
		a.href = href;a.rel = 'noreferrer';a.click();
	}, 1);
	*/
	let isTouch = 'ontouchend' in document.createElement('div'),
		start = isTouch ? 'touchstart' : 'mousedown',
		move = isTouch ? 'touchmove' : 'mousemove',
		end = isTouch ? 'touchend' : 'mouseup',
		cancel = isTouch ? 'touchcancel' : 'mouseout',
		doc = $(document.body);
	if(typeof fn === 'undefined'){
		return this.trigger(start).trigger(end);
	}
	if(typeof fn === 'boolean' && !fn){
		doc.off(move, this.data('tapper.move'));
		return this.off(start, this.data('tapper.start')).off(end, this.data('tapper.end')).off(cancel, this.data('tapper.end'));
	}
	return this.each(function(){
		let _this = $(this), i = {target:this};
		function onStart(e){
			let p = $.browser.mobile ? ((('touches' in e) && e.touches) ? e.touches[0] : (isTouch ? window.event.touches[0] : window.event)) : e;
			i.startX = p.clientX || 0;
			i.startY = p.clientY || 0;
			i.endX = p.clientX || 0;
			i.endY = p.clientY || 0;
			i.startTime = + new Date;
			doc.on(move, onMove);
		}
		function onMove(e){
			let p = $.browser.mobile ? ((('touches' in e) && e.touches) ? e.touches[0] : (isTouch ? window.event.touches[0] : window.event)) : e;
			i.endX = p.clientX;
			i.endY = p.clientY;
		}
		function onEnd(e){
			doc.off(move, onMove);
			if((+ new Date) - i.startTime < 300){
				if(Math.abs(i.endX-i.startX) + Math.abs(i.endY-i.startY) < 20){
					e = e || window.event;
					e.preventDefault();
					fn = _this.data('tapper.fn');
					let res = fn.call(i.target, e);
					if(typeof res === 'boolean')return res;
				}
			}
			i = {target:i.target};
		}
		_this.on(start, onStart).on(end, onEnd).on(cancel, onEnd).data({'tapper.fn':fn, 'tapper.start':onStart, 'tapper.move':onMove, 'tapper.end':onEnd});
	});
};

//兼容移动端点击事件
$.fn.onclick = function(fn){
	if(typeof fn === 'boolean' && !fn){
		if(!$.browser.mobile)this.off(window.eventType, this.data('onclick'));
		else{
			this.off('touchstart', this.data('ontouchend-touchstart'));
			this.off('touchend', this.data('ontouchend-touchend'));
			this.off('touchcancel', this.data('ontouchend-touchcancel'));
		}
		return this;
	}
	return this.each(function(){
		let _this = $(this).data('onclick', fn);
		if(!$.browser.mobile)_this.on(window.eventType, fn);
		else _this.data('hasClick', true).ontouchend(fn);
	});
};

//兼容移动端双击事件
$.fn.ondblclick = function(fn){
	if(typeof fn === 'boolean' && !fn){
		if(!$.browser.mobile)this.off('dblclick', this.data('ondblclick'));
		else{
			this.off('touchstart', this.data('ontouchend-touchstart'));
			this.off('touchend', this.data('ontouchend-touchend'));
			this.off('touchcancel', this.data('ontouchend-touchcancel'));
		}
		return this;
	}
	return this.each(function(){
		let _this = $(this).data('ondblclick', fn);
		if(!$.browser.mobile)_this.on('dblclick', fn);
		else _this.data('hasClick', true).ontouchend(fn, true);
	});
};

//执行touchend前判断是否拖曳过
$.fn.ontouchend = function(callback, dblclick){
	if(!!this.data('touchend') || !this.length)return this;
	if(!dblclick)this.data('ontouchend-click', callback);
	else this.data('ontouchend-dblclick', callback);
	return this.each(function(){
		$(this).data('ontouchend', true);
		let _this = $(this), moved = false, startX = 0, startY = 0, touchTime = new Date().getTime(), timer = null,
		touchstart = function(e){
			moved = false;
			startX = $.touches(e).x;
			startY = $.touches(e).y;
			_this.on('touchmove', touchmove);
		},
		touchmove = function(e){
			if(Math.abs($.touches(e).x - startX)>10 || Math.abs($.touches(e).y - startY)>10){
				moved = true;
			}
		},
		touchend = function(e){
			e.preventDefault();
			_this.off('touchmove', touchmove);
			if(!moved){
				if(new Date().getTime() - touchTime < 500){
					if(timer){clearTimeout(timer);timer = null}
					if($.isFunction(_this.data('ontouchend-dblclick')))_this.data('ontouchend-dblclick').call(_this[0], e);
				}else{
					touchTime = new Date().getTime();
					timer = setTimeout(function(){
						if($.isFunction(_this.data('ontouchend-click')))_this.data('ontouchend-click').call(_this[0], e);
					}, 300);
				}
			}
		},
		touchcancel = function(){
			if(timer){clearTimeout(timer);timer = null}
			moved = false;
			startX = 0;
			startY = 0;
			_this.off('touchmove', touchmove);
		};
		_this.data('ontouchend-touchstart', touchstart).on('touchstart', touchstart);
		_this.data('ontouchend-touchend', touchend).on('touchend', touchend);
		_this.data('ontouchend-touchcancel', touchcancel).on('touchcancel', touchcancel);
	});
};

//解除插件
$.fn.removePlug = function(dataName){
	if(!!this.data(dataName+'-plug')){
		this.find('*').off().remove();
		let html = this.data(dataName+'-html');
		this.empty().html('').html(html?html:'');
		return true;
	}else{
		this.data(dataName+'-plug', true);
		this.data(dataName+'-html', this.html());
		return false;
	}
};

//选项卡头
$.fn.switchView = function(options){
	options = $.extend({
		list: 'li', //列表
		index: 0, //默认选中
		cls: 'this', //选中的样式
		column: '', //底部滚动条样式, 为空即不显示滚动条
		autoWidth: true, //没有产生滚动时自动设置每个选项卡宽度以达到沾满一行
		selectFn: null //选中后执行, 设置后选项卡头里面的所有a连接将return false, this指向列表的每个a链接
	}, $('body').data('switchView.options'), options);
	return this.each(function(){
		$(this).removePlug('switchView');
		let _this = $(this), width = _this.width(), height = _this.height(), list = options.list?_this.find(options.list):_this.find('li'),
			index = options.index<0?0:(options.index>list.length-1?list.length-1:options.index), totalWidth = 0, wrap = null, div = null;
		if(!list.length)return true;
		wrap = list.wrapAll('<div></div>').parent().css({position:'relative', height:'100%', 'float':'left'});
		div = wrap.wrap('<div></div>').parent().css({width:'100%', height:'100%', overflow:'auto', '-webkit-overflow-scrolling':'touch'});
		setTimeout(function(){
			list.each(function(){totalWidth += Math.ceil($(this).outerWidth(true)+0.5)});
			if(totalWidth<=width){
				if(options.autoWidth){
					let padding = list.padding(), w = width / list.length - padding.left - padding.right;
					list.width(w);
				}
				totalWidth = '100%';
			}else{
				let left = $('<div class="left"></div>').height(height), right = $('<div class="right"></div>').height(height);
				_this.append(left).append(right);
				div.scroll(function(){
					setTimeout(function(){
						let scrollLeft = div.scrollLeft();
						if(scrollLeft>0){
							left.show();
							setTimeout(function(){left.addClass('x')}, 0);
						}else{
							left.removeClass('x');
							setTimeout(function(){left.hide()}, 310);
						}
						if(scrollLeft<div[0].scrollWidth-width){
							right.show();
							setTimeout(function(){right.addClass('x')}, 0);
						}else{
							right.removeClass('x');
							setTimeout(function(){right.hide()}, 310);
						}
					}, 0);
				});
				if(index>0){
					setTimeout(function(){
						let w = 0;
						list.each(function(k){
							let li = $(this);
							if(index === k){
								if(w+li.outerWidth(true) > width)div.scrollLeft(w+li.outerWidth(true)-width);
								return false;
							}
							w += li.outerWidth(true);
						});
						div.scroll();
					}, 0);
				}else{
					div.scroll();
				}
			}
			wrap.width(totalWidth);
			let ele = list.eq(index).addClass(options.cls);
			if(options.column.length){
				let column = $('<div class="'+options.column+'"></div>').css({
					position:'absolute', left:0, bottom:0, 'z-index':1, width:0,
					transform:'translate3d(0,0,0)', '-webkit-transform':'translate3d(0,0,0)',
					'-webkit-transition':'-webkit-transform 300ms ease-out', transition:'transform 300ms ease-out'
				});
				wrap.append(column);
				let x = ele.position().left;
				column.css({width:ele.outerWidth(true), transform:'translate3d('+x+'px,0,0)', '-webkit-transform':'translate3d('+x+'px,0,0)'});
			}
			if($.isFunction(options.selectFn)){
				list.find('a').click(function(){
					list.removeClass(options.cls);
					let ele = $(this).parent().addClass(options.cls), x = ele.position().left;
					if(options.column.length)column.css({width:ele.outerWidth(false), transform:'translate3d('+x+'px,0,0)', '-webkit-transform':'translate3d('+x+'px,0,0)'});
					options.selectFn.call(ele);
					return false;
				});
			}
		}, 100);
	});
};

//简化版遮罩层与展示层
$.fn.presentView = function(options){
	if(typeof options === 'undefined')options = 0;
	if(typeof options === 'boolean' && !options){
		options = this.data('presentView-options');
		let _this = this.removeClass('load-presentView'), overlay = $('.load-overlay'), bgDelay = 400;
		setTimeout(function(){
			if($('.load-face, .load-view, .load-presentView, .dialog-action, .dialog-alert, .dialog-popover').length)return;
			overlay.removeClass('load-overlay-in');
			setTimeout(function(){overlay.remove()}, bgDelay);
		}, bgDelay);
		switch(options.type){
			case 0:
				_this.css({'-webkit-transform':'translate(0,100%)', transform:'translate(0,100%)'});
				break;
			case 1:
				_this.css({'-webkit-transform':'translate(100%,0)', transform:'translate(100%,0)'});
				break;
			case 2:
				_this.css({'-webkit-transform':'translate(0,-100%)', transform:'translate(0,-100%)'});
				break;
			case 3:
				_this.css({'-webkit-transform':'translate(-100%,0)', transform:'translate(-100%,0)'});
				break;
		}
		setTimeout(function(){
			_this.css({display:'none', '-webkit-transition-duration':'0s', 'transition-duration':'0s'});
			if($.isFunction(options.closeCallback))options.closeCallback.call(_this);
		}, 300);
		return this;
	}
	if(typeof options === 'number')options = {type:options};
	options = $.extend({
		type: 0, //0下往上, 1右往左, 2上往下, 3左往右
		callback: null,
		closeCallback: null
	}, $('body').data('presentView.options'), options);
	let _this = this.addClass('load-presentView').data('presentView-options', options).css({
		display:'none', position:'fixed', 'z-index':9999, '-webkit-transition':'transform 0s ease-out', transition:'transform 0s ease-out'
	}), overlay = $('.load-overlay');
	if(!overlay.length && !!!_this.data('overlay-no') && !!!_this.data('overlay-no-overlay')){
		overlay = $('<div class="load-overlay" style="background:rgba(0,0,0,0.6);"></div>');
		$(document.body).append(overlay);
	}
	if(!this.parent().length)$(document.body).append(this);
	if($.isFunction(options.callback))setTimeout(function(){options.callback.call(this)}, 300);
	setTimeout(function(){overlay.addClass('load-overlay-in')}, 0);
	overlay.on(window.eventType, function(){_this.presentView(false)});
	switch(options.type){
		case 0:
			_this.css({bottom:0, '-webkit-transform':'translate(0,100%)', transform:'translate(0,100%)'});
			break;
		case 1:
			_this.css({right:0, '-webkit-transform':'translate(100%,0)', transform:'translate(100%,0)'});
			break;
		case 2:
			_this.css({top:0, '-webkit-transform':'translate(0,-100%)', transform:'translate(0,-100%)'});
			break;
		case 3:
			_this.css({left:0, '-webkit-transform':'translate(-100%,0)', transform:'translate(-100%,0)'});
			break;
	}
	setTimeout(function(){
		_this.css({display:'block', '-webkit-transition-duration':'0.3s', 'transition-duration':'0.3s'});
		setTimeout(function(){_this.css({'-webkit-transform':'translate(0,0)', transform:'translate(0,0)'})}, 50);
	}, 0);
	return this;
};

//遮罩层与展示层, target:expr|对象|html代码(内容)|空字符串只显示背景遮罩层|false(删除),
//type:浮动控件位置类型(0:居中|1:底部|2:全屏居中(不随滚动)|3:居中(不自动opacity)|funciton:自定义)
//调用前 caller.data('overlay-no', true) 或  caller.data('overlay-no-overlay', true) 可不加遮罩层
//target可增加以下自定义属性, target-width:指定宽度, target-height:指定高度, overlay-opacity:遮罩层背景色透明度, no-close:点击遮罩层不关闭, show-close:显示右上角关闭按钮, css:增加样式到face(字面量格式), add-class:增加样式名到face, delay-class:延迟增加样式名到face, delay-close:指定关闭时长(默认300), close-class:关闭前增加样式到face, no-animate:不使用动画
$.fn.overlay = function(target, type, callback, closeCallback){
	let _this = this;
	if(typeof target === 'boolean' && !target){
		let overlay = $('.load-overlay', _this), face = $('.load-face', _this), target = face.data('overlay.target'),
			bgDelay = (target && !!!target.attr('no-animate')) ? 400 : 0;
		if(!target)return;
		setTimeout(function(){
			if($('.load-face, .load-view, .load-presentView, .dialog-action, .dialog-alert, .dialog-popover', _this).length)return;
			overlay.removeClass('load-overlay-in');
			setTimeout(function(){overlay.remove()}, bgDelay);
		}, bgDelay);
		if(face.length){
			let closeCallback = face.data('overlay.callback'),
				origin = target.removeData('overlay.overlay').data('overlay.origin'),
				cls = face.data('overlay.closeClass'), delay = face.data('overlay.delayClose')||(!!!target.attr('no-animate')?300:0);
			if(typeof type === 'undefined' || (!$.isNumeric(type) && !$.isFunction(type)))type = face.data('overlay.type');
			if(!!cls)face.addClass(cls);
			if($.isFunction(type)){
				type.call(face);
			}else{
				if(!!!type || type !== 1){
					if(type !== 3 && !!!target.attr('no-animate'))face.animate({opacity:0}, delay);
					setTimeout(function(){
						if(!!origin){origin.after(target.css('display', target.data('overlay.display')));origin.remove()}
						if($.isFunction(closeCallback))closeCallback.call(target);
						face.remove();
					}, delay);
				}else{
					if(!!!target.attr('no-animate')){
						face.animate({bottom:-face.height()}, delay, function(){
							if(!!origin){origin.after(target.css('display', target.data('overlay.display')));origin.remove()}
							if($.isFunction(closeCallback))closeCallback.call(target);
							face.remove();
						});
					}else{
						if(!!origin){origin.after(target.css('display', target.data('overlay.display')));origin.remove()}
						if($.isFunction(closeCallback))closeCallback.call(target);
						face.remove();
					}
				}
			}
		}
		return;
	}
	let t = $([]), isUrl = false;
	if(typeof target !== 'undefined' && typeof target !== 'boolean' && target.length){
		if(/^https?:\/\//.test(target)){
			isUrl = true;
			let s = target.split('||'), iframeWidth = 800, iframeHeight = 500, scrolling = '';
			for(let i=1; i<s.length; i++){
				if(s[i].indexOf('scrolling')>-1){
					let o = s[i].split('=');
					scrolling = 'scrolling="'+o[1]+'"';
				}else if(/\d+%?(\*\d+%?)?/.test(s[i])){
					let o = s[i].split('*');
					iframeWidth = o[0];
					if(o.length>1)iframeHeight = o[1];
				}
			}
			if((iframeWidth+'').indexOf('%')>-1)iframeWidth = $.window().width * (iframeWidth.replace(/%/g, '') / 100);
			if((iframeHeight+'').indexOf('%')>-1)iframeHeight = $.window().height * (iframeHeight.replace(/%/g, '') / 100);
			if(!isNaN(iframeWidth))iframeWidth += 'px';
			if(!isNaN(iframeHeight))iframeHeight += 'px';
			target = '<iframe src="'+s[0]+'" frameborder="0" style="width:'+iframeWidth+';height:'+iframeHeight+';" '+scrolling+'></iframe>\
				<div class="circle-db-ico" style="position:absolute;left:0;top:0;right:0;bottom:0;background:#fff no-repeat center center;background-size:64px 64px;"></div>';
		}
		t = $(target);
		if(t.parent().length){
			let display = t.css('display');
			let origin = $('<div style="display:'+display+';opacity:0;width:'+t.outerHeight(false)+'px;height:'+t.outerHeight(false)+'px;"></div>');
			t.after(origin);
			t.data({'overlay.origin':origin, 'overlay.display':display});
			t.removeClass('hidden');
		}
	}
	let win = $.window(), winHeight = win.height, overlay = $('.load-overlay', _this), face = $('.load-face', _this);
	if(!overlay.length && !!!_this.data('overlay-no') && !!!_this.data('overlay-no-overlay'))overlay = $('<div class="load-overlay"></div>');
	if(!face.length)face = $('<div class="load-face"></div>');
	else{
		face.removeClass(face.data('overlay.addClass')).removeClass(face.data('overlay.delayClass'));
		let tar = face.data('overlay.target'), origin = tar.data('overlay.origin');
		if(!!origin){origin.after(tar.css('display', tar.data('overlay.display')));origin.remove()}
		face.html('');
	}
	if(!!!_this.data('overlay-no') && !!!_this.data('overlay-no-overlay'))_this.append(overlay.css({background:'rgba(0,0,0,'+(t.attr('overlay-opacity')||0.6)+')'}));
	if(overlay.height() === 0)overlay.css({position:'fixed', top:0, left:0, 'z-index':998, width:'100%', height:win.height, overflow:'hidden'});
	if(!!!t.attr('no-animate'))setTimeout(function(){overlay.addClass('load-overlay-in')}, 0);
	else overlay.css({opacity:1, '-webkit-transition-duration':'0s', 'transition-duration':'0s'});
	if(!!!t.attr('no-close'))overlay.on(window.eventType, function(){_this.overlay(false)});
	if(!t.length)return;
	_this.append(face);
	if(typeof type === 'undefined' || (!$.isNumeric(type) && !$.isFunction(type)))type = 0;
	face.data({'overlay.target':t.data('overlay.overlay', true), 'overlay.type':type, 'overlay.callback':closeCallback}).append(t);
	t.eq(0).css('display', 'block');
	face.css({position:'fixed', 'z-index':999, '-webkit-transform':'translateY(-9999px)', transform:'translateY(-9999px)'});
	if(!!t.attr('css'))face.data({'overlay.css':t.attr('css')}).css($.json(t.attr('css')));
	if(!!t.attr('add-class'))face.data({'overlay.addClass':t.attr('add-class')}).addClass(t.attr('add-class'));
	if(!!t.attr('delay-class'))setTimeout(function(){face.data({'overlay.delayClass':t.attr('delay-class')}).addClass(t.attr('delay-class'))}, 100);
	if(!!t.attr('delay-close'))face.data({'overlay.delayClose':Number(t.attr('delay-close'))});
	if(!!t.attr('close-class'))face.data({'overlay.closeClass':t.attr('close-class')});
	if(!!t.attr('show-close')){
		let close = $('<a href="javascript:void(0)">×</a>').css({position:'absolute', right:0, top:0, 'z-index':999, width:30, height:30, 'line-height':'24px', overflow:'hidden', background:'rgba(0,0,0,0.6)', color:'#fff', 'font-size':'22px', 'text-align':'center', 'padding-left':'5px', 'box-sizing':'border-box', 'font-family':'arial', 'text-decoration':'none', '-moz-border-radius-bottomleft':'30px', '-webkit-border-bottom-left-radius':'30px', 'border-bottom-left-radius':'30px'}).click(function(){_this.overlay(false)});
		face.append(close);
	}
	if(isUrl){
		let iframe = face.find('iframe');
		iframe.on('load', function(){
			iframe.next().remove();
		});
	}
	setTimeout(function(){
		if(!!t.attr('target-width'))t.width(t.attr('target-width'));
		if(!!t.attr('target-height'))t.height(t.attr('target-height'));
		face.css({width:t.outerWidth(true)});
		if($.isFunction(type)){
			type.call(face);
			if($.isFunction(callback))setTimeout(function(){callback.call(t)}, 100);
		}else{
			if(type !== 1){
				if(type === 2){
					winHeight = win.maxHeight;
					face.css({position:'absolute'});
				}
				face.css({left:'50%', top:'50%', '-webkit-transform':'translate(-50%, -50%)', transform:'translate(-50%, -50%)'});
				if(type === 0 && !!!t.attr('no-animate')){
					face.css({opacity:0});
					setTimeout(function(){
						face.animate({opacity:1}, 300, 'easeout', function(){
							if($.isFunction(callback))callback.call(t);
						});
					}, 300);
				}else{
					if($.isFunction(callback))callback.call(t);
				}
			}else{
				face.css({'-webkit-transform':'translateY(0%)', transform:'translateY(0%)'});
				face.css({left:0, top:'', bottom:-face.height(), width:win.width});
				if(!!!t.attr('no-animate')){
					face.animate({bottom:0}, 300, function(){
						if($.isFunction(callback))callback.call(t);
					});
				}else{
					face.css({bottom:0});
					if($.isFunction(callback))callback.call(t);
				}
			}
		}
	}, 100);
	return face;
};

//加载动画遮罩层
//调用前document.body可设置data属性, overload.caller调用者, overload.delay延迟显示, overload.auto自动隐藏时间, overload.click-close点击立即关闭, overload.class附加样式, overlay.class遮罩层附加样式
//text:[false(关闭)|数字(顶部进度条)|null(只显示菊花)|string(提示文字)], image:[数字(默认)|null(不显示菊花)|.类名(使用类名)|string(图片路径)], auto:自动关闭时间(毫秒), callback:消失后执行
$.fn.overload = function(text, image, auto, callback){
	let _this = this;
	if(typeof text === 'number'){
		let view = _this.find('.load-view-progress');
		if(view.length)return;
		function randAssign(totalAmount, totalPeople){
			let remainAmount = +totalAmount, remainPeople = +totalPeople, arr = [];
			let scramble = function(remainAmount, remainPeople){
				if(remainPeople === 1)return +remainAmount.toFixed(2);
				let max = ((remainAmount / remainPeople) * 2 - 0.01).toFixed(2), min = 0.01, range = max - min, rand = Math.random();
				return min + Math.round(rand * range); //四舍五入
			};
			while(remainPeople > 0){
				let num = scramble(remainAmount, remainPeople);
				num = parseInt(num);
				remainAmount = remainAmount - num;
				remainPeople--;
				arr.push(num);
			}
			return arr;
		}
		view = $('<div class="load-view-progress"><div></div></div>');
		_this.append(view);
		setTimeout(function(){
			if(view.height() === 0)view.css({position:'fixed', 'z-index':9999, top:0, left:0, right:0, margin:0, padding:0, height:'3px'})
			.find('div').css({margin:0, padding:0, width:0, height:'100%', overflow:'hidden', background:'#2aa346', 'box-shadow':'0 0 4px rgba(121,210,142,0.8)',
				'-webkit-transition':'width 0.2s ease-out', transition:'width 0.2s ease-out'});
			let portion = text, arr = randAssign(100, portion), remain = portion, percent = 0;
			let timer = setInterval(function(){
				percent += arr[portion-remain];
				view.find('div').css('width', percent+'%');
				remain--;
				if(remain === 0){
					clearInterval(timer);
					timer = null;
				}
			}, 1000);
		}, 20);
		return;
	}else if(typeof text === 'boolean' && !text){
		let view = $('.load-view', _this);
		if(!view.length)return;
		setTimeout(function(){
			if(!!view.data('overload.timer'))return;
			view.removeClass('load-view-in').addClass('load-view-out');
			setTimeout(function(){
				callback = view.data('overload.callback');
				if($.isFunction(callback))callback();
				view.remove();
				if($('.load-face, .load-view, .load-presentView, .dialog-action, .dialog-alert, .dialog-popover', _this).length)return;
				$('.load-overlay', _this).remove();
			}, 400);
		}, 10);
		return;
	}
	if(typeof image === 'undefined' || (typeof image === 'string' && !image.length) || typeof image === 'number')image = '.load-animate';
	let overlay = $('.load-overlay', _this), view = $('.load-view', _this);
	if(typeof text === 'boolean')text = '';
	if(!view.length){
		if(!overlay.length && !!!_this.data('overlay-no') && !!!_this.data('overlay-no-overload')){
			overlay = $('<div class="load-overlay"></div>');
			_this.append(overlay);
			if(!!_this.data('overlay.class'))overlay.addClass(_this.data('overlay.class'));
			if(!!$(document.body).data('overload.click-close')){
				overlay.tapper(function(){
					if(!!view.data('overload.timer')){
						let timer = view.data('overload.timer');
						clearTimeout(timer);
						view.removeData('overload.timer');
					}
					view.data('overload.caller').overload(false);
				});
			}
		}
		view = $('<div class="load-view"><div></div><span>'+text+'</span></div>');
		_this.append(view);
		view.data('overload.caller', _this);
		if(!!_this.data('overload.class'))view.addClass(_this.data('overload.class'));
		if(!!$(document.body).data('overload.click-close')){
			view.tapper(function(){
				if(!!view.data('overload.timer')){
					let timer = view.data('overload.timer');
					clearTimeout(timer);
					view.removeData('overload.timer');
				}
				view.data('overload.caller').overload(false);
			});
		}
	}else{
		let timer = view.removeAttr('style').data('overload.timer');
		if(!!timer){clearTimeout(timer);view.removeData('overload.timer')}
		view.find('div').removeAttr('class').removeAttr('style').show();
		view.find('span').removeAttr('style').show().html(text);
	}
	//view.css({'max-width':(window.width>750 ? 260 : window.width-15*2)+'px'}); //750:安卓
	if(view.width()>180 && view.width()<260)view.css({'max-width':'180px'});
	view.css({'margin-top':(-view.height()/2)+'px', 'margin-left':(-view.width()/2)+'px'});
	if(!image){
		view.find('div').hide();
		view.find('span').addClass('text').css({'margin-top':(view.height()-view.find('span').outerHeight(false))/2});
	}else{
		if(image.substr(0, 1) === '.'){
			view.find('div').addClass(image.substr(1));
		}else{
			view.find('div').css({width:35, height:35, 'background-image':'url('+image+')'});
		}
	}
	if(!text)view.find('div').css({'margin-top':(view.height()-view.find('div').height())/2}).next().hide();
	setTimeout(function(){
		overlay.addClass('load-overlay-in');
		view.addClass('load-view-in');
	}, 10);
	if(auto){
		let timer = setTimeout(function(){
			let timer = view.data('overload.timer');
			if(!!timer){clearTimeout(timer);view.removeData('overload.timer')}
			$.overload(false);
		}, auto);
		view.data('overload.timer', timer);
	}
	if($.isFunction(callback))view.data('overload.callback', callback);
	return view;
};

//json数据自动替换模板
//由第二个arguments开始将同时也加载到模板内, 若使用KEY来包括数据{'arg:':data}(假设data为{key:value})即模板可以使用{arg.key}, 否则arguments[1].key或args[1].key
$.fn.template = function(options){
	if(typeof options === 'boolean'){
		return this.each(function(){
			let _this = $(this), html = _this.html();
			if(/{template}[\s\S]+{\/template}/.test(html)){
				_this.html(html.replace(/{template}[\s\S]+{\/template}/, ''));
				html = html.replace(/{template}([\s\S]+){\/template}/, '$1');
			}
			_this.data('template.tpl', html);
			if(!!options){
				!!!_this.data('template.init') && _this.css('display', 'none');
			}
		});
	}
	if(typeof options === 'string'){
		if(options === 'tpl')return this.data('template.tpl');
		else if(options === 'data')return this.data('template.data');
		else{
			return this.each(function(){
				let _this = $(this);
				_this.data('template.tpl', _this.find(options).outerHTML());
				_this.find(options).remove();
			});
		}
	}
	options = $.extend({
		data: null, //对象字面量||json字符串
		append: 'html', //填充方式(jQuery的操作函数)
		leftMark: '\\{', //左标识
		rightMark: '\\}', //右标识
		debug: false, //console打印code
		before: null, //填充前执行,接受一个参数:模板解析后的html代码,若返回字符串,即按该字符串填充
		complete: null //解析完毕替换后执行
	}, $('body').data('template.options'), options);
	if(!options.append.length)return this;
	let args = []; //附加参数
	for(let i=0; i<arguments.length; i++)args.push(arguments[i]);
	return this.each(function(){
		let _this = $(this), data = options.data, tpl = !!_this.data('template.tpl') ? _this.data('template.tpl') : _this.html(), errorShown = false, html = '', code = 'var $ = window.jQuery, args = arguments;\n';
		_this.data('template.data', data);
		_this.data('template.tpl', tpl);
		!!!_this.data('template.init') && _this.css('display', 'none');
		if(!$.trim(tpl).length)return true;
		if(args.length>1){
			code += 'if (args.length>1) {\n\
	for (var i=1; i<args.length; i++) {\n\
		if ($.isPlainObject(args[i])) {\n\
			for (var j in args[i]) {\n\
				if (/^\\w+:$/i.test(j)) {\n\
					eval("var "+j.substr(0, j.length-1)+" = args[i][j];");\n\
				}\n\
			}\n\
		}\n\
	}\n\
}\n';
		}
		code += $.template(tpl, options.leftMark, options.rightMark);
		if(options.debug)console.log(code);
		try{
			html = new Function(code).apply(data, args);
			html = $.template(html, true);
			if($.isFunction(options.before)){
				let result = options.before.call(_this, html);
				if(typeof result === 'string')html = result;
			}
		}catch(e){
			if(!errorShown){
				console.log(code);
				console.log(e);
				errorShown = true;
			}
		}
		new Function('this.'+options.append+'(arguments[0]);').call(_this, html);
		!!!_this.data('template.init') && _this.css('display', '');
		if($.isFunction(options.complete)){
			setTimeout(function(){
				options.complete.call(_this);
			}, 10);
		}
		let templateComplete = $('body').data('template.complete');
		if($.isFunction(templateComplete)){
			setTimeout(function(){
				templateComplete.call(_this);
			}, 10);
		}
		_this.data('template.init', true);
	});
};

//动画过渡效果
$.extend($.easing, {
	linear: function(x){
		return x;
	},
	swing: function(x){
		return 0.5 - Math.cos( x*Math.PI ) / 2;
	},
	easeout: function(x, t, b, c, d){
		return -c * (t /= d) * (t - 2) + b;
	},
	bounceout: function(x, t, b, c, d){
		if ((t/=d) < (1/2.75)) {
			return c*(7.5625*t*t) + b;
		} else if (t < (2/2.75)) {
			return c*(7.5625*(t-=(1.5/2.75))*t + .75) + b;
		} else if (t < (2.5/2.75)) {
			return c*(7.5625*(t-=(2.25/2.75))*t + .9375) + b;
		} else {
			return c*(7.5625*(t-=(2.625/2.75))*t + .984375) + b;
		}
	},
	backout: function(x, t, b, c, d){
		let s = 1.70158;
		return c*((t=t/d-1)*t*((s+1)*t + s) + 1) + b;
	}
});

//重写$.browser
$.uaMatch = function(ua){
	let match = /(chrome)[ \/]([\w.]+)/.exec(ua) ||
		/(webkit)[ \/]([\w.]+)/.exec(ua) ||
		/(opera)(?:.*version|)[ \/]([\w.]+)/.exec(ua) ||
		/(msie) ([\w.]+)/.exec(ua) ||
		ua.indexOf('compatible') === -1 && /(mozilla)(?:.*? rv:([\w.]+)|)/.exec(ua) ||
		[];
	return {
		browser: match[1] || '',
		version: match[2] || '0'
	};
};
let matched = $.uaMatch(ua), browser = {ua:ua};
if(matched.browser){
	browser[matched.browser] = true;
	browser.version = matched.version;
}
if(ua.match(/windows mobile/i))browser.wm = true;
else if(ua.match(/windows ce/i))browser.wince = true;
else if(ua.match(/ucweb/i))browser.ucweb = true;
else if(ua.match(/rv:1.2.3.4/i))browser.uc7 = true;
else if(ua.match(/midp/i))browser.midp = true;
else if(browser.msie){
	if(browser.version<7)browser.ie6 = true;
	else if(browser.version<8)browser.ie7 = true;
	else if(browser.version<9)browser.ie8 = true;
	else if(browser.version<10)browser.ie9 = true;
}else if(browser.chrome)browser.webkit = true;
else if(browser.webkit){
	browser.safari = true;
	let matcher = /safari\/([\d._]+)/.exec(ua);
	if($.isArray(matcher))browser.version = matcher[1].replace(/_/g, '.');
}else if(browser.mozilla)browser.firefox = true;
if(ua.match(/iphone/i) || ua.match(/ipad/i)){
	if(ua.match(/iphone/i))browser.iphone = true;
	if(ua.match(/ipad/i))browser.ipad = true;
	let matcher = / os ([\d._]+) /.exec(ua);
	if($.isArray(matcher))browser.version = matcher[1].replace(/_/g, '.');
}
if(ua.match(/android/i)){
	browser.android = true;
	let matcher = /android ([\d.]+)/.exec(ua);
	if($.isArray(matcher))browser.version = matcher[1];
}
if(browser.iphone || browser.ipad)browser.ios = true;
if(ua.match(/micromessenger/i) && (browser.ios || browser.android))browser.wechat = browser.weixin = browser.wx = true;
if(browser.ios || browser.android || browser.wm || browser.wince || browser.ucweb || browser.uc7 || browser.midp || browser.wx)browser.mobile = true;

$.extend({
	log: console.log,
	browser: browser,
	etarget: function(e){return e.target||e.srcElement},
	ebubble : function(e, breaker, callback){
		if(!$.isFunction(breaker) || !$.isFunction(callback))return;
		let o = $.etarget(e);
		do{
			if(breaker(o))return;
			if((/^(html|body)$/i).test(o.tagName)){
				callback();
				return;
			}
			o = o.parentNode;
		}while(o.parentNode);
	},
	referer: function(){
		let referer = '';
		try{
			referer = window.top.document.referrer;
		}catch(e){
			if(window.parent){
				try{
					referer = window.parent.document.referrer;
				}catch(e2){
					referer = '';
				}
			}
		}
		if(!referer.length)referer = document.referrer;
		return referer;
	},
	resize: function(resize){
		if(!$.isFunction(resize))return;
		let fn = $(document).data(resize.toString());
		if(!!fn)return;
		$(document).data(resize.toString(), resize);
		(function (doc, win, resize){
			let resizeEvt = 'orientationchange' in window ? 'orientationchange' : 'resize';
			if(!doc.addEventListener)return;
			win.addEventListener(resizeEvt, function(){setTimeout(resize, 200)}, false);
			doc.addEventListener('DOMContentLoaded', function(){setTimeout(resize, 200)}, false);
		})(document, window, resize);
	},
	trim: function(str, symbol){
		if(typeof str !== 'string')return str;
		return str.trim(symbol);
	},
	isArray: function(obj){
		return (Object.prototype.toString.call(obj) === '[object Array]');
	},
	isFunction: function(fn){
		if(typeof document.getElementById === 'object'){
			try{
				return /^\s*\bfunction\b/.test('' + fn);
			}catch(e){
				return false;
			}
		}else{
			return (Object.prototype.toString.call(fn) === '[object Function]');
		}
	},
	getStyle: function(path){$('<link>').attr({rel:'stylesheet', type:'text/css', href:path}).appendTo('head')},
	getJSON: function(url, data, callback, async, crossDomain){
		if($.isFunction(data)){
			if(typeof callback === 'boolean'){
				async = callback;
				callback = data;
				data = {};
			}else if(typeof callback === 'undefined'){
				callback = data;
				data = {};
			}else{
				data = data.call();
			}
		}
		let error = null;
		let complete = null;
		if($.isPlainObject(callback)){
			if(typeof callback.error !== 'undefined')error = callback.error;
			if(typeof callback.complete !== 'undefined')complete = callback.complete;
			if(typeof callback.callback !== 'undefined')callback = callback.callback;
			if($.isPlainObject(callback) && typeof callback.success !== 'undefined')callback = callback.success;
		}
		let qmark = url.match(/\?/g) ? url.match(/\?/g).length : 0;
		let cbName = url.match(/(\w+)=\?/) ? url.match(/(\w+)=\?/)[1] : '';
		url = url.replace(/&\w+=\?&?/, '').replace(/\?\w+=\?&?/, '?');
		let options = {type:'GET', url:url, data:data, dataType:'json', contentType:'application/x-www-form-urlencoded; charset=utf-8',
			success: function(json){
				$.overload(false);
				if(typeof(json.error) !== 'undefined' && typeof(json.msg) !== 'undefined' && Number(json.error) !== 0){
					if(json.msg.length && !$.isFunction(error))$.overloadError(json.msg);
					if($.isFunction(error))error(json, json.error);
					return;
				}
				if($.isFunction(callback))callback(json);
			},
			error: function(xml, status, e){
				$.overloadError(status+'\n'+e);
				if($.isFunction(error))error(e, status);
			},
			complete: function(){
				if($.isFunction(complete))complete();
			}
		};
		if(!!$(document.body).data('getJSON.timeout'))options.timeout = Number($(document.body).data('getJSON.timeout')); //毫秒
		if(typeof async === 'boolean')options.async = async;
		if(qmark === 2){
			options.dataType = 'jsonp';
			options.jsonp = cbName;
		}else if(crossDomain){
			options = $.extend({}, options, {
				xhrFields: { withCredentials:true },
				crossDomain: true
			});
		}
		return $.ajax(options);
	},
	postJSON: function(url, data, callback, async, crossDomain){
		if($.isFunction(data)){
			if(typeof callback === 'boolean'){
				async = callback;
				callback = data;
				data = {};
			}else if(typeof callback === 'undefined'){
				callback = data;
				data = {};
			}else{
				data = data.call();
			}
		}
		let error = null;
		let complete = null;
		if($.isPlainObject(callback)){
			if(typeof callback.error !== 'undefined')error = callback.error;
			if(typeof callback.complete !== 'undefined')complete = callback.complete;
			if(typeof callback.callback !== 'undefined')callback = callback.callback;
			if($.isPlainObject(callback) && typeof callback.success !== 'undefined')callback = callback.success;
		}
		if(data && data.toString() === '[object FormData]'){
			if(typeof async === 'undefined')async = true;
			let xhr = new XMLHttpRequest();
			xhr.addEventListener('load', function(e){success(e.target.responseText)}, false);
			if($.isFunction(error))xhr.addEventListener('error', function(e){error(e)}, false);
			if($.isFunction(complete))xhr.addEventListener('loadend', function(){complete()}, false);
			xhr.open('POST', url, async);
			xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=utf-8');
			if(!!$(document.body).data('postJSON.timeout'))xhr.timeout = Number($(document.body).data('postJSON.timeout')); //毫秒
			xhr.send(data);
			return xhr;
		}else{
			let qmark = url.match(/\?/g) ? url.match(/\?/g).length : 0;
			let cbName = url.match(/(\w+)=\?/) ? url.match(/(\w+)=\?/)[1] : '';
			url = url.replace(/&\w+=\?&?/, '').replace(/\?\w+=\?&?/, '?');
			let options = {type:'POST', url:url, data:data, dataType:'text', contentType:'application/x-www-form-urlencoded; charset=utf-8',
				success: function(json){
					success(json);
				},
				error: function(xml, status, e){
					$.overloadError(status+'\n'+e);
					if($.isFunction(error))error(e, status);
				},
				complete: function(){
					if($.isFunction(complete))complete();
				}
			};
			if(!!$(document.body).data('postJSON.timeout'))options.timeout = Number($(document.body).data('postJSON.timeout')); //毫秒
			if(qmark === 2){
				options.dataType = 'jsonp';
				options.jsonp = cbName;
			}
			if(typeof async === 'boolean')options.async = async;
			if(crossDomain){
				options = $.extend({}, options, {
					xhrFields: { withCredentials:true },
					crossDomain: true
				});
			}
			return $.ajax(options);
		}
		function success(res){
			$.overload(false);
			let json = {};
			if(typeof res === 'string'){
				if($.isJsonString(res)){
					json = $.json(res);
					if(typeof(json.error) !== 'undefined' && typeof(json.msg) !== 'undefined' && Number(json.error) !== 0){
						if(json.msg.length && !$.isFunction(error))$.overloadError(json.msg);
						if($.isFunction(error))error(json, json.error);
						return;
					}
				}else if(res.replace(/^[\s\r\n\t\f\v]+/g, '').substr(0, 1) !== '<'){
					if($.isFunction(error))error(res, -1);
					console.log(res);
					return;
				}else{
					json = res;
				}
			}else{
				json = res;
			}
			if($.isFunction(callback))callback(json);
		}
	},
	putJSON: function(url, data, callback){
		$.ajax({type:'put', url:url, data:data, dataType:'json', contentType:'application/json; charset=utf-8', success:function(json){
				if(typeof(json.error) !== 'undefined' && typeof(json.msg) !== 'undefined' && Number(json.error) !== 0){
					if(json.msg.length)$.overloadError(json.msg);
					return;
				}
				if($.isFunction(callback))callback(json);
			}, error:function(xml, status, e){
				$.overloadError(status+'\n'+e);
			}
		});
	},
	deleteJSON: function(url, data, callback){
		$.ajax({type:'delete', url:url, data:data, dataType:'json', contentType:'application/json; charset=utf-8', success:function(json){
				if(typeof(json.error) !== 'undefined' && typeof(json.msg) !== 'undefined' && Number(json.error) !== 0){
					if(json.msg.length)$.overloadError(json.msg);
					return;
				}
				if($.isFunction(callback))callback(json);
			}, error:function(xml, status, e){
				$.overloadError(status+'\n'+e);
			}
		});
	},
	postFORM: function(url, data, debug){
		let id = 'postform_'+parseInt(String(Math.random() * 1000)), enctype = false;
		if($.isPlainObject(data))$.each(data, function(key){
			if(key.indexOf('-')>-1){let keys = key.split('-');if(keys[0] === 'file'){enctype = true;return false}}
		});
		let form = '<form id="'+id+'" method="post" action="'+url+'" '+(enctype?'enctype="multipart/form-data"':'')+'>';
		if($.isPlainObject(data))$.each(data, function(key, value){
			let type = 'hidden';
			if(key.indexOf('-')>-1){let keys = key.split('-');type = keys[0];key = keys[1]}
			if($.isArray(value)){
				$.each(value, function(k){form += '<input type="'+type+'" name="'+key+'[]" id="'+key+k+'" style="display:none;" />'});
			}else if($.isPlainObject(value)){
				form += '<input type="'+type+'" name="'+key+'" id="'+key+'" style="display:none;" />';
			}else{
				form += '<input type="'+type+'" name="'+key+'" id="'+key+'" style="display:none;" />';
			}
		});
		form += '</form>';
		form = $(form);
		$(document.body).append(form);
		if($.isPlainObject(data))$.each(data, function(key, value){
			if(key.indexOf('-')>-1){let keys = key.split('-');key = keys[1]}
			if($.isArray(value)){
				$.each(value, function(k, v){form.find('#'+key+k).val($.isPlainObject(v)?$.jsonString(v):v)});
			}else if($.isPlainObject(value)){
				form.find('#'+key).val($.jsonString(value));
			}else{
				form.find('#'+key).val(value);
			}
		});
		if(!debug)$('#'+id).submit();
	},
	jsonString: function(obj){
		if(typeof JSON !== 'undefined')return JSON.stringify(obj);
		let type = $.type(obj), results = [];
		switch(type){
			case 'undefined':case 'unknown':case 'null':return type;
			case 'function':case 'regexp':return toString.call(obj);
			case 'boolean':return obj ? 'true' : 'false';
			case 'number':return isFinite(obj) ? obj+'' : 'null';
			case 'string':
				return '"' + obj.replace(/([\/"])/g, '\\$1').replace(/[\n\r\t]/g, function(a){
					return (a === '\n') ? '\\n' : (a === '\r') ? '\\r' : (a === '\t') ? '\\t': '';
				}) + '"';
			case 'object':
				if(obj === null)return 'null';
				for(let key in obj){
					if(obj.hasOwnProperty(key)){
						if($.isFunction(obj[key]))continue;
						let val = $.jsonString(obj[key]);
						if(val.length && val !== 'undefined' && val !== 'unknown')results.push('"' + key + '":' + val);
					}
				}
				return '{' + results.join(',') + '}';
			case 'array':
				for(let i=0; i<obj.length; i++){
					let val = $.jsonString(obj[i]);
					if(val.length && val !== 'undefined' && val !== 'unknown')results.push(val);
				}
				return '[' + results.join(',') + ']';
		}
		return '';
	},
	json: function(str){
		if(typeof str === 'string' && !str.length)return str;
		if($.isPlainObject(str))return str;
		if(typeof JSON !== 'undefined')return JSON.parse(str);
		try{
			return evil(str);
		}catch(e){
			return null;
		}
	},
	isJsonString: function(str){
		if(typeof str === 'undefined' || !str || (typeof str === 'string' && !str.length))return false;
		try{
			let json = null;
			if(typeof str === 'object'){
				json = str;
			}else{
				if(typeof JSON !== 'undefined')json = JSON.parse(str);
				else json = evil(str);
			}
			return ($.isPlainObject(json) || $.isArray(json));
		}catch(e){
			return false;
		}
	},
	isJson: function(obj){
		return (($.isPlainObject(obj) && !$.isEmptyObject(obj)) || ($.isArray(obj) && obj.length));
	},
	isDate: function(obj){
		if(typeof obj === 'undefined' || !obj)return false;
		if((obj instanceof Date) && !isNaN(obj.getFullYear()))return true;
		if(typeof obj === 'string')return /^\d{4}-\d{1,2}-\d{1,2}( \d{1,2}:\d{1,2}:\d{1,2})?$/.test(obj);
		return false;
	},
	random: function(min, max){
		return Math.floor(Math.random() * (max - min + 1) + min);
	},
	//生成由数字,大写字母,小写字母组合的指定位数的随机字符串, s:指定字符(可使用中文字), randomCode(8,'')
	randomCode: function(n, s){
		let o, codes = '';
		if(!s)s = 'EabXYcde12OP3FBADCUijk45WZlt6GHLMvwIJKfgh90TxNQRSmnopyzqrs78Vu';
		if(isNaN(n))return '';
		o = s.split('');
		for(let i=0; i<n; i++){
			let id = Math.ceil(Math.random()*o.length);
			codes += o[id];
		}
		return codes;
	},
	datetimeAndRandom: function(){
		return (new Date()).formatDate('yyyymmddhhnnss') + Math.ceil(Math.random()*8999+1000);
	},
	window: function(father){
		let docEl = window.document.documentElement, doc = $.document(father);
		return {
			width:doc.clientWidth, height:doc.clientHeight,
			scrollLeft:doc.scrollLeft, scrollTop:doc.scrollTop,
			scrollWidth:doc.scrollWidth, scrollHeight:doc.scrollHeight,
			minWidth:Math.min(doc.clientWidth, doc.scrollWidth), minHeight:Math.min(doc.clientHeight, doc.scrollHeight),
			maxWidth:Math.max(doc.clientWidth, doc.scrollWidth), maxHeight:Math.max(doc.clientHeight, doc.scrollHeight),
			screenWidth:docEl.getBoundingClientRect()?docEl.getBoundingClientRect().width:window.screen.width,
			screenHeight:docEl.getBoundingClientRect()?docEl.getBoundingClientRect().height:window.screen.height,
			ratio:window.devicePixelRatio?window.devicePixelRatio:1
		}
	},
	document: function(father){
		let doc = null;
		switch(father){
			case 'top':doc = top.document[top.document.compatMode === 'CSS1Compat' ? 'documentElement' : 'body'];break;
			case 'parent':doc = parent.document[parent.document.compatMode === 'CSS1Compat' ? 'documentElement' : 'body'];break;
			default:doc = document[document.compatMode === 'CSS1Compat' ? 'documentElement' : 'body'];break;
		}
		return doc;
	},
	//滚动条宽高
	scrollBar: function(){
		let div = $('<div></div>').css({ position:'absolute', left:-9999, top:-9999, overflow:'scroll', width:100, height:100, padding:0, margin:0, border:'none', 'box-shadow':'none' });
		$(document.body).append(div);
		let width = div.outerWidth(false) - div.width(), height = div.outerHeight(false) - div.height();
		div.remove();
		return {width:width, height:height};
	},
	//获取event对象的屏幕距离
	touches: function(e, type){
		let x = 0, y = 0, pageX = 0, pageY = 0,
			changedTouches = [{'pageX':0, 'pageY':0}], targetTouches = [{'pageX':0, 'pageY':0}], touches = [{'pageX':0, 'pageY':0}];
		if(typeof e.changedTouches !== 'undefined')changedTouches = e.changedTouches;
		if(typeof e.targetTouches !== 'undefined')targetTouches = e.targetTouches;
		if(typeof e.touches !== 'undefined')touches = e.touches;
		if(typeof e.pageX !== 'undefined'){pageX = e.pageX; pageY = e.pageY}
		if(typeof e.changedTouches !== 'undefined'){
			x = e.changedTouches[0].pageX; y = e.changedTouches[0].pageY;
		}else if(typeof e.targetTouches !== 'undefined'){
			x = e.targetTouches[0].pageX; y = e.targetTouches[0].pageY;
		}else if(typeof e.touches !== 'undefined'){
			x = e.touches[0].pageX; y = e.touches[0].pageY;
		}else if(typeof e.pageX !== 'undefined'){
			x = e.pageX; y = e.pageY;
		}
		if(typeof type !== 'undefined'){
			switch(type){
				case 1:x = changedTouches[0].pageX; y = changedTouches[0].pageY;break;
				case 2:x = targetTouches[0].pageX; y = targetTouches[0].pageY;break;
				case 3:x = touches[0].pageX; y = touches[0].pageY;break;
				case 4:x = pageX; y = pageY;break;
				case 5:x = e.clientX; y = e.clientY;break;
			}
			return {x:x, y:y};
		}
		return {changedTouches:changedTouches, targetTouches:targetTouches, touches:touches, pageX:pageX, pageY:pageY, clientX:e.clientX, clientY:e.clientY, x:x, y:y};
	},
	//判断手指滑动方向,[1:上,2:下,3:左,4:右,0未滑动]
    getDirection: function(startx, starty, endx, endy){
	    let angx = endx - startx, angy = endy - starty, result = 0;
		if(Math.abs(angx)<2 && Math.abs(angy)<2)return result; //如果滑动距离太短
		//获得角度
	    let angle = Math.atan2(angy, angx) * 180 / Math.PI;
		if(angle>=-135 && angle<=-45){
			result = 1;
		}else if(angle>45 && angle<135){
			result = 2;
		}else if((angle>=135 && angle<=180) || (angle>=-180 && angle<-135)){
			result = 3;
		}else if(angle>=-45 && angle<=45){
			result = 4;
		}
		return result;
    },
	//检测是否存在函数
	hasFunction: function(funcName){
		try{
			if(typeof(evil(funcName)) === 'function')return true;
		}catch(e){}
		return false;
	},
	//检测是否存在变量
	hasVariable: function(variableName){
		try{
			if(typeof(evil(variableName)) !== 'undefined')return true;
		}catch(e){}
		return false;
	},
	//去除单位px
	unit: function(unit){
		unit = unit.toString();
		if(!unit)return 0;
		if(unit !== '0')unit = /^-?\d+$/.test(unit) ? unit : unit.replace(/px/g, '');
		return Number(unit);
	},
	//原页面和目标页面的编码是一致时，只需escape，如果页面是GB2312或其他的编码，而目标页面是UTF-8编码，就采用encodeURI或encodeURIComponent
	urlencode: function(str){
		if(typeof str !== 'string' || !str.length)return '';
		return str.urlencode();
	},
	urldecode: function(str){
		if(typeof str !== 'string' || !str.length)return '';
		return str.urldecode();
	},
	scrollto: function(el, repair, speed, easing){
		$('html, body').scrollto({el:el, repair:repair, speed:speed, easing:easing});
		return false;
	},
	//获取URL参数, 格式:[?|#]param1=value1&param2=value2
	request: function(p, w){
		if(typeof w === 'undefined')w = window;
		let params = {}, pairs, query = w.location.href;
		if(!p)p = '?';
		if(p === '?')p = '\\?';
		query = query.replace(new RegExp('^[^'+p+']+'+p+'?'), '');
		if(!query.length)return null;
		pairs = query.split('&');
		for(let i=0; i<pairs.length; i++){
			let kv = pairs[i].split('='), key, val;
			key = $.urldecode(kv[0]);
			val = $.urldecode(pairs[i].substr((kv[0]).length+1));
			params[key] = val;
		}
		return params;
	},
	//绑定点击外部
	registControl: function(options){
		if(!$.isPlainObject(options)){
			let operateControl = $(options).data('operateControl');
			if(!!operateControl && $.isFunction(operateControl))operateControl();
			return $(options);
		}
		options = $.extend({
			menu: '', //被绑定对象
			partner: '', //例外控件,即匹配该选择器的控件都认为在内部
			outside: null //点击外部时执行, this:被绑定对象
		}, options);
		let menu = $(options.menu);
		if(!menu.length || !!menu.data('registControl'))return menu;
		menu.data('registControl', true).data('operateControl', operateControl).parents('body').on('click', operateControlHandle);
		function operateControlHandle(e){
			let o = $.etarget(e);
			do{
				if((options.partner && $(o).is(options.partner)) || $(o).is(menu))return;
				if((/^(html|body)$/i).test(o.tagName)){
					operateControl();
					return;
				}
				o = o.parentNode;
			}while(o.parentNode);
		}
		function operateControl(){
			if($.isFunction(options.outside))options.outside.call(menu);
		}
		return menu;
	},
	//modal填写弹框, 类型为select时placeholder为option(#隔开)(value|text)
	modalView: function(title, value, callback, closeCallback){
		if(typeof title === 'boolean'){
			$.overlay(false);
			return;
		}
		if($.isFunction(value)){
			callback = value;
			value = '';
		}
		let btn = '确定', element = [], width = '';
		if($.isPlainObject(value)){
			if(typeof value.width === 'undefined'){
				value = [value];
			}else{
				width = value.width; //弹框宽度
				value = value.item;
				if($.isPlainObject(value))value = [value];
			}
		}
		if($.isArray(value)){
			$.each(value, function(i){
				if(typeof this.title === 'undefined')this.title = 'input'+(i+1);
				if(typeof this.name === 'undefined')this.name = '';
				if(typeof this.value === 'undefined')this.value = '';
				if(this.name === 'password')this.type = 'password';
				if(typeof this.readonly === 'undefined')this.readonly = false;
				if(typeof this.disabled === 'undefined')this.disabled = false;
				if(typeof this.type === 'undefined' || !this.type.length)this.type = 'text';
				if(typeof this.placeholder === 'undefined' || !this.placeholder.length)this.placeholder = '请输入'+this.title;
				if(typeof this.height === 'undefined')this.height = '';
				if(typeof this.btn !== 'undefined' && typeof this.btn === 'string' && this.btn.length)btn = this.btn;
				element.push(this);
			});
		}else{
			element.push({title:title, type:'text', name:'', value:value, placeholder:'请输入'+title});
		}
		let html = '<div class="modalView" style="'+(String(width).length?'width:'+width+'px;':'')+'">\
			<div><span>'+title+'</span></div>';
			$.each(element, function(){
				html += '<label>';
				if(this.type === 'textarea'){
					html += '<textarea name="'+this.name+'" placeholder="'+this.placeholder+'" style="'+(this.height?'height:'+this.height+'px;':'')+'" '+(this.readonly?'readonly':'')+' '+(this.disabled?'disabled':'')+'>'+this.value+'</textarea>';
				}else if(this.type === 'select'){
					let value = this.value;
					html += '<select name="'+this.name+'" '+(this.disabled?'disabled':'')+'>';
					$.each(this.placeholder.split('#'), function(k, v){
						let f = v.split('|');
						html += '<option value="'+f[0]+'" '+(String(value)===String(f[0])?'selected':'')+'>'+f[1]+'</option>';
					});
					html += '</select>';
				}else{
					html += '<input type="'+this.type+'" name="'+this.name+'" placeholder="'+this.placeholder+'" value="'+this.value+'" '+(this.readonly?'readonly':'')+' '+(this.disabled?'disabled':'')+' />';
				}
				html += '</label>';
			});
			html += '<a class="ge-top ge-light" href="javascript:void(0)">'+btn+'</a>\
		</div>';
		$.overlay(html, 3, function(){
			if($.isFunction(callback)){
				let _this = this;
				$('.modalView a').on('click', function(){
					let data = '';
					if(element.length === 1){
						data = $(this).prev().find('input').val();
					}else{
						data = [];
						$.each(element, function(){
							if(this.name.length)data.push({title:this.title, name:this.name, value:_this.find('[name="'+this.name.replace(/([\[\]])/g, '\\$1')+'"]').val()});
						});
					}
					callback.call(_this, data);
				});
			}else{
				$('.modalView a').on('click', function(){
					$.modalView(false);
				});
			}
		}, closeCallback);
	},
	//遮罩层与展示层
	overlay: function(target, type, callback, closeCallback){
		return $(document.body).overlay(target, type, callback, closeCallback);
	},
	//加载动画遮罩层
	overload: function(text, image, auto, callback){
		let caller = top.document.body, delay = 0;
		if(!!$(document.body).data('overload.caller'))caller = $(document.body).data('overload.caller');
		if(typeof text !== 'undefined' && text){
			if(typeof text === 'number')caller = document.body;
			if(!!$(document.body).data('overload.delay'))delay = Number($(document.body).data('overload.delay'));
		}
		setTimeout(function(){
			$(caller).overload(text, image, auto, callback);
		}, delay);
	},
	//成功遮罩层
	overloadSuccess: function(text, auto, callback){
		if(typeof auto === 'undefined')auto = 3000;
		if(!!$(document.body).data('overload.auto'))auto = Number($(document.body).data('overload.auto'));
		$(document.body).data('overload.click-close', true);
		setTimeout(function(){$.overload(text, '.load-success', auto, callback)}, 0);
	},
	//失败遮罩层
	overloadError: function(text, auto, callback){
		if(typeof auto === 'undefined')auto = 3000;
		if(!!$(document.body).data('overload.auto'))auto = Number($(document.body).data('overload.auto'));
		$(document.body).data('overload.click-close', true);
		setTimeout(function(){$.overload(text, '.load-error', auto, callback)}, 0);
	},
	//问题遮罩层
	overloadProblem: function(text, auto, callback){
		if(typeof auto === 'undefined')auto = 3000;
		if(!!$(document.body).data('overload.auto'))auto = Number($(document.body).data('overload.auto'));
		$(document.body).data('overload.click-close', true);
		setTimeout(function(){$.overload(text, '.load-problem', auto, callback)}, 0);
	},
	//警告遮罩层
	overloadWarning: function(text, auto, callback){
		if(typeof auto === 'undefined')auto = 3000;
		if(!!$(document.body).data('overload.auto'))auto = Number($(document.body).data('overload.auto'));
		$(document.body).data('overload.click-close', true);
		setTimeout(function(){$.overload(text, '.load-warning', auto, callback)}, 0);
	},
	//模板解析
	template: function(tpl, leftMark, rightMark){
		if(!tpl.length)return '';
		if(typeof leftMark === 'boolean')return parseLast(tpl);
		if(typeof leftMark === 'undefined')leftMark = '\\{';
		if(typeof rightMark === 'undefined')rightMark = '\\}';
		let re = new RegExp(leftMark+'\\s*([\\s\\S]+?)\\s*'+rightMark, 'g'), cursor = 0, matcher,
			reExp = /(^( )?(\/?foreach|elseif|\/?if|var|let|for|else|\/?switch|case|default|break|return|\/?try|{|}|\+\+|--|;))(.*)?/g,
			switchAutoBreak = false, hasCase = false, code = 'var _this = data = this, arrRows = [];\n';
		tpl = tpl.replace(/<!--[\s\S]*?-->/g, '').replace(/(["])/g, '\\$1').replace(/\n/g, ' ').replace(/&lt;/ig, '<').replace(/&gt;/ig, '>').replace(/&amp;/ig, '&');
		tpl = tpl.replace(/->/g, '.').replace(/-&gt;/g, '.').replace(/\$(\w+)\b/g, '$1');
		while((matcher=re.exec(tpl)) !== null){
			parseHtml(tpl.slice(cursor, matcher.index));
			parseJs(matcher[1]);
			cursor = matcher.index + matcher[0].length;
		}
		parseHtml(tpl.substr(cursor, tpl.length-cursor));
		code += 'return arrRows.join("");';
		return code;
		function parseLast(html){
			html = html.replace(/<img ([\s\S]*?)data-src="([^"]*?)"([^>]*)>/g, '<img $1src="$2" $3>'); //ex: <img data-src="{this}" />
			html = html.replace(/\s+data-template-attr="([^"]*?)"/g, '$1'); //ex: <input type="checkbox" data-template-attr="{this==1?'checked':''}" />
			if(/\s+data-template-([^=]+)="(\w*)"/.test(html)){ //ex: <div data-template-tips-list="callbackFunction">
				let re = new RegExp('\\s+data-template-([\\w-]+)="(\\w*)"', 'g'), matcher, _html = $(html);
				while((matcher = re.exec(html)) !== null){
					let $1 = $.trim(matcher[1]), $2 = $.trim(matcher[2]);
					if(!$1.length || !$2.length)continue;
					if(!!_html.attr('data-template-'+$1)){
						let fn = evil(_html.attr('data-template-'+$1));
						if($.isFunction(fn)){
							let result = fn.call(_html);
							if(typeof result !== 'undefined')_html.attr($1, result);
						}
						_html.removeAttr('data-template-'+$1);
					}
					_html.find('[data-template-'+$1+']').each(function(){
						let ele = $(this);
						let fn = evil(ele.attr('data-template-'+$1));
						if($.isFunction(fn)){
							let result = fn.call(ele);
							if(typeof result !== 'undefined')ele.attr($1, result);
						}
						ele.removeAttr('data-template-'+$1);
					});
				}
				html = _html.outerHTML();
			}
			return html;
		}
		function parseHtml(line){
			if((line.length && !/^[\s\t]+$/.test(line)) || (line.length && line.length<3 && /^[\s\t]+$/.test(line)))code += 'arrRows.push("'+line+'");\n';
		}
		function parseJs(line){
			line = line.replace(/(^[\s\t]+)|([\s\t]+$)/g, '');
			line = line.replace(/&lt;/ig, '<').replace(/&gt;/ig, '>').replace(/&amp;/ig, '&');
			if(line.length){
				let matcher = line.match(reExp);
				if(matcher){
					//{foreach this as g key=i} {foreach (this as g)}
					let foreachExp = /^foreach\s*\(?\s*([^\s]+)\s+as\s+(\w+)(?:\s+key\s*=\s*(\w+))?\s*\)?\s*{?$/g;
					if(line.match(foreachExp)){
						line = line.replace(foreachExp, function(_$, $1, $2, $3){
							let key = $3 ? $3 : '_index';
							//return '$.each('+$1+', function('+key+', '+$2+'){';
							let str = 'for (var '+key+'=0; '+key+'<'+$1+'.length; '+key+'++) {\n';
							str += 'var '+$2+' = '+$1+'['+key+'];';
							return str;
						});
					}
					//{foreach from=this item=g key=i}
					foreachExp = /^foreach\s+from\s*=\s*([^\s]+)\s+item\s*=\s*(\w+)(?:\s+key\s*=\s*(\w+))?\s*$/g;
					if(line.match(foreachExp)){
						line = line.replace(foreachExp, function(_$, $1, $2, $3){
							let key = $3 ? $3 : '_index';
							//return '$.each('+$1+', function('+key+', '+$2+'){';
							let str = 'for (var '+key+'=0; '+key+'<'+$1+'.length; '+key+'++) {\n';
							str += 'var '+$2+' = '+$1+'['+key+'];';
							return str;
						});
					}
					//if(line === '/foreach')line = '});';
					if(line === '/foreach')line = '}';
					//{for(let i=0; i<this.length; i++)}
					let forExp = /^for\s*\((.+)$/g;
					if(line.match(forExp)){
						line = line.replace(forExp, function(_$, $1){
							let param = $.trim($1);
							if(param.substr(-1) === '{')param = param.substr(0, param.length-1);
							param = $.trim(param).replace(/^(.+)\)$/, '$1');
							return 'for ('+param+') {';
						});
					}
					if(line === '/for')line = '}';
					//{switch (this)} {switch this}
					let switchExp = /^switch\s*(.+?)(\s+autoBreak)?$/g;
					if(line.match(switchExp)){
						hasCase = false;
						line = line.replace(switchExp, function(_$, $1, $2){
							let param = $.trim($1);
							if(param.substr(-1) === '{')param = param.substr(0, param.length-1);
							param = $.trim(param).replace(/^\((.+)\)$/, '$1');
							if($2)switchAutoBreak = true;
							return 'switch ('+param+') {';
						});
					}
					let caseExp = /^case\s+(.+)$/g;
					if(line.match(caseExp)){
						line = line.replace(caseExp, function(_$, $1){
							let param = $.trim($1), breakCode = '';
							if(param.substr(-1) === ':')param = param.substr(0, param.length-1);
							if(switchAutoBreak && hasCase)breakCode = 'break;\n';
							return breakCode+'case '+param+':';
						});
						hasCase = true;
					}
					let defaultExp = /^default\s*:?$/g;
					if(line.match(defaultExp)){
						line = line.replace(defaultExp, ((switchAutoBreak && hasCase)?'break;\n':'')+'default:');
					}
					let breakExp = /^break\s*;?$/g;
					if(line.match(breakExp)){
						line = line.replace(breakExp, 'break;');
					}
					if(line === '/switch'){
						let breakCode = '';
						if(switchAutoBreak)breakCode = 'break;\n';
						switchAutoBreak = false;
						hasCase = false;
						line = breakCode+'}';
					}
					//{if(this>0)} {if this>0}
					let ifExp = /^if\s*(.+)$/g;
					if(line.match(ifExp)){
						line = line.replace(ifExp, function(_$, $1){
							let param = $.trim($1);
							if(param.substr(-1) === '{')param = param.substr(0, param.length-1);
							param = $.trim(param).replace(/^\((.+)\)$/, '$1');
							return 'if ('+param+') {';
						});
					}
					if(line === '/if')line = '}';
					//{else if} {elseif}
					let elseIfExp = /^else\s*if\s*(.+)$/g;
					if(line.match(elseIfExp)){
						line = line.replace(elseIfExp, function(_$, $1){
							let param = $.trim($1);
							if(param.substr(-1) === '{')param = param.substr(0, param.length-1);
							param = $.trim(param).replace(/^\((.+)\)$/, '$1');
							return '} else if ('+param+') {';
						});
					}
					//{else}
					let elseExp = /^else$/g, elseMatcher = line.match(elseExp);
					if(elseMatcher){
						line = '} else {';
					}
					//{return}
					let returnExp = /^return\s*(.+)$/g;
					if(line.match(returnExp)){
						line = line.replace(returnExp, function(_$, $1){
							let param = $.trim($1);
							if(param.substr(-1) === ';')param = param.substr(0, param.length-1);
							return 'return '+param+';';
						});
					}
					//{try}
					let tryExp = /^try$/g;
					if(line.match(tryExp)){
						line = 'try {';
					}
					if(line === '/try')line = '} catch(e) {\nconsole.log(e);\n}';
					//{var variable = true} {let variable = true}
					let varExp = /^(var|let)\s+(.+)$/g;
					if(line.match(varExp)){
						line = line.replace(varExp, function(_$, $1, $2){
							let param = $.trim($2);
							if(param.substr(-1) === ';')param = param.substr(0, param.length-1);
							param = $.trim(param);
							return $1+' '+param+';';
						});
					}
					//{++variable} {--variable}
					let plusExp = /^(\+\+|--)\s*(.+)$/g;
					if(line.match(plusExp)){
						line = line.replace(plusExp, function(_$, $1, $2){
							let param = $.trim($2);
							if(param.substr(-1) === ';')param = param.substr(0, param.length-1);
							param = $.trim(param);
							return $1+param+';';
						});
					}
					code += line+'\n';
				}else{
					code += 'arrRows.push('+line+');\n';
				}
			}
		}
	},
	//加密base64
	/*base64Encode: function(str){
		return window.btoa(str);
	},
	//解密base64
	base64Decode: function(str){
		return window.atob(str);
	},*/
	base64 : function(){
		let BASE64_MAPPING = [
			'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H',
			'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P',
			'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X',
			'Y', 'Z', 'a', 'b', 'c', 'd', 'e', 'f',
			'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n',
			'o', 'p', 'q', 'r', 's', 't', 'u', 'v',
			'w', 'x', 'y', 'z', '0', '1', '2', '3',
			'4', '5', '6', '7', '8', '9', '+', '/'
		];
		let URLSAFE_BASE64_MAPPING = [
			'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H',
			'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P',
			'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X',
			'Y', 'Z', 'a', 'b', 'c', 'd', 'e', 'f',
			'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n',
			'o', 'p', 'q', 'r', 's', 't', 'u', 'v',
			'w', 'x', 'y', 'z', '0', '1', '2', '3',
			'4', '5', '6', '7', '8', '9', '-', '_'
		];
		let _toBinary = function (ascii) {
			let binary = [];
			while (ascii > 0) {
				var b = ascii % 2;
				ascii = Math.floor(ascii / 2);
				binary.push(b);
			}
			binary.reverse();
			return binary;
		};
		let _toDecimal = function (binary) {
			var dec = 0;
			var p = 0;
			for (var i = binary.length - 1; i >= 0; --i) {
				var b = binary[i];
				if (b === 1) {
					dec += Math.pow(2, p);
				}
				++p;
			}
			return dec;
		};
		let _toUTF8Binary = function (c, binaryArray) {
			let mustLen = (8 - (c + 1)) + ((c - 1) * 6);
			let fatLen = binaryArray.length;
			let diff = mustLen - fatLen;
			while (--diff >= 0) {
				binaryArray.unshift(0);
			}
			let binary = [];
			let _c = c;
			while (--_c >= 0) {
				binary.push(1);
			}
			binary.push(0);
			let i = 0, len = 8 - (c + 1);
			for (; i < len; ++i) {
				binary.push(binaryArray[i]);
			}
			for (let j = 0; j < c - 1; ++j) {
				binary.push(1);
				binary.push(0);
				var sum = 6;
				while (--sum >= 0) {
					binary.push(binaryArray[i++]);
				}
			}
			return binary;
		};
		let _toBinaryArray = function (str) {
			let binaryArray = [];
			for (let i = 0, len = str.length; i < len; ++i) {
				let unicode = str.charCodeAt(i);
				let _tmpBinary = _toBinary(unicode);
				if (unicode < 0x80) {
					let _tmpdiff = 8 - _tmpBinary.length;
					while (--_tmpdiff >= 0) {
						_tmpBinary.unshift(0);
					}
					binaryArray = binaryArray.concat(_tmpBinary);
				} else if (unicode >= 0x80 && unicode <= 0x7FF) {
					binaryArray = binaryArray.concat(_toUTF8Binary(2, _tmpBinary));
				} else if (unicode >= 0x800 && unicode <= 0xFFFF) {//UTF-8 3byte
					binaryArray = binaryArray.concat(_toUTF8Binary(3, _tmpBinary));
				} else if (unicode >= 0x10000 && unicode <= 0x1FFFFF) {//UTF-8 4byte
					binaryArray = binaryArray.concat(_toUTF8Binary(4, _tmpBinary));
				} else if (unicode >= 0x200000 && unicode <= 0x3FFFFFF) {//UTF-8 5byte
					binaryArray = binaryArray.concat(_toUTF8Binary(5, _tmpBinary));
				} else if (unicode >= 4000000 && unicode <= 0x7FFFFFFF) {//UTF-8 6byte
					binaryArray = binaryArray.concat(_toUTF8Binary(6, _tmpBinary));
				}
			}
			return binaryArray;
		};
		let _toUnicodeStr = function (binaryArray) {
			let unicode;
			let unicodeBinary = [];
			let str = "";
			for (let i = 0, len = binaryArray.length; i < len;) {
				if (binaryArray[i] === 0) {
					unicode = _toDecimal(binaryArray.slice(i, i + 8));
					str += String.fromCharCode(unicode);
					i += 8;
				} else {
					let sum = 0;
					while (i < len) {
						if (binaryArray[i] === 1) {
							++sum;
						} else {
							break;
						}
						++i;
					}
					unicodeBinary = unicodeBinary.concat(binaryArray.slice(i + 1, i + 8 - sum));
					i += 8 - sum;
					while (sum > 1) {
						unicodeBinary = unicodeBinary.concat(binaryArray.slice(i + 2, i + 8));
						i += 8;
						--sum;
					}
					unicode = _toDecimal(unicodeBinary);
					str += String.fromCharCode(unicode);
					unicodeBinary = [];
				}
			}
			return str;
		};
		let _encode = function (str, url_safe) {
			let base64_Index = [];
			let binaryArray = _toBinaryArray(str);
			let dictionary = url_safe ? URLSAFE_BASE64_MAPPING : BASE64_MAPPING;
			let extra_Zero_Count = 0;
			for (let i = 0, len = binaryArray.length; i < len; i += 6) {
				let diff = (i + 6) - len;
				if (diff == 2) {
					extra_Zero_Count = 2;
				} else if (diff == 4) {
					extra_Zero_Count = 4;
				}
				let _tmpExtra_Zero_Count = extra_Zero_Count;
				while (--_tmpExtra_Zero_Count >= 0) {
					binaryArray.push(0);
				}
				base64_Index.push(_toDecimal(binaryArray.slice(i, i + 6)));
			}
			let base64 = '';
			for (let i = 0, len = base64_Index.length; i < len; ++i) {
				base64 += dictionary[base64_Index[i]];
			}
			for (let i = 0, len = extra_Zero_Count / 2; i < len; ++i) {
				base64 += '=';
			}
			return base64;
		};
		let _decode = function (_base64Str, url_safe) {
			let _len = _base64Str.length;
			let extra_Zero_Count = 0;
			let dictionary = url_safe ? URLSAFE_BASE64_MAPPING : BASE64_MAPPING;
			if (_base64Str.charAt(_len - 1) === '=') {
				if (_base64Str.charAt(_len - 2) === '=') {//两个等号说明补了4个0
					extra_Zero_Count = 4;
					_base64Str = _base64Str.substring(0, _len - 2);
				} else {//一个等号说明补了2个0
					extra_Zero_Count = 2;
					_base64Str = _base64Str.substring(0, _len - 1);
				}
			}
			let binaryArray = [];
			for (let i = 0, len = _base64Str.length; i < len; ++i) {
				let c = _base64Str.charAt(i);
				for (let j = 0, size = dictionary.length; j < size; ++j) {
					if (c === dictionary[j]) {
						let _tmp = _toBinary(j);
						/*不足6位的补0*/
						let _tmpLen = _tmp.length;
						if (6 - _tmpLen > 0) {
							for (let k = 6 - _tmpLen; k > 0; --k) {
								_tmp.unshift(0);
							}
						}
						binaryArray = binaryArray.concat(_tmp);
						break;
					}
				}
			}
			if (extra_Zero_Count > 0) {
				binaryArray = binaryArray.slice(0, binaryArray.length - extra_Zero_Count);
			}
			let str = _toUnicodeStr(binaryArray);
			return str;
		};
		return {
			encode: function (str) {
				return _encode(str, false);
			},
			decode: function (base64Str) {
				return _decode(base64Str, false);
			}
		};
	},
	//加密base64
	base64Encode : function(str){
		let c1, c2, c3, base64EncodeChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';
		let i = 0, len= str.length, string = '';
		while(i < len){
			c1 = str.charCodeAt(i++) & 0xff;
			if(i === len){
				string += base64EncodeChars.charAt(c1 >> 2);
				string += base64EncodeChars.charAt((c1 & 0x3) << 4);
				string += '==';
				break;
			}
			c2 = str.charCodeAt(i++);
			if(i === len){
				string += base64EncodeChars.charAt(c1 >> 2);
				string += base64EncodeChars.charAt(((c1 & 0x3) << 4) | ((c2 & 0xF0) >> 4));
				string += base64EncodeChars.charAt((c2 & 0xF) << 2);
				string += '=';
				break;
			}
			c3 = str.charCodeAt(i++);
			string += base64EncodeChars.charAt(c1 >> 2);
			string += base64EncodeChars.charAt(((c1 & 0x3) << 4) | ((c2 & 0xF0) >> 4));
			string += base64EncodeChars.charAt(((c2 & 0xF) << 2) | ((c3 & 0xC0) >> 6));
			string += base64EncodeChars.charAt(c3 & 0x3F);
		}
		return string;
	},
	//解密base64
	base64Decode : function(str){
		let c1, c2, c3, c4, base64DecodeChars = [
			-1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,
			-1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,
			-1, -1, -1, -1, -1, -1, -1, 62, -1, -1, -1, 63, 52, 53, 54, 55, 56, 57,
			58, 59, 60, 61, -1, -1, -1, -1, -1, -1, -1, 0,  1,  2,  3,  4,  5,  6,
			7,  8,  9,  10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24,
			25, -1, -1, -1, -1, -1, -1, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36,
			37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51, -1, -1, -1,
			-1, -1
		];
		let i = 0, len = str.length, string = '';
		while(i < len){
			do{
				c1 = base64DecodeChars[str.charCodeAt(i++) & 0xff]
			}while(i < len && c1 === -1);
			if (c1 === -1) break;
			do{
				c2 = base64DecodeChars[str.charCodeAt(i++) & 0xff]
			}while(i < len && c2 === -1);
			if (c2 === -1) break;
			string += String.fromCharCode((c1 << 2) | ((c2 & 0x30) >> 4));
			do{
				c3 = str.charCodeAt(i++) & 0xff;
				if(c3 === 61)return string;
				c3 = base64DecodeChars[c3]
			}while(i < len && c3 === -1);
			if (c3 === -1) break;
			string += String.fromCharCode(((c2 & 0XF) << 4) | ((c3 & 0x3C) >> 2));
			do{
				c4 = str.charCodeAt(i++) & 0xff;
				if(c4 === 61)return string;
				c4 = base64DecodeChars[c4]
			}while(i < len && c4 === -1);
			if (c4 === -1) break;
			string += String.fromCharCode(((c3 & 0x03) << 6) | c4);
		}
		return string;
	},
	//MD5加密
	md5: function(str){
		let hexcase=0;let chrsz=8;function hex_md5(s){return binl2hex(core_md5(str2binl(s),s.length*chrsz))}function core_md5(x,len){x[len>>5]|=0x80<<((len)%32);x[(((len+64)>>>9)<<4)+14]=len;let a=1732584193;let b=-271733879;let c=-1732584194;let d=271733878;for(let i=0;i<x.length;i+=16){let olda=a;let oldb=b;let oldc=c;let oldd=d;a=md5_ff(a,b,c,d,x[i],7,-680876936);d=md5_ff(d,a,b,c,x[i+1],12,-389564586);c=md5_ff(c,d,a,b,x[i+2],17,606105819);b=md5_ff(b,c,d,a,x[i+3],22,-1044525330);a=md5_ff(a,b,c,d,x[i+4],7,-176418897);d=md5_ff(d,a,b,c,x[i+5],12,1200080426);c=md5_ff(c,d,a,b,x[i+6],17,-1473231341);b=md5_ff(b,c,d,a,x[i+7],22,-45705983);a=md5_ff(a,b,c,d,x[i+8],7,1770035416);d=md5_ff(d,a,b,c,x[i+9],12,-1958414417);c=md5_ff(c,d,a,b,x[i+10],17,-42063);b=md5_ff(b,c,d,a,x[i+11],22,-1990404162);a=md5_ff(a,b,c,d,x[i+12],7,1804603682);d=md5_ff(d,a,b,c,x[i+13],12,-40341101);c=md5_ff(c,d,a,b,x[i+14],17,-1502002290);b=md5_ff(b,c,d,a,x[i+15],22,1236535329);a=md5_gg(a,b,c,d,x[i+1],5,-165796510);d=md5_gg(d,a,b,c,x[i+6],9,-1069501632);c=md5_gg(c,d,a,b,x[i+11],14,643717713);b=md5_gg(b,c,d,a,x[i],20,-373897302);a=md5_gg(a,b,c,d,x[i+5],5,-701558691);d=md5_gg(d,a,b,c,x[i+10],9,38016083);c=md5_gg(c,d,a,b,x[i+15],14,-660478335);b=md5_gg(b,c,d,a,x[i+4],20,-405537848);a=md5_gg(a,b,c,d,x[i+9],5,568446438);d=md5_gg(d,a,b,c,x[i+14],9,-1019803690);c=md5_gg(c,d,a,b,x[i+3],14,-187363961);b=md5_gg(b,c,d,a,x[i+8],20,1163531501);a=md5_gg(a,b,c,d,x[i+13],5,-1444681467);d=md5_gg(d,a,b,c,x[i+2],9,-51403784);c=md5_gg(c,d,a,b,x[i+7],14,1735328473);b=md5_gg(b,c,d,a,x[i+12],20,-1926607734);a=md5_hh(a,b,c,d,x[i+5],4,-378558);d=md5_hh(d,a,b,c,x[i+8],11,-2022574463);c=md5_hh(c,d,a,b,x[i+11],16,1839030562);b=md5_hh(b,c,d,a,x[i+14],23,-35309556);a=md5_hh(a,b,c,d,x[i+1],4,-1530992060);d=md5_hh(d,a,b,c,x[i+4],11,1272893353);c=md5_hh(c,d,a,b,x[i+7],16,-155497632);b=md5_hh(b,c,d,a,x[i+10],23,-1094730640);a=md5_hh(a,b,c,d,x[i+13],4,681279174);d=md5_hh(d,a,b,c,x[i],11,-358537222);c=md5_hh(c,d,a,b,x[i+3],16,-722521979);b=md5_hh(b,c,d,a,x[i+6],23,76029189);a=md5_hh(a,b,c,d,x[i+9],4,-640364487);d=md5_hh(d,a,b,c,x[i+12],11,-421815835);c=md5_hh(c,d,a,b,x[i+15],16,530742520);b=md5_hh(b,c,d,a,x[i+2],23,-995338651);a=md5_ii(a,b,c,d,x[i],6,-198630844);d=md5_ii(d,a,b,c,x[i+7],10,1126891415);c=md5_ii(c,d,a,b,x[i+14],15,-1416354905);b=md5_ii(b,c,d,a,x[i+5],21,-57434055);a=md5_ii(a,b,c,d,x[i+12],6,1700485571);d=md5_ii(d,a,b,c,x[i+3],10,-1894986606);c=md5_ii(c,d,a,b,x[i+10],15,-1051523);b=md5_ii(b,c,d,a,x[i+1],21,-2054922799);a=md5_ii(a,b,c,d,x[i+8],6,1873313359);d=md5_ii(d,a,b,c,x[i+15],10,-30611744);c=md5_ii(c,d,a,b,x[i+6],15,-1560198380);b=md5_ii(b,c,d,a,x[i+13],21,1309151649);a=md5_ii(a,b,c,d,x[i+4],6,-145523070);d=md5_ii(d,a,b,c,x[i+11],10,-1120210379);c=md5_ii(c,d,a,b,x[i+2],15,718787259);b=md5_ii(b,c,d,a,x[i+9],21,-343485551);a=safe_add(a,olda);b=safe_add(b,oldb);c=safe_add(c,oldc);d=safe_add(d,oldd)}return Array(a,b,c,d)}function md5_cmn(q,a,b,x,s,t){return safe_add(bit_rol(safe_add(safe_add(a,q),safe_add(x,t)),s),b)}function md5_ff(a,b,c,d,x,s,t){return md5_cmn((b&c)|((~b)&d),a,b,x,s,t)}function md5_gg(a,b,c,d,x,s,t){return md5_cmn((b&d)|(c&(~d)),a,b,x,s,t)}function md5_hh(a,b,c,d,x,s,t){return md5_cmn(b^c^d,a,b,x,s,t)}function md5_ii(a,b,c,d,x,s,t){return md5_cmn(c^(b|(~d)),a,b,x,s,t)}function safe_add(x,y){let lsw=(x&0xFFFF)+(y&0xFFFF);let msw=(x>>16)+(y>>16)+(lsw>>16);return(msw<<16)|(lsw&0xFFFF)}function bit_rol(num,cnt){return(num<<cnt)|(num>>>(32-cnt))}function str2binl(str){let bin=Array();let mask=(1<<chrsz)-1;for(let i=0;i<str.length*chrsz;i+=chrsz)bin[i>>5]|=(str.charCodeAt(i/chrsz)&mask)<<(i%32);return bin}function binl2hex(binarray){let hex_tab=hexcase?'0123456789ABCDEF':'0123456789abcdef';let str="";for(let i=0;i<binarray.length*4;i++){str+=hex_tab.charAt((binarray[i>>2]>>((i%4)*8+4))&0xF)+hex_tab.charAt((binarray[i>>2]>>((i%4)*8))&0xF)}return str}return hex_md5(str);
	},
	//浏览器本地存储, time:单位天,默认一天
	//$.localStorage(); 返回window.localStorage
	//$.localStorage('key'); 获取
	//$.localStorage('key', 'data'); 设置
	//$.localStorage('key', 'data', 1/24); 设置,过期时间为1小时
	//$.localStorage('key', null); 删除
	//$.localStorage(null); 删除所有
	localStorage: function(key, data, time){
		if(typeof key === 'undefined')return window.localStorage;
		if(key === null){
			if(window.localStorage){
				for(let i=0; i<window.localStorage.length; i++){
					if((window.localStorage.key(i).split('_') || [''])[0] === 'storage'){
						window.localStorage.removeItem(name);
					}
				}
			}
			return null;
		}
		key = {data:'storage_data_'+encodeURIComponent(key), time:'storage_time_'+encodeURIComponent(key)};
		if(window.localStorage){
			if(typeof data === 'undefined'){
				data = window.localStorage.getItem(key.data);
				if(data){
					if(Number(window.localStorage.getItem(key.time)) > (new Date()).getTime()){
						//data = $.json(data);
						return data;
					}else{
						window.localStorage.removeItem(key.data);
						window.localStorage.removeItem(key.time);
					}
				}
			}else if(data === null){
				window.localStorage.removeItem(key.data);
				window.localStorage.removeItem(key.time);
			}else{
				if(typeof time === 'undefined')time = 1;
				time = (new Date()).getTime() + Number(time) * 24*60*60*1000;
				if(typeof data !== 'string')data = $.jsonString(data);
				window.localStorage.setItem(key.data, data);
				window.localStorage.setItem(key.time, time);
			}
		}else{
			if(typeof data === 'undefined'){
				data = $.cookie(key.data);
				if(data){
					if(Number($.cookie(key.time)) > (new Date()).getTime()){
						//data = $.json(data);
						return data;
					}else{
						$.cookie(key.data, null);
						$.cookie(key.time, null);
					}
				}
			}else if(data === null){
				$.cookie(key.data, null);
				$.cookie(key.time, null);
			}else{
				if(typeof time === 'undefined')time = 1;
				if(typeof data !== 'string')data = $.jsonString(data);
				$.cookie(key.data, data, {expires:time});
				$.cookie(key.time, time, {expires:time});
			}
		}
		return null;
	},
	/*
	$.cookie(); //返回document.cookie
	$.cookie('name'); //获取
	$.cookie('name', 'value'); //保存
	$.cookie('name', 'value', { expires:7, path:'/', domain:'jquery.com', secure:true }); //保存带有效期(单位天),路径,域名,安全协议
	$.cookie('name', '', { expires:-1 }); or $.cookie('name', null); //删除
	*/
	cookie: function(name, value, options){
		if(typeof name === 'undefined')return document.cookie;
		if(typeof value !== 'undefined'){
			options = options || {};
			if(value === null){
				value = '';
				options.expires = -1;
			}
			if(typeof value !== 'string')value = $.jsonString(value);
			let expires = '';
			if($.isNumeric(options)){
				let date = new Date();
				date.setTime(date.getTime() + (options * 24*60*60*1000) + (8*60*60*1000));
				expires = ';expires='+date.toUTCString();
				options = {};
			}else if(options.expires && (typeof options.expires === 'number' || options.expires.toUTCString)){
				let date = '';
				if(typeof options.expires === 'number'){
					date = new Date();
					date.setTime(date.getTime() + (options.expires * 24*60*60*1000) + (8*60*60*1000));
				}else{
					date = options.expires;
				}
				expires = ';expires=' + date.toUTCString();
			}
			let path = options.path ? ';path='+options.path : '';
			let domain = options.domain ? ';domain='+options.domain : '';
			let secure = options.secure ? ';secure' : '';
			document.cookie = [name, '=', value, expires, path, domain, secure].join('');
			return true;
		}else{
			value = null;
			if(document.cookie.length){
				let cookies = document.cookie.split(';');
				for(let i=0; i<cookies.length; i++){
					let cookie = $.trim(cookies[i]);
					if(cookie.substring(0, name.length+1) === (name + '=')){
						value = cookie.substring(name.length+1);
						break;
					}
				}
			}
			return value;
		}
	},
	debugHTML: function(cls, width, height){
		if(typeof cls === 'undefined')cls = 'debugHTML';
		let ele = $('.'+cls);
		if(!ele.length){
			width = typeof width === 'undefined' ? '100%' : width + 'px';
			height = typeof height === 'undefined' ? 'auto' : height + 'px';
			ele = $('<div class="'+cls+'" style="position:fixed;left:0;top:0;z-index:99999;padding:8px 5px;font-size:12px;text-align:left;box-shadow:0 0 20px rgba(0,0,0,0.5);background:rgba(255,255,255,0.85);color:#333;width:'+width+';height:'+height+';"></div>');
			$(document.body).append(ele);
		}
		return ele;
	}
});

})(jQuery);

//兼容浏览器支持console
if(typeof window.console === 'undefined')window.console = {log: function(){}};

//兼容浏览器支持localStorage
if(typeof window.localStorage === 'undefined'){
	let localStorageClass = function(){
		this.options = {
			expires: 365,
			domain: location.hostname
		}
	};
	localStorageClass.prototype = {
		getKey: function(key){
			return 'cache_data_' + encodeURIComponent(key);
		},
		key: function(i){
			let keys = [], cookies = document.cookie.split(';');
			for(let _i=0; _i<cookies.length; _i++){
				let cookie = $.trim(cookies[_i]);
				keys.push((cookie.split('=') || [''])[0]);
			}
			if(i<0 || i>=keys.length)return '';
			return keys[i];
		},
		//获取,不存在返回null
		getItem: function(key){
			return $.cookie(this.getKey(key));
		},
		//设置
		setItem: function(key, value){
			$.cookie(this.getKey(key), value, this.options);
		},
		//删除
		removeItem: function(key){
			$.cookie(this.getKey(key), null);
		},
		//删除所有
		clear: function(){
			let cookies = document.cookie.split(';');
			for(let i=0; i<cookies.length; i++){
				let cookie = $.trim(cookies[i]);
				if((cookie.split('_') || [''])[0] === 'cache')$.cookie((cookie.split('='))[0], null);
			}
		}
	};
	window.localStorage = new localStorageClass();
}

//兼容浏览器支持atob、btoa
if(typeof window.atob === 'undefined'){
	window.atob = function(str){
		let c1, c2, c3, base64EncodeChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';
		let i = 0, len= str.length, string = '';
		while(i < len){
			c1 = str.charCodeAt(i++) & 0xff;
			if(i === len){
				string += base64EncodeChars.charAt(c1 >> 2);
				string += base64EncodeChars.charAt((c1 & 0x3) << 4);
				string += '==';
				break;
			}
			c2 = str.charCodeAt(i++);
			if(i === len){
				string += base64EncodeChars.charAt(c1 >> 2);
				string += base64EncodeChars.charAt(((c1 & 0x3) << 4) | ((c2 & 0xF0) >> 4));
				string += base64EncodeChars.charAt((c2 & 0xF) << 2);
				string += '=';
				break;
			}
			c3 = str.charCodeAt(i++);
			string += base64EncodeChars.charAt(c1 >> 2);
			string += base64EncodeChars.charAt(((c1 & 0x3) << 4) | ((c2 & 0xF0) >> 4));
			string += base64EncodeChars.charAt(((c2 & 0xF) << 2) | ((c3 & 0xC0) >> 6));
			string += base64EncodeChars.charAt(c3 & 0x3F);
		}
		return string;
	};
	window.btoa = function(str){
		let c1, c2, c3, c4, base64DecodeChars = [
			-1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,
			-1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,
			-1, -1, -1, -1, -1, -1, -1, 62, -1, -1, -1, 63, 52, 53, 54, 55, 56, 57,
			58, 59, 60, 61, -1, -1, -1, -1, -1, -1, -1, 0,  1,  2,  3,  4,  5,  6,
			7,  8,  9,  10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24,
			25, -1, -1, -1, -1, -1, -1, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36,
			37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51, -1, -1, -1,
			-1, -1];
		let i = 0, len = str.length, string = '';
		while(i < len){
			do{
				c1 = base64DecodeChars[str.charCodeAt(i++) & 0xff]
			}while(i < len && c1 === -1);
			if (c1 === -1) break;
			do{
				c2 = base64DecodeChars[str.charCodeAt(i++) & 0xff]
			}while(i < len && c2 === -1);
			if (c2 === -1) break;
			string += String.fromCharCode((c1 << 2) | ((c2 & 0x30) >> 4));
			do{
				c3 = str.charCodeAt(i++) & 0xff;
				if(c3 === 61)return string;
				c3 = base64DecodeChars[c3]
			}while(i < len && c3 === -1);
			if (c3 === -1) break;
			string += String.fromCharCode(((c2 & 0XF) << 4) | ((c3 & 0x3C) >> 2));
			do{
				c4 = str.charCodeAt(i++) & 0xff;
				if(c4 === 61)return string;
				c4 = base64DecodeChars[c4]
			}while(i < len && c4 === -1);
			if (c4 === -1) break;
			string += String.fromCharCode(((c3 & 0x03) << 6) | c4);
		}
		return string;
	};
}

//兼容浏览器支持Object.keys
if(typeof Object.keys === 'undefined'){
	Object.defineProperty(Object.prototype, 'keys', {
		value: function(object){
			let keys = [];
			for(let key in object){
				if(typeof key !== 'function' && object.hasOwnProperty(key))keys.push(key);
			}
			return keys;
		},
		enumerable: false
	});
}

//px2rem
Number.prototype.rem = function(){
	let style = $('html').attr('style'), _this = $.unit(this);
	if(!!!style || style.indexOf('font-size') === -1)return _this + 'px';
	let fontSize = $.unit($('html').css('font-size'));
	return (_this/fontSize) + 'rem';
};

//去除首尾空格
String.prototype.trim = function(symbol){
	if(typeof symbol === 'undefined' || !symbol.length)symbol = '\\s';
	symbol = symbol.replace(/([()\[\]*.?|^$]\\)/g, '\\$1');
	return this.replace(new RegExp('(^'+symbol+'*)|('+symbol+'*$)', 'g'), '');
};

//URL编码
String.prototype.urlencode = function(){
	if(!this.length)return '';
	return encodeURIComponent(this).replace(/!/g, '%21').replace(/'/g, '%27').replace(/\(/g, '%28').replace(/\)/g, '%29').replace(/\*/g, '%2A').replace(/%20/g, '+');
};
String.prototype.urldecode = function(){
	if(!this.length)return '';
	return decodeURIComponent(this);
};

//字符串转JSON
String.prototype.json = function(){
	if(!this.length)return null;
	return $.json(this);
};

//检测是否为空
String.prototype.isEmpty = function(){
	return $.trim(this).length === 0;
};

//检测中文
String.prototype.isCN = function(){
	return /^[\u4e00-\u9fa5]+$/.test(this);
};

//检测邮箱
String.prototype.isEmail = function(){
	return /^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/.test(this);
};

//检测固话
String.prototype.isTel = function(){
	return /^((\d{3,4}-)?\d{8}(-\d+)?|(\(\d{3,4}\))?\d{8}(-\d+)?)$/.test(this);
};

//检测手机
String.prototype.isMobile = function(){
	return /^(\+?86)?(13|15|18)[0-9]\d{8}$/.test(this);
};

//检测网址
String.prototype.isUrl = function(){
	return /^((http|https|ftp):\/\/)?[\w\-_]+(\.[\w\-_]+)+([\w\-.,@?^=%&:\/~+#]*[\w\-@?^=%&\/~+#])?$/.test(this);
};

//检测日期
String.prototype.isDate = function(){
	return /^(?:(?!0000)[0-9]{4}[\/-](?:(?:0?[1-9]|1[0-2])[\/-](?:0?[1-9]|1[0-9]|2[0-8])|(?:0?[13-9]|1[0-2])[\/-](?:29|30)|(?:0?[13578]|1[02])[\/-]31)|(?:[0-9]{2}(?:0[48]|[2468][048]|[13579][26])|(?:0[48]|[2468][048]|[13579][26])00)[\/-]0?2[\/-]29)$/.test(this);
};

//截取字符串
String.prototype.cutFont = function(num){
	let x = 0;
	return $.trim(this).replace(/[\s\S]/g, function(m, i){
		if(m.charCodeAt(0)>127)x++;
		if(x+i>=num)return '';
		return m;
	});
};

//填充前导零
String.prototype.fillZero = function(prec){
	let _this = this;
	for(let i=0; i<prec; i++)_this = '0'+''+_this;
	return _this.substr(-prec);
};
Number.prototype.fillZero = function(prec){
	let _this = this+'';
	return _this.fillZero(prec);
};

//保留小数
String.prototype.round = function(prec){
	if(isNaN(this)){
		return this.cutFont(prec);
	}else{
		let _this = this * 1;
		return _this.round(prec);
	}
};
Number.prototype.round = function(prec){
	prec = !isNaN(prec=Math.abs(prec)) ? prec : 0;
	return Math.round(this * Math.pow(10,prec)) / Math.pow(10,prec);
	//Math.ceil() 小数进一
	//Math.floor() 取整数部分
	//Math.round() 四舍五入
};
String.prototype.numberFormat = function(prec){ //保留小数后转为字符串
	let _this = this * 1;
	return _this.numberFormat(prec);
};
Number.prototype.numberFormat = function(prec){
	let _this = this.round(prec) + '', arr = _this.split('.'), decimal = '';
	for(let i=0; i<prec; i++)decimal += '0';
	if(arr.length>1)decimal = arr[1] + decimal;
	decimal = decimal.substr(0, prec);
	decimal = prec<=0 ? '' : '.' + decimal;
	return arr[0] + decimal;
};

//时间戳转日期
String.prototype.toDate = function(formatStr){
	let number = this * 1;
	return number.toDate(formatStr);
};
Number.prototype.toDate = function(formatStr){
	let date = new Date(this * 1000);
	if(typeof formatStr === 'undefined'){
		return date;
	}else{
		return date.formatDate(formatStr);
	}
};

//日期转时间戳
String.prototype.time = function(){
	let date = this.date();
	return date.time();
};
Date.prototype.time = function(){
	return this.getTime()/1000;
};

//日期字符串转日期
String.prototype.date = function(){
	let m = /^(?:(\d{4})-(\d{1,2})(?:-(\d{1,2}))?)?(?: ?(\d{1,2}))?(?::(\d{1,2}))?(?::(\d{1,2}))?$/.exec(this);
	if(!m || !m.length)return null;
	let date = this.split(/\D/);
	if(m[1]){
		--date[1];
	}else{
		let d = new Date();
		date.unshift(String(d.getDate()));
		date.unshift(String(d.getMonth()));
		date.unshift(String(d.getFullYear()));
	}
	let count = date.length;
	for(let i=0; i<6-count; i++){
		date.push('0');
	}
	for(let i=0; i<date.length; i++){
		date[i] = (i === 2 && Number(date[i])<=0) ? 1 : Number(date[i]);
	}
	date = evil('new Date('+date.join(',')+')');
	return date;
};

//加上天数得到日期
String.prototype.dateAdd = function(t, number){
	let date = this.date();
	return date.dateAdd(t, number);
};
Date.prototype.dateAdd = function(t, number){
	number = parseInt(number);
	let date = this.Clone();
	switch (t) {
		case 's':return new Date(Date.parse(date) + (1000 * number));
		case 'n':return new Date(Date.parse(date) + (60000 * number));
		case 'h':return new Date(Date.parse(date) + (3600000 * number));
		case 'd':return new Date(Date.parse(date) + (86400000 * number));
		case 'w':return new Date(Date.parse(date) + ((86400000 * 7) * number));
		case 'q':return new Date(date.getFullYear(), (date.getMonth()) + number*3, date.getDate(), date.getHours(), date.getMinutes(), date.getSeconds());
		case 'm':return new Date(date.getFullYear(), (date.getMonth()) + number, date.getDate(), date.getHours(), date.getMinutes(), date.getSeconds());
		case 'y':return new Date((date.getFullYear() + number), date.getMonth(), date.getDate(), date.getHours(), date.getMinutes(), date.getSeconds());
	}
	return date;
};

//减去天数得到日期
String.prototype.dateDiff = function(t, number){
	let date = this.date();
	return date.dateDiff(t, number);
};
Date.prototype.dateDiff = function(t, number){
	let d = this.Clone(), k = { 'd':24*60*60*1000, 'h':60*60*1000, 'n':60*1000, 's':1000 };
	d = d.getTime();
	d = d - number * k[t];
	return new Date(d);
};

//两个日期的时间差
String.prototype.dateDiffNum = function(t, dtEnd){
	let date = this.date();
	return date.dateDiffNum(t, dtEnd);
};
Date.prototype.dateDiffNum = function(t, dtEnd){
	let dtStart = this.Clone();
	if(typeof dtEnd === 'string')dtEnd = dtEnd.date();
	switch(t){
		case 'y':return dtEnd.getFullYear() - dtStart.getFullYear();
		case 'm':return (dtEnd.getMonth() + 1) + ((dtEnd.getFullYear() - dtStart.getFullYear()) * 12) - (dtStart.getMonth() + 1);
		case 'd':return parseInt(String((dtEnd - dtStart) / 86400000));
		case 'w':return parseInt(String((dtEnd - dtStart) / (86400000 * 7)));
		case 'h':return parseInt(String((dtEnd - dtStart) / 3600000));
		case 'n':return parseInt(String((dtEnd - dtStart) / 60000));
		case 's':return parseInt(String((dtEnd - dtStart) / 1000));
	}
	return 0;
};

//日期格式化, callback:接受1个参数date(date为对象字面量,包含year, month, day, hour, minute, second, week)
String.prototype.formatDate = function(formatStr, callback){
	let date = this.date();
	return date.formatDate(formatStr, callback);
};
Number.prototype.formatDate = function(formatStr, callback){
	let date = this.toDate();
	return date.formatDate(formatStr, callback);
};
Date.prototype.formatDate = function(formatStr, callback){
	let format = formatStr ? formatStr : 'yyyy-mm-dd hh:nn:ss',
		monthName = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
		monthFullName = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
		weekName = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
		weekFullName = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
		monthNameCn = ['一月', '二月', '三月', '四月', '五月', '六月', '七月', '八月', '九月', '十月', '十一月', '十二月'],
		monthFullNameCn = monthNameCn,
		weekNameCn = ['日', '一', '二', '三', '四', '五', '六'],
		weekFullNameCn = ['星期天', '星期一', '星期二', '星期三', '星期四', '星期五', '星期六'],
		getYearWeek = function(y, m, d){
			let dat = new Date(y, m, d), firstDay = new Date(y, 0, 1),
				day = Math.round((dat.valueOf()-firstDay.valueOf()) / 86400000);
			return Math.ceil( (day + ((firstDay.getDay()+1)-1)) / 7 );
		},
		year = this.getFullYear()+'', month = (this.getMonth()+1)+'', day = this.getDate()+'', week = this.getDay(),
		hour = this.getHours()+'', minute = this.getMinutes()+'', second = this.getSeconds()+'',
		yearWeek = getYearWeek(this.getFullYear(), this.getMonth(), this.getDate())+'';
	format = format.replace(/yyyy/g, year);
	format = format.replace(/yy/g, (this.getYear()%100)>9 ? (this.getYear()%100)+'' : '0'+(this.getYear()%100));
	format = format.replace(/Y/g, year);
	format = format.replace(/mme/g, monthFullName[month-1]);
	format = format.replace(/me/g, monthName[month-1]);
	format = format.replace(/mmc/g, monthFullNameCn[month-1]);
	format = format.replace(/mc/g, monthNameCn[month-1]);
	format = format.replace(/mm/g, month.length<2?'0'+month:month);
	format = format.replace(/m/g, month);
	format = format.replace(/dd/g, day.length<2?'0'+day:day);
	format = format.replace(/d/g, day);
	format = format.replace(/hh/g, hour.length<2?'0'+hour:hour);
	format = format.replace(/h/g, hour);
	format = format.replace(/H/g, hour);
	format = format.replace(/G/g, hour);
	format = format.replace(/nn/g, minute.length<2?'0'+minute:minute);
	format = format.replace(/n/g, minute);
	format = format.replace(/ii/g, minute.length<2?'0'+minute:minute);
	format = format.replace(/i/g, minute);
	format = format.replace(/ss/g, second.length<2?'0'+second:second);
	format = format.replace(/s/g, second);
	format = format.replace(/wwe/g, weekFullName[week]);
	format = format.replace(/we/g, weekName[week]);
	format = format.replace(/ww/g, weekFullNameCn[week]);
	format = format.replace(/w/g, weekNameCn[week]);
	format = format.replace(/WW/g, yearWeek.length<2?'0'+yearWeek:yearWeek);
	format = format.replace(/W/g, yearWeek);
	format = format.replace(/a/g, hour<12?'am':'pm');
	format = format.replace(/A/g, hour<12?'AM':'PM');
	if($.isFunction(callback))callback.call(this, {year:year, month:month, day:day, hour:hour, minute:minute, second:second, week:week});
	return format;
	//d.toLocaleDateString() 获取当前日期
	//d.toLocaleTimeString() 获取当前时间
	//d.toLocaleString() 获取日期与时间
};

//友好时间形式
String.prototype.timeWord = function(){
	let date = this.date();
	if(!date)return this;
	return date.timeWord();
};
Date.prototype.timeWord = function(){
	let date = this, d1 = date.getTime(), d2 = new Date().getTime(), between = Math.floor(d2/1000) - Math.floor(d1/1000);
	if(between < 60)return '刚刚';
	if(between < 3600)return Math.floor(between/60) + '分钟前';
	if(between < 86400)return Math.floor(between/3600) + '小时前';
	if(between <= 864000)return Math.floor(between/86400) + '天前';
	if(between > 864000)return this.formatDate('yyyy-mm-dd');
};

//日期复制
Date.prototype.Clone = function(){return new Date(this.valueOf())};

//搜索数组元素
if(!Array.indexOf){
	Array.prototype.indexOf = function(obj){
		for(let i=0; i<this.length; i++){
			if(this[i] === obj)return i;
		}
		return -1;
	};
}

//代替eval
function evil(fn){
	let Func = Function; //一个变量指向Function,防止有些前端编译工具报错
	return new Func('return '+fn).apply(this, arguments);
}

function setCoo(){
	let $ = window.jQuery;
	let e = ('click,dblclick,change,focus,blur,keydown,keyup,mousedown,mousemove,mouseover,mouseup,scroll,submit,input,touchstart,touchmove,touchend,ajax').split(',');
	$.each(e, function(){
		let _this = this+'', ev = _this;
		if(ev === 'input')ev = 'input propertychange';
		if(ev === 'click'){
			if($.browser.mobile){
				$(document.body).on('touchend', '[coo-'+_this+']:not(a)', function(e){execute.call(this, e)});
				$(document.body).on('click', 'a[coo-'+_this+']', function(e){execute.call(this, e)});
			}else{
				$(document.body).on('click', '[coo-'+_this+']', function(e){execute.call(this, e)});
			}
		}else if(ev === 'ajax'){
			//<input type="checkbox" value="1" coo-ajax="post" data-url="/gm/api/article/recommend" data-data="{ id:{$row->id}, recommend:this.checked }" />
			$(document.body).on('change', '[coo-'+_this+']', function(){
				let ele = $(this), type = ele.attr('coo-'+_this).toLowerCase(), url = ele.attr('data-url'), data = ele.attr('data-data'), callback = ele.attr('data-callback');
				if(!!data && data.length){
					if(ele.is('select')){
						let value = ele.selected().attr('value') || '';
						data = data.replace(/this\.selected/g, value.length ? value : '""');
					}else{
						let value = ele.val();
						data = data.replace(/this\.checked/g, this.checked ? 1 : 0).replace(/this\.value/g, this.checked ? value : '""');
					}
					data = data.replace(/\b(\w+)\s*:/g, '"$1":').replace(/\b'(\w+)'\s*:/g, '"$1":');
					data = data.replace(/:\s*(.+?)(\s*,|\s*}|$)/g, function(_$, $1, $2){
						if(!isNaN($1))return _$;
						if(/^"/.test($1))return _$;
						if(/^`(.+)`$/.test($1))return ':"'+$1.replace(/^`(.+)`$/, '$1')+'"'+$2;
						if(/^'(.+)'$/.test($1))return ':"'+$1.replace(/^'(.+)'$/, '$1')+'"'+$2;
						return ':"'+$1+'"'+$2;
					});
					if($.isJsonString(data)){
						data = $.json(data);
					}else{
						data = null;
					}
				}
				if(type === 'get'){
					$.getJSON(url, function(json){
						if(!!callback){
							let fn = evil(callback);
							if($.isFunction(fn))fn.call(ele, json);
						}
					});
				}else if(type === 'post'){
					$.postJSON(url, data ? data : {}, function(json){
						if(!!callback){
							let fn = evil(callback);
							if($.isFunction(fn))fn.call(ele, json);
						}
					});
				}
			});
		}else{
			$(document.body).on(ev, '[coo-'+_this+']', function(e){execute.call(this, e)});
		}
		function execute(e){
			let _t = $(this), fn = _t.attr('coo-'+_this);
			if(fn.length){
				if(fn.indexOf('function(')>-1){
					let f = evil(fn);
					if($.isFunction(f))f.call(this, e);
				}else{
					let r = fn.match(/^(\w+)(\((.*?)\))?;?$/), f = r[1], v = r[3] ? ', '+r[3] : '';
					if($.hasFunction(f))evil(f+'.call(arguments[1]'+v+', arguments[2])', this, e);
				}
			}
		}
	});
}

if($.browser.mobile)window.eventType = 'touchend';
$(function(){
	//正则replace内的函数参数为function(匹配到的字符,$1,$2,...,出现的位置[整数],字符本身)
	//replace补充说明: 如果正则存在子表达式匹配, 如 /ab(\w+?)ef/, 函数有三个参数, 如 function(m,i,s), 那么 i:子表达式匹配的字符串, s:出现的位置, 如此向后推
	$(document.body).on(window.eventType, 'a.return:not([skip])', function(e){
		e.preventDefault();
		history.go(-1);
		return false;
	});
	$(document.body).on(window.eventType, 'a.delete:not([skip])', function(e){
		e.preventDefault();
		let q = '真的要删除?';
		if(!!$(this).attr('confirm'))q = $(this).attr('confirm');
		if(!confirm(q))return false;
		let warning = $(this).attr('warning');
		if(!!warning){$.overloadError(warning);return false}
		let link = $(this).attr('href');
		if(!link.length || link.indexOf('javascript:')>-1)return true;
		window.location = link;
		return false;
	});
	$(document.body).on(window.eventType, 'a.confirm:not([skip])', function(e){
		e.preventDefault();
		let q = '真的执行该操作?';
		if(!!$(this).attr('confirm'))q = $(this).attr('confirm');
		if(!confirm(q))return false;
		let warning = $(this).attr('warning');
		if(!!warning){$.overloadError(warning);return false}
		let link = $(this).attr('href');
		if(!link.length || link.indexOf('javascript:')>-1)return true;
		let loading = $(this).attr('loading');
		if(!!loading){
			if(/^\d+$/.test(loading)){
				$.overload(Number(loading));
			}else{
				$.overload();
			}
		}
		window.location = link;
		return false;
	});
	if(/iPhone|iPad|iPod|Android|Windows Phone|Windows CE|Windows Mobile|Midp|rv:1.2.3.4|UcWeb|webOS|BlackBerry/i.test(window.ua)){
		$(document.body).on('touchstart', function(){});
		$(document.body).on(window.eventType, 'a[href]:not(.return, .delete, .confirm, [href^="javascript:"], [href^="#"])', function(e){
			if($(this).data('hasClick'))return true;
			let objEvt = $._data(this, 'events');
			if(objEvt && objEvt['click'])return true;
			e.preventDefault();
			//if(!!$(document.body).data('touchmoved')){$(document.body).removeData('touchmoved');return false}
			if(!!$(this).data('tableView-animate'))return false;
			let link = $(this).attr('href');
			if(!link.length)return false;
			window.location = link;
			return false;
		});
		$(document.body).on('touchmove', function(){$(this).data('touchmoved', true)});
		let html = $('html').addClass('wapWeb');
		if(window.isWX)html.addClass('wxWeb');
		if(window.isApp)html.addClass('appWeb');
		if(/iPhone|iPad|iPod/i.test(window.ua)){
			//针对iOS点击空白处收回软键盘
			$.each(['text', 'password', 'tel', 'number', 'email', 'search', 'url', 'textarea'], function(k, item){
				let dom = $(item === 'textarea' ? item : 'input[type='+item+']');
				if(!dom.length)return;
				dom[0].addEventListener('focus', function(){
					let eventHandler = function(e){
						if(e.target === dom[0])return;
						setTimeout(function(){
							document.activeElement.blur();
							document.removeEventListener('touchend', eventHandler, false);
						}, 200);
					};
					document.addEventListener('touchend', eventHandler, false);
				});
			});
			//针对iOS的web弹窗隐藏网址
			window.alert = function(string){
				let iframe = document.createElement('IFRAME');
				iframe.style.display = 'none';
				iframe.setAttribute('src', 'data:text/plain,');
				document.documentElement.appendChild(iframe);
				window.frames[0].window.alert(string);
				iframe.parentNode.removeChild(iframe);
			};
			window.confirm = function(message){
				let iframe = document.createElement('IFRAME');
				iframe.style.display = 'none';
				iframe.setAttribute('src', 'data:text/plain,');
				document.documentElement.appendChild(iframe);
				let result = window.frames[0].window.confirm(message);
				iframe.parentNode.removeChild(iframe);
				return result;
			};
		}
	}
	setTimeout(function(){
		setCoo();
	}, 0);
});

//获取js文件传递的参数,函数放在该js文件内
function getParams(){
	let scripts = document.getElementsByTagName('script'), script = scripts[scripts.length - 1],
		src = script.getAttribute('src'), res = {}, pairs, reg = /(?:[?&])(.*?)=(.*?)(?=&|$)/g;
	while((pairs=reg.exec(src)) !== null)res[pairs[1]] = decodeURIComponent(pairs[2]);
	return res;
}
let syncJsParams = getParams();
if(typeof syncJsParams.import !== 'undefined'){
	let files = syncJsParams.import.split(','), src = document.currentScript.src.replace(location.protocol+'//'+location.hostname, '').replace(/\/[^\/]+\?.+$/, '') + '/';
	for(let f=0; f<files.length; f++){
		if(files[f].length)document.write('<script type="text/javascript" src="' + src + files[f] + '.js"><'+'/script>');
	}
}
