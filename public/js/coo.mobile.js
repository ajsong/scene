/*
Developed by @mario v12.5.20210113
*/
(function($){

//分页滚动
$.fn.touchmove = function(options){
	options = $.extend({
		list: null, //滚动列表
		type: 0, //切换效果, 0:滚动切换, 1:渐显切换
		index: 0, //默认显示
		dir: 0, //拖拽(滚动)方向, 水平[0|x|left|right], 垂直[1|y|top|bottom]
		visible: 1, //显示个数, [visible<scroll ? scroll : visible]
		scroll: 1, //滚动个数
		mouseWheel: false, //使用鼠标滚轮
		autoWH: true, //自动设置宽高, 为了一页只显示一个列表元素
		autoW: true, //自动设置宽, autoWH为true时无效
		autoH: true, //自动设置高, autoWH为true时无效
		title: '', //显示list的title为标题的标题类名, 留空即不显示
		opacity: 0.7, //标题容器透明度
		titleAnimate: 'move', //标题容器显示动画, [move|opacity]
		hide: true, //标题是否隐藏(产生动画显示,否则一直显示)
		keydown: false, //箭头键控制滚动分页列表, [上:第一页|下:最后一页|左:上一页|右:下一页]
		prev: '', //滚动到上一个分页列表的expr按钮
		next: '', //滚动到下一个分页列表的expr按钮
		disprev: '', //已经没有上一分页即增加这个样式, unlimit:false 时有效
		disnext: '', //已经没有下一分页即增加这个样式, unlimit:false 时有效
		pager: '', //存放分页按钮的容器的expr, 留空即不显示
		curpager: 'this', //当前分页按钮类名
		pagerText: [], //按钮文字,为空即使用数字,若使用,元素数量必须与list数量相同
		autoPagerWH: true, //按钮容器自动宽高
		autoPager: true, //只有一个列表时自动隐藏按钮容器
		offset: '', //按钮容器位置, [left|center|right]
		offsetW: 10, //按钮容器距离左右边
		offsetH: 10, //按钮容器距离上下边
		section: true, //分页滚动
		act: 'click', //分页按钮的操作方式
		easing: 'easeout', //切换效果, 可使用 $.easing 扩展
		speed: 500, //切换速度
		auto: 0, //自动切换速度(0:不自动切换), auto==speed无限滚动
		autoWait: 0, //自动滚动前停留
		progress: '', //自动切换时在容器下面显示时间动画的样式, 为空即不显示
		progressPager: false, //自动切换时分页按钮显示时间动画
		unlimit: false, //无限滚动
		hoverStop: true, //自动切换时鼠标移到停止滚动
		drag: false, //可否拖拽
		bounces: true, //回弹效果
		pagerAction: null, //分页按钮操作时执行
		autoReload: true, //窗口改变大小自动重载
		beforeForLast: null, //滚动前执行, this:前一次的li对象
		before: null, //滚动前执行
		move: null, //滚动时执行
		afterLeft: null, //向左滚动后执行
		afterRight: null, //向右滚动后执行
		after: null, //滚动后执行
		complete: null //插件加载后执行
	}, $('body').data('touchmove.options'), options);
	if(options.autoReload)(function(ths){
		setTimeout(function(){$.resize(function(){ths.touchmove(options)})}, 1000);
	})(this);
	return this.each(function(){
		$(this).stopBounces(true).removePlug('touchmove');
		let _this = $(this), width = _this.width(), height = _this.height(), list = options.list?_this.find(options.list):_this.find('li'), count = list.length,
			wrapper, title, pager = [], index = -Math.abs(options.index), indexLast = null, hovering = false, moved = false,
			direction = 0, startD = 0, d, red, lw, lh, time = 0, touchpx, lastX = 0, lastY = 0,
			dirs = {'0':'x', 'left':'x', 'right':'x', '1':'y', 'top':'y', 'bottom':'y'}, dir = dirs[options.dir] ? dirs[options.dir] : options.dir, progress = null,
			scroll = options.scroll, visible = options.visible<scroll ? scroll : options.visible, draging = false, moving = false, auto = 0, autoHandle = null, autoWaitHandle = null;
		if(count<=0)return true;
		if(options.index<0)index = 0;
		if(Math.abs(options.index)>=count)index = -(count-1);
		_this.css({overflow:'hidden'}).data({width:width, height:height});
		if(_this.css('position') === 'static')_this.css({position:'relative'});
		if(!list.parent().is('ul'))list.wrapAll('<div></div>');
		wrapper = list.parent().css({position:'relative', overflow:'hidden'});
		let padding = list.padding(), margin = list.margin();
		if(options.type === 0){
			if(dir === 'x'){
				wrapper.css({left:(options.unlimit?index-scroll:index)*(width/visible), top:0, width:(width/visible)*count});
				lw = (width/visible) - padding.left - padding.right - margin.left - margin.right;
				lh = height - padding.top - padding.bottom;
			}else{
				wrapper.css({left:0, top:(options.unlimit?index-scroll:index)*(height/visible), height:(height/visible)*count});
				lw = width - padding.left - padding.right;
				lh = (height/visible) - padding.top - padding.bottom - margin.top - margin.bottom;
			}
			if(options.autoWH)list.css({width:lw, height:lh});
			else{
				if(options.autoW)list.width(lw);
				if(options.autoH)list.height(lh);
			}
			list.css({'float':'left'}).each(function(){
				if(!!$(this).attr('title')){
					$(this).data('title', $(this).attr('title'));
					$(this).removeAttr('title');
				}
			});
			if(options.unlimit){
				wrapper.prepend(list.last().clone()).append(list.eq(0).clone());
				if(dir === 'x'){
					wrapper.width(wrapper.width()+(width/visible)*2);
				}else{
					wrapper.height(wrapper.height()+(height/visible)*2);
				}
			}
			if(dir === 'x'){
				if(wrapper.width() === width)count = 1;
			}else{
				if(wrapper.height() === height)count = 1;
			}
		}else{
			wrapper.css({height:height});
			list.css({display:'none',position:'absolute',top:0,left:0}).eq(Math.abs(index)).css({display:'','z-index':505});
			list.each(function(){
				if(!!$(this).attr('title')){
					$(this).data('title', $(this).attr('title'));
					$(this).removeAttr('title');
				}
			});
		}
		if(options.title.length){
			_this.find('.'+options.title).remove();
			title = $('<div></div>').addClass(options.title);
			wrapper.after(title);
			let tpadding = title.padding();
			title.css({
				position: 'absolute',
				'z-index': 555,
				left: 0,
				top: height,
				width: width - tpadding.left - tpadding.right,
				opacity: options.opacity
			});
		}
		if(options.progress.length){
			progress = $('<div></div>').addClass(options.progress);
			wrapper.after(progress);
			progress.css({
				position: 'absolute',
				'z-index': 556,
				left: 0,
				width: 0,
				overflow: 'hidden'
			});
		}
		if(options.pager.length)pager = $(options.pager);
		if(pager.length){
			if(!pager.find('a').length){
				for(let i=0; i<count; i++){
					let text = options.pagerText.length ? options.pagerText[i] : i+1;
					if(i === Math.abs(index))pager.append('<a href="javascript:void(0)" class="'+options.curpager+'"><span></span><font>'+text+'</font></a>');
					else pager.append('<a href="javascript:void(0)"><span></span><font>'+text+'</font></a>');
				}
			}
			if(options.autoPagerWH){
				setTimeout(function(){
					let tm = 0, pagerW = 0, pagerH = 0;
					pager.find('a').each(function(){
						pagerW += $(this).outerWidth(true);
						tm = $(this).outerHeight(true);
						pagerH = tm>pagerH ? tm : pagerH;
					});
					//pager.css({width:pagerW, height:pagerH});
					if(options.offset.length){
						let offsetW = options.offsetW||0, offsetH = options.offsetH||0, uw = 0, uh = height - pagerH - offsetH;
						switch(options.offset){
							case 'left':uw = offsetW;break;
							case 'center':uw = Number((width-pagerW)/2);break;
							default:uw = width - pagerW - offsetW;
						}
						pager.css({position:'absolute', 'z-index':10, left:uw, top:uh});
					}
				}, 300);
			}
		}
		if(options.autoPager && count === 1)pager.hide();
		let tit = list.eq(Math.abs(index)).data('title');
		if(options.title.length && !!tit)title.html(tit).animate({top:height-title.outerHeight(false)}, 200);
		let clearEvent = function(){},
		startDrag = function(e){
			if(autoHandle){clearInterval(autoHandle);autoHandle = null}
			if((e.type === 'mousedown' && e.button !== 0) || (e.type === 'touchstart' && e.which !== 0))return false;
			beforeMove();
			lastX = 0;
			lastY = 0;
			moved = false;
			moving = false;
			time = new Date().getTime();
			if(dir === 'x'){
				startD = wrapper.position().left;
				d = $.touches(e).x;
				red = $.touches(e).y;
			}else{
				startD = wrapper.position().top;
				d = $.touches(e).y;
				red = $.touches(e).x;
				e.preventDefault();
			}
			wrapper.stop(true, false).on('mousemove', moveDrag).css('cursor', 'move');
			if(window.addEventListener)wrapper[0].addEventListener('touchmove', moveDrag, true);
			draging = true;
			return false;
		},
		moveDrag = function(e){
			e.preventDefault();
			if((e.touches && e.touches.length>1) || (e.scale && e.scale !== 1))return;
			if(!!_this.data('stopMove'))return false;
			let movepx, curpx, recurpx;
			moved = true;
			direction = 0;
			if(dir === 'x'){
				curpx = $.touches(e).x;
				if(!moving){
					recurpx = $.touches(e).y;
					if(recurpx>red+5 || recurpx<red-5)return false;
					else e.preventDefault();
				}
				touchpx = movepx = lastX = curpx - d;
				if(movepx>0 && movepx>=(width/5))direction = 1;
				else if(movepx<0 && Math.abs(movepx)>=(width/5))direction = -1;
			}else{
				curpx = $.touches(e).y;
				if(!moving){
					recurpx = $.touches(e).x;
					if(recurpx>red+5 || recurpx<red-5)return false;
				}
				touchpx = movepx = lastY = curpx - d;
				if(movepx>0 && movepx>=(height/5))direction = 1;
				else if(movepx<0 && Math.abs(movepx)>=(height/5))direction = -1;
			}
			moving = true;
			if( (index>=0 && movepx>0) || ((Math.abs(index)+visible)>=count && movepx<0) )movepx *= 0.2;
			if( !options.bounces && ((index>=0 && movepx>0) || ((Math.abs(index)+visible)>=count && movepx<0)) )movepx = 0;
			if(dir === 'x')wrapper.css('left', startD+movepx);
			else wrapper.css('top', startD+movepx);
			if($.isFunction(options.move))options.move.call(list.eq(Math.abs(index)), Math.abs(index));
			return false;
		},
		endDrag = function(e){
			if(moved)e.preventDefault();
			if((e.type === 'mousedown' && e.button !== 0) || (e.type === 'touchstart' && e.which !== 0))return false;
			if(draging){
				wrapper.off('mousemove', moveDrag).css('cursor', '');
				if(window.addEventListener)wrapper[0].removeEventListener('touchmove', moveDrag, true);
				if(!moved)return;
				if(direction === 1)index += scroll;
				else if(direction === -1)index -= scroll;
				if(index>=0 && (direction === 1))index = 0;
				if((Math.abs(index)+visible)>=count && (direction === -1))index = (-count + visible);
				draging = false;
				time = new Date().getTime() - time;
				moveWrapper();
			}
			return false;
		},
		beforeMove = function(){
			if($.isFunction(options.beforeForLast) && indexLast!=null)options.beforeForLast.call(list.eq(Math.abs(indexLast)), Math.abs(indexLast));
			if($.isFunction(options.before))options.before.call(list.eq(Math.abs(index)), Math.abs(index));
			if(options.title.length && options.hide){
				options.titleAnimate === 'opacity' ? title.stop(true, false).animate({opacity:0}, 200) : title.stop(true, false).animate({top:height}, 200);
			}
		},
		moveWrapper = function(){
			if(options.type === 0){
				let width = _this.data('width'), height = _this.data('height'), tit = list.eq(Math.abs(index)).data('title'), px, v = 1,
				prop = dir === 'x' ? {left:index*(width/visible)} : {top:index*(height/visible)};
				if(!options.section){
					if(time<300)v = 1.5;
					if(dir === 'x'){
						px = wrapper.position().left + touchpx * v;
						if(touchpx>0 && px>0)px = 0;
						else if(touchpx<0 && px<width-wrapper.width())px = width-wrapper.width();
						prop = {left:px};
					}else{
						px = wrapper.position().top + touchpx * v;
						if(touchpx>0 && px>0)px = 0;
						else if(touchpx<0 && px<height-wrapper.height())px = height-wrapper.height();
						prop = {top:px};
					}
				}
				touchpx = 0;
				wrapper.stop(true, false).animate(prop, options.speed, options.easing, function(){
					if(options.section){
						if(pager.length){
							let idx = (options.unlimit?index+1:index), curA = pager.find('a');
							if(options.unlimit && Math.abs(idx) === count)idx = 0;
							curA.removeClass(options.curpager);
							curA.eq(Math.abs(idx)).addClass(options.curpager);
							if(options.auto>0 && options.progressPager && !hovering)progressPager((Math.abs(idx)+1>=count?0:Math.abs(idx)+1));
						}
						if(options.title.length && !!tit){
							title.html(tit);
							if(options.hide){
								if(options.titleAnimate === 'opacity'){
									title.stop(true, false).animate({opacity:options.opacity}, 200);
								}else{
									title.stop(true, false).animate({top:height-title.outerHeight(false)}, 200);
								}
							}
						}
						if(!options.unlimit){
							if(options.prev.length && options.disprev.length){
								if(index >= 0)$(options.prev).addClass(options.disprev);
								else $(options.prev).removeClass(options.disprev);
							}
							if(options.next.length && options.disnext.length){
								if((Math.abs(index)+visible) >= count)$(options.next).addClass(options.disnext);
								else $(options.next).removeClass(options.disnext);
							}
						}
					}
					if(direction === -1){
						if($.isFunction(options.afterLeft))options.afterLeft.call(list.eq(Math.abs(index)), Math.abs(index));
					}else{
						if($.isFunction(options.afterRight))options.afterRight.call(list.eq(Math.abs(index)), Math.abs(index));
					}
					if($.isFunction(options.after))options.after.call(list.eq(Math.abs(index)), Math.abs(index));
					if(options.auto>0 && !hovering){
						wrapper.stop(true, false);
						clearInterval(autoHandle);autoHandle = null;
						autoHandle = setInterval(function(){autoMove()}, auto);
						if(options.progress.length)progressHandle();
					}
					_this.removeData('prev').removeData('next').removeData('mousewheel');
					indexLast = index;
					direction = 0;
				});
			}else{
				let height = _this.height(), tit = list.eq(Math.abs(index)).data('title'),
				curList = list.filter(':visible'), nextList = list.eq(Math.abs(index));
				nextList.css({display:'', 'z-index':504, opacity:1});
				curList.fadeOut(options.speed, function(){
					nextList.css({'z-index':505});
					if(pager.length){
						let idx = index, curA = pager.find('a');
						curA.removeClass(options.curpager);
						curA.eq(Math.abs(idx)).addClass(options.curpager);
						if(options.auto>0 && options.progressPager && !hovering)progressPager((Math.abs(idx)+1>=count?0:Math.abs(idx)+1));
					}
					if(options.title.length && !!tit){
						title.html(tit);
						if(options.hide){
							if(options.titleAnimate === 'opacity'){
								title.stop(true, false).animate({opacity:options.opacity}, 200);
							}else{
								title.stop(true, false).animate({top:height-title.outerHeight(false)}, 200);
							}
						}
					}
					if(direction === -1){
						if($.isFunction(options.afterLeft))options.afterLeft.call(list.eq(Math.abs(index)), Math.abs(index));
					}else{
						if($.isFunction(options.afterRight))options.afterRight.call(list.eq(Math.abs(index)), Math.abs(index));
					}
					if($.isFunction(options.after))options.after(Math.abs(index));
					if(options.auto>0 && !hovering){
						clearInterval(autoHandle);autoHandle = null;
						autoHandle = setInterval(function(){autoMove()}, auto);
						if(options.progress.length)progressHandle();
					}
					_this.removeData('prev').removeData('next').removeData('mousewheel');
					indexLast = index;
					direction = 0;
				});
			}
		};
		if($.isFunction(options.complete))options.complete.call(_this);
		if(options.keydown)$(document).keydown(function(e){
			e = e||window.event;
			let code = e.which||e.keyCode;
			if(code === 37 || code === 38){
				if(!!!_this.data('prev')){
					if(index>=0)return;
					else{
						cancelProgress();
						beforeMove();
						if(code === 37){
							if(index+scroll >= 0)index = 0;
							else index += scroll;
						}else index = 0;
					}
					_this.data('prev', true);
					direction = -1;
					moveWrapper();
					if(e.preventDefault)e.preventDefault();
					e.returnValue = false;
					return false;
				}
			}else if(code === 39 || code === 40){
				if(!!!_this.data('next')){
					if(Math.abs(index)+visible >= count)return;
					else{
						cancelProgress();
						beforeMove();
						if(code === 39){
							if(Math.abs(index)+visible+scroll >= count)index -= count+index-visible;
							else index -= scroll;
						}else index = -count+1;
					}
					_this.data('next', true);
					direction = 1;
					moveWrapper();
					if(e.preventDefault)e.preventDefault();
					e.returnValue = false;
					return false;
				}
			}
		});
		if(!options.unlimit){
			if(options.prev.length && options.disprev.length){
				if(index >= 0)$(options.prev).addClass(options.disprev);
			}
			if(options.next.length && options.disnext.length){
				if((Math.abs(index)+visible) >= count)$(options.next).addClass(options.disnext);
			}
		}
		if(options.prev.length)$(options.prev).on('click', function(){
			if(!!!_this.data('prev')){
				if(index>=0)return false;
				else{
					cancelProgress();
					beforeMove();
					if(index+scroll >= 0)index = 0;
					else index += scroll;
				}
				_this.data('prev', true);
				direction = -1;
				moveWrapper();
				//if(e.preventDefault)e.preventDefault();
				//e.returnValue = false;
				return false;
			}
		});
		if(options.next.length)$(options.next).on('click', function(){
			if(!!!_this.data('next')){
				if(Math.abs(index)+visible >= count)return false;
				else{
					cancelProgress();
					beforeMove();
					if(Math.abs(index)+visible+scroll >= count)index -= count+index-visible;
					else index -= scroll;
				}
				_this.data('next', true);
				direction = 1;
				moveWrapper();
				//if(e.preventDefault)e.preventDefault();
				//e.returnValue = false;
				return false;
			}
		});
		if(options.mouseWheel)_this.mousewheel(function(e, d){
			if(!!!_this.data('mousewheel')){
				if(d>0){
					if(index>=0)return;
					else{
						cancelProgress();
						beforeMove();
						if(index+scroll>=0)index += -index;
						else index += scroll;
					}
					direction = 1;
				}else{
					if(Math.abs(index)+visible>=count)return;
					else{
						cancelProgress();
						beforeMove();
						if(Math.abs(index)+visible+scroll>=count)index -= count+index-visible;
						else index -= scroll;
					}
					direction = -1;
				}
				_this.data('mousewheel', true);
				moveWrapper();
			}
		});
		if(mobileDevice())options.act = 'click';
		if(pager.length && options.act.length){
			pager.find('a').on(options.act, function(){
				let thisIndex = $(this).index();
				if(!!!_this.data('pager.a'+thisIndex)){
					_this.data('pager.a'+thisIndex, true);
					cancelProgress();
					beforeMove();
					index = -thisIndex;
					if(visible>1 && index<=-(count-visible))index = -(count-visible);
					if(options.unlimit){
						if(index === 0)index = -1;
						else if(index === -count)index = -count-1;
						else index--;
					}
					if(indexLast !== null){
						if(indexLast<index)direction = -1;
						else direction = 1;
					}
					if($.isFunction(options.pagerAction))options.pagerAction.call($(this), Math.abs(index));
					moveWrapper();
					//if(e.preventDefault)e.preventDefault();
					//e.returnValue = false;
					return false;
				}
			});
		}
		if(options.auto>0){
			auto = options.auto;
			if(options.dir === 'right' || options.dir === 'bottom')direction = 1;
			else direction = -1;
			if(auto<options.speed)auto = options.speed;
			if(options.progress.length && options.autoWait>0)progressHandle();
			if(options.progressPager && pager.length){
				pager.find('a').css('position', 'relative');
				progressPager((Math.abs(index)+1>=count?0:Math.abs(index)+1));
			}
			autoWaitHandle = setTimeout(function(){
				autoMove();
				autoHandle = setInterval(function(){autoMove()}, auto);
			}, options.autoWait);
		}
		if(options.hoverStop){
			_this.hover(function(){hoverHandle(true)},function(){hoverHandle(false)});
			if(options.title.length)title.hover(function(){hoverHandle(true)},function(){hoverHandle(false)});
		}
		if(options.drag && options.type === 0){
			_this.unselect().on('mouseup mouseleave', endDrag);
			wrapper.on('mousedown', startDrag).on('mouseup mouseleave', endDrag).on('click', clearEvent);
			wrapper.on('dragstart', 'img, a', function(e){e.preventDefault()});
			if(window.addEventListener){
				_this[0].addEventListener('touchend', endDrag, true);
				wrapper[0].addEventListener('touchstart', startDrag, true);
				wrapper[0].addEventListener('touchend', endDrag, true);
				wrapper[0].addEventListener('touchcancel', endDrag, true);
			}
		}
		function hoverHandle(bool){
			if(options.auto>0){
				hovering = bool;
				if(bool){
					clearInterval(autoWaitHandle);autoWaitHandle = null;
					clearInterval(autoHandle);autoHandle = null;
					if(pager.length)pager.find('a span').stop(true, false).fadeOut(200);
					if(options.progress.length)progress.stop(true, false).fadeOut(200);
				}else{
					autoHandle = setInterval(function(){autoMove()}, auto);
					if(options.progressPager && pager.length)progressPager((Math.abs(index)+1>=count?0:Math.abs(index)+1));
					if(options.progress.length)progressHandle();
				}
			}
		}
		function autoMove(){
			beforeMove();
			if(Math.abs(index)+visible>=count+(options.unlimit?3:0)){
				index = 0;
			}else{
				if(Math.abs(index)+visible+scroll>=count+(options.unlimit?3:0)){
					if(options.unlimit){
						index = count+index-visible;
						wrapper.stop(true, false);
						if(dir === 'x'){
							wrapper.css('left', -(width/visible));
						}else{
							wrapper.css('top', -(height/visible));
						}
					}else index -= count+index-visible;
				}else if(options.unlimit && index === 0){
					index = -(count-1);
					wrapper.stop(true, false);
					if(dir === 'x'){
						wrapper.css('left', -(wrapper.width()-(width/visible)*2));
					}else{
						wrapper.css('top', -(wrapper.height()-(height/visible)*2));
					}
				}else{
					if(options.unlimit){
						index = (direction === -1 ? index-scroll : index+scroll);
					}else index -= scroll;
				}
			}
			moveWrapper();
		}
		function cancelProgress(){
			clearInterval(autoWaitHandle);
			if(options.auto>0 && options.progress.length)progress.stop(true, false).hide();
		}
		function progressHandle(){
			progress.width(0).show().animate({width:width}, options.auto, 'linear');
		}
		function progressPager(idx){
			pager.find('a span').hide();
			let curA = pager.find('a').eq(idx), span = curA.find('span').width(0).show();
			span.animate({width:curA.width()+1}, options.auto, 'linear');
		}
		function mobileDevice(mark){
			let na = navigator.userAgent.toLowerCase();
			if(typeof mark !== 'undefined'){
				return na.match(new RegExp(mark,'i')) === mark;
			}else{
				return $.browser.mobile;
			}
		}
	});
};

//上拉刷新
$.fn.pullRefresh = function(options){
	if(typeof options === 'undefined'){
		let _this = this;
		return {
			headerBegin: function(){ //开始下拉刷新
				return _this.pullRefresh('headerBegin');
			},
			headerEnd: function(){ //结束下拉刷新
				return _this.pullRefresh('headerEnd');
			},
			footerBegin: function(){ //开始加载更多
				return _this.pullRefresh('footerBegin');
			},
			footerEnd: function(){ //结束加载更多
				return _this.pullRefresh('footerEnd');
			},
			refresh: function(){ //开始无动画下拉刷新
				return _this.pullRefresh('refresh');
			},
			refreshCallback: function(){ //执行下拉刷新后的回调
				return _this.pullRefresh('refreshCallback');
			},
			complete: function(){ //插件加载执行
				return _this.pullRefresh('pullRefreshComplete');
			}
		};
	}
	if(typeof options === 'string'){
		if(!!this.data(options) && $.isFunction(this.data(options)))this.data(options)();
		return this;
	}
	options = $.extend({
		header: false, //使用顶部, 如果使用html,即自定义
		footer: false, //使用底部
		dragFooter: false, //使用拖曳底部, 如果使用html,即自定义
		headerText: '下拉可以刷新', //顶部默认文字
		footerText: '上拉加载更多', //底部默认文字
		headerTipText: '松开立即刷新', //顶部下拉时的提示文字
		footerTipText: '松开加载更多', //底部上拉时的提示文字
		headerUpdatingText: '正在刷新中...', //顶部加载中的文字
		footerUpdatingText: '正在加载中...', //底部加载中的文字
		headerUpdateTime: '最后更新 %t', //顶部更新时间, %t将替换为当前时间
		footerUpdateTime: '最后加载 %t', //底部更新时间, %t将替换为当前时间
		headerView: '', //头部控件, html代码
		footerNoMoreText: '', //底部加载后滚动条高度没有变化时显示, 为空即不显示
		noData: null, //没有数据时执行(pullWrap高度没变化)
		noMore: null, //没有更多数据时执行(pullWrap高度没变化)
		scroll: null, //滚动时执行, 接受两个参数:headerView, headerViewHolder
		start: null, //准备拖动时执行
		move: null, //拖动时执行, 接受一个参数:当前拖曳距离
		release: null, //松开时执行
		complete: null, //插件加载后执行
		restore: null, //恢复正常状态时执行
		refresh: null, //下拉执行, 附带一个参数(该参数是一个函数,且执行完后需执行这个函数来关闭显示区域)
		load: null //上拉执行, 附带一个参数(同上)
	}, $('body').data('pullRefresh.options'), options);
	return this.each(function(){
		$(this).data('pullRefresh', true).stopBounces(true).removePlug('pullRefresh');
		if(!options.noData && $(document.body).data('pullRefresh.noData'))options.noData = $(document.body).data('pullRefresh.noData');
		if(!options.noMore && $(document.body).data('pullRefresh.noMore'))options.noMore = $(document.body).data('pullRefresh.noMore');
		let _this = $(this), binded = false, height = _this.outerHeight(false), wrap = null, headerArea = null, footerArea = null,
			headerView = null, headerViewHolder = null, headerCustom = false, footerCustom = false, scrollFooterArea = false, moved = false;
		_this.css('position', 'relative').parent().css('position', 'relative');
		if(_this.parent().prev().is('.navBar') && !_this.parent().hasClass('non-padding-top'))_this.parent().addClass('main-padding-top');
		if(!_this.children().length)return true;
		_this.children().wrapAll('<div class="pullWrap clear-after"></div>');
		wrap = _this.find('.pullWrap');
		wrap.unselect().on('dragstart', 'img, a', function(e){e.preventDefault()});
		if(options.header){
			if(typeof options.header === 'boolean'){
				headerArea = $('<div class="pullHeader"><div><i></i><i class="x"></i><span><font>'+options.headerText+'</font><strong></strong></span></div></div>');
			}else{
				headerCustom = true;
				headerArea = $('<div class="pullHeader">'+options.header+'</div>');
				if(headerArea.children().length === 1 && headerArea.children().is('.preloader'))headerArea.height(64);
			}
			wrap.prepend(headerArea);
		}
		if(options.footer || options.dragFooter){
			if(typeof options.footer === 'boolean' || typeof options.dragFooter === 'boolean'){
				footerArea = $('<div class="pullFooter"><div><i></i><i class="x"></i><span><font>'+options.footerText+'</font><strong></strong></span></div><span>'+options.footerNoMoreText+'</span></div>');
			}else{
				footerCustom = true;
				let footerHtml = typeof options.footer !== 'boolean' ? options.footer : options.dragFooter;
				footerArea = $('<div class="pullFooter">'+footerHtml+'</div>');
			}
			if(options.footer){
				scrollFooterArea = true;
				wrap.append(footerArea);
				footerArea.addClass('pullScrollFooter');
			}else{
				wrap.append(footerArea);
				footerArea.css('bottom', 99999);
			}
		}
		if(options.headerView){
			let offset = _this.offset();
			headerView = $(options.headerView);
			_this.before(headerView);
			let headerHeight = headerView.css({position:'absolute', 'z-index':-1, left:offset.left, top:offset.top}).outerHeight(false);
			headerViewHolder = $('<div style="width:100%;height:'+headerHeight+'px;overflow:hidden;"></div>');
			if(headerArea){
				headerArea.after(headerViewHolder);
			}else{
				wrap.prepend(headerViewHolder);
			}
		}

		let emptyHeight = wrap.css('height', 'auto').height()-(footerArea?wrap.find('.pullFooter').height():0), scrollHeight = _this[0].scrollHeight, prevScrollHeight = scrollHeight, loading = false,
		d = 0, transformY = 0, scrollTop, originScroll = false, originScrollY = 0, originScrollLastY = 0, originScrollTimer = null, originScrollDirection = 0,
		startDrag = function(e){
			e.preventDefault();
			loading = false;
			moved = false;
			transformY = wrap.transform().y;
			scrollTop = _this.scrollTop();
			d = $.touches(e).y;
			wrap.on('mousemove', moveDrag).on('mouseleave', endDrag);
			if(window.addEventListener)wrap[0].addEventListener('touchmove', moveDrag, true);
			if($.isFunction(options.start))options.start.call(_this);
			return false;
		},
		moveDrag = function(e){
			e.preventDefault();
			scrollHeight = _this[0].scrollHeight;
			moved = true;
			let curTop = 0, moveY = 0, curY = $.touches(e).y, direction = $.getDirection(0, d, 0, curY);
			//if( (options.header && scrollTop<=0 && curY<d) || (options.footer && scrollTop>=scrollHeight-height && curY>d) ){
			if( scrollTop<=0 && curY<d ){
				originScrollY = curY - originScrollLastY;
				originScrollLastY = curY;
				originScroll = true;
				originScrollDirection = 0;
				_this.scrollTop(d-curY);
				return;
			}
			if( scrollTop>=scrollHeight-height && curY>d && !scrollFooterArea ){
				originScrollY = originScrollLastY - curY;
				originScrollLastY = curY;
				originScroll = true;
				originScrollDirection = 1;
				_this.scrollTop(scrollTop+(d-curY));
				return;
			}
			moveY = ($.browser.mobile ? (curY-d)/2 : curY-d);
			curTop = transformY + moveY * ($.window().screenHeight<=500 ? 0.7 : 0.4);
			if(headerArea && scrollTop<=0 && curY>d && direction === 2){
				if(!headerCustom){
					let headerHeight = headerArea.height();
					if(Math.abs(curTop)>=headerHeight){
						headerArea.find('i:eq(0)').addClass('h');
						headerArea.find('font').html(options.headerTipText);
					}else{
						headerArea.find('i:eq(0)').removeClass('h');
						headerArea.find('font').html(options.headerText);
					}
				}
			}
			if(footerArea && scrollTop>=scrollHeight-height && curY<d && direction === 1){
				let footerHeight = footerArea.height();
				if(!footerCustom){
					if(Math.abs(curTop)>=footerHeight){
						footerArea.find('i:eq(0)').addClass('h');
						footerArea.find('font').html(options.footerTipText);
					}else{
						footerArea.find('i:eq(0)').removeClass('h');
						footerArea.find('font').html(options.footerText);
					}
				}
				if(!scrollFooterArea){
					footerArea.css('bottom', -footerHeight);
				}
			}
			wrap.css({transform:'translate3d(0,'+curTop+'px,0)', '-webkit-transform':'translate3d(0,'+curTop+'px,0)', 'transition-duration':'0s', '-webkit-transition-duration':'0s'});
			if(headerView)headerView.height(headerViewHolder.height()+curTop);
			if($.isFunction(options.move))options.move.call(_this, curTop);
			return false;
		},
		endDrag = function(e){
			e.preventDefault();
			unbind();
			if(originScroll){
				//$('body').addClass('disable-hover');
				wrap.on('mousedown', originScrollStop);
				if(window.addEventListener)wrap[0].addEventListener('touchstart', originScrollStop, true);
				if(originScrollTimer){clearInterval(originScrollTimer);originScrollTimer = null}
				originScrollTimer = setInterval(function(){
					let _scrollTop = _this.scrollTop();
					if(Math.abs(originScrollY)<=1){
						clearInterval(originScrollTimer);originScrollTimer = null;
						originScrollY = originScrollLastY = 0;
						//$('body').removeClass('disable-hover');
						wrap.off('mousedown', originScrollStop);
						if(window.removeEventListener)wrap[0].removeEventListener('touchstart', originScrollStop, true);
					}else{
						if(!originScrollDirection){
							_this.scrollTop(_scrollTop+Math.abs(originScrollY));
						}else{
							_this.scrollTop(_scrollTop-Math.abs(originScrollY));
						}
						originScrollY *= 0.95;
					}
				}, $.browser.mobile ? 30 : 10);
				originScroll = false;
				return;
			}
			if((e.type === 'mousedown' && e.button !== 0) || (e.type === 'touchstart' && e.which !== 0))return;
			if($.isFunction(options.release))options.release.call(_this);
			if(!moved)return;
			let transform = wrap.transform().y;
			if(headerArea){
				let headerHeight = headerArea.height();
				if(transform>=headerHeight){
					headerBegin();
				}else{
					if(headerView)headerView.heightAnimate(headerViewHolder.height());
				}
			}
			if(footerArea){
				let footerHeight = footerArea.height();
				if(transform<=-footerHeight && !footerArea.hasClass('pullNoMore')){
					footerBegin();
				}
			}
			if(!loading)restore();
			return false;
		},
		originScrollStop = function(){
			if(originScrollTimer){clearInterval(originScrollTimer);originScrollTimer = null}
			wrap.off('mousedown', originScrollStop);
			if(window.removeEventListener)wrap[0].removeEventListener('touchstart', originScrollStop, true);
		};
		_this.add(_this.parent()).add($('html, body')).addClass('height-wrap');
		wrap.css('height', '');
		if(_this.scrollTop() === 0)bind();
		setTimeout(function(){
			height = _this.outerHeight(false);
			scrollHeight = _this[0].scrollHeight;
			prevScrollHeight = scrollHeight;
		}, 10);
		//let debug = $.debugHTML();
		_this.scroll(function(){
			if($.isFunction(options.scroll))options.scroll.call(_this, headerView, headerViewHolder);
			if(loading)return;
			//unbind();
			let _scrollTop = _this.scrollTop();
			height = _this.outerHeight(false);
			scrollHeight = _this[0].scrollHeight;
			if(headerArea && _scrollTop<=0)bind();
			//debug.html(_scrollTop+' '+height+' '+(scrollHeight-height));
			if(footerArea && scrollHeight>height){
				if(!scrollFooterArea){
					if(_scrollTop>=(scrollHeight-height))bind();
				}else if(!footerArea.hasClass('pullNoMore')){
					if(_scrollTop>=(scrollHeight-height-5))footerBegin();
				}
			}
		});
		if(headerArea){
			_this.data('headerBegin', headerBegin);
			_this.data('headerEnd', restore);
			_this.data('refresh', setRefresh);
		}
		if(footerArea){
			_this.data('footerBegin', footerBegin);
			_this.data('footerEnd', restore);
		}
		_this.data('refreshCallback', refreshCallback);
		if(!options.header && footerArea)unbind();
		if(footerArea){
			if(wrap.height()<wrap.parent().height())footerArea.hide();
			wrap.resize(function(){
				let listHeight = wrap.height();
				if(headerArea)listHeight -= headerArea.height();
				if(footerArea)listHeight -= footerArea.height();
				if(listHeight<=_this.height()){
					footerArea.addClass('pullNoMore').hide();
					wrap.css('height', '100%');
					if($.isFunction(options.noMore))options.noMore.call(_this);
				}else{
					footerArea.removeClass('pullNoMore').css('display', '');
					wrap.css('height', '');
				}
				prevScrollHeight = _this[0].scrollHeight;
			});
		}
		if($.isFunction(options.complete)){
			options.complete.call(_this, refreshCallback);
			_this.data('pullRefreshComplete', function(){
				options.complete.call(_this, refreshCallback);
			});
		}
		function headerBegin(){
			let headerHeight = headerArea.height();
			loading = true;
			wrap.css({transform:'translate3d(0,'+headerHeight+'px,0)', '-webkit-transform':'translate3d(0,'+headerHeight+'px,0)', 'transition-duration':'', '-webkit-transition-duration':''});
			if(headerView)headerView.heightAnimate(headerViewHolder.height()+headerHeight);
			if(!headerCustom){
				headerArea.find('i:eq(0)').removeClass('h').hide().next().show();
				headerArea.find('font').html(options.headerUpdatingText);
			}
			setRefresh();
		}
		function setRefresh(){
			if($.isFunction(options.refresh)){
				options.refresh.call(_this, refreshCallback);
			}else{
				if(headerView)headerView.heightAnimate(headerViewHolder.height());
				restore();
			}
		}
		function refreshCallback(callback){
			_this.removeClass('pullNoData');
			let listHeight = wrap.height();
			if(listHeight<=emptyHeight){
				_this.addClass('pullNoData');
				if($.isFunction(options.noData))options.noData.call(_this);
			}
			if(headerArea){
				if(!headerCustom)headerArea.find('strong').html(options.headerUpdateTime.replace(/%t/ig, getNow()));
				listHeight -= headerArea.height();
			}
			if(footerArea){
				footerArea.removeClass('pullNoMore').show();
				if(options.footerNoMoreText.length){
					footerArea.children('span').hide();
					footerArea.children('div').show();
				}else{
					footerArea.slideDown(300);
				}
				listHeight -= footerArea.height();
				if(listHeight<=_this.height())footerArea.addClass('pullNoMore').hide();
			}
			if(headerView)headerView.heightAnimate(headerViewHolder.height());
			restore();
			if($.isFunction(callback))callback.call(_this);
		}
		function footerBegin(){
			let footerHeight = footerArea.height();
			loading = true;
			if(!scrollFooterArea){
				wrap.css({transform:'translate3d(0,-'+footerHeight+'px,0)', '-webkit-transform':'translate3d(0,-'+footerHeight+'px,0)', 'transition-duration':'', '-webkit-transition-duration':''});
			}
			if(!footerCustom){
				footerArea.find('i:eq(0)').removeClass('h').hide().next().show();
				footerArea.find('font').html(options.footerUpdatingText);
			}
			if($.isFunction(options.load)){
				options.load.call(_this, function(callback){
					if(!footerCustom)footerArea.find('strong').html(options.footerUpdateTime.replace(/%t/ig, getNow()));
					if(scrollHeight>=_this[0].scrollHeight){
						footerArea.addClass('pullNoMore');
						if(options.footerNoMoreText.length){
							footerArea.children('span').css('display', 'block');
							footerArea.children('div').hide();
						}else{
							footerArea.slideUp(300);
						}
						if($.isFunction(options.noMore))options.noMore.call(_this);
					}else{
						footerArea.removeClass('pullNoMore').show();
						if(options.footerNoMoreText.length){
							footerArea.children('span').hide();
							footerArea.children('div').show();
						}
					}
					if(prevScrollHeight === _this[0].scrollHeight)setTimeout(function(){bind()}, 310);
					prevScrollHeight = _this[0].scrollHeight;
					restore();
					if($.isFunction(callback))callback.call(_this);
				});
			}else{
				restore();
			}
		}
		function restore(){
			setTimeout(function(){
				loading = false;
				wrap.css({transform:'', '-webkit-transform':'', 'transition-duration':'', '-webkit-transition-duration':''});
				if(headerArea){
					if(!headerCustom){
						headerArea.find('i:eq(0)').show().next().hide();
						headerArea.find('font').html(options.headerText);
					}
					if(_this.scrollTop() === 0)bind();
				}
				if(footerArea){
					if(!scrollFooterArea){
						setTimeout(function(){
							footerArea.css('bottom', 99999);
						}, 310);
					}
					if(!footerCustom){
						footerArea.find('i:eq(0)').show().next().hide();
						footerArea.find('font').html(options.footerText);
					}
					/*setTimeout(function(){
						if(_this.scrollTop()<=scrollHeight-height && scrollHeight>height)bind();
					}, 310);*/
				}
				if($.isFunction(options.restore))options.restore.call(_this);
				if($.isFunction($(document.body).data('pullRefresh.finish')))$(document.body).data('pullRefresh.finish').call(_this);
				scrollHeight = _this[0].scrollHeight;
			}, 10);
		}
		function bind(){
			if(!wrap.length || binded)return;
			binded = true;
			wrap.on('mousedown', startDrag).on('mouseup', endDrag);
			if(window.addEventListener){
				wrap[0].addEventListener('touchstart', startDrag, true);
				wrap[0].addEventListener('touchend', endDrag, true);
			}
		}
		function unbind(){
			if(!wrap.length || !binded)return;
			binded = false;
			wrap.off('mousedown', startDrag).off('mouseup', endDrag).off('mouseleave', endDrag).off('mousemove', moveDrag);
			if(window.removeEventListener){
				wrap[0].removeEventListener('touchstart', startDrag, true);
				wrap[0].removeEventListener('touchend', endDrag, true);
				wrap[0].removeEventListener('touchmove', moveDrag, true);
			}
		}
		function getNow(){
			let now = new Date();
			return now.getFullYear()+'-'+$.fillZero(now.getMonth()+1,2)+'-'+$.fillZero(now.getDate(),2)+' '+$.fillZero(now.getHours(),2)+':'+$.fillZero(now.getMinutes(),2)+':'+$.fillZero(now.getSeconds(),2);
		}
	});
};

//动画设定高度
$.fn.heightAnimate = function(height, callback){
	return this.each(function(){
		$(this).animate({height:height}, 350, 'easeout', callback);
	});
};

//按原宽高的比例自动设定宽度, originWidth,originHeight可使用百分比(字符串)
$.fn.autoWidth = function(originWidth, originHeight, screenWidth){
	return this.each(function(){
		let _this = $(this), width = _this.outerWidth(false);
		//if(!_this.is(':visible'))return true;
		if(typeof screenWidth === 'undefined')screenWidth = $.window().width;
		let percent = width / screenWidth;
		if(!!_this.attr('percent')){
			percent = _this.attr('percent') * 1;
		}else{
			_this.attr('percent', percent);
		}
		if((originWidth+'').indexOf('%')>-1){
			_this.width( originWidth );
		}else{
			_this.width( Math.floor($.window().width * percent) );
		}
		if((originHeight+'').indexOf('%')>-1){
			_this.height( originHeight );
		}else{
			_this.height( Math.floor(_this.width() * originHeight / originWidth) );
		}
	});
};

//按原宽高的比例自动设定高度, percent是按屏幕宽度作为参考对象
$.fn.autoHeight = function(originWidth, originHeight, percent){
	return this.each(function(){
		let _this = $(this);
		//if(!_this.is(':visible'))return true;
		if(typeof percent !== 'undefined'){
			_this.width( Math.floor($.window().width * percent) );
		}
		if((originWidth+'').indexOf('%')>-1){
			_this.width( originWidth );
		}
		if((originHeight+'').indexOf('%')>-1){
			_this.height( originHeight );
		}else{
			_this.height( Math.floor(_this.width() * originHeight / originWidth) );
		}
	});
};

//按原宽高的比例自动设定宽高
$.fn.autoSize = function(originWidth, originHeight, screenWidth){
	return this.each(function(){
		let _this = $(this), width = _this.outerWidth(false);
		//if(!_this.is(':visible'))return true;
		if(typeof screenWidth === 'undefined')screenWidth = $.window().width;
		let percent = width / screenWidth;
		if(!!_this.attr('percent')){
			percent = _this.attr('percent') * 1;
		}else{
			_this.attr('percent', percent);
		}
		if((originWidth+'').indexOf('%')>-1){
			_this.width( originWidth );
		}else{
			_this.width( Math.floor($.window().width * percent) );
		}
		if((originHeight+'').indexOf('%')>-1){
			_this.height( originHeight );
		}else{
			_this.height( Math.floor(_this.width() * originHeight / originWidth) );
		}
	});
};

//移动端样式密码框, 调用者必须为input:text
$.fn.passwordView = function(options){
	let opt = {
		cls: 'ring', //附加样式
		placeholder: '●', //占位符,为空即显示字符串
		length: 6, //位数
		empty: null, //值为空时执行
		input: null, //值不为空且未输入所有位数时执行
		callback: null //输入所有位数后执行
	}, _ths = this;
	if($.isFunction(options)){
		opt.callback = options;
		options = $.extend({}, $('body').data('passwordView.options'), opt);
	}else{
		options = $.extend(opt, $('body').data('passwordView.options'), options);
	}
	setTimeout(function(){_ths.select().focus()}, 10);
	return this.each(function(){
		if(!!$(this).data('passwordView'))return true;
		let length = Number(options.length),
			_this = $(this).attr('maxlength', length).addClass('inp').removeClass('hidden').css('display', 'block').data('passwordView', true);
		_this.wrap('<div class="passwordView"><div class="'+options.cls+'"></div></div>');
		let view = _this.parent(), w = 100/length, html = '<ul>';
		for(let i=0; i<length; i++)html += '<li style="width:'+w+'%;padding-top:'+w+'%;"><span><input type="text" /></span></li>';
		html += '</ul><font></font>';
		view.append(html);
		let font = view.find('font'), placeholders = view.find('ul input');
		_this.on('input', function(){
			fillPlaceholder();
			if(_this.val().trim().length && _this.val().trim().length<length && $.isFunction(options.input))options.input.call(_this);
			if(_this.val().trim().length === length && $.isFunction(options.callback))options.callback.call(_this);
		});
		fillPlaceholder();
		function fillPlaceholder(){
			let length = _this.val().trim().length, li = placeholders.eq(length).parent().parent();
			if(!length && $.isFunction(options.empty))options.empty.call(_this);
			for(let i=0; i<length; i++)placeholders.eq(i).addClass('this').val(options.placeholder.length?options.placeholder:_this.val().substr(i, 1));
			placeholders.each(function(index){if(index>=length)$(this).removeClass('this').val('')});
			if(li.length)font.show().css('left', li.position().left+li.width()/2);
			else font.hide();
		}
	});
};

//仿iOS的UIActionSheet
$.fn.actionView = function(title, btns, e){
	let _this = this, tablet = $.window().width>=1024, overlay = $('.load-overlay', _this), dialog = $('.dialog-action', _this), group;
	if(typeof title === 'boolean'){
		let height = dialog.height();
		dialog.removeClass('dialog-action-x');
		if(!dialog.hasClass('dialog-action-popover'))dialog.css({transform:'translate3d(0,'+height+'px,0)', '-webkit-transform':'translate3d(0,'+height+'px,0)'});
		setTimeout(function(){dialog.remove()}, 400);
		setTimeout(function(){
			if($('.load-face, .load-view, .load-presentView, .dialog-action, .dialog-alert, .dialog-popover', _this).length)return;
			overlay.removeClass('load-overlay-in');
			setTimeout(function(){overlay.remove()}, 400);
		}, 400);
		return;
	}
	if(!$.isArray(btns) || !btns.length)return dialog;
	if(!overlay.length && !!!_this.data('overlay-no') && !!!_this.data('overlay-no-actionView')){
		overlay = $('<div class="load-overlay"></div>');
		_this.append(overlay.css({background:'rgba(0,0,0,0.6)'}));
		if(tablet && e){
			overlay.on(window.eventType, function(){_this.popoverView(false)});
		}else{
			overlay.on(window.eventType, function(){_this.actionView(false)});
		}
	}
	setTimeout(function(){overlay.addClass('load-overlay-in')}, 0);
	dialog = $('<div class="dialog-action"></div>').css('z-index', 999);
	group = $('<div class="dialog-action-group"><div class="dialog-action-box"></div></div>');
	dialog.append(group);
	let inner = group.find('.dialog-action-box').stopBounces();
	if(title.length)inner.append('<div class="dialog-action-label">'+title+'</div>');
	for(let i=0; i<btns.length; i++){
		let text = btns[i].text||'btn'+(i+1), btn = $('<a href="javascript:void(0)" class="dialog-action-button">'+text+'</a>');
		inner.append(btn);
		if($.isFunction(btns[i].click)){
			btn.data('click', btns[i].click);
			(function(j){
			btn.click(function(){
				$(this).data('click').call(dialog, j);
				_this.actionView(false);
			});
			})(i);
		}else{
			if(tablet && e){
				btn.click(function(){_this.popoverView(false)});
			}else{
				btn.click(function(){_this.actionView(false)});
			}
		}
	}
	group = $('<div class="dialog-action-group"><div class="dialog-action-box"><a href="javascript:void(0)" class="dialog-action-button dialog-action-bold">取消</a></div></div>');
	dialog.append(group);
	if(tablet && e){
		group.find('a').click(function(){_this.popoverView(false)});
		_this.popoverView(e, dialog.addClass('dialog-action-popover'));
	}else{
		group.find('a').click(function(){_this.actionView(false)});
		_this.append(dialog);
		let height = dialog.height();
		dialog.css({transform:'translate3d(0,'+height+'px,0)', '-webkit-transform':'translate3d(0,'+height+'px,0)', 'transition-duration':'0s', '-webkit-transition-duration':'0s'});
	}
	setTimeout(function(){
		dialog.css({transform:'', '-webkit-transform':'', 'transition-duration':'', '-webkit-transition-duration':''}).addClass('dialog-action-x');
	}, 10);
	return dialog;
};

//popoverView
$.fn.popoverView = function(e, target){
	let _this = $(this), win = $.window(), overlay = $('.load-overlay', _this), dialog = $('.dialog-popover', _this);
	if(!target){
		dialog.removeClass('dialog-popover-x');
		setTimeout(function(){
			let child = dialog.find('.dialog-popover-box > *:eq(0)');
			if(!!child.data('parent'))child.data('parent').append(child);
			if(!!child.data('originnext'))child.data('originnext').before(child);
			dialog.remove();
		}, 400);
		setTimeout(function(){
			if($('.load-face, .load-view, .load-presentView, .dialog-action, .dialog-alert, .dialog-popover', _this).length)return;
			overlay.removeClass('load-overlay-in');
			setTimeout(function(){overlay.remove()}, 400);
		}, 400);
		return;
	}
	if(!e)return;
	let o = $.etarget(e);
	if(!overlay.length && !!!_this.data('overlay-no') && !!!_this.data('overlay-no-popoverView')){
		overlay = $('<div class="load-overlay"></div>');
		_this.append(overlay.css({background:'rgba(0,0,0,0.6)'}));
		overlay.on(window.eventType, function(){_this.popoverView(false)});
	}
	setTimeout(function(){overlay.addClass('load-overlay-in')}, 0);
	dialog = $('<div class="dialog-popover"><div class="dialog-popover-inner"><div class="dialog-popover-box"></div></div><div class="dialog-popover-angle"></div></div>');
	_this.append(dialog);
	let inner = dialog.find('.dialog-popover-box').stopBounces();
	if(typeof target !== 'object'){
		let htm = target+'', object = $(htm);
		if(object.length){
			if(object.next().length)object.data('originnext', object.next());
			else object.data('parent', object.parent());
			inner.append(object);
		}else{
			inner.html(htm);
		}
	}else{
		target = $(target);
		if(target.length){
			if(target.next().length)target.data('originnext', target.next());
			else target.data('parent', target.parent());
			inner.append(target);
		}
	}
	let ge = 4, width = dialog.width(), height = inner.height()>44*6-10 ? inner.height(44*6-10).height() : inner.height(), offset = o.offset(),
		angle = inner.parent().next(), angleWidth = angle.width(), angleHeight = angle.height(), left, top, scrollTop = $.scroll().top;
	left = offset.left + (o.width() - width) / 2;
	if(left < ge)left = ge;
	if(left+width > win.width-ge)left = win.width - width - ge;
	top = offset.top - height - angleHeight/2;
	if(top < ge){
		top = offset.top + o.height() + angleHeight/2;
		if(top+height > scrollTop+win.height-ge){
			let halfTop = offset.top - scrollTop - ge - angleHeight/2,
				halfBottom = scrollTop + win.height - offset.top - o.height() - ge - angleHeight/2;
			if(halfTop > halfBottom){
				inner.height(halfTop);
				top = offset.top - halfTop - angleHeight/2;
				angle.addClass('on-bottom');
			}else{
				inner.height(halfBottom);
				top = offset.top + o.height() + angleHeight/2;
				angle.addClass('on-top');
			}
		}else{
			angle.addClass('on-top');
		}
	}else{
		angle.addClass('on-bottom');
	}
	angle.css('left', offset.left + (o.width()-angleWidth)/2 - left);
	dialog.css({left:left, top:top});
	setTimeout(function(){
		dialog.addClass('dialog-popover-x');
	}, 10);
	return dialog;
};

$.extend({
	//手机端摇动, 为避免多次调用 callback, 需要在操作页面增加一个全局变量来控制, 例如:
	//let shake = false; $.shake(function(){ if(!shake){shake=true; ... } });
	shake: function(callback){
		if(window.DeviceMotionEvent && $.isFunction(callback)){
			let speed = 20, x = 0, y = 0, lastX = 0, lastY = 0;
			window.addEventListener('devicemotion', function(e){
				let acceleration = e.accelerationIncludingGravity;
				x = acceleration.x;
				y = acceleration.y;
				if(Math.abs(x - lastX)>speed || Math.abs(y - lastY)>speed) callback();
				lastX = x;
				lastY = y;
			}, false);
		}
	},
	//仿iOS的UIActionSheet
	actionView: function(title, btns, e){
		return $(document.body).actionView(title, btns, e);
	},
	//popView
	popoverView: function(e, target){
		return $(document.body).popoverView(e, target);
	}
});

//设置rem
(function(win, doc){
	if(!doc.addEventListener)return;
	let docEl = doc.documentElement, resizeEvt = 'orientationchange' in win ? 'orientationchange' : 'resize',
		recalc = function(){
			if($('html').hasClass('font-size') || $('body').hasClass('font-size'))return;
			let clientWidth = docEl.clientWidth;
			if(!clientWidth)return;
			docEl.style.fontSize = 100 * (clientWidth / 320) + 'px';
			if(clientWidth >= 768)docEl.style.fontSize = '100px';
		};
	win.addEventListener(resizeEvt, recalc, false);
	doc.addEventListener('DOMContentLoaded', recalc, false);
})(window, document);

})(jQuery);

//自动设置viewport
window.mobileUtil = function(i, e){
	let t = navigator.userAgent, n = /android|adr/gi.test(t), a = /iphone|ipod|ipad/gi.test(t) && !n, r = n || a;
	return {
		isAndroid: n,
		isIos: a,
		isMobile: r,
		fixScreen: function(){
			function t(i){
				return 'initial-scale=' + i + ',maximum-scale=' + i + ',minimum-scale=' + i;
			}
			let n = e.querySelector('meta[name="viewport"]'), a = n ? n.content : '', d = a.match(/initial-scale=([\d.]+)/), o = a.match(/width=([^,\s]+)/);
			if (r && !d && o && "device-width" !== o[1]) {
				let c = parseInt(o[1]), s = i.innerWidth || c, m = i.screen.width || s, l = m / c;
				n.content = a + ',target-densitydpi=device-dpi,' + t(l);
			}
		}
	}
}(window, document);
mobileUtil.fixScreen();
