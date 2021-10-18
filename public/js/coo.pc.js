/*
Developed by @mario v12.5.20210308
*/

/*//
let checkDevtools = function(){
	let check = function(a){
		if(('' + a / a)['length'] !== 1 || a % 20 === 0){
			(function(){}['constructor']('debugger')());
		}else{
			(function(){}['constructor']('debugger')());
		}
		check(++a);
	}
	try{check(0)}catch(e){}
};
checkDevtools();
setInterval(checkDevtools, 100);
//*/

(function($){

//标签验证
//表单有.data('checkform-error')错误提示(第一个验证不通过的错误提示)
//表单有.data('checkform-error-field')错误的验证控件(第一个验证不通过的验证控件)
//验证控件有.data('checkform-label')提示label
//验证控件有.data('checkform-error')错误提示
$.fn.checkform = function(options){
	if(!this.length)return false;
	function getTags(obj){
		if(!!!obj.data('checkform-tags') && !!!obj.attr('class'))return [];
		if(!!obj.data('checkform-tags'))return obj.data('checkform-tags');
		let tags = [], names = obj.attr('class').trim().split(' ');
		for(let i=0; i<names.length; i++){
			if(new RegExp('^'+options.tag+'-').test(names[i])){
				let tag = names[i].replace(new RegExp(options.tag+'-', 'g'), '').split(/-(?!\d)/g);
				for(let t=0; t<tag.length; t++)tags.push(tag[t]);
			}
		}
		return tags;
	}
	function labelPosition(position, obj, label){
		let left = 0, top = 0;
		switch(position){
			case 'self':
				obj.before(label.hide());
				left = obj.position().left;
				top = obj.position().top;
				if(obj.is(':hidden')){
					if(obj.is('input:hidden')){
						let prev = obj.prev() || obj.parent();
						left = prev.position().left;
						top = prev.position().top;
					}else{
						obj.css('display', 'inherit');
						left = obj.position().left;
						top = obj.position().top;
						obj.hide();
					}
				}
				label.show().css({
					position: 'absolute',
					'z-index': 555,
					left: left,
					top: top
				});
				break;
			case 'top':
				obj.before(label.hide());
				left = obj.position().left;
				top = obj.position().top - label.outerHeight(false);
				if(obj.is(':hidden')){
					if(obj.is('input:hidden')){
						let prev = obj.prev() || obj.parent();
						left = prev.position().left;
						top = prev.position().top - label.outerHeight(false);
					}else{
						obj.css('display', 'inherit');
						left = obj.position().left;
						top = obj.position().top - label.outerHeight(false);
						obj.hide();
					}
				}
				label.show().css({
					position: 'absolute',
					'z-index': 555,
					left: left,
					top: top
				});
				break;
			case 'bottom':
				obj.before(label.hide());
				left = obj.position().left;
				top = obj.position().top + obj.outerHeight(false);
				if(obj.is(':hidden')){
					if(obj.is('input:hidden')){
						let prev = obj.prev() || obj.parent();
						left = prev.position().left;
						top = prev.position().top + prev.outerHeight(false);
					}else{
						obj.css('display', 'inherit');
						left = obj.position().left;
						top = obj.position().top + obj.outerHeight(false);
						obj.hide();
					}
				}
				label.show().css({
					position: 'absolute',
					'z-index': 555,
					left: left,
					top: top
				});
				break;
			case 'left':
				obj.before(label);
				break;
			case 'right':
				obj.after(label);
				break;
			case 'end':
				obj.parent().append(label);
				break;
			case 'parent':
				obj.parent().after(label);
				break;
			case 'absleft':
				obj.after(label);
				label.css({
					position: 'absolute',
					'z-index': 555
				});
				left = obj.position().left - label.outerWidth(false);
				top = obj.position().top;
				if(obj.is(':hidden')){
					if(obj.is('input:hidden')){
						let prev = obj.prev() || obj.parent();
						left = prev.position().left - label.outerWidth(false);
						top = prev.position().top;
					}else{
						obj.css('display', 'inherit');
						left = obj.position().left - label.outerWidth(false);
						top = obj.position().top;
						obj.hide();
					}
				}
				label.css({
					left: left,
					top: top
				});
				break;
			case 'absright':
				obj.after(label);
				label.css({
					position: 'absolute',
					'z-index': 555
				});
				left = obj.position().left + obj.outerWidth(false);
				top = obj.position().top;
				if(obj.is(':hidden')){
					if(obj.is('input:hidden')){
						let prev = obj.prev() || obj.parent();
						left = prev.position().left + prev.outerWidth(false);
						top = prev.position().top;
					}else{
						obj.css('display', 'inherit');
						left = obj.position().left + obj.outerWidth(false);
						top = obj.position().top;
						obj.hide();
					}
				}
				label.css({
					left: left,
					top: top
				});
				break;
			default:
				let re = /^(-?\d+) (-?\d+)$/; //left top
				if(re.test(position)){
					left = obj.position().left;
					top = obj.position().top;
					let mpos = position.match(re);
					obj.after(label);
					if(typeof mpos[1] !== 'undefined' && typeof mpos[2] !== 'undefined'){
						label.css({
							position: 'absolute',
							'z-index': 555,
							left: left + Number(mpos[1]),
							top: top + Number(mpos[2])
						});
					}
				}else{
					let expr = obj.parents('body').find(position);
					if(expr.length){
						expr.before(label);
					}else{
						obj.after(label);
					}
				}
				break;
		}
	}
	function checkHandle(){
		let obj = this, tags = obj.data('checkform-tags') || [], form = obj.data('checkform-form') || null,
			objType = obj[0].tagName.toLowerCase(), objVal = obj.val(), objName = obj.attr('name'), labelId = '', fail = false, errorMsg = '',
			isBox = (obj.attr('type') === 'radio' || obj.attr('type') === 'checkbox'); //, isMultiple = (objType === 'select' && obj.attr('multiple'))
		if(!tags.length)return true;
		if(objType === 'input')objType = obj.attr('type');
		if(objType === 'select')objVal = obj.selected().attr('value') || '';
		if(objVal === null)objVal = '';
		objVal = String(objVal);
		if(!!!obj.attr('id') && !!objName)obj.attr('id', objName);
		if(!!!objName)objName = obj.attr('id');
		if(!!!objName){
			objName = objType + '_' + (new Date()).getTime();
			obj.attr('id', objName);
		}
		labelId = 'coo_' + objName.replace(/[[]]/g, '');
		$.each(tags, function(t, tag){
			if(!!obj.data('checkform-box-break'))return false;
			let tagPrefix = ((tag instanceof RegExp) ? 'reg' : tag) + '-', labelClass = '', labelArea = '';
			if(tag instanceof RegExp){
				if(!isBox){
					obj.data('checkform-reg', true);
					if(!tag.test(objVal)){
						fail = true;
						errorMsg = '格式错误';
					}
				}else{
					let box = [];
					if(!!obj.attr('name'))box = obj.parents('body').find('input[name="'+obj.attr('name').replace(/([[]])/g, '\\$1')+'"]');
					if(!box.length && !!obj.attr('id'))box = obj.parents('body').find('input[id="'+obj.attr('id').replace(/([[]])/g, '\\$1')+'"]');
					if(box.length){
						box.data('checkform-reg', true).data('checkform-box', box);
						let min = 1;
						if(box.filter(':checked').length < min){
							box.data('checkform-box-break', true);
							fail = true;
							errorMsg = '最少选' + min + '项';
						}else{
							box.removeData('checkform-box-break');
						}
					}
				}
			}else{
				if(tag.charAt(0) === '!'){
					if(!objVal.length){
						fail = false;
						$('#'+labelId).remove();
						if(!!obj.attr('checkform-label-area'))labelArea = obj.parents('body').find(obj.attr('checkform-label-area'));
						if(!!obj.attr(tagPrefix+'label-area'))labelArea = obj.parents('body').find(obj.attr(tagPrefix+'label-area'));
						if(labelArea.length)labelArea.html(labelArea.data('html'));
						return true;
					}
					tag = tag.substr(1);
				}
				switch(tag){
					case 'need':
						if(!isBox){
							obj.data('checkform-need', true);
							if(objVal.replace(/[\s\n\r]+/g, '') === ''){
								fail = true;
								errorMsg = '此项必填';
								if(objType === 'file')errorMsg = '请选择文件';
								if(objType === 'select')errorMsg = '请选择';
							}else{
								if(objType === 'select'){
									let min = 1, max = 0;
									if(!!obj.attr('checkform-need'))min = Number(obj.attr('checkform-need'));
									if(!!obj.attr('checkform-min'))min = Number(obj.attr('checkform-min'));
									if(!!obj.attr('checkform-max'))max = Number(obj.attr('checkform-max'));
									if(min){
										if(obj.selected().length < min){
											fail = true;
											errorMsg = '最少选' + min + '项';
										}
									}
									if(max){
										if(obj.selected().length > max){
											fail = true;
											errorMsg = '最多选' + max + '项';
										}
									}
								}
							}
						}else{
							let box = [];
							if(!!obj.attr('name'))box = obj.parents('body').find('input[name="'+obj.attr('name').replace(/([[]])/g, '\\$1')+'"]');
							if(!box.length && !!obj.attr('id'))box = obj.parents('body').find('input[id="'+obj.attr('id').replace(/([[]])/g, '\\$1')+'"]');
							if(box.length){
								box.data('checkform-need', true).data('checkform-box', box).each(function(){
									let _box = $(this);
									if(!!_box.attr('checkform-need'))box.data('checkform-min', Number(_box.attr('checkform-need')));
									if(!!_box.attr('checkform-min'))box.data('checkform-min', Number(_box.attr('checkform-min')));
									if(!!_box.attr('checkform-max'))box.data('checkform-max', Number(_box.attr('checkform-max')));
								});
								let min = obj.data('checkform-min') || 1, max = obj.data('checkform-max') || 0;
								if(min){
									if(box.filter(':checked').length < min){
										box.data('checkform-box-break', true);
										fail = true;
										errorMsg = '最少选' + min + '项';
									}else{
										box.removeData('checkform-box-break');
									}
								}
								if(max){
									if(box.filter(':checked').length > max){
										box.data('checkform-box-break', true);
										fail = true;
										errorMsg = '最多选' + max + '项';
									}else{
										box.removeData('checkform-box-break');
									}
								}
							}
						}
						break;
					case 'username':
						obj.data('checkform-username', true);
						if(!/^[a-zA-Z]\w{5,15}$/.test(objVal)){
							fail = true;
							errorMsg = '字母开头,6-16位,只能字母、数字与下划线';
						}
						break;
					case 'usernamecn':
						obj.data('checkform-usernamecn', true);
						if(!/^([\u4e00-\u9fa5]|\w){3,15}$/.test(objVal)){
							fail = true;
							errorMsg = '3-15位包括A-Z、a-z、汉字、不含特殊字符';
						}
						break;
					case 'password':
						obj.data('checkform-password', true);
						if(!/^\S{6,16}$/.test(objVal)){
							fail = true;
							errorMsg = '6-16位,不含空格,区分大小写';
						}
						break;
					case 'num':
					case 'number':
						obj.data('checkform-num', true);
						if(!/^(-?[1-9]\d*|0)$/.test(objVal)){
							fail = true;
							errorMsg = '请输入数字';
						}
						break;
					case 'char':
						obj.data('checkform-char', true);
						if(!/^[a-zA-Z]+$/.test(objVal)){
							fail = true;
							errorMsg = '请输入字母';
						}
						break;
					case 'en':
					case 'english':
						obj.data('checkform-en', true);
						if(!/^[\w\-]+$/.test(objVal)){
							fail = true;
							errorMsg = '只能字母、数字与下划线';
						}
						break;
					case 'cn':
					case 'chinese':
						obj.data('checkform-cn', true);
						if(!/^[\u4e00-\u9fa5]+$/.test(objVal)){
							fail = true;
							errorMsg = '请输入中文';
						}
						break;
					case 'double':
						obj.data('checkform-double', true);
						if(!/^[^\x00-\xff]+$/.test(objVal)){
							fail = true;
							errorMsg = '只能中文、全角字符';
						}
						break;
					case 'money':
						obj.data('checkform-money', true);
						if(!/^\d+(\.\d{1,2})?$/.test(objVal)){
							fail = true;
							errorMsg = '请输入正确金额';
						}
						break;
					case 'idcard':
						obj.data('checkform-idcard', true);
						if(!/(^\d{15}$)|(^\d{17}([0-9]|x|X)$)/.test(objVal)){
							fail = true;
							errorMsg = '请输入身份证号';
						}
						break;
					case 'idcardstrict':
						obj.data('checkform-idcardstrict', true);
						let Wi = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2, 1], //加权因子
							ValideCode = [1, 0, 10, 9, 8, 7, 6, 5, 4, 3, 2], //身份证验证位值,10代表X
							idCardValidate = function(idCard){
								if(idCard.length === 15){
									return is15IdCard(idCard); //进行15位身份证的验证
								}else if(idCard.length === 18){
									return is18IdCard(idCard) && isTrue18IdCard(idCard.split('')); //进行18位身份证的基本验证和第18位的验证
								}else{
									return false;
								}
							},
							isTrue18IdCard = function(idCard){
								let sum = 0;
								if(idCard[17].toLowerCase() === 'x')idCard[17] = 10; //将最后位为x的验证码替换为10方便后续操作
								for(let i=0; i<17; i++)sum += Wi[i] * idCard[i]; //加权求和
								let valCodePosition = sum % 11; //得到验证码所位置
								return idCard[17] === ValideCode[valCodePosition];
							},
							is18IdCard = function(idCard){
								let year =  idCard.substring(6, 10), month = idCard.substring(10, 12), day = idCard.substring(12, 14),
									date = new Date(year, parseInt(month)-1, parseInt(day));
								return !(date.getFullYear() !== parseInt(year) || date.getMonth() !== parseInt(month)-1 || date.getDate() !== parseInt(day));
							},
							is15IdCard = function(idCard){
								let year =  idCard.substring(6, 8), month = idCard.substring(8, 10), day = idCard.substring(10, 12),
									date = new Date(year, parseInt(month)-1, parseInt(day));
								return !(date.getYear() !== parseInt(year) || date.getMonth() !== parseInt(month)-1 || date.getDate() !== parseInt(day));
							};
						if(!idCardValidate(objVal)){
							fail = true;
							errorMsg = '身份证号不正确';
						}
						break;
					case 'zip':
						obj.data('checkform-zip', true);
						if(!/^[1-9]\d{5}(?!\d)$/.test(objVal)){
							fail = true;
							errorMsg = '请输入邮编';
						}
						break;
					case 'tel':
						obj.data('checkform-tel', true);
						if(!/^((\d{3,4}-)?\d{8}(-\d+)?|(\(\d{3,4}\))?\d{8}(-\d+)?)$/.test(objVal)){
							fail = true;
							errorMsg = '请输入固话号码';
						}
						break;
					case 'telac':
						obj.data('checkform-telac', true);
						if(!/^(\d{3,4}-\d{8}(-\d+)?|\(\d{3,4}\)\d{8}(-\d+)?)$/.test(objVal)){
							fail = true;
							errorMsg = '请输入固话号码(包括区号)';
						}
						break;
					case 'mobile':
						obj.data('checkform-mobile', true);
						if(!/^(\+?86)?1[3-8]\d{9}$/.test(objVal)){
							fail = true;
							errorMsg = '请输入手机号码';
						}
						break;
					case 'phone':
						obj.data('checkform-phone', true);
						if(!/^((\+?86)?1[3-8]\d{9}|(\d{3,4}-)?\d{8}(-\d+)?|(\(\d{3,4}\))?\d{8}(-\d+)?)$/.test(objVal)){
							fail = true;
							errorMsg = '请输入联系电话';
						}
						break;
					case 'phoneac':
						obj.data('checkform-phoneac', true);
						if(!/^((\+?86)?1[3-8]\d{9}|\d{3,4}-\d{8}(-\d+)?|\(\d{3,4}\)\d{8}(-\d+)?)$/.test(objVal)){
							fail = true;
							errorMsg = '请输入联系电话(固话需区号)';
						}
						break;
					case 'email':
						obj.data('checkform-email', true);
						if(!/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/.test(objVal)){
							fail = true;
							errorMsg = '请输入正确邮箱';
						}
						break;
					case 'url':
						obj.data('checkform-url', true);
						if(!/^((http|https|ftp):\/\/)?[\w\-_]+(\.[\w\-_]+)+([\w\-.,@?^=%&:\/~+#]*[\w\-@?^=%&\/~+#])?$/.test(objVal)){
							fail = true;
							errorMsg = '请输入正确网址';
						}
						break;
					case 'qq':
						obj.data('checkform-qq', true);
						if(!/^[1-9][0-9]{4,}$/.test(objVal)){
							fail = true;
							errorMsg = '请输入正确QQ';
						}
						break;
					case 'ip':
						obj.data('checkform-ip', true);
						if(!/^((([1-9]\d?)|(1\d{2})|(2[0-4]\d)|(25[0-5]))\.){3}(([1-9]\d?)|(1\d{2})|(2[0-4]\d)|(25[0-5]))$/.test(objVal)){
							fail = true;
							errorMsg = '请输入正确IP';
						}
						break;
					case 'color':
						obj.data('checkform-color', true);
						if(!/^#[a-fA-F0-9]{6}$/.test(objVal)){
							fail = true;
							errorMsg = '请选择颜色';
						}
						break;
					case 'date':
						obj.data('checkform-date', true);
						//http://www.cnblogs.com/love2wllw/archive/2010/05/30/1747534.html
						if(!/^(?:(?!0000)[0-9]{4}[\/-](?:(?:0?[1-9]|1[0-2])[\/-](?:0?[1-9]|1[0-9]|2[0-8])|(?:0?[13-9]|1[0-2])[\/-](?:29|30)|(?:0?[13578]|1[02])[\/-]31)|(?:[0-9]{2}(?:0[48]|[2468][048]|[13579][26])|(?:0[48]|[2468][048]|[13579][26])00)[\/-]0?2[\/-]29)$/.test(objVal)){
							fail = true;
							errorMsg = '请选择日期';
						}
						break;
					case 'reg':
						obj.data('checkform-reg', true);
						let reg = obj.attr('checkform-reg');
						if(!!!reg)return true;
						if(!new RegExp(reg).test(objVal)){
							fail = true;
							errorMsg = '格式错误';
						}
						break;
					default:
						let mark = '';
						if(tag.substring(0, 4) === 'need' || tag.substring(0, 3) === 'min' || tag.substring(0, 3) === 'max'){
							let min = 1, max = 0;
							if(tag.substring(0, 4) === 'need')min = Number(tag.substring(4));
							if(tag.substring(0, 3) === 'min')min = Number(tag.substring(3));
							if(tag.substring(0, 3) === 'max')max = Number(tag.substring(3));
							if(min || max){
								if(!isBox){
									if(objType === 'select'){
										if(min){
											if(obj.selected().length < min){
												fail = true;
												errorMsg = '最少选' + min + '项';
											}
										}
										if(max){
											if(obj.selected().length > max){
												fail = true;
												errorMsg = '最多选' + max + '项';
											}
										}
									}
								}else{
									let box = [];
									if(!!obj.attr('name'))box = obj.parents('body').find('input[name="'+obj.attr('name').replace(/([[]])/g, '\\$1')+'"]');
									if(!box.length && !!obj.attr('id'))box = obj.parents('body').find('input[id="'+obj.attr('id').replace(/([[]])/g, '\\$1')+'"]');
									if(box.length){
										box.data('checkform-need', true).data('checkform-box', box).each(function(){
											let _box = $(this);
											if(!!_box.attr('checkform-need'))min = Number(_box.attr('checkform-need'));
											if(!!_box.attr('checkform-min'))min = Number(_box.attr('checkform-min'));
											if(!!_box.attr('checkform-max'))max = Number(_box.attr('checkform-max'));
										});
										box.data('checkform-min', min).data('checkform-max', max);
										if(min){
											if(box.filter(':checked').length < min){
												box.data('checkform-box-break', true);
												fail = true;
												errorMsg = '最少选' + min + '项';
											}else{
												box.removeData('checkform-box-break');
											}
										}
										if(max){
											if(box.filter(':checked').length > max){
												box.data('checkform-box-break', true);
												fail = true;
												errorMsg = '最多选' + max + '项';
											}else{
												box.removeData('checkform-box-break');
											}
										}
									}
								}
							}
							break;
						}
						if(tag.substring(0, 5) === 'float'){
							mark = tag.substring(5);
							if(!mark.length || /^\d+$/.test(mark)){
								if(!mark.length)mark = '2';
								if(!new RegExp('^((-?(([1-9]\\d*(\\.\\d{1,'+mark+'})?)|(0\\.\\d{1,'+mark+'})))|0)$').test(objVal) || /^-?0+(\.0+)$/.test(objVal)){
									fail = true;
									errorMsg = '请输入数字(最多' + mark + '位小数)';
								}
							}
							break;
						}
						let m = tag.charAt(0);
						mark = tag.substring(1);
						if($.inArray(tag.substr(0, 2), ['<=', '>=']) > -1){
							m = tag.substr(0, 2);
							mark = tag.substring(2);
						}
						if($.inArray(tag.substr(0, 7), ['lstrict', 'mstrict', 'nstrict']) > -1){
							m = tag.substr(0, 7);
							mark = tag.substring(7);
						}
						let targetObj,
							Length = function(type){
								if(typeof type === 'undefined')type = 'utf8';
								let str = this, bytesCount = 0;
								for(let i=0; i<str.length; i++){
									let c = str.charCodeAt(i);
									if((c >= 0x0001 && c <= 0x007e) || (0xff60 <= c && c <= 0xff9f)){
										bytesCount += 1;
									}else{
										bytesCount += ((type === 'gb' || type === 'gb2312') ? 2 : 3);
									}
								}
								return bytesCount;
							};
						switch(m){
							case '=':
								if(!mark.length)return true;
								if(!/^-?\d+(\.\d+)?$/.test(mark)){ //两个值必须相等
									targetObj = obj.parents('body').find(mark);
									if(!targetObj.length)return true;
									let targetName = targetObj.attr('id') || targetObj.attr('name');
									if(!targetName.length || targetName === objName)return true;
									if(!!!targetObj.data(objName)){
										targetObj.data(objName, true);
										targetObj.on('keyup blur', function(){
											checkHandle.call(targetObj.data({'checkform-tags':tags, 'checkform-form':form}));
										});
									}
									if(objVal !== targetObj.val()){
										fail = true;
										errorMsg = '两项不一致';
									}
								}else{ //必须等于n
									if(!objVal.length || !/^-?\d+(\.\d+)?$/.test(objVal) || Number(objVal) !== Number(mark)){
										fail = true;
										errorMsg = '必须等于' + mark;
									}
								}
								break;
							case '%':
								if(!mark.length)return true;
								if(!/^-?\d+$/.test(mark)){ //二选一
									targetObj = obj.parents('body').find(mark);
									if(!targetObj.length)return true;
									if(!!!targetObj.data(objName)){
										targetObj.data(objName, true);
										targetObj.on('keyup blur', function(){
											checkHandle.call(targetObj.data({'checkform-tags':tags, 'checkform-form':form}));
										});
									}
									if(!objVal.length && !targetObj.val().length){
										fail = true;
										errorMsg = '至少填写一项';
									}
								}else{ //必须为n的整倍数
									if(!objVal.length || !/^-?\d+$/.test(objVal) || (Number(objVal) % Number(mark)) !== 0){
										fail = true;
										errorMsg = '必须为' + mark + '的整倍数';
									}
								}
								break;
							case '^': //其中之一填写后另一个也必须填写
								if(!mark.length)return true;
								targetObj = obj.parents('body').find(mark);
								if(!targetObj.length)return true;
								if(!!!targetObj.data(objName)){
									targetObj.data(objName, true);
									targetObj.on('keyup blur', function(){
										checkHandle.call(targetObj.data({'checkform-tags':tags, 'checkform-form':form}));
									});
								}
								if((objVal.length && !targetObj.val().length) || (!objVal.length && targetObj.val().length)){
									fail = true;
									errorMsg = '两项都必须填写';
								}
								break;
							case '>': //大于
							case '>=': //大于或等于
								if(!mark.length)return true;
								if(!/^-?\d+(\.\d+)?$/.test(mark)){
									targetObj = obj.parents('body').find(mark);
									if(!targetObj.length)return true;
									if(!!obj.data('checkform-date')){
										if(!objVal.length || !targetObj.val().length || objVal.time() < targetObj.val().time()){
											fail = true;
											errorMsg = '日期不能比' + (obj.attr('checkform-label-%')||mark) + '早';
										}
									}else{
										if(!!!targetObj.data(objName)){
											targetObj.data(objName, true);
											targetObj.on('keyup blur', function(){
												checkHandle.call(targetObj.data({'checkform-tags':tags, 'checkform-form':form}));
											});
										}
										if(!objVal.length || !targetObj.val().length || isNaN(objVal) || isNaN(targetObj.val())){
											fail = true;
											errorMsg = '数值为空';
										}else if(m === '>' && Number(objVal) <= Number(targetObj.val())){
											fail = true;
											errorMsg = '必须大于' + (obj.attr('checkform-label-%')||mark) + '的值';
										}else if(m === '>=' && Number(objVal) < Number(targetObj.val())){
											fail = true;
											errorMsg = '必须大于或等于' + (obj.attr('checkform-label-%')||mark) + '的值';
										}
									}
								}else{
									if(!objVal.length || isNaN(objVal) || !/^-?\d+(\.\d+)?$/.test(objVal)){
										fail = true;
										errorMsg = '数值为空';
									}else if(m === '>' && Number(objVal) <= Number(mark)){
										fail = true;
										errorMsg = '必须大于' + mark;
									}else if(m === '>=' && Number(objVal) < Number(mark)){
										fail = true;
										errorMsg = '必须大于或等于' + mark;
									}
								}
								break;
							case '<': //小于
							case '<=': //小于或等于
								if(!mark.length)return true;
								if(!/^-?\d+(\.\d+)?$/.test(mark)){
									targetObj = obj.parents('body').find(mark);
									if(!targetObj.length)return true;
									if(!!obj.data('checkform-date')){
										if(!objVal.length || !targetObj.val().length || objVal.time() > targetObj.val().time()){
											fail = true;
											errorMsg = '日期不能比' + (obj.attr('checkform-label-%')||mark) + '迟';
										}
									}else{
										if(!!!targetObj.data(objName)){
											targetObj.data(objName, true);
											targetObj.on('keyup blur', function(){
												checkHandle.call(targetObj.data({'checkform-tags':tags, 'checkform-form':form}));
											});
										}
										if(!objVal.length || !targetObj.val().length || isNaN(objVal) || isNaN(targetObj.val())){
											fail = true;
											errorMsg = '数值为空';
										}else if(m === '<' && Number(objVal) >= Number(targetObj.val())){
											fail = true;
											errorMsg = '必须小于' + (obj.attr('checkform-label-%')||mark) + '的值';
										}else if(m === '<=' && Number(objVal) > Number(targetObj.val())){
											fail = true;
											errorMsg = '必须小于或等于' + (obj.attr('checkform-label-%')||mark) + '的值';
										}
									}
								}else{
									if(!objVal.length || isNaN(objVal) || !/^-?\d+(\.\d+)?$/.test(objVal)){
										fail = true;
										errorMsg = '数值为空';
									}else if(m === '<' && Number(objVal) >= Number(mark)){
										fail = true;
										errorMsg = '必须小于' + mark;
									}else if(m === '<=' && Number(objVal) > Number(mark)){
										fail = true;
										errorMsg = '必须小于或等于' + mark;
									}
								}
								break;
							case 'l': //长度必须等于n位
								if(isNaN(mark))return true;
								if(objVal.length !== Number(mark)){
									fail = true;
									errorMsg = '必须填写' + mark + '个字';
								}
								break;
							case 'lstrict': //长度必须等于n位,严格验证,中文占3位字符
								if(isNaN(mark))return true;
								if(Length.call(objVal) !== Number(mark)){
									fail = true;
									errorMsg = '必须填写' + mark + '个字，中文占3个';
								}
								break;
							case 'm': //长度必须少于或等于n位
								if(isNaN(mark))return true;
								if(!objVal.length){
									fail = true;
									errorMsg = '此项必填';
								}else if(objVal.length > Number(mark)){
									fail = true;
									errorMsg = '必须少于或等于' + mark + '个字';
								}
								break;
							case 'mstrict': //长度必须少于或等于n位,严格验证,中文占3位字符
								if(isNaN(mark))return true;
								if(!objVal.length){
									fail = true;
									errorMsg = '此项必填';
								}else if(Length.call(objVal) > Number(mark)){
									fail = true;
									errorMsg = '必须少于或等于' + mark + '个字，中文占3个';
								}
								break;
							case 'n': //长度必须多于或等于n位
								if(isNaN(mark))return true;
								if(objVal.length < Number(mark)){
									fail = true;
									errorMsg = '必须多于或等于' + mark + '个字';
								}
								break;
							case 'nstrict': //长度必须多于或等于n位,严格验证,中文占3位字符
								if(isNaN(mark))return true;
								if(Length.call(objVal) < Number(mark)){
									fail = true;
									errorMsg = '必须多于或等于' + mark + '个字，中文占3个';
								}
								break;
							case 'e': //允许选择的文件类型(支持正则)(一般file用)
								if(!mark.length)return true;
								if(!new RegExp('^.+\\.('+mark+')$', 'i').test(objVal)){
									fail = true;
									errorMsg = '只可选择 ' + mark.replace(/\|/g, ', ') + ' 格式';
								}
								break;
						}
						break;
				}
			}
			let label = false;
			if(fail){
				obj.data('checkform-error', errorMsg);
				$('#'+labelId).remove();
				if(!form)return !fail;
				form.data({'checkform-error':errorMsg, 'checkform-error-field':obj});
				let errorClass = obj.attr('checkform-error-class') || options.errorClass;
				if(!!errorClass)obj.addClass(errorClass);
				let successClass = obj.attr('checkform-success-class');
				if(!!successClass)obj.removeClass(successClass);
				if(!!obj.attr('checkform-label') || !options.labelNon){
					if(!!obj.attr('checkform-label-error'))errorMsg = obj.attr('checkform-label-error');
					if(!!obj.attr('checkform-label-error-class'))labelClass = obj.attr('checkform-label-error-class');
					if(!!obj.attr('checkform-label-area'))labelArea = obj.parents('body').find(obj.attr('checkform-label-area'));
					if(!!obj.attr(tagPrefix+'label-error'))errorMsg = obj.attr(tagPrefix+'label-error');
					if(!!obj.attr(tagPrefix+'label-error-class'))labelClass = obj.attr(tagPrefix+'label-error-class');
					if(!!obj.attr(tagPrefix+'label-area'))labelArea = obj.parents('body').find(obj.attr(tagPrefix+'label-area'));
					if(options.overload){
						$.overloadError(errorMsg);
					}else{
						label = true;
					}
				}
			}else{
				obj.removeData('checkform-error');
				$('#'+labelId).remove();
				if(!form)return !fail;
				form.removeData('checkform-error').removeData('checkform-error-field');
				let errorClass = obj.attr('checkform-error-class') || options.errorClass;
				if(!!errorClass)obj.removeClass(errorClass);
				let successClass = obj.attr('checkform-success-class');
				if(!!successClass)obj.addClass(successClass);
				let fn = obj.attr('fn');
				if(!!fn){
					let func = evil(fn);
					if($.isFunction(func))func(obj[0]);
				}
				if(!!obj.attr('checkform-label-area'))labelArea = obj.parents('body').find(obj.attr('checkform-label-area'));
				if(!!obj.attr(tagPrefix+'label-area'))labelArea = obj.parents('body').find(obj.attr(tagPrefix+'label-area'));
				if(labelArea.length){
					if(labelArea.data('html'))labelArea.html(labelArea.data('html'));
					else labelArea.html('');
				}
				if( (!!obj.attr('checkform-label') || !options.labelNon) && !!obj.attr('checkform-label-success') ){
					if(!!obj.attr('checkform-label-success'))errorMsg = obj.attr('checkform-label-success');
					if(!!obj.attr('checkform-label-success-class'))labelClass = obj.attr('checkform-label-success-class');
					if(!!obj.attr(tagPrefix+'label-success'))errorMsg = obj.attr(tagPrefix+'label-success');
					if(!!obj.attr(tagPrefix+'label-success-class'))labelClass = obj.attr(tagPrefix+'label-success-class');
					label = true;
				}
			}
			if(label){
				let position = obj.attr('checkform-label-position') || form.attr('checkform-label-position') || options.labelPosition,
					htmlFor = !!obj.attr('id') ? 'for="'+obj.attr('id')+'"' : '',
					positionClass = (!!position && !/^(-?\d+) (-?\d+)$/.test(position)) ? position + 'label' : 'rightlabel';
				label = $('<label id="'+labelId+'" class="'+labelClass+' '+positionClass+' '+options.tag+'-label '+objName+'-label" '+htmlFor+'><span>'+errorMsg+'</span></label>');
				obj.data('checkform-label', label);
				if(labelArea.length){
					if(!!!labelArea.data('html'))labelArea.data('html', labelArea.html());
					labelArea.html('').append(label);
				}else{
					if(!!!position){
						if(isBox){
							obj.parent().append(label);
						}else{
							obj.after(label);
						}
					}else{
						labelPosition(position, obj, label);
					}
				}
			}
			if(fail)return false;
		});
		return !fail;
	}
	function validateHandle(form, tag){
		if(form.is('input, select, textarea')){
			return checkHandle.call(form.data({'checkform-tags':$.isArray(tag) ? tag : [tag]}));
		}else{
			let validateResult = true;
			let field = form.data('checkform-status-field');
			if(!!form.data('checkform-status-error') && field['input'] === form.find('input').length && field['textarea'] === form.find('textarea').length && field['select'] === form.find('select').length)return false;
			form.find(':radio[class*="'+options.tag+'-"], :checkbox[class*="'+options.tag+'-"]').each(function(){
				let obj = $(this);
				let box = [], tags = [];
				if(!!obj.attr('name'))box = obj.parents('body').find('input[name="'+obj.attr('name').replace(/([[]])/g, '\\$1')+'"]');
				if(!box.length && !!obj.attr('id'))box = obj.parents('body').find('input[id="'+obj.attr('id').replace(/([[]])/g, '\\$1')+'"]');
				if(box.length){
					box.data('checkform-box', box).each(function(){
						let _box = $(this);
						let _tags = getTags(_box);
						if(_tags.length){
							$.each(_tags, function(t, item){
								if($.inArray(item, tags) === -1)tags.push(item);
							});
						}
					});
					if(tags.length)box.data('checkform-tags', tags);
				}
			});
			if($.isFunction(options.before)){
				let res = options.before.call(form);
				if(typeof res === 'boolean')return res;
			}
			form.find('input[class*="'+options.tag+'-"], select[class*="'+options.tag+'-"], textarea[class*="'+options.tag+'-"]').each(function(){
				let obj = $(this);
				let tags = getTags(obj);
				if(!tags.length)return true;
				let res = checkHandle.call(obj.data({'checkform-tags':tags, 'checkform-form':form}));
				obj.data('checkform-checkHandle', checkHandle);
				if(validateResult)validateResult = res;
			});
			if($.isPlainObject(options.addEles)){
				$.each(options.addEles, function(key, item){
					if(!(item instanceof RegExp))return true;
					let field = form.find(key);
					if(!field.length)return true;
					field.each(function(){
						let obj = $(this);
						validateResult = checkHandle.call(obj.data({'checkform-tags':[item], 'checkform-form':form}));
						obj.data('checkform-checkHandle', checkHandle);
					});
				});
			}
			form.data('checkform-status-field', {
				'input': form.find('input').length,
				'textarea': form.find('textarea').length,
				'select': form.find('select').length
			}).removeData('checkform-status-error').removeData('checkform-error').removeData('checkform-error-field');
			if(!validateResult)return false;
			let submit = form.data('checkform-submit');
			if($.isFunction(submit)){
				let res = submit.call(form[0], form[0]);
				if(!!form.data('checkform-submit-return')){
					if(typeof res === 'boolean' && !res)validateResult = false;
				}
			}
			if($.isFunction(options.callback)){
				let res = options.callback.call(form);
				if(typeof res === 'boolean' && !res)return false;
			}
			if(validateResult){
				if(options.overlayText.length)form.find('.checkform-overlay').show();
				if(form.attr('target') !== '_blank')form.find(':submit, :image, :reset').prop('disabled', true);
			}
			return options.debug ? false : validateResult;
		}
	}
	if(typeof options === 'string' || typeof options === 'undefined'){
		if(this.is('input, select, textarea'))return validateHandle(this, typeof options === 'undefined' ? 'need' : options.replace(/checkform-/g, '').trim());
		options = {};
	}
	if($.isFunction(options))options = {callback:options};
	options = $.extend({
		debug: false, //调试模式(不提交表单)
		tag: 'checkform', //验证标识前缀
		overlayText: '', //提交后表单添加遮罩的文字, 不设置即不添加
		overlay: '', //提交后表单添加遮罩的背景颜色
		overload: false, //提示信息使用overloadError
		addEles: null, //扩展验证控件(没有使用tag前缀类名的控件), 格式 { '.required': /.+/, '[diy="img"]': /\.(jpg|jpeg|png|gif|bmp)$/ }
		labelNon: false, //不使用label提示信息
		labelPosition: '', //提示信息位置, 有效值为 self|top|bottom|left|right|end|parent|absleft|absright|expr|(x y)
		errorClass: '', //验证不通过添加的提示样式名称
		reset: null, //表单重置后执行, this表单
		before: null, //表单提交时验证前执行, 可返回boolean, true不验证直接提交, false终止提交, this表单
		callback: null //表单提交时验证通过后执行, 返回false终止提交, this表单
	}, options);
	return this.each(function(){
		let form = $(this);
		form.data({
			'checkform-status-field': {
				'input': form.find('input').length,
				'textarea': form.find('textarea').length,
				'select': form.find('select').length
			}
		});
		if(!!!form.data('checkform')){
			form.data('checkform', true);
			let submit = form.attr('onsubmit');
			if(!!submit && typeof submit === 'string'){
				let fn = evil('function(_form){' + submit.replace(/this/g, '_form') + '}');
				if(submit.indexOf('return ') > -1)form.data('checkform-submit-return', true);
				form.data('checkform-submit', fn);
			}
			form.removeAttr('onsubmit').get(0).onsubmit = null;
			form.on('submit', function(){
				return validateHandle(form);
			}).on('keyup blur', 'input[class*="'+options.tag+'-"]:not(:radio, :checkbox, :file, :image, :submit, :reset), textarea[class*="'+options.tag+'-"]', function(){
				form.removeData('checkform-status-error');
				let obj = $(this), tags = getTags(obj);
				if(!tags.length)return true;
				checkHandle.call(obj.data({'checkform-tags':tags, 'checkform-form':form}));
				obj.data('checkform-checkHandle', checkHandle);
			}).on('change', 'select[class*="'+options.tag+'-"], :file[class*="'+options.tag+'-"]', function(){
				form.removeData('checkform-status-error');
				let obj = $(this), tags = getTags(obj);
				if(!tags.length)return true;
				checkHandle.call(obj.data({'checkform-tags':tags, 'checkform-form':form}));
				obj.data('checkform-checkHandle', checkHandle);
			}).on('click', ':radio[class*="'+options.tag+'-"], :checkbox[class*="'+options.tag+'-"]', function(){
				form.removeData('checkform-status-error');
				let obj = $(this);
				if(!!!obj.data('checkform-tags')){
					let box = [], tags = [];
					if(!!obj.attr('name'))box = obj.parents('body').find('input[name="'+obj.attr('name').replace(/([[]])/g, '\\$1')+'"]');
					if(!box.length && !!obj.attr('id'))box = obj.parents('body').find('input[id="'+obj.attr('id').replace(/([[]])/g, '\\$1')+'"]');
					if(box.length){
						box.data('checkform-box', box).each(function(){
							let _tags = getTags($(this));
							if(_tags.length){
								$.each(_tags, function(t, item){
									if($.inArray(item, tags) === -1)tags.push(item);
								});
							}
						});
						if(tags.length)box.data('checkform-tags', tags);
					}
				}
				if(!!obj.data('checkform-box'))obj.data('checkform-box').removeData('checkform-box-break');
				let tags = getTags(obj);
				if(!tags.length)return true;
				checkHandle.call(obj.data({'checkform-tags':tags, 'checkform-form':form}));
				obj.data('checkform-checkHandle', checkHandle);
			}).on('click', ':reset', function(){
				form.removeData('checkform-status-error');
				$('label.'+options.tag+'-label').remove();
				if($.isFunction(options.reset))options.reset.call(form);
			});
			form.find(':radio[class*="'+options.tag+'-"], :checkbox[class*="'+options.tag+'-"]').each(function(){
				let obj = $(this);
				let box = [];
				if(!!obj.attr('name'))box = obj.parents('body').find('input[name="'+obj.attr('name').replace(/([[]])/g, '\\$1')+'"]');
				if(!box.length && !!obj.attr('id'))box = obj.parents('body').find('input[id="'+obj.attr('id').replace(/([[]])/g, '\\$1')+'"]');
				if(box.length){
					box.data('checkform-box', box).each(function(){
						let _box = $(this);
						if(!!!_box.attr('class') || _box.attr('class').indexOf(options.tag+'-') === -1){
							let tags = [], names = obj.attr('class').trim().split(' ');
							for(let i=0; i<names.length; i++){
								if(new RegExp('^'+options.tag+'-').test(names[i])){
									let tag = names[i].replace(new RegExp(options.tag+'-', 'g'), '').split(/-(?!\d)/g);
									for(let t=0; t<tag.length; t++)tags.push(tag[t]);
								}
							}
							if(tags.length)_box.addClass(options.tag+'-'+tags.join(' '+options.tag+'-'));
						}
					});
				}
			});
			if(options.overlayText.length)form.css('position', 'relative').append('<div class="checkform-overlay" style="position:absolute;z-index:20;left:0;top:0;right:0;bottom:0;display:none;color:#fff;font:bold 14px/'+form.height()+'px tahoma;text-align:center;background:'+(options.overlay||'rgba(0,0,0,0.5)')+';">' + options.overlayText + '</div>');
		}
		if(!form.is('form'))form.trigger('submit');
	});
};

//获取所有控件值, 若控件存在number自定义属性或type="number"即值转为数值型
//可用带有自定义属性[object|array]=key值的标签包裹特定的控件,达到形成内嵌式的复杂型json数据,不支持options.type=string
$.fn.param = function(options){
	options = $.extend({
		type: 'object', //返回类型，object|string
		attr: '', //没有name属性时使用自定义属性代替
		range: '', //只获取指定范围内的控件值,其他控件不获取
		ignore: '', //忽略的控件，expr|element|jquery
		disabled: false, //disabled也有效
		filter: true, //过滤空值控件
		each: null //每个控件取值时执行,返回值将作为控件的值,this:控件,接收两个参数:index(控件索引),element(原生控件)
	}, $('body').data('param.options'), options);
	let form = this, url = options.type === 'object' ? {} : '', parent = options.range.length ? form.find(options.range) : form,
		params = parent.find('input, select, textarea').not(options.ignore);
	if(!options.disabled)params = params.not('[disabled]');
	params.each(function(k){
		let _this = $(this), name = _this.attr('name'), isArray = false, returnVal = '', objVal = '';
		if(_this.is(':radio, :checkbox') && !_this.is(':checked'))return true;
		if(!!!name && options.attr.length)name = _this.attr(options.attr);
		if(!!!name || (!!name && !name.replace(/\[]/ig, '').trim().length))return true;
		if($.isFunction(options.each) && typeof (returnVal = options.each.call(_this, k, _this[0])) !== 'undefined'){
			objVal = returnVal;
		}else{
			if(_this.is('select')){
				objVal = _this.selected().attr('value').trim();
			}else if(_this.is(':file')){
				objVal = this.files.length ? this.files[0].name : '';
			}else{
				objVal = _this.val().trim();
			}
		}
		if(options.filter && (!objVal || (typeof objVal === 'string' && !objVal.length) || ($.isArray(objVal) && !objVal.length) || ($.isPlainObject(objVal) && !$.isJson(objVal))))return true;
		if(!!_this.attr('number') || _this.attr('type') === 'number')objVal = Number(objVal);
		isArray = /\[]/ig.test(name);
		name = name.replace(/\[]/ig, '');
		if(options.type === 'object'){
			if(_this.parents('[object]').length || _this.parents('[array]').length){
				let _last = _this, attr = '';
				do{
					let p = _last.parents('[object], [array]').eq(0);
					attr = p.attr('object') || p.attr('array');
					if(!p.is('[object]')){
						let parent = p;
						p = form.find('['+attr+']');
						if(!p.length){
							p = $('<p style="display:none;" '+attr+'="'+attr+'" array="'+attr+'"></p>').data('mirror', true);
							parent.before(p);
						}
					}
					let data = p.data(attr);
					if(_last.is('input, select, textarea')){
						if(!!!data){
							let obj = {};
							if(isArray){
								obj[name] = [objVal];
							}else{
								obj[name] = objVal;
							}
							if(p.is('[object]')){
								p.data(attr, obj);
							}else{//array
								p.data(attr, [obj]);
							}
						}else{
							if(!$.isArray(data)){
								if(isArray){
									if($.isArray(data[name]))data[name].push(objVal);
									else data[name] = [objVal];
								}else{
									data[name] = objVal;
								}
								p.data(attr, data);
							}else{//array
								let lastIndex = data.length - 1;
								if($.inArray(name, Object.keys(data[lastIndex])) === -1){
									if(isArray){
										data[lastIndex][name] = [objVal];
									}else{
										data[lastIndex][name] = objVal;
									}
								}else{
									if($.isArray(data[lastIndex][name])){
										data[lastIndex][name].push(objVal);
									}else{
										let obj = {};
										obj[name] = objVal;
										data.push(obj);
									}
								}
								p.data(attr, data);
							}
						}
					}else{//嵌套
						let _attr = _last.attr('object')||_last.attr('array'), _d = _last.data(_attr);
						if(!!!data){
							let obj = {};
							obj[_attr] = _d;
							if(p.is('[object]')){
								p.data(attr, obj);
							}else{//array
								p.data(attr, [obj]);
							}
						}else{
							if(!$.isArray(data)){
								data[_attr] = _d;
								p.data(attr, data);
							}else{//array
								let lastIndex = data.length - 1;
								if($.inArray(_attr, Object.keys(data[lastIndex])) === -1){
									data[lastIndex][_attr] = _d;
								}else{
									data[_attr] = _d;
								}
								p.data(attr, data);
							}
						}
					}
					_last = p;
					if(!_last.parents('[object], [array]').length)break;
				}while(true);
				url[_last.attr('object')||_last.attr('array')] = _last.data(attr);
			}else{
				if(isArray){
					if($.isArray(url[name]))url[name].push(objVal);
					else url[name] = [objVal];
				}else{
					url[name] = objVal;
				}
			}
		}else{
			url = (!url.length ? name+'=' : url+'&'+name+'=') + encodeURIComponent(objVal);
		}
	});
	params.each(function(){
		$(this).parents('[object], [array]').each(function(){
			let p = $(this), attr = p.attr('object') || p.attr('array');
			p.removeData(attr);
			form.find('['+attr+']').each(function(){
				if(!!$(this).data('mirror'))$(this).remove();
			});
		});
	});
	return url;
};

//拖曳排序
$.fn.dragsort = function(options){
	options = $.extend({
		dragList: 'li', //移动对象
		dragItem: '', //移动对象内的拖动手柄, 默认为移动对象自己
		dragItemExcept: 'input, textarea, select, a[href]', //不会触发移动的对象
		releaseTarget: document.body, //执行mouseup的对象,null即使用dragItem
		opacity: 0.9, //鼠标按下后移动对象半透明
		lockX: false, //锁定水平位置
		lockY: false, //锁定垂直位置
		lockRange: false, //锁定区域(只能在this内移动)
		autoCursor: true, //鼠标自动变为move
		placeholder: '', //占位符html, 为空即复制移动对象且增加placeholder样式
		scrollSpeed: 5, //自动滚动的速度, 0为禁止滚动
		debug: false, //显示当前移动的位置数据
		before: null, //鼠标按下前执行
		start: null, //鼠标按下后执行
		move: null, //鼠标拖动时执行
		stop: null, //鼠标放开后执行
		after: null, //鼠标放开后执行(位置产生改变才执行)
		complete: null //插件加载后执行
	}, $('body').data('dragsort.options'), options);
	return this.each(function(){
		let _this = $(this), scrollX, scrollY, curList = null, placeholder = null, isTr = false, hasMove = false, lockRange = options.lockRange, doc = options.lockRange ? _this : $(document),
			items = _this.find(options.dragList), dragItem = items, scrollSpeed = options.scrollSpeed, dragItemLeft = 0, dragItemTop = 0, sl = 0, st = 0, releaseTarget = options.releaseTarget;
		if(!items.length)return true;
		let tagName = items[0].tagName.toLowerCase(), placeholderItem = options.placeholder;
		if(options.dragItem.length)dragItem = items.find(options.dragItem);
		if(_this.css('position') === 'static')_this.css('position', 'relative');
		_this.data('dragsort-items', items);
		items.each(function(){
			let _item = $(this);
			if(!!_item.attr('style'))_item.attr('data-style', _item.attr('style'));
			_item.attr('data-width', _item.width());
			_item.attr('data-height', _item.height());
		});
		if(items.is('tr'))lockRange = true;
		let area = lockRange ? _this : null, dragItemExs = [];
		dragItem.each(function(){
			if(!!$(this).data('dragsort')){
				dragItemExs.push($(this));
				return true;
			}
			$(this).data('dragsort', true);
		});
		dragItem.not(dragItemExs).drag({
			target: function(){
				if(options.dragItem.length)return this.parents(tagName).eq(0);
				return this.eq(0);
			},
			releaseTarget: function(){
				if(releaseTarget)return releaseTarget;
				if(options.dragItem.length)return this.parents(tagName).eq(0);
				return this.eq(0);
			},
			debug: options.debug,
			area: area,
			lockX: options.lockX,
			lockY: options.lockY,
			lockRange: lockRange,
			autoCursor: options.autoCursor,
			exceptEl: options.dragItemExcept,
			before: function(){
				let target = options.dragItem.length ? this.parents(tagName).eq(0) : this.eq(0);
				items = _this.data('dragsort-items');
				curList = target.css('position', 'relative');
				dragItemLeft = this.position().left;
				dragItemTop = this.position().top;
				if($.isFunction(options.before))options.before.call(curList);
				isTr = curList.is('tr');
				if(isTr)curList.children().each(function(){
					$(this).css('width', $(this).outerWidth(false));
				});
			},
			start: function(){
				hasMove = false;
				sl = doc.scrollLeft();
				st = doc.scrollTop();
				placeholder = placeholderItem.length ? $(placeholderItem) : curList.clone();
				let position = curList.position();
				curList.attr('data-position', (position.left+sl)+'|'+(position.top+st)).css({
					position:'absolute', 'z-index':888,
					opacity:options.opacity, width:curList.attr('data-width'), height:curList.attr('data-height')
				});
				curList.after(placeholder.addClass('placeholder'));
				if($.isFunction(options.start))options.start.call(curList);
			},
			move: function(e, d){
				hasMove = true;
				sl = doc.scrollLeft();
				st = doc.scrollTop();
				let currentX = $.touches(e, 4).x + sl, currentY = $.touches(e, 4).y + st;
				items.not(curList).each(function(){
					let _item = $(this), left = _item.offset().left + sl, top = _item.offset().top + st,
						width = _item.outerWidth(false), height = _item.outerHeight(false);
					if((isTr ? 1 : (left<currentX && currentX<left+width)) && (top<currentY && currentY<top+height)){
						if(((isTr ? 1 === 0 : (left<currentX && currentX<left+width/2)) && d.dirX<0) || (top<currentY && currentY<top+height/2 && d.dirY<0)){
							_item.before(placeholder);
						}else if(((isTr ? 1 === 0 : (left+width/2<currentX && currentX<left+width)) && d.dirX>0) || (top+height/2<currentY && currentY<top+height && d.dirY>0)){
							_item.after(placeholder);
						}
						return false;
					}
				});
				if(scrollSpeed){
					if(scrollX){clearInterval(scrollX);scrollX=null}
					if(scrollY){clearInterval(scrollY);scrollY=null}
					let left1 = _this.position().left, left2 = left1 + _this.outerWidth(false),
						top1 = _this.position().top, top2 = top1 + _this.outerHeight(false);
					if(currentX<left1)scrollX = setInterval(function(){_this.scrollLeft(_this.scrollLeft()-3)}, scrollSpeed);
					if(currentX>left2)scrollX = setInterval(function(){_this.scrollLeft(_this.scrollLeft()+3)}, scrollSpeed);
					if(currentY<top1)scrollY = setInterval(function(){_this.scrollTop(_this.scrollTop()-3)}, scrollSpeed);
					if(currentY>top2)scrollY = setInterval(function(){_this.scrollTop(_this.scrollTop()+3)}, scrollSpeed);
				}
				if($.isFunction(options.move))options.move.call(curList, d);
			},
			stop: function(){
				if(hasMove){
					let position = placeholder.position();
					curList.stop(true, false).css({'transition-duration':'0s', '-webkit-transition-duration':'0s'}).animate({left:position.left+sl, top:position.top+st}, 200, function(){
						restore();
						if(curList.attr('data-position') !== position.left+'|'+position.top && $.isFunction(options.after))options.after.call(curList);
						if($.isFunction(options.stop))options.stop.call(curList);
					});
				}else{
					restore();
					if($.isFunction(options.stop))options.stop.call(curList);
				}
				hasMove = false;
			}
		});
		function restore(){
			if(scrollX){clearInterval(scrollX);scrollX=null}
			if(scrollY){clearInterval(scrollY);scrollY=null}
			curList.removeAttr('style');
			if(!!curList.attr('data-style'))curList.attr('style', curList.attr('data-style'));
			placeholder.after(curList);
			placeholder.remove();
			placeholder = null;
			dragItemLeft = 0;
			dragItemTop = 0;
			if(isTr)curList.children().each(function(){
				$(this).css('width', '');
			});
		}
		if($.isFunction(options.complete))options.complete.call(_this);
	});
};

//拖拽显示
$.fn.dragshow = function(options){
	options = $.extend({
		list: 'li', //拖动列表
		title: '', //显示按钮内的内容(支持html代码)(支持函数,接受一个参数:当前行)
		cls: '.title', //显示按钮的类名称
		useTransform: true, //使用CSS3特性来移动, list的css样式需要增加transform:translate3d(0,0,0);transition-duration:200ms;
		click: null, //点击显示按钮时执行, this:显示按钮, 接受两个参数:当前行, 显示按钮点击的element(为区别显示区域内有多个html标签)
		before: null //拖动前执行
	}, $('body').data('dragshow.options'), options);
	return this.each(function(){
		$(this).stopBounces(true);
		let _this = $(this), width = _this.width(), originalX, dx, dy, moved = false, originBtnWidth = 0, btnWidth = 0,
		editingFix = 0, curX = 0, list = _this.find(options.list), btn = _this.children(options.cls), useTransform = options.useTransform;
		//if(list.css('transform')=='none')useTransform = false;
		if(!useTransform)_this.css('position', 'relative');
		if(!btn.length){
			btn = $('<div class="'+options.cls.replace('.','').replace('#','')+'"></div>');
			_this.append(btn);
			if($.isFunction(options.click)){
				btn.tapper(function(e){
					let _b = $(this), o = $.etarget(e);
					if(btn.children().length){
						do{
							if($(o).is(_b.children())){
								options.click.call(_b, _b.data('curRow'), $(o));
								setTimeout(function(){_b.removeData('curRow');_this.removeData('lastEdit')}, 100);
								return;
							}
							o = o.parentNode;
						}while(o.parentNode);
					}else{
						options.click.call(_b, _b.data('curRow'), _b);
						setTimeout(function(){_b.removeData('curRow');_this.removeData('lastEdit')}, 100);
					}
				});
			}
		}
		originBtnWidth = btnWidth = btn.width();
		btn.hide();
		list.css({position:'relative', 'z-index':2}).each(function(){
			let _l = $(this);
			if(!!_l.data('dragshow'))return true;
			_l.data('dragshow', true);
			let startDrag = function(e){
				let o = e.target || e.srcElement;
				if($(o).is('input'))$(o).focus();
				if(!!_this.data('lastEdit') && _this.data('lastEdit')[0]!==_l[0]){cancelLast(e);return false}
				if($.isFunction(options.before)){
					let before = options.before.call(_l);
					if(typeof before === 'boolean' && !before)return;
				}
				moved = false;
				let event = 'click dragstart';
				if($.browser.mobile)event = 'touchend dragstart';
				_this.find('a').on(event, stop);
				let title = options.title;
				if($.isFunction(title))title = title.call(btn, _l);
				btn.width(originBtnWidth).html(title).data('curRow', _l)
					.css({top:_l.position().top, height:_l.outerHeight(false), 'line-height':_l.outerHeight(false)+'px'});
				if(btn.children().length){
					btnWidth = 0;
					btn.children().each(function(){btnWidth+=$(this).outerWidth(false)});
					let minWidth = $.unit(btn.css('min-width'));
					if(btnWidth<minWidth)btnWidth = minWidth;
					btn.width(btnWidth);
				}
				btnWidth = btn.outerWidth(false);
				originalX = useTransform ? _l.transform().x : _l.position().left;
				dx = $.touches(e).x;
				dy = $.touches(e).y;
				editingFix = !!_this.data('lastEdit') ? btnWidth : 0;
				_l.stop(true, false);
				_l.on('mousemove', moveDrag).css('cursor', 'move');
				if(window.addEventListener)_l[0].addEventListener('touchmove', moveDrag, true);
				return false;
			},
			moveDrag = function(e){
				if(btn.children().length){
					btnWidth = 0;
					btn.children().each(function(){btnWidth+=$(this).outerWidth(false)});
					let minWidth = $.unit(btn.css('min-width'));
					if(btnWidth<minWidth)btnWidth = minWidth;
					btn.width(btnWidth);
				}
				let newPosition = 0, moveX = $.touches(e).x, moveY = $.touches(e).y;
				if(moveY-dy>10 || moveY-dy<-10)return false;
				if(moveX-dx>10 || moveX-dx<-10)e.preventDefault(); //终止屏幕拖动, 使用后页面不能上下拖动动
				btn.css({display:''});
				moveX -= dx;
				moved = true;
				if(!_l.find('.overlay-div').length){
					list.each(function(){
						$(this).append('<div class="overlay-div" style="position:absolute;z-index:999;top:0;left:0;width:100%;height:100%;"></div>');
					});
				}
				if(moveX < 0){
					if(moveX <= -(width-btnWidth)/3-btnWidth+editingFix){
						newPosition = curX + (moveX+(width-btnWidth)/3+btnWidth-editingFix) * 0.2;
					}else{
						newPosition = curX = originalX + moveX;
					}
				}else{
					if(moveX <= Number(editingFix)){
						newPosition = originalX + moveX;
					}else{

					}
				}
				if(useTransform){
					_l.css({transform:'translate3d('+newPosition+'px,0,0)', '-webkit-transform':'translate3d('+newPosition+'px,0,0)', 'transition-duration':'0s', '-webkit-transition-duration':'0s'});
				}else{
					_l.css('left', newPosition);
				}
				return false;
			},
			endDrag = function(e){
				if(moved)e.preventDefault();
				_l.off('mousemove', moveDrag).css('cursor', '');
				if(window.removeEventListener)_l[0].removeEventListener('touchmove', moveDrag, true);
				if(!!_this.data('lastEdit')){
					if(!moved){cancelLast(e);return false}
				}
				if(!moved){
					let event = 'click dragstart';
                    if($.browser.mobile)event = 'touchend dragstart';
					_this.find('a').off(event, stop);
					return;
				}
				let left = useTransform ? _l.transform().x : _l.position().left;
				if(left<=-btnWidth/2){
					if(useTransform){
						_l.css({transform:'translate3d('+(-btnWidth)+'px,0,0)', '-webkit-transform':'translate3d('+(-btnWidth)+'px,0,0)',
							'transition-duration':'200ms', '-webkit-transition-duration':'200ms'});
					}else{
						_l.animate({left:-btnWidth}, 200, 'easeout');
					}
					_this.data('lastEdit', _l);
				}else{
					if(useTransform){
						_l.css({transform:'', '-webkit-transform':'', 'transition-duration':'200ms', '-webkit-transition-duration':'200ms'});
						setTimeout(function(){btn.hide().removeData('curRow')}, 200);
					}else{
						_l.animate({left:0}, 200, 'easeout', function(){btn.hide().removeData('curRow')});
					}
					_this.removeData('lastEdit');
					list.find('.overlay-div').remove();
				}
				return false;
			};
			_l.unselect().on('mousedown', startDrag).on('mouseup', endDrag)
			.on('dragstart', 'img, a', function(e){e.preventDefault()});
			if(window.addEventListener){
				this.addEventListener('touchstart', startDrag, true);
				this.addEventListener('touchend', endDrag, true);
				this.addEventListener('touchcancel', endDrag, true);
			}
		});
		_this.data('reset', cancelLast);
		function stop(e){
			if(e.preventDefault)e.preventDefault();
			e.returnValue = false;
			return false;
		}
		function cancelLast(e){
			if(e)e.preventDefault();
			let lastEdit = _this.data('lastEdit');
			_this.removeData('lastEdit');
			if(useTransform){
				lastEdit.css({transform:'', '-webkit-transform':'', 'transition-duration':'200ms', '-webkit-transition-duration':'200ms'});
				setTimeout(function(){btn.hide().removeData('curRow')}, 200);
			}else{
				lastEdit.animate({left:0}, 200, 'easeout', function(){btn.hide().removeData('curRow')});
			}
			list.find('.overlay-div').remove();
			let event = 'click dragstart';
            if($.browser.mobile)event = 'touchend dragstart';
			_this.find('a').off(event, stop);
		}
	});
};

//显示菜单
$.fn.showmenu = function(options){
	if(typeof options === 'undefined'){
		let operateControl = $(this).data('operateControl');
		if(!!operateControl && $.isFunction(operateControl))operateControl();
		return;
	}
	options = $.extend({
		html: '', //显示菜单的内容, string|对象
		cls: '', //显示菜单的样式
		effect: '', //显示|关闭效果, 'opacity'|'height'
		arrow: true, //显示箭头
		fixArrowCss: null, //一个参数[0:箭头在上面(菜单在下面)|1:箭头在下面(菜单在上面)], this代表箭头(eq(0):背面箭头,eq(1):表面箭头)
		hideEle: null, //绑定关闭菜单的对象, string|对象
		on: null, //显示后执行, this代表菜单
		off: null //关闭后执行, this代表菜单
	}, options);
	if(!options.html)return;
	return this.each(function(){
		let _this = $(this), list = $('<div></div>').addClass(options.cls).css({position:'absolute', 'z-index':555, display:'none', float:'left'});
		$(document.body).append(list);
		let object = $(options.html);
		if(object.parent().length){
			list.append(object);
			object.show();
		}else{
			list.html(object);
		}
		if(options.arrow){
			let arrow = '<em style="position:absolute;z-index:1;display:block;border-style:solid;border-color:#999 transparent;"></em>\
				<em style="position:absolute;z-index:2;display:block;border-style:solid;border-color:#fff transparent;"></em>';
			list.append(arrow);
		}
		$.registControl({
			menu: list,
			partner: _this,
			outside: function(){
				switch(options.effect){
					case 'opacity': list.fadeOut(200);break;
					case 'height': list.slideUp(200);break;
					default: list.hide();break;
				}
				if($.isFunction(options.off))options.off.call(list);
			}
		});
		_this.onclick(function(){
			if(!list.is(':visible')){
				let clientWidth = $.window().width, clientHeight = $.window().height,
					scrollLeft = $.scroll().left, scrollTop = $.scroll().top,
					position = _this.position(), left = 0, top = 0;
				if(position.left+list.outerWidth(false)>scrollLeft+clientWidth){
					left = position.left + _this.outerWidth(false) - list.outerWidth(true);
					list.children('em').css({left:'', right:30});
				}else{
					left = position.left;
					list.children('em').css({left:30, right:''});
				}
				if(position.top+_this.outerHeight(false)+list.outerHeight(true)>scrollTop+clientHeight){
					top = position.top - list.outerHeight(true);
					let em = list.children('em').css({'border-width':'6px 6px 0 6px', top:(list.outerHeight(false)-2)+'px'});
					em.eq(0).css({top:(list.outerHeight(false)-1)+'px'});
					if($.isFunction(options.fixArrowCss))options.fixArrowCss.call(em, 1);
				}else{
					top = position.top + _this.outerHeight(false);
					let em = list.children('em').css({'border-width':'0 6px 6px 6px', top:-5});
					em.eq(0).css({top:-6});
					if($.isFunction(options.fixArrowCss))options.fixArrowCss.call(em, 0);
				}
				list.css({left:left, top:top});
				switch(options.effect){
					case 'opacity': list.fadeIn(200);break;
					case 'height': list.slideDown(200);break;
					default: list.show();break;
				}
				if($.isFunction(options.on))options.on.call(list);
			}else{
				$.registControl(list);
			}
		});
		if(options.hideEle)$(options.hideEle).onclick(function(){
			$.registControl(list);
		});
	});
};

//惯性滑动
$.fn.inertia = function(options){
	options = $.extend({
		lockX: false, //锁定水平位置
		lockY: false, //锁定垂直位置
		bounce: null, //摩擦与缓动的前后固定位置,如果设置必须返回{start:数值, end:数值}
		scroll: false, //按照滚动条滚动,移动端设为true即插件无效
		start: null, //滚动前执行
		move: null, //滚动时执行
		end: null, //滚动后执行
		complete: null //插件加载后执行
	}, $('body').data('inertia.options'), options);
	return this.each(function(){
		if(!!$(this).data('inertia')){
			$(this).off('mousedown', startDrag).off('mousemove', moveDrag).off('mouseup', endDrag).find('img').off('dragstart', function(e){e.preventDefault()});
			if(window.removeEventListener){
				this.removeEventListener('touchstart', startDrag, true);
				this.removeEventListener('touchend', endDrag, true);
				this.removeEventListener('touchcancel', endDrag, true);
			}
		}
		let _this = $(this).data('inertia', true), mouseStart = {x:0, y:0}, startPos = {x:0, y:0}, lastPos = {x:0, y:0}, dragSpeed = {x:0, y:0}, timer = null,
			scrollHandle = options.scroll, moveX = 0, moveY = 0, flg = false, scrollWidth = 0, scrollHeight = 0, bounce = options.bounce, bounceStart = 0, bounceEnd = 0;
		if(scrollHandle && $.browser.mobile)return; //在移动端滚动条滚动默认就是惯性
		if(!scrollHandle)_this.parent().css('transform', 'translateZ(0)');
		let stop = function(e){
			if(e.preventDefault)e.preventDefault();
			e.returnValue = false;
			return false;
		},
		startDrag = function(e){
			if(timer){clearInterval(timer);timer = null}
			stop(e);
			let evt = $.browser.mobile ? 'touchend dragstart' : 'click dragstart';
			_this.find('a').on(evt, stop);
			scrollWidth = scrollHandle ? _this[0].scrollWidth - _this.width() : _this.parent().width() - _this.width();
			scrollHeight = scrollHandle ? _this[0].scrollHeight - _this.height() : _this.parent().height() - _this.height();
			flg = true;
			moveX = 0;
			moveY = 0;
			let touches = $.touches(e),
				x = scrollHandle ? _this.scrollLeft() : _this.transform().x,
				y = scrollHandle ? _this.scrollTop() : _this.transform().y;
			mouseStart.x = touches.x;
			mouseStart.y = touches.y;
			startPos.x = lastPos.x = x;
			startPos.y = lastPos.y = y;
			_this.on('mousemove', moveDrag).on('mouseleave', endDrag);
			if(window.addEventListener)_this[0].addEventListener('touchmove', moveDrag, true);
			if($.isFunction(options.start))options.start.call(_this, x, y);
		},
		moveDrag = function(e){
			if(timer){clearInterval(timer);timer = null}
			if(flg){
				stop(e);
				let touches = $.touches(e), x = touches.x, y = touches.y, direction = $.getDirection(mouseStart.x, mouseStart.y, x, y);
				if(scrollHandle){
					moveX = mouseStart.x - x;
					moveY = mouseStart.y - y;
				}else{
					moveX = x - mouseStart.x;
					moveY = y - mouseStart.y;
				}
				x = moveX + startPos.x;
				y = moveY + startPos.y;
				if(options.lockX)x = startPos.x;
				if(options.lockY)y = startPos.y;
				dragSpeed.x = x - lastPos.x;
				dragSpeed.y = y - lastPos.y;
				lastPos.x = x;
				lastPos.y = y;
				if(scrollHandle){
					if(x < 0)x = 0;
					if(x > scrollWidth)x = scrollWidth;
					if(y < 0)y = 0;
					if(y > scrollHeight)y = scrollHeight;
					_this.scrollLeft(x);
					_this.scrollTop(y);
				}else{
					if(x > bounceStart && direction === 4)x = bounceStart + moveX * 0.2;
					if(x < scrollWidth - bounceEnd && direction === 3)x = scrollWidth - bounceEnd + moveX * 0.2;
					if(y > bounceStart && direction === 2)y = bounceStart + moveY * 0.2;
					if(y < scrollHeight - bounceEnd && direction === 1)y = scrollHeight - bounceEnd + moveY * 0.2;
					_this.css({transform:'translate3d('+x+'px,'+y+'px,0)', '-webkit-transform':'translate3d('+x+'px,'+y+'px,0)', 'transition-duration':'0s', '-webkit-transition-duration':'0s'});
				}
				if($.isFunction(options.move))options.move.call(_this, lastPos.x, lastPos.y);
			}
		},
		endDrag = function(e){
			if(timer){clearInterval(timer);timer = null}
			stop(e);
			flg = false;
			_this.off('mousemove', moveDrag).off('mouseleave', endDrag);
			if(window.addEventListener)_this[0].removeEventListener('touchmove', moveDrag, true);
			if(moveX === 0 && moveY === 0){
				let evt = $.browser.mobile ? 'touchend dragstart' : 'click dragstart';
				_this.find('a').off(evt, stop);
				return;
			}
			timer = setInterval(function(){
				let x = scrollHandle ? _this.scrollLeft() : _this.transform().x,
					y = scrollHandle ? _this.scrollTop() : _this.transform().y;
				if(Math.abs(dragSpeed.x) <= 1 && Math.abs(dragSpeed.y) <= 1){
					clearInterval(timer);timer = null;
					if(!scrollHandle){
						if(x > 0)x = 0;
						if(x < _this.parent().width() - _this.width())x = _this.parent().width() - _this.width();
						if(y > 0)y = 0;
						if(y < _this.parent().height() - _this.height())y = _this.parent().height() - _this.height();
						_this.css({transform:'translate3d('+x+'px,'+y+'px,0)', '-webkit-transform':'translate3d('+x+'px,'+y+'px,0)', 'transition-duration':'300ms', '-webkit-transition-duration':'300ms'});
					}
					if($.isFunction(options.end))options.end.call(_this, x, y);
					return;
				}
				if(scrollHandle){
					_this.scrollLeft(x+dragSpeed.x);
					_this.scrollTop(y+dragSpeed.y);
				}else{
					_this.css({
						transform:'translate3d('+(x+dragSpeed.x)+'px,'+(y+dragSpeed.y)+'px,0)',
						'-webkit-transform':'translate3d('+(x+dragSpeed.x)+'px,'+(y+dragSpeed.y)+'px,0)',
						'transition-duration':'0s', '-webkit-transition-duration':'0s'
					});
				}
				if( !scrollHandle && (x>bounceStart || x<scrollWidth-bounceEnd || y>bounceStart || y<scrollHeight-bounceEnd) ){
					dragSpeed.x *= 0.4;
					dragSpeed.y *= 0.4;
				}
				dragSpeed.x *= 0.95;
				dragSpeed.y *= 0.95;
			}, $.browser.mobile ? 30 : 10);
		};
		if($.isFunction(bounce))bounce = bounce.call(_this);
		if($.isPlainObject(bounce) && !isNaN(bounce.start) && !isNaN(bounce.end)){
			bounceStart = Number(bounce.start);
			bounceEnd = Number(bounce.end);
		}
		if($.isFunction(options.complete))options.complete.call(_this);
		setTimeout(function(){
			scrollWidth = _this.parent().width() - _this.width();
			scrollHeight = _this.parent().height() - _this.height();
		}, 310);
		_this.unselect().on('mousedown', startDrag).on('mouseup', endDrag).find('img').on('dragstart', function(e){e.preventDefault()});
		if(window.addEventListener){
			this.addEventListener('touchstart', startDrag, true);
			this.addEventListener('touchend', endDrag, true);
			this.addEventListener('touchcancel', endDrag, true);
		}
	});
};

//百度地图
/*
需要先在调用页面添加(使用baiduMapApi不需要添加)
<script type="text/javascript" src="https://api.map.baidu.com/api?v=2.0&ak=iaDZrNldobQVbG7L357j8fIPKxIj8A1i"></script>
百度地图api申请的密钥, http://lbsyun.baidu.com/apiconsole/key
http://developer.baidu.com/map/jsdemo.htm
*/
$.fn.baiduMapApi = function(options){
	if(typeof window.BMap === 'undefined'){
		let ak = window.BAIDU_AK, callback = null;
		if(typeof options.ak !== 'undefined' && options.ak.length)ak = options.ak;
		if(typeof options.callback !== 'undefined' && $.isFunction(options.callback))callback = options.callback;
		$(document.body).data('baiduMap-element', this);
		$(document.body).data('baiduMap-options', options);
		let script = document.createElement('script');
		script.type = 'text/javascript';
		script.src = (document.location.protocol==='file:'?'http:':document.location.protocol)+'//api.map.baidu.com/api?v=2.0&ak='+ak+'&callback=baiduMapApi';
		document.getElementsByTagName('head')[0].appendChild(script);
		if(callback){
			if(script.addEventListener){
				script.addEventListener('load', function(){
					callback();
				}, false);
			}else if(script.attachEvent){
				script.attachEvent('onreadystatechange', function(){
					let target = window.event.srcElement;
					if(target.readyState === 'loaded'){
						callback();
					}
				});
			}
		}
		return this;
	}else{
		return this.baiduMap(options);
	}
};
$.fn.baiduMap = function(options){
	if(typeof window.BMap === 'undefined')return this.baiduMapApi(options);
	if(typeof options === 'undefined' || !$.isPlainObject(options))return this.data('baiduMap');
	options = $.extend({
		longitude: '', //经度, 113.440685
		latitude: '', //纬度, 23.136588
		zoom: 16, //默认地图级别
		zoomEnable: true, //可否缩放
		dragEnable: true, //可否拖动
		detailEnable: true, //可否点击查看地址详情气泡
		setOverlaysTime: 0, //加载完毕后延迟加载Marker的时间
		lngObj: '', //存放经度数值的控件expr, 一般为输出用
		latObj: '', //存放纬度数值的控件expr
		minZoom: 1, //地图允许的最小级别
		maxZoom: 19, //地图允许的最大级别
		centerCity: '广州', //默认显示城市, options.longitude,options.latitude两个值为空才有效
		controlType: 0, //控件类型, 1平移和缩放按钮, 2平移按钮, 3缩放按钮
		controlAnchor: 1, //控件位置, 0左上, 1右上
		getCoordinate: null, //点击地图获取坐标且返回经度与纬度, function
		isNav: false, //使用导航功能
		autoNav: true, //自动使用地点导航, 以当前位置导航到 (options.longitude, options.latitude)
		navType: -1, //非自动导航时的驾车策略, 0:最少时间, 1:最短距离, 2:避开高速
		navDrag: false, //自定义途经点(拖曳线路)
		navPoints: [], //自定义途经点(使用文字设定)
		panel: '', //显示路线详情面板,面板id
		complete: null //地图加载完成
	}, $('body').data('baiduMap.options'), options);
	return this.each(function(k){
		let _this = $(this), map = _this.data('baiduMap'), _id = _this.attr('id');
		if(!!map){
			if(!isNaN(options.longitude) && !isNaN(options.latitude) && Number(options.longitude)>0 && Number(options.latitude)>0){
				if(!!_this.data('lngObj'))$(_this.data('lngObj')).val(options.longitude);
				if(!!_this.data('latObj'))$(_this.data('latObj')).val(options.latitude);
				setOverlays(options.longitude, options.latitude);
				if(!!_this.data('getCoordinate'))_this.data('getCoordinate').call(_this, options.longitude, options.latitude);
			}
			return true;
		}
		if(options.lngObj.length)_this.data('lngObj', options.lngObj);
		if(options.latObj.length)_this.data('latObj', options.latObj);
		if($.isFunction(options.getCoordinate))_this.data('getCoordinate', options.getCoordinate);
		if(!!!_id){
			let td = new Date();
			_id = td.getFullYear()+''+(td.getMonth()+1)+''+td.getDate()+''+td.getHours()+''+td.getMinutes()+''+td.getSeconds()+''+k;
			_this.attr('id', _id);
		}
		map = new BMap.Map(_id, {minZoom:options.minZoom, maxZoom:options.maxZoom, enableMapClick:options.detailEnable, enableHighResolution:true});
		//map.panTo(new BMap.Point(113.262232, 23.154345)); //切换地图到指定位置
		//map.setCenter(cityName); //用城市名设置地图中心点
		//map.setZoom(14); //设置地图当前的放大级别
		//map.getDistance(pointA, pointB).toFixed(2); //获取两点距离,单位米,保留小数点后两位,pointA=new BMap.Point
		//let polyline = new BMap.Polyline([pointA, pointB], {strokeColor:'blue', strokeWeight:6, strokeOpacity:0.5}); //定义折线
		//map.addOverlay(polyline); //添加折线到地图上
		//let control = new BMap.NavigationControl(); //左上角,默认缩放平移控件
		//let control = new BMap.NavigationControl({anchor:BMAP_ANCHOR_TOP_RIGHT, type:BMAP_NAVIGATION_CONTROL_SMALL}); //右上角,仅包含平移和缩放按钮
		//new BMap.ScaleControl({anchor:BMAP_ANCHOR_TOP_LEFT}); //左上角,比例尺
		//缩放控件type有四种类型
		//BMAP_NAVIGATION_CONTROL_SMALL:仅包含平移和缩放按钮, BMAP_NAVIGATION_CONTROL_PAN:仅包含平移按钮, BMAP_NAVIGATION_CONTROL_ZOOM:仅包含缩放按钮
		//map.addControl(control); //添加控件
		//map.removeControl(control); //移除控件
		if(options.controlType>0)map.addControl(new BMap.NavigationControl({anchor:options.controlAnchor, type:options.controlType}));
		if(options.zoomEnable){
			map.enableScrollWheelZoom();
			map.enableDoubleClickZoom();
			map.enablePinchToZoom();
		}else{
			map.disableScrollWheelZoom();
			map.disableDoubleClickZoom();
			map.disablePinchToZoom();
		}
		options.dragEnable ? map.enableDragging() : map.disableDragging(); //拖动
		_this.data('baiduMap', map);
		if($.isFunction(options.complete)){
			let tilesloaded = function(){
				options.complete.call(_this, map);
				map.removeEventListener('tilesloaded', tilesloaded);
			};
			map.addEventListener('tilesloaded', tilesloaded);
		}
		let lng = 0, lat = 0;
		if(!isNaN(options.longitude) && !isNaN(options.latitude) && Number(options.longitude)>0 && Number(options.latitude)>0){
			if(options.lngObj.length)$(options.lngObj).val(options.longitude);
			if(options.latObj.length)$(options.latObj).val(options.latitude);
			let newPoint = new BMap.Point(options.longitude, options.latitude);
			map.centerAndZoom(newPoint, options.zoom); //初始化地图,设置中心点坐标和地图级别
			lng = options.longitude;
			lat = options.latitude;
		}else{
			map.centerAndZoom(options.centerCity, options.zoom);
			lng = map.getCenter().lng;
			lat = map.getCenter().lat;
		}
		setTimeout(function(){setOverlays(lng, lat)}, options.setOverlaysTime);
		if($.isFunction(options.getCoordinate) || (options.lngObj.length && options.latObj.length))map.addEventListener('click', function(e){
			if(options.lngObj.length)$(options.lngObj).val(e.point.lng);
			if(options.latObj.length)$(options.latObj).val(e.point.lat);
			setOverlays(e.point.lng, e.point.lat);
			if($.isFunction(options.getCoordinate))options.getCoordinate.call(_this, e.point.lng, e.point.lat);
		});
		if(options.isNav){
			if(options.autoNav){
				let geolocation = new BMap.Geolocation();
				geolocation.getCurrentPosition(function(r){ //浏览器定位
					if(this.getStatus() === BMAP_STATUS_SUCCESS){
						let longitude = r.point.lng, latitude = r.point.lat;
						let p1 = new BMap.Point(longitude, latitude), p2 = new BMap.Point(options.longitude, options.latitude);
						let driving = new BMap.DrivingRoute(map, {renderOptions:{map:map, autoViewport:true}});
						driving.search(p1, p2);
					}else{
						$.overloadError('failed:'+this.getStatus());
					}
				}, {enableHighAccuracy:true});
				//IP定位
				//let city = new BMap.LocalCity();
				//city.get(function(r){let cityName = r.name;});
			}else{
				let navView = $('<div class="navView">\
					<select><option value="0">最少时间</option><option value="1">最短距离</option><option value="2">避开高速</option></select>\
					<input type="text" placeholder="起始地" /><input type="text" placeholder="目的地" />\
					<a href="javascript:void(0)">查询</a>\
					<div style="height:0;font-size:0;clear:both;overflow:hidden;box-sizing:border-box;"></div>\
				</div>');
				_this.css({position:'relative'}).append(navView);
				navView.css({position:'absolute', 'z-index':200, left:0, bottom:0, width:'100%', height:'auto', 'box-sizing':'border-box',
					background:'rgba(255,255,255,0.95)', padding:'10px 10px 7px 10px'})
				.find('select,input').css({'float':'left', width:'80px', height:'24px', 'font-size':'12px', border:'1px #ccc solid', 'border-radius':'3px',
					'margin-right':'10px', 'margin-bottom':'3px'});
				navView.find('input').css({width:'100px', 'padding-left':'5px', 'box-sizing':'border-box'});
				navView.find('a').css({'float':'left', display:'block', width:'50px', height:'24px', 'line-height':'24px', 'font-size':'12px',
					background:'#d60000', color:'#fff', 'border-radius':'3px', 'text-align':'center', 'text-decoration':'none', 'margin-bottom':'3px'});
				let routePolicy = [BMAP_DRIVING_POLICY_LEAST_TIME, BMAP_DRIVING_POLICY_LEAST_DISTANCE, BMAP_DRIVING_POLICY_AVOID_HIGHWAYS],
					navType = Math.floor(options.navType);
				if(navType>-1 && navType<3)navView.find('select').hide();
				navView.find('a').click(function(){
					let i = (navType>-1 && navType<3) ? navType : Number(navView.find('select').val()),
						start = navView.find('input:eq(0)').val(), end = navView.find('input:eq(1)').val();
					if(!start.length || !end.length){
						$.overloadError('请输入起始地与目的地');
						return;
					}
					map.clearOverlays();
					let driving = new BMap.DrivingRoute(map, {renderOptions:{
						map: map,
						panel: options.panel, //显示路线详情面板, 面板id
						enableDragging: options.navDrag,
						autoViewport: true
					}, policy:routePolicy[i]});
					driving.search(start, end, {waypoints:options.navPoints});
				});
			}
		}
		function setOverlays(longitude, latitude, clear){
			if(typeof clear === 'undefined' || clear)map.clearOverlays();
			let newPoint = new BMap.Point(longitude, latitude),
				marker = new BMap.Marker(newPoint); //创建标注
			map.addOverlay(marker); //将标注添加到地图中
			map.panTo(newPoint);
		}
	});
};

//百度静态地图
$.fn.baiduMapImage = function(options){
	options = $.extend({
		longitude: '', //经度, 113.440685
		latitude: '', //纬度, 23.136588
		zoom: 16, //默认地图级别
	}, $('body').data('baiduMapImage.options'), options);
	return this.each(function(){
		let _this = $(this), width = _this.width(), height = _this.height(),
			longitude = _this.attr('longitude')||options.longitude, latitude = _this.attr('latitude')||options.latitude, zoom = _this.attr('zoom')||options.zoom;
		_this.attr('src', 'http://api.map.baidu.com/staticimage?center='+longitude+','+latitude+'&copyright=1&zoom='+zoom+'&width='+width+'&height='+height+'&markers='+longitude+','+latitude);
		//&dpiType=ph
	});
};

//高德地图
//https://lbs.amap.com/api/javascript-api/guide/abc/prepare
//<script type="text/javascript" src="http://webapi.amap.com/maps?v=1.4.15&key=2333d8c7fcb90e0c6d600d9d40b759dc"></script>
$.fn.amap = function(options){
	if(typeof options === 'boolean' && options)return this.data('amap');
	options = $.extend({
		key: '2333d8c7fcb90e0c6d600d9d40b759dc', //地图应用key(Web端)
		longitude: '', //经度, 113.434065
		latitude: '', //纬度, 23.130378
		zoom: 16, //默认地图级别
		zoomEnable: true, //可否缩放
		dragEnable: true, //可否拖动
		setOverlaysTime: 0, //加载完毕后延迟加载Marker的时间
		lngObj: '', //存放经度数值的控件expr, 一般为输出用
		latObj: '', //存放纬度数值的控件expr
		minZoom: 1, //地图允许的最小级别
		maxZoom: 19, //地图允许的最大级别
		centerCity: '广州', //默认显示城市, options.longitude,options.latitude两个值为空才有效
		controlType: 0, //控件类型, 1平移和缩放按钮, 2平移按钮, 3缩放按钮
		controlAnchor: 1, //控件位置, 0左上, 1右上
		getCoordinate: null, //点击地图获取坐标且返回经度与纬度, function
		isNav: false, //使用导航功能
		autoNav: true, //自动使用地点导航, 以当前位置导航到 (options.longitude, options.latitude)
		navType: -1, //非自动导航时的驾车策略, 0:最少时间, 1:最短距离, 2:避开高速
		navDrag: false, //自定义途经点(拖曳线路)
		navPoints: [], //自定义途经点(使用文字设定)
		panel: '', //显示路线详情面板,面板id
		complete: null //地图加载完成
	}, $('body').data('amap.options'), options);
	return this.each(function(k){
		let _this = $(this), map = _this.data('amap'), _id = _this.attr('id');
		if(!!map){
			if(!isNaN(options.longitude) && !isNaN(options.latitude) && Number(options.longitude)>0 && Number(options.latitude)>0){
				if(!!_this.data('lngObj'))$(_this.data('lngObj')).val(options.longitude);
				if(!!_this.data('latObj'))$(_this.data('latObj')).val(options.latitude);
				setOverlays(options.longitude, options.latitude);
				if(!!_this.data('getCoordinate'))_this.data('getCoordinate').call(_this, options.longitude, options.latitude);
			}
			return true;
		}
		if(options.lngObj.length)_this.data('lngObj', options.lngObj);
		if(options.latObj.length)_this.data('latObj', options.latObj);
		if($.isFunction(options.getCoordinate))_this.data('getCoordinate', options.getCoordinate);
		if(!!!_id){
			let td = new Date();
			_id = td.getFullYear()+''+(td.getMonth()+1)+''+td.getDate()+''+td.getHours()+''+td.getMinutes()+''+td.getSeconds()+''+k;
			_this.attr('id', _id);
		}
		if(typeof window.AMap === 'undefined'){
			$.getScript('http://webapi.amap.com/maps?v=1.4.15&key='+options.key, initMap);
		}else{
			initMap();
		}
		function initMap(){
			map = new AMap.Map(_id,
				{resizeEnable:true, zooms:[options.minZoom, options.maxZoom], center:[Number(options.longitude), Number(options.latitude)], zoom:options.zoom});
			//map.panTo(new AMap.LngLat(113.434065, 23.130378)); //切换地图到指定位置
			//map.setCity(cityName); //用城市名设置地图中心点
			//map.setZoom(14); //设置地图当前的放大级别
			//AMap.GeometryUtil.distance(pointA, pointB).toFixed(2); //获取两点距离,单位米,保留小数点后两位,pointA=new AMap.LngLat
			//let polyline = new AMap.Polyline({path:[pointA, pointB], strokeColor:'blue', borderWeight:6, lineJoin:'round'}); //定义折线
			//map.add(polyline); //添加折线到地图上
			//let control = new AMap.Scale(); //左下角,比例尺
			//map.addControl(control); //添加控件
			//map.removeControl(control); //移除控件
			map.setStatus({
				doubleClickZoom:options.zoomEnable, zoomEnable:options.zoomEnable, rotateEnable:options.zoomEnable,
				scrollWheel:options.zoomEnable, touchZoom:options.zoomEnable,
				dragEnable:options.dragEnable
			});
			_this.data('amap', map);
			AMap.plugin(['AMap.ToolBar', 'AMap.Driving', 'AMap.Geocoder', 'AMap.Geolocation'], function(){
				if(options.controlType>0){
					let toolBar = new AMap.ToolBar({
						position: (options.controlAnchor === 0 ? 'LT' : 'RT'),
						direction: (options.controlType === 1 || options.controlType === 2),
						ruler: (options.controlType === 1 || options.controlType === 3)
					});
					map.addControl(toolBar);
				}
				if(options.isNav){
					if(options.autoNav){
						let geolocation = new AMap.Geolocation();
						map.addControl(geolocation);
						geolocation.getCurrentPosition(function(e){
							if(e.info === 'SUCCESS'){
								let longitude = e.position.getLng(), latitude = e.position.getLat();
								let p1 = new AMap.LngLat(longitude, latitude), p2 = new AMap.LngLat(options.longitude, options.latitude);
								let driving = new AMap.Driving({
									map: map,
									panel: options.panel,
									policy: AMap.DrivingPolicy.REAL_TRAFFIC
								});
								driving.search(p1, p2, {waypoints:options.navPoints});
							}else{
								if(typeof e.message !== 'undefined')$.overloadError('failed:'+e.message);
							}
						});
						//IP定位
						/*AMap.plugin(['AMap.CitySearch'], function(){
							let city = AMap.CitySearch();
							city.getLocalCity(function(status, result){
								if(status === 'complete'){
									cityName = result.city;
								}
							});
						});*/
					}else{
						let navView = $('<div class="navView">\
							<select><option value="0">快捷模式</option><option value="1">经济模式</option><option value="2">最短距离</option><option value="2">实时路况</option></select>\
							<input type="text" placeholder="起始地" /><input type="text" placeholder="目的地" />\
							<a href="javascript:void(0)">查询</a>\
							<div style="height:0;font-size:0;clear:both;overflow:hidden;box-sizing:border-box;"></div>\
						</div>');
						_this.css({position:'relative'}).append(navView);
						navView.css({position:'absolute', 'z-index':200, left:0, bottom:0, width:'100%', height:'auto', 'box-sizing':'border-box',
							background:'rgba(255,255,255,0.95)', padding:'10px 10px 7px 10px'})
						.find('select,input').css({'float':'left', width:'80px', height:'24px', 'font-size':'12px', border:'1px #ccc solid', 'border-radius':'3px',
							'margin-right':'10px', 'margin-bottom':'3px'});
						navView.find('input').css({width:'100px', 'padding-left':'5px', 'box-sizing':'border-box'});
						navView.find('a').css({'float':'left', display:'block', width:'50px', height:'24px', 'line-height':'24px', 'font-size':'12px',
							background:'#d60000', color:'#fff', 'border-radius':'3px', 'text-align':'center', 'text-decoration':'none', 'margin-bottom':'3px'});
						let routePolicy = [AMap.DrivingPolicy.LEAST_TIME, AMap.DrivingPolicy.LEAST_FEE,
								AMap.DrivingPolicy.LEAST_DISTANCE, AMap.DrivingPolicy.REAL_TRAFFIC],
							navType = Math.floor(options.navType);
						if(navType>-1 && navType<4)navView.find('select').hide();
						navView.find('a').click(function(){
							let i = (navType>-1 && navType<4) ? navType : Number(navView.find('select').val()),
								start = navView.find('input:eq(0)').val(), end = navView.find('input:eq(1)').val();
							if(!start.length || !end.length){
								$.overloadError('请输入起始地与目的地');
								return;
							}
							let geo = new AMap.Geocoder(); //地理编码,返回地理编码结果
							geo.getLocation(start, function(status, result){
								if(status === 'complete'){
									start = result.geocodes[0].location;
									geo.getLocation(end, function(status, result){
										if(status === 'complete'){
											end = result.geocodes[0].location;
											let driving = new AMap.Driving({
												map: map,
												panel: options.panel, //显示路线详情面板, 面板id
												policy: routePolicy[i]
											});
											driving.search(start, end, {waypoints:options.navPoints});
										}else{
											$.overloadError(result.info);
										}
									});
								}else{
									$.overloadError(result.info);
								}
							});
						});
					}
				}
			});
			if($.isFunction(options.complete))map.on('complete', function(){
				options.complete.call(_this, map);
			});
			let lng = 0, lat = 0;
			if(!isNaN(options.longitude) && !isNaN(options.latitude) && Number(options.longitude)>0 && Number(options.latitude)>0){
				if(options.lngObj.length)$(options.lngObj).val(options.longitude);
				if(options.latObj.length)$(options.latObj).val(options.latitude);
				map.setZoomAndCenter(options.zoom, new AMap.LngLat(options.longitude, options.latitude)); //初始化地图,设置中心点坐标和地图级别
				//map.setCenter(new AMap.LngLat(options.longitude, options.latitude));
				lng = options.longitude;
				lat = options.latitude;
			}else{
				map.setCity(options.centerCity);
				map.setZoom(options.zoom);
				lng = map.getCenter().getLng();
				lat = map.getCenter().getLat();
			}
			setTimeout(function(){setOverlays(lng, lat)}, options.setOverlaysTime);
			if($.isFunction(options.getCoordinate) || (options.lngObj.length && options.latObj.length))map.on('click', function(e){
				let lng = e.lnglat.getLng(), lat = e.lnglat.getLat();
				if(options.lngObj.length)$(options.lngObj).val(lng);
				if(options.latObj.length)$(options.latObj).val(lat);
				setOverlays(lng, lat);
				if($.isFunction(options.getCoordinate))options.getCoordinate.call(_this, lng, lat);
			});
		}
		function setOverlays(longitude, latitude, clear){
			if(typeof clear === 'undefined' || clear)map.clearMap();
			let newPoint = new AMap.LngLat(longitude, latitude),
				marker = new AMap.Marker({ //创建标注
					icon: 'https://webapi.amap.com/theme/v1.3/markers/n/mark_r.png',
					//title: '吉山',
					position: newPoint
				});
			map.add(marker); //将标注添加到地图中
			map.panTo(newPoint);
		}
	});
};

//高德静态地图
$.fn.amapImage = function(options){
	options = $.extend({
		key: '36323220d0a1e42f2c9f921bd8026533', //地图应用key(Web服务)
		longitude: '', //经度, 113.434065
		latitude: '', //纬度, 23.130378
		zoom: 15, //默认地图级别
	}, $('body').data('amapImage.options'), options);
	return this.each(function(){
		let _this = $(this), width = _this.width(), height = _this.height(), longitude = _this.attr('longitude')||options.longitude, latitude = _this.attr('latitude')||options.latitude, zoom = _this.attr('zoom')||options.zoom, key = _this.attr('key')||options.key;
		_this.attr('src', 'https://restapi.amap.com/v3/staticmap?location='+longitude+','+latitude+'&zoom='+zoom+'&scale=2&size='+width+'*'+height+'&markers=large,,:'+longitude+','+latitude+'&key='+key);
	});
};

//固定浮动
$.fn.fixed = function(options){
	options = $.extend({
		dir: 'top', //保持方向
		stay: 0, //保持所在位置, [number|function]
		fixed: null, //固定飘浮后执行
		normal: null, //取消固定飘浮后执行
		scroll: null //滚动时执行
	}, options);
	return this.each(function(){
		let _this = $(this), dir = options.dir, stay = $.isFunction(options.stay)?options.stay.call(_this):$.unit(options.stay), pos = _this.position()||{top:0, left:0}, lt = pos[dir] - stay;
		function position(){
			let scroll = document.documentElement.scrollTop + document.body.scrollTop;
			if(scroll>lt){
				if(!$.browser.ie6)_this.css('position', 'fixed');
				_this.css(dir, ($.browser.ie6 ? scroll+stay : stay)).removeData('normal');
				if($.isFunction(options.fixed) && !!!_this.data('fixed')){
					_this.data('fixed', true);
					options.fixed.call(_this);
				}
			}else{
				_this.css('position', '').css(dir, '').removeData('fixed');
				if($.isFunction(options.normal) && !!!_this.data('normal'))options.normal.call(_this);
			}
			if($.isFunction(options.scroll))options.scroll.call(_this);
		}
		function restay(){
			stay = $.isFunction(options.stay)?options.stay():$.unit(options.stay);
		}
		$(window).resize(restay);
		$(window).scroll(position);
		position();
	});
};

//拖动调整大小
$.fn.resizable = function(options){
	if(typeof(options) === 'string'){
		if(options === 'disable'){
			this.removeClass('resizable').find('.resizable-handler').css('display', 'none');
		}else if(options === 'enable'){
			this.addClass('resizable').find('.resizable-handler').css('display', '');
		}
		return this;
	}
	options = $.extend({
		handles: 'r,b,rb',
		helper: false,
		helperStyle: {},
		maxWidth: $().width(),
		maxHeight: $().height(),
		minWidth: 20,
		minHeight: 20,
		start: null,
		resize: null,
		stop: null
	}, $('body').data('resizable.options'), options);
	return this.each(function(){
		if($(this).is('input, textarea, select, button, img, canvas'))return true;
		if(!!$(this).data('resizable')){$(this).resizable('enable');return true}
		let _this = $(this).data('resizable', true).addClass('resizable'), handles = options.handles;
		if(_this.css('position') === 'static')_this.css('position', 'relative');
		if(handles.indexOf('all')>-1)handles = 'r,b,t,l,rb';
		let handle = handles.split(',');
		for(let i=0; i<handle.length; i++){
			let han = $.trim(handle[i]), div = $('<div></div>').appendTo(_this), pos = {position:'absolute', 'z-index':9999}, hand = '';
			switch(han){
				case 'r':
					pos.top = 0;
					pos.right = -5;
					pos.width = 7;
					pos.height = '100%';
					hand = 'e';
					break;
				case 'b':
					pos.left = 0;
					pos.bottom = -5;
					pos.width = '100%';
					pos.height = 7;
					hand = 's';
					break;
				case 't':
					pos.top = -5;
					pos.left = 0;
					pos.width = '100%';
					pos.height = 7;
					hand = 'n';
					break;
				case 'l':
					pos.top = 0;
					pos.left = -5;
					pos.width = 7;
					pos.height = '100%';
					hand = 'w';
					break;
				case 'rb':
					pos.right = 1;
					pos.bottom = 1;
					pos.width = 7;
					pos.height = 7;
					hand = 'se';
					div.css({'border-right':'1px solid #888', 'border-bottom':'1px solid #888'});
					break;
			}
			pos.cursor = hand+'-resize';
			div.addClass('resizable-handler').css(pos).unselect().on('mousedown touchstart', {dir:hand}, start);
		}
		function get(key) {
			let n = parseInt(_this.css(key));
			if(isNaN(n))return 0;
			return n;
		}
		function start(e) {
			if($.isFunction(options.start))options.start.call(_this, e);
			let data = {
				target: _this,
				dir: e.data.dir,
				startLeft: get('left'),
				startTop: get('top'),
				left: get('left'),
				top: get('top'),
				startX: e.pageX,
				startY: e.pageY,
				startWidth: _this.outerWidth(false),
				startHeight: _this.outerHeight(false),
				width: _this.outerWidth(false),
				height: _this.outerHeight(false),
				deltaWidth: _this.outerWidth(false) - _this.width(),
				deltaHeight: _this.outerHeight(false) - _this.height()
			};
			$('body').css('cursor', data.dir+'-resize');
			if(options.helper){
				let helper = $('<div class="resizable-helper"></div>').appendTo(_this);
				helper.css({position:'absolute', 'z-index':99999, left:-1, top:-1, display:'none', border:'1px solid #f00', width:_this.width(), height:_this.height()});
				helper.css(options.helperStyle).unselect();
				data.target = helper;
			}
			$(document).on('mousemove touchmove', data, resize).on('mouseup touchend', data, stop);
		}
		function resize(e){
			let data = e.data;
			data.target.css('display', 'block');
			if(data.dir.indexOf('e') > -1){
				let w = data.startWidth + e.pageX - data.startX - data.deltaWidth;
				w = Math.min(Math.max(w, options.minWidth), options.maxWidth);
				data.width = w;
				data.target.css('width', w);
			}
			if(data.dir.indexOf('s') > -1){
				let h = data.startHeight + e.pageY - data.startY - data.deltaHeight;
				h = Math.min(Math.max(h, options.minHeight), options.maxHeight);
				data.height = h;
				data.target.css('height', h);
			}
			if(data.dir.indexOf('n') > -1){
				let top = data.startTop + e.pageY - data.startY;
				let h = data.startHeight + data.startTop - top - data.deltaHeight;
				if(h>=options.minHeight && h<=options.maxHeight){
					data.height = h;
					data.target.css('height', h);
				}
			}
			if(data.dir.indexOf('w') > -1){
				let left = data.startLeft + e.pageX - data.startX;
				let w = data.startWidth + data.startLeft - left - data.deltaWidth;
				if (w>=options.minWidth && w<=options.maxWidth) {
					data.width = w;
					data.target.css('width', w);
				}
			}
			if($.isFunction(options.resize))options.resize.call(data.target, e);
		}
		function stop(e){
			$('body').css('cursor', '');
			if(options.helper){
				_this.css({width:e.data.width-e.data.deltaWidth, height:e.data.height-e.data.deltaHeight});
				_this.find('.resizable-helper').remove();
			}
			$(document).off('mousemove touchmove', resize).off('mouseup touchend', stop);
			if($.isFunction(options.stop))options.stop.call(_this, e);
		}
	});
};

//等比例缩放加载图片
$.fn.loadpic = function(options){
	options = $.extend({
		src: null, //直接指定图片的路径
		width: 0, //最大宽度, [数字|百分比(根据调用的元素的实际宽度)]
		height: 0, //最大高度, [数字|百分比(根据调用的元素的实际高度)]
		fill: 0, //最大宽高(填充容器,宽高为0有效)
		range: false, //expr容器内所有图片缩放(设置后只有width、height、load、error、after有效,其他参数无效)
		lazyload: false, //图片延迟加载
		centerX: true, //图片自动水平居中
		centerY: true, //图片自动垂直居中
		imgW: 0, //强制设置图片宽
		imgH: 0, //强制设置图片高
		parentM: true, //容器是否自动居中
		parentW: 0, //设置容器宽
		parentH: 0, //设置容器高
		autoWH: true, //使用插件设置宽高
		fillAll: false, //填充容器时强制填满
		resize: true, //false:只使用载入特效,不改变图片大小
		overflow: true, //容器防溢出
		wrap: true, //使用p包裹且显示加载效果
		fadeIn: 0, //使用fadeIn渐显
		load: '', //加载动画的[图片|样式],为空即使用内置
		error: '/images/nopic.png', //加载失败显示的图片
		after: null, //完全显示后执行
		complete: null //所有图片载入后执行
	}, $('body').data('loadpic.options'), options);
	let total = 0, count = this.find('img').length;
	return this.each(function(){
		let _this = $(this), width = options.width, height = options.height, fill = options.fill, range = options.range, lazyload = options.lazyload,
			centerX = options.centerX, centerY = options.centerY, imgW = options.imgW, imgH = options.imgH, parentM = options.parentM,
			parentW = options.parentW, parentH = options.parentH, autoWH = options.autoWH, fillAll = options.fillAll,
			overflow = options.overflow, fadeIn = options.fadeIn, load = options.load, error = options.error, after = options.after,
			complete = options.complete, doc = $.document();
		if(!load.length)load = '.preloader preloader-gray';
		if(!width && !height && !fill)fill = 120;
		let t = {}, tp, loading;
		if(!range){
			if(_this.removePlug('loadpic'))_this.removeData('loadpiced');
			t = _this.is('img') ? _this : _this.find('img:eq(0)');
			let src = options.src||t.attr('src')||t.attr('url'), img = new Image();
			t.attr('url', src).removeAttr('src');
			_this.css({'text-align':'left'});
			if(parentM)_this.css({'margin-left':'auto', 'margin-right':'auto'});
			if(autoWH && (width+'').indexOf('%') === -1)_this.css({width:(imgW||parentW||width||fill)});
			if(autoWH && (height+'').indexOf('%') === -1)_this.css({height:(imgH||parentH||height||fill)});
			if(overflow)_this.css('overflow','hidden');
			if(options.wrap){
				if(t.parent().is('a')){t.parent().wrap('<p></p>')}else{t.wrap('<p></p>')}
				tp = t.parents('p');
				tp.hide();
				let loadType = 'class="loadpic" style="background-image:url('+load+');"';
				if(load.charAt(0) === '.')loadType = 'class="loadpic '+load.substring(1)+'"';
				loading = $('<div '+loadType+'></div>');
				tp.before(loading);
				loading.css({
					'margin-left': Number((_this.width()-loading.width())/2)+'px',
					'margin-top': Number((_this.height()-loading.height())/2)+'px'
				});
			}else{
				tp = $([]);
				loading = $([]);
			}
			if(!src){errimg();return true}
			function startLoad(){
				if($.browser.ie8){
					img.onload = function(){transrc(img, src)};
					img.onerror = function(){errimg()};
					img.src = src;
				}else{
					img.src = src;
					$(img).on('load', function(){
						transrc(this, src);
					}).on('error', function(){
						errimg();
					});
				}
			}
			if(/^https?:\/\//.test(src)){
				t.attr('src', src);
			}else{
				if(lazyload){
					let offsetY = _this.position().top;
					$(window).on('scroll', function(){
						if(_this.data('loadpiced'))return;
						if(document.documentElement.scrollTop+document.body.scrollTop+doc.clientHeight>=offsetY){
							_this.data('loadpiced', true);
							startLoad();
						}
					});
					$(window).trigger('scroll');
				}else{
					startLoad();
				}
			}
		}else{
			if(!width && !height){$.overloadError('When use range parameter must be set the width or height');return true}
			_this.find('img').each(function(){
				let loadType = 'class="loadpic" style="background-image:url('+load+');"';
				if(load.charAt(0) === '.')loadType = 'class="loadpic '+load.substring(1)+'"';
				let rangeImg = $(this), rangeSrc = rangeImg.attr('src')||rangeImg.attr('url'), img = new Image(), rangeLoad = $('<div '+loadType+'></div>');
				if(rangeImg.removePlug('loadpic'))rangeImg.removeData('loadpiced');
				rangeImg.hide().attr('url', rangeSrc).removeAttr('src').before(rangeLoad);
				if(!rangeSrc){errimg(rangeImg, rangeLoad);return true}
				function rangeStartLoad(){
					if($.browser.ie8){
						img.onload = function(){transrc(img, rangeSrc, rangeImg, rangeLoad)};
						img.onerror = function(){errimg(rangeImg, rangeLoad)};
						img.src = rangeSrc;
					}else{
						img.src = rangeSrc;
						$(img).on('load', function(){
							transrc(this, rangeSrc, rangeImg, rangeLoad);
						}).on('error', function(){
							errimg(rangeImg, rangeLoad);
						});
					}
				}
				if(/^https?:\/\//.test(rangeSrc)){
					rangeImg.attr('src', rangeSrc);
				}else{
					if(lazyload){
						let offsetY = rangeImg.position().top;
						$(window).on('scroll', function(){
							if(rangeImg.data('loadpiced'))return;
							if(document.documentElement.scrollTop+document.body.scrollTop+doc.clientHeight>=offsetY){
								rangeImg.data('loadpiced', true);
								rangeStartLoad();
							}
						});
						$(window).trigger('scroll');
					}else{
						rangeStartLoad();
					}
				}
			});
		}
		function transrc(img, src, rangeImg, rangeLoad){
			let dem = {}, w = width, h = height;
			if(!imgW || !imgH){
				dem.w = img.width;
				dem.h = img.height;
				if(dem.w === 0 || dem.h === 0){errimg();return}
				if((width+'').indexOf('%') > -1)w = (width.replace('%','')/100) * _this.width();
				if((height+'').indexOf('%') > -1)h = (height.replace('%','')/100) * _this.height();
				if(w && h && !fill){
					if(dem.w/dem.h >= w/h){
						if(dem.w>w){
							dem.h = (dem.h*w)/dem.w;
							dem.w = w;
						}
					}else{
						if(dem.h>h){
							dem.w = (dem.w*h)/dem.h;
							dem.h = h;
						}
					}
				}else if(w !== 0 && h === 0){
					if(dem.w>w){
						dem.h = (dem.h*w)/dem.w;
						dem.w = w;
					}
				}else if(w === 0 && h !== 0){
					if(dem.h>h){
						dem.w = (dem.w*h)/dem.h;
						dem.h = h;
					}
				}else{ //填满容器
					_this.css('overflow', 'hidden');
					if(dem.w/dem.h > 1){
						if(dem.h>fill){
							dem.w = (dem.w*fill)/dem.h;
							dem.h = fill;
						}else{
							if(fillAll){
								dem.w = (dem.w*fill)/dem.h;
								dem.h = fill;
							}
						}
					}else{
						if(dem.w>fill){
							dem.h = (dem.h*fill)/dem.w;
							dem.w = fill;
						}else{
							if(fillAll){
								dem.h = (dem.h*fill)/dem.w;
								dem.w = fill;
							}
						}
					}
				}
				dem.w = Number(dem.w);
				dem.h = Number(dem.h);
			}else{
				dem.w = imgW;
				dem.h = imgH;
			}
			if(!range){
				if((width+'').indexOf('%') > -1)_this.width(dem.w);
				if((height+'').indexOf('%') > -1)_this.height(dem.h);
				if(options.resize){
					tp.css({width:dem.w, height:dem.h, margin:0, 'text-align':'center'});
					if(centerX)tp.css('margin-left', Number((tp.parent().width()-dem.w)/2)+'px');
					if(centerY)tp.css('margin-top', Number((tp.parent().height()-dem.h)/2)+'px');
					t.removeAttr('width');
					t.removeAttr('height');
					t.css({width:dem.w, height:dem.h});
				}
				t.attr('src', src);
				loading.remove();
				if(fadeIn){
					tp.fadeIn(fadeIn, function(){
						total++;
						if($.isFunction(after))after(t);
						if($.isFunction(complete) && count === total)complete(_this);
					});
				}else{
					tp.show();
					total++;
					if($.isFunction(after))after(t);
					if($.isFunction(complete) && count === total)complete(_this);
				}
			}else{
				if(options.resize){
					rangeImg.removeAttr('width');
					rangeImg.removeAttr('height');
					rangeImg.css({width:dem.w, height:dem.h});
				}
				rangeImg.attr('src', src);
				rangeLoad.remove();
				if(fadeIn){
					rangeImg.fadeIn(fadeIn, function(){
						total++;
						if($.isFunction(after))after(rangeImg);
						if($.isFunction(complete) && count === total)complete(_this);
					});
				}else{
					rangeImg.show();
					total++;
					if($.isFunction(after))after(rangeImg);
					if($.isFunction(complete) && count === total)complete(_this);
				}
			}
		}
		function errimg(rangeImg, rangeLoad){
			let tmp = new Image();
			if($.browser.ie8){
				tmp.onload = function(){transrc(tmp, error, rangeImg, rangeLoad)};
				tmp.onerror = function(){errimg()};
				tmp.src = error;
			}else{
				tmp.src = error;
				$(tmp).on('load', function(){
					transrc(this, error, rangeImg, rangeLoad);
				});
			}
		}
	});
};

//等比例缩放加载背景图
$.fn.loadbackground = function(options){
	options = $.extend({
		src: '', //直接指定图片的路径
		load: '', //加载动画的[图片|样式],为空即使用内置
		loadWH: 0, //加载动画的宽高,0为自动
		error: '/images/nopic.png', //加载失败显示的图片
		fadeIn: 300, //使用fadeIn渐显
		complete: null //图片载入后执行
	}, $('body').data('loadbackground.options'), options);
	return this.each(function(){
		let _this = $(this), loading, src = options.src, load = options.load, loadWH = options.loadWH,
			error = options.error, fadeIn = options.fadeIn, complete = options.complete;
		if(!!_this.data('loadbackground'))return true;
		_this.data('loadbackground', true);
		if(!src)src = _this.attr('url') || _this.attr('src') || _this.attr('background-image');
		if(!src || src === 'none')src = _this.attr('href');
		if(!src)return true;
		if(!load.length)load = '.preloader preloader-gray';
		if(/url\(['"]?(.+?)['"]?\)/i.test(src))src = src.replace(/url\(['"]?(.+?)['"]?\)/i, '$1');
		let loadType = ' style="background-image:url('+load+');"';
		if(load.charAt(0) === '.')loadType = ' class="'+load.substring(1)+'"';
		loading = $('<div'+loadType+'></div>');
		let img = new Image();
		if(_this.css('position') === 'static')_this.css('position', 'relative');
		_this.css('background-image', 'none').append(loading);
		function startLoad(t){
			if($.browser.ie8){
				img.onload = function(){transrc.call(t, src)};
				img.onerror = function(){transrc.call(t, error)};
				img.src = src;
			}else{
				img.src = src;
				$(img).on('load', function(){
					transrc.call(t, src);
				}).on('error', function(){
					transrc.call(t, error);
				});
			}
		}
		function transrc(src){
			loading.remove();
			this.css({'background-image':'url('+src+')'});
			if(src === error){
				let size = 'cover', wh = $.browser.mobile ? 115 : 230;
				if(wh>_this.width() || wh>_this.height()){
					size = this.width()>this.height() ? 'auto 100%' : '100% auto';
				}else{
					size = wh+'px '+wh+'px';
				}
				this.css('background-size', size);
			}
			if(fadeIn){
				this.css('opacity', 0);
				this.animate({opacity:1}, fadeIn);
			}
			if($.isFunction(complete))complete.call(this);
		}
		if(loading.width()>_this.width() || loading.height()>_this.height()){
			let wh = loadWH>0 ? loadWH : (_this.width()>_this.height() ? _this.height() : _this.width()) / 3;
			loading.css({width:wh, height:wh});
		}
		loading.css({
			'background-repeat': 'no-repeat',
			'background-position': 'center center',
			'background-size': 'cover',
			'z-index': 999,
			position: 'absolute',
			left: '50%',
			top: '50%',
			'margin-left': (-loading.width()/2)+'px',
			'margin-top': (-loading.height()/2)+'px',
			'line-height': 'inherit'
		});
		startLoad(_this);
	});
};

//相册(仿APP,支持移动端), options为true即重新构建
/*需要引入插件
<link type="text/css" href="/js/photoswipe/photoswipe.css" rel="stylesheet" />
<link type="text/css" href="/js/photoswipe/default-skin/default-skin.css" rel="stylesheet" />
<script type="text/javascript" src="/js/photoswipe/photoswipe.min.js"></script>
<script type="text/javascript" src="/js/photoswipe/photoswipe-ui-default.min.js"></script>
*/
$.fn.photoBrowser = function(options){
	if(typeof options === 'undefined'){
		this.each(function(){
			let _this = $(this), swiper = _this.data('photoBrowser-swiper');
			if(typeof(swiper) !== 'undefined' && swiper !== null && typeof(swiper.destroy) === 'function')swiper.destroy();
		});
		if(!!this.data('tapper'))this.tapper(false);
		if(!!this.data('photoBrowser-options'))options = this.data('photoBrowser-options');
	}
	options = $.extend({
		minimal: true, //精简版相册
		spacing: 0, //图片之间的距离(百分比,0~1)
		loop: false //循环拖曳滚动
	}, $('body').data('photoBrowser.options'), options);
	let id = this.data('pswp-id');
	if(!!!id || !$('.'+id).length){
		id = 'pswp_' + parseInt(String(Math.random() * 1000));
		let html = $('<div class="pswp '+id+'" tabindex="-1" role="dialog" aria-hidden="true">\
			<div class="pswp__bg"></div>\
			<div class="pswp__scroll-wrap">\
				<div class="pswp__container"><div class="pswp__item"></div><div class="pswp__item"></div><div class="pswp__item"></div></div>\
				<div class="pswp__ui pswp__ui--hidden">\
					<div class="pswp__top-bar">\
						<div class="pswp__counter"></div>\
						<button class="pswp__button pswp__button--close" title="Close (Esc)"></button>\
						<button class="pswp__button pswp__button--share" title="Share"></button>\
						<button class="pswp__button pswp__button--fs" title="Toggle fullscreen"></button>\
						<button class="pswp__button pswp__button--zoom" title="Zoom in/out"></button>\
						<div class="pswp__preloader">\
							<div class="pswp__preloader__icn"><div class="pswp__preloader__cut"><div class="pswp__preloader__donut"></div></div></div>\
						</div>\
					</div>\
					<div class="pswp__share-modal pswp__share-modal--hidden pswp__single-tap"><div class="pswp__share-tooltip"></div></div>\
					<button class="pswp__button pswp__button--arrow--left" title="Previous (arrow left)"></button>\
					<button class="pswp__button pswp__button--arrow--right" title="Next (arrow right)"></button>\
					<div class="pswp__caption"><div class="pswp__caption__center"></div></div>\
				</div>\
			</div>\
		</div>');
		$(document.body).append(html);
	}
	let ths = this.data('pswp-id', id), pswpElement = $('.'+id)[0], items = [];
	this.each(function(i){
		let _this = $(this), url = _this.attr('url')||_this.attr('href')||_this.attr('src'), title = _this.attr('alt')||_this.attr('title')||'';
		items.push({
			src: url,
			title: title,
			el: this
		});
		let image = new Image();
		if($.browser.ie8){
			image.onload = function(){
				items[i].w = image.width;
				items[i].h = image.height;
			};
			image.src = url;
		}else{
			image.src = url;
			$(image).on('load', function(){
				items[i].w = image.width;
				items[i].h = image.height;
			});
		}
	});
	//http://photoswipe.com/documentation/options.html
	let opt = {
		index: 0,
		bgOpacity: 0.9,
		history: false,
		pinchToClose: false,
		closeOnScroll: false,
		closeOnVerticalDrag: false,
		getThumbBoundsFn: function(index){
			let thumb = items[index].el, pageYScroll = window.pageYOffset||document.documentElement.scrollTop, rect = thumb.getBoundingClientRect();
			return {x:rect.left, y:rect.top+pageYScroll, w:rect.width};
		},
		spacing: options.spacing,
		loop: options.loop
	};
	if(options.minimal){
		//opt.mainClass = 'pswp--minimal--dark';
		opt.barsSize = {top:0, bottom:0};
		opt.captionEl = true;
		opt.fullscreenEl = false;
		opt.shareEl = false;
		opt.tapToClose = true;
		opt.tapToToggleControls = false;
	}
	return this.data('photoBrowser-options', options).click(function(){
		opt.index = ths.index(this);
		let swiper = new PhotoSwipe(pswpElement, PhotoSwipeUI_Default, items, opt);
		$(this).data('photoBrowser-swiper', swiper);
		swiper.init();
		return false;
	});
};

//提示框
$.fn.alert = function(options){
	if($.isPlainObject(options))options = $.extend(options, {html:this.html()});
	alertUI(options);
	return this;
};

//提示窗链接
$.fn.alertURL = function(options){
	options = $.extend({
		width: 630, //宽度
		height: 400 //高度
	}, options);
	return this.each(function(){
		let _this = $(this), href = _this.attr('href'), width = options.width, height = options.height;
		if(!!_this.data('alertURL'))return true;
		if(href.substring(0,11) === 'javascript:' || href.substring(0,1) === '#')return true;
		_this.data('alertURL', true);
		_this.click(function(){
			let opt, href = _this.attr('href'), title = _this.attr('title')||_this.attr('alt')||_this.html()||_this.val()||'Title';
			if(/\bwidth=(\d+)/i.test(href))width = href.replace(/^.+?\bwidth=(\d+)\b.*$/i, '$1');
			if(/\bheight=(\d+)/i.test(href))height = href.replace(/^.+?\bheight=(\d+)\b.*$/i, '$1');
			opt = $.extend({}, options, {html:href, width:width, height:height, title:title});
			alertUI(opt);
			//e.preventDefault();
			return false;
		});
	});
};

//ajax分页
$.fn.ajaxpage = function(options){
	options = $.extend({
		mark: '', //插件唯一特征码
		total: 0, //记录数
		page: 1, //当前页
		size: 10, //每页显示记录数
		section: 5, //每段页数
		totalText: '个记录', //记录数文字
		firstPage: 'First', //首页文字
		prevPage: '&lt;', //上一页文字
		nextPage: '&gt;', //下一页文字
		lastPage: 'Last', //尾页文字
		btnText: '确定', //跳转按钮文字
		apexHidden: false, //隐藏首页、尾页
		ellipsisHidden: false, //隐藏首页、尾页数字与省略号
		totalHidden: false, //隐藏总数据
		currentHidden: false, //隐藏当前页
		autoHidden: true, //自动隐藏首上下尾
		click: null //点击链接执行,接受两个参数:跳转页数,操作完毕回调(直接执行即可,不回调数字不会改变)
	}, options);
	return this.each(function(){
		if(Number(options.total)<=0)return true;
		let _this = $(this), _body = $('body'), page = _body.data('ajaxpage.page') || options.page;
		if(!!!_body.data('ajaxpage.page')){
			let feature = '';
			if(options.mark.length){
				feature = 'ajaxpage_'+options.mark;
			}else{
				if(!!_this.attr('name') && $('[name="'+_this.attr('name')+'"]').length === 1){
					feature = 'ajaxpage_'+_this.attr('name');
				}
				if(!feature.length && !!_this.attr('class')){
					let _cls = '', cls = _this.attr('class').split(' ');
					$.each(cls, function(){
						if(!this.length)return true;
						if($('.'+this).length === 1){
							_cls = this;
							return false;
						}
					});
					feature = 'ajaxpage_'+_cls;
				}
			}
			if(feature.length){
				if($.cookie(feature))page = Number($.cookie(feature));
				$(window).on('unload', function(){
					$.cookie(feature, _body.data('ajaxpage.page')||page, (60*15)/(60*60*24)); //15分钟
				});
			}
		}
		let pages = Number(Math.ceil(options.total/options.size)),
			html = '<span class="ezr_num_records"'+(options.totalHidden?' style="display:none;"':'')+'>共<span class="ezr_num_record">'+options.total+'</span>'+options.totalText+'</span>\
		<span class="ezr_num"'+(options.currentHidden?' style="display:none;"':'')+'><span class="ezr_num_page">'+page+'</span>/<span class="ezr_num_pages">'+pages+'</span>页</span>\
		<a href="javascript:void(0)" class="ezr_first_page"'+(options.apexHidden?' style="display:none;"':'')+'><font>'+options.firstPage+'</font></a>\
		<a href="javascript:void(0)" class="ezr_back"><font>'+options.prevPage+'</font></a>\
		<a href="javascript:void(0)" class="ezr_next"><font>'+options.nextPage+'</font></a>\
		<a href="javascript:void(0)" class="ezr_last_page"'+(options.apexHidden?' style="display:none;"':'')+'><font>'+options.lastPage+'</font></a>\
		<span class="ezr_btn"><input type="text" class="ezr_input"><input type="button" value="'+options.btnText+'" class="ezr_submit" /></span>';
		_this.html(html);
		let callback = function(){
			let section = Number(Math.floor(page/(options.section+0.1))+1);
			_this.find('.ezr_num_page').html(page);
			if(options.autoHidden){
				if(!options.apexHidden){
					section<=1 ? _this.find('.ezr_first_page').hide() : _this.find('.ezr_first_page').show();
				}
				page<=1 ? _this.find('.ezr_back').hide() : _this.find('.ezr_back').show();
				page>=pages ? _this.find('.ezr_next').hide() : _this.find('.ezr_next').show();
				if(!options.apexHidden){
					section>=Number(Math.ceil(pages/options.section)) ? _this.find('.ezr_last_page').hide() : _this.find('.ezr_last_page').show();
				}
			}
			_this.find('.ezr_nav').remove();
			let html = '', end = section * options.section, start = end - options.section + 1;
			if(!options.ellipsisHidden && page>options.section){
				html += '<a href="javascript:void(0)" class="ezr_nav">1</a><span class="ezr_nav ezr_ellipsis">...</span>';
			}
			for(let i=start; i<=end; i++){
				if(i>pages)break;
				html += '<a href="javascript:void(0)" class="ezr_nav'+(i === page?' ezr_nav_na':'')+'">'+i+'</a>';
			}
			if(!options.ellipsisHidden && section<Number(Math.ceil(pages/options.section))){
				html += '<span class="ezr_nav ezr_ellipsis">...</span><a href="javascript:void(0)" class="ezr_nav">'+pages+'</a>';
			}
			_this.find('.ezr_back').after(html);
		};
		callback();
		if($.isFunction(options.click)){
			_this.on('click', 'a', function(){
				let _a = $(this), cls = _a.attr('class');
				if(_a.is('.ezr_nav_na'))return false;
				switch(cls){
					case 'ezr_first_page':
						if(page === 1)return false;
						page = 1;
						break;
					case 'ezr_back':
						if(page === 1)return false;
						page--;
						break;
					case 'ezr_next':
						if(page === pages)return false;
						page++;
						break;
					case 'ezr_last_page':
						if(page === pages)return false;
						page = pages;
						break;
					default:
						page = Number(_a.text());
						break;
				}
				$('body').data('ajaxpage.page', page);
				options.click(page, callback);
			});
			_this.find('.ezr_submit').on('click', function(){
				let prev = $(this).prev(), num = prev.val();
				prev.val('');
				if(!num.length)return;
				if(page === Number(num))return;
				page = Number(num);
				if(page<1)page = 1;
				if(page>pages)page = pages;
				$('body').data('ajaxpage.page', page);
				options.click(page, callback);
			});
		}
	});
};

//动画滚动
$.fn.scrollto = function(options){
	if(!$.isPlainObject(options))options = {el:options};
	options = $.extend({
		el: null, //滚动位置
		repair: {x:0, y:0}, //修补,可为函数,接受一个参数:d[1正向滚动|-1反向滚动]
		speed: 800, //滚动速度
		easing: 'easeout', //滚动效果
		before: null, //滚动前执行
		after: null //滚动后执行
	}, $('body').data('scrollto.options'), options);
	return this.each(function(){
		let _this = $(this), el = options.el, left = 0, top = 0, doc = $.document();
		if($.isFunction(options.before))options.before.call(_this);
		left = getEl(el, 1);
		top = getEl(el, 0);
		if(options.repair){
			let repair = options.repair;
			if($.isFunction(repair)){
				let d = 0;
				if(_this.scrollLeft()<left){
					d = 1;
				}else if(_this.scrollLeft()>left){
					d = -1;
				}
				if(_this.scrollTop()<top){
					d = 1;
				}else if(_this.scrollTop()>top){
					d = -1;
				}
				repair = repair.call(_this, d);
			}
			if($.isPlainObject(repair)){
				if(repair.x !== 'undefined')left += repair.x;
				if(repair.y !== 'undefined')top += repair.y;
			}
		}
		_this.stop(true, false).animate({scrollLeft:left, scrollTop:top}, options.speed?options.speed:800, options.easing.length?options.easing:'easeout', function(){
			if($.isFunction(options.after))options.after.call(_this);
		});
		function getEl(ele, d){
			let n = 0;
			switch(typeof ele){
				case 'number':
					n = ele;
					break;
				case 'string':
					if(ele.length){
						let re = /^(([+-])?\d+(\.\d+)?)(px|%)?$/;
						if(re.test(ele)){
							let num, r, t = d ? _this.scrollLeft() : _this.scrollTop();
							while( (r=re.exec(ele)) !== null ){
								num = r[1] * 1;
								if(typeof r[4] === 'undefined' || r[4] !== 'px'){
									n = (d ? doc.scrollWidth-doc.clientWidth : doc.scrollHeight-doc.clientHeight) * (num/100);
								}else{
									n = typeof r[2] !== 'undefined' ? t+num : num;
								}
								break;
							}
						}else if(ele === 'max'){
							n = d ? doc.scrollWidth-doc.clientWidth : doc.scrollHeight-doc.clientHeight;
						}else if($(ele).length){
							n = d ? $(ele).offset().left : $(ele).offset().top;
						}
					}
					break;
				case 'object':
					if(ele){
						if($.isPlainObject(ele)){
							if(d){
								n = typeof ele.left !== 'undefined' ? getEl(ele.left, d) : _this.scrollLeft();
							}else{
								n = typeof ele.top !== 'undefined' ? getEl(ele.top, d) : _this.scrollTop();
							}
						}else if($(ele).length){
							n = d ? $(ele).offset().left : $(ele).offset().top;
						}
					}
					break;
			}
			return n;
		}
	});
};

//兼容桌面端长按事件
$.fn.longclick = function(startFn, endFn, duration){
	if(typeof duration === 'undefined')duration = 500;
	let isTouch = 'ontouchend' in document.createElement('div'),
		start = isTouch ? 'touchstart' : 'mousedown',
		end = isTouch ? 'touchend' : 'mouseup',
		cancel = isTouch ? 'touchcancel' : 'mouseout';
	if(typeof startFn === 'undefined'){
		startFn = this.data('longclick.fn.start');
		if($.isFunction(startFn))startFn.call(this);
		return this;
	}
	if(typeof startFn === 'boolean' && !startFn){
		this.off(start, this.data('longclick.start'));
		this.off(end, this.data('longclick.end'));
		this.off(cancel, this.data('longclick.end'));
		this.removeData('longclick.fn.start').removeData('longclick.fn.end').removeData('longclick.start').removeData('longclick.end');
		return this;
	}
	return this.each(function(){
		let _this = $(this), timer = null, isRun = false;
		function onStart(e){
			e.stopPropagation();
			timer = setTimeout(function(){
				isRun = true;
				startFn.call(_this[0], e);
			}, duration);
		}
		function onEnd(e){
			e.stopPropagation();
			clearTimeout(timer);
			timer = null;
			if(isRun && $.isFunction(endFn)){
				endFn.call(_this[0], e);
			}
		}
		_this.on(start, onStart);
		_this.on(end, onEnd);
		_this.on(cancel, onEnd);
		_this.data('longclick.fn.start', startFn).data('longclick.start', onStart).data('longclick.end', onEnd);
		if($.isFunction(endFn))_this.data('longclick.fn.end', endFn);
	});
};

//增加resize控件
let elems = $([]), jq_resize = $.resize = $.extend($.resize, {}), timeout_id,
	str_setTimeout = 'setTimeout', str_resize = 'resize', str_data = str_resize+'-special-event', str_delay = 'delay', str_throttle = 'throttleWindow';
jq_resize[str_delay] = 250;
jq_resize[str_throttle] = true;
$.event.special[str_resize] = {
	setup: function(){
		if(!jq_resize[str_throttle] && this[str_setTimeout])return false;
		let elem = $(this);
		elems = elems.add(elem);
		$.data(this, str_data, {w:elem.width(), h:elem.height()});
		if(elems.length === 1)loopresize();
	},
	teardown: function(){
		if(!jq_resize[str_throttle] && this[str_setTimeout])return false;
		let elem = $(this);
		elems = elems.not(elem);
		elem.removeData(str_data);
		if(!elems.length)clearTimeout(timeout_id);
	},
	add: function(handleObj){
		if(!jq_resize[str_throttle] && this[str_setTimeout])return false;
		let old_handler;
		function new_handler(e, w, h){
			let elem = $(this), data = $.data(this, str_data)||{};
			data.w = typeof(w) !== 'undefined' ? w : elem.width();
			data.h = typeof(h) !== 'undefined' ? h : elem.height();
			old_handler.apply(this, arguments);
		}
		if($.isFunction(handleObj)){
			old_handler = handleObj;
			return new_handler;
		}else{
			old_handler = handleObj.handler;
			handleObj.handler = new_handler;
		}
	}
};
function loopresize(){
	timeout_id = window[str_setTimeout](function(){
		elems.each(function(k){
			try{
				let elem = $(this), width = elem.width(), height = elem.height(), data = $.data(this, str_data)||{};
				if(width !== data.w || height !== data.h)elem.trigger(str_resize, [data.w=width, data.h=height]);
			}catch(e){
				elems.splice(k, 1);
			}
		});
		loopresize();
	}, jq_resize[str_delay]);
}

//增加旋转控件
function getTransformProperty(element){
	let properties = ['transform', 'WebkitTransform', 'MozTransform', 'msTransform', 'OTransform'], p;
	while(p = properties.shift()){
		if(element.style[p] !== undefined)return p;
	}
	return false;
}
$.cssHooks['rotate'] = {
	get: function(elem){
		let property = getTransformProperty(elem);
		if(property){
			return elem.style[property].replace(/.*rotate\((.*)deg\).*/, '$1');
		}else{
			return '';
		}
	},
	set: function(elem, value){
		let property = getTransformProperty(elem);
		if(property){
			value = Number(value);
			$(elem).data('rotatation', value);
			if(value === 0){
				elem.style[property] = '';
			}else{
				elem.style[property] = 'rotate(' + value%360 + 'deg)';
			}
		}else{
			return '';
		}
	}
};
$.fx.step['rotate'] = function(fx){
	$.cssHooks['rotate'].set(fx.elem, fx.now);
};

$.extend({
	fillZero: function(num, length){
		num = num+'';
		if(num.length>=length)return num;
		let str = '';
		for(let i=0; i<length; i++)str += '0';
		str += num;
		return str.substr(str.length-length);
	},
	//滚轮绑定
	mousewheel: function(fn){
		return this.each(function(){
			function wheel(e){
				let delta = 0;
				if(!e)e = window.event;
				if(e.wheelDelta){
					delta = e.wheelDelta/120;
				}else if(e.detail){
					delta = -e.detail/3;
				}
				if(delta && $.isFunction(fn))fn(e, delta);
				if(e.preventDefault)e.preventDefault();
				e.returnValue = false;
			}
			if(window.addEventListener)this.addEventListener('DOMMouseScroll', wheel, false);
			this.onmousewheel = wheel;
		});
	},
	unmousewheel: function(fn){
		return this.each(function(){
			function wheel(e){
				let delta = 0;
				if(e.wheelDelta){
					delta = e.wheelDelta/120;
				}else if(e.detail){
					delta = -e.detail/3;
				}
				if(delta && $.isFunction(fn))fn(e, delta);
				if(e.preventDefault)e.preventDefault();
				e.returnValue = false;
			}
			if(window.addEventListener)this.removeEventListener('DOMMouseScroll', wheel, false);
			this.onmousewheel = null;
		});
	},
	//页面滚动距离
	scroll: function(father){
		let doc = $.document(father), body = $(doc).find('body'), l = body.scrollLeft()||$(doc).scrollLeft(), t = body.scrollTop()||$(doc).scrollTop();
		return {left:l, top:t};
	},
	//获取图片主色调, 必须网络图片
	getImageColor: function(url, callback){
		if(!url.length || !$.isFunction(callback))return;
		let img = new Image(), canvas = document.createElement('canvas');
		img.onload = function(){
			canvas.width = img.width;
			canvas.height = img.height;
			let ctx = canvas.getContext('2d');
			ctx.drawImage(img, 0, 0);
			let data = ctx.getImageData(0, 0, canvas.width, canvas.height).data;
			let r = 1, g = 1, b = 1;
			//取所有像素的平均值
			for(let row=0; row<canvas.height; row++){
				for(let col=0; col<canvas.width; col++){
					if(row === 0){
						r += data[((canvas.width * row) + col)];
						g += data[((canvas.width * row) + col) + 1];
						b += data[((canvas.width * row) + col) + 2];
					}else{
						r += data[((canvas.width * row) + col) * 4];
						g += data[((canvas.width * row) + col) * 4 + 1];
						b += data[((canvas.width * row) + col) * 4 + 2];
					}
				}
			}
			//求取平均值
			r /= (canvas.width * canvas.height);
			g /= (canvas.width * canvas.height);
			b /= (canvas.width * canvas.height);
			//将最终的值取整
			r = Math.round(r);
			g = Math.round(g);
			b = Math.round(b);
			callback(r, g, b);
		};
		img.src = url;
		img.crossOrigin = 'anonymous';
	},
	//图片等比例缩放
	imageResize: function(image, width, height, fix){
		let imageWidth = image.width, imageHeight = image.height;
		if(imageWidth<=width && imageHeight<=height)return image;
		if(imageWidth<=0 || imageHeight<=0)return image;
		let rect = $.rectResize(imageWidth, imageHeight, width, height, fix);
		let targetWidth = rect.width, targetHeight = rect.height;
		let newImage = new Image();
		newImage.src = image.src;
		newImage.width = targetWidth;
		newImage.height = targetHeight;
		return newImage;
	},
	//按宽高等比例缩放
	rectResize: function(targetWidth, targetHeight, width, height, fix){
		if(targetWidth<=width && targetHeight<=height)return {width:targetWidth, height:targetHeight};
		if(targetWidth<=0 || targetHeight<=0)return {width:targetWidth, height:targetHeight};
		if(width>0 && height>0){
			if(targetWidth/targetHeight >= width/height){
				if(targetWidth>width){
					targetWidth = width;
					targetHeight = (targetHeight*width)/targetWidth;
				}
			}else{
				if(targetHeight>height){
					targetWidth = (targetWidth*height)/targetHeight;
					targetHeight = height;
				}
			}
		}else{
			if(width === 0 && height>0){
				targetWidth = (targetWidth*height)/targetHeight;
				targetHeight = height;
			}else if(width>0 && height === 0){
				targetWidth = width;
				targetHeight = (targetHeight*width)/targetWidth;
			}else if(typeof fix !== 'undefined' && width === 0 && height === 0 && fix>0){
				if(targetWidth>targetHeight){
					targetWidth = (targetWidth*fix)/targetHeight;
					targetHeight = fix;
				}else{
					targetWidth = fix;
					targetHeight = (targetHeight*fix)/targetWidth;
				}
			}
		}
		return {width:targetWidth, height:targetHeight};
	},
	//图片压缩
	imageCompress: function(img, attr, callback){
		if(!$.isFunction(callback))return;
		if(typeof callback === 'undefined'){
			callback = attr;
			attr = {};
		}
		let image = new Image();
		image.src = (typeof img === 'string') ? img : img.src;
		if(typeof img === 'string' && /^data:/.test(img))image.crossOrigin = '*';
		image.onload = function(){
			let canvas = document.createElement('canvas'), context = canvas.getContext('2d'),
				imageWidth = this.width, imageHeight = this.height, quality = 0.7;
			if(typeof attr.width !== 'undefined' || typeof attr.height !== 'undefined'){
				let rect = $.rectResize(attr.width||0, attr.height||0, imageWidth, imageHeight);
				imageWidth = rect.width;
				imageHeight = rect.height;
			}
			canvas.width = imageWidth;
			canvas.height = imageHeight;
			context.drawImage(image, 0, 0, imageWidth, imageHeight);
			if(attr.quality && attr.quality>0 && attr.quality<=1)quality = attr.quality;
			let data = canvas.toDataURL(attr.type||'image/jpeg', quality);
			callback.call(this, data);
		}
	},
	//图片转base64
	imageToBase64: function(image){
		image.setAttribute('crossOrigin', 'anonymous');
		let canvas = document.createElement('canvas'), ctx = canvas.getContext('2d'),
			ext = image.src.substring(image.src.lastIndexOf('.')+1).toLowerCase();
		canvas.width = image.width;
		canvas.height = image.height;
		ctx.drawImage(image, 0, 0, image.width, image.height);
		return canvas.toDataURL('image/'+ext);
	},
	//file控件的图片转base64, input:file控件, maxWidth:最大宽高, isSquare:是否强制正方形, callback:回调,参数为 base64 字符串
	fileToBase64: function(input, maxWidth, isSquare, callback){
		let file = $(input).get(0).files[0];
		if(!file.type.match(/image.*/)){$.overloadError('The file is not an image');return}
		let reader = new FileReader();
		reader.onload = function(e){
			let image = $('<img />');
			image.on('load', function(){
				let offsetX = 0, offsetY = 0, imageWidth = this.width, imageHeight = this.height;
				if(maxWidth){
					if(imageWidth<maxWidth && imageHeight<maxWidth){
						offsetX = Math.round((maxWidth - imageWidth) / 2);
						offsetY = Math.round((maxWidth - imageHeight) / 2);
					}else{
						if(imageWidth > imageHeight){
							imageWidth = maxWidth;
							imageHeight = Math.round(maxWidth * this.height / this.width);
							offsetY = - Math.round((imageHeight - maxWidth) / 2);
						}else{
							imageHeight = maxWidth;
							imageWidth = Math.round(maxWidth * this.width / this.height);
							offsetX = - Math.round((imageWidth - maxWidth) / 2);
						}
					}
				}
				if($.isFunction(isSquare)){
					callback = isSquare;
					isSquare = false;
				}
				let canvas = document.createElement('canvas'), context = canvas.getContext('2d');
				if(isSquare){
					canvas.width = maxWidth;
					canvas.height = maxWidth;
					context.clearRect(0, 0, maxWidth, maxWidth);
				}else{
					offsetX = offsetY = 0;
					canvas.width = imageWidth;
					canvas.height = imageHeight;
					context.clearRect(0, 0, imageWidth, imageHeight);
				}
				context.imageSmoothingEnabled = true;
				context.drawImage(this, offsetX, offsetY, imageWidth, imageHeight);
				let xStart = canvas.width/2, yStart = canvas.height/2, xEnd = canvas.width/2+1, yEnd = canvas.height/2+1,
					imgData = context.getImageData(xStart, yStart, xEnd, yEnd),
					red = imgData.data[0], green = imgData.data[1], blue = imgData.data[2], alpha = imgData.data[3];
				if(red === 0 && green === 0 && blue === 0 && alpha === 0){
					context.clearRect(offsetX, offsetY, imageWidth, imageHeight);
					context.drawImage(this, offsetX, offsetY, imageWidth*1.1, imageHeight*2.720000091);
				}
				let data = canvas.toDataURL('image/png');
				if($.isFunction(callback))callback(data);
			});
			image.attr('src', e.target.result);
		};
		reader.readAsDataURL(file);
	},
	//底部弹出
	actionpicker: function(options){
		let overlay = $('.actionpicker-overlay'), picker = $('.actionpicker');
		if(typeof options === 'undefined'){
			if(overlay.length){
				overlay.removeClass('actionpicker-overlay-x');
				setTimeout(function(){overlay.remove()}, 300);
			}
			if(picker.length){
				let height = picker.height();
				picker.css({transform:'translate3d(0,'+height+'px,0)', '-webkit-transform':'translate3d(0,'+height+'px,0)'});
				setTimeout(function(){picker.remove()}, 300);
			}
			if(!!picker.data('actionpicker-close') && $.isFunction(picker.data('actionpicker-close')))picker.data('actionpicker-close').call(picker);
			return;
		}
		options = $.extend({
			cls: '', //附加类名
			title: '', //toolBar标题
			leftBtn: null, //toolBar左按钮, 格式:{text:'left', cls:'', click:function(){}}
			rightBtn: null, //toolBar右按钮, 格式同上
			before: null, //弹出前执行
			after: null, //弹出后执行
			close: null //关闭前执行
		}, $('body').data('actionpicker.options'), options);
		if(!overlay.length){
			overlay = $('<div class="actionpicker-overlay"></div>');
			$(document.body).append(overlay);
			overlay.tapper(function(){
				if($.isFunction(options.close))picker.data('actionpicker-close', options.close);
				$.actionpicker();
			});
		}
		if(!picker.length){
			picker = $('<div class="actionpicker'+(options.cls.length?' '+options.cls:'')+'"></div>');
			$(document.body).append(picker);
		}else{
			picker.html('');
		}
		if(options.title.length || $.isPlainObject(options.leftBtn) || $.isPlainObject(options.rightBtn)){
			let toolBar = $('<div class="toolBar"></div>');
			picker.append(toolBar);
			if(options.title.length){
				toolBar.append('<div>'+options.title+'</div>');
			}
			if($.isPlainObject(options.leftBtn)){
				let left = $('<a href="javascript:void(0)" class="leftBtn '+(options.leftBtn.cls||'')+'">'+(options.leftBtn.text||'leftBtn')+'</a>');
				toolBar.append(left);
				if($.isFunction(options.leftBtn.click))left.click(function(){options.leftBtn.click.call(picker)});
			}
			if($.isPlainObject(options.rightBtn)){
				let right = $('<a href="javascript:void(0)" class="rightBtn '+(options.rightBtn.cls||'')+'">'+(options.rightBtn.text||'rightBtn')+'</a>');
				toolBar.append(right);
				if($.isFunction(options.rightBtn.click))right.click(function(){options.rightBtn.click.call(picker)});
			}
		}
		let modal = $('<div class="modal"></div>');
		picker.append(modal);
		if($.isFunction(options.before))options.before.call(modal);
		let height = picker.height();
		picker.css({transform:'translate3d(0,'+height+'px,0)', '-webkit-transform':'translate3d(0,'+height+'px,0)'});
		setTimeout(function(){
			overlay.addClass('actionpicker-overlay-x');
			picker.css({transform:'', '-webkit-transform':'', 'transition':'transform 300ms ease-out', '-webkit-transition':'-webkit-transform 300ms ease-out'});
			if($.isFunction(options.after))setTimeout(function(){options.after.call(modal)}, 300);
		}, 10);
	},
	//获取本地坐标, callback接受一个参数:geo, 包含geo.longitude(经度), geo.latitude(纬度)
	getLocation: function(callback, notallowed){
		if(navigator.geolocation){
			$.overload(null);
			navigator.geolocation.getCurrentPosition(function(position){
				$.overload(false);
				let lng = position.coords.longitude, lat = position.coords.latitude;
				if($.isFunction(callback))callback({longitude:lng, latitude:lat});
			}, function(error){
				$.overload(false);
				switch(error.code){
					case error.PERMISSION_DENIED:console.log('定位: 用户不允许获取当前位置');break;
					case error.POSITION_UNAVAILABLE:console.log('定位: 无法获取当前位置');break;
					case error.TIMEOUT:console.log('定位: 操作超时');break;
					case error.UNKNOWN_ERROR:console.log('定位: 未知错误');break;
				}
				if($.isFunction(notallowed)){
					notallowed();
				}else if($.isFunction(callback)){
					$.getJSON('https://freegeoip.net/json/?callback=?', function(json){
						callback({longitude:json.longitude, latitude:json.latitude});
					});
				}
			}, {
				enableHighAcuracy: true, //指示浏览器获取高精度的位置，默认为false
				timeout: 5000, //指定获取地理位置的超时时间，默认不限时，单位为毫秒
				maximumAge: 3000 //最长有效期，在重复获取地理位置时，此参数指定多久再次获取位置
			});
		}else{
			console.log('定位: 此浏览器不支持Geolocation脚本');
			if($.isFunction(notallowed)){
				notallowed();
			}else if($.isFunction(callback)){
				$.getJSON('https://freegeoip.net/json/?callback=?', function(json){
					callback({longitude:json.longitude, latitude:json.latitude});
				});
			}
		}
	},
	//使用第三方地图定位
	getThirthLocation: function(register, callback){
		if($.isFunction(register)){callback = register;register = ''}
		switch(register){
			case 'qq': //腾讯地图
				$.getScript('https://3gimg.qq.com/lightmap/components/geolocation/geolocation.min.js', function(){
					let geolocation = new qq.maps.Geolocation('NC6BZ-TOF2J-KWKF4-F6I3B-EM2CZ-QJFJ3', 'mapqq');
					geolocation.getLocation(function(result){
						if($.isFunction(callback))callback({longitude:result.lng, latitude:result.lat});
						//{"module":"geolocation","nation": "中国","province": "广州省","city":"深圳市","district":"南山区","adcode":"440305","addr":"深圳大学杜鹃山","lat":22.530001,"lng":113.935364,"accuracy":13}
					}, function(err){
						console.log(err);
					});
				});
				break;
			case 'baidu': //百度地图
			default:
				if(typeof window.BMap === 'undefined'){
					$.getScript((document.location.protocol==='file:'?'http:':document.location.protocol)+'//api.map.baidu.com/getscript?v=2.0&ak='+window.BAIDU_AK, function(){
						done();
					});
				}else{
					done();
				}
				function done(){
					let geolocation = new BMap.Geolocation();
					geolocation.getCurrentPosition(function(result){
						if(this.getStatus() === window.BMAP_STATUS_SUCCESS){
							if($.isFunction(callback))callback({longitude:result.point.lng, latitude:result.point.lat});
						}else{
							console.log('failed: '+this.getStatus());
						}
					}, {enableHighAccuracy:true});
				}
				break;
		}
	},
	//GPS坐标转百度坐标, callback接受一个参数:geo, 包含geo.longitude, geo.latitude
	getBaiduLocation: function(callback){
        $.getThirthLocation(callback);
	},
	//经纬度转地点, callback接受一个参数:json, pois=1为获取附近小区
	getBaiduGeocoder: function(lat, lng, callback){
		$.getJSON((document.location.protocol==='file:'?'http:':document.location.protocol)+'//api.map.baidu.com/geocoder/v2/?callback=?&ak='+window.BAIDU_AK+'&location='+lat+','+lng+'&output=json&pois=1', function(json){
			if(json.status !== 0){alert(json.message);return}
			if($.isFunction(callback))callback(json);
		});
	},
	//地点转经纬度, callback接受一个参数:json
	getBaiduGeo: function(address, callback){
		$.getJSON((document.location.protocol==='file:'?'http:':document.location.protocol)+'//api.map.baidu.com/geocoder/v2/?callback=?&ak='+window.BAIDU_AK+'&address='+address+'&output=json', function(json){
			if(json.status !== 0){alert(json.message);return}
			if($.isFunction(callback))callback(json);
		});
	},
	//城市范围内的关键字地点, callback接受两个参数:json,xq
	getBaiduPlace: function(keyword, city, callback){
		if(!keyword.length){$.overloadError('请输入地区关键词');return}
		$.getJSON((document.location.protocol==='file:'?'http:':document.location.protocol)+'//api.map.baidu.com/place/v2/search?callback=?&ak='+window.BAIDU_AK+'&q='+keyword+'&region='+city+'&output=json', function(json){
			if(json.status !==0 ){alert(json.message);return}
			let xq = [];
			for(let i=0; i<json.results.length; i++)xq.push({name:json.results[i].name, location:json.results[i].location});
			if($.isFunction(callback))callback(json, xq);
		});
	},
	//获取当前位置附近小区或大厦, callback接受两个参数:json,xq
	getBaiduNearby: function(callback){
		$.getBaiduLocation(function(geo){
			let lng = geo.longitude, lat = geo.latitude;
			$.getBaiduGeocoder(lat, lng, function(json){
				let xq = [];
				for(let i=0; i<json.result.pois.length; i++)xq.push({name:json.result.pois[i].name, location:{lat:json.result.pois[i].point.y, lng:json.result.pois[i].point.x}});
				if($.isFunction(callback))callback(json, xq);
			});
		});
	},
	//获取当前位置天气, callback接受两个参数:today,json
	getBaiduWeather: function(callback){
		$.getJSON((document.location.protocol==='file:'?'http:':document.location.protocol)+'//www.baidu.com/home/xman/data/superload?callback=?&type=weather', function(json){
			if(json.errNo !== 0)return;
			if($.isFunction(callback))callback(json.data.weather.content.today, json);
		});
	},
	debug: function(o, n){
		n = n||0;
		let s, i, m = '', mn = n*2;
		if($.isArray(o)){
			if(o.length>0){
				s = 'Array['+o.length+']';
				for(i=0; i<mn; i++)m += '    ';
				s += '\n' + m + "(" + '\n';
				for(i=0; i<o.length; i++)s += m + '    [' + i + '] => ' + $.debug(o[i], n+1) + '\n';
				s += m + ')';
			}else{
				s = 'Array[0]';
			}
		}else if($.isPlainObject(o)){
			if(!$.isEmptyObject(o)){
				s = 'Object';
				for(i=0; i<mn; i++)m += '    ';
				s += '\n' + m + "{" + '\n';
				for(i in o)if(o.hasOwnProperty(i))s += m + '    ' + i + ' => ' + $.debug(o[i], n+1) + '\n';
				s += m + '}';
			}else{
				s = 'Object { }';
			}
		}else{
			switch(typeof o){
				case 'undefined':
					s = '(Undefined)';break;
				case 'object':
					if(o instanceof Date)s = '(Date) ' + o;
					else if(o instanceof jQuery)s = '(Object) jQuery';
					else s = '(Null)';
					break;
				case 'number':
					s = '(Number) ' + o;break;
				case 'boolean':
					s = '(Boolean) ' + (o?'true':'false');break;
				case 'function':
					s = '(Function)';
					Function.prototype.getName = function(){
						return typeof this.name === 'string' ? this.name : /function\s+([^{(\s]+)/.test(toString.call(this)) ? RegExp['$1'] : '';
					};
					let f = o.getName();
					if(f.length)s += ' ' + f + '()';
					break;
				case 'string':
					s = '(String)';
					let p = o.replace(/(^\s*)|(\s*$)/g, '');
					if(p.length)s += ' ' + p;
					break;
				default:
					s = '('+(typeof o)+')' + o;break;
			}
		}
		return s;
	}
});

})(jQuery);

//精确加法, arguments[1]要保留的小数位数(可以不传此参数,如不传则不处理小数位数)
String.prototype.bcadd = function(arg){
	return Number(this).bcadd(arg);
};
Number.prototype.bcadd = function(arg){
	let arg1 = this.toString(), arg2 = arg.toString();
	let arg1Arr = arg1.split('.'), arg2Arr = arg2.split('.'), d1 = arg1Arr.length === 2 ? arg1Arr[1] : '', d2 = arg2Arr.length === 2 ? arg2Arr[1] : '';
	let maxLen = Math.max(d1.length, d2.length);
	let m = Math.pow(10, maxLen);
	let result = Number(((arg1 * m + arg2 * m) / m).toFixed(maxLen));
	let d = arguments[1];
	return typeof d === 'number' ? Number(result.toFixed(d)) : result;
};
//精确减法
String.prototype.bcsub = function(arg){
	return Number(this).bcsub(arg);
};
Number.prototype.bcsub = function(arg){
	return this.bcadd(-Number(arg), arguments[1]);
};
//精确乘法
String.prototype.bcmul = function(arg){
	return Number(this).bcmul(arg);
};
Number.prototype.bcmul = function(arg){
	let r1 = this.toString(), r2 = arg.toString(), m, resultVal, d = arguments[1];
	m = (r1.split('.')[1] ? r1.split('.')[1].length : 0) + (r2.split('.')[1] ? r2.split('.')[1].length : 0);
	resultVal = (Number(r1.replace('.', '')) * Number(r2.replace('.', ''))) / Math.pow(10, m);
	return typeof d !== 'number' ? Number(resultVal) : Number(resultVal.toFixed(parseInt(d)));
};
//精确除法
String.prototype.bcdiv = function(arg){
	return Number(this).bcdiv(arg);
};
Number.prototype.bcdiv = function(arg){
	let r1 = this.toString(), r2 = arg.toString(), m, resultVal, d = arguments[1];
	m = (r2.split('.')[1] ? r2.split('.')[1].length : 0) - (r1.split('.')[1] ? r1.split('.')[1].length : 0);
	resultVal = (Number(r1.replace('.', '')) / Number(r2.replace('.', ''))) * Math.pow(10, m);
	return typeof d !== 'number' ? Number(resultVal) : Number(resultVal.toFixed(parseInt(d)));
};

//百度地图,自动引入api后的调用函数
function baiduMapApi(){
	let el = $(document.body).data('baiduMap-element'), options = $(document.body).data('baiduMap-options');
	el.baiduMap(options);
}

//提示框, 需引入alertUI.css
function alertUI(options){
	let $ = window.jQuery;
	if(!$.isPlainObject(options)){
		let win, child;
		switch(options){
			case 'top':win = $(top.document.body);break;
			case 'parent':win = $(parent.document.body);break;
			default:win = $(document.body);break;
		}
		let dialog = $('.dialog-alert', win), overlay = $('.load-overlay', win);
		options = dialog.data('options');
		child = dialog.find('.contentbox').children().eq(0);
		if(!!dialog.data('close'))dialog.data('close').call(dialog);
		dialog.find('.tbox').addClass('tbox-out');
		overlay.removeClass('load-overlay-in');
		if(options.cls)overlay.removeClass('dialog-overlay-'+options.cls+'-in');
		setTimeout(function(){
			if(!!child.data('originnext'))child.data('originnext').before(child);
			if(!!child.data('parent'))child.data('parent').append(child);
			dialog.remove();
			if($('.load-face, .load-view, .dialog-action, .dialog-alert, .dialog-popover', win).length)return;
			if($.browser.ie6)$('select').css({visibility:'visible'});
			overlay.remove();
		}, 300);
	}else{
		options = $.extend({
			win: 'self', //显示窗口['self'|'parent'|'top']
			cls: '', //自定义窗口样式
			title: '', //标题
			width: 0, //总宽度
			height: 'auto', //总高度
			drag: false, //允许拖动
			fixd: false, //随页面滚动(设置后不可拖动)
			auto: 0, //自动关闭, 单位毫秒
			nox: false, //不显示右上角的关闭按钮
			nobg: false, //不显示遮罩层
			html: '', //显示内容, string|url|对象, 默认为空
			btns: [], //按钮组, 不设置即不显示按钮
			clickbg: null, //点击背景执行
			before: null, //显示提示窗前执行
			after: null, //显示提示窗后执行
			close: null //关闭提示窗后执行
		}, $('body').data('alertUI.options'), options);
		let win, doc, dialog, html, contentbox, sl, st, ow = 0, oh = 0, overlay = [];
		if($.isFunction(options.before))options.before(options);
		if($.inArray(options.win, ['top','parent','self'])>-1)options.win = 'self';
		doc = $.document(options.win);
		switch(options.win){
			case 'top':win = $(top.document.body);break;
			case 'parent':win = $(parent.document.body);break;
			default:win = $(document.body);break;
		}
		if(!options.nobg){
			overlay = $('.load-overlay', win);
			if(!overlay.length){
				overlay = $('<div class="load-overlay"></div>');
				win.append(overlay);
			}
			if($.browser.ie6)overlay.css({width:Math.max(doc.clientWidth, doc.scrollWidth), height:Math.max(doc.clientHeight, doc.scrollHeight)});
			if(options.cls)overlay.addClass('dialog-overlay-'+options.cls);
			if($.browser.ie6)$('select').css({visibility:'hidden'});
			if($.isFunction(options.clickbg))overlay.tapper(function(){options.clickbg.call($(this))});
			else overlay.stopBounces();
		}
		dialog = $('<div class="dialog-alert"></div>');
		win.append(dialog);
		html = '<div class="tbox">\
			<div class="titlebox">'+(options.nox?'':'<a href="javascript:;" target="_self">×</a>')+'<span>'+options.title+'</span></div>\
			<div class="contentbox"></div>\
			'+(options.btns.length?'<div class="btnbox"><div></div></div>':'')+'\
		</div>';
		dialog.html(html).stopBounces();
		if(!options.title.length)dialog.find('.tbox').addClass('tbox-titlebox-none');
		contentbox = dialog.find('.contentbox');
		if(options.cls)dialog.addClass(options.cls);
		for(let i=0; i<options.btns.length; i++){
			let btntext = options.btns[i].text||'btn'+(i+1), tbtn = $('<button class="tbtn"><span>'+btntext+'</span></button>');
			dialog.find('.btnbox div').append(tbtn);
			tbtn.hover(function(){$(this).addClass('hover')},function(){$(this).removeClass('hover')});
			if(i === 0)tbtn.addClass('tbtn_l');
			if(i === options.btns.length-1)tbtn.addClass('tbtn_r');
			if($.isFunction(options.btns[i].click)){
				tbtn.data('click', options.btns[i].click);
				tbtn.click(function(){$(this).data('click').call(dialog, options.win)});
			}
		}
		if(typeof options.html !== 'object'){
			let htm = options.html+'';
			if(/^https?:\/\//.test(htm)){
				contentbox.css('overflow', 'hidden').html('<iframe scrolling="auto" frameborder="0" src="'+options.html+'" width="100%" height="100%"></iframe>');
			}else{
				let object = $(htm);
				if(object.length){
					if(object.next().length)object.data('originnext', object.next());
					else object.data('parent', object.parent());
					contentbox.append(object);
				}else{
					contentbox.html(htm);
				}
			}
		}else{
			let object = $(options.html);
			if(object.length){
				if(object.next().length)object.data('originnext', object.next());
				else object.data('parent', object.parent());
				contentbox.append(object);
			}
		}
		if(!options.nox){
			dialog.find('.titlebox a').click(function(){alertUI(options.win)});
			$(document).onkey(function(code){
				if(code === 27)alertUI(options.win);
			});
		}
		sl = $.scroll(options.win).left;
		st = $.scroll(options.win).top;
		let tbox = contentbox.parent();
		if(!isNaN(options.width) && options.width>0){
			tbox.addClass('alertURL').width(options.width);
			dialog.css('width', 'auto');
		}
		if(!isNaN(options.height)){
			tbox.addClass('alertURL').height(options.height);
			setTimeout(function(){
				contentbox.height(tbox.outerHeight(false)-contentbox.position().top-tbox.find('.btnbox').outerHeight(true)-$.unit(contentbox.css('min-height')));
			}, 200);
		}
		let dch = doc.clientHeight>=dialog.outerHeight(false);
		oh = dch ? (doc.clientHeight-dialog.outerHeight(false))/2 : 0;
		dialog.css({
			left: ($.window().width-dialog.width())/2,
			top: ( ((options.fixd && (!$.browser.msie || ($.browser.msie && $.browser.version>=7)))?0:(dch?st:0)) + oh )
		});
		if(overlay.length && oh === 0)overlay.css({height:dialog.outerHeight(false)});
		if(options.drag && !options.fixd)dialog.find('.titlebox').drag({target:dialog});
		if(options.fixd && dch){
			if($.browser.ie6){
				if($(document).css('background-image') === 'none')$(document).css({'background-image':'url(about:blank)', 'background-attachment':'fixed'});
				dialog[0].style.cssText += ";left:expression(documentElement.scrollLeft+body.scrollLeft+"+ow+"+'px');";
				dialog[0].style.cssText += ";top:expression(documentElement.scrollTop+body.scrollTop+"+oh+"+'px');";
			}else{
				dialog.css({position:'fixed'});
			}
		}
		//if(options.btns.length && !$.browser.mobile)dialog.find('.btnbox div button:eq(0)').focus();
		if($.isFunction(options.after))options.after.call(dialog);
		if($.isFunction(options.close))dialog.data('close', options.close);
		if(options.auto>0)setTimeout(function(){alertUI(options.win)}, options.auto);
		setTimeout(function(){
			if(overlay.length){
				overlay.addClass('load-overlay-in');
				if(options.cls)overlay.addClass('dialog-overlay-'+options.cls+'-in');
			}
			tbox.addClass('tbox-x');
		}, 10);
		dialog.data('options', options);
		return contentbox;
	}
}

$(function(){
	setTimeout(function(){
		$('form.checkform').checkform({labelNon:true, errorClass:'error'});
		//$('input.datepicker').datepicker();
		$('.tips[title]').tips();
		$('input.focus, textarea.focus').focus();
	}, 0);
});
