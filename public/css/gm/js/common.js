(function($){
let checkDeletePermission = true; //列表批量删除是否检测权限

if(window.top.document !== window.document){
	$('html').addClass('iframe');
}

//关联
$.fn.goodsModal = function(options){
	options = $.extend({
		type: 'goods', //[goods|category]
		text: '',
		api: '',
		target: null
	}, options);
	return this.each(function(){
		let _this = $(this), ids = [];
		$(options.target).on('click', 'a.del', function(){
			let parent = $(this).parent();
			parent.addClass('remove').heightAnimate(0, function(){parent.remove()});
		});
		$(options.target).find(':hidden').each(function(){ids.push($(this).val())});
		_this.on('click', function(){
			let win = $.window(), modal = _this.data('goodsModal');
			if(!!!modal){
				modal = $('<div class="goods">\
					<div class="title ge-bottom ge-light"><a href="javascript:void(0)">×</a>搜索'+options.text+'</div>\
					<div class="view">\
						<table>\
							<thead>\
								<tr>\
									'+(options.type === 'goods' ? '<th class="pic">图片</th>' : '')+'\
									<th class="name">\
										<font>名称</font>\
										<div><span>搜索</span><input type="text" class="keyword" placeholder="输入'+options.text+'搜索" /></div>\
									</th>\
									'+(options.type === 'goods' ? '<th class="price">价格</th>' : '')+'\
									<th class="btnView"></th>\
								</tr>\
							</thead>\
						</table>\
						<div class="list">\
							<table><tbody></tbody></table>\
						</div>\
					</div>\
					<div class="bottom ge-top ge-light">\
						<div class="mypage"></div>\
						<a href="javascript:void(0)" class="selected hidden"></a>\
					</div>\
				</div>');
				_this.data('goodsModal', modal);
			}
			if(win.width <= 768)modal.css({ width:win.width, height:win.height });
			else modal.css({ width:'', height:'' });
			$.overlay(modal, 0);
			modal.find('.title a').on('click', function(){$.overlay(false)});
			modal.find('.keyword').onkey({
				callback: function(code){
					if(code === 13)setList(modal, $(this).val());
				}
			}).prev().on('click', function(){
				setList(modal, modal.find('.keyword').val());
			});
			setList(modal, modal.find('.keyword').val());
		});
		function setList(modal, keyword, BRSR){
			if(!BRSR)BRSR = 0;
			BRSR = Number(BRSR);
			$.getJSON(options.api+'&keyword='+keyword+'&BRSR='+BRSR, function(json){
				if(!$.isArray(json.data.rs))return;
				let html = '', items = [];
				if($.isArray(json.data.rs)){
					$.each(json.data.rs, function(){
						let name = [];
						if(options.type === 'goods'){
							name.push('<a href="?app='+options.type+'&act=edit&id='+this.id+'" target="_blank">'+this.name+'</a>');
						}else{
							$.each(this.parents, function(i, _this){
								name.push('<a href="?app='+options.type+'&act=edit&id='+_this.id+'" target="_blank">'+_this.name+'</a>');
							});
						}
						html += '<tr>\
							'+(options.type === 'goods' ? '<td class="pic"><div url="'+this.pic+'"></div></td>' : '')+'\
							<td class="name">'+name.join('<em>»</em>')+'</td>\
							'+(options.type === 'goods' ? '<td class="price red">￥'+this.price+'</td>' : '')+'\
							<td class="btnView"><a href="javascript:void(0)" '+($.inArray(this.id, ids) > -1 ? 'class="selected"' : '')+'></a></td>\
						</tr>';
						if($.inArray(this.id, ids) > -1)items.push(this);
					});
				}
				modal.find('.list tbody').html(html);
				modal.find('.list tbody .pic div').loadbackground();
				let pages = json.data.pages;
				html = '当前第'+pages.current_page+'页，共'+pages.num_pages+'页 ';
				if(pages.current_page === 1){
					html += '<span class="ezr_nav_na">＜</span> ';
				}else{
					html += '<a href="javascript:void(0)" class="ezr_back" BRSR="'+(BRSR-10)+'">＜</a> ';
				}
				if(pages.current_page === pages.num_pages){
					html += '<span class="ezr_nav_na">＞</span>';
				}else{
					html += '<a href="javascript:void(0)" class="ezr_next" BRSR="'+(BRSR+10)+'">＞</a>';
				}
				modal.find('.bottom .mypage').html(html);
				modal.find('.bottom .mypage a').on('click', function(){
					setList(modal, keyword, $(this).attr('BRSR'));
				});
				modal.find('.bottom .selected').on('click', function(){
					let html = '';
					$.each(items, function(){
						if(options.type === 'goods'){
							html += '<li class="item">\
								<a class="del" href="javascript:void(0)">－</a>\
								<a href="?app='+options.type+'&act=edit&id='+this.id+'" target="_blank">\
									<div style="background-image:url('+this.pic+');"></div>\
									<span>'+this.name+'</span>\
									<font>￥'+this.price+'</font>\
								</a>\
								<input type="hidden" class="goods_id" name="goods[]" value="'+this.id+'" />\
							</li>';
						}else{
							let name = [];
							$.each(this.parents, function(i, _this){
								name.push('<a href="?app='+options.type+'&act=edit&id='+_this.id+'" target="_blank">'+_this.name+'</a>');
							});
							html += '<li class="row">\
								<a class="del" href="javascript:void(0)">－</a>\
								'+name.join('<em>»</em>')+'\
								<input type="hidden" class="goods_id" name="goods[]" value="'+this.id+'" />\
							</li>';
						}
					});
					$(options.target).html(html);
					$.overlay(false);
				});
				modal.find('.list td.btnView a').each(function(i){
					$(this).data('data', json.data.rs[i]).on('click', function(){
						let data = $(this).data('data');
						if($(this).hasClass('selected')){
							$(this).removeClass('selected');
							let idsTmp = [], itemsTmp = [];
							$.each(items, function(){
								if(Number(data.id) !== Number(this.id)){
									idsTmp.push(this.id);
									itemsTmp.push(this);
								}
							});
							ids = idsTmp;
							items = itemsTmp;
						}else{
							$(this).addClass('selected');
							ids.push(data.id);
							items.push(data);
						}
						if(items.length)modal.find('.bottom .selected').removeClass('hidden');
						else modal.find('.bottom .selected').addClass('hidden');
					});
				});
			});
		}
	});
};

function configs(){
	$('table[muldelete]').each(function(){
		let _table = $(this), url = _table.attr('muldelete'), permission = _table.attr('permission'), init = _table.attr('data-configs');
		if(!checkDeletePermission)return true;
		if(!/^\d+$/.test(_table.find('tbody td:eq(0)').html()) && !_table.find('tbody td:eq(0) .checkbox').length)return true;
		if(!!permission && !!!init){
			_table.attr('data-configs', 'init');
			permission = permission.split(',');
			$.getJSON('/core/checkPermission?application='+permission[0]+'&action='+permission[1], function(json){
				checkDeletePermission = Number(json.data) === 1;
			}, false);
		}
		if(checkDeletePermission){
			if(!_table.find('thead .checkboxes').length)_table.find('thead tr').prepend('<th width="50"><input type="checkbox" data-type="ace" data-seleteAll=".checkbox" class="checkboxes" title="全选" /></th>');
			_table.find('tbody tr').each(function(){
				if($(this).find('td:eq(0) .checkbox').length)return true;
				let id = $(this).find('td:eq(0)').html();
				$(this).prepend('<td><input type="checkbox" data-type="ace" name="id[]" class="checkbox" id="'+id+'" value="'+id+'" /></td>');
			});
			let button = $('.muldelete');
			if(!button.length){
				$('.mypage').before('<div><button type="button" class="btn btn-xs btn-danger muldelete" style="border-radius:2px;"><i class="ace-icon far fa-trash-alt bigger-120"></i>批量删除!</button></div>');
				$('.muldelete').on('click', function(){
					if(!confirm('确定要删除？'))return;
					let ids = [];
					$('.checkbox:checked').each(function(){
						ids.push($(this).val());
					});
					if(!ids.length){
						$.overloadError('请选择');
						return;
					}
					$.overload();
					$.postJSON(url, { ids:ids.join(',') }, function(){
						window.location.reload();
					});
				});
			}
		}
	});
	$('.tips').not('[data-configs]').attr('data-configs', 'init').tips();
	$('.some-tips').not('[data-configs]').attr('data-configs', 'init').each(function(){
		$(this).attr('title', $(this).html());
	}).tips();
	$('form.form-horizontal').not('[data-configs]').attr('data-configs', 'init').checkform({
		labelNon: true,
		errorClass: 'error'
	});
	$('[type="number"]').not('[data-configs]').attr('data-configs', 'init').on('blur', function(){
		let val = Number($(this).val());
		$(this).val(val.numberFormat(2));
	});
	if($('input.premobile').length){
		let textarea = $('textarea.ckeditor').not('[data-configs]');
		if(!textarea.length)textarea = $('textarea.ckeditor_custom').not('[data-configs]');
		if(textarea.length && !!textarea.attr('name') && textarea.attr('name').length){
			setTimeout(function(){
				let replaceMiniprogram = function(code){
					return code.replace(/(<mp-miniprogram[\s\S]+<\/mp-miniprogram>)/g, function(_$, $1){
						let matcher = $1.match(/(data-miniprogram-appid="([^"]+)"|data-miniprogram-avatar="([^"]+)"|data-miniprogram-imageurl="([^"]+)"|data-miniprogram-nickname="([^"]+)"|data-miniprogram-title="([^"]+)")/g);
						let avatar = matcher[1].replace('data-miniprogram-avatar="', '').replace('"', ''),
							//appid = matcher[0].replace('data-miniprogram-appid="', '').replace('"', ''),
							imageurl = matcher[2].replace('data-miniprogram-imageurl="', '').replace('"', ''),
							nickname = matcher[3].replace('data-miniprogram-nickname="', '').replace('"', ''),
							title = matcher[4].replace('data-miniprogram-title="', '').replace('"', '');
						return '<iframe width="300" height="330" frameborder="0" src="https://mp.weixin.qq.com/cgi-bin/readtemplate?t=tmpl/weapp_tmpl&title='+title+'&imageUrl='+imageurl+'&avatar='+avatar+'&nickname='+nickname+'"></iframe>';
					});
				};
				textarea.attr('data-configs', 'init').after('<div class="premobile"><div class="premobile-title">预览</div><div class="premobile-content">'+textarea.val()+'</div></div>');
				$('input.premobile').not('[data-configs]').attr('data-configs', 'init').on('click', function(){
					if(this.checked){
						let html = replaceMiniprogram($('.premobile-content').html());
						$('.premobile-content').html(html);
						$('div.premobile').addClass('premobile-active');
						$('.premobile-content img').each(function(){
							let _this = $(this);
							if(_this.outerWidth(false) >= 300){
								_this.removeAttr('width').removeAttr('height').css({width:'100%', height:''});
							}
						});
					}else{
						$('div.premobile').removeClass('premobile-active');
					}
				});
				CKEDITOR.instances[textarea.attr('name')].on('change', function(){
					let html = replaceMiniprogram(this.getData());
					$('.premobile-content').html(html).find('img').each(function(){
						let _this = $(this);
						if(_this.outerWidth(false) >= 300){
							_this.removeAttr('width').removeAttr('height').css({ width:'100%', height:'' });
						}
					});
				});
			}, 100);
		}
	}
	//<a href="javascript:void(0)" url="" class="import" data='{ "type":"xls,xlsx" }' fileType="xls,xlsx" callback="importList">导入列表</a>
	$('a.import').not('[data-configs]').attr('data-configs', 'init').ajaxupload({
		before: function(){
			$.overload();
		}
	});
	$(':checkbox[data-seleteAll]').off('change').on('change', function(){
		let ele = $($(this).attr('data-seleteAll'));
		if(this.checked){
			ele.prop('checked', true).each(function(){
				$(this).parents('tr').eq(0).addClass('checked');
			});
		}else{
			ele.prop('checked', false).each(function(){
				$(this).parents('tr').eq(0).removeClass('checked');
			});
		}
	});
	$(':checkbox[data-type="app"]').not('[data-configs]').attr('data-configs', 'init').each(function(){
		let _this = $(this);
		_this.before('<label class="coo-checkbox-app"'+(!!_this.attr('data-style')?' style="'+_this.attr('data-style')+'"':'')+'><span></span></label>');
		if(!!_this.attr('data-class'))_this.prev().addClass(_this.attr('data-class'));
		_this.prev().prepend(_this);
	});
	$(':radio[data-type="css"], :checkbox[data-type="css"]').not('[data-configs]').attr('data-configs', 'init').each(function(){
		let _this = $(this).addClass('ace'), type = _this.attr('type').toLowerCase();
		_this.before('<label class="coo-'+type+'"'+(!!_this.attr('data-style')?' style="'+_this.attr('data-style')+'"':'')+'><span></span></label>');
		if(!!_this.attr('data-class'))_this.prev().addClass(_this.attr('data-class'));
		_this.prev().prepend(_this);
	});
	$(':radio[data-type="ace"], :checkbox[data-type="ace"]').not('[data-configs]').attr('data-configs', 'init').each(function(){
		let _this = $(this).addClass('ace'), type = _this.attr('type').toLowerCase();
		_this.before('<div class="'+type+'"'+(!!_this.attr('data-style')?' style="'+_this.attr('data-style')+'"':'')+'><label><span class="lbl">'+(!!_this.attr('data-text')?_this.attr('data-text'):'')+'</span></label></div>');
		if(!!_this.attr('data-class'))_this.prev().addClass(_this.attr('data-class'));
		_this.prev().find('label').prepend(_this);
	});
	let splitBefore = function(e, count, files){
			let _this = this;
			if(_this.is('[type="file"]'))_this = _this.parent();
			let getSize = function(size){
				//求次幂
				let pow1024 = function(num){return Math.pow(1024, num)};
				if(!size)return '';
				if(size < pow1024(1))return size + 'B';
				if(size < pow1024(2))return (size / pow1024(1)).toFixed(2) + 'KB';
				if(size < pow1024(3))return (size / pow1024(2)).toFixed(2) + 'MB';
				if(size < pow1024(4))return (size / pow1024(3)).toFixed(2) + 'GB';
				return (size / pow1024(4)).toFixed(2) + 'TB';
			};
			_this.addClass('col-splitfile-selected').find('i').html(files[0].name + ' - ' + getSize(files[0].size));
			_this.find('[type="file"]').css('top', '-9999px');
			let fn = _this.attr('data-before');
			if(!!fn){
				let func = eval(fn);
				if($.isFunction(func)){
					let result = func.call(_this, e, count, files);
					if(typeof result === 'boolean' && !result)return false;
				}
			}
		},
		splitProgress = function(e){
			let _this = this, percent = Math.round((e.loaded / e.total) * 100, 1);
			if(_this.is('[type="file"]'))_this = _this.parent();
			_this.find('span').css({width:percent+'%'}).html(percent+'%');
			if(Number(percent) >= 100)_this.find('span').html('转换中，请稍候...');
		},
		splitSuccess = function(json){
			let _this = this;
			if(_this.is('[type="file"]'))_this = _this.parent();
			_this.find('span').css({width:'100%'}).html('上传完成');
			_this.find('input').not('[type="file"]').val(json.data);
			if(_this.next('div').length)_this.next('div').remove();
			_this.after('<div style="clear:both;padding-top:15px;"><video src="'+json.data+'" controls style="width:220px;height:220px;"></video></div>');
			let fn = _this.attr('data-success');
			if(!!fn){
				let func = eval(fn);
				if($.isFunction(func))func.call(_this);
			}
		},
		splitError = function(){
			let _this = this;
			if(_this.is('[type="file"]'))_this = _this.parent();
			_this.find('[type="file"]').css('top', '');
			let fn = _this.attr('data-error');
			if(!!fn){
				let func = eval(fn);
				if($.isFunction(func))func.call(_this);
			}
		};
	let splitfile = $('.col-splitfile').not('[data-configs]').attr('data-configs', 'init').html5upload({
		splitSize: 20 * 1024 * 1000,
		before: splitBefore,
		progress: splitProgress,
		success: splitSuccess,
		error: splitError
	});
	splitfile.prepend('<i>点击选择或文件拖放到这里</i><span></span>').append('<input type="file" '+(!!splitfile.attr('data-fileType') ? 'data-fileType="'+splitfile.attr('data-fileType')+'"' : '')+' />');
	splitfile.find('[type="file"]').not('[data-configs]').attr('data-configs', 'init').attr('data-url', splitfile.attr('data-url')).html5upload({
		splitSize: 20 * 1024 * 1000,
		before: splitBefore,
		progress: splitProgress,
		success: splitSuccess,
		error: splitError
	});
	$('#simple-table thead th[data-sortby]').not('[data-configs]').attr('data-configs', 'init').append('<i></i>').on('click', function(){
		//<th data-sortby="clicks">点击数</th>
		let href = location.href, sort = 'desc', matcher = href.match(/sortby=((\w+)(?:,|%2C)(\w+))/);
		href = href.replace(/&sortby=(\w+(?:,|%2C)\w+)?/, '').replace(/\?sortby=(\w+(?:,|%2C)\w+)?&?/, '?');
		if(matcher){
			if(matcher[3] === 'desc'){
				href += (href.substr(href.length-1, 1) === '?' ? 'sortby=' : '&sortby=') + $(this).attr('data-sortby') + ',asc';
			}
		}else{
			href += (href.substr(href.length-1, 1) === '?' ? 'sortby=' : '&sortby=') + $(this).attr('data-sortby') + ',' + sort;
		}
		location.href = href;
	});
	$('.some-edit').not('[data-configs]').attr('data-configs', 'init').on('mousedown', function(e){
		let _this = $(this);
		if(e.metaKey || e.ctrlKey){
			let input = $('<input class="some-edit-input" type="text" value="'+_this.html()+'" />');
			_this.hide().after(input);
			input.onkey(function(code){
				if(code === 13){
					let val = $(this).val(), data = {id:_this.attr('data-id')};
					data[_this.attr('data-field')] = val;
					let href = location.href.replace(/\/(gm|ag|op)\/?\?/, '/$1/api?').replace(/\/(gm|ag|op)(\/\w+)(\/\w+)(\/?\?)?/, function(_$, $1, $2, $3, $4){
						return '/' + $1 + '/api' + $2 + $3 + (typeof $4 === 'undefined' ? '?' : $4);
					});
					if(!/\/(gm|ag|op)\b/.test(location.pathname) && !/\/api\b/.test(href)){
						href = href.split('?');
						href = href[0] + 'api?' + href[1];
					}
					$.putJSON(href, data, function(){
						_this.html(val).show();
						input.remove();
					});
				}else if(code === 27){
					_this.show();
					input.remove();
				}
			});
		}
	});
	$('[data-preview-image]').off('click').on('click', function(){
		let _this = $(this), url = _this.attr('data-preview-image'), logo = _this.attr('data-logo') || '', title = _this.attr('data-title') || '',
			width = _this.attr('data-width') || 200, height = _this.attr('data-height') || 200, padding = _this.attr('data-padding')||0;
		let html = '<div style="position:relative;width:'+(width*1+padding*2)+'px;height:'+(height*1+padding*2)+'px;padding:'+padding+'px;box-sizing:border-box;border-radius:7px;overflow:hidden;background:#fff;">\
			<img url="'+url+'" style="width:100%;height:100%;display:none;" />';
		if(logo.length)html += '<div class="logo" style="display:none;position:absolute;z-index:1;left:50%;top:50%;transform:translate(-50%,-50%);width:'+(width*0.25)+'px;height:'+(width*0.25)+'px;background:url('+logo+') no-repeat center center;background-size:cover;border-radius:10px;border:3px solid #fff;box-shadow:0 0 3px rgba(0,0,0,0.5);"></div>';
		html += '<div class="preloader-gray" style="position:absolute;z-index:1;left:50%;top:50%;margin-left:-18px;margin-top:-18px;"></div>\
			</div>';
		if(title.length)html += '<div style="font-size:16px;font-weight:700;color:#fff;line-height:40px;text-align:center;">'+title+'</div>';
		$.overlay(html, 0, function(){
			let _this = this, img = _this.find('img');
			img.attr('src', url);
			img.on('load', function(){
				_this.find('.preloader-gray').remove();
				_this.find('img').fadeIn(300);
				if(logo.length)_this.find('.logo').fadeIn(300);
			});
		});
		return false;
	});
	$('.col-file').not('[data-configs]').attr('data-configs', 'init').each(function(){
		let _this = $(this), prev = _this.prev();
		if(prev && prev.is('input') && prev.attr('name').indexOf('origin_')>-1){
			let checkbox = $('<div class="checkbox" style="margin-left:5px;"><label><input type="checkbox" class="ace orange" /><span class="lbl" style="color:#a3a3a3;">填写</span></label></div>');
			_this.after(checkbox);
			checkbox.find('input').on('change', function(){
				if(this.checked){
					prev.addClass('col-xs-3').attr('type', 'text');
					_this.hide();
				}else{
					prev.attr('type', 'hidden');
					_this.show();
				}
			});
		}
	});
}

$.extend({
	iframeLayer: function(url, title, width, height, scrolling, max){
		let showLayerArea = window.document;
		if(typeof url === 'boolean' && !url){
			let layer = $('.some-layer:last', showLayerArea);
			layer.addClass('some-layer-toggle');
			setTimeout(function(){
				layer.remove();
				if(!$('.some-layer', showLayerArea).length){
					let layerShadow = $('.some-layer-shadow', showLayerArea);
					layerShadow.addClass('some-layer-shadow-toggle');
					setTimeout(function(){layerShadow.remove()}, 310);
				}
			}, 310);
			return;
		}
		let html = '', win = $.window(), classUrl = $.base64Encode(url.split('?')[1]).replace(/=/g, ''), hasShadow = true;
		if(typeof scrolling === 'undefined')scrolling = '';
		if(!$('.some-layer-shadow', showLayerArea).length){
			hasShadow = false;
			html += '<div class="some-layer-shadow some-layer-shadow-toggle"></div>';
		}else{
			$('.some-layer-shadow', showLayerArea).show();
		}
		html += '<div class="some-layer some-layer-normal iframe-layer-'+$.base64Encode('act=login').replace(/=/g, '')+' iframe-layer-'+classUrl+'" style="opacity:0;'+(!!width?'width:'+width+'px;':'')+(!!height?'height:auto;':'')+'">\
			<div class="layer-header page-header">\
				<a href="javascript:void(0)"></a>\
				<a href="javascript:void(0)"></a>\
				<a href="javascript:void(0)"></a>\
				<h6>'+((!!title&&title.length)?title:'')+'</h6>\
			</div>\
			<div class="layer-content" style="'+(!!height?'height:'+height+'px;':'')+'">\
				<div class="preloader-gray"></div>\
				<iframe name="iframe-layer-case" src="'+url+'" frameborder="0" '+scrolling+'></iframe>\
			</div>\
		</div>';
		$('body', showLayerArea).append(html);
		let layer = $('.some-layer:last', showLayerArea), layerShadow = $('.some-layer-shadow', showLayerArea),
			left = (win.width-layer.width()) / 2, top = (win.height-layer.height()) / 2;
		layer.css({left:left, top:top, opacity:''}).addClass('some-layer-toggle').attr({'origin-left':left, 'origin-top':top, 'origin-width':layer.width(), 'origin-height':layer.find('.layer-content').height()});
		setTimeout(function(){
			layerShadow.removeClass('some-layer-shadow-toggle');
			layer.removeClass('some-layer-toggle');
		}, 100);
		if(!hasShadow)layerShadow.on('click', function(){
			let allLayer = $('.some-layer', showLayerArea);
			allLayer.addClass('some-layer-toggle');
			setTimeout(function(){
				allLayer.remove();
				if(!$('.some-layer', showLayerArea).length){
					layerShadow.addClass('some-layer-shadow-toggle');
					setTimeout(function(){layerShadow.remove()}, 310);
				}
			}, 310);
		});
		layer.find('.layer-header').drag({
			target: layer,
			area: $('body', showLayerArea),
			exceptEl: function(e){
				let parent = this.parent();
				if(parent.hasClass('some-layer-max') || parent.hasClass('some-layer-min') || $(e.target).is('a'))return true;
			},
			stop: function(position){
				this.attr({'origin-left':position.left, 'origin-top':position.top});
			}
		});
		layer.find('.layer-header a').on('click', function(){
			let index = $(this).index();
			if(index === 0){
				layer.addClass('some-layer-toggle');
				let layerCount = $('.some-layer', showLayerArea).length;
				setTimeout(function(){
					layer.remove();
					if(layerCount > 1)return;
					layerShadow.addClass('some-layer-shadow-toggle');
					setTimeout(function(){layerShadow.remove()}, 310);
				}, 310);
			}else if(index === 1){
				layer.css({left:'', top:'', bottom:''});
				if(layer.is('.some-layer-min')){
					layer.addClass('some-layer-normal').removeClass('some-layer-min').css({
						left:layer.attr('origin-left')+'px', top:layer.attr('origin-top')+'px', width:layer.attr('origin-width'), height:!!height ? 'auto' : ''
					});
					let minLeft = 10, minBottom = 10;
					$('.some-layer-min', showLayerArea).each(function(){
						let minBox = $(this).css({left:minLeft, bottom:minBottom});
						let minWidth = minBox.width(), offset = minBox.offset();
						if(offset.left+minWidth+10+minWidth > win.width){
							minLeft = 10;
							minBottom = win.height - offset.top + 10;
						}else{
							minLeft = offset.left + minWidth + 10;
						}
					});
					setTimeout(function(){
						layer.css({'-webkit-transition':'', 'transition':''});
					}, 10);
					layerShadow.show();
				}else{
					if(layer.is('.some-layer-max')){
						layer.addClass('some-layer-normal').removeClass('some-layer-max').css({
							left:layer.attr('origin-left')+'px', top:layer.attr('origin-top')+'px', width:layer.attr('origin-width'), height:!!height ? 'auto' : ''
						}).find('.layer-content').animate({height:layer.attr('origin-height')}, 150);
					}else{
						layer.removeClass('some-layer-normal').addClass('some-layer-max').css({left:'', top:'', width:'', height:''}).find('.layer-content').css({height:''});
					}
				}
			}else if(index===2){
				layer.css({'-webkit-transition':'none', 'transition':'none'}).removeClass('some-layer-normal').removeClass('some-layer-max').css({left:'', top:'', bottom:'', width:'', height:''});
				let minLeft = 10, minBottom = 10;
				$('.some-layer-min', showLayerArea).each(function(){
					let minBox = $(this).css({left:minLeft, bottom:minBottom});
					let minWidth = minBox.width(), offset = minBox.offset();
					if(offset.left + minWidth + 10 + minWidth > win.width){
						minLeft = 10;
						minBottom = win.height - offset.top + 10;
					}else{
						minLeft = offset.left + minWidth + 10;
					}
				});
				layer.addClass('some-layer-min').css({left:minLeft, bottom:minBottom});
				if(!$('.some-layer-normal', showLayerArea).length)layerShadow.hide();
			}
		});
		if(!!max)layer.find('.layer-header a:eq(1)').trigger('click');
	}
});

$(function(){
	if(navigator.userAgent.toLowerCase().indexOf('windows') > -1)$('html').addClass('col-windows');
	$(document.body).data('overlay-no-overload', true);
	$(document.body).data('datepicker.options', {readonly:false});
	if($.request() && !$.request('#')){
		let request = $.request();
		if(request.msg){
			let msg = Number(request.msg) === 1 ? '提交成功' : request.msg;
			$.overloadSuccess(msg);
			location.href += '#showd';
		}
		if(request.wxactive){
			let msg = Number(request.wxactive) === 1 ? '绑定成功' : request.wxactive;
			$.overloadSuccess(msg);
			location.href += '#wxactive';
		}
	}
	configs();
	$('#simple-table').on('click', 'tbody td', function(e){
		let _td = $(this), checkbox = _td.parent().find('td:eq(0) :checkbox'), o = e.target;
		if(checkbox.length){
			do{
				if((/^(a|button|label|input|textarea|select)$/i).test(o.tagName))return true;
				if((/^td$/i).test(o.tagName)){
					if(checkbox.prop('checked')){
						checkbox.prop('checked', false).parents('tr').eq(0).removeClass('checked');
					}else{
						checkbox.prop('checked', true).parents('tr').eq(0).addClass('checked');
					}
					return false;
				}
				o = o.parentNode;
			}while(o.parentNode);
		}
	}).on('change', 'tbody td input.checkbox', function(){
		if(this.checked){
			$(this).parents('tr').eq(0).addClass('checked');
		}else{
			$(this).parents('tr').eq(0).removeClass('checked');
		}
	});
	let hrefMatcher = location.href.match(/sortby=((\w+)(?:,|%2C)(\w+))/);
	if(hrefMatcher){
		let sortbyElement = $('#simple-table thead th[data-sortby="'+hrefMatcher[2]+'"]');
		if(sortbyElement.length){
			sortbyElement.removeClass('asc').removeClass('desc');
			if(/^(asc|desc)$/.test(hrefMatcher[3]))sortbyElement.addClass(hrefMatcher[3]);
		}
	}
	let dataTemplate = $('#simple-table tbody tr.template');
	if(dataTemplate.length){
		dataTemplate.find('label.coo-checkbox-app, div.checkbox, div.radio').each(function(){
			let _this = $(this), input = _this.find('input');
			_this.after(input).remove();
		});
		let btn = $('<div><button type="button" class="btn btn-xs btn-default" style="border-radius:2px;"><i class="ace-icon fa fa-list-ul bigger-110"></i>加载更多数据</button></div>'),
			template = dataTemplate.removeClass('template').outerHTML(), tbody = $('#simple-table tbody'), tbodyHtml = tbody.outerHTML(), mypage = $('.mypage');
		dataTemplate.remove();
		if(mypage.find('.pagination').length)$('.table-content').after(btn);
		template = template.replace(/data-configs="init"/g, '');
		let tbodyMatcher = tbodyHtml.match(/<tbody([\s\S]+?)>[\r\n]/);
		let re = new RegExp('\\b([\\w\\-]+)="([^"]+)"', 'g'), matcher;
		while((matcher = re.exec(tbodyMatcher[1])) !== null){
			template = template.replace(new RegExp('\\['+matcher[1]+'\\]', 'g'), matcher[2]);
		}
		btn.on('click', function(){
			tbody.data('template')();
		});
		tbody.data('template', function(data){
			if(typeof data !== 'undefined')return setData(data);
			let href = !!tbody.data('href') ? tbody.data('href') : location.href;
			if(!/\bpage=(\d+)/.test(href))href += (href.indexOf('?')>0 ? '&' : '?') + 'page=1';
			href = href.replace(/\bpage=(\d+)/, function(_$, $1){
				return 'page=' + (Number($1) + 1);
			});
			tbody.data('href', href);
			$.overload();
			$.getJSON(href, function(json){
				setData(json.data.rs.data);
			});
			function setData(_data){
				if($.isArray(_data) && _data.length){
					let list = '', errorShown = false;
					$.each(_data, function(){
						let code = 'let $ = window.jQuery, row = this;\n';
						code += $.template(template);
						try{
							try{
								let tbFn = eval('templateBefore');
								if(typeof(tbFn) === 'function'){
									let result = tbFn(code);
									if(typeof(result) === 'string')code = result;
								}
							}catch(e){}
							let html = new Function(code).apply(this);
							html = $.template(html, true);
							try{
								let tcFn = eval('templateComplete');
								if(typeof(tcFn) === 'function'){
									let result = tcFn(html);
									if(typeof(result) === 'string')html = result;
								}
							}catch(e){}
							list += html;
						}catch(e){
							if(!errorShown){
								console.log(template);
								console.log(code);
								console.log(e);
								errorShown = true;
							}
						}
					});
					tbody.append(list);
					configs();
				}else{
					if(typeof data === 'undefined')btn.remove();
				}
				return false;
			}
		});
	}
	if(!$.browser.mobile){
		$(document).on('click', '.iframe-layer', function(e){
			if(e.metaKey || e.ctrlKey)return true;
			let _this = $(this), max = _this.attr('iframe-layer-max'), title = _this.attr('title'), width = _this.attr('width'), height = _this.attr('height'), scrolling = '', url = _this.attr('url') || _this.attr('href');
			if(/^#+$/.test(url) || url.indexOf('javascript:')>-1)return true;
			if(!!_this.attr('scrolling'))scrolling = 'scrolling="'+_this.attr('scrolling')+'"';
			$.iframeLayer(url, title, width, height, scrolling, max);
			return false;
		});
		if(window.parent.document !== window.document){
			let classUrl = $.base64Encode(window.location.search.split('?')[1]).replace(/=/g, '');
			let layer = $('.iframe-layer-'+classUrl+', .iframe-layer-'+$.base64Encode('act=login').replace(/=/g, ''), window.parent.document);
			if(layer.length>1)layer = layer.last();
			layer.find('.layer-content .preloader-gray').remove();
			$('.footer-inner').remove();
			if(layer.find('.layer-header h6').length && !layer.find('.layer-header h6').html().length && $('.page-header h6').length){
				let html = $('.page-header h6').html();
				//html = html.replace(/<div[\s\S]+\/div>/, '');
				layer.find('.layer-header h6').html(html);
				layer.find('.layer-header h6 a').each(function(){
					let _link = $(this);
					if(!!!_link.attr('href') || _link.attr('href').indexOf('javascript:') > -1){
						_link.remove();
						return true;
					}
					_link.attr('target', 'iframe-layer-case');
				});
			}
		}
	}
});
})(jQuery);

function changeColor(str){
	str = str.replace(/#([0-9a-fA-F]{6}|[0-9a-fA-F]{3})([^#]+)#/g, function(_$, $1, $2){
		return '<font color="#'+$1+'">'+$2+'</font>';
	});
	str = str.replace(/#([RGBOPY])([^#]+)#/g, function(_$, $1, $2){
		let html = '<font color="';
		switch ($1) {
			case 'R':html += 'red';break;
			case 'G':html += 'green';break;
			case 'B':html += 'blue';break;
			case 'O':html += 'orange';break;
			case 'P':html += 'purple';break;
			case 'Y':html += '#ffc700';break;
		}
		html += '">'+$2+'</font>';
		return html;
	});
	return str;
}

function ajaxPost(){
	let _this = $(this), url = _this.attr('data-url'), data = !!_this.attr('data-data') ? $.json(_this.attr('data-data')) : {}, name = _this.attr('data-name'), callback = _this.attr('data-callback');
	data[name] = _this.val();
	if(_this.is('[type="checkbox"]') || _this.is('[type="radio"]')) data['checked'] = this.checked ? 1 : 0;
	$.postJSON(url, data, {
		success: function(json){
			if($.isFunction(callback))callback.call(_this, json);
		},
		error: function(json){
			if($.isFunction(callback))callback.call(_this, json);
		}
	});
}

function ckeditorUploadPath(name, dir, extend){
	//name为编辑器的name属性值
	//编辑器class不能为ckeditor, 否则会自动初始化, 推荐使用ckeditor_custom
	let options = {
		wechatCollectUrl: '/index/ckediter_wechat_collect?dir=' + dir,
		filebrowserUploadUrl: '/index/ckediter_upload?dir=' + dir
	};
	if(typeof extend !== 'undefined')options = $.extend(options, extend);
	CKEDITOR.replace(name, options);
}

function ckediterWechatCollect(data){
	let title = $('#title');
	if(title.length)title.val(data.title);
}
