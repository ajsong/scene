(function($){

//自定义滚动条,需引入scrollBar.css
$.fn.scrollBar = function(options){
	options = $.extend({
		cls: '', //附加样式
		drag: true, //滚动条拖曳
		debug: false //调试模式
	}, options);
	let barSize = $.scrollBar();
	if(options.debug)barSize = {width:0, height:0};
	return this.each(function(){
		if(!!$(this).data('scrollBar'))return true;
		let _this = $(this).data('scrollBar', true).addClass(options.cls), parent = _this, doc = $(document), wrap = null, horizontal = null, vertical = null, horizontalThumb = null, verticalThumb = null,
			sl = 0, st = 0, startX = 0, startY = 0, isDraging = false, isHorizontal = false, isVertical = false, barOffset = null,
			overflowX = (_this.css('overflow-x') === 'auto' || _this.css('overflow-x') === 'scroll'), overflowY = (_this.css('overflow-y') === 'auto' || _this.css('overflow-y') === 'scroll'),
			scrollEvt = (typeof $._data(this, 'events') !== 'undefined' && typeof($._data(this, 'events')['scroll']) !== 'undefined');
		if(!overflowX && !overflowY){
			console.error('Use the scrollBar plugin must be set the style "overflow" to "auto" or "scroll"');
			return true;
		}
		_this.data('scrollEvt', scrollEvt);
		if(scrollEvt){
			parent = _this.parent();
			wrap = _this;
		}else{
			_this.wrapInner('<div class="scrollbar-wrap"></div>');
			wrap = _this.children();
		}
		parent.css('overflow', 'hidden');
		if(parent.css('position') === 'static')parent.css({position:'relative'});
		parent.on('mouseover', function(){
			parent.children('.scrollbar-bar').css('opacity', 1);
		}).on('mouseout', function(){
			parent.children('.scrollbar-bar').css('opacity', '');
		});
		wrap.css({
			width:parent.outerWidth(false) + (overflowY ? barSize.width : 0), height:parent.outerHeight(false) + (overflowX ? barSize.height : 0), 'overflow-x':overflowX ? 'scroll' : '', 'overflow-y':overflowY ? 'scroll' : ''
		});
		let dragMove = function(e){
				if(isHorizontal){
					let x = $.touches(e).x - startX + sl;
					x = Math.max(Math.min(x, horizontal.width() - horizontalThumb.width()), 0);
					horizontalThumb.css('left', x);
					wrap.scrollLeft(x * wrap[0].scrollWidth / horizontal.width());
				}else if(isVertical){
					let y = $.touches(e).y - startY + st;
					y = Math.max(Math.min(y, vertical.height() - verticalThumb.height()), 0);
					verticalThumb.css('top', y);
					wrap.scrollTop(y * wrap[0].scrollHeight / vertical.height());
				}
			},
			dragEnd = function(){
				isHorizontal = false;
				isVertical = false;
				isDraging = false;
				parent.children('.scrollbar-bar').removeClass('scrollbar-bar-draging');
				doc.unselect(false).off('mousemove', dragMove);
			};
		if(overflowX){
			horizontal = $('<div class="scrollbar-bar scrollbar-horizontal"><div class="scrollbar-thumb"></div></div>');
			parent.append(horizontal);
			if(overflowY)horizontal.css('right', (!options.debug && barSize.width === 0) ? horizontal.height() + 3 : barSize.width);
			horizontalThumb = horizontal.find('div');
			if(options.drag)horizontalThumb.css('cursor', 'pointer');
			if(wrap[0].scrollWidth > parent.width())horizontalThumb.width(horizontal.width() * wrap.width() / wrap[0].scrollWidth);
			horizontalThumb.on('contextmenu', function(){ return false });
		}
		if(overflowY){
			vertical = $('<div class="scrollbar-bar scrollbar-vertical"><div class="scrollbar-thumb"></div></div>');
			parent.append(vertical);
			if(overflowX)vertical.css('bottom', (!options.debug && barSize.height === 0) ? vertical.width() + 3 : barSize.height);
			verticalThumb = vertical.find('div');
			if(options.drag)verticalThumb.css('cursor', 'pointer');
			if(wrap[0].scrollHeight > parent.height())verticalThumb.height(vertical.height() * wrap.height() / wrap[0].scrollHeight);
			verticalThumb.on('contextmenu', function(){ return false });
		}
		if(options.drag){
			doc.on('mousedown', function(e){
				if(e.button !== 0)return true;
				let o = $.etarget(e);
				if(horizontal && $(o).is(horizontalThumb)){
					sl = parseInt(horizontalThumb.css('left').replace(/px/, ''));
					barOffset = horizontal.offset();
					startX = $.touches(e).x;
					isHorizontal = true;
					isDraging = true;
					parent.children('.scrollbar-bar').addClass('scrollbar-bar-draging');
					doc.unselect().on('mousemove', dragMove);
				}else if(vertical && $(o).is(verticalThumb)){
					st = parseInt(verticalThumb.css('top').replace(/px/, ''));
					barOffset = vertical.offset();
					startY = $.touches(e).y;
					isVertical = true;
					isDraging = true;
					parent.children('.scrollbar-bar').addClass('scrollbar-bar-draging');
					doc.unselect().on('mousemove', dragMove);
				}
			}).on('mouseup', dragEnd);//.on('mouseleave', dragEnd);
		}
		wrap.on('scroll', function(){
			if(isDraging)return true;
			if(wrap[0].scrollWidth > parent.width() && horizontal.length)horizontalThumb.css({left:horizontal.width() * wrap.scrollLeft() / wrap[0].scrollWidth});
			if(wrap[0].scrollHeight > parent.height() && vertical.length)verticalThumb.css({top:vertical.height() * wrap.scrollTop() / wrap[0].scrollHeight});
		});
	});
};

$.fn.scrollBarUpdate = function(){
	return this.each(function(){
		let _this = $(this), parent = _this, wrap = null, scrollEvt = _this.data('scrollEvt');
		if(scrollEvt){
			parent = _this.parent();
			wrap = _this;
		}else{
			wrap = _this.children('.scrollbar-wrap');
		}
		if(!wrap.length)return true;
		let horizontal = parent.children('.scrollbar-horizontal'), vertical = parent.children('.scrollbar-vertical');
		if(wrap[0].scrollWidth > parent.width() && horizontal.length)horizontal.find('div').width(horizontal.width() * parent.width() / wrap[0].scrollWidth);
		if(wrap[0].scrollHeight > parent.height() && vertical.length)vertical.find('div').height(vertical.height() * parent.height() / wrap[0].scrollHeight);
	});
};

})(jQuery);
