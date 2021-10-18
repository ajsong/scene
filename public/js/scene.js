window.sceneData = {};
window.scenePages = [];
window.scenePageIndex = 0;
window.changePaging = false;
window.popTime = new Date().getTime();

let lastY = 0, direction = 0;
function swipeStart(e){
	direction = 0;
	let o = e.target;
	do{
		if($(o).is('a') || $(o).is('div[type="web"]') || $(o).is('div[type="map"]'))return true;
		o = o.parentNode;
	}while(o.parentNode);
	lastY = $.touches(e).y;
	let detail = $('.wap-detail');
	detail.on('mousemove', swipeMove);
	detail.on('mouseup', swipeEnd);
	if(window.addEventListener){
		detail[0].addEventListener('touchmove', swipeMove, false);
		detail[0].addEventListener('touchend', swipeEnd, false);
	}
	return true;
}
function swipeMove(e){
	e.preventDefault();
	if(window.changePaging)return false;
	let y = $.touches(e).y;
	if(y - lastY < 0){
		direction = -1;
	}else if(y - lastY > 0){
		direction = 1;
	}
	return true;
}
function swipeEnd(){
	let detail = $('.wap-detail');
	detail.off('mousemove', swipeMove);
	detail.off('mouseup', swipeEnd);
	if(window.addEventListener){
		detail[0].removeEventListener('touchmove', swipeMove, false);
		detail[0].removeEventListener('touchend', swipeEnd, false);
	}
	if(direction !== 0)changePage(direction);
	return true;
}
function changePage(direction, pageIndex){
	window.changePaging = true;
	let cls = '';
	if(direction === -1){ //下一页
		if(typeof pageIndex === 'undefined'){
			if(window.scenePageIndex===window.scenePages.length-1){
				window.scenePageIndex = 0;
			}else{
				window.scenePageIndex++;
			}
		}else{
			window.scenePageIndex = pageIndex;
		}
		cls = 'swipeOutBottom';
	}else{ //上一页
		if(typeof pageIndex === 'undefined'){
			if(window.scenePageIndex===0){
				window.scenePageIndex = window.scenePages.length - 1;
			}else{
				window.scenePageIndex--;
			}
		}else{
			window.scenePageIndex = pageIndex;
		}
		cls = 'swipeOutTop';
	}
	$('.wap-detail > ul').append('<li class="'+cls+'"></li>');
	setTimeout(function(){
		let page = window.scenePages[window.scenePageIndex];
		let li = $('.wap-detail > ul > li:last').removeClass(cls), content = page.content;
		if(typeof page.bg !== 'undefined' && page.bg.length){
			if(page.bg.substr(0, 1)==='#'){
				li.css('background-color', page.bg);
			}else{
				li.css('background-image', 'url('+page.bg+')');
			}
		}
		if(content.length){
			content = content.replace(/(-?[\d.]+)px\b/ig, function($, $1){
				return (Number($1) / 100) + 'rem';
			});
			content = $.json(content);
			if(!$.isArray(content)){
				window.changePaging = false;
				return;
			}
		}
		setTimeout(function(){
			window.changePaging = false;
			$('.wap-detail > ul > li:first').remove();
			if($.isArray(content)){
				let images = [];
				$.each(content, function(){
					if(this.type === 'image'){
						images.push(this.url);
					}
				});
				let s = function(){
					$.each(content, function(){
						if(this.type === 'image'){
							setPic(this, true);
						}else if(this.type === 'text'){
							setText(this);
						}else if(this.type === 'video'){
							setVideo(this);
						}else if(this.type === 'web'){
							setWeb(this);
						}else if(this.type === 'map'){
							setMap(this);
						}else if(this.type === 'chart'){
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
							if(count === images.length)s();
						};
					}
				}else{
					s();
				}
			}
			$('.num').html((window.scenePageIndex+1)+'/'+window.scenePages.length);
		}, 500);
	}, 10);
}
function appendPage(page){
	$('.wap-detail > ul').html('<li></li>');
	let li = $('.wap-detail > ul > li:last');
	if(typeof page.bg !== 'undefined' && page.bg.length){
		if(page.bg.substr(0, 1) === '#'){
			li.css('background-color', page.bg);
		}else{
			li.css('background-image', 'url('+page.bg+')');
		}
	}
	if(!page.content.length)return;
	let content = page.content.replace(/(-?[\d.]+)px\b/ig, function($, $1){
		return (Number($1) / 100) + 'rem';
	});
	content = $.json(content);
	if(!$.isArray(content))return;
	let images = [];
	$.each(content, function(){
		if(this.type === 'image'){
			images.push(this.url);
		}
	});
	let s = function(){
		$.each(content, function(){
			if(this.type === 'image'){
				setPic(this, true);
			}else if(this.type === 'text'){
				setText(this);
			}else if(this.type === 'video'){
				setVideo(this);
			}else if(this.type === 'web'){
				setWeb(this);
			}else if(this.type === 'map'){
				setMap(this);
			}else if(this.type === 'chart'){
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
				if(count === images.length)s();
			};
		}
	}else{
		s();
	}
}
function setPic(obj, nonLoadImage){
	let viewer = $('.wap-detail > ul > li:last'), width = viewer.width(), image = new Image(),
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
		let div = '<div type="image" url="'+url+'" style="'+style+'">';
		if(typeof obj.link_type === 'undefined'){
			div += '<img src="'+url+'" />';
		}else{
			let href = 'javascript:void(0)', param = '';
			if(obj.link_type === 'web'){
				href = obj.link_value;
				param = ' target="_blank"';
			}else{
				param = ' page="'+obj.link_value+'"';
			}
			div += '<a href="'+href+'"'+param+'><img src="'+url+'" /></a>';
		}
		div += '</div>';
		viewer.append(div);
	};
	if(!nonLoadImage){
		image.onload = function(){
			s();
		};
	}else{
		s();
	}
}
function setText(obj){
	let viewer = $('.wap-detail > ul > li:last'),
		style = (typeof obj.style === 'undefined' ? '' : obj.style),
		text = (typeof obj.text === 'undefined' ? '双击此处进行编辑' : obj.text);
	if(!style.length)style = 'left:0;top:0;width:100%;height:38px;font-size:24px;line-height:1;';
	let div = '<div type="text" style="'+style+'">';
	if(typeof obj.link_type === 'undefined'){
		div += '<span><font>'+text+'</font></span>';
	}else{
		let href = 'javascript:void(0)', param = '';
		if(obj.link_type === 'web'){
			href = obj.link_value;
			param = ' target="_blank"';
		}else{
			param = ' page="'+obj.link_value+'"';
		}
		div += '<a href="'+href+'"'+param+'><span><font>'+text+'</font></span></a>';
	}
	div += '</div>';
	viewer.append(div);
}
function setVideo(obj){
	let viewer = $('.wap-detail > ul > li:last'),
		style = (typeof obj.style === 'undefined' ? '' : obj.style),
		code = (typeof obj.code === 'undefined' ? '' : obj.code);
	if(!code.length)return;
	if(!style.length)style = 'left:0;top:0;width:100%;height:170px;';
	let div = '<div type="video" style="'+style+'">'+code+'</div>';
	viewer.append(div);
	$('iframe').removeAttr('width').removeAttr('height').css({width:'100%', height:'100%'});
}
function setWeb(obj){
	let viewer = $('.wap-detail > ul > li:last'),
		style = (typeof obj.style === 'undefined' ? '' : obj.style),
		url = (typeof obj.url === 'undefined' ? '' : obj.url);
	if(!url.length)return;
	if(!style.length)style = 'left:0;top:0;width:100%;height:170px;';
	let div = '<div type="web" style="'+style+'"><iframe src="'+url+'" frameborder="0" style="width:100%;height:100%;"></iframe></div>';
	viewer.append(div);
}
function setMap(obj){
	let viewer = $('.wap-detail > ul > li:last'),
		style = (typeof obj.style === 'undefined' ? '' : obj.style),
		longitude = (typeof obj.longitude === 'undefined' ? '0' : obj.longitude),
		latitude = (typeof obj.latitude === 'undefined' ? '0' : obj.latitude),
		zoom = (typeof obj.zoom === 'undefined' ? 18 : obj.zoom);
	if(!style.length)style = 'left:0;top:0;width:100%;height:170px;';
	let div = $('<div type="map" style="'+style+'"></div>');
	viewer.append(div);
	div.baiduMapApi({
		longitude: longitude,
		latitude: latitude,
		zoom: zoom
	});
}
function setChart(obj){
	let viewer = $('.wap-detail > ul > li:last'),
		style = (typeof obj.style === 'undefined' ? '' : obj.style),
		type = (typeof obj.ctype === 'undefined' ? 'bar' : obj.ctype),
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
	let div = $('<div type="chart" style="'+style+'"><i style="display:block;width:100%;height:100%;"></i></div>');
	viewer.append(div);
	let chart = echarts.init(div.find('i')[0]);
	chart.setOption(option);
}
$(function(){
	let music = $('.music');
	music.click(function(){
		let _this = $(this), audio = $('#audio');
		if(_this.hasClass('isPlaying')){
			_this.removeClass('isPlaying');
			audio[0].pause();
			audio[0].currentTime = 0;
		}else{
			_this.addClass('isPlaying');
			audio[0].play();
		}
	});
	$('.wap-detail ul').on('mouseup touchend', 'a[page]', function(){
		if(window.changePaging)return false;
		if(window.scenePages.length<=1)return false;
		let pageIndex = Number($(this).attr('page')), direction = -1;
		if(window.scenePageIndex > pageIndex)direction = 1;
		changePage(direction, pageIndex);
		return false;
	});
	$(document).on('dragstart', 'img, a', function(){return false});
	document.oncontextmenu = function(){return false};
	try{
		let domain = $('#click_domain').length ? $('#click_domain').val() : '';
		window.sceneData = $.json($.base64Decode($('#data').val().substr(5)));
		if($.isArray(window.sceneData.jssdk)){
			wxShareInit(window.sceneData.jssdk, function(){
				$.postJSON(domain+'/api/wap/update', {code:window.sceneData.code}, function(){
					if(window.sceneData.share_url.length){
						location.href = window.sceneData.share_url;
					}
				});
			});
		}
		window.scenePages = window.sceneData.pages;
		if(window.sceneData.return_url.length){
			setTimeout(function(){
				history.pushState({}, null, window.location.href);
				window.onpopstate = function(){
					history.pushState(null, null, window.location.href);
					if((new Date().getTime()) - window.popTime < 500)return true;
					location.href = window.sceneData.return_url;
					return true;
				};
			}, 50);
		}
		let audio = $('#audio');
		if(audio.length && Number(window.sceneData.music_play) === 1){
			function musicPlay(){
				let p = navigator.platform.toLowerCase();
				if(p.indexOf('win') === -1 && p.indexOf('mac') === -1){
					music.addClass('isPlaying');
					audio[0].play();
				}
			}
			function musicInBrowserHandler(){
				musicPlay();
				document.body.removeEventListener('touchstart', musicInBrowserHandler, false);
			}
			document.body.addEventListener('touchstart', musicInBrowserHandler, false);
			musicPlay();
			document.addEventListener('WeixinJSBridgeReady', function(){
				musicPlay();
			}, false);
		}
		if($.isArray(window.scenePages)){
			$(document.body).append('<div class="num">1/'+window.scenePages.length+'</div>');
			appendPage(window.scenePages[0]);
			if(window.scenePages.length>1){
				let wrap = $('.wap-detail'), domain = $('#static_domain').length ? $('#static_domain').val() : '';
				$('.wap-detail > ul').after('<section><div>向上滑动继续浏览<img src="'+domain+'/images/touch.jpg" /></div></section>');
				wrap.on('mousedown', swipeStart).on('mouseleave', swipeEnd);
				if(window.addEventListener){
					wrap[0].addEventListener('touchstart', swipeStart, false);
					wrap[0].addEventListener('touchcancel', swipeEnd, false);
					document.addEventListener('touchmove', function(e){
						e.preventDefault();
					}, false);
				}
				let nextPages = window.scenePages.concat();
				nextPages.shift();
				$.each(nextPages, function(){
					if(this.bg && this.bg.length && this.bg.substr(0, 1)!=='#'){
						let img = new Image();
						img.src = this.bg;
					}
					let content = this.content;
					if(!content || !content.length)return true;
					content = $.json(content);
					if(!$.isArray(content))return true;
					$.each(content, function(){
						if(this.type === 'image'){
							let img = new Image();
							img.src = this.url;
						}
					});
				});
			}
		}
		$.postJSON(domain+'/api/wap/click', { id:window.sceneData.id });
	}catch(e){
		$.overloadError('场景数据错误');
		$.log(e);
	}
});