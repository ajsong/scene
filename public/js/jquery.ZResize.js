(function($) {
	
	/**
	 * 默认参数
	 */
	let defaultOpts = {
		stage: document,
		target: '.resize-item',
		minWidth: '',
		maxWidth: '',
		minHeight: '',
		maxHeight: '',
		restrict: false,
		isRotate: false,
		range: null,
		panelCss: {},
		guide: true,
		guideCss: {},
		unusualGuide: [],
		suck: '',
		suckDistance: 8,
		suckIgnore: [],
		skipShow: null,
		skipHide: null,
		skipOperate: null,
		show: null,
		resize: null,
		start: null,
		move: null,
		end: null,
		rotate: null,
		hide: null
	};
	
	/**
	 * 定义类
	 */
	let ZResize = function(options) {
		this.options = $.extend({}, defaultOpts, options);
		this.guides = [];
		this.guideH = null;
		this.guideV = null;
		this.init();
	};
	
	ZResize.prototype = {
		/**
		 *  初始化拖拽item
		 */
		init: function() {
			let self = this, targetParent = $(self.options.target).parent();
			if(self.options.guide) {
				if(!targetParent.find('.guide-h').length) {
					self.guideH = $('<em class="guide-h"></em>');
					self.guideV = $('<em class="guide-v"></em>');
					let guideCss = $.extend({
						display: 'none',
						position: 'absolute',
						left: 0,
						top: 0,
						'z-index': 999,
						'pointer-events': 'none'
					}, self.options.guideCss);
					self.guideH.css({'border-top':'1px dashed #dcdb00', width:'100%'}).css(guideCss);
					self.guideV.css({'border-left':'1px dashed #dcdb00', height:'100%'}).css(guideCss);
					targetParent.prepend(self.guideV);
					targetParent.prepend(self.guideH);
					setTimeout(function() {
						targetParent.prepend(self.guideV);
						targetParent.prepend(self.guideH);
					}, 0);
				}else {
					self.guideH = targetParent.find('.guide-h');
					self.guideV = targetParent.find('.guide-v');
				}
			}
			if(typeof self.options.minWidth === 'string') {
				if(/^[\d.]+%$/.test(String(self.options.minWidth))) {
					self.options.minWidth = Number(Math.floor(targetParent.outerWidth(false) * (Number(self.options.minWidth.replace('%', '')) / 100)));
				}else if(/^[\d.]+px$/.test(String(self.options.minWidth))) {
					self.options.minWidth = Number(Math.floor(self.options.minWidth.replace('px', '')));
				}else{
					self.options.minWidth = 0;
				}
			}
			if(self.options.minWidth < 0) self.options.minWidth = 0;
			if(typeof self.options.minHeight === 'string') {
				if(/^[\d.]+%$/.test(String(self.options.minHeight))) {
					self.options.minHeight = Number(Math.floor(targetParent.outerWidth(false) * (Number(self.options.minHeight.replace('%', '')) / 100)));
				}else if(/^[\d.]+px$/.test(String(self.options.minHeight))) {
					self.options.minHeight = Number(Math.floor(self.options.minHeight.replace('px', '')));
				}else{
					self.options.minHeight = 0;
				}
			}
			if(self.options.minHeight < 0) self.options.minHeight = 0;
			if(typeof self.options.maxWidth === 'string') {
				if(/^[\d.]+%$/.test(String(self.options.maxWidth))) {
					self.options.maxWidth = Number(Math.floor(targetParent.outerWidth(false) * (Number(self.options.maxWidth.replace('%', '')) / 100)));
				}else if(/^[\d.]+px$/.test(String(self.options.maxWidth))) {
					self.options.maxWidth = Number(Math.floor(self.options.maxWidth.replace('px', '')));
				}else{
					self.options.maxWidth = '';
				}
			}
			if(typeof self.options.maxHeight === 'string') {
				if(/^[\d.]+%$/.test(String(self.options.maxHeight))) {
					self.options.maxHeight = Number(Math.floor(targetParent.outerWidth(false) * (Number(self.options.maxHeight.replace('%', '')) / 100)));
				}else if(/^[\d.]+px$/.test(String(self.options.maxHeight))) {
					self.options.maxHeight = Number(Math.floor(self.options.maxHeight.replace('px', '')));
				}else{
					self.options.maxHeight = '';
				}
			}
			$(self.options.target).each(function() {
				let _target = $(this);
				if(!!_target.data('initResizeBox')) return true;
				_target.data('initResizeBox', true).css({
					position: 'absolute'
				});
				//创建面板
				let resizePanel = $('<div class="resize-panel"></div>');
				let panelCss = $.extend({
					width: '100%',
					height: '100%',
					top: 0,
					left: 0,
					position: 'absolute',
					cursor: 'move',
					display: 'none',
					'border': '1px solid #08a1ef',
					'box-sizing': 'border-box',
					'user-select': 'none',
					'-webkit-user-drag': 'none',
					'-webkit-tap-highlight-color': 'rgba(0,0,0,0)'
				}, self.options.panelCss);
				resizePanel.css(panelCss);
				self.appendHandler(resizePanel, _target);
				
				//创建控制点
				let n = $('<div class="n"><b></b></div>');//北
				let s = $('<div class="s"><b></b></div>');//南
				let w = $('<div class="w"><b></b></div>');//西
				let e = $('<div class="e"><b></b></div>');//东
				let ne = $('<div class="ne"><b></b></div>');//东北
				let nw = $('<div class="nw"><b></b></div>');//西北
				let se = $('<div class="se"><b></b></div>');//东南
				let sw = $('<div class="sw"><b></b></div>');//西南
				let nb = $('<div class="nb"></div>');//北
				let sb = $('<div class="sb"></div>');//南
				let wb = $('<div class="wb"></div>');//西
				let eb = $('<div class="eb"></div>');//东
				
				//添加公共样式
				self.addHandlerCss([n, s, w, e, ne, nw, se, sw]);
				self.addHandlerCss([nb, sb, wb, eb]);
				//添加各自样式
				n.css({
					'top': '-8px',
					'margin-left': '-8px',
					'left': '50%',
					'cursor': 'ns-resize'
				});
				s.css({
					'bottom': '-8px',
					'margin-left': '-8px',
					'left': '50%',
					'cursor': 'ns-resize'
				});
				e.css({
					'top': '50%',
					'margin-top': '-8px',
					'right': '-8px',
					'cursor': 'ew-resize'
				});
				w.css({
					'top': '50%',
					'margin-top': '-8px',
					'left': '-8px',
					'cursor': 'ew-resize'
				});
				ne.css({
					'top': '-8px',
					'right': '-8px',
					'cursor': 'nesw-resize'
				});
				nw.css({
					top: '-8px',
					'left': '-8px',
					'cursor': 'nwse-resize'
				});
				se.css({
					'bottom': '-8px',
					'right': '-8px',
					'cursor': 'nwse-resize'
				});
				sw.css({
					'bottom': '-8px',
					'left': '-8px',
					'cursor': 'nesw-resize'
				});
				nb.css({
					'top': '0',
					'left': '0',
					'width': '100%',
					'cursor': 'ns-resize'
				});
				sb.css({
					'bottom': '0',
					'left': '0',
					'width': '100%',
					'cursor': 'ns-resize'
				});
				wb.css({
					'top': '0',
					'left': '0',
					'height': '100%',
					'cursor': 'ew-resize'
				});
				eb.css({
					'top': '0',
					'right': '0',
					'height': '100%',
					'cursor': 'ew-resize'
				});
				
				//添加项目
				self.appendHandler([n, s, w, e, ne, nw, se, sw, nb, sb, wb, eb], resizePanel);
				
				//旋转
				let isRotate = self.options.isRotate;
				if($.isFunction(isRotate)) isRotate = isRotate();
				if(isRotate) {
					let r = $('<div class="r"><b></b></div>');
					self.addHandlerCss([r]);
					self.appendHandler([r], resizePanel);
				}
				
				//绑定拖拽缩放事件
				self.bindResizeEvent(resizePanel);
				
				//绑定触发事件
				self.bindTrigger(_target);
			});
			self.bindHidePanel();
		},
		//控制点公共样式
		addHandlerCss: function(els) {
			if(els.length === 1) {
				for(let i = 0; i < els.length; i++) {
					els[i].css({
						position: 'absolute',
						top: '-24px',
						left: '50%',
						'z-index': 1,
						width: '18px',
						height: '18px',
						overflow: 'hidden',
						cursor: 'pointer',
						'margin-left': '-9px',
						'box-sizing': 'border-box',
						'touch-action': 'pan-x pan-y',
						'user-select': 'none',
						'-webkit-user-drag': 'none',
						'-webkit-tap-highlight-color': 'rgba(0,0,0,0)'
					}).find('b').css({
						display: 'block',
						width: '16px',
						height: '16px',
						overflow: 'hidden',
						background: 'url("data:image/svg+xml;charset=utf-8,%3Csvg%20viewBox%3D%220%200%201024%201024%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Cpath%20d%3D%22M827.4%20195.5L959%2064v393H565.7l181.2-181.1c-31.3-31.3-67.3-55.7-108.1-73.1-40.7-17.4-83.5-26.2-128.2-26.2-60.8%200-117%2015-168.5%2044.9-51.5%2030-92.2%2070.6-122.1%20122.1-30%2051.4-45%20107.5-45%20168.3%200%2060.8%2015%20116.9%2045%20168.3s70.7%2092.1%20122.1%20122.1c51.5%2030%20107.6%2044.9%20168.5%2044.9%2072.5%200%20137.8-20.6%20196-61.7%2058.2-41.1%2098.4-94.8%20120.8-161h115.4c-16.1%2064.4-45.2%20121.8-87.2%20172.4-42.1%2050.5-92.2%2090.1-150.3%20118.7-60.9%2029.5-125.7%2044.3-194.6%2044.3-80.5%200-155.7-20.6-225.5-61.7-67.1-39.3-120.4-93-159.7-161-40.3-68.9-60.4-144-60.4-225.3s20.1-156.5%2060.4-225.3c39.4-68%2092.6-121.6%20159.7-161%2069.8-41.1%20145-61.7%20225.5-61.7%2060.8%200%20118.6%2011.6%20173.2%2034.9%2054.5%2023.3%20102.4%2055.5%20143.5%2096.7z%22%20fill%3D%22%2308a1ef%22%3E%3C%2Fpath%3E%3C%2Fsvg%3E") no-repeat center center',
						'background-size': 'cover',
						margin: '1px 0 0 1px',
						'box-sizing': 'border-box',
						'touch-action': 'pan-x pan-y',
						'user-select': 'none',
						'-webkit-user-drag': 'none',
						'-webkit-tap-highlight-color': 'rgba(0,0,0,0)'
					});
				}
				return;
			}
			if(els.length === 4) {
				for(let i = 0; i < els.length; i++) {
					els[i].css({
						position: 'absolute',
						width: '5px',
						height: '5px',
						margin: '0',
						'touch-action': 'pan-x pan-y',
						'user-select': 'none',
						'-webkit-user-drag': 'none',
						'-webkit-tap-highlight-color': 'rgba(0,0,0,0)'
					});
				}
				return;
			}
			for(let k = 0; k < els.length; k++) {
				els[k].css({
					position: 'absolute',
					'z-index': 1,
					width: '16px',
					height: '16px',
					overflow: 'hidden',
					margin: '0',
					'box-sizing': 'border-box',
					'touch-action': 'pan-x pan-y',
					'user-select': 'none',
					'-webkit-user-drag': 'none',
					'-webkit-tap-highlight-color': 'rgba(0,0,0,0)'
				}).find('b').css({
					display: 'block',
					width: '6px',
					height: '6px',
					overflow: 'hidden',
					background: '#fff',
					margin: '5px 0 0 5px',
					'border-radius': '100%',
					border: '1px solid #08a1ef',
					'box-sizing': 'border-box',
					'touch-action': 'pan-x pan-y',
					'user-select': 'none',
					'-webkit-user-drag': 'none',
					'-webkit-tap-highlight-color': 'rgba(0,0,0,0)'
				});
			}
		},
		/**
		 *  插入容器
		 */
		appendHandler: function(handlers, target) {
			for(let i = 0; i < handlers.length; i++) {
				target.append(handlers[i]);
			}
		},
		/**
		 *  点击item显示拖拽面板
		 */
		bindTrigger: function(el) {
			let self = this;
			el.on('click', function(e) {
				e.stopPropagation();
				self.triggerResize(el, e);
				return false;
			});
			/*el.on('longclick', function(e) {
				console.log(e);
			});*/
		},
		/**
		 *  显示拖拽面板
		 */
		triggerResize: function(el, e) {
			let self = this, stage = $(self.options.stage), bindKeyMove = stage.data('bindKeyMove');
			if($.isFunction(self.options.skipShow)) {
				let result = self.options.skipShow.call(el, e);
				if(typeof result === 'boolean' && !result) return;
			}
			el.siblings().children('.resize-panel').css({
				display: 'none'
			});
			if(!!bindKeyMove) stage.off('keydown', bindKeyMove).removeData('bindKeyMove');
			el.children('.resize-panel').css({
				display: 'block'
			});
			bindKeyMove = function(e) {return self.bindKeyMove(e)};
			stage.data('bindKeyMove', bindKeyMove).on('keydown', bindKeyMove);
			if($.isFunction(self.options.show)) {
				self.options.show.call(el, {left:Number(Math.floor(el.css('left').replace('px', ''))), top:Number(Math.floor(el.css('top').replace('px', '')))}, e);
			}
		},
		/**
		 *  点击舞台空闲区域 隐藏缩放面板
		 */
		bindHidePanel: function() {
			let self = this, el = $(self.options.target);
			$(self.options.stage).on('click', function(e) {
				if($.isFunction(self.options.skipHide)) {
					let result = self.options.skipHide.call(el, e);
					if(typeof result === 'boolean' && !result) return;
				}
				self.hidePanel();
			});
		},
		/**
		 *  手动隐藏缩放面板 解绑上左下右按键移动
		 */
		hidePanel: function() {
			let self = this, el = $(self.options.target), stage = $(self.options.stage), bindKeyMove = stage.data('bindKeyMove');
			el.children('.resize-panel').css({
				display: 'none'
			});
			if(!!bindKeyMove) stage.off('keydown', bindKeyMove).removeData('bindKeyMove');
			if($.isFunction(self.options.hide)) {
				self.options.hide.call(el);
			}
		},
		/**
		 *  绑定上左下右按键 逐个像素移动 同时按下Shift可5像素移动 同时按下Ctrl(macOS为Command)可10像素移动
		 */
		bindKeyMove: function(e) {
			let self = this, org = $(self.options.target), p = navigator.platform.toLowerCase(), isMac = p.indexOf('mac') > -1,
				o = e.target, code = e.which||e.keyCode, tagName = o.tagName.toUpperCase(),
				meta = e.metaKey, ctrl = e.ctrlKey, shift = e.shiftKey, px = (shift ? 5 : 1),
				left = Number(Math.floor(org.css('left').replace('px', ''))),
				top = Number(Math.floor(org.css('top').replace('px', '')));
			if(tagName === 'INPUT' || tagName === 'TEXTAREA' || tagName === 'SELECT') return true;
			if(self.skipOperate(e, org)) return true;
			if(shift) return true;
			if((isMac && meta) || (!isMac && ctrl)) px = 10;
			if(code === 38) {//上
				e.preventDefault();
				org.css('top', top - px);
			}else if(code === 37) {//左
				e.preventDefault();
				org.css('left', left - px);
			}else if(code === 40) {//下
				e.preventDefault();
				org.css('top', top + px);
			}else if(code === 39) {//右
				e.preventDefault();
				org.css('left', left + px);
			}else if(code === 27) {//Esc
				self.hidePanel();
				return true;
			}
			self.triggerMove(org);
			return true;
		},
		/**
		 * 开始移动事件触发
		 */
		triggerStart: function(org, isDrag) {
			let self = this;
			if($.isFunction(self.options.start)) {
				if(typeof isDrag === 'undefined') isDrag = false;
				let left = Number(Math.floor(org.css('left').replace('px', ''))), top = Number(Math.floor(org.css('top').replace('px', '')));
				self.options.start.call(org, {left:left, top:top}, isDrag);
			}
		},
		/**
		 * 移动事件触发
		 */
		triggerMove: function(org, isDrag) {
			let self = this;
			if($.isFunction(self.options.move)) {
				if(typeof isDrag === 'undefined') isDrag = false;
				let left = Number(Math.floor(org.css('left').replace('px', ''))), top = Number(Math.floor(org.css('top').replace('px', '')));
				self.options.move.call(org, {left:left, top:top}, isDrag);
			}
		},
		/**
		 * 结束移动事件触发
		 */
		triggerEnd: function(org, isDrag) {
			let self = this;
			if($.isFunction(self.options.end)) {
				if(typeof isDrag === 'undefined') isDrag = false;
				let left = Number(Math.floor(org.css('left').replace('px', ''))), top = Number(Math.floor(org.css('top').replace('px', '')));
				self.options.end.call(org, {left:left, top:top}, isDrag);
			}
		},
		/**
		 * 旋转事件触发
		 */
		triggerRotate: function(org, degrees) {
			let self = this;
			if($.isFunction(self.options.rotate)) {
				self.options.rotate.call(org, degrees);
			}
		},
		/**
		 * 跳过拖曳事件, 返回true即不可拖拉
		 */
		skipOperate: function(e, org) {
			let self = this;
			if($.isFunction(self.options.skipOperate)) {
				return self.options.skipOperate.call(org, e);
			}
			return false;
		},
		/**
		 * 拖拽事件控制 包含8个缩放点  和一个拖拽位置
		 */
		bindResizeEvent: function(el) {
			let self = this;
			let ox = 0; //原始事件x位置
			let oy = 0; //原始事件y位置
			let ow = 0; //原始宽度
			let oh = 0; //原始高度
			
			let oleft = 0; //原始元素位置
			let otop = 0;
			let org = el.parent();
			let range = self.options.range ? $(self.options.range) : $(self.options.stage);
			let started = false, resize = false;
			
			let rangeWidth = 0;
			let rangeHeight = 0;
			
			let doc = self.options.range ? $(self.options.range) : $(document), documentPw = $(document).innerWidth(), documentPh = $(document).innerHeight(),
				parentLeft = org.parent().offset().left, parentTop = org.parent().offset().top,
				suckArr = {'no':0, 'parent':1, 'all':2}, suck = self.options.suck, suckPw = doc[0].clientWidth, suckPh = doc[0].clientHeight,
				dis = self.options.suckDistance, targetWidth = 0, targetHeight = 0, targetBrother = null, right = 0, bottom = 0;
			
			if(typeof suck === 'string') {
				if(suck.length && suckArr[suck.toLowerCase()]) suck = suckArr[suck.toLowerCase()];
				else suck = 0;
			}
			
			let sl = doc.scrollLeft();
			let st = doc.scrollTop();
			let emove = false;
			let smove = false;
			let wmove = false;
			let nmove = false;
			let nemove = false;
			let nwmove = false;
			let semove = false;
			let swmove = false;
			let drag = false;
			let rotate = false;
			
			let stageMousemove = function(e) {
				if(self.skipOperate(e, org)) return true;
				let x = 0, y = 0, width = 0, height = 0, left = 0, top = 0, j = 0, k = 0;
				if(emove) {
					x = (e.pageX - ox);
					width = ow + x;
					if(width < self.options.minWidth) {
						width = self.options.minWidth;
					}
					if(typeof self.options.maxWidth === 'number') {
						if(width > self.options.maxWidth) {
							width = self.options.maxWidth;
						}
					}
					org.css({
						width: Math.floor(width)
					});
					self.triggerMove(org);
				}else if(smove) {
					y = (e.pageY - oy);
					height = oh + y;
					if(height < self.options.minHeight) {
						height = self.options.minHeight;
					}
					if(typeof self.options.maxHeight === 'number') {
						if(height > self.options.maxHeight) {
							height = self.options.maxHeight;
						}
					}
					org.css({
						height: Math.floor(height)
					});
					self.triggerMove(org);
				}else if(wmove) {
					x = (e.pageX - ox);
					width = ow - x;
					if(width < self.options.minWidth) {
						width = self.options.minWidth;
					}
					if(typeof self.options.maxWidth === 'number') {
						if(width > self.options.maxWidth) {
							width = self.options.maxWidth;
						}
					}
					org.css({
						width: Math.floor(width),
						left: Math.floor(oleft + x)
					});
					self.triggerMove(org);
				}else if(nmove) {
					y = (e.pageY - oy);
					height = oh - y;
					if(height < self.options.minHeight) {
						height = self.options.minHeight;
					}
					if(typeof self.options.maxHeight === 'number') {
						if(height > self.options.maxHeight) {
							height = self.options.maxHeight;
						}
					}
					org.css({
						height: Math.floor(height),
						top: Math.floor(otop + y)
					});
					self.triggerMove(org);
				}else if(nemove) {
					x = e.pageX - ox;
					y = e.pageY - oy;
					if(self.options.restrict) {
						j = (ow + x) / ow;
						k = (oh - y) / oh;
						if(j >= k) {
							width = j * ow;
							height = j * oh;
							if(height < self.options.minHeight) {
								width = (j * ow) / (j * oh) * self.options.minHeight;
								height = self.options.minHeight;
							}
							if(typeof self.options.maxHeight === 'number') {
								if(height > self.options.maxHeight) {
									width = (j * ow) / (j * oh) * self.options.maxHeight;
									height = self.options.maxHeight;
								}
							}
						}else {
							width = k * ow;
							height = k * oh;
							if(width < self.options.minWidth) {
								width = self.options.minWidth;
								height = (k * oh) / (k * ow) * self.options.minWidth;
							}
							if(typeof self.options.maxWidth === 'number') {
								if(width > self.options.maxWidth) {
									width = self.options.maxWidth;
									height = (k * oh) / (k * ow) * self.options.maxWidth;
								}
							}
						}
					}else {
						width = ow + x;
						height = oh - y;
						if(width < self.options.minWidth) width = self.options.minWidth;
						if(typeof self.options.maxWidth === 'number') {
							if(width > self.options.maxWidth) width = self.options.maxWidth;
						}
						if(height < self.options.minHeight) height = self.options.minHeight;
						if(typeof self.options.maxHeight === 'number') {
							if(height > self.options.maxHeight) height = self.options.maxHeight;
						}
					}
					top = otop + (oh - height);
					org.css({
						width: Math.floor(width),
						height: Math.floor(height),
						top: Math.floor(top)
					});
					self.triggerMove(org);
				}else if(nwmove) {
					x = e.pageX - ox;
					y = e.pageY - oy;
					if(self.options.restrict) {
						j = (ow - x) / ow;
						k = (oh - y) / oh;
						if(j >= k) {
							width = j * ow;
							height = j * oh;
							if(height < self.options.minHeight) {
								width = (j * ow) / (j * oh) * self.options.minHeight;
								height = self.options.minHeight;
							}
							if(typeof self.options.maxHeight === 'number') {
								if(height > self.options.maxHeight) {
									width = (j * ow) / (j * oh) * self.options.maxHeight;
									height = self.options.maxHeight;
								}
							}
						}else {
							width = k * ow;
							height = k * oh;
							if(width < self.options.minWidth) {
								width = self.options.minWidth;
								height = (k * oh) / (k * ow) * self.options.minWidth;
							}
							if(typeof self.options.maxWidth === 'number') {
								if(width > self.options.maxWidth) {
									width = self.options.maxWidth;
									height = (k * oh) / (k * ow) * self.options.maxWidth;
								}
							}
						}
					}else {
						width = ow - x;
						height = oh - y;
						if(width < self.options.minWidth) width = self.options.minWidth;
						if(typeof self.options.maxWidth === 'number') {
							if(width > self.options.maxWidth) width = self.options.maxWidth;
						}
						if(height < self.options.minHeight) height = self.options.minHeight;
						if(typeof self.options.maxHeight === 'number') {
							if(height > self.options.maxHeight) height = self.options.maxHeight;
						}
					}
					top = otop + (oh - height);
					left = oleft + (ow - width);
					org.css({
						width: Math.floor(width),
						height: Math.floor(height),
						top: Math.floor(top),
						left: Math.floor(left)
					});
					self.triggerMove(org);
				}else if(semove) {
					x = e.pageX - ox;
					y = e.pageY - oy;
					if(self.options.restrict) {
						j = (ow + x) / ow;
						k = (oh + y) / oh;
						if(j >= k) {
							width = j * ow;
							height = j * oh;
							if(height < self.options.minHeight) {
								width = (j * ow) / (j * oh) * self.options.minHeight;
								height = self.options.minHeight;
							}
							if(typeof self.options.maxHeight === 'number') {
								if(height > self.options.maxHeight) {
									width = (j * ow) / (j * oh) * self.options.maxHeight;
									height = self.options.maxHeight;
								}
							}
						}else {
							width = k * ow;
							height = k * oh;
							if(width < self.options.minWidth) {
								width = self.options.minWidth;
								height = (k * oh) / (k * ow) * self.options.minWidth;
							}
							if(typeof self.options.maxWidth === 'number') {
								if(width > self.options.maxWidth) {
									width = self.options.maxWidth;
									height = (k * oh) / (k * ow) * self.options.maxWidth;
								}
							}
						}
					}else {
						width = ow + x;
						height = oh + y;
						if(width < self.options.minWidth) width = self.options.minWidth;
						if(typeof self.options.maxWidth === 'number') {
							if(width > self.options.maxWidth) width = self.options.maxWidth;
						}
						if(height < self.options.minHeight) height = self.options.minHeight;
						if(typeof self.options.maxHeight === 'number') {
							if(height > self.options.maxHeight) height = self.options.maxHeight;
						}
					}
					org.css({
						width: Math.floor(width),
						height: Math.floor(height)
					});
					self.triggerMove(org);
				}else if(swmove) {
					x = e.pageX - ox;
					y = e.pageY - oy;
					if(self.options.restrict) {
						j = (ow - x) / ow;
						k = (oh + y) / oh;
						if(j >= k) {
							width = j * ow;
							height = j * oh;
							if(height < self.options.minHeight) {
								width = (j * ow) / (j * oh) * self.options.minHeight;
								height = self.options.minHeight;
							}
							if(typeof self.options.maxHeight === 'number') {
								if(height > self.options.maxHeight) {
									width = (j * ow) / (j * oh) * self.options.maxHeight;
									height = self.options.maxHeight;
								}
							}
						}else {
							width = k * ow;
							height = k * oh;
							if(width < self.options.minWidth) {
								width = self.options.minWidth;
								height = (k * oh) / (k * ow) * self.options.minWidth;
							}
							if(typeof self.options.maxWidth === 'number') {
								if(width > self.options.maxWidth) {
									width = self.options.maxWidth;
									height = (k * oh) / (k * ow) * self.options.maxWidth;
								}
							}
						}
					}else {
						width = ow - x;
						height = oh + y;
						if(width < self.options.minWidth) width = self.options.minWidth;
						if(typeof self.options.maxWidth === 'number') {
							if(width > self.options.maxWidth) width = self.options.maxWidth;
						}
						if(height < self.options.minHeight) height = self.options.minHeight;
						if(typeof self.options.maxHeight === 'number') {
							if(height > self.options.maxHeight) height = self.options.maxHeight;
						}
					}
					left = oleft + (ow - width);
					org.css({
						width: Math.floor(width),
						height: Math.floor(height),
						left: Math.floor(left)
					});
					self.triggerMove(org);
				}else if(drag) {
					x = e.pageX - ox;
					y = e.pageY - oy;
					left = oleft + x;
					top = otop + y;
					if(suck > 0) {
						switch(suck) {
							case 1:
								suckPw = org.parent().outerWidth(false);
								suckPh = org.parent().outerHeight(false);
								if(left < dis && left > -dis) left = 0;
								else if(left < -targetWidth + dis && left > -targetWidth - dis) left = -targetWidth;
								else if(left > suckPw - targetWidth - dis && left < suckPw - targetWidth + dis) left = suckPw - targetWidth;
								else if(left > suckPw - dis && left < suckPw + dis) left = suckPw;
								if(top < dis && top > -dis) top = 0;
								else if(top < -targetHeight + dis && top > -targetHeight - dis) top = -targetHeight;
								else if(top > suckPh - targetHeight - dis && top < suckPh - targetHeight + dis) top = suckPh - targetHeight;
								else if(top > suckPh - dis && top < suckPh + dis) top = suckPh;
								break;
							case 2:
								targetBrother.each(function() {
									let brother = $(this), bleft = brother.position().left, btop = brother.position().top,
										bwidth = brother.outerWidth(false), bheight = brother.outerHeight(false);
									if(left <= bleft + bwidth + dis + sl && left >= bleft + bwidth - dis + sl && top + targetHeight >= btop + st && top <= btop + bheight + st) {
										left = bleft + bwidth + st;
									}else if(left + targetWidth >= bleft - dis + sl && left + targetWidth <= bleft + dis + sl && top + targetHeight >= btop + st && top <= btop + bheight + st) {
										left = bleft - targetWidth + st;
									}
									if(top <= btop + bheight + dis + st && top >= btop + bheight - dis + st && left + targetWidth >= bleft + sl && left <= bleft + bwidth + sl) {
										top = btop + bheight + st;
									}else if(top + targetHeight >= btop - dis + st && top + targetHeight <= btop + dis + st && left + targetWidth >= bleft + sl && left <= bleft + bwidth + sl) {
										top = btop - targetHeight + st;
									}
								});
								suckPw = org.parent().outerWidth(false);
								suckPh = org.parent().outerHeight(false);
								if(left < dis && left > -dis) left = 0;
								else if(left < -targetWidth + dis && left > -targetWidth - dis) left = -targetWidth;
								else if(left > suckPw - targetWidth - dis && left < suckPw - targetWidth + dis) left = suckPw - targetWidth;
								else if(left > suckPw - dis && left < suckPw + dis) left = suckPw;
								else if(left < -(parentLeft - dis)) left = -parentLeft;
								else if(left > documentPw - targetWidth - dis) left = documentPw - targetWidth;
								if(top < dis && top > -dis) top = 0;
								else if(top < -targetHeight + dis && top > -targetHeight - dis) top = -targetHeight;
								else if(top > suckPh - targetHeight - dis && top < suckPh - targetHeight + dis) top = suckPh - targetHeight;
								else if(top > suckPh - dis && top < suckPh + dis) top = suckPh;
								else if(top < -(parentTop - dis)) top = -parentTop;
								else if(top > documentPh - targetHeight - dis) top = documentPh - targetHeight;
								break;
						}
					}
					if(self.options.range) {
						if(oleft + x <= 0) left = 0;
						if(oleft + x + ow >= rangeWidth) left = rangeWidth - ow;
						if(otop + y <= 0) top = 0;
						if(otop + y + oh >= rangeHeight) top = rangeHeight - oh;
					}
					if(self.options.guide) {
						let pos = {left:Number(left), top:Number(top)}, elemGuides = self.guidesForElement(org, pos), chosenGuides = {left:{dist:dis+1}, top:{dist:dis+1}};
						$.each(self.guides, function(i, guide) {
							$.each(elemGuides, function(i, elemGuide) {
								if(guide.type === elemGuide.type) {
									let prop = guide.type === 'h' ? 'top' : 'left', d = Math.abs(elemGuide[prop] - guide[prop]);
									if(d < chosenGuides[prop].dist) {
										chosenGuides[prop].dist = d;
										chosenGuides[prop].position = elemGuide[prop] - pos[prop];
										chosenGuides[prop].guide = guide;
									}
								}
							});
						});
						if(chosenGuides.top.dist <= dis) {
							self.guideH.css('top', chosenGuides.top.guide.top).show();
							top = chosenGuides.top.guide.top - chosenGuides.top.position;
						}else {
							self.guideH.hide();
						}
						if(chosenGuides.left.dist <= dis) {
							self.guideV.css('left', chosenGuides.left.guide.left).show();
							left = chosenGuides.left.guide.left - chosenGuides.left.position;
						}else {
							self.guideV.hide();
						}
					}
					org.css({
						left: Math.floor(left),
						top: Math.floor(top)
					});
					self.triggerMove(org, true);
				}else if(rotate) {
					let curX = e.clientX;
					let curY = e.clientY;
					let curAngle = Math.atan2(curY - (orgOffset.top+targetHeight/2), curX - (orgOffset.left+targetWidth/2));
					let transferAngle = curAngle - preAngle;
					degrees += transferAngle * 180 / Math.PI;
					let _degrees = degrees;
					preX = curX;
					preY = curY;
					preAngle = curAngle;
					if(degrees >= -4 && degrees <= 4) {
						_degrees = 0;
					}else if(degrees >= 86 && degrees <= 94) {
						_degrees = 90;
					}else if(degrees >= 176 && degrees <= 184) {
						_degrees = 180;
					}else if(degrees >= 266 || degrees <= -86) {
						_degrees = -90;
					}
					org.css({
						'-webkit-transform': 'rotate('+_degrees+'deg)',
						transform: 'rotate('+_degrees+'deg)'
					});
					self.triggerRotate(org, degrees);
				}
			};
			let stageMouseup = function(e) {
				$(self.options.stage).off('mousemove', stageMousemove).off('mouseup', stageMouseup).off('mouseleave', stageMouseleave);
				if(self.options.range) $(self.options.range).off('mouseleave', rangeMouseleave);
				emove = false;
				smove = false;
				wmove = false;
				nmove = false;
				nemove = false;
				nwmove = false;
				swmove = false;
				semove = false;
				drag = false;
				rotate = false;
				if(self.options.guide) {
					self.guideH.hide();
					self.guideV.hide();
				}
				if(resize) {
					if(self.options.resize) {
						self.options.resize.call(org, {width:org.width(), height:org.height()});
					}
				}
				resize = false;
				if(started) {
					let o = e.target;
					do{
						if($(o).is('.resize-panel')) {
							self.triggerEnd(org, true);
							return true;
						}
						if($(o).is('.e, .s, .w, .n, .ne, .nw, .sw, .se, .eb, .sb, .wb, .nb') || (/^(html|body)$/i).test(o.tagName)) {
							self.triggerEnd(org);
							return true;
						}
						o = o.parentNode;
					}while(o.parentNode);
				}
			};
			let stageMouseleave = function() {
				$(self.options.stage).off('mousemove', stageMousemove).off('mouseup', stageMouseup).off('mouseleave', stageMouseleave);
				if(self.options.range) $(self.options.range).off('mouseleave', rangeMouseleave);
				emove = false;
				smove = false;
				wmove = false;
				nmove = false;
				nemove = false;
				nwmove = false;
				swmove = false;
				semove = false;
				drag = false;
				rotate = false;
				if(self.options.guide) {
					self.guideH.hide();
					self.guideV.hide();
				}
			};
			let rangeMouseleave = function() {
				emove = false;
				smove = false;
				wmove = false;
				nmove = false;
				nemove = false;
				nwmove = false;
				swmove = false;
				semove = false;
				drag = false;
				rotate = false;
				if(self.options.guide) {
					self.guideH.hide();
					self.guideV.hide();
				}
			};
			let stageHandle = function() {
				$(self.options.stage).on('mousemove', stageMousemove).on('mouseup', stageMouseup).on('mouseleave', stageMouseleave);
				if(self.options.range) $(self.options.range).on('mouseleave', rangeMouseleave);
			};
			
			//东
			el.on('mousedown', '.e, .eb', function(e) {
				if(self.skipOperate(e, org)) return true;
				started = true;
				ox = e.pageX;
				ow = el.outerWidth(false);
				rangeWidth = range.outerWidth(false);
				rangeHeight = range.outerHeight(false);
				self.triggerStart(org);
				emove = true;
				stageHandle();
			});
			
			//南
			el.on('mousedown', '.s, .sb', function(e) {
				if(self.skipOperate(e, org)) return true;
				started = true;
				oy = e.pageY;
				oh = el.outerHeight(false);
				rangeWidth = range.outerWidth(false);
				rangeHeight = range.outerHeight(false);
				self.triggerStart(org);
				smove = true;
				stageHandle();
			});
			
			//西
			el.on('mousedown', '.w, .wb', function(e) {
				if(self.skipOperate(e, org)) return true;
				started = true;
				ox = e.pageX;
				ow = el.outerWidth(false);
				oleft = parseInt(org.css('left').replace('px', ''));
				rangeWidth = range.outerWidth(false);
				rangeHeight = range.outerHeight(false);
				self.triggerStart(org);
				wmove = true;
				stageHandle();
			});
			
			//北
			el.on('mousedown', '.n, .nb', function(e) {
				if(self.skipOperate(e, org)) return true;
				started = true;
				oy = e.pageY;
				oh = el.outerHeight(false);
				otop = parseInt(org.css('top').replace('px', ''));
				rangeWidth = range.outerWidth(false);
				rangeHeight = range.outerHeight(false);
				self.triggerStart(org);
				nmove = true;
				stageHandle();
			});
			
			//东北
			el.on('mousedown', '.ne', function(e) {
				if(self.skipOperate(e, org)) return true;
				started = true;
				ox = e.pageX;
				oy = e.pageY;
				ow = el.outerWidth(false);
				oh = el.outerHeight(false);
				otop = parseInt(org.css('top').replace('px', ''));
				rangeWidth = range.outerWidth(false);
				rangeHeight = range.outerHeight(false);
				self.triggerStart(org);
				nemove = true;
				stageHandle();
			});
			
			//西北
			el.on('mousedown', '.nw', function(e) {
				if(self.skipOperate(e, org)) return true;
				started = true;
				ox = e.pageX;
				oy = e.pageY;
				ow = el.outerWidth(false);
				oh = el.outerHeight(false);
				otop = parseInt(org.css('top').replace('px', ''));
				oleft = parseInt(org.css('left').replace('px', ''));
				rangeWidth = range.outerWidth(false);
				rangeHeight = range.outerHeight(false);
				self.triggerStart(org);
				nwmove = true;
				stageHandle();
			});
			
			//东南
			el.on('mousedown', '.se', function(e) {
				if(self.skipOperate(e, org)) return true;
				started = true;
				ox = e.pageX;
				oy = e.pageY;
				ow = el.outerWidth(false);
				oh = el.outerHeight(false);
				rangeWidth = range.outerWidth(false);
				rangeHeight = range.outerHeight(false);
				self.triggerStart(org);
				semove = true;
				stageHandle();
			});
			
			//西南
			el.on('mousedown', '.sw', function(e) {
				if(self.skipOperate(e, org)) return true;
				started = true;
				ox = e.pageX;
				oy = e.pageY;
				ow = el.outerWidth(false);
				oh = el.outerHeight(false);
				oleft = parseInt(org.css('left').replace('px', ''));
				rangeWidth = range.outerWidth(false);
				rangeHeight = range.outerHeight(false);
				self.triggerStart(org);
				swmove = true;
				stageHandle();
			});
			
			//拖拽
			let preAngle = 0, preX = 0, preY = 0, degrees = 0, orgOffset = {left:0, top:0};
			el.on('mousedown', function(e) {
				let o = e.target;
				if(self.skipOperate(e, org)) return true;
				//旋转
				if($(o).is('.r, .r b')) {
					orgOffset = org.offset();
					preX = e.clientX;
					preY = e.clientY;
					targetWidth = org.outerWidth(false);
					targetHeight = org.outerHeight(false);
					preAngle = Math.atan2(preY - (orgOffset.top+targetHeight/2), preX - (orgOffset.left+targetWidth/2));
					rotate = true;
					return false;
				}
				if(self.options.guide) {
					self.guides = $.map(org.siblings().not(self.options.unusualGuide), self.guidesForElement);
				}
				started = true;
				if($(o).is('.s, .s b, .w, .w b, .e, .e b, .ne, .ne b, .nw, .nw b, .se, .se b, .sw, .sw b, .nb, .sb, .wb, .eb')) resize = true;
				ox = e.pageX;
				oy = e.pageY;
				ow = el.outerWidth(false);
				oh = el.outerHeight(false);
				otop = parseInt(org.css('top').replace('px', ''));
				oleft = parseInt(org.css('left').replace('px', ''));
				rangeWidth = range.outerWidth(false);
				rangeHeight = range.outerHeight(false);
				if($.inArray(2, self.options.suckIgnore) === -1) targetWidth = org.outerWidth(false);
				if($.inArray(3, self.options.suckIgnore) === -1) targetHeight = org.outerHeight(false);
				targetBrother = org.siblings();
				right = doc.outerWidth(false) - targetWidth;
				bottom = doc.outerHeight(false) - targetHeight;
				self.triggerStart(org, true);
				drag = true;
				stageHandle();
			});
		},
		guidesForElement: function(elem, pos){
			let _elem = $(elem), w = _elem.outerWidth(false) - 1, h = _elem.outerHeight(false) - 1;
			if(!$.isPlainObject(pos)) pos = _elem.position();
			return [
				{type:'h', left:pos.left, top:pos.top}, //垂直方向左下对齐线
				{type:'h', left:pos.left, top:pos.top + h},
				{type:'v', left:pos.left, top:pos.top},
				{type:'v', left:pos.left + w, top:pos.top},
				//中轴
				{type:'h', left:pos.left, top:pos.top + h / 2},
				{type:'v', left:pos.left + w / 2, top:pos.top}
			];
		}
	};
	
	window.ZResize = ZResize;
	
	$.fn.ZResize = function(options) {
		if(typeof options === 'undefined') return $(this).data('ZResize');
		options = $.extend({
			stage: document, //舞台
			target: '.resize-item', //缩放目标
			minWidth: '', //最小宽度, [number|100%|100px], 不能少于0
			maxWidth: '', //最大宽度, [number|100%|100px]
			minHeight: '', //最小高度, [number|100%|100px], 不能少于0
			maxHeight: '', //最大高度, [number|100%|100px]
			restrict: false, //角等比缩放
			isRotate: false, //旋转操作
			range: null, //在range的范围内拖曳
			panelCss: {}, //拖曳面板追加样式
			guide: true, //辅助线
			guideCss: {}, //辅助线追加样式
			unusualGuide: [], //例外不计算辅助线的兄弟元素
			suck: '', //吸附边缘, [0|'']不吸附, [1|parent]吸附父元素边缘, [2|all]吸附所有(包括窗口、父元素与兄弟元素)
			suckDistance: 8, //吸附临界点
			suckIgnore: [], //忽略吸附位置, [1上|2右|3下|4左]
			skipShow: null, //跳过显示面板, 接受一个参数e
			skipHide: null, //跳过隐藏面板, 接受一个参数e
			skipOperate: null, //跳过拖曳事件, 返回true即不可拖拉, 接受一个参数e
			show: null, //显示缩放面板后执行, 接受两个参数{left, top}, e
			resize: null, //拖曳大小mouseup后执行, 接受一个参数{width, height}
			start: null, //开始拖动缩放面板后执行, 接受两个参数{left, top}, isDrag
			move: null, //拖动缩放面板后执行, 接受两个参数{left, top}, isDrag
			end: null, //结束拖动面板后执行, 接受两个参数{left, top}, isDrag
			rotate: null, //旋转时执行, 接收一个参数deg
			hide: null //隐藏缩放面板后执行
		}, options, {
			target: this
		});
		return this.each(function() {
			let z = new ZResize(options);
			$(this).data('ZResize', z);
		});
	};
	
	$.fn.ZResizeStart = function() {
		let z = this.ZResize();
		if(!!z) z.triggerStart(this);
		return this;
	};
	
	$.fn.ZResizeMove = function() {
		let z = this.ZResize();
		if(!!z) z.triggerMove(this);
		return this;
	};
	
	$.fn.ZResizeEnd = function() {
		let z = this.ZResize();
		if(!!z) z.triggerEnd(this);
		return this;
	};
	
	$.fn.ZResizeHidden = function() {
		let z = this.ZResize();
		if(!!z) z.hidePanel();
		return this;
	};
	
})(jQuery);