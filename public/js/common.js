/*
Developed by @mario v3.1.20210105
*/
window.$ = jQuery;
window.sceneNonSaveTip = true;
window.sceneSaved = true;
window.sceneInitd = false;
window.sceneData = null;
window.showToastTimer = null;
window.selectAreaLastX = 0;
window.selectAreaLastY = 0;
window.contextmenuControl = null;
window.showIframeTimer = null;
window.sceneElementClone = null;
window.sceneMouseX = 0;
window.sceneMouseY = 0;
window.previewGrid = null;
window.previewPages = [];
window.previewPageIndex = 0;
window.previewChangePaging = false;
window.previewLastY = 0;
window.previewDirection = 0;
window.gridMinValue = 20;
window.gridMaxValue = 100;

function returnHome(){
	location.href = '/index';
}
function dialogClose(){
	if(window.top === window.self){
		$.overlay(false);
	}else{
		window.top.dialogClose();
	}
}
function dialogResize(){
	if(window.top === window.self)return;
	$('.dialog', top.document.body).css({width:document.body.scrollWidth, height:document.body.scrollHeight}).parent().css({width:document.body.scrollWidth});
}
function grid(){
	if($(this).hasClass('disable'))return false;
	let _grid = $('.viewer section'), value = $('#grid').val();
	if(_grid.hasClass('hidden')){
		_grid.removeClass('hidden');
		value = value.replace(/^\d/, '1');
		$('#grid').val(value);
	}else{
		_grid.addClass('hidden');
		value = value.replace(/^\d/, '0');
		$('#grid').val(value);
	}
}
function setGrid(size, color){
	let viewer = $('.viewer'), viewerWidth = viewer.width(), viewerHeight = viewer.height(),
		canvas = $('.viewer section canvas').attr({width:viewerWidth * 2, height:viewerHeight * 2}).css({width:viewerWidth, height:viewerHeight}),
		_canvas = canvas[0], ctx = _canvas.getContext('2d'), cW = _canvas.width, cH = _canvas.height, w = parseInt(size) * 2, h = parseInt(size) * 2, wLen = Math.ceil(cW / w), hLen = Math.ceil(cH / h),
		drawLine = function(x1, y1, x2, y2){
			ctx.moveTo(x1, y1);
			ctx.lineTo(x2, y2);
			ctx.stroke();
		};
	ctx.translate(0.5, 0.5);
	ctx.lineWidth = 1;
	ctx.strokeStyle = color;
	ctx.clearRect(0, 0, cW, cH);
	for(let i=1; i<=wLen; i++)drawLine(w * i, 0, w * i, cH);
	for(let i=1; i<=hLen; i++)drawLine(0, h * i, cW, h * i);
}
function skipGrid(e){
	let p = $.etarget(e);
	if(p)p = $(p);
	if(p.is('.grid-face') || p.parent().is('.grid-face'))return false;
}
function ruler(){
	if($(this).hasClass('disable'))return false;
	let _ruler = $('.boxer .ruler');
	if(_ruler.hasClass('hidden')){
		_ruler.removeClass('hidden');
		$('#ruler').val(1);
	}else{
		_ruler.addClass('hidden');
		$('#ruler').val(0);
	}
}
function pad(){
	let parent = $('.parent'), face = $(this).attr('data-face');
	parent.addClass('show').attr('face', face);
	$('#pad').val(face);
}
function removePad(){
	let parent = $('.parent');
	parent.removeClass('show').removeAttr('face');
	$('#pad').val('');
}
function skipPad(e){
	let p = $.etarget(e);
	if(p)p = $(p);
	if(p.is('.pad-face') || p.parent().is('.pad-face'))return false;
}
function publish(){
	if(!$('.boxer > .loading').hasClass('hidden'))return;
	sceneSave(null, true, function(){
		let id = $('#id').val()||0;
		if(id<=0 || !window.sceneSaved){
			alert('请先保存场景后再发布');
			return;
		}
		$.postJSON('/scene/publish', {id:id}, function(){
			location.href = '/scene/detail?id='+id;
		});
	});
}
function detail(){
	location.href = '/scene/detail?id=' + $('#id').val();
}
function setTips(el){
	setTimeout(function(){
		el.tips({
			css: {background:'#444'},
			follow: 2
		});
	}, 0);
}
function platformKey(keyName){
	let keys = {ctrl:'⌘', alt:'⌥', shift:'⇧'},
		p = navigator.platform.toLowerCase();
	if(p.indexOf('mac') > -1)return keys[keyName.toLowerCase()];
	return keyName;
}
function sceneInit(){
	let val = $('textarea.data').val(), content = $.base64Decode(val.substr(0, 5) === 'Mario' ? val.substr(5) : val);
	window.sceneData = $.json(content);
	window.onbeforeunload = function(){
		if(!window.sceneSaved && window.sceneNonSaveTip)return '您编辑的信息尚未保存，您确定要离开吗？';
	};
	$(document).on('change input', 'input, textarea, select', function(){
		if(window.sceneInitd)window.sceneSaved = false;
	}).on('mousemove', function(e){
		let o = e.target;
		do{
			if($(o).is('.toolbar') || $(o).is('.contextmenu') || $(o).is('.linkarea'))return true;
			o = o.parentNode;
		}while(o.parentNode);
		let viewer = $('.viewer'), offset = $.touches(e), left = offset.x, top = offset.y;
		window.sceneMouseX = Math.floor(left-viewer.offset().left);
		window.sceneMouseY = Math.floor(top-viewer.offset().top);
		return true;
	}).on('keydown', function(e){
		if(!$('.boxer > .loading').hasClass('hidden'))return true;
		let viewer = $('.viewer'), control = getControl(), o = e.target, code = e.which||e.keyCode,
			meta = e.metaKey, ctrl = e.ctrlKey, alt = e.altKey, shift = e.shiftKey, p = navigator.platform.toLowerCase(), isMac = p.indexOf('mac') > -1,
			tagName = o.tagName.toUpperCase();
		if(tagName === 'FONT' || tagName === 'P' || tagName === 'INPUT' || tagName === 'TEXTAREA' || tagName === 'SELECT')return true;
		if((isMac && meta && code === 83) || (!isMac && ctrl && code === 83)){//保存 Ctrl+S
			sceneSave();
			return false;
		}else if(shift && code === 83){//设置 Shift+S
			showSet();
			return false;
		}else if((isMac && !meta && shift && code === 66) || (!isMac && !ctrl && shift && code === 66)){//背景 Shift+B
			$('.scene-menu .bg').trigger('click');
			return false;
		}else if(shift && code === 80){//图片 Shift+P
			$('.scene-menu .pic').trigger('click');
			return false;
		}else if(shift && code === 84){//文字 Shift+T
			$('.scene-menu .text').trigger('click');
			return false;
		}else if(shift && code === 77){//音乐 Shift+M
			$('.scene-menu .music').trigger('click');
			return false;
		}else if(shift && code === 86){//视频 Shift+V
			$('.scene-menu .video').trigger('click');
			return false;
		}else if(shift && code === 87){//网页 Shift+W
			$('.scene-menu .web').trigger('click');
			return false;
		}else if(shift && code === 68){//地图 Shift+D
			$('.scene-menu .map').trigger('click');
			return false;
		}else if(shift && code === 67){//图表 Shift+C
			$('.scene-menu .chart').trigger('click');
			return false;
		}else if(shift && code === 69){//模板 Shift+E
			$('.scene-menu .template').trigger('click');
			return false;
		}else if(shift && code === 71){//网格 Shift+G
			//$('.scene-menu .grid').trigger('click');
			grid();
			return false;
		}else if(shift && code === 82){//标尺 Shift+R
			$('.scene-menu .ruler').trigger('click');
			return false;
		}else if(shift && code === 70){//边框 Shift+F
			//$('.scene-menu .pad').trigger('click');
			removePad();
			return false;
		}else if(shift && code === 89){//预览 Shift+Y
			$('.scene-menu .preview').trigger('click');
			return false;
		}else if(alt && code>48 && code<58){//场景页 Alt+[1-9]
			let li = $('.pages ul li').eq(code-49);
			if(li.length && !li.hasClass('this')){
				li.tapper();
				return false;
			}
		}else if((isMac && meta && !shift && code === 66) || (!isMac && ctrl && !shift && code === 66)){//加粗 Ctrl+B
			if(!control)return false;
			$('.toolbar .style .b').trigger('click');
			return false;
		}else if((isMac && meta && code === 73) || (!isMac && ctrl && code === 73)){//斜体 Ctrl+I
			if(!control)return true;
			$('.toolbar .style .i').trigger('click');
			return false;
		}else if((isMac && meta && code === 85) || (!isMac && ctrl && code === 85)){//下划线 Ctrl+U
			if(!control)return false;
			$('.toolbar .style .u').trigger('click');
			return false;
		}else if((isMac && meta && code === 68) || (!isMac && ctrl && code === 68)){//删除线 Ctrl+D
			if(!control)return false;
			$('.toolbar .style .s').trigger('click');
			return false;
		}else if((isMac && meta && shift && code === 37) || (!isMac && ctrl && shift && code === 37)){//左对齐 Ctrl+Shift+Left
			if(!control)return false;
			$('.toolbar .textalign + ul .left').trigger('click');
			return false;
		}else if((isMac && meta && shift && code === 67) || (!isMac && ctrl && shift && code === 67)){//居中 Ctrl+Shift+C
			if(!control)return false;
			$('.toolbar .textalign + ul .center').trigger('click');
			return false;
		}else if((isMac && meta && shift && code === 39) || (!isMac && ctrl && shift && code === 39)){//右对齐 Ctrl+Shift+Right
			if(!control)return false;
			$('.toolbar .textalign + ul .right').trigger('click');
			return false;
		}else if((isMac && meta && code === 88) || (!isMac && ctrl && code === 88)){//剪切 Ctrl+X
			if(!control)return false;
			window.contextmenuControl = control;
			contextmenuOperate('cut');
		}else if((isMac && meta && code === 67) || (!isMac && ctrl && code === 67)){//复制 Ctrl+C
			if(!control)return false;
			window.contextmenuControl = control;
			contextmenuOperate('copy');
		}else if((isMac && meta && !shift && code === 86) || (!isMac && ctrl && !shift && code === 86)){//粘贴到画布 Ctrl+V
			contextmenuOperate('paste');
		}else if((isMac && meta && shift && code === 86) || (!isMac && ctrl && shift && code === 86)){//粘贴到鼠标位置 Ctrl+Shift+V
			let width = viewer.outerWidth(false), height = viewer.outerHeight(false);
			if(window.sceneMouseX>=0 && window.sceneMouseX<width && window.sceneMouseY>=0 && window.sceneMouseY<height){
				contextmenuOperate('paste-mouse');
			}
		}else if((isMac && meta && shift && code === 76) || (!isMac && ctrl && shift && code === 76)){//锁定/解锁 Ctrl+Shift+L
			if(!control)return false;
			window.contextmenuControl = control;
			contextmenuOperate('lock');
		}else if((isMac && meta && shift && code === 38) || (!isMac && ctrl && shift && code === 38)){//置顶 Ctrl+Shift+Up
			if(!control)return false;
			window.contextmenuControl = control;
			contextmenuOperate('top');
		}else if((isMac && meta && alt && code === 38) || (!isMac && ctrl && alt && code === 38)){//上移一层 Ctrl+Alt+Up
			if(!control)return false;
			window.contextmenuControl = control;
			contextmenuOperate('up');
		}else if((isMac && meta && alt && code === 40) || (!isMac && ctrl && alt && code === 40)){//下移一层 Ctrl+Alt+Down
			if(!control)return false;
			window.contextmenuControl = control;
			contextmenuOperate('down');
		}else if((isMac && meta && shift && code === 40) || (!isMac && ctrl && shift && code === 40)){//置底 Ctrl+Shift+Down
			if(!control)return false;
			window.contextmenuControl = control;
			contextmenuOperate('bottom');
		}else if(code === 8 || code === 46){//删除 Delete
			if(!control)return false;
			window.contextmenuControl = control;
			contextmenuOperate('delete');
		}
		return true;
	});
	$('.scene .container .parent').on('mousedown', selectAreaStart);
	$('.scene .viewer').on('dragstart', 'img, a', function(){return false});
	$('.preview-out').click(preview);
	$('.boxer > .music').click(function(){
		let _this = $(this), audio = $('#audio');
		if(_this.hasClass('playing')){
			_this.removeClass('playing');
			audio[0].pause();
			audio[0].currentTime = 0;
		}else{
			_this.addClass('playing');
			audio[0].play();
		}
	});
	$('.viewer').on('mouseup', '.lock .resize-panel .ne', function(){
		window.contextmenuControl = $(this).parent().parent();
		contextmenuOperate('lock');
	});
	$('.phone').after('<div class="loadicon"></div>');
	$('.phone').after('<div class="selectarea hidden"></div>');
	let boxer = $('.boxer').append('<ul class="ruler ruler-h '+(Number(window.sceneData.ruler)===0?'hidden':'')+'"></ul><ul class="ruler ruler-v '+(Number(window.sceneData.ruler)===0?'hidden':'')+'"></ul>'),
		horizontalNum = Math.ceil(boxer.outerWidth(false)/100), verticalNum = Math.ceil(boxer.outerHeight(false)/100);
	for(let i=0; i<horizontalNum; i++){
		$('.ruler-h').append('<li><span>'+(i*100)+'</span></li>');
	}
	for(let i=0; i<verticalNum; i++){
		$('.ruler-v').append('<li><span>'+(i*100)+'</span></li>');
	}
	let gridInfo = window.sceneData.grid || '1|50|#dddddd';
	gridInfo = gridInfo.split('|');
	setGrid(gridInfo[1], gridInfo[2]);
	if(Number(gridInfo[0]) === 0)$('.viewer section').addClass('hidden');
	else $('.viewer section').removeClass('hidden');
	contextmenuInit();
	menuInit();
	pageInit();
	setInit();
	toolbarInit();
	linkareaInit();
	setTips($('.tips'));
	let request = $.request('#');
	if(request && typeof request.showSet!=='undefined'){
		showSet();
		window.history.replaceState(null, document.title, location.href.replace('#showSet', ''));
	}
}
function contextmenuInit(){
	$('.phone').after('<div class="contextmenu hidden"></div>');
	$('.scene .container')[0].oncontextmenu = function(e){
		if($('body').hasClass('scene-preview'))return true;
		$('.toolbar').addClass('hidden');
		resetPageTitle();
		let o = e.target, contextmenu = $('.contextmenu'), control = getControl();
		do{
			if($(o).is('.item') || $(o).is('.viewer')){
				o = $(o);
				let clientHeight = $.window().height, offset = $.touches(e), left = offset.x, top = offset.y, html = '';
				if(o.is('.viewer')){
					if(control)control.ZResizeHidden();
					contextmenu.attr('target', 'container');
					html = '<div '+(!window.sceneElementClone?'class="disable"':'')+'>'+(!window.sceneElementClone?'':'<span class="eqf-right"></span>')+'<em class="eqf-print"></em>粘贴'+(!window.sceneElementClone?'':'<ul><li><a href="javascript:void(0)" type="paste"><span>'+platformKey('Ctrl')+' + V</span>到画布</a></li><li><a href="javascript:void(0)" type="paste-mouse"><span>'+platformKey('Ctrl')+' + '+platformKey('Shift')+' + V</span>到鼠标位置</a></li></ul>')+'</div>';
				}else{
					contextmenu.removeAttr('target');
					if(!o.is('.control') || o.find('.resize-panel').css('display')==='none')o.trigger('click');
					window.contextmenuControl = o;
					html = '<a href="javascript:void(0)" type="cut"><span>'+platformKey('Ctrl')+' + X</span><em class="eqf-cut"></em>剪切</a>\
					<a href="javascript:void(0)" type="copy"><span>'+platformKey('Ctrl')+' + C</span><em class="eqf-scene-copy"></em>复制</a>\
					<div '+(!window.sceneElementClone?'class="disable"':'')+'>'+(!window.sceneElementClone?'':'<span class="eqf-right"></span>')+'<em class="eqf-print"></em>粘贴'+(!window.sceneElementClone?'':'<ul><li><a href="javascript:void(0)" type="paste"><span>'+platformKey('Ctrl')+' + V</span>到画布</a></li><li><a href="javascript:void(0)" type="paste-mouse"><span>'+platformKey('Ctrl')+' + '+platformKey('Shift')+' + V</span>到鼠标位置</a></li></ul>')+'</div>\
					<em></em>\
					<a href="javascript:void(0)" type="lock"><span>'+platformKey('Ctrl')+' + '+platformKey('Shift')+' + L</span><em class="'+(o.hasClass('lock')?'eqf-lock-open':'eqf-lock')+'"></em>'+(o.hasClass('lock')?'解锁':'锁定')+'</a>\
					<em></em>\
					<a href="javascript:void(0)" type="top" '+((!o.next().length||o.hasClass('lock'))?'class="disable"':'')+'><span>'+platformKey('Ctrl')+' + '+platformKey('Shift')+' + ↑</span><em class="eqf-top"></em>置顶</a>\
					<a href="javascript:void(0)" type="up" '+((!o.next().length||o.hasClass('lock'))?'class="disable"':'')+'><span>'+platformKey('Ctrl')+' + '+platformKey('Alt')+' + ↑</span><em class="eqf-up"></em>上移一层</a>\
					<a href="javascript:void(0)" type="down" '+((o.prev().is('section')||o.hasClass('lock'))?'class="disable"':'')+'><span>'+platformKey('Ctrl')+' + '+platformKey('Alt')+' + ↓</span><em class="eqf-down"></em>下移一层</a>\
					<a href="javascript:void(0)" type="bottom" '+((o.prev().is('section')||o.hasClass('lock'))?'class="disable"':'')+'><span>'+platformKey('Ctrl')+' + '+platformKey('Shift')+' + ↓</span><em class="eqf-under"></em>置底</a>\
					<em></em>\
					<a href="javascript:void(0)" type="delete"><span>Delete/Backspace</span><em class="eqf-scene-delete"></em>删除</a>';
				}
				contextmenu.html(html);
				contextmenu.css({left:-9999, top:-9999}).removeClass('hidden');
				let height = contextmenu.outerHeight(false);
				if(top + height > clientHeight)top = clientHeight - height;
				contextmenu.css({left:left+10, top:top-50+$('.scene .container').scrollTop()});
				return false;
			}
			o = o.parentNode;
		}while(o.parentNode);
		window.contextmenuControl = null;
		contextmenu.addClass('hidden');
		if(control)control.ZResizeHidden();
		return false;
	};
	$('.contextmenu').on('click', 'a:not(.disable)', function(){
		contextmenuOperate($(this).attr('type'));
	});
}
function menuInit(){
	let gridValue = $('#grid').val()||'';
	if(gridValue.length){
		gridValue = gridValue.split('|');
		gridValue = gridValue[2];
	}
	$('.scene-menu').html('<span class="return"><em class="eqf-left"></em></span>\
	<div class="menu bg">\
		<em class="eqf-top-background"></em>背景\
		<ul>\
			<li><a href="/scene/pic?type=bg" class="bg tips iframe-layer" width="770" tips-follow="right" title="'+platformKey('Shift')+' + B"><span class="eqf-top-pic"></span>图片</a></li>\
			<li><a href="javascript:void(0)" class="color"><span class="eqf-moban"></span>纯色</a></li>\
		</ul>\
	</div>\
	<a href="/scene/pic" class="menu pic tips iframe-layer" width="770" title="'+platformKey('Shift')+' + P"><em class="eqf-top-pic"></em>图片</a>\
	<a href="javascript:void(0)" class="menu text tips" coo-click="setText" title="'+platformKey('Shift')+' + T"><em class="eqf-text-word"></em>文字</a>\
	<a href="/scene/music" class="menu music tips iframe-layer" width="770" title="'+platformKey('Shift')+' + M"><em class="eqf-top-music"></em>音乐</a>\
	<a href="/scene/video" class="menu video tips iframe-layer" width="600" title="'+platformKey('Shift')+' + V"><em class="eqf-top-video"></em>视频</a>\
	<a href="javascript:void(0)" class="menu web tips" coo-click="setWeb" title="'+platformKey('Shift')+' + W"><em></em>网页</a>\
	<a href="/scene/map" class="menu map tips iframe-layer" width="770" title="'+platformKey('Shift')+' + D<br />建议整个场景只添加一个地图"><em></em>地图</a>\
	<a href="/scene/chart" class="menu chart tips iframe-layer" width="1000" title="'+platformKey('Shift')+' + C"><em class="eqf-date3"></em>图表</a>\
	<a href="/scene/template" class="menu template tips iframe-layer" width="770" title="'+platformKey('Shift')+' + E"><em></em>模板</a>\
	<div class="menu grid tips" tips-skip="skipGrid" title="显示隐藏网格 '+platformKey('Shift')+' + G">\
		<em class="eqf-wangge"></em>网格\
		<ul>\
			<li><div class="grid-face slider size"><strong class="size" data-value=""></strong><input type="text" /><font>网格大小</font><span minValue="'+window.gridMinValue+'" maxValue="'+window.gridMaxValue+'"><i></i><b></b></span></div>\</li>\
			<li><a href="javascript:void(0)" class="grid-face color" color="'+gridValue+'"><span class="eqf-moban"></span>线条颜色</a></li>\
		</ul>\
	</div>\
	<a href="javascript:void(0)" class="menu ruler tips" coo-click="ruler" title="'+platformKey('Shift')+' + R"><em></em>标尺</a>\
	<div class="menu pad tips" tips-skip="skipPad" title="隐藏边框 '+platformKey('Shift')+' + F">\
		<em class="eqf-pad"></em>边框\
		<ul>\
			<li><a href="javascript:void(0)" class="pad-face" coo-click="pad" data-face="iphone5s"><span class="eqf-phone"></span>iPhone 5s</a></li>\
			<li><a href="javascript:void(0)" class="pad-face" coo-click="pad" data-face="iphonex"><span class="eqf-phone"></span>iPhone X</a></li>\
			<li><a href="javascript:void(0)" class="pad-face" coo-click="pad" data-face="galaxys8"><span class="eqf-phone"></span>Galaxy S8</a></li>\
		</ul>\
	</div>\
	<a href="javascript:void(0)" class="menu preview tips" coo-click="preview" title="'+platformKey('Shift')+' + Y"><em class="eqf-play2"></em>预览</a>\
	<div class="action">\
		<a href="javascript:void(0)" class="gray-dark" coo-click="publish">发布</a>\
		<a href="javascript:void(0)" class="gray-dark save tips" coo-click="sceneSave" tips-bgcolor="#fff" fn="setSaveBtnTitle">保存</a>\
		<a href="javascript:void(0)" class="gray-dark tips" tips-bgcolor="#fff" coo-click="showSet" title="'+platformKey('Shift')+' + S">设置</a>\
	</div>').on('mousedown', resetPageTitle);
	$(document).on('mousedown', function(e){
		let o = e.target;
		do{
			if($(o).is('.bg') || $(o).is('.grid') || $(o).is('.pad') || $(o).is('.colorpickerControl'))return true;
			o = o.parentNode;
		}while(o.parentNode);
		$('.scene-menu div.menu').removeClass('open');
		return true;
	});
	$('.scene-menu div.menu').click(function(e){
		if($(this).hasClass('disable'))return false;
		let o = e.target, _this = $(this);
		do{
			if($(o).is('.color') || $(o).is('.slider') || $(o).is('.colorpickerControl'))return false;
			if($(o).is('.menu.grid')){
				let value = $('#grid').val(), span = $('.scene-menu .grid .slider span'), minVal = Number(span.attr('minValue')), maxVal = Number(span.attr('maxValue'));
				value = value.split('|');
				let percent = (Number(value[1]) - minVal) / (maxVal - minVal);
				if(percent<0)percent = 0;
				if(percent>1)percent = 1;
				$('.scene-menu .grid .slider input').val(value[1]).prev().attr('data-value', value[1]);
				span.find('i').css('width', Math.ceil(percent*100)+'%');
				span.find('b').css('left', Math.ceil(percent*100)+'%');
			}
			o = o.parentNode;
		}while(o.parentNode);
		$('.scene-menu div.menu').removeClass('open');
		if(_this.hasClass('open')){
			_this.removeClass('open');
		}else{
			_this.addClass('open');
		}
	});
	$('.scene-menu .bg .color').colorpicker({
		target: null,
		callback: function(color){
			setBg(color);
			$('.scene-menu div.menu').removeClass('open');
		}
	});
	$('.scene-menu .grid .color').colorpicker({
		target: null,
		type: 'panel',
		callback: function(color, el, rgba){
			let value = $('#grid').val();
			value = value.split('|');
			setGrid(value[1], rgba);
			$('#grid').val(value[0]+'|'+value[1]+'|'+rgba);
			//$('.scene-menu div.menu').removeClass('open');
		}
	});
	
	$('.scene-menu .grid .slider span').each(function(){
		let span = $(this), input = span.prev().prev(), parent = span.parent(), left = 0, width = 0, val = 0, minVal = Number(span.attr('minValue')), maxVal = Number(span.attr('maxValue')),
			sliderStart = function(e){
				width = parent.width();
				left = span.offset().left;
				span.css('cursor', 'grabbing');
				sliderMove(e);
				parent.on('mousemove', sliderMove);
			},
			sliderMove = function(e){
				let x = $.touches(e).x - left, percent = x / width;
				if(percent<0)percent = 0;
				if(percent>1)percent = 1;
				if(parent.hasClass('size')){
					val = minVal + Math.ceil((maxVal-minVal)*percent);
					let value = $('#grid').val();
					value = value.split('|');
					setGrid(val, value[2]);
					$('#grid').val(value[0]+'|'+val+'|'+value[2]);
				}
				input.val(val).prev().attr('data-value', val);
				span.find('i').css('width', Math.ceil(percent*100)+'%');
				span.find('b').css('left', Math.ceil(percent*100)+'%');
			},
			sliderEnd = function(e){
				span.css('cursor', '');
				parent.off('mousemove', sliderMove);
			},
			sliderInputChange = function(){
				width = parent.width();
				let val = input.val(), x = (val.length && !isNaN(val)) ? Number(Math.ceil(val)) : 0, percent = 0;
				if(x < minVal)x = minVal;
				if(x > maxVal)x = maxVal;
				input.val(x).prev().attr('data-value', x);
				if(parent.hasClass('size')){
					percent = (x - minVal) / (maxVal - minVal);
				}
				if(percent<0)percent = 0;
				if(percent>1)percent = 1;
				if(parent.hasClass('size')){
					let value = $('#grid').val();
					value = value.split('|');
					setGrid(x, value[2]);
					$('#grid').val(value[0]+'|'+x+'|'+value[2]);
				}
				span.find('i').css('width', Math.ceil(percent*100)+'%');
				span.find('b').css('left', Math.ceil(percent*100)+'%');
			};
		span.on('mousedown', sliderStart).on('mouseup', sliderEnd);
		input.on('keyup', sliderInputChange);
		parent.on('mouseup', sliderEnd);
	});
	$('.scene-menu .chart-item').click(function(e){
		setChart();
	});
	$('.scene-menu span.return').click(returnHome);
	setSaveBtnTitle();
}
function setInit(){
	let data = window.sceneData;
	$('.set').html('<div class="nav">\
		<a href="javascript:void(0)" class="this">常用设置</a><a href="javascript:void(0)">分享设置</a><a href="javascript:void(0)">高级设置</a>\
	</div>\
	<ul class="view">\
		<li>\
			<div class="line share-info">\
				<a href="/scene/pic?type=cover" class="tips iframe-layer" title="更换封面" width="770"></a>\
				<div>\
					<em>标题</em>\
					<span><input type="text" name="title" id="title" placeholder="场景名称" value="'+data.title+'" class="input" maxlength="34" /></span>\
					<em>描述</em>\
					<span><textarea name="memo" placeholder="点击添加描述即可在左侧同步看到最终分享时的效果" class="input" maxlength="30">'+data.memo+'</textarea></span>\
				</div>\
			</div>\
			<h6>场景音乐</h6>\
			<div class="line music-info">\
				<a href="/scene/music" class="iframe-layer input width180" width="770">\
					<em class="eqf-top-music"></em>\
					<span>'+((data.music_name&&data.music_name.length)?data.music_name:'未添加')+'</span>\
				</a>\
				<a href="javascript:void(0)" class="clear-music '+(!data.music.length?'hidden':'')+'"></a>\
				<a href="/scene/pic?type=music" class="iframe-layer tips music-button" width="770" max-width="50" data-src="'+((data.music_pic&&data.music_pic.length)?data.music_pic:'')+'">音乐图片</a>\
				<label class="radio"><input type="radio" name="music_position" class="music_position" id="music_position0" value="0" '+(Number(data.music_position)===0?'checked':'')+' /><div></div></label><label for="music_position0">左上角</label>\
				<label class="radio"><input type="radio" name="music_position" class="music_position" id="music_position1" value="1" '+(Number(data.music_position)===1?'checked':'')+' /><div></div></label><label for="music_position1">右上角</label>\
				<div class="music-play">\
					<font>立即播放</font>\
					<label class="checkbox-app">\
						<input type="checkbox" name="music_play" value="1" '+(Number(data.music_play)===1?'checked':'')+' /><div><span></span></div>\
					</label>\
				</div>\
			</div>\
		</li>\
		<li class="hidden">\
			<h6>场景访问状态</h6>\
			<div class="line">\
				<div class="width180 input2 status">\
					<i class="eqf-clickmore"></i>\
					<span>'+(Number(data.status)===0?'不允许访问':'允许访问')+'</span>\
					<div class="width180">\
						<a href="javascript:void(0)">不允许访问</a>\
						<a href="javascript:void(0)">允许访问</a>\
					</div>\
				</div>\
			</div>\
			<div class="line">\
				<div class="option">\
					<span>跳转链接</span><input type="text" class="input" name="share_url" value="'+data.share_url+'" placeholder="分享后跳转链接" />\
				</div>\
			</div>\
			<div class="line">\
				<div class="option">\
					<span>后退链接</span><input type="text" class="input" name="return_url" value="'+data.return_url+'" placeholder="场景后退跳转链接" />\
				</div>\
			</div>\
			<div class="line">\
				<div class="option">\
					<span>分流链接</span><input type="text" class="input" name="fenliu_url" value="'+data.fenliu_url+'" placeholder="打开场景直接跳转的链接" />\
				</div>\
			</div>\
		</li>\
		<li class="hidden">\
			<h6>广告尾页</h6>\
			<div class="line suffix">\
				<label class="radio-line">\
					<div class="radio"><input type="radio" name="suffix" value="1" '+(Number(data.suffix)===1?'checked':'')+' /><div></div></div> 显示广告尾页\
				</label>\
				<label class="radio-line">\
					<div class="radio"><input type="radio" name="suffix" value="0" '+(Number(data.suffix)===0?'checked':'')+' /><div></div></div> 隐藏广告尾页\
				</label>\
			</div>\
		</li>\
	</ul>').before('<div class="set-layer hidden"></div>');
	if(data.music.length)setMusic({title:data.music_name, url:data.music});
	$('.set-layer').click(showSet);
	$('.set .nav a').click(function(){
		$(this).addClass('this').siblings().removeClass('this');
		$('.set .view li').eq($(this).index()).removeClass('hidden').siblings().addClass('hidden');
	});
	$('.set .status').control({
		show: function(){
			this.find('div').slideDown(200);
		},
		hide: function(){
			this.find('div').slideUp(200);
		}
	}).find('a').click(function(){
		$('.set .status span').html($(this).html());
		$('#status').val($(this).index());
	});
	$('.clear-music').click(function(){
		let music = $(this).addClass('hidden').prev();
		music.find('span').html('未添加');
		music.find('em').removeAttr('class').addClass('eqf-top-music');
		$('.boxer > .music').addClass('hidden');
		$('#music').val('');
		$('#music_name').val('');
		$(this).parent().find(':checkbox').prop('checked', false);
	});
	$('.music_position').change(function(){
		if(this.checked){
			if(Number(this.value)===0){
				$('.music').removeClass('right');
			}else{
				$('.music').addClass('right');
			}
		}
	});
}
function toolbarInit(){
	$('.phone').after('<div class="toolbar hidden">\
		<div class="group picHidden videoHidden webHidden mapHidden chartHidden">\
			<a href="javascript:void(0)" class="jt tips fontfamily" title="字体样式" style="width:90px;">默认字体</a>\
			<ul style="width:128px;">\
				<li><a href="javascript:void(0)">(默认字体)</a></li>\
				<li><a href="javascript:void(0)" style="font-family:SimSun;" font="SimSun">宋体</a></li>\
				<li><a href="javascript:void(0)" style="font-family:NSimSun;" font="NSimSun">新宋体</a></li>\
				<li><a href="javascript:void(0)" style="font-family:FangSong_GB2312;" font="FangSong_GB2312">仿宋_GB2312</a></li>\
				<li><a href="javascript:void(0)" style="font-family:KaiTi_GB2312;" font="KaiTi_GB2312">楷体_GB2312</a></li>\
				<li><a href="javascript:void(0)" style="font-family:SimHei;" font="SimHei">黑体</a></li>\
				<li><a href="javascript:void(0)" style="font-family:\'Microsoft YaHei\';" font="Microsoft YaHei">微软雅黑</a></li>\
				<li><a href="javascript:void(0)" style="font-family:Arial;" font="Arial">Arial</a></li>\
				<li><a href="javascript:void(0)" style="font-family:\'Arial Black\';" font="Arial Black">Arial Black</a></li>\
				<li><a href="javascript:void(0)" style="font-family:\'Times New Roman\';" font="Times New Roman">Times New Roman</a></li>\
				<li><a href="javascript:void(0)" style="font-family:\'Courier New\';" font="Courier New">Courier New</a></li>\
				<li><a href="javascript:void(0)" style="font-family:Tahoma;" font="Tahoma">Tahoma</a></li>\
				<li><a href="javascript:void(0)" style="font-family:Verdana;" font="Verdana">Verdana</a></li>\
			</ul>\
		</div>\
		<div class="group picHidden videoHidden webHidden mapHidden chartHidden">\
			<a href="javascript:void(0)" class="jt tips fontsize" title="字体大小" style="width:60px;">24px</a>\
			<ul style="width:180px;">\
				<li><a href="javascript:void(0)" class="fontsize12" style="font-size:12px;">12px</a></li>\
				<li><a href="javascript:void(0)" class="fontsize13" style="font-size:13px;">13px</a></li>\
				<li><a href="javascript:void(0)" class="fontsize14" style="font-size:14px;">14px</a></li>\
				<li><a href="javascript:void(0)" class="fontsize16" style="font-size:16px;">16px</a></li>\
				<li><a href="javascript:void(0)" class="fontsize18" style="font-size:18px;">18px</a></li>\
				<li><a href="javascript:void(0)" class="fontsize20" style="font-size:20px;">20px</a></li>\
				<li><a href="javascript:void(0)" class="fontsize24" style="font-size:24px;">24px</a></li>\
				<li><a href="javascript:void(0)" class="fontsize32" style="font-size:32px;">32px</a></li>\
				<li><a href="javascript:void(0)" class="fontsize48" style="font-size:48px;">48px</a></li>\
				<li><a href="javascript:void(0)" class="fontsize64" style="font-size:64px;">64px</a></li>\
			</ul>\
		</div>\
		<div class="group picHidden videoHidden webHidden mapHidden chartHidden">\
			<a href="javascript:void(0)" class="tips text" title="字体颜色"><span>A<i></i></span></a>\
		</div>\
		<div class="group picHidden videoHidden webHidden mapHidden chartHidden">\
			<a href="javascript:void(0)" class="tips bg" title="背景颜色"><span>A</span></a>\
		</div>\
		<div class="group picHidden videoHidden webHidden mapHidden chartHidden">\
			<div class="style" style="width:80px;">\
				<a href="javascript:void(0)" class="b tips eqf-b" title="加粗<br />'+platformKey('Ctrl')+' + B"></a>\
				<a href="javascript:void(0)" class="i tips eqf-I" title="倾斜<br />'+platformKey('Ctrl')+' + I"></a>\
				<a href="javascript:void(0)" class="u tips eqf-U" title="下划线<br />'+platformKey('Ctrl')+' + U"></a>\
				<a href="javascript:void(0)" class="s tips eqf-s" title="删除线<br />'+platformKey('Ctrl')+' + D"></a>\
			</div>\
		</div>\
		<div class="group picHidden videoHidden webHidden mapHidden chartHidden">\
			<a href="javascript:void(0)" class="jt tips textalign" title="对齐方式" style="width:60px;"><span class="eqf-leftword"></span></a>\
			<ul style="width:60px;">\
				<li><a href="javascript:void(0)" class="left" title="左对齐"><span class="eqf-leftword"></span></a></li>\
				<li><a href="javascript:void(0)" class="center" title="居中"><span class="eqf-minddleword"></span></a></li>\
				<li><a href="javascript:void(0)" class="right" title="右对齐"><span class="eqf-rightword"></span></a></li>\
				<li><a href="javascript:void(0)" class="justify" title="两端对齐"><span class="eqf-scene-list"></span></a></li>\
			</ul>\
		</div>\
		<div class="group picHidden videoHidden webHidden mapHidden chartHidden">\
			<a href="javascript:void(0)" class="jt tips lineheight" title="行高" style="width:60px;"><span class="eqf-linebig"></span></a>\
			<ul style="width:80px;">\
				<li><a href="javascript:void(0)" class="lineheight025">0.25</a></li>\
				<li><a href="javascript:void(0)" class="lineheight050">0.50</a></li>\
				<li><a href="javascript:void(0)" class="lineheight075">0.75</a></li>\
				<li><a href="javascript:void(0)" class="lineheight100">1.00</a></li>\
				<li><a href="javascript:void(0)" class="lineheight150">1.50</a></li>\
				<li><a href="javascript:void(0)" class="lineheight175">1.75</a></li>\
				<li><a href="javascript:void(0)" class="lineheight200">2.00</a></li>\
				<li><a href="javascript:void(0)" class="lineheight250">2.50</a></li>\
				<li><a href="javascript:void(0)" class="lineheight300">3.00</a></li>\
			</ul>\
		</div>\
		<div class="group picHidden videoHidden webHidden mapHidden chartHidden">\
			<a href="javascript:void(0)" class="jt tips letterspacing" title="字间距" style="width:60px;"><span class="eqf-letter"></span></a>\
			<ul style="width:80px;">\
				<li><a href="javascript:void(0)" class="letterspacing0">0%</a></li>\
				<li><a href="javascript:void(0)" class="letterspacing10">10%</a></li>\
				<li><a href="javascript:void(0)" class="letterspacing25">25%</a></li>\
				<li><a href="javascript:void(0)" class="letterspacing50">50%</a></li>\
				<li><a href="javascript:void(0)" class="letterspacing75">75%</a></li>\
				<li><a href="javascript:void(0)" class="letterspacing100">100%</a></li>\
			</ul>\
		</div>\
		<div class="group">\
			<a href="javascript:void(0)" class="jt tips style" title="样式" style="width:50px;"><span class="eqf-type"></span></a>\
			<ul class="style" style="width:220px;">\
				<li>\
					<div class="symmetry">\
						<div class="x"><font>X</font><input type="text" /></div>\
						<div class="y"><font>Y</font><input type="text" /></div>\
					</div>\
				</li>\
				<li>\
					<div class="symmetry">\
						<div class="width"><font>宽</font><input type="text" /></div>\
						<div class="height"><font>高</font><input type="text" /></div>\
					</div>\
				</li>\
				<li class="textHidden">\
					<div class="option fulladaption"><font>全屏自适应</font><label class="checkbox-app"><input type="checkbox" /><span><i></i></span></label></div>\
				</li>\
				<li class="videoHidden webHidden mapHidden chartHidden">\
					<div class="slider opacity"><strong class="opacity" data-value=""></strong><input type="text" /><font>不透明度</font><span><i></i><b></b></span></div>\
				</li>\
				<li class="videoHidden webHidden mapHidden chartHidden">\
					<div class="slider rotate"><strong class="rotate" data-value=""></strong><input type="text" /><font>旋转角度</font><span><i></i><b></b></span></div>\
				</li>\
				<li class="videoHidden webHidden mapHidden chartHidden">\
					<div class="slider radius"><strong class="radius" data-value=""></strong><input type="text" /><font>圆角</font><span><i></i><b></b></span></div>\
				</li>\
			</ul>\
		</div>\
		<div class="group videoHidden webHidden mapHidden chartHidden">\
			<a href="javascript:void(0)" class="tips clearstyle" title="清除样式"><span class="eqf-clear"></span></a>\
		</div>\
		<div class="group videoHidden webHidden mapHidden chartHidden">\
			<a href="javascript:void(0)" class="jt tips animation" title="动画" style="width:60px;"><span class="eqf-paste-animation"></span></a>\
			<ul class="animation" style="width:260px;">\
				<div class="btns">\
					<a href="javascript:void(0)"><em class="eqf-play2"></em> 预览动画</a>\
					<a href="javascript:void(0)"><em class="eqf-plus2"></em> 添加动画</a>\
				</div>\
			</ul>\
		</div>\
		<div class="group videoHidden webHidden mapHidden chartHidden">\
			<a href="javascript:void(0)" class="jt tips link" title="超链接" style="width:50px;"><span class="eqf-link"></span></a>\
			<ul style="width:100px;">\
				<li><a href="/scene/link" class="iframe-layer" width="600"><em class="eqf-link"></em> 添加超链接</a></li>\
				<li><a href="javascript:void(0)"><em class="eqf-linkout"></em> 清除超链接</a></li>\
			</ul>\
		</div>\
	</div>');
	$(document).on('mousedown', function(e){
		let o = e.target;
		do{
			if($(o).is('.group'))return true;
			if((/^(html|body)$/i).test(o.tagName)){$('.toolbar .group').removeClass('open');return true}
			o = o.parentNode;
		}while(o.parentNode);
	});
	$('.toolbar .group > a').each(function(){
		let _this = $(this);
		if(_this.is('.fontfamily')){
			let ul = _this.next();
			ul.find('a').click(function(){
				let control = getControl(), txt = $(this).html(), val = $(this).attr('font');
				ul.find('.select').removeClass('select');
				_this.parent().removeClass('open');
				if(!!!val){
					_this.html('默认字体');
					control.css('font-family', '');
				}else{
					$(this).addClass('select');
					_this.html(txt);
					control.css('font-family', val);
				}
			});
		}
		if(_this.is('.fontsize')){
			let ul = _this.next();
			ul.find('a').click(function(){
				let control = getControl(), val = $(this).html();
				ul.find('.select').removeClass('select');
				$(this).addClass('select');
				_this.parent().removeClass('open');
				_this.html(val);
				control.css('font-size', val);
			});
		}
		if(_this.is('.textalign')){
			let span = _this.find('span'), ul = _this.next();
			ul.find('a').click(function(){
				let control = getControl();
				ul.find('.select').removeClass('select');
				$(this).addClass('select');
				_this.parent().removeClass('open');
				if($(this).is('.left')){
					span.removeAttr('class').addClass('eqf-leftword');
					control.css('text-align', 'left');
				}else if($(this).is('.center')){
					span.removeAttr('class').addClass('eqf-minddleword');
					control.css('text-align', 'center');
				}else if($(this).is('.right')){
					span.removeAttr('class').addClass('eqf-rightword');
					control.css('text-align', 'right');
				}else if($(this).is('.justify')){
					span.removeAttr('class').addClass('eqf-scene-list');
					control.css('text-align', 'justify');
				}
			});
		}
		if(_this.is('.lineheight')){
			let ul = _this.next();
			ul.find('a').click(function(){
				let control = getControl(), val = $(this).html();
				ul.find('.select').removeClass('select');
				$(this).addClass('select');
				_this.parent().removeClass('open');
				control.css('line-height', val);
			});
		}
		if(_this.is('.letterspacing')){
			let ul = _this.next();
			ul.find('a').click(function(){
				let control = getControl(), val = $(this).html().replace('%', '')/100;
				ul.find('.select').removeClass('select');
				$(this).addClass('select');
				_this.parent().removeClass('open');
				control.css('letter-spacing', val+'em');
			});
		}
		if(_this.is('.link')){
			let ul = _this.next();
			ul.find('a').click(function(){
				_this.parent().removeClass('open');
				if(ul.find('a').index(this)===1){
					getControl().removeAttr('link').find('em').remove();
				}
			});
		}
	}).on('click', function(){
		let _this = $(this), control = getControl()||[], toolbar = $('.toolbar');
		$('.toolbar .group').removeClass('open');
		if(!_this.parent().hasClass('open')){
			if(!_this.hasClass('clearstyle'))_this.parent().addClass('open');
			if(_this.next().is('ul')){
				let ul = _this.next().css({top:'', height:''}), ulHeight = ul.outerHeight(false),
					ulTop = ul.offset().top, ulPadding = ul.padding().top + ul.padding().bottom, ulBorder = ul.border().top + ul.border().bottom,
					toolbarHeight = toolbar.outerHeight(false), toolbarTop = toolbar.offset().top, clientHeight = $.window().height;
				if(ulTop + ulHeight > clientHeight){
					let topHeight = toolbarTop - $('.scene-menu').outerHeight(false), bottomHeight = clientHeight - toolbarTop - toolbarHeight;
					if(topHeight > bottomHeight){
						if(topHeight > ulHeight){
							ul.css('top', -ulHeight-8);
						}else{
							ul.css({top:-topHeight-8+2, height:topHeight-8-ulBorder-2});
						}
					}else{
						ul.height(clientHeight - ulTop - ulPadding - ulBorder);
					}
				}
			}
		}
		if(_this.is('.link')){
			_this.next().find('li:first a').html('<em class="eqf-link"></em> '+(!!control.attr('link')?'修改超链接':'添加超链接'));
		}
		if(_this.is('.style')){
			let style = control.attr('style'), controlHalfHeight = Math.ceil(control.outerHeight(false)/2), ul = _this.next(),
				opacity = 0,
				rotate = getTransform(control[0]).angle,
				radius = control.css('border-radius').replace('px', '');
			ul.find('.rotate i').css('width', Math.ceil(rotate/360*100)+'%');
			ul.find('.rotate b').css('left', Math.ceil(rotate/360*100)+'%');
			ul.find('.rotate input').val(rotate).prev().attr('data-value', rotate);
			ul.find('.radius i').css('width', Math.ceil(radius/controlHalfHeight*100)+'%');
			ul.find('.radius b').css('left', Math.ceil(radius/controlHalfHeight*100)+'%');
			ul.find('.radius input').val(radius).prev().attr('data-value', radius);
			if(/opacity:\s*[\d.]+;/.test(style)){
				let matcher = style.match(/opacity:\s*([\d.]+);/);
				opacity = Math.ceil(Number(matcher[1])*100);
			}else{
				opacity = Math.ceil(Number(control.css('opacity'))*100);
			}
			ul.find('.opacity i').css('width', opacity+'%');
			ul.find('.opacity b').css('left', opacity+'%');
			ul.find('.opacity input').val(opacity).prev().attr('data-value', opacity);
			let left = Number(control.css('left').replace('px', '')), top = Number(control.css('top').replace('px', '')), width = 0, height = 0;
			ul.find('.x input').val(left);
			ul.find('.y input').val(top);
			if(/width:\s*[\d.]+%;/.test(style)){
				let matcher = style.match(/width:\s*([\d.]+)%;/);
				width = matcher[1]+'%';
				ul.find('.width input').val(width);
			}else{
				width = control.outerWidth(false);
				ul.find('.width input').val(width);
			}
			if(/height:\s*[\d.]+%;/.test(style)){
				let matcher = style.match(/height:\s*([\d.]+)%;/);
				height = matcher[1]+'%';
				ul.find('.height input').val(height);
			}else{
				height = control.outerHeight(false);
				ul.find('.height input').val(height);
			}
			if(!!control.attr('fulladaption')){
				ul.find('.fulladaption input').attr('checked', 'checked').prop('checked', true);
				control.css({left:0, top:0, width:'100%', height:'100%'});
				ul.find('.x input, .y input, .width input, .height input').attr('disabled', 'disabled').prop('disabled', true);
			}else{
				ul.find('.fulladaption input').removeAttr('checked').prop('checked', false);
				control.css({left:left, top:top, width:width, height:height}).attr({left:left, top:top, width:width, height:height});
				ul.find('.x input, .y input, .width input, .height input').removeAttr('disabled').prop('disabled', false);
			}
		}
		if(_this.is('.clearstyle')){
			control.css({
				'font-family': '',
				'font-size': '',
				'font-weight': '',
				'font-style': '',
				'text-decoration-line': '',
				'text-align': '',
				'line-height': '',
				'letter-spacing': '',
				'border-radius': '',
				color: '',
				background: '',
				opacity: '',
				transform: '',
				animation: ''
			}).find('img').css({opacity:''});
			if(control.is('[type="text"]'))control.css({'font-size':'24px', 'line-height':'1'});
			$('.toolbar ul .select').removeClass('select');
			$('.toolbar .group > a.fontfamily').html('默认字体');
			$('.toolbar .group > a.fontsize').html('24px').next().find('fontsize24').addClass('select');
			$('.toolbar .group > a.text i').css('background', '');
			$('.toolbar .group > a.bg span').css('background', '');
			$('.toolbar .style a').removeClass('select');
			$('.toolbar .group > a.textalign span').removeAttr('class').addClass('eqf-leftword');
			$('.toolbar .lineheight100').addClass('select');
			$('.toolbar .group ul.animation li').remove();
		}
	});
	$('.toolbar .group > a.text').colorpicker({
		target: null,
		readonly: false,
		type: 'panel',
		sep: -15,
		callback: function(color, el, rgba){
			getControl().css('color', rgba);
			$('.toolbar .group > a.text i').css('background', rgba);
		}
	});
	$('.toolbar .group > a.bg').colorpicker({
		target: null,
		transparent: true,
		readonly: false,
		type: 'panel',
		sep: -15,
		callback: function(color, el, rgba){
			if(color==='transparent'){
				getControl().css('background', '');
				$('.toolbar .group > a.bg span').css('background', '');
			}else{
				getControl().css('background', rgba);
				$('.toolbar .group > a.bg span').css('background', rgba);
			}
		}
	});
	$('.toolbar .group > div > a').click(function(){
		let _this = $(this), control = getControl(), line = control.css('text-decoration-line');
		if(line==='none')line = '';
		$('.toolbar .group').removeClass('open');
		if(_this.hasClass('select')){
			_this.removeClass('select');
			if(_this.hasClass('b')){
				control.css('font-weight', '');
			}else if(_this.hasClass('i')){
				control.css('font-style', '');
			}else if(_this.hasClass('u')){
				line = $.trim(line.replace('underline', ''));
				control.css('text-decoration-line', line);
			}else if(_this.hasClass('s')){
				line = $.trim(line.replace('line-through', ''));
				control.css('text-decoration-line', line);
			}
		}else{
			_this.addClass('select');
			if(_this.hasClass('b')){
				control.css('font-weight', 'bold');
			}else if(_this.hasClass('i')){
				control.css('font-style', 'italic');
			}else if(_this.hasClass('u')){
				line += line.length ? ' underline' : 'underline';
				control.css('text-decoration-line', line);
			}else if(_this.hasClass('s')){
				line += line.length ? ' line-through' : 'line-through';
				control.css('text-decoration-line', line);
			}
		}
	});
	$('.toolbar .group ul.style .x input').on('keyup', function(){
		let val = Number(Math.floor($(this).val())), control = getControl();
		if(/^-?\d+$/.test(val)){
			control.css('left', val);
		}else{
			$(this).val(0);
			control.css('left', 0);
		}
		let toolbar = $('.toolbar'), wrapper = $('.wrapper'),
			left = wrapper.parent().parent().position().left + wrapper.parent().position().left + wrapper.position().left + control.position().left,
			top = wrapper.parent().parent().position().top + wrapper.parent().position().top + wrapper.position().top + control.position().top;
		if(control.attr('type')==='text'){
			toolbar.css({
				left: ((Number(left)+control.outerWidth(false)+Number(left)) - toolbar.outerWidth(false)) / 2,
				top: Number(top) - 13 - toolbar.outerHeight(false)
			});
		}else{
			toolbar.css({
				left: Number(left) + control.outerWidth(false) + 20,
				top: Number(top)
			});
		}
	});
	$('.toolbar .group ul.style .y input').on('keyup', function(){
		let val = Number(Math.floor($(this).val())), control = getControl();
		if(/^-?\d+$/.test(String(val))){
			control.css('top', val);
		}else{
			$(this).val(0);
			control.css('top', 0);
		}
		let toolbar = $('.toolbar'), wrapper = $('.wrapper'),
			left = wrapper.parent().parent().position().left + wrapper.parent().position().left + wrapper.position().left + control.position().left,
			top = wrapper.parent().parent().position().top + wrapper.parent().position().top + wrapper.position().top + control.position().top;
		if(control.attr('type')==='text'){
			toolbar.css({
				left: ((Number(left)+control.outerWidth(false)+Number(left)) - toolbar.outerWidth(false)) / 2,
				top: Number(top) - 13 - toolbar.outerHeight(false)
			});
		}else{
			toolbar.css({
				left: Number(left) + control.outerWidth(false) + 20,
				top: Number(top)
			});
		}
	});
	$('.toolbar .group ul.style .width input').on('keyup', function(){
		let val = $(this).val(), control = getControl();
		if(!val.length)return;
		if(/^[\d.]+%?$/.test(val))control.css('width', val);
		let toolbar = $('.toolbar'), wrapper = $('.wrapper'),
			left = wrapper.parent().parent().position().left + wrapper.parent().position().left + wrapper.position().left + control.position().left,
			top = wrapper.parent().parent().position().top + wrapper.parent().position().top + wrapper.position().top + control.position().top;
		if(control.attr('type')==='text'){
			toolbar.css({
				left: ((Number(left)+control.outerWidth(false)+Number(left)) - toolbar.outerWidth(false)) / 2,
				top: Number(top) - 13 - toolbar.outerHeight(false)
			});
		}else{
			toolbar.css({
				left: Number(left) + control.outerWidth(false) + 20,
				top: Number(top)
			});
		}
		if(control.attr('type')==='map'){
			let map = control.find('i').baiduMap(true);
			map.panTo(new BMap.Point(control.attr('longitude'), control.attr('latitude')));
			map.setZoom(control.attr('zoom'));
		}else if(control.attr('type')==='chart'){
			control.find('i').remove();
			control.prepend('<i></i>');
			let chart = echarts.init(control.find('i')[0]);
			chart.setOption(control.data('chart.option'));
		}
	});
	$('.toolbar .group ul.style .height input').on('keyup', function(){
		let val = $(this).val(), control = getControl();
		if(!val.length)return;
		if(/^[\d.]+%?$/.test(val))control.css('height', val);
		if(control.attr('type')==='map'){
			let map = control.find('i').baiduMap(true);
			map.panTo(new BMap.Point(control.attr('longitude'), control.attr('latitude')));
			map.setZoom(control.attr('zoom'));
		}else if(control.attr('type')==='chart'){
			control.find('i').remove();
			control.prepend('<i></i>');
			let chart = echarts.init(control.find('i')[0]);
			chart.setOption(control.data('chart.option'));
		}
	});
	$('.toolbar .group ul.style .fulladaption input').on('change', function(){
		let control = getControl(), toolbar = $('.toolbar'), wrapper = $('.wrapper'), ul = $(this).parents('ul').eq(0), left = 0, top = 0;
		if(this.checked){
			control.attr('fulladaption', '1').css({left:0, top:0, width:'100%', height:'100%'});
			ul.find('.x input, .y input, .width input, .height input').attr('disabled', 'disabled').prop('disabled', true);
			ul.find('.x input').val(0);
			ul.find('.y input').val(0);
			ul.find('.width input').val('100%');
			ul.find('.height input').val('100%');
			left = wrapper.parent().parent().position().left + wrapper.parent().position().left + wrapper.position().left;
			top = wrapper.parent().parent().position().top + wrapper.parent().position().top + wrapper.position().top;
		}else{
			control.removeAttr('fulladaption');
			let l = control.attr('left'), t = control.attr('top'), w = control.attr('width'), h = control.attr('height');
			ul.find('.x input, .y input, .width input, .height input').removeAttr('disabled').prop('disabled', false);
			if(!!l){
				control.css({left:Number(l), top:Number(t), width:/^[\d.]+$/.test(w)?Number(w):w, height:/^[\d.]+$/.test(h)?Number(h):h});
				ul.find('.x input').val(l);
				ul.find('.y input').val(t);
				ul.find('.width input').val(w);
				ul.find('.height input').val(h);
			}
			left = wrapper.parent().parent().position().left + wrapper.parent().position().left + wrapper.position().left + Number(control.attr('left')||0);
			top = wrapper.parent().parent().position().top + wrapper.parent().position().top + wrapper.position().top + Number(control.attr('top')||0);
		}
		if(control.attr('type')==='map'){
			let map = control.find('i').baiduMap(true);
			map.panTo(new BMap.Point(control.attr('longitude'), control.attr('latitude')));
			map.setZoom(control.attr('zoom'));
		}else if(control.attr('type')==='chart'){
			control.find('i').remove();
			control.prepend('<i></i>');
			let chart = echarts.init(control.find('i')[0]);
			chart.setOption(control.data('chart.option'));
		}
		toolbar.css({
			left: Number(left) + control.outerWidth(false) + 20,
			top: Number(top)
		});
	});
	$('.toolbar .group ul.style .slider span').each(function(){
		let control = null, span = $(this), input = span.prev().prev(), parent = span.parent(), left = 0, width = 0, controlHalfHeight = 0, val = 0,
			sliderStart = function(e){
				control = getControl();
				controlHalfHeight = Math.ceil(control.outerHeight(false)/2);
				width = parent.width();
				left = span.offset().left;
				span.css('cursor', 'grabbing');
				let x = $.touches(e).x - left, percent = x / width;
				if(percent<0)percent = 0;
				if(percent>1)percent = 1;
				if(parent.hasClass('opacity')){
					val = Math.ceil(100*percent);
					control.css('opacity', percent).find('img, span').css('opacity', percent);
				}else if(parent.hasClass('rotate')){
					val = Math.ceil(360*percent);
					control.css('transform', 'rotate('+val+'deg)');
				}else if(parent.hasClass('radius')){
					val = Math.ceil(controlHalfHeight*percent);
					$('.viewer .control, .viewer .control img').css('border-radius', val+'px');
				}
				input.val(val).prev().attr('data-value', val);
				span.find('i').css('width', Math.ceil(percent*100)+'%');
				span.find('b').css('left', Math.ceil(percent*100)+'%');
				parent.on('mousemove', sliderMove);
			},
			sliderMove = function(e){
				let x = $.touches(e).x - left, percent = x / width;
				if(percent<0)percent = 0;
				if(percent>1)percent = 1;
				if(parent.hasClass('opacity')){
					val = Math.ceil(100*percent);
					control.css('opacity', percent).find('img, span').css('opacity', percent);
				}else if(parent.hasClass('rotate')){
					val = Math.ceil(360*percent);
					control.css('transform', 'rotate('+val+'deg)');
				}else if(parent.hasClass('radius')){
					val = Math.ceil(controlHalfHeight*percent);
					$('.viewer .control, .viewer .control img').css('border-radius', val+'px');
				}
				input.val(val).prev().attr('data-value', val);
				span.find('i').css('width', Math.ceil(percent*100)+'%');
				span.find('b').css('left', Math.ceil(percent*100)+'%');
			},
			sliderEnd = function(e){
				span.css('cursor', '');
				parent.off('mousemove', sliderMove);
			},
			sliderInputChange = function(e){
				width = parent.width();
				control = getControl();
				let val = input.val(), x = (val.length && !isNaN(val)) ? Number(Math.ceil(val)) : 0, percent = 0,
					controlHalfHeight = Math.ceil(control.outerHeight(false)/2);
				input.val(x).prev().attr('data-value', x);
				if(parent.hasClass('opacity')){
					percent = x / width;
				}else if(parent.hasClass('rotate')){
					percent = x / 360;
				}else if(parent.hasClass('radius')){
					percent = x / controlHalfHeight;
				}
				if(percent<0)percent = 0;
				if(percent>1)percent = 1;
				if(parent.hasClass('opacity')){
					control.css('opacity', percent).find('img, span').css('opacity', percent);
				}else if(parent.hasClass('rotate')){
					control.css('transform', 'rotate('+x+'deg)');
				}else if(parent.hasClass('radius')){
					$('.viewer .control, .viewer .control img').css('border-radius', x+'px');
				}
				span.find('i').css('width', Math.ceil(percent*100)+'%');
				span.find('b').css('left', Math.ceil(percent*100)+'%');
			};
		span.on('mousedown', sliderStart).on('mouseup', sliderEnd);
		input.on('keyup', sliderInputChange);
		parent.on('mouseup', sliderEnd);
	});
	$('.toolbar .group ul.animation .btns a').click(function(){
		if($(this).index()===0){
			getControl().css('animation', '');
			setTimeout(changeEffect, 10);
		}else{
			appendAnimation();
		}
		return false;
	});
	let animationUl = $('.toolbar .group ul.animation');
	animationUl.on('click', 'h6 span', function(){
		$(this).parent().parent().remove();
		getControl().css('animation', '');
		setTimeout(changeEffect, 10);
		return false;
	});
	animationUl.on('change', '.animations', function(){
		let _this = $(this), arr = _this.selected().attr('arr');
		if(!!arr){
			_this.parent().next().removeClass('hidden');
			$('.toolbar .group ul.animation .directions').attr('arr', arr);
		}else{
			_this.parent().next().addClass('hidden');
			$('.toolbar .group ul.animation .directions').removeAttr('arr');
		}
		changeEffect();
		return false;
	});
	animationUl.on('change', '.directions', function(){
		changeEffect();
		return false;
	});
}
function linkareaInit(){
	$('.phone').after('<div class="linkarea">\
		<div><span><em></em><i></i></span><a href="/scene/link" class="iframe-layer" width="600"></a></div>\
		<div><span></span><em></em></div>\
	</div>');
	let el = null, linkarea = $('.linkarea').css('left', -99999);
	$('.linkarea > div:first em').on('click', function(){
		$('.linkarea > div:first').addClass('hidden').siblings().removeClass('hidden');
		el = getControl();
		el.removeAttr('link').find('em').remove();
		let degrees = getTransform(el[0]).angle;
		let rotateSize = getRotateSize(degrees, el.width(), el.height());
		let rotatedWidth = rotateSize.width, rotatedHeight = rotateSize.height;
		let elOffset = el.offset(), elRight = elOffset.left + rotatedWidth, container = $('.container'), menuHeight = $('.scene-menu').height();
		linkarea.css({
			left: elRight,
			top: elOffset.top + container.scrollTop() - menuHeight,
			width: $('.parent').width() - elRight + 5,
			height: rotatedHeight
		});
		let em = $('.linkarea > div:last em'), span = $('.linkarea > div:last span');
		em.css({left:20, top:rotatedHeight/2-em.outerHeight(false)/2});
		em.attr({'origin-left':em.position().left, 'origin-top':em.position().top});
		span.css({
			top: rotatedHeight/2 - 1,
			width: 20 + em.outerWidth(false)/2,
			'-webkit-transform': 'rotate(0deg)',
			transform: 'rotate(0deg)'
		});
	});
	let span = $('.linkarea > div:last span'), controlMaxX = 0, controlCenterY = 0;
	$('.linkarea > div:last em').drag({
		start: function(){
			el = getControl();
			let degrees = getTransform(el[0]).angle;
			let rotateSize = getRotateSize(degrees, el.width(), el.height());
			let rotatedWidth = rotateSize.width, rotatedHeight = rotateSize.height;
			controlMaxX = el.offset().left + rotatedWidth;
			controlCenterY = rotatedHeight/2 + el.offset().top;
			this.parent().addClass('this');
		},
		move: function(e, d){
			let x = $.touches(e).x, y = $.touches(e).y;
			let width = d.left + this.outerWidth(false)/2, height = controlCenterY<y ? y - controlCenterY : controlCenterY - y;
			span.css('width', Math.sqrt(Math.pow(width, 2) + Math.pow(height, 2)));
			let deg = Math.asin(height/span.outerWidth(false)) * 180 / Math.PI + (controlMaxX<x?0:180);
			if(controlCenterY>=y)deg = -deg;
			if(controlMaxX>=x)deg = -deg;
			span.css({'-webkit-transform':'rotate('+deg+'deg)', transform:'rotate('+deg+'deg)'});
			$('.pages li').each(function(){
				let li = $(this), offset = li.offset(), width = li.width(), height = li.height();
				if(offset.left<=x && x<=offset.left+width && offset.top<=y && y<=offset.top+height){
					li.addClass('selected');
				}else{
					li.removeClass('selected');
				}
			});
		},
		stop: function(e, d){
			this.parent().removeClass('this');
			this.css({
				left: Number(this.attr('origin-left')),
				top: Number(this.attr('origin-top'))
			});
			span.css('height', '').css({
				width: Number(this.attr('origin-left')) + this.outerWidth(false)/2,
				transform: 'rotate(0deg)'
			});
			let li = $('.pages li.selected');
			if(li.length){
				let degrees = getTransform(el[0]).angle;
				let rotateSize = getRotateSize(degrees, el.width(), el.height());
				let rotatedWidth = rotateSize.width, rotatedHeight = rotateSize.height;
				let elOffset = el.offset(), elRight = elOffset.left + rotatedWidth, container = $('.container'), menuHeight = $('.scene-menu').height();
				linkarea.css({
					left: elRight,
					top: elOffset.top + container.scrollTop() - menuHeight,
					width: $('.parent').width() - elRight + 5,
					height: rotatedHeight/2
				});
				$('.linkarea > div:first').removeClass('hidden').siblings().addClass('hidden');
				let liOffset = li.offset(), index = li.index();
				let elCenter = elOffset.top + rotatedHeight/2, liCenter = liOffset.top + li.height()/2;
				if(elCenter>liCenter){
					linkarea.css({top:liCenter+container.scrollTop()-menuHeight, height:elCenter-liCenter});
					linkarea.find('span').addClass('reverse');
				}else{
					linkarea.css({top:elCenter+container.scrollTop()-menuHeight, height:liCenter-elCenter});
					linkarea.find('span').removeClass('reverse');
				}
				let width = linkarea.width(), height = linkarea.height(), _span = $('.linkarea > div:first span');
				_span.css('width', Math.sqrt(Math.pow(width, 2) + Math.pow(height, 2)));
				let deg = Math.asin(height/_span.outerWidth(false)) * 180 / Math.PI;
				if(elCenter>=liCenter)deg = -deg;
				_span.css({'-webkit-transform':'rotate('+deg+'deg)', transform:'rotate('+deg+'deg)'});
				el.prepend('<em></em>').attr('link', 'page:'+index);
			}
			$('.pages li').removeClass('selected');
		}
	});
	setTimeout(function(){
		linkarea.addClass('hidden');
	}, 10);
	$('.container').on('scroll', function(){
		el = getControl();
		if(!el)return true;
		let link = el.attr('link'), linkarea = $('.linkarea');
		if((!!!link || link.indexOf('page:')>-1) && !linkarea.hasClass('hidden')){
			let pageIndex = !!link ? Number(link.substr(5)) : Number('-');
			if(isNaN(pageIndex))return true;
			let degrees = getTransform(el[0]).angle;
			let rotateSize = getRotateSize(degrees, el.width(), el.height());
			let rotatedWidth = rotateSize.width, rotatedHeight = rotateSize.height;
			let elOffset = el.offset(), elRight = elOffset.left + rotatedWidth, container = $('.container'), menuHeight = $('.scene-menu').height();
			linkarea.css({
				left: elRight,
				top: elOffset.top + container.scrollTop() - menuHeight,
				width: $('.parent').width() - elRight + 5,
				height: rotatedHeight/2
			});
			let li = $('.pages ul li').eq(pageIndex), liOffset = li.offset();
			let elCenter = elOffset.top + rotatedHeight/2, liCenter = liOffset.top + li.height()/2;
			if(elCenter>liCenter){
				linkarea.css({top:liCenter+container.scrollTop()-menuHeight, height:elCenter-liCenter});
				linkarea.find('span').addClass('reverse');
			}else{
				linkarea.css({top:elCenter+container.scrollTop()-menuHeight, height:liCenter-elCenter});
				linkarea.find('span').removeClass('reverse');
			}
			let width = linkarea.width(), height = linkarea.height(), span = $('.linkarea > div:first span');
			span.css('width', Math.sqrt(Math.pow(width, 2) + Math.pow(height, 2)));
			let deg = Math.asin(height/span.outerWidth(false)) * 180 / Math.PI;
			if(elCenter>=liCenter)deg = -deg;
			span.css({'-webkit-transform':'rotate('+deg+'deg)', transform:'rotate('+deg+'deg)'});
		}
	});
}
function pageInit(){
	$.getJSON('/scene/getPages', {id:$('#id').val()}, function(json){
		if(json.data.length===1)$('.pages a.del').addClass('hidden');
		$.each(json.data, function(){
			pageAdd(null, this, true);
		});
		$('.pages li:first').addClass('this').siblings().removeClass('this');
		pageSelect();
		pageTitle();
		pageSort();
		sceneSet();
		setTimeout(function(){
			window.sceneInitd = true;
		}, 1000);
	});
}
function contextmenuOperate(elementType){
	let viewer = $('.viewer'), toolbar = $('.toolbar'), control = window.contextmenuControl;
	if(!control || !control.length)return;
	switch(elementType){
		case 'top':
			viewer.append(control);
			break;
		case 'up':
			if(control.next().length)control.next().after(control);
			break;
		case 'down':
			if(!control.prev().is('section'))control.prev().before(control);
			break;
		case 'bottom':
			viewer.find('section').after(control);
			break;
		case 'delete':
			control.remove();
			$('.linkarea').addClass('hidden');
			break;
		case 'lock':
			if(control.hasClass('lock')){
				control.removeClass('lock');
				toolbar.css({left:-99999, top:-99999, width:''}).removeClass('hidden');
				let wrapper = $('.wrapper'),
					left = wrapper.parent().parent().position().left + wrapper.parent().position().left + wrapper.position().left + control.position().left,
					top = wrapper.parent().parent().position().top + wrapper.parent().position().top + wrapper.position().top + control.position().top;
				if(control.attr('type')==='text'){
					toolbar.width(toolbar.width());
					setTimeout(function(){
						toolbar.css({
							left: ((Number(left)+control.outerWidth(false)+Number(left)) - toolbar.outerWidth(false)) / 2,
							top: Number(top) - 13 - toolbar.outerHeight(false)
						});
					}, 10);
				}else{
					setTimeout(function(){
						toolbar.css({
							left: Number(left) + control.outerWidth(false) + 20,
							top: Number(top)
						});
					}, 10);
				}
			}else{
				control.addClass('lock');
				toolbar.addClass('hidden');
			}
			break;
		case 'cut':
			window.sceneElementClone = control.clone();
			window.sceneElementClone.find('.resize-panel').css({'z-index':'', display:'none'});
			control.remove();
			break;
		case 'copy':
			window.sceneElementClone = control.clone();
			window.sceneElementClone.find('.resize-panel').css({'z-index':'', display:'none'});
			break;
		case 'paste':
		case 'paste-mouse':
			window.sceneElementClone = control.clone();
			if(window.sceneElementClone){
				let clone = window.sceneElementClone, elementClone = clone.clone();
				if(elementType==='paste-mouse')elementClone.css({left:window.sceneMouseX, top:window.sceneMouseY});
				let style = elementClone.attr('style')||'', link = window.sceneElementClone.attr('link'), type = '', value = '';
				elementClone.remove();
				if(!!link){
					let arr = link.split(':');
					type = arr[0];
					value = link.replace(/^(web|page):/, '');
				}
				if(clone.attr('type')==='image'){
					setPic({style:style, link_type:type, link_value:value, url:clone.attr('url')});
				}else if(clone.attr('type')==='text'){
					setText({style:style, link_type:type, link_value:value, text:clone.find('font').html()});
				}else if(clone.attr('type')==='video'){
					setVideo({style:style, code:clone.find('textarea').val()});
				}else if(clone.attr('type')==='web'){
					setWeb({style:style, text:clone.attr('url')});
				}else if(clone.attr('type')==='map'){
					setMap({style:style, longitude:clone.attr('longitude'), latitude:clone.attr('latitude'), zoom:clone.attr('zoom')});
				}else if(clone.attr('type')==='chart'){
					setChart({style:style, ctype:clone.attr('chart-type'), data:clone.attr('chart-data')});
				}
				clone.remove();
			}
			break;
	}
	if(elementType!=='lock')toolbar.addClass('hidden');
	$('.contextmenu').addClass('hidden');
}
function selectAreaStart(e){
	if($('body').hasClass('scene-preview'))return false;
	resetPageTitle();
	let o = e.target;
	if(e.button===2)return true;
	do{
		if($(o).is('.item, .toolbar, .linkarea, .contextmenu, .music'))return true;
		o = o.parentNode;
	}while(o.parentNode);
	$('.contextmenu').addClass('hidden');
	let selectarea = $('.selectarea'), touches = $.touches(e), sceneTop = $('.scene').position().top, scrollTop = $('.container').scrollTop();
	window.selectAreaLastX = touches.x;
	window.selectAreaLastY = (touches.y - sceneTop) + scrollTop;
	selectarea.removeClass('hidden').css({left:window.selectAreaLastX, top:window.selectAreaLastY, width:0, height:0});
	$('.scene .parent').on('mousemove', selectAreaMove).on('mouseup', selectAreaEnd).on('mouseleave', selectAreaEnd);
	return true;
}
function selectAreaMove(e){
	let selectarea = $('.selectarea'), touches = $.touches(e), sceneTop = $('.scene').position().top, scrollTop = $('.container').scrollTop(),
		width = touches.x - window.selectAreaLastX, height = (touches.y - sceneTop) + scrollTop - window.selectAreaLastY;
	if(width>0 && height>0){
		selectarea.css({width:width, height:height});
	}else{
		selectarea.css({width:Math.abs(width), height:Math.abs(height)});
		if(width<0)selectarea.css('left', touches.x);
		if(height<0)selectarea.css('top', (touches.y - sceneTop) + scrollTop);
	}
	return true;
}
function selectAreaEnd(e){
	let selectarea = $('.selectarea'),
		collision = function(a, b){
			let ax = a.offset().left, ay = a.offset().top, aw = a.outerWidth(false), ah = a.outerHeight(false),
				bx = b.offset().left, by = b.offset().top, bw = b.outerWidth(false), bh = b.outerHeight(false);
			let leftTop = (bx>ax && bx<ax+aw && by>ay && by<ay+ah),
				rightTop = (bx+bw>ax && bx+bw<ax+aw && by>ay && by<ay+ah),
				leftBottom = (bx>ax && bx<ax+aw && by+bh>ay && by+bh<ay+ah),
				rightBottom = (bx+bw>ax && bx+bw<ax+aw && by+bh>ay && by+bh<ay+ah);
			return (leftTop || rightTop || leftBottom || rightBottom);
		};
	$('.scene .parent').off('mousemove', selectAreaMove).off('mouseup', selectAreaEnd).off('mouseleave', selectAreaEnd);
	$('.viewer .item:not(.lock)').each(function(){
		let div = $(this);
		if(collision(selectarea, div) || collision(div, selectarea)){
			setTimeout(function(){div.trigger('click')}, 0);
			return false;
		}
	});
	selectarea.addClass('hidden');
	return true;
}
function changeEffect(){
	let control = getControl(), animations = '';
	$('.toolbar .group ul.animation li').each(function(){
		let row = $(this).find('.row');
		if(!row.eq(0).find('select').selected().val().length)return true;
		let name = '', time = row.eq(2).find('input').val(), delay = row.eq(3).find('input').val(), count = row.eq(4).find('input').val(),
			arr = row.eq(0).find('select').selected().attr('arr');
		if(!!arr){
			let effects = arr.split(',');
			name = effects[Number(row.eq(1).find('select').selected().val())];
		}else{
			name = row.eq(0).find('select').selected().val();
		}
		animations += name+' '+time+'s ease '+delay+'s '+count+', '; //animation: 名称 花费时间 曲线函数 延迟 播放次数;
	});
	control.css('animation', animations.replace(/, $/, ''));
}
function appendAnimation(name, time, delay, count){
	if(typeof name === 'undefined')name = '';
	if(typeof time === 'undefined')time = 2;
	if(typeof delay === 'undefined')delay = 0;
	if(typeof count === 'undefined')count = 1;
	let li = $('<li>\
		<h6><span class="eqf-wrong"></span>动画组</h6>\
		<div class="row jt">\
			<span>方式</span>\
			<select class="animations">\
			<option value="">无</option>\
			<optgroup label="进入">\
			<option value="fadeIn">淡入</option>\
			<option value="fadeInLeft" arr="fadeInLeft,fadeInDown,fadeInRight,fadeInUp">移入</option>\
			<option value="bounceInLeft" arr="bounceInLeft,bounceInDown,bounceInRight,bounceInUp">弹入</option>\
			<option value="flipInY">翻转进入</option>\
			<option value="bounceIn">中心弹入</option>\
			<option value="zoomIn">中心放大</option>\
			<option value="rollIn">翻滚进入</option>\
			<option value="flipInX">翻开进入</option>\
			<option value="lightSpeedIn">光速进入</option>\
			<option value="twisterInDown">魔幻进入</option>\
			<option value="puffIn">缩小进入</option>\
			<option value="twisterInUp">旋转进入</option>\
			</optgroup>\
			<optgroup label="强调">\
			<option value="wobble">摇摆</option>\
			<option value="rubberBand">抖动</option>\
			<option value="rotateIn">旋转</option>\
			<option value="flip">翻转</option>\
			<option value="swing">悬摆</option>\
			<option value="flash">闪烁</option>\
			<option value="slideDown">下滑</option>\
			<option value="slideUp">上滑</option>\
			<option value="tada">放大抖动</option>\
			<option value="jello">倾斜摆动</option>\
			</optgroup>\
			<optgroup label="退出">\
			<option value="fadeOut">淡出</option>\
			<option value="fadeOutRight" arr="fadeOutRight,fadeOutDown,fadeOutLeft,fadeOutUp">移出</option>\
			<option value="bounceOutRight" arr="bounceOutRight,bounceOutDown,bounceOutLeft,bounceOutUp">弹出</option>\
			<option value="flipOutY">翻转消失</option>\
			<option value="bounceOut">中心消失</option>\
			<option value="zoomOut">中心缩小</option>\
			<option value="rollOut">翻滚退出</option>\
			<option value="flipOutX">翻开消失</option>\
			<option value="lightSpeedOut">光速退出</option>\
			<option value="puffOut">放大退出</option>\
			</optgroup>\
			</select>\
		</div>\
		<div class="row jt hidden">\
			<span>方向</span>\
			<select class="directions">\
			<option value="0">从左向右</option>\
			<option value="1">从上到下</option>\
			<option value="2">从右向左</option>\
			<option value="3">从下到上</option>\
			</select>\
		</div>\
		<div class="row"><strong>秒</strong><span>时长</span><input type="number" step="0.1" min="0" max="20" value="2" /></div>\
		<div class="row"><strong>秒</strong><span>延时</span><input type="number" step="0.1" min="0" max="20" value="0" /></div>\
		<div class="row"><strong>次</strong><span>重复</span><input type="number" min="1" max="10" value="1" /></div>\
	</li>');
	let btns = $('.toolbar .group ul.animation .btns');
	btns.before(li);
	setTimeout(function(){
		let row = li.find('.row');
		if(name.length){
			if(/^(fadeIn|bounceIn|fadeOut|bounceOut)\w+$/.test(name)){
				let option = row.eq(0).find('select option[arr*="'+name+'"]');
				row.eq(0).find('select').selected(row.eq(0).find('select option').index(option), false);
			}else{
				row.eq(0).find('select').selected(name, false);
			}
			setTimeout(function(){
				let arr = row.eq(0).find('select').selected().attr('arr');
				if(!!arr){
					let effects = arr.split(','), direction = effects.indexOf(name);
					row.eq(1).removeClass('hidden').find('select').attr('arr', arr).selected(direction+'', false);
				}else{
					row.eq(1).addClass('hidden');
				}
			}, 10);
		}
		row.eq(2).find('input').val(time);
		row.eq(3).find('input').val(delay);
		row.eq(4).find('input').val(count);
	}, 10);
}
function showSet(index){
	let layer = $('.set-layer'), menu = $('.scene-menu > .menu'), cover = $('#cover'), music_name = $('#music_name');
	if(layer.hasClass('hidden')){
		layer.removeClass('hidden');
		menu.addClass('disable').data('disableTips', true);
		setTimeout(function(){layer.addClass('set-layer-in')}, 50);
		if(cover.val().length){
			$('.set .share-info a').css('background-image', 'url('+cover.val()+')');
		}else{
			$('.set .share-info a').css('background-image', '');
		}
		if(music_name.val().length){
			$('.set .music-info .input span').html(music_name.val());
			$('.set .music-info .input em').removeAttr('class').addClass('eqf-wrong2');
		}else{
			$('.set .music-info .input span').html('未添加');
			$('.set .music-info .input em').removeAttr('class').addClass('eqf-top-music');
		}
		if(typeof index === 'number'){
			$('.set .nav a').removeClass('this').eq(index).addClass('this');
			$('.set .view li').addClass('hidden').eq(index).removeClass('hidden');
		}
	}else{
		layer.removeClass('set-layer-in');
		menu.removeClass('disable').removeData('disableTips');
		setTimeout(function(){layer.addClass('hidden')}, 400);
	}
}
function getControl(){
	let control = $('.viewer:last .control');
	return control.length ? control : null;
}
function getPages(){
	return $('.pages').param({filter:false});
}
function getLink(){
	let control = getControl(), type = 'web', value = 'http://';
	if(control){
		let link = control.attr('link');
		if(!!link){
			let arr = link.split(':');
			type = arr[0];
			value = link.replace(/^(web|page):/, '');
		}
	}
	return {type:type, value:value};
}
function getPic(){
	return getControl();
}
function getVideo(){
	let control = getControl();
	if(!control)return '';
	return control.find('textarea').val();
}
function getMap(){
	let control = getControl();
	if(!control)return {longitude:'113.440685', latitude:'23.136588', zoom:18};
	return {longitude:control.attr('longitude'), latitude:control.attr('latitude'), zoom:control.attr('zoom')};
}
function getChart(){
	let control = getControl();
	if(!control)return {type:'bar', data:''};
	return {type:control.attr('chart-type'), data:control.attr('chart-data')};
}
function pageSelect(){
	$('.pages li').each(function(){
		let _this = $(this);
		if(!!_this.data('select'))return true;
		_this.data('select', true).tapper(function(e){
			let o = e.target;
			do{
				if($(o).is('label, span, input'))return false;
				if($(o).is('.this')){
					resetPageTitle();
					return false;
				}
				o = o.parentNode;
			}while(o.parentNode);
			resetPageTitle();
			let _this = $(this), viewer = $('.viewer'), index = _this.index();
			if(viewer.attr('page')===index)return false;
			if(!$('.boxer > .loading').hasClass('hidden')){
				showToast('请先等待图片加载完成');
				return false;
			}
			viewer.attr('page', index);
			setContent();
			setTimeout(function(){
				$('.pages li.this').removeClass('this');
				_this.addClass('this');
				sceneSet();
			}, 300);
		});
	});
}
function pageTitle(){
	$('.pages li span').each(function(){
		if(!!$(this).data('tapper'))return true;
		$(this).data('tapper', true).tapper(function(){
			resetPageTitle();
			let input = $(this).next();
			if(input.hasClass('hidden')){
				input.removeClass('hidden').focus();
			}else{
				input.addClass('hidden');
			}
			return false;
		}).next().onkey(function(code){
			if(code!==13 && code!==27)return true;
			$(this).addClass('hidden');
			if(code===13){
				$(this).prev().html($(this).val());
			}else if(code===27){
				$(this).val($(this).prev().html());
			}
			if(window.sceneInitd)window.sceneSaved = false;
		});
	});
}
function pageSort(){
	$('.pages ul').dragsort({
		releaseTarget: null,
		opacity: 1,
		lockX: true,
		lockRange: true,
		autoCursor: false,
		placeholder: '<div></div>',
		dragItemExcept: function(e, o){
			if(o.is('input, textarea, select, a[href], span'))return true;
			if(o.parent().is('label'))return true;
		},
		start: function(){
			resetPageTitle();
			let control = getControl();
			if(control)control.ZResizeHidden();
		},
		after: function(){
			pageReset();
			if(window.sceneInitd)window.sceneSaved = false;
		}
	});
}
function pageAdd(e, data, nonMenu){
	let ul = $('.pages ul'), length = ul.find('li').removeClass('this').length, title = '新页面', bg = '', content = '', status = true;
	if(typeof data !== 'undefined'){
		title = data.title;
		bg = data.bg;
		content = data.content;
		status = Number(data.status) === 1;
	}
	let li = '<li class="this">\
		<div>\
			<label class="checkbox tips" title="在场景中显示" tips-follow="left"><input type="checkbox" name="status" value="1" '+(status?'checked':'')+' /><div><span></span></div></label>\
			<font><em>'+(length+1)+'</em></font>\
			<span>'+title+'</span>\
			<input type="text" class="title tips hidden" tips-follow="auto" name="title" value="'+title+'" title="回车确定，esc取消" />\
		</div>\
		<input type="hidden" class="bg" name="bg" value="'+bg+'" />\
		<input type="hidden" class="sort" name="sort" value="'+length+'" />\
		<textarea class="content hidden" name="content">'+content+'</textarea>\
	</li>';
	setTips(ul.append(li).find('.tips'));
	if(ul.find('li').length>1)$('.pages .con .del').removeClass('hidden');
	if(typeof nonMenu === 'undefined'){
		pageSelect();
		pageTitle();
		pageSort();
		sceneSet();
	}
	if(window.sceneInitd)window.sceneSaved = false;
}
function pageReplace(bg, content){
	let li = $('.pages ul li.this');
	li.find('.bg').val(bg);
	li.find('.content').val(content);
	sceneSet();
	if(window.sceneInitd)window.sceneSaved = false;
}
function pageCopy(e){
	setContent();
	let page = $('.pages li.this');
	pageAdd(e, {title:'副本-'+page.find('.title').val(), bg:page.find('.bg').val(), content:page.find('.content').val()});
	if(window.sceneInitd)window.sceneSaved = false;
}
function pageDel(){
	if($('.pages ul li').length<=1)return;
	if(!confirm('确定要删除该场景页？'))return;
	window.sceneSaved = false;
	$('.pages ul li.this').remove();
	$('.pages ul li:eq(0)').addClass('this');
	if($('.pages ul li').length<=1)$('.pages .con .del').addClass('hidden');
	pageReset();
	sceneSet();
}
function pageTemplate(){
	setContent();
	let page = $('.pages li.this'), bg = page.find('.bg').val(), content = page.find('.content').val();
	$.postJSON('/scene/addTemplate', { bg:bg, content:content }, function(){
		showToast('模板保存成功');
	});
}
function setTemplate(obj){
	pageReplace(obj.bg, obj.content);
}
function pageReset(){
	$('.pages ul li').each(function(){
		let _this = $(this), index = $('.pages ul li').index(_this);
		_this.find('em').html(index+1);
		_this.find('.sort').val(index);
	});
}
function resetPageTitle(){
	$('.pages .title').each(function(){
		$(this).addClass('hidden').val($(this).prev().html());
	});
}
function getTransform(el){
	let st = window.getComputedStyle(el, null), matrix = st.getPropertyValue('-webkit-transform') || st.getPropertyValue('transform');
	if(!matrix || matrix==='none')return {scale:1, angle:0};
	let values = matrix.split('(')[1].split(')')[0].split(','),
		a = Number(values[0]), b = Number(values[1]), c = Number(values[2]), d = Number(values[3]),
		scale = Math.ceil(Math.sqrt(a * a + b * b)),
		angle = Math.round(Math.atan2(b, a) * (180 / Math.PI));
	return {scale:scale, angle:angle};
}
function getRotateSize(degrees, width, height){
	let angle = degrees * Math.PI / 180, sin = Math.sin(angle), cos = Math.cos(angle);
	let x1 = cos * width, y1 = sin * width;
	let x2 = -sin * height, y2 = cos * height;
	let x3 = cos * width - sin * height, y3 = sin * width + cos * height;
	let minX = Math.min(0, x1, x2, x3),
		maxX = Math.max(0, x1, x2, x3),
		minY = Math.min(0, y1, y2, y3),
		maxY = Math.max(0, y1, y2, y3);
	let rotatedWidth = maxX - minX, rotatedHeight = maxY - minY;
	return {width:rotatedWidth, height:rotatedHeight};
}
function setBg(obj){
	let bg = $('.pages li.this .bg');
	if(typeof obj==='string'){
		bg.val(obj);
		$('.viewer').css({'background-color':obj, 'background-image':''});
		return;
	}
	$('.boxer > .loading').removeClass('hidden');
	bg.val(obj.url);
	let image = new Image();
	image.src = obj.url;
	image.onload = function(){
		$('.boxer > .loading').addClass('hidden');
		$('.viewer').css({'background-color':'', 'background-image':'url('+obj.url+')'});
	};
	if(window.sceneInitd)window.sceneSaved = false;
}
function setCover(obj){
	$('#cover').val(obj.url);
	$('.set .share-info a').css('background-image', 'url('+obj.url+')');
	if(window.sceneInitd)window.sceneSaved = false;
}
function setMusicPic(obj){
	$('#music_pic').val(obj.url);
	$('.boxer > .music').css('background-image', 'url('+obj.url+')');
	$('.set .music-button').attr('data-src', obj.url);
	if(window.sceneInitd)window.sceneSaved = false;
}
function setMusic(obj){
	$('#music').val(obj.url);
	$('#music_name').val(obj.title);
	$('#audio').attr('src', obj.url);
	$('.boxer > .music').removeClass('hidden');
	let music = $('.clear-music').removeClass('hidden').prev();
	music.find('span').html(obj.title);
	music.find('em').removeAttr('class').addClass('eqf-wrong2');
	if(window.sceneInitd)window.sceneSaved = false;
}
function setLink(type, value){
	getControl().attr('link', type+':'+value).prepend('<em></em>');
	if(window.sceneInitd)window.sceneSaved = false;
}
function setPic(obj, nonMenu, nonLoadImage){
	if($(this).hasClass('disable'))return false;
	let viewer = $('.viewer:last'), link = '', width = viewer.width(), image = new Image(),
		style = (typeof obj.style === 'undefined' ? '' : obj.style),
		lock = (typeof obj.lock === 'undefined' ? 0 : Number(obj.lock)),
		fulladaption = (typeof obj.fulladaption === 'undefined' ? '' : ' fulladaption="1"'),
		url = (typeof obj.url === 'undefined' ? '' : obj.url);
	$('.boxer > .loading').removeClass('hidden');
	if(!url.length)return;
	image.src = url;
	let s = function(){
		$('.boxer > .loading').addClass('hidden');
		let imageWidth = image.width, imageHeight = image.height;
		if(imageWidth > width){
			imageHeight = (imageHeight * width) / imageWidth;
			imageWidth = width;
		}
		if(typeof obj.control!=='undefined' && obj.control){
			let wrapper = $('.wrapper'), parent = wrapper.parent(),
				left = parent.parent().position().left + parent.position().left + wrapper.position().left + Number(obj.control.css('left').replace('px', ''));
			obj.control.css({width:imageWidth, height:imageHeight}).attr('url', url).find('img').attr('src', url);
			$('.toolbar').css('left', left + imageWidth + 20);
			return;
		}
		if(!style.length)style = 'left:0;top:0;width:'+imageWidth+'px;height:'+imageHeight+'px;';
		if(typeof obj.link_type!=='undefined' && /^(web|page)$/.test(obj.link_type))link = ' link="'+obj.link_type+':'+obj.link_value+'"';
		let div = $('<div class="item '+(lock===1?'lock':'')+'" type="image" title="按住鼠标进行拖动，双击鼠标更换图片" style="'+style+'" '+fulladaption+' '+link+' url="'+url+'">'+(link.length?'<em></em>':'')+'<img src="'+url+'" /></div>');
		viewer.append(div);
		if(style.indexOf('border-radius')>-1)div.find('img').css('border-radius', div.css('border-radius'));
		if(style.indexOf('opacity')>-1){
			let matcher = style.match(/opacity:\s*([\d.]+);/);
			div.find('img').css('opacity', matcher[1]);
		}
		div.ZResize({
			restrict: true,
			isRotate: true,
			suck: 2,
			unusualGuide: 'section, em',
			skipShow: function(e){
				if($('body').hasClass('scene-preview'))return false;
				let contextmenu = $('.contextmenu');
				if(!!contextmenu.attr('target'))contextmenu.addClass('hidden');
				let o = e.target;
				do{
					if($(o).is('.control')){
						if($(o).find('.resize-panel').css('display')==='block'){
							contextmenu.addClass('hidden');
							if(!$(o).hasClass('lock'))$('.toolbar').removeClass('hidden');
						}
						return false;
					}
					if((/^(html|body)$/i).test(o.tagName))return true;
					o = o.parentNode;
				}while(o.parentNode);
			},
			skipHide: function(e){
				let o = e.target;
				do{
					if($(o).is('.toolbar') || $(o).is('.linkarea div em') || $(o).is('.linkarea div a') || $(o).is('.load-overlay')){
						$('.contextmenu').addClass('hidden');
						return false;
					}
					if($(o).is('.contextmenu'))return false;
					if((/^(html|body)$/i).test(o.tagName)){
						$('.contextmenu').addClass('hidden');
						return true;
					}
					o = o.parentNode;
				}while(o.parentNode);
			},
			skipOperate: function(e){
				return (this.hasClass('lock') || !!this.attr('fulladaption'));
			},
			show: function(position, e){
				let el = this.addClass('control'), style = el.attr('style'),
					toolbar = $('.toolbar').css({left:-99999, top:-99999, width:''}), wrapper = $('.wrapper'),
					left = wrapper.parent().parent().position().left + wrapper.parent().position().left + wrapper.position().left + position.left,
					top = wrapper.parent().parent().position().top + wrapper.parent().position().top + wrapper.position().top + position.top;
				if(!this.hasClass('lock')){
					toolbar.removeClass('hidden');
				}else{
					toolbar.addClass('hidden');
				}
				if(typeof e.button === 'undefined'){
					toolbar.addClass('hidden');
				}else{
					$('.contextmenu').addClass('hidden');
				}
				let link = el.attr('link'), linkarea = $('.linkarea').addClass('hidden');
				if(!!!link || link.indexOf('page:')>-1){
					let degrees = getTransform(el[0]).angle;
					let rotateSize = getRotateSize(degrees, el.width(), el.height());
					let rotatedWidth = rotateSize.width, rotatedHeight = rotateSize.height;
					let elOffset = el.offset(), elRight = elOffset.left + rotatedWidth, container = $('.container'), menuHeight = $('.scene-menu').height(),
						pageIndex = !!link ? Number(link.substr(5)) : Number('-'), isLink = true;
					if(isNaN(pageIndex))isLink = false;
					linkarea.css({
						left: elRight,
						top: elOffset.top + container.scrollTop() - menuHeight,
						width: Math.abs($('.parent').width()-elRight+5),
						height: isLink ? rotatedHeight/2 : rotatedHeight
					}).removeClass('hidden');
					if(isLink){
						$('.linkarea > div:first').removeClass('hidden').siblings().addClass('hidden');
						let li = $('.pages ul li').eq(pageIndex), liOffset = li.offset();
						let elCenter = elOffset.top + rotatedHeight/2, elMaxX = elOffset.left + rotatedWidth,
							liCenter = liOffset.top + li.height()/2, liLeft = liOffset.left;
						if(elCenter>liCenter){
							linkarea.css({top:liCenter+container.scrollTop()-menuHeight, height:elCenter-liCenter});
							linkarea.find('span').addClass('reverse');
						}else{
							linkarea.css({top:elCenter+container.scrollTop()-menuHeight, height:liCenter-elCenter});
							linkarea.find('span').removeClass('reverse');
						}
						let width = linkarea.width(), height = linkarea.height(), span = $('.linkarea > div:first span');
						span.css('width', Math.sqrt(Math.pow(width, 2) + Math.pow(height, 2)));
						let deg = Math.asin(height/span.outerWidth(false)) * 180 / Math.PI + (elMaxX<liLeft?0:180);
						if(elCenter>=liCenter)deg = -deg;
						if(elMaxX>=liLeft){
							deg = -deg;
							linkarea.addClass('hidden');
						}else{
							linkarea.removeClass('hidden');
						}
						span.css({'-webkit-transform':'rotate('+deg+'deg)', transform:'rotate('+deg+'deg)'});
					}else{
						$('.linkarea > div:last').removeClass('hidden').siblings().addClass('hidden');
						let em = $('.linkarea > div:last em'), span = $('.linkarea > div:last span');
						em.css({left:20, top:rotatedHeight/2-em.outerHeight(false)/2});
						em.attr({'origin-left':em.position().left, 'origin-top':em.position().top});
						span.css({
							top: rotatedHeight/2 - 1,
							width: 20 + em.outerWidth(false)/2,
							'-webkit-transform': 'rotate(0deg)',
							transform: 'rotate(0deg)'
						});
					}
				}
				toolbar.find('.hidden').removeClass('hidden');
				toolbar.find('.picHidden').addClass('hidden');
				el.siblings().removeClass('control');
				toolbar.width(toolbar.width());
				setTimeout(function(){
					toolbar.css({
						left: Number(left) + el.outerWidth(false) + 20,
						top: Number(top)
					});
				}, 10);
				if(style.indexOf('animation:')>-1){
					$('.toolbar .group ul.animation li').remove();
					let names = el.css('animation-name').split(','),
						times = el.css('animation-duration').split(','),
						delays = el.css('animation-delay').split(','),
						counts = el.css('animation-iteration-count').split(',');
					for(let i=0; i<names.length; i++){
						appendAnimation($.trim(names[i]), $.trim(times[i]).replace('s',''), $.trim(delays[i]).replace('s',''), $.trim(counts[i]));
					}
				}else{
					$('.toolbar .group ul.animation li').remove();
				}
			},
			move: function(position){
				if(this.hasClass('lock'))return;
				let el = this, toolbar = $('.toolbar'), wrapper = $('.wrapper'),
					left = wrapper.parent().parent().position().left + wrapper.parent().position().left + wrapper.position().left + position.left,
					top = wrapper.parent().parent().position().top + wrapper.parent().position().top + wrapper.position().top + position.top;
				toolbar.css({
					left: Number(left) + el.outerWidth(false) + 20,
					top: Number(top)
				}).find('.group.open').removeClass('open');
				$('.contextmenu').addClass('hidden');
				let link = el.attr('link'), linkarea = $('.linkarea');
				if(!!!link || link.indexOf('page:')>-1){
					let degrees = getTransform(el[0]).angle;
					let rotateSize = getRotateSize(degrees, el.width(), el.height());
					let rotatedWidth = rotateSize.width, rotatedHeight = rotateSize.height;
					let elOffset = el.offset(), elRight = elOffset.left + rotatedWidth, container = $('.container'), menuHeight = $('.scene-menu').height(),
						pageIndex = !!link ? Number(link.substr(5)) : Number('-'), isLink = true;
					if(isNaN(pageIndex))isLink = false;
					linkarea.css({
						left: elRight,
						top: elOffset.top + container.scrollTop() - menuHeight,
						width: Math.abs($('.parent').width()-elRight+5),
						height: isLink ? rotatedHeight/2 : rotatedHeight
					});
					if(isLink){
						let li = $('.pages ul li').eq(pageIndex), liOffset = li.offset();
						let elCenter = elOffset.top + rotatedHeight/2, elMaxX = elOffset.left + rotatedWidth,
							liCenter = liOffset.top + li.height()/2, liLeft = liOffset.left;
						if(elCenter>liCenter){
							linkarea.css({top:liCenter+container.scrollTop()-menuHeight, height:elCenter-liCenter});
							linkarea.find('span').addClass('reverse');
						}else{
							linkarea.css({top:elCenter+container.scrollTop()-menuHeight, height:liCenter-elCenter});
							linkarea.find('span').removeClass('reverse');
						}
						let width = linkarea.width(), height = linkarea.height(), span = $('.linkarea > div:first span');
						span.css('width', Math.sqrt(Math.pow(width, 2) + Math.pow(height, 2)));
						let deg = Math.asin(height/span.outerWidth(false)) * 180 / Math.PI + (elMaxX<liLeft?0:180);
						if(elCenter>=liCenter)deg = -deg;
						if(elMaxX>=liLeft){
							deg = -deg;
							linkarea.addClass('hidden');
						}else{
							linkarea.removeClass('hidden');
						}
						span.css({'-webkit-transform':'rotate('+deg+'deg)', transform:'rotate('+deg+'deg)'});
					}
				}
				setContent();
				if(window.sceneInitd)window.sceneSaved = false;
			},
			rotate: function(degrees){
				let el = this, link = this.attr('link'), linkarea = $('.linkarea');
				let rotateSize = getRotateSize(degrees, this.width(), this.height());
				let rotatedWidth = rotateSize.width, rotatedHeight = rotateSize.height;
				let elOffset = el.offset(), elRight = elOffset.left + rotatedWidth, container = $('.container'), menuHeight = $('.scene-menu').height(),
					pageIndex = !!link ? Number(link.substr(5)) : Number('-'), isLink = true;
				if(isNaN(pageIndex))isLink = false;
				linkarea.css({
					left: elRight,
					top: elOffset.top + container.scrollTop() - menuHeight,
					width: Math.abs($('.parent').width()-elRight+5),
					height: isLink ? rotatedHeight/2 : rotatedHeight
				});
				if(isLink){
					$('.linkarea > div:first').removeClass('hidden').siblings().addClass('hidden');
					let li = $('.pages ul li').eq(pageIndex), liOffset = li.offset();
					let elCenter = elOffset.top + rotatedHeight/2, elMaxX = elOffset.left + rotatedWidth,
						liCenter = liOffset.top + li.height()/2, liLeft = liOffset.left;
					if(elCenter>liCenter){
						linkarea.css({top:liCenter+container.scrollTop()-menuHeight, height:elCenter-liCenter});
						linkarea.find('span').addClass('reverse');
					}else{
						linkarea.css({top:elCenter+container.scrollTop()-menuHeight, height:liCenter-elCenter});
						linkarea.find('span').removeClass('reverse');
					}
					let width = linkarea.width(), height = linkarea.height(), span = $('.linkarea > div:first span');
					span.css('width', Math.sqrt(Math.pow(width, 2) + Math.pow(height, 2)));
					let deg = Math.asin(height/span.outerWidth(false)) * 180 / Math.PI + (elMaxX<liLeft?0:180);
					if(elCenter>=liCenter)deg = -deg;
					if(elMaxX>=liLeft){
						deg = -deg;
						linkarea.addClass('hidden');
					}else{
						linkarea.removeClass('hidden');
					}
					span.css({'-webkit-transform':'rotate('+deg+'deg)', transform:'rotate('+deg+'deg)'});
				}else{
					$('.linkarea > div:last').removeClass('hidden').siblings().addClass('hidden');
					let em = $('.linkarea > div:last em'), span = $('.linkarea > div:last span');
					em.css({left:20, top:rotatedHeight/2-em.outerHeight(false)/2});
					em.attr({'origin-left':em.position().left, 'origin-top':em.position().top});
					span.css({
						top: rotatedHeight/2 - 1,
						width: 20 + em.outerWidth(false)/2,
						'-webkit-transform': 'rotate(0deg)',
						transform: 'rotate(0deg)'
					});
				}
				setContent();
				if(window.sceneInitd)window.sceneSaved = false;
			},
			hide: function(){
				$('.toolbar').addClass('hidden');
				$('.linkarea').addClass('hidden');
				this.removeClass('control');
				this.find('.resize-panel').css('z-index', '');
			}
		}).tapper(function(){
			if(!$('body').hasClass('scene-preview') || !!!$(this).attr('link'))return true;
			let link = $(this).attr('link'), arr = link.split(':'), type = arr[0], value = link.replace(/^(web|page):/, '');
			if(type==='web'){
				window.open(value);
			}else{
				if(window.previewChangePaging)return false;
				if(window.previewPages.length<=1)return false;
				let pageIndex = Number(value), direction = -1;
				if(window.previewPageIndex > pageIndex)direction = 1;
				previewChangePage(direction, pageIndex);
				return false;
			}
		});
		div.on('dblclick', function(){
			if($('body').hasClass('scene-preview') || $(this).hasClass('lock'))return;
			iframeLayer('/scene/pic', 770);
		});
		if(typeof nonMenu === 'undefined'){
			setTimeout(function(){
				if($('body').hasClass('scene-preview'))return;
				div.trigger('click');
			}, 0);
		}
	};
	if(!nonLoadImage){
		image.onerror = function(){setPic(obj, nonMenu)};
		image.onload = function(){
			s();
		};
	}else{
		s();
	}
}
function setText(obj, nonMenu){
	if($(this).hasClass('disable'))return false;
	let viewer = $('.viewer:last'), link = '',
		style = (typeof obj.style === 'undefined' ? '' : obj.style),
		lock = (typeof obj.lock === 'undefined' ? 0 : Number(obj.lock)),
		fulladaption = (typeof obj.fulladaption === 'undefined' ? '' : ' fulladaption="1"'),
		text = (typeof obj.text === 'undefined' ? '' : obj.text);
	if(!text.length)text = '双击此处进行编辑';
	if(!style.length)style = 'left:0;top:0;width:100%;height:38px;font-size:24px;line-height:1;';
	if(typeof obj.link_type!=='undefined' && /^(web|page)$/.test(obj.link_type))link = ' link="'+obj.link_type+':'+obj.link_value+'"';
	let div = $('<div class="item '+(lock===1?'lock':'')+'" type="text" title="按住鼠标进行拖动，双击鼠标编辑文字" style="'+style+'" '+fulladaption+' '+link+'>'+(link.length?'<em></em>':'')+'<span><font>'+text+'</font></span></div>');
	viewer.append(div);
	if(style.indexOf('opacity')>-1){
		let matcher = style.match(/opacity:\s*([\d.]+);/);
		div.find('span').css('opacity', matcher[1]);
	}
	div.ZResize({
		isRotate: true,
		suck: 2,
		unusualGuide: 'section, em',
		skipShow: function(e){
			if($('body').hasClass('scene-preview'))return false;
			let contextmenu = $('.contextmenu');
			if(!!contextmenu.attr('target'))contextmenu.addClass('hidden');
			let o = e.target;
			do{
				if($(o).is('.control')){
					if($(o).find('.resize-panel').css('display')==='block'){
						contextmenu.addClass('hidden');
						if(!$(o).hasClass('lock'))$('.toolbar').removeClass('hidden');
					}
					return false;
				}
				if((/^(html|body)$/i).test(o.tagName))return true;
				o = o.parentNode;
			}while(o.parentNode);
		},
		skipHide: function(e){
			let o = e.target;
			do{
				if($(o).is('.toolbar') || $(o).is('.linkarea div em') || $(o).is('.linkarea div a') || $(o).is('.load-overlay')){
					$('.contextmenu').addClass('hidden');
					return false;
				}
				if($(o).is('.contextmenu'))return false;
				if((/^(html|body)$/i).test(o.tagName)){
					$('.contextmenu').addClass('hidden');
					return true;
				}
				o = o.parentNode;
			}while(o.parentNode);
		},
		skipOperate: function(e){
			return (this.hasClass('lock') || !!this.attr('fulladaption'));
		},
		show: function(position, e){
			let el = this.addClass('control'), style = el.attr('style'),
				toolbar = $('.toolbar').css({left:-99999, top:-99999, width:''}), wrapper = $('.wrapper'),
				left = wrapper.parent().parent().position().left + wrapper.parent().position().left + wrapper.position().left + position.left,
				top = wrapper.parent().parent().position().top + wrapper.parent().position().top + wrapper.position().top + position.top;
			if(!this.hasClass('lock')){
				toolbar.removeClass('hidden');
			}else{
				toolbar.addClass('hidden');
			}
			if(typeof e.button === 'undefined'){
				toolbar.addClass('hidden');
			}else{
				$('.contextmenu').addClass('hidden');
			}
			let link = el.attr('link'), linkarea = $('.linkarea').addClass('hidden');
			if(!!!link || link.indexOf('page:')>-1){
				let degrees = getTransform(el[0]).angle;
				let rotateSize = getRotateSize(degrees, el.width(), el.height());
				let rotatedWidth = rotateSize.width, rotatedHeight = rotateSize.height;
				let elOffset = el.offset(), elRight = elOffset.left + rotatedWidth, container = $('.container'), menuHeight = $('.scene-menu').height(),
					pageIndex = !!link ? Number(link.substr(5)) : Number('-'), isLink = true;
				if(isNaN(pageIndex))isLink = false;
				linkarea.css({
					left: elRight,
					top: elOffset.top + container.scrollTop() - menuHeight,
					width: Math.abs($('.parent').width()-elRight+5),
					height: isLink ? rotatedHeight/2 : rotatedHeight
				}).removeClass('hidden');
				if(isLink){
					$('.linkarea > div:first').removeClass('hidden').siblings().addClass('hidden');
					let li = $('.pages ul li').eq(pageIndex), liOffset = li.offset();
					let elCenter = elOffset.top + rotatedHeight/2, elMaxX = elOffset.left + rotatedWidth,
						liCenter = liOffset.top + li.height()/2, liLeft = liOffset.left;
					if(elCenter>liCenter){
						linkarea.css({top:liCenter+container.scrollTop()-menuHeight, height:elCenter-liCenter});
						linkarea.find('span').addClass('reverse');
					}else{
						linkarea.css({top:elCenter+container.scrollTop()-menuHeight, height:liCenter-elCenter});
						linkarea.find('span').removeClass('reverse');
					}
					let width = linkarea.width(), height = linkarea.height(), span = $('.linkarea > div:first span');
					span.css('width', Math.sqrt(Math.pow(width, 2) + Math.pow(height, 2)));
					let deg = Math.asin(height/span.outerWidth(false)) * 180 / Math.PI + (elMaxX<liLeft?0:180);
					if(elCenter>=liCenter)deg = -deg;
					if(elMaxX>=liLeft){
						deg = -deg;
						linkarea.addClass('hidden');
					}else{
						linkarea.removeClass('hidden');
					}
					span.css({'-webkit-transform':'rotate('+deg+'deg)', transform:'rotate('+deg+'deg)'});
				}else{
					$('.linkarea > div:last').removeClass('hidden').siblings().addClass('hidden');
					let em = $('.linkarea > div:last em'), span = $('.linkarea > div:last span');
					em.css({left:20, top:rotatedHeight/2-em.outerHeight(false)/2});
					em.attr({'origin-left':em.position().left, 'origin-top':em.position().top});
					span.css({
						top: rotatedHeight/2 - 1,
						width: 20 + em.outerWidth(false)/2,
						'-webkit-transform': 'rotate(0deg)',
						transform: 'rotate(0deg)'
					});
				}
			}
			toolbar.find('.hidden').removeClass('hidden');
			toolbar.find('.textHidden').addClass('hidden');
			el.siblings().removeClass('control');
			toolbar.width(toolbar.width());
			setTimeout(function(){
				toolbar.css({
					left: ((Number(left)+el.outerWidth(false)+Number(left)) - toolbar.outerWidth(false)) / 2,
					top: Number(top) - 30 - toolbar.outerHeight(false)
				});
			}, 10);
			if(style.indexOf('font-family:')>-1){
				let obj = $('.toolbar .fontfamily'), ul = obj.next(), val = style.replace(/^.*?font-family:\s*([^;]+).*$/, '$1');
				ul.find('.select').removeClass('select');
				ul.find('a').each(function(){
					if($(this).html()===val){
						$(this).addClass('select');
						return false;
					}
				});
			}else{
				$('.toolbar .fontfamily').next().find('.select').removeClass('select');
			}
			if(style.indexOf('font-size:')>-1){
				let obj = $('.toolbar .fontsize'), ul = obj.next(), val = el.css('font-size').replace('px', '');
				obj.html(ul.find('.fontsize'+val).html());
				ul.find('.select').removeClass('select');
				ul.find('.fontsize'+val).addClass('select');
			}else{
				$('.toolbar .fontsize').html('24px').next().find('.select').removeClass('select');
			}
			if(style.indexOf('color:')>-1){
				$('.toolbar .text').attr('color', el.css('color'));
				$('.toolbar .text i').css('background', el.css('color'));
			}else{
				$('.toolbar .text').removeAttr('color');
				$('.toolbar .text i').css('background', '');
			}
			if(style.indexOf('background:')>-1 || style.indexOf('background-color:')>-1){
				$('.toolbar .bg').attr('color', el.css('background-color'));
				$('.toolbar .bg span').css('background', el.css('background-color'));
			}else{
				$('.toolbar .bg').attr('color', el.css('background-color'));
				$('.toolbar .bg span').css('background', el.css('background-color'));
			}
			if(style.indexOf('font-weight:')>-1){
				$('.toolbar .style .b').addClass('select');
			}else{
				$('.toolbar .style .b').removeClass('select');
			}
			if(style.indexOf('font-style:')>-1){
				$('.toolbar .style .i').addClass('select');
			}else{
				$('.toolbar .style .i').removeClass('select');
			}
			if(style.indexOf('text-decoration-line:')>-1){
				if(style.indexOf('underline')>-1){
					$('.toolbar .style .u').addClass('select');
				}else{
					$('.toolbar .style .u').removeClass('select');
				}
				if(style.indexOf('line-through')>-1){
					$('.toolbar .style .s').addClass('select');
				}else{
					$('.toolbar .style .s').removeClass('select');
				}
			}else{
				$('.toolbar .style .u').removeClass('select');
				$('.toolbar .style .s').removeClass('select');
			}
			if(style.indexOf('text-align:')>-1){
				let span = $('.toolbar .textalign span').removeAttr('class'), ul = $('.toolbar .textalign').next();
				ul.find('.select').removeClass('select');
				switch(el.css('text-align')){
					case 'left':
						span.addClass('eqf-leftword');
						ul.find('.left').addClass('select');
						break;
					case 'center':
						span.addClass('eqf-minddleword');
						ul.find('.center').addClass('select');
						break;
					case 'right':
						span.addClass('eqf-rightword');
						ul.find('.right').addClass('select');
						break;
					case 'justify':
						span.addClass('eqf-scene-list');
						ul.find('.justify').addClass('select');
						break;
				}
			}else{
				$('.toolbar .textalign span').removeAttr('class').addClass('eqf-leftword');
				$('.toolbar .textalign').next().find('.select').removeClass('select');
			}
			if(style.indexOf('line-height:')>-1){
				let obj = $('.toolbar .lineheight'), ul = obj.next(), val = style.replace(/^.*?line-height:\s*([^;]+).*$/, '$1');
				let prefixInteger = function(num, length) {
					return (Array(length).join('0') + num).slice(-length);
				};
				ul.find('.select').removeClass('select');
				ul.find('.lineheight'+prefixInteger(val*100, 3)).addClass('select');
			}else{
				$('.toolbar .lineheight').next().find('.select').removeClass('select');
			}
			if(style.indexOf('letter-spacing:')>-1){
				let obj = $('.toolbar .letterspacing'), ul = obj.next(), val = style.replace(/^.*?letter-spacing:\s*([^;]+).*$/, '$1');
				ul.find('.select').removeClass('select');
				ul.find('.letterspacing'+(val.replace('em', '')*100)).addClass('select');
			}else{
				$('.toolbar .letterspacing').next().find('.select').removeClass('select');
			}
			if(style.indexOf('animation:')>-1){
				$('.toolbar .group ul.animation li').remove();
				let names = el.css('animation-name').split(','),
					times = el.css('animation-duration').split(','),
					delays = el.css('animation-delay').split(','),
					counts = el.css('animation-iteration-count').split(',');
				for(let i=0; i<names.length; i++){
					appendAnimation($.trim(names[i]), $.trim(times[i]).replace('s',''), $.trim(delays[i]).replace('s',''), $.trim(counts[i]));
				}
			}else{
				$('.toolbar .group ul.animation li').remove();
			}
		},
		move: function(position){
			if(this.hasClass('lock'))return;
			let el = this, toolbar = $('.toolbar'), wrapper = $('.wrapper'),
				left = wrapper.parent().parent().position().left + wrapper.parent().position().left + wrapper.position().left + position.left,
				top = wrapper.parent().parent().position().top + wrapper.parent().position().top + wrapper.position().top + position.top;
			toolbar.css({
				left: ((Number(left)+el.outerWidth(false)+Number(left)) - toolbar.outerWidth(false)) / 2,
				top: Number(top) - 30 - toolbar.outerHeight(false)
			}).find('.group.open').removeClass('open');
			$('.contextmenu').addClass('hidden');
			let link = el.attr('link'), linkarea = $('.linkarea');
			if(!!!link || link.indexOf('page:')>-1){
				let degrees = getTransform(el[0]).angle;
				let rotateSize = getRotateSize(degrees, el.width(), el.height());
				let rotatedWidth = rotateSize.width, rotatedHeight = rotateSize.height;
				let elOffset = el.offset(), elRight = elOffset.left + rotatedWidth, container = $('.container'), menuHeight = $('.scene-menu').height(),
					pageIndex = !!link ? Number(link.substr(5)) : Number('-'), isLink = true;
				if(isNaN(pageIndex))isLink = false;
				linkarea.css({
					left: elRight,
					top: elOffset.top + container.scrollTop() - menuHeight,
					width: Math.abs($('.parent').width()-elRight+5),
					height: isLink ? rotatedHeight/2 : rotatedHeight
				});
				if(isLink){
					let li = $('.pages ul li').eq(pageIndex), liOffset = li.offset();
					let elCenter = elOffset.top + rotatedHeight/2, elMaxX = elOffset.left + rotatedWidth,
						liCenter = liOffset.top + li.height()/2, liLeft = liOffset.left;
					if(elCenter > liCenter){
						linkarea.css({top:liCenter+container.scrollTop()-menuHeight, height:elCenter-liCenter});
						linkarea.find('span').addClass('reverse');
					}else{
						linkarea.css({top:elCenter+container.scrollTop()-menuHeight, height:liCenter-elCenter});
						linkarea.find('span').removeClass('reverse');
					}
					let width = linkarea.width(), height = linkarea.height(), span = $('.linkarea > div:first span');
					span.css('width', Math.sqrt(Math.pow(width, 2) + Math.pow(height, 2)));
					let deg = Math.asin(height/span.outerWidth(false)) * 180 / Math.PI + (elMaxX<liLeft?0:180);
					if(elCenter >= liCenter)deg = -deg;
					if(elMaxX >= liLeft){
						deg = -deg;
						linkarea.addClass('hidden');
					}else{
						linkarea.removeClass('hidden');
					}
					span.css({'-webkit-transform':'rotate('+deg+'deg)', transform:'rotate('+deg+'deg)'});
				}
			}
			setContent();
			if(window.sceneInitd)window.sceneSaved = false;
		},
		rotate: function(degrees){
			let el = this, link = this.attr('link'), linkarea = $('.linkarea');
			let rotateSize = getRotateSize(degrees, this.width(), this.height());
			let rotatedWidth = rotateSize.width, rotatedHeight = rotateSize.height;
			let elOffset = el.offset(), elRight = elOffset.left + rotatedWidth, container = $('.container'), menuHeight = $('.scene-menu').height(),
				pageIndex = !!link ? Number(link.substr(5)) : Number('-'), isLink = true;
			if(isNaN(pageIndex))isLink = false;
			linkarea.css({
				left: elRight,
				top: elOffset.top + container.scrollTop() - menuHeight,
				width: Math.abs($('.parent').width()-elRight+5),
				height: isLink ? rotatedHeight/2 : rotatedHeight
			});
			if(isLink){
				$('.linkarea > div:first').removeClass('hidden').siblings().addClass('hidden');
				let li = $('.pages ul li').eq(pageIndex), liOffset = li.offset();
				let elCenter = elOffset.top + rotatedHeight/2, elMaxX = elOffset.left + rotatedWidth,
					liCenter = liOffset.top + li.height()/2, liLeft = liOffset.left;
				if(elCenter>liCenter){
					linkarea.css({top:liCenter+container.scrollTop()-menuHeight, height:elCenter-liCenter});
					linkarea.find('span').addClass('reverse');
				}else{
					linkarea.css({top:elCenter+container.scrollTop()-menuHeight, height:liCenter-elCenter});
					linkarea.find('span').removeClass('reverse');
				}
				let width = linkarea.width(), height = linkarea.height(), span = $('.linkarea > div:first span');
				span.css('width', Math.sqrt(Math.pow(width, 2) + Math.pow(height, 2)));
				let deg = Math.asin(height/span.outerWidth(false)) * 180 / Math.PI + (elMaxX<liLeft?0:180);
				if(elCenter >= liCenter)deg = -deg;
				if(elMaxX >= liLeft){
					deg = -deg;
					linkarea.addClass('hidden');
				}else{
					linkarea.removeClass('hidden');
				}
				span.css({'-webkit-transform':'rotate('+deg+'deg)', transform:'rotate('+deg+'deg)'});
			}else{
				$('.linkarea > div:last').removeClass('hidden').siblings().addClass('hidden');
				let em = $('.linkarea > div:last em'), span = $('.linkarea > div:last span');
				em.css({left:20, top:rotatedHeight/2-em.outerHeight(false)/2});
				em.attr({'origin-left':em.position().left, 'origin-top':em.position().top});
				span.css({
					top: rotatedHeight/2 - 1,
					width: 20 + em.outerWidth(false)/2,
					'-webkit-transform': 'rotate(0deg)',
					transform: 'rotate(0deg)'
				});
			}
			setContent();
			if(window.sceneInitd)window.sceneSaved = false;
		},
		hide: function(){
			$('.toolbar').addClass('hidden');
			$('.linkarea').addClass('hidden');
			let font = this.removeClass('control').find('font').removeAttr('contenteditable');
			this.find('.resize-panel').css('z-index', '');
			if(!!font.data('edited')){
				font.removeData('edited');
				setContent();
				if(window.sceneInitd)window.sceneSaved = false;
			}
		}
	}).tapper(function(){
		if(!$('body').hasClass('scene-preview') || !!!$(this).attr('link'))return true;
		let link = $(this).attr('link'), arr = link.split(':'), type = arr[0], value = link.replace(/^(web|page):/, '');
		if(type==='web'){
			window.open(value);
		}else{
			if(window.previewChangePaging)return false;
			if(window.previewPages.length<=1)return false;
			let pageIndex = Number(value), direction = -1;
			if(window.previewPageIndex > pageIndex)direction = 1;
			previewChangePage(direction, pageIndex);
			return false;
		}
	});
	div.on('dblclick', function(){
		if($('body').hasClass('scene-preview') || $(this).hasClass('lock'))return;
		$(this).find('font').attr('contenteditable', 'true').focus().select().onkey(function(){
			$(this).data('edited', true);
		});
		$(this).find('.resize-panel').css('z-index', '-1');
	});
	if(typeof nonMenu === 'undefined'){
		setTimeout(function(){
			if($('body').hasClass('scene-preview'))return;
			div.trigger('click');
		}, 0);
	}
}
function setVideo(obj, nonMenu){
	if($(this).hasClass('disable'))return false;
	let viewer = $('.viewer:last'),
		style = (typeof obj.style === 'undefined' ? '' : obj.style),
		lock = (typeof obj.lock === 'undefined' ? 0 : Number(obj.lock)),
		fulladaption = (typeof obj.fulladaption === 'undefined' ? '' : ' fulladaption="1"'),
		code = (typeof obj.code === 'undefined' ? '' : obj.code);
	if(typeof obj.control !== 'undefined' && obj.control){
		obj.control.find('textarea').val(code);
		return;
	}
	if(!style.length)style = 'left:0;top:0;width:100%;height:170px;';
	let div = $('<div class="item '+(lock===1?'lock':'')+'" type="video" title="按住鼠标进行拖动，双击鼠标更换视频" style="'+style+'" '+fulladaption+'><textarea>'+code+'</textarea><i class="eqf-play2"></i></div>');
	viewer.append(div);
	div.ZResize({
		restrict: true,
		suck: 2,
		unusualGuide: 'section, em',
		skipShow: function(e){
			if($('body').hasClass('scene-preview'))return false;
			let contextmenu = $('.contextmenu');
			if(!!contextmenu.attr('target'))contextmenu.addClass('hidden');
			let o = e.target;
			do{
				if($(o).is('.control')){
					if($(o).find('.resize-panel').css('display')==='block'){
						contextmenu.addClass('hidden');
						if(!$(o).hasClass('lock'))$('.toolbar').removeClass('hidden');
					}
					return false;
				}
				if((/^(html|body)$/i).test(o.tagName))return true;
				o = o.parentNode;
			}while(o.parentNode);
		},
		skipHide: function(e){
			let o = e.target;
			do{
				if($(o).is('.toolbar') || $(o).is('.load-overlay')){
					$('.contextmenu').addClass('hidden');
					return false;
				}
				if($(o).is('.contextmenu'))return false;
				if((/^(html|body)$/i).test(o.tagName)){
					$('.contextmenu').addClass('hidden');
					return true;
				}
				o = o.parentNode;
			}while(o.parentNode);
		},
		skipOperate: function(e){
			return (this.hasClass('lock') || !!this.attr('fulladaption'));
		},
		show: function(position, e){
			let el = this.addClass('control'), style = el.attr('style'),
				toolbar = $('.toolbar').css({left:-99999, top:-99999, width:''}), wrapper = $('.wrapper'),
				left = wrapper.parent().parent().position().left + wrapper.parent().position().left + wrapper.position().left + position.left,
				top = wrapper.parent().parent().position().top + wrapper.parent().position().top + wrapper.position().top + position.top;
			if(!this.hasClass('lock')){
				toolbar.removeClass('hidden');
			}else{
				toolbar.addClass('hidden');
			}
			if(typeof e.button === 'undefined'){
				toolbar.addClass('hidden');
			}else{
				$('.contextmenu').addClass('hidden');
			}
			toolbar.find('.hidden').removeClass('hidden');
			toolbar.find('.videoHidden').addClass('hidden');
			el.siblings().removeClass('control');
			setTimeout(function(){
				toolbar.css({
					left: Number(left) + el.outerWidth(false) + 20,
					top: Number(top)
				});
			}, 10);
		},
		move: function(position){
			if(this.hasClass('lock'))return;
			let el = this, toolbar = $('.toolbar'), wrapper = $('.wrapper'),
				left = wrapper.parent().parent().position().left + wrapper.parent().position().left + wrapper.position().left + position.left,
				top = wrapper.parent().parent().position().top + wrapper.parent().position().top + wrapper.position().top + position.top;
			toolbar.css({
				left: Number(left) + el.outerWidth(false) + 20,
				top: Number(top)
			}).find('.group.open').removeClass('open');
			$('.contextmenu').addClass('hidden');
			setContent();
			if(window.sceneInitd)window.sceneSaved = false;
		},
		hide: function(){
			$('.toolbar').addClass('hidden');
			this.removeClass('control');
			this.find('.resize-panel').css('z-index', '');
		}
	});
	div.on('dblclick', function(){
		if($('body').hasClass('scene-preview') || $(this).hasClass('lock'))return;
		iframeLayer('/scene/video', 600);
	});
	if(typeof nonMenu === 'undefined'){
		setTimeout(function(){div.trigger('click')}, 0);
	}
}
function setWeb(obj, nonMenu){
	if($(this).hasClass('disable'))return false;
	let viewer = $('.viewer:last'),
		style = (typeof obj.style === 'undefined' ? '' : obj.style),
		lock = (typeof obj.lock === 'undefined' ? 0 : Number(obj.lock)),
		fulladaption = (typeof obj.fulladaption === 'undefined' ? '' : ' fulladaption="1"'),
		url = (typeof obj.url === 'undefined' ? '' : obj.url);
	let placeholder = '双击编辑URL，仅支持http,https协议网站';
	if(!style.length)style = 'left:0;top:0;width:100%;height:170px;';
	let div = $('<div class="item '+(lock===1?'lock':'')+'" type="web" url="'+url+'" title="按住鼠标进行拖动，双击鼠标编辑网址" style="'+style+'" '+fulladaption+'><h6><i></i><i></i><strong><p>'+(url.length?url:placeholder)+'</p></strong></h6><h4><p>Web Page</p></h4></div>');
	viewer.append(div);
	div.ZResize({
		minWidth: '100%',
		maxWidth: '100%',
		minHeight: 230,
		restrict: true,
		suck: 2,
		unusualGuide: 'section, em',
		skipShow: function(e){
			if($('body').hasClass('scene-preview'))return false;
			let contextmenu = $('.contextmenu');
			if(!!contextmenu.attr('target'))contextmenu.addClass('hidden');
			let o = e.target;
			do{
				if($(o).is('.control')){
					if($(o).find('.resize-panel').css('display')==='block'){
						contextmenu.addClass('hidden');
						if(!$(o).hasClass('lock'))$('.toolbar').removeClass('hidden');
					}
					return false;
				}
				if((/^(html|body)$/i).test(o.tagName))return true;
				o = o.parentNode;
			}while(o.parentNode);
		},
		skipHide: function(e){
			let o = e.target;
			do{
				if($(o).is('.toolbar') || $(o).is('.load-overlay')){
					$('.contextmenu').addClass('hidden');
					return false;
				}
				if($(o).is('.contextmenu'))return false;
				if((/^(html|body)$/i).test(o.tagName)){
					$('.contextmenu').addClass('hidden');
					return true;
				}
				o = o.parentNode;
			}while(o.parentNode);
		},
		skipOperate: function(e){
			return (this.hasClass('lock') || !!this.attr('fulladaption'));
		},
		show: function(position, e){
			let el = this.addClass('control'), style = el.attr('style'),
				toolbar = $('.toolbar').css({left:-99999, top:-99999, width:''}), wrapper = $('.wrapper'),
				left = wrapper.parent().parent().position().left + wrapper.parent().position().left + wrapper.position().left + position.left,
				top = wrapper.parent().parent().position().top + wrapper.parent().position().top + wrapper.position().top + position.top;
			if(!this.hasClass('lock')){
				toolbar.removeClass('hidden');
			}else{
				toolbar.addClass('hidden');
			}
			if(typeof e.button === 'undefined'){
				toolbar.addClass('hidden');
			}else{
				$('.contextmenu').addClass('hidden');
			}
			toolbar.find('.hidden').removeClass('hidden');
			toolbar.find('.webHidden').addClass('hidden');
			el.siblings().removeClass('control');
			setTimeout(function(){
				toolbar.css({
					left: Number(left) + el.outerWidth(false) + 20,
					top: Number(top)
				});
			}, 10);
		},
		move: function(position){
			if(this.hasClass('lock'))return;
			let el = this, toolbar = $('.toolbar'), wrapper = $('.wrapper'),
				left = wrapper.parent().parent().position().left + wrapper.parent().position().left + wrapper.position().left + position.left,
				top = wrapper.parent().parent().position().top + wrapper.parent().position().top + wrapper.position().top + position.top;
			toolbar.css({
				left: Number(left) + el.outerWidth(false) + 20,
				top: Number(top)
			}).find('.group.open').removeClass('open');
			$('.contextmenu').addClass('hidden');
			setContent();
			if(window.sceneInitd)window.sceneSaved = false;
		},
		hide: function(){
			$('.toolbar').addClass('hidden');
			let p = this.removeClass('control').find('h6 p');
			p.removeAttr('contenteditable');
			this.find('.resize-panel').css('z-index', '');
			if(!p.html().length || p.html()===placeholder){
				p.html(placeholder);
			}else{
				this.attr('url', p.html());
			}
		}
	});
	div.on('dblclick', function(){
		if($('body').hasClass('scene-preview') || $(this).hasClass('lock'))return;
		let p = $(this).find('h6 p');
		if(p.html()===placeholder)p.html('');
		p.attr('contenteditable', 'true').focus().select().onkey(function(code){
			if(code===13){
				$(this).removeAttr('contenteditable');
				if(!$(this).html().length){
					p.html(placeholder);
				}else{
					div.attr('url', $(this).html());
				}
				setContent();
				if(window.sceneInitd)window.sceneSaved = false;
				return false;
			}
		});
		$(this).find('.resize-panel').css('z-index', '-1');
	});
	if(typeof nonMenu === 'undefined'){
		setTimeout(function(){div.trigger('click')}, 0);
	}
}
function setMap(obj, nonMenu){
	if($(this).hasClass('disable'))return false;
	let viewer = $('.viewer:last'),
		style = (typeof obj.style === 'undefined' ? '' : obj.style),
		lock = (typeof obj.lock === 'undefined' ? 0 : Number(obj.lock)),
		fulladaption = (typeof obj.fulladaption === 'undefined' ? '' : ' fulladaption="1"'),
		longitude = (typeof obj.longitude === 'undefined' ? '0' : obj.longitude),
		latitude = (typeof obj.latitude === 'undefined' ? '0' : obj.latitude),
		zoom = (typeof obj.zoom === 'undefined' ? 18 : obj.zoom);
	if(typeof obj.control !== 'undefined' && obj.control){
		obj.control.attr({ longitude:longitude, latitude:latitude, zoom:zoom });
		let map = obj.control.find('i').baiduMap(true);
		map.panTo(new BMap.Point(longitude, latitude));
		map.setZoom(zoom);
		return;
	}
	if(!style.length)style = 'left:0;top:0;width:100%;height:170px;';
	let div = $('<div class="item '+(lock===1?'lock':'')+'" type="map" title="按住鼠标进行拖动，双击鼠标进行编辑" style="'+style+'" '+fulladaption+' longitude="'+longitude+'" latitude="'+latitude+'" zoom="'+zoom+'"><i></i></div>');
	viewer.append(div);
	div.find('i').baiduMap({longitude:longitude, latitude:latitude, zoom:zoom, zoomEnable:false, dragEnable:false, detailEnable:false});
	div.ZResize({
		restrict: true,
		suck: 2,
		unusualGuide: 'section, em',
		skipShow: function(e){
			if($('body').hasClass('scene-preview'))return false;
			let contextmenu = $('.contextmenu');
			if(!!contextmenu.attr('target'))contextmenu.addClass('hidden');
			let o = e.target;
			do{
				if($(o).is('.control')){
					if($(o).find('.resize-panel').css('display')==='block'){
						contextmenu.addClass('hidden');
						if(!$(o).hasClass('lock'))$('.toolbar').removeClass('hidden');
					}
					return false;
				}
				if((/^(html|body)$/i).test(o.tagName))return true;
				o = o.parentNode;
			}while(o.parentNode);
		},
		skipHide: function(e){
			let o = e.target;
			do{
				if($(o).is('.toolbar') || $(o).is('.load-overlay')){
					$('.contextmenu').addClass('hidden');
					return false;
				}
				if($(o).is('.contextmenu'))return false;
				if((/^(html|body)$/i).test(o.tagName)){
					$('.contextmenu').addClass('hidden');
					return true;
				}
				o = o.parentNode;
			}while(o.parentNode);
		},
		skipOperate: function(e){
			return (this.hasClass('lock') || !!this.attr('fulladaption'));
		},
		show: function(position, e){
			let el = this.addClass('control'), style = el.attr('style'),
				toolbar = $('.toolbar').css({left:-99999, top:-99999, width:''}), wrapper = $('.wrapper'),
				left = wrapper.parent().parent().position().left + wrapper.parent().position().left + wrapper.position().left + position.left,
				top = wrapper.parent().parent().position().top + wrapper.parent().position().top + wrapper.position().top + position.top;
			if(!this.hasClass('lock')){
				toolbar.removeClass('hidden');
			}else{
				toolbar.addClass('hidden');
			}
			if(typeof e.button === 'undefined'){
				toolbar.addClass('hidden');
			}else{
				$('.contextmenu').addClass('hidden');
			}
			toolbar.find('.hidden').removeClass('hidden');
			toolbar.find('.mapHidden').addClass('hidden');
			el.siblings().removeClass('control');
			setTimeout(function(){
				toolbar.css({
					left: Number(left) + el.outerWidth(false) + 20,
					top: Number(top)
				});
			}, 10);
		},
		move: function(position){
			if(this.hasClass('lock'))return;
			let el = this, toolbar = $('.toolbar'), wrapper = $('.wrapper'),
				left = wrapper.parent().parent().position().left + wrapper.parent().position().left + wrapper.position().left + position.left,
				top = wrapper.parent().parent().position().top + wrapper.parent().position().top + wrapper.position().top + position.top;
			toolbar.css({
				left: Number(left) + el.outerWidth(false) + 20,
				top: Number(top)
			}).find('.group.open').removeClass('open');
			$('.contextmenu').addClass('hidden');
			setContent();
			if(window.sceneInitd)window.sceneSaved = false;
		},
		hide: function(){
			$('.toolbar').addClass('hidden');
			this.removeClass('control');
			this.find('.resize-panel').css('z-index', '');
		}
	});
	div.on('dblclick', function(){
		if($('body').hasClass('scene-preview') || $(this).hasClass('lock'))return;
		iframeLayer('/scene/map', 770);
	});
	if(typeof nonMenu === 'undefined'){
		setTimeout(function(){div.trigger('click')}, 0);
	}
}
function setChart(obj, nonMenu){
	if($(this).hasClass('disable'))return false;
	let viewer = $('.viewer:last'),
		style = (typeof obj.style === 'undefined' ? '' : obj.style),
		lock = (typeof obj.lock === 'undefined' ? 0 : Number(obj.lock)),
		fulladaption = (typeof obj.fulladaption === 'undefined' ? '' : ' fulladaption="1"'),
		type = (typeof obj.ctype === 'undefined' ? 'bar' : obj.ctype),
		originData = (typeof obj.data === 'undefined' ? null : obj.data);
	if(!style.length)style = 'left:0;top:0;width:100%;height:270px;';
	if(typeof originData === 'string')originData = $.excelTable(originData);
	let data = [], series = [];
	if($.isArray(originData))$.each(originData, function(i, row){
		if(i === 0){
			data.push(row);
			for(let r=0; r<row.length-1; r++)series.push({type:type});
		}else{
			if(!$.isArray(row) || !row.length || !row[0].length)return true;
			data.push(row);
		}
	});
	if(!data.length){
		$.overloadError('图标数据为空');
		return false;
	}
	let option = {
		title: {
			text: ''
		},
		tooltip: {},
		legend: {},
		dataset: {
			source: data
		},
		xAxis: {type: 'category'},
		yAxis: {},
		series: series
	};
	if(typeof obj.control !== 'undefined' && obj.control){
		obj.control.attr({ 'chart-type':type, 'chart-data':typeof data==='string' ? data : $.excelTable(data) });
		obj.control.find('i').remove();
		obj.control.prepend('<i></i>');
		let chart = echarts.init(obj.control.find('i')[0]);
		chart.setOption(option);
		obj.control.data('chart.option', option);
		return;
	}
	let div = $('<div class="item '+(lock===1?'lock':'')+'" type="chart" title="按住鼠标进行拖动，双击鼠标进行编辑" style="'+style+'" '+fulladaption+' chart-type="'+type+'" chart-data="'+(typeof data==='string'?data:$.excelTable(data))+'"><i></i></div>');
	viewer.append(div);
	let chart = echarts.init(div.find('i')[0]);
	chart.setOption(option);
	div.data('chart.option', option);
	div.ZResize({
		restrict: true,
		suck: 2,
		unusualGuide: 'section, em',
		skipShow: function(e){
			if($('body').hasClass('scene-preview'))return false;
			let contextmenu = $('.contextmenu');
			if(!!contextmenu.attr('target'))contextmenu.addClass('hidden');
			let o = e.target;
			do{
				if($(o).is('.control')){
					if($(o).find('.resize-panel').css('display')==='block'){
						contextmenu.addClass('hidden');
						if(!$(o).hasClass('lock'))$('.toolbar').removeClass('hidden');
					}
					return false;
				}
				if((/^(html|body)$/i).test(o.tagName))return true;
				o = o.parentNode;
			}while(o.parentNode);
		},
		skipHide: function(e){
			let o = e.target;
			do{
				if($(o).is('.toolbar') || $(o).is('.load-overlay')){
					$('.contextmenu').addClass('hidden');
					return false;
				}
				if($(o).is('.contextmenu'))return false;
				if((/^(html|body)$/i).test(o.tagName)){
					$('.contextmenu').addClass('hidden');
					return true;
				}
				o = o.parentNode;
			}while(o.parentNode);
		},
		skipOperate: function(e){
			return (this.hasClass('lock') || !!this.attr('fulladaption'));
		},
		resize: function(){
			div.find('i').remove();
			div.prepend('<i></i>');
			let chart = echarts.init(div.find('i')[0]);
			chart.setOption(div.data('chart.option'));
		},
		show: function(position, e){
			let el = this.addClass('control'), style = el.attr('style'),
				toolbar = $('.toolbar').css({left:-99999, top:-99999, width:''}), wrapper = $('.wrapper'),
				left = wrapper.parent().parent().position().left + wrapper.parent().position().left + wrapper.position().left + position.left,
				top = wrapper.parent().parent().position().top + wrapper.parent().position().top + wrapper.position().top + position.top;
			if(!this.hasClass('lock')){
				toolbar.removeClass('hidden');
			}else{
				toolbar.addClass('hidden');
			}
			if(typeof e.button === 'undefined'){
				toolbar.addClass('hidden');
			}else{
				$('.contextmenu').addClass('hidden');
			}
			toolbar.find('.hidden').removeClass('hidden');
			toolbar.find('.chartHidden').addClass('hidden');
			el.siblings().removeClass('control');
			setTimeout(function(){
				toolbar.css({
					left: Number(left) + el.outerWidth(false) + 20,
					top: Number(top)
				});
			}, 10);
		},
		move: function(position){
			if(this.hasClass('lock'))return;
			let el = this, toolbar = $('.toolbar'), wrapper = $('.wrapper'),
				left = wrapper.parent().parent().position().left + wrapper.parent().position().left + wrapper.position().left + position.left,
				top = wrapper.parent().parent().position().top + wrapper.parent().position().top + wrapper.position().top + position.top;
			toolbar.css({
				left: Number(left) + el.outerWidth(false) + 20,
				top: Number(top)
			}).find('.group.open').removeClass('open');
			$('.contextmenu').addClass('hidden');
			setContent();
			if(window.sceneInitd)window.sceneSaved = false;
		},
		hide: function(){
			$('.toolbar').addClass('hidden');
			this.removeClass('control');
			this.find('.resize-panel').css('z-index', '');
		}
	});
	div.on('dblclick', function(){
		if($('body').hasClass('scene-preview') || $(this).hasClass('lock'))return;
		iframeLayer('/scene/chart', 1000);
	});
	if(typeof nonMenu === 'undefined'){
		setTimeout(function(){div.trigger('click')}, 0);
	}
}
function setSimplePic(viewer, obj){
	setContent();
	let width = viewer.width(), image = new Image(),
		style = (typeof obj.style === 'undefined' ? '' : obj.style),
		url = (typeof obj.url === 'undefined' ? '' : obj.url);
	if(!url.length)return;
	image.src = url;
	let s = function(){
		let imageWidth = image.width, imageHeight = image.height;
		if(imageWidth > width){
			imageHeight = (imageHeight * width) / imageWidth;
			imageWidth = width;
		}
		if(!style.length)style = 'left:0;top:0;width:'+imageWidth+'px;height:'+imageHeight+'px;';
		let div = $('<div class="item" type="image" style="'+style+'"><img src="'+url+'" /></div>');
		viewer.append(div);
		if(style.indexOf('border-radius')>-1)div.find('img').css('border-radius', div.css('border-radius'));
		if(style.indexOf('opacity')>-1){
			let matcher = style.match(/opacity:\s*([\d.]+);/);
			div.find('img').css('opacity', matcher[1]);
		}
	};
	image.onload = function(){
		s();
	};
}
function setSimpleText(viewer, obj){
	setContent();
	let style = (typeof obj.style === 'undefined' ? '' : obj.style),
		text = (typeof obj.text === 'undefined' ? '双击此处进行编辑' : obj.text);
	if(!style.length)style = 'left:0;top:0;width:100%;height:38px;font-size:24px;line-height:1;';
	let div = $('<div class="item" type="text" style="'+style+'"><span><font>'+text+'</font></span></div>');
	viewer.append(div);
	if(style.indexOf('opacity')>-1){
		let matcher = style.match(/opacity:\s*([\d.]+);/);
		div.find('span').css('opacity', matcher[1]);
	}
}
function setSimpleVideo(viewer, obj){
	setContent();
	let style = (typeof obj.style === 'undefined' ? '' : obj.style);
	if(!style.length)style = 'left:0;top:0;width:100%;height:170px;';
	let div = $('<div class="item" type="video" style="'+style+'"><i class="eqf-play2"></i></div>');
	viewer.append(div);
}
function setSimpleWeb(viewer, obj){
	setContent();
	let style = (typeof obj.style === 'undefined' ? '' : obj.style),
		url = (typeof obj.url === 'undefined' ? '' : obj.url);
	if(!style.length)style = 'left:0;top:0;width:100%;height:170px;';
	let div = $('<div class="item" type="web" style="'+style+'"><h6><i></i><i></i><strong><p>'+(url.length?url:'双击编辑URL，仅支持http,https协议网站')+'</p></strong></h6><h4><p>Web Page</p></h4></div>');
	viewer.append(div);
}
function setSimpleMap(viewer, obj){
	setContent();
	let style = (typeof obj.style === 'undefined' ? '' : obj.style),
		longitude = (typeof obj.longitude === 'undefined' ? '0' : obj.longitude),
		latitude = (typeof obj.latitude === 'undefined' ? '0' : obj.latitude),
		zoom = (typeof obj.zoom === 'undefined' ? 18 : obj.zoom);
	if(!style.length)style = 'left:0;top:0;width:100%;height:170px;';
	let div = $('<div class="item" type="map" style="'+style+'"><img /></div>');
	viewer.append(div);
	div.find('img').baiduMapImage({longitude:longitude, latitude:latitude, zoom:zoom, zoomEnable:false, dragEnable:false, detailEnable:false});
}
function setSimpleChart(viewer, obj){
	setContent();
	let style = (typeof obj.style === 'undefined' ? '' : obj.style),
		type = (typeof obj.type === 'undefined' ? 'bar' : obj.type),
		originData = (typeof obj.data === 'undefined' ? null : obj.data);
	if(!style.length)style = 'left:0;top:0;width:100%;height:270px;';
	if(typeof originData === 'string'){
		let obj = [];
		$.each(originData.split(','), function(i, item){
			let row = [];
			$.each(item.split(':'), function(j, column){
				row.push(column);
			});
			obj.push(row);
		});
		originData = obj;
	}
	let data = [], series = [];
	if($.isArray(originData))$.each(originData, function(i, row){
		if(i === 0){
			data.push(row);
			for(let r=0; r<row.length-1; r++)series.push({type:type});
		}else{
			if(!$.isArray(row) || !row.length || !row[0].length)return true;
			data.push(row);
		}
	});
	if(!data.length)return;
	let option = {
		title: {
			text: ''
		},
		tooltip: {},
		legend: {},
		dataset: {
			source: data
		},
		xAxis: {type: 'category'},
		yAxis: {},
		series: series
	};
	let div = $('<div class="item" type="chart" style="'+style+'"><i></i></div>');
	viewer.append(div);
	let chart = echarts.init(div.find('i')[0]);
	chart.setOption(option);
}
function setContent(){
	let content = [];
	$('.viewer').children().not('section, em').each(function(){
		let _this = $(this), type = _this.attr('type'), style = _this.attr('style'), link = _this.attr('link'), lock = _this.hasClass('lock') ? 1 : 0,
			left = parseInt(_this.css('left').replace('px', '')),
			top = parseInt(_this.css('top').replace('px', '')),
			width = _this.outerWidth(false),
			height = _this.outerHeight(false);
		let obj = {type:type, left:left, top:top, width:width, height:height, style:style, lock:lock};
		if(!!_this.attr('fulladaption')){
			left = 0;
			top = 0;
			width = '100%';
			height = '100%';
			obj.fulladaption = 1;
		}
		if(!!link){
			let arr = link.split(':');
			obj.link_type = arr[0];
			obj.link_value = link.replace(/^(web|page):/, '');
		}
		if(type==='image'){
			obj.url = _this.attr('url');
		}else if(type==='text'){
			obj.text = _this.find('font').html();
		}else if(type==='video'){
			obj.code = _this.find('textarea').val();
		}else if(type==='web'){
			obj.url = _this.attr('url');
		}else if(type==='map'){
			obj.longitude = _this.attr('longitude');
			obj.latitude = _this.attr('latitude');
			obj.zoom = _this.attr('zoom');
		}else if(type==='chart'){
			obj.ctype = _this.attr('chart-type');
			obj.data = _this.attr('chart-data');
		}
		content.push(obj);
	});
	if(content.length){
		content = $.jsonString(content);
	}else{
		content = '';
	}
	$('.pages li.this .content').val(content);
}
function sceneSet(){
	if(!$('.pages li.this').length)return;
	let viewer = $('.viewer'), bg = $('.pages li.this .bg').val(), content = $('.pages li.this .content').val();
	viewer.css({'background-color':'', 'background-image':''}).children().not('section, em').remove();
	if(bg.length){
		if(bg.substr(0, 1)==='#'){
			viewer.css('background-color', bg);
		}else{
			viewer.css('background-image', 'url('+bg+')');
		}
	}
	if(!content.length)return;
	if(content.substr(0, 1) !== '[')content = $.base64().decode(content);
	content = $.json(content);
	if(!$.isArray(content))return;
	let images = [];
	$.each(content, function(){
		if(this.type==='image'){
			images.push(this.url);
		}
	});
	let s = function(){
		$.each(content, function(){
			if(this.type==='image'){
				setPic(this, true, true);
			}else if(this.type==='text'){
				setText(this, true);
			}else if(this.type==='video'){
				setVideo(this, true);
			}else if(this.type==='web'){
				setWeb(this, true);
			}else if(this.type==='map'){
				setMap(this, true);
			}else if(this.type==='chart'){
				setChart(this, true);
			}
		});
	};
	if(images.length){
		let count = 0;
		for(let i=0; i<images.length; i++){
			let image = new Image();
			image.src = images[i];
			image.onload = function(){
				count++;
				if(count===images.length)s();
			};
		}
	}else{
		s();
	}
}
function sceneSave(e, nonToast, callback){
	if(!$('.boxer > .loading').hasClass('hidden'))return;
	setContent();
	let params = $('.scene').param({filter:false});
	if(typeof params.pages==='undefined' || !$.isArray(params.pages) || !params.pages.length){
		alert('缺少场景页');
		return;
	}
	if(!$('#cover').val().length){
		alert('请选择封面图片');
		showSet(0);
		return;
	}
	if(!$('#title').val().length){
		alert('场景标题不能为空');
		showSet(0);
		return;
	}
	if(!nonToast)$('.loadicon').addClass('playing');
	$.postJSON('/scene/edit', params, function(json){
		$('#id').val(json.data);
		if(!nonToast){
			$('.loadicon').removeClass('playing');
			showToast('场景保存成功');
		}
		window.sceneData.edit_time = Math.floor(new Date().getTime()/1000);
		setSaveBtnTitle();
		if($.isFunction(callback))callback();
		setTimeout(function(){
			window.sceneSaved = true;
		}, 1000);
	});
}
function showToast(msg, timer){
	if(typeof timer==='undefined')timer = 4000;
	let toast = $('.toast', top.document.body);
	if(toast.length){
		toast.html(msg);
		clearTimeout(window.showToastTimer);
		window.showToastTimer = null;
	}else{
		$(top.document.body).append('<div class="toast">'+msg+'</div>');
		toast = $('.toast', top.document.body).click(function(){
			clearTimeout(window.showToastTimer);
			window.showToastTimer = null;
			toast.addClass('toast-out');
			setTimeout(function(){toast.remove()}, 400);
		});
	}
	setTimeout(function(){
		toast.addClass('toast-in');
		window.showToastTimer = setTimeout(function(){
			toast.addClass('toast-out');
			setTimeout(function(){toast.remove()}, 400);
		}, timer);
	}, 10);
}
function preview(){
	if($(this).hasClass('disable'))return false;
	let _body = $('body');
	if(_body.hasClass('scene-preview')){
		_body.removeClass('scene-preview');
		let parent = $('.parent');
		if(!!parent.data('removeface'))parent.removeData('removeface').removeAttr('face');
		$('.wrapper').off('mousedown', swipeStart);
		if($('.viewer .item').length){
			$('.viewer .item:first').before(window.previewGrid);
		}else{
			$('.viewer').append(window.previewGrid);
		}
	}else{
		setContent();
		_body.addClass('scene-preview');
		let parent = $('.parent');
		if(!!!parent.attr('face'))parent.data('removeface', true).attr('face', $('.pad-face:eq(0)').attr('data-face'));
		let _grid = $('.viewer > section');
		window.previewGrid = _grid.clone();
		_grid.remove();
		window.previewPages = [];
		window.previewChangePaging = false;
		window.previewPageIndex = Number($('.viewer').attr('page'));
		$('.pages li').each(function(){
			window.previewPages.push({
				bg: $(this).find('.bg').val(),
				content: $(this).find('.content').val()
			});
		});
		$('.wrapper').on('mousedown', swipeStart);
	}
}
function swipeStart(e){
	window.previewDirection = 0;
	window.previewLastY = $.touches(e).y;
	$('.wrapper').on('mousemove', swipeMove).on('mouseup', swipeEnd);
	return true;
}
function swipeMove(e){
	e.preventDefault();
	if(window.previewChangePaging)return false;
	let y = $.touches(e).y;
	if(y - window.previewLastY < 0){
		window.previewDirection = -1;
	}else if(y - window.previewLastY > 0){
		window.previewDirection = 1;
	}
	return true;
}
function swipeEnd(e){
	$('.wrapper').off('mousemove', swipeMove).off('mouseup', swipeEnd);
	if(window.previewDirection!==0)previewChangePage(window.previewDirection);
	return true;
}
function previewChangePage(direction, pageIndex){
	window.previewChangePaging = true;
	let cls = '';
	if(direction===-1){ //下一页
		if(typeof pageIndex==='undefined'){
			if(window.previewPageIndex===window.previewPages.length-1){
				window.previewPageIndex = 0;
			}else{
				window.previewPageIndex++;
			}
		}else{
			window.previewPageIndex = pageIndex;
		}
		cls = 'swipeOutBottom';
	}else{ //上一页
		if(typeof pageIndex==='undefined'){
			if(window.previewPageIndex===0){
				window.previewPageIndex = window.previewPages.length - 1;
			}else{
				window.previewPageIndex--;
			}
		}else{
			window.previewPageIndex = pageIndex;
		}
		cls = 'swipeOutTop';
	}
	$('.wrapper').append('<div class="viewer '+cls+'" page="'+window.previewPageIndex+'"></div>');
	setTimeout(function(){
		let page = window.previewPages[window.previewPageIndex];
		let li = $('.wrapper .viewer:last').removeClass(cls), content = page.content;
		if(typeof page.bg!=='undefined' && page.bg.length){
			if(page.bg.substr(0, 1)==='#'){
				li.css('background-color', page.bg);
			}else{
				li.css('background-image', 'url('+page.bg+')');
			}
		}
		if(content.length){
			content = $.json(content);
			if(!$.isArray(content)){
				window.previewChangePaging = false;
				return;
			}
		}
		setTimeout(function(){
			window.previewChangePaging = false;
			$('.wrapper .viewer:first').remove();
			if($.isArray(content)){
				let images = [];
				$.each(content, function(){
					if(this.type==='image'){
						images.push(this.url);
					}
				});
				let s = function(){
					$.each(content, function(){
						if(this.type==='image'){
							setPic(this, false, true);
						}else if(this.type==='text'){
							setText(this);
						}else if(this.type==='video'){
							setVideo(this);
						}else if(this.type==='web'){
							setWeb(this);
						}else if(this.type==='map'){
							setMap(this);
						}else if(this.type==='chart'){
							setChart(this);
						}
					});
				};
				if(images.length){
					let count = 0;
					for(let i=0; i<images.length; i++){
						let image = new Image();
						image.src = images[i];
						image.onload = function(){
							count++;
							if(count===images.length)s();
						};
					}
				}else{
					s();
				}
			}
			$('.pages li').eq(window.previewPageIndex).addClass('this').siblings().removeClass('this');
		}, 500);
	}, 10);
}
function setSaveBtnTitle(){
	let save = $('.scene-menu .save'), title = platformKey('Ctrl')+' + S';
	if(window.sceneData.edit_time>0)title += '<br />'+getSecondTime(Math.floor(new Date().getTime()/1000)-Number(window.sceneData.edit_time));
	let tips = save.attr('title', title).data('tips');
	if(!!tips){
		tips.children('span').html(title);
		tips.css({left:save.offset().left+(save.outerWidth(false)/2-tips.outerWidth(false)/2)});
	}
}
function getSecondTime(second){
	let time = 0;
	second = Number(second);
	if(second===0){
		return '场景保存成功';
	}else if(second>60*60*24*30*12){
		time = Math.floor(second/(60*60*24*30*12))+'年';
	}else if(second>60*60*24*30){
		time = Math.floor(second/(60*60*24*30))+'月';
	}else if(second>60*60*24*7){
		time = Math.floor(second/(60*60*24*7))+'周';
	}else if(second>60*60*24){
		time = Math.floor(second/(60*60*24))+'天';
	}else if(second>60*60){
		time = Math.floor(second/(60*60))+'小时';
	}else if(second>60){
		time = Math.floor(second/60)+'分钟';
	}else{
		time = second+'秒';
	}
	return '大约'+time+'之前保存过';
}

function password(origin_password, password){
	$.postJSON('/api/home/password', {origin_password:origin_password, password:password}, function(){
		showToast('修改成功');
	});
}
function sceneGift(id, username){
	$.overload(null, '.load-animate load-animate2');
	$.postJSON('/scene/gift', {id:id, username:username}, function(){
		showToast('转赠成功');
		setTimeout(function(){
			let li = $('.section ul li[scene="'+id+'"]').addClass('scale-out');
			setTimeout(function(){
				li.remove();
			}, 300);
		}, 500);
	});
}
function sceneCopy(id){
	$.overload(null, '.load-animate load-animate2');
	$.postJSON('/scene/copy', {id:id}, function(json){
		let data = json.data,
			html = '<li scene="'+data.id+'" class="scale-out">\
			<div class="nameplate"><span>未发布</span></div>\
			<div class="pic" style="background-image:url('+data.cover+');"></div>\
			<div class="lay">\
				<a href="/scene/publish?id='+data.id+'"><em class="eqf-scene-send"></em>发布</a>\
				<a href="/scene/'+data.id+'"><em class="eqf-xiuziti"></em>编辑</a>\
				<a href="/scene/detail?id='+data.id+'"><em class="eqf-date"></em>详情</a>\
			</div>\
			<div class="view">\
				<div class="project">\
					<div>'+data.title+'</div>\
					<span>编码：'+data.code+'<font><em class="eqf-eye"></em>'+data.click+'</font></span>\
				</div>\
				<div class="button">\
					<a href="/scene/'+data.id+'#showSet"><em class="eqf-scene-setting"></em><span>设置</span></a>\
					<a href="/scene/publish?id='+data.id+'" class="publish"><em class="eqf-scene-send"></em><span>发布</span></a>\
					<a href="/scene/gift?id='+data.id+'" class="gift iframe-layer"><em class="eqf-scene-gift"></em><span>转赠</span></a>\
					<a href="/scene/copy?id='+data.id+'" class="copy iframe-layer"><em class="eqf-scene-copy"></em><span>复制</span></a>\
					<a href="/scene/delete?id='+data.id+'" class="del iframe-layer"><em class="eqf-scene-delete"></em><span>删除</span></a>\
					<div><em class="eqf-QRcode"></em></div>\
					<span><i url="'+data.url+'"></i></span>\
				</div>\
			</div>\
		</li>';
		$('.section ul').prepend(html);
		setTimeout(function(){
			$('.section ul li[scene="'+data.id+'"]').removeClass('scale-out');
			$('.section li .view .button i').each(function(){
				let _this = $(this);
				if(!!_this.data('qrcode'))return true;
				_this.data('qrcode', true).qrcode({
					render: 'background',
					width: 220,
					height: 220,
					text: _this.attr('url')
				})
			});
		}, 500);
	});
}
function sceneDelete(id){
	$.overload(null, '.load-animate load-animate2');
	$.postJSON('/scene/delete', {id:id}, function(){
		setTimeout(function(){
			let li = $('.section ul li[scene="'+id+'"]').addClass('scale-out');
			setTimeout(function(){
				li.remove();
			}, 300);
		}, 500);
	});
}
function setStatus(){
	let request = $.request('?'), status = this.checked ? 1 : 0;
	if(!request || typeof request.id==='undefined')return;
	$.postJSON('/scene/status', {id:request.id, status:status});
}
function iframeLayer(url, clientWidth, clientHeight){
	let body = $(top.document.body), width = 460, height = 166, autoHeight = false;
	if(typeof clientWidth === 'undefined')clientWidth = width;
	if(typeof clientHeight === 'undefined' || clientHeight === 0){
		clientHeight = height;
		autoHeight = true;
	}
	let html = '<div class="dialog" no-close="true" style="width:'+width+'px;height:'+height+'px;">\
		<iframe src="about:blank" frameborder="0"></iframe>\
		<div></div>\
	</div>\
	<a href="javascript:void(0)" class="dialog-cancel"><em class="eqf-wrong"></em></a>';
	body.overlay(html);
	body.find('.dialog-cancel').click(function(){
		clearTimeout(window.showIframeTimer);
		window.showIframeTimer = null;
		body.overlay(false);
	});
	let iframe = body.find('.dialog iframe').attr('src', url);
	iframe.on('load', function(){
		clearTimeout(window.showIframeTimer);
		window.showIframeTimer = null;
		body.find('.dialog-cancel').fadeOut(300);
		body.find('.dialog div').remove();
		clientHeight = iframe[0].contentWindow.document.body.offsetHeight;
		let dialog = body.find('.dialog'), regularWidth = false;
		if(clientWidth>0){
			dialog.width(clientWidth).parent().css({width:clientWidth, height:'auto'});
			regularWidth = true;
		}
		if(autoHeight)dialog.height(clientHeight);
		if(!regularWidth)setTimeout(function(){dialog.parent().css({width:dialog.width(), height:'auto'})}, 100);
	});
	clearTimeout(window.showIframeTimer);
	window.showIframeTimer = setTimeout(function(){
		clearTimeout(window.showIframeTimer);
		window.showIframeTimer = null;
		body.find('.dialog-cancel').fadeIn(300);
	}, 5000);
}
$.extend({
	getApi: function(url, data, callback, async, crossDomain){
		if($.isFunction(data)){
			if(typeof callback==='undefined'){
				callback = data;
				data = { };
			}else{
				data = data.call();
			}
		}
		$.api('get', url, data, callback, async, crossDomain);
	},
	postApi: function(url, data, callback, async, crossDomain){
		$.api('post', url, data, callback, async, crossDomain);
	},
	//async:true 异步, crossDomain:true 带上cookie请求
	api: function(method, url, data, callback, async, crossDomain){
		let timer = setTimeout(function(){ $.overload() }, 800);
		let error = null;
		if($.isPlainObject(callback)){
			error = callback.error;
			callback = callback.success || callback.callback;
		}
		if(!/^\//.test(url) && !/^https?:\/\//.test(url))url = '/'+url;
		if(typeof async==='undefined')async = true;
		let options = {
			url: url,
			type: method,
			dataType: 'json',
			data: data,
			async: async,
			timeout: 10000,
			success: function(json){
				clearTimeout(timer); timer = null;
				$.overload(false);
				if(typeof(json.error)!=='undefined' && typeof(json.msg)!=='undefined' && json.error!==0){
					if(json.msg.length)$.overloadError(json.msg);
					if($.isFunction(error))error(json);
					if(typeof(json.msg_type)!=='undefined' && Number(json.msg_type)===-100){
						setTimeout(function(){
							location.reload();
						}, 2000);
					}
					return;
				}
				if($.isFunction(callback))callback(json);
			},
			error: function(){
				clearTimeout(timer); timer = null;
				$.overload(false);
				$.overloadError('接口错误');
			}
		};
		if(crossDomain){
			options = $.extend({}, options, {
				xhrFields: {
					withCredentials: true
				},
				crossDomain: true
			});
		}
		$.ajax(options);
	}
});
$(function(){
	if($('.scene').length)sceneInit();
	$(document.body).on('click', 'a.iframe-layer', function(){
		let _this = $(this), href = _this.attr('href'), width = 0, height = 0;
		if(href.indexOf('#')>-1 || href.indexOf('javascript:')>-1 || _this.hasClass('disable'))return false;
		if(!!_this.attr('width'))width = _this.attr('width');
		if(!!_this.attr('height'))height = _this.attr('height');
		iframeLayer(href, width, height);
		return false;
	});
});