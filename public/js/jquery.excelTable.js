/*
Developed by @mario v1.1.20210105
*/
(function($){

//table转excel工作表, 需要引入excelTable.css, sheetTitle,sheetIndex,autoInsert不需要操作
$.fn.excelTable = function(options, sheetTitle, sheetIndex, autoInsert){
	options = $.extend({
		data: null, //填充数据, 多工作表{sheet:[[column, column], [column, column]]}, 单工作表[[column, column], [column, column]]
		debug: false, //单元格编辑完不删除textarea
		row: 1, //冻结首行数, 若存在thead即强制为1, 为0即不冻结
		column: 0, //冻结首列数, 为0即不冻结
		newline: true, //单元格编辑可回车换行
		dragCopy: true, //向下拖动选中单元格十字可向下复制
		columnType: [], //列类型, 数组索引为列索引, 支持类型['(text|空)', 'select|option1|option2', 'checkbox{td内容为true|false}', '(date|time|datetime)[|yyyy-m-d]{需引入datepicker.css}', 'color[|(small|big|panel)]{td内容为#000000}']
		edit: null, //单元格在编辑后执行, 参数:td,(input|value),sheetTitle,row,column,maxRow,maxColumn, 不设置即不可编辑
		insertRow: null, //插入行后执行, 参数:插入行
		insertColumn: null, //插入列后执行, 参数:插入列的数组
		deleteRowBefore: null, //删除行前执行, 参数:删除行, 返回false不删除
		deleteRow: null, //删除行后执行, 参数:删除行的索引
		deleteColumnBefore: null, //删除列前执行, 参数:删除列, 返回false不删除
		deleteColumn: null, //删除列后执行, 参数:删除列的行索引,列索引
		ditSheetTitle: null, //修改工作表名称后执行
		insertSheet: null, //增加工作表后执行, 参数:增加的工作表,名称,是否自动增加, 不设置即不可增加工作表(隐藏工作表tab)
		deleteSheetBefore: null, //删除工作表前执行, 参数:删除工作表, 返回false不删除
		deleteSheet: null, //删除工作表后执行, 参数:删除工作表的名称, 不设置即不可删除工作表
		getData: null, //获取当前所有单元格数据, 参数:包含工作表名称的object|二维数组
		complete: null //生成表格后执行, 参数:是否第二次以上的complete
	}, options);
	options.row = parseInt(String(options.row));
	options.column = parseInt(String(options.column));
	if(options.row < 1 && options.column < 1 && !this.children('thead').length)return this;
	if(options.row < 1)options.row = 0;
	if(options.column < 1)options.column = 0;
	if(typeof sheetIndex === 'undefined')sheetIndex = 0;
	if(typeof sheetIndex !== 'number')sheetIndex = Number(sheetIndex);
	return this.each(function(){
		let _this = $(this).css({position:'relative'}), doc = $(document), opt = $.extend({}, options), table = _this.children('table'), thead = null, tbody = table;
		if(table.length !== 1)return true;
		if(!table.find('th, td').eq(0).children('div').length)table.find('th, td').wrapInner('<div></div>');
		let thisInited = _this.find('.excel-sheet').length > 0;
		let sheet = table.wrap('<div class="excel-sheet" data-sheet-title="'+(typeof sheetTitle === 'undefined' ? 'Sheet'+(sheetIndex+1) : sheetTitle)+'"></div>').parent().css({position:'relative', width:'100%', height:'100%'});
		let wrap = table.wrap('<section class="excel-wrap"></section>').parent().css({position:'relative', width:'100%', height:'100%', overflow:'auto'});
		if(table.find('thead').length){
			thead = table.find('thead');
			sheet.data('excel.thead', thead);
			opt.row = 1;
		}
		if(table.find('tbody').length)tbody = table.find('tbody');
		sheet.data('excel.tbody', tbody);
		sheet.getTitle = function(){return sheet.attr('data-sheet-title')};
		_this.data('excel.options', opt);
		if(!!_this.data('excel.sheets')){
			let sheets = _this.data('excel.sheets');
			sheets.push(sheet);
			_this.data('excel.sheets', sheets);
		}else{
			_this.data('excel.sheets', [sheet]);
		}
		let getData = function(nonCallback){
				let isInsertSheet = $.isFunction(opt.insertSheet), data = isInsertSheet ? {} : [];
				if(isInsertSheet){
					_this.children('.excel-sheet').each(function(){
						let key = $(this).attr('data-sheet-title'), tbody = $(this).children('section').find('tbody'), thead = $(this).children('section').find('thead')||null;
						let max = getMax(tbody, thead), maxRow = max.row + 1, maxColumn = max.column + 1 + opt.column;
						data[key] = [];
						let rows = tbody.find('tr:lt('+(maxRow+(thead?0:1))+')'+(thead?'':':gt('+(opt.row-1)+')'));
						rows.each(function(){
							let item = [];
							let columns = $(this).find('th:lt('+maxColumn+')'+(opt.column<=0?'':':gt('+(opt.column-1)+')')+', td:lt('+maxColumn+')'+(!opt.column?'':':gt('+(opt.column-1)+')'));
							columns.each(function(){
								item.push($(this).children('div').html());
							});
							data[key].push(item);
						});
					});
				}else{
					let tbody = _this.children('.excel-sheet').children('section').find('tbody'), thead = _this.children('.excel-sheet').children('section').find('thead')||null;
					let max = getMax(tbody, thead), maxRow = max.row + 1, maxColumn = max.column + 1 + opt.column;
					let rows = tbody.find('tr:lt('+(maxRow+(thead?0:1))+')'+(thead?'':':gt('+(opt.row-1)+')'));
					rows.each(function(){
						let item = [];
						let columns = $(this).find('th:lt('+maxColumn+')'+(opt.column<=0?'':':gt('+(opt.column-1)+')')+', td:lt('+maxColumn+')'+(!opt.column?'':':gt('+(opt.column-1)+')'));
						columns.each(function(){
							item.push($(this).children('div').html());
						});
						data.push(item);
					});
				}
				if($.isFunction(opt.getData) && !nonCallback)opt.getData.call(_this, data);
				return data;
			},
			setData = function(data){
				$.each(data, function(r, rowItem){
					if(!$.isArray(rowItem) || !rowItem.length)return true;
					$.each(rowItem, function(c, columnItem){
						if(tbody.find('tr').length <= r){
							tbody.find('tr').last().find('th, td').last().addClass('excel-select-td');
							sheet.find('.excel-contextmenu .excel-insert-down').trigger('click');
						}
						if(tbody.find('tr').last().find('th, td').length <= c){
							tbody.find('tr').last().find('th, td').last().addClass('excel-select-td');
							sheet.find('.excel-contextmenu .excel-insert-right').trigger('click');
						}
						tbody.find('tr').eq(r + (thead ? 0 : opt.row)).find('th, td').eq(c + opt.column).children('div').html(String(columnItem));
					});
				});
			},
			hideContextmenu = function(justContextmenu){
				$('.excel-contextmenu').removeAttr('data-display').hide();
				if(!justContextmenu)$('.excel-select-list').removeClass('excel-shown');
			},
			insertSheet = function(tab, key, sheetData, autoInsert, callback){
				hideContextmenu();
				if(!tab.length)return;
				let sheet = _this.children('.excel-sheet').eq(0), thead = sheet.data('excel.thead')||null, tbody = sheet.data('excel.tbody');
				if(!sheetData){
					tab.children('div').removeClass('excel-selected-tab');
					_this.find('.excel-sheet').hide();
				}
				tab.find('span').before('<div class="'+(!sheetData ? 'excel-selected-tab' : '')+'"><em></em><font>'+key+'</font></div>');
				let html = '<table>';
				if(thead)html += thead.outerHTML();
				html += '<tbody>';
				tbody.find('tr').each(function(i){
					html += '<tr>';
					$(this).find('th, td').each(function(j){
						html += '<'+this.tagName+'><div>';
						if( (!thead && opt.row > i) || opt.column > j )html += $(this).children('div').html();
						html += '</div></'+this.tagName+'>';
					});
					html += '</tr>';
				});
				html += '</tbody></table>';
				let object = $(html);
				tab.before(object);
				_this.excelTable($.extend({}, opt, {data:sheetData ? sheetData : null}), key, _this.children('.excel-sheet').length, autoInsert);
				setTimeout(function(){
					opt.insertSheet.call(_this, object.parent().parent(), key, autoInsert);
					if($.isFunction(callback))callback.call(_this, object.parent().parent(), key);
				}, 200);
			},
			setFreezeSize = function(_sheet){
				_sheet.find('.excel-head').each(function(){
					$(this).children('section').find('tbody').find('tr:eq(0)').find('th, td').each(function(i){
						$(this).children('div').width(_sheet.find('tbody').find('tr:eq(0)').find('th, td').eq(i).children('div').width());
					});
				});
				_sheet.find('.excel-side').children('section').find('tbody').find('tr').each(function(i){
					let td = _sheet.find('tbody').find('tr').eq(i).find('th, td').eq(0), padding = td.padding(), border = td.border();
					$(this).find('th, td').eq(0).children('div').height(td.outerHeight(false) - padding.top - padding.bottom - border.bottom);
				});
			},
			restoreTable = function(){
				_this.find('.excel-select-td').removeClass('excel-select-td');
				_this.find('.excel-head-select-td').removeClass('excel-head-select-td');
				_this.find('.excel-side-select-td').removeClass('excel-side-select-td');
				_this.find('.excel-select-column').removeClass('excel-select-column');
				_this.find('.excel-select-row').removeClass('excel-select-row');
				_this.find('.excel-grid').css({top:-99999, left:-99999, display:'none'});
				_this.find('.excel-grid-clone').removeAttr('data-grid-clone-type').css({top:-99999, left:-99999, display:'none'});
				_this.find('[data-grid-clone-td]').removeAttr('data-grid-clone-td');
				hideContextmenu();
			},
			getMax = function(tbody, thead){
				let maxRow = 0, maxColumn = 0;
				tbody.find('tr'+(thead?'':':gt('+(opt.row-1)+')')).each(function(i){
					$(this).find('th'+(opt.column>0?':gt('+(opt.column-1)+')':'')+', td'+(opt.column>0?':gt('+(opt.column-1)+')':'')).each(function(j){
						if($(this).children('div').html().length){
							if(maxRow < i)maxRow = i;
							if(maxColumn < j)maxColumn = j;
						}
					});
				});
				return {row:maxRow, column:maxColumn};
			};
		_this.data('excel.insertSheet', insertSheet);
		_this.data('excel.getData', getData);
		_this.data('excel.getMax', getMax);
		if($.isFunction(opt.insertSheet) && !thisInited){
			_this.addClass('excel-sheets');
			let tab = $('<div class="excel-tab"><div class="excel-selected-tab"><em></em><font>Sheet1</font></div><span></span></div>').unselect();
			if($.isFunction(opt.deleteSheet))tab.addClass('excel-sheet-remove');
			_this.append(tab);
			tab.on('click', 'span', function(){
				insertSheet(tab, 'Sheet'+(tab.children('div').length+1));
			}).on('click', 'em', function(){
				hideContextmenu();
				if(tab.children('div').length <=1){
					alert('至少要有一个工作表');
					return;
				}
				let div = $(this).parent(), index = div.index(), _sheet = _this.children('.excel-sheet').eq(index), sheetTitle = _sheet.attr('data-sheet-title');
				if($.isFunction(opt.deleteSheetBefore)){
					let res = opt.deleteSheetBefore.call(_this, _sheet);
					if(typeof res === 'boolean' && !res)return false;
				}
				if(div.hasClass('excel-selected-tab')){
					let first = tab.children('div').not('.excel-selected-tab').eq(0).addClass('excel-selected-tab');
					_this.children('.excel-sheet').eq(first.index()).show();
				}
				_sheet.remove();
				div.remove();
				if($.isFunction(opt.deleteSheet))opt.deleteSheet.call(_this, sheetTitle);
			}).on('click', 'font', function(){
				hideContextmenu();
				let div = $(this).parent();
				if(div.hasClass('excel-selected-tab'))return true;
				div.addClass('excel-selected-tab').siblings().removeClass('excel-selected-tab');
				let _sheet = _this.find('.excel-sheet').hide().eq(div.index()).show();
				setFreezeSize(_sheet);
			}).on('dblclick', 'font', function(){
				hideContextmenu();
				let font = $(this), title = prompt('工作表名称', font.html());
				if(!title || !title.length)return true;
				font.html(title);
				_this.find('.excel-sheet').eq($(this).parent().index()).attr('data-sheet-title', title);
				if($.isFunction(opt.editSheetTitle))opt.editSheetTitle.call(_this);
			});
		}
		if($.isPlainObject(opt.data)){
			let k = -1;
			$.each(opt.data, function(key, sheetItem){
				k++;
				if(sheetIndex === 0){
					if(k > 0){
						if(!_this.children('.excel-sheet[data-sheet-title="'+key+'"]').length){
							insertSheet(_this.children('.excel-tab'), key, sheetItem, true);
							return true;
						}
					}
				}
				if(k === sheetIndex){
					if(!$.isArray(sheetItem) || !sheetItem.length)return true;
					if(sheetIndex === 0){
						_this.children('.excel-sheet:eq(0)').attr('data-sheet-title', key);
						_this.children('.excel-tab').find('div:eq(0)').find('font').html(key);
					}
					setData(sheetItem);
				}
			});
		}else if($.isArray(opt.data)){
			setData(opt.data);
		}
		let setFreezeHead = function(container, insideFreezeSide){
			let freezeHeadHeight = 0, html = '';
			if(thead){
				freezeHeadHeight += thead.outerHeight(false);
				html += '<table><tbody>' + thead.find('tr').outerHTML() + '</tbody></table>';
			}else{
				html += '<table><tbody>';
				tbody.find('tr').each(function(i){
					freezeHeadHeight += $(this).outerHeight(false);
					html += $(this).outerHTML();
					if(opt.row === i+1)return false;
				});
				html += '</tbody></table>';
			}
			let freezeHead = $('<div class="excel-head"><section>'+html+'</section></div>').css({position:'absolute', 'z-index':11, top:0, overflow:'hidden', width:'100%', height:freezeHeadHeight + 1});
			container.append(freezeHead);
			freezeHead.find('section').css({position:'absolute', left:0, top:0, overflow:'hidden', height:'100%'});
			if(insideFreezeSide){
				freezeHead.addClass('excel-corner');
			}else{
				freezeHead.on('click', 'th, td', function(){
					let isSelected = $(this).hasClass('excel-head-select-td');
					restoreTable();
					if(isSelected)return false;
					let column = $(this).addClass('excel-head-select-td').index();
					tbody.find('tr').each(function(){
						$(this).find('th, td').eq(column).addClass('excel-head-select-td');
					});
					return false;
				});
			}
		};
		if(opt.row > 0)setFreezeHead(sheet);
		if(opt.column > 0){
			let freezeSideWidth = 0, k = 0, html = '<table>';
			if(thead){
				html += '<thead><tr>';
				for(let i=0; i<opt.column; i++)html += '<th><div>&nbsp;</div></th>';
				html += '</tr></thead>';
			}
			html += '<tbody>';
			tbody.find('tr').each(function(){
				html += '<tr>';
				$(this).find('th, td').each(function(i){
					if(opt.column > k)freezeSideWidth += $(this).outerWidth(false);
					html += $(this).outerHTML();
					k++;
					if(opt.column === i+1)return false;
				});
				html += '</tr>';
			});
			html += '</tbody></table>';
			let freezeSide = $('<div class="excel-side"><section>'+html+'</section></div>').css({position:'absolute', 'z-index':12, top:0, overflow:'hidden', width:freezeSideWidth + 1, height:'100%'});
			sheet.append(freezeSide);
			freezeSide.find('section').css({position:'absolute', left:0, top:0, overflow:'hidden', width:'100%'});
			freezeSide.find('section').on('click', 'th, td', function(){
				let isSelected = $(this).hasClass('excel-side-select-td');
				restoreTable();
				if(isSelected)return false;
				tbody.find('tr').eq($(this).addClass('excel-side-select-td').parent().index()).find('th, td').addClass('excel-side-select-td');
				return false;
			});
			if(opt.row > 0)setFreezeHead(freezeSide, true);
		}
		wrap.on('scroll', function(e){
			if(e.preventDefault)e.preventDefault();
			sheet.children('.excel-head').children('section').css('left', -wrap.scrollLeft());
			sheet.children('.excel-side').children('section').css('top', -wrap.scrollTop());
			let selectTd = table.find('.excel-select-td');
			let contextmenu = sheet.find('.excel-contextmenu');
			if(!!contextmenu.attr('data-display')){
				let offset = selectTd.position(), tdWidth = selectTd.outerWidth(false), tdHeight = selectTd.outerHeight(false),
					sideWidth = sheet.find('.excel-side').width(), headHeight = sheet.find('.excel-head').height();
				if(offset.left + tdWidth/2 - wrap.scrollLeft() - sideWidth <= 0 || offset.top + tdHeight/2 - wrap.scrollTop() - headHeight <= 0){
					contextmenu.hide();
				}else{
					contextmenu.css({left:Number(contextmenu.attr('data-origin-left'))-wrap.scrollLeft(), top:Number(contextmenu.attr('data-origin-top'))-wrap.scrollTop()}).show();
				}
			}
			let gridClone = sheet.find('.excel-grid-clone');
			if(gridClone.css('display') !== 'none'){
				if(selectTd.length){
					let offset = selectTd.position(),
						sideWidth = sheet.find('.excel-side').width(), headHeight = sheet.find('.excel-head').height();
					gridClone.css({left:offset.left - wrap.scrollLeft(), top:offset.top - wrap.scrollTop()});
					if(offset.left - wrap.scrollLeft() - sideWidth <= 0 || offset.top - wrap.scrollTop() - headHeight <= 0){
						gridClone.css('z-index', 10);
					}else{
						gridClone.css('z-index', 13);
					}
				}else if(gridClone.attr('data-grid-clone-type') === 'row'){
					let offset = tbody.find('[data-grid-clone-td="row"]').position(), sideWidth = sheet.find('.excel-side').width();
					gridClone.css({left:offset.left - wrap.scrollLeft()});
					if(offset.left - wrap.scrollLeft() - sideWidth <= 0){
						gridClone.css('z-index', 11);
					}else{
						gridClone.css('z-index', 13);
					}
				}else if(gridClone.attr('data-grid-clone-type') === 'column'){
					let offset = tbody.find('[data-grid-clone-td="column"]').position(), headHeight = sheet.find('.excel-head').height();
					gridClone.css({top:offset.top - wrap.scrollTop()});
					if(offset.top - wrap.scrollTop() - headHeight <= 0){
						gridClone.css('z-index', 10);
					}else{
						gridClone.css('z-index', 13);
					}
				}
			}
		});
		if(autoInsert)sheet.hide();
		if(!$.isFunction(opt.edit)){
			sheet.addClass('excel-nonedit');
		}else{
			let grid = $('<div class="excel-grid"></div>').css({position:'absolute', 'z-index':9, top:-99999, left:-99999, 'box-sizing':'border-box', display:'none'}),
				gridClone = $('<div class="excel-grid-clone"><svg><rect x="0" y="0" width="100%" height="100%"></rect></svg></div>').css({position:'absolute', 'z-index':13, top:-99999, left:-99999, display:'none'}),
				gridCopy = null, contextmenuWidth = 0, contextmenuHeight = 0, contextmenu = null, copyObject = null,
				showContextmenu = function(currentTd, e){
					currentTd = $(currentTd);
					contextmenu.css({left:-99999, top:-99999});
					let screen = $.window(), offset = sheet.offset(), touches = $.touches(e), toucheLeft = touches.x, toucheTop = touches.y,
						left = toucheLeft + 10 - offset.left, top = toucheTop + 10 - offset.top;
					currentTd.trigger('click');
					if(copyObject){
						contextmenu.find('.excel-paste').removeAttr('disabled');
					}else{
						contextmenu.find('.excel-paste').attr('disabled', 'disabled');
					}
					if(toucheLeft + 10 + contextmenuWidth > screen.width + screen.scrollLeft)left = toucheLeft - 10 - offset.left - contextmenuWidth;
					if(toucheTop + 10 + contextmenuHeight > screen.height + screen.scrollTop)top = toucheTop - 10 - offset.top - contextmenuHeight;
					contextmenu.attr({'data-origin-left':left, 'data-origin-top':top, 'data-display':'block'}).css({left:left, top:top}).show();
				},
				copyUp = function(){
					let td = table.find('.excel-select-td'), sideTd = table.find('.excel-side-select-td');
					if(td.length){
						let row = parseInt(grid.attr('data-row-index')), column = parseInt(grid.attr('data-column-index'));
						if(row > opt.row){
							let aboveDiv = tbody.find('tr').eq(row-1).find('th, td').eq(column).children('div'), input = aboveDiv.html(),
								padding = td.padding(), border = td.border(), max = getMax(tbody, thead), maxRow = max.row, maxColumn = max.column;
							let div = td.children('div').css({width:aboveDiv.width(), height:aboveDiv.height()}).html(input);
							grid.css({width:td.outerWidth(false) + border.right, height:td.outerHeight(false) + border.bottom});
							if(opt.row > 0)sheet.find('.excel-head').children('section').find('tbody').find('tr:eq(0)').find('th, td').eq(column).children('div').width(div.width());
							if(opt.column > 0)sheet.find('.excel-side').children('section').find('tbody').find('tr').eq(row).find('th, td').eq(0).children('div').height(td.outerHeight(false)-padding.top-padding.bottom-border.bottom);
							opt.edit.call(sheet, td, input, sheet.attr('data-sheet-title'), row - (thead ? 0 : opt.row), column - opt.column, maxRow, maxColumn);
						}
					}else if(sideTd.length){
						let row = sideTd.parent().index(), padding = sideTd.padding(), border = sideTd.border();
						if(row > opt.row){
							tbody.find('tr').eq(row).find('th, td').each(function(){
								let aboveDiv = tbody.find('tr').eq(row-1).find('th, td').eq($(this).index()).children('div'), input = aboveDiv.html();
								$(this).children('div').css({width:aboveDiv.width(), height:aboveDiv.height()}).html(input);
								//if(opt.row > 0)sheet.find('.excel-head').children('section').find('tbody').find('tr:eq(0)').find('th, td').eq(column).children('div').width(div.width());
								if(opt.column > 0)sheet.find('.excel-side').children('section').find('.excel-side-select-td').children('div').height(sideTd.outerHeight(false) - padding.top - padding.bottom - border.bottom);
							});
							if($.isFunction(opt.getData))getData();
						}
					}
				};
			wrap.append(grid);
			if(opt.dragCopy){
				let plus = $('<i></i>'), startX = 0, startY = 0, dir = 0, copyTd = null, copyTdBorder = null, copyTdWidth = 0, copyTdHeight = 0, beginX = 0, beginY = 0, copyMaxRow = -1, copyMaxColumn = -1;
				let dragCopyMove = function(e){
						let touches = $.touches(e), x = touches.x, y = touches.y;
						if(dir === 0){
							if(x > startX + 10)dir = 1;
							if(y > startY + 10)dir = 2;
						}else if(dir === 1){
							let distance = x - startX, column = copyTd.index();
							copyTd.parent().find('th:gt('+column+'), td:gt('+column+')').each(function(){
								let td = $(this), position = td.position(), tdWidth = td.outerWidth(false);
								if(distance > position.left - beginX && distance <= position.left - beginX + tdWidth){
									if(distance > position.left - beginX + tdWidth / 2){
										copyMaxColumn = td.index();
										gridCopy.show().width(position.left - beginX + tdWidth + copyTdWidth + copyTdBorder.right);
										grid.hide();
									}
									return false;
								}
							});
						}else if(dir === 2){
							let distance = y - startY, row = copyTd.parent().index(), column = copyTd.index();
							tbody.find('tr:gt('+row+')').each(function(){
								let td = $(this).find('th, td').eq(column), position = td.position(), tdHeight = td.outerHeight(false);
								if(distance > position.top - beginY && distance <= position.top - beginY + tdHeight){
									if(distance > position.top - beginY + tdHeight / 2){
										copyMaxRow = td.parent().index();
										gridCopy.show().height(position.top - beginY + tdHeight + copyTdHeight + copyTdBorder.bottom);
										grid.hide();
									}
									return false;
								}
							});
						}
					},
					dragCopyEnd = function(){
						doc.unselect(false).off('mousemove', dragCopyMove);
						if(dir === 1){
							if(copyMaxColumn > -1){
								let column = copyTd.index(), html = copyTd.children('div').html();
								let columns = copyTd.parent().find('th:lt('+(copyMaxColumn+1)+'):gt('+column+'), td:lt('+(copyMaxColumn+1)+'):gt('+column+')');
								columns.each(function(){
									$(this).children('div').html(html);
								});
								if($.isFunction(opt.getData))getData();
							}
						}else if(dir === 2){
							if(copyMaxRow > -1){
								let row = copyTd.parent().index(), column = copyTd.index(), html = copyTd.children('div').html();
								let rows = tbody.find('tr:lt('+(copyMaxRow+1)+'):gt('+row+')');
								rows.each(function(){
									$(this).find('th, td').eq(column).children('div').html(html);
								});
								if($.isFunction(opt.getData))getData();
							}
						}
						dir = 0;
						copyTd = null;
						copyTdBorder = null;
						copyTdWidth = 0;
						copyTdHeight = 0;
						beginX = 0;
						beginY = 0;
						copyMaxRow = -1;
						copyMaxColumn = -1;
						gridCopy.hide();
						grid.show();
					};
				grid.addClass('excel-grid-drag').append(plus);
				doc.on('mousedown', function(e){
					if(e.button !== 0)return true;
					let o = $.etarget(e);
					if($(o).is(plus)){
						copyTd = table.find('.excel-select-td');
						let touches = $.touches(e), offset = copyTd.position();
						startX = touches.x;
						startY = touches.y;
						copyTdBorder = copyTd.border();
						copyTdWidth = copyTd.outerWidth(false);
						copyTdHeight = copyTd.outerHeight(false);
						beginX = offset.left + copyTdWidth;
						beginY = offset.top + copyTdHeight;
						gridCopy.css({left:offset.left, top:offset.top, width:copyTd.outerWidth(false) + copyTdBorder.right, height:copyTd.outerHeight(false) + copyTdBorder.bottom});
						doc.unselect().on('mousemove', dragCopyMove);
						return false;
					}
				}).on('mouseup', dragCopyEnd);
				gridCopy = $('<div class="excel-grid-copy"><svg><rect x="0" y="0" width="100%" height="100%"></rect><rect x="0" y="0" width="100%" height="100%"></rect></svg></div>').css({
					position:'absolute', 'z-index':10, top:-99999, left:-99999, display:'none', 'pointer-events':'none'
				});
				wrap.append(gridCopy);
			}
			table.on('click', 'th, td', function(){
				_this.find('.excel-head-select-td').removeClass('excel-head-select-td');
				_this.find('.excel-side-select-td').removeClass('excel-side-select-td');
				hideContextmenu();
				let td = $(this), row = td.parent().index(), column = td.index(), offset = td.position(), border = td.border();
				table.find('th, td').removeClass('excel-select-td');
				td.addClass('excel-select-td');
				if(opt.row > 0)sheet.find('.excel-head').children('section').find('tbody').find('tr:eq(0)').find('th, td').removeClass('excel-select-column').eq(column).addClass('excel-select-column');
				if(opt.column > 0)sheet.find('.excel-side').children('section').find('tbody').find('tr').removeClass('excel-select-row').eq(row).addClass('excel-select-row');
				grid.addClass('excel-grid-drag').attr({'data-row-index':row, 'data-column-index':column}).css('display', 'block').css({
					left:offset.left, top:offset.top, width:td.outerWidth(false) + border.right, height:td.outerHeight(false) + border.bottom, 'pointer-events':'auto'
				});
				if(!opt.dragCopy)grid.removeClass('excel-grid-drag');
			}).on('contextmenu', 'td', function(e){
				showContextmenu(this, e);
				return false;
			});
			grid.on('click', function(e){
				hideContextmenu(true);
				let o = $.etarget(e);
				if($(o).is('i'))return;
				grid.css('pointer-events', 'none');
				gridClone.css({top:-99999, left:-99999, display:'none'});
				let row = parseInt(grid.attr('data-row-index')), column = parseInt(grid.attr('data-column-index')),
					td = tbody.find('tr').eq(row).find('th, td').eq(column), div = td.children('div'), originValue = div.html(), tmpValue = !!td.attr('data-excel-formula') ? td.attr('data-excel-formula') : originValue,
					minWidth = div.width(), minHeight = div.height(), padding = td.padding(), border = td.border(), inputTagName = opt.newline ? 'textarea' : 'input';
				if(opt.columnType.length > column - opt.column){
					let item = opt.columnType[column - opt.column], mark = item.split('|');
					if(mark[0] === 'select'){
						grid.css('pointer-events', 'auto');
						let list = td.find('.excel-select-list'), left = -(padding.left + border.left), top = minHeight + padding.bottom - 1;
						if(list.hasClass('excel-shown')){
							list.removeClass('excel-shown');
							return;
						}
						list.addClass('excel-shown');
						if(td.position().top + padding.top + minHeight + padding.bottom - 1 + list.outerHeight(false) > wrap.scrollTop() + wrap.height()){
							top = 'auto';
							list.addClass('excel-select-list-above').css({bottom:minHeight + padding.top - 1});
						}
						list.css({'min-width':grid.outerWidth(false)+'px', left:left, top:top});
						grid.removeClass('excel-grid-drag');
						return;
					}else if(mark[0] === 'checkbox'){
						grid.css('pointer-events', 'auto');
						let box = td.find('.excel-checkbox-label input'), checked = box[0].checked;
						box.checked(!checked, false);
						checked ? box.removeAttr('checked') : box.attr('checked', 'checked');
						let max = getMax(tbody, thead), maxRow = max.row, maxColumn = max.column;
						opt.edit.call(sheet, td, checked ? 'false' : 'true', sheet.attr('data-sheet-title'), row - (thead ? 0 : opt.row), column - opt.column, maxRow, maxColumn);
						return;
					}else if(mark[0] === 'date' || mark[0] === 'time' || mark[0] === 'datetime'){
						grid.css('pointer-events', 'auto');
						div.trigger('datepicker.click');
						return;
					}else if(mark[0] === 'color'){
						grid.css('pointer-events', 'auto');
						div.trigger('colorpicker.click');
						return;
					}
				}
				div.html(opt.newline ? '<textarea>'+tmpValue.replace(/<br[^>]*>/g, '\n')+'</textarea>' : '<input type="text" value="'+tmpValue.replace(/<br[^>]*>/g, '\n')+'" />');
				let input = div.find(inputTagName).css({width:minWidth, height:minHeight}).data('originValue', originValue);
				let submitInput = function(){
					let value = input.val().replace(/</g, '&lt;').replace(/\n/g, '<br />'), max = getMax(tbody, thead), maxRow = max.row, maxColumn = max.column;
					if( opt.row > 0 && opt.column > 0 &&
						/^[a-z]+$/i.test(sheet.children('.excel-head').children('section').find('tbody').find('tr:eq(0)').find('th, td').eq(opt.column).children('div').html()) &&
						/^\d+$/.test(sheet.children('.excel-side').children('section').find('tbody').find('tr').eq(opt.row).find('th, td').eq(0).children('div').html()) &&
						(/^=\s*[a-z]+\d+(\s*\+\s*[a-z]+\d+)+$/i.test($.trim(value)) || /^=\s*SUM\s*\(\s*[a-z]+\d+\s*:\s*[a-z]+\d+\s*\)$/i.test($.trim(value))) ){
						value = $.trim(value);
						td.attr('data-excel-formula', value.replace(/\s+/g, '').toUpperCase());
						let amount = 0, getCellValue = function(letter, number){
							let cellRow = -1, cellColumn = -1, val = 0, freezeHead = thead ? thead : tbody;
							freezeHead.find('tr:eq(0)').find('th, td').each(function(){
								if($(this).children('div').html().toUpperCase() === letter.toUpperCase()){
									cellColumn = $(this).index();
									return false;
								}
							});
							tbody.find('tr').each(function(){
								if(parseInt($(this).find('th, td').eq(0).children('div').html()) === parseInt(number)){
									cellRow = $(this).index();
									return false;
								}
							});
							if(cellRow > -1 && cellColumn > -1)val = parseInt(tbody.find('tr').eq(cellRow).find('th, td').eq(cellColumn).children('div').html());
							if(isNaN(val))val = 0;
							return val;
						};
						if(/^=\s*[a-z]+\d+(\s*\+\s*[a-z]+\d+)+$/i.test(value)){
							let res, re = /\s*\+\s*([a-z]+)(\d+)/ig, matcher = value.match(/^=\s*([a-z]+)(\d+)(.+)$/i);
							amount += getCellValue(matcher[1], matcher[2]);
							while((res = re.exec(matcher[3])) !== null)amount += getCellValue(res[1], res[2]);
						}else if(/^=\s*SUM\s*\(\s*[a-z]+\d+\s*:\s*[a-z]+\d+\s*\)$/i.test(value)){
							let matcher = value.match(/^=\s*SUM\s*\(\s*(([a-z]+)(\d+))\s*:\s*(([a-z]+)(\d+))\s*\)$/i);
							if(matcher[2].toUpperCase() === matcher[5].toUpperCase()){
								if(parseInt(matcher[3]) < parseInt(matcher[6])){
									for(let i=parseInt(matcher[3]); i<=parseInt(matcher[6]); i++)amount += getCellValue(matcher[2], i);
								}
							}else if(parseInt(matcher[3]) === parseInt(matcher[6])){
								if(matcher[2].toUpperCase().charCodeAt(0) < matcher[5].toUpperCase().charCodeAt(0)){
									for(let i=matcher[2].toUpperCase().charCodeAt(0); i<=matcher[5].toUpperCase().charCodeAt(0); i++)amount += getCellValue(String.fromCharCode(i), matcher[3]);
								}
							}
						}
						value = String(amount);
					}
					if(!opt.debug){
						div.html(value);
						grid.css('pointer-events', 'auto');
					}
					grid.css({width:td.outerWidth(false) + border.right, height:td.outerHeight(false) + border.bottom});
					if(opt.dragCopy)grid.addClass('excel-grid-drag');
					if(opt.row > 0)sheet.find('.excel-head').children('section').find('tbody').find('tr:eq(0)').find('th, td').eq(column).children('div').width(div.width());
					if(opt.column > 0)sheet.find('.excel-side').children('section').find('tbody').find('tr').eq(row).find('th, td').eq(0).children('div').height(div.height());
					if(value !== originValue)opt.edit.call(sheet, td, input, sheet.attr('data-sheet-title'), row - (thead ? 0 : opt.row), column - opt.column, maxRow, maxColumn);
				};
				if(opt.newline)input.height(input[0].scrollHeight);
				input.focus();
				grid.height(td.innerHeight()-border.bottom);
				if(opt.column > 0)sheet.find('.excel-side').children('section').find('tbody').find('tr').eq(row).find('th, td').eq(0).children('div').height(td.outerHeight(false) - padding.top - padding.bottom - border.bottom);
				input.on('blur', function(){
					submitInput();
				});
				if(opt.newline){
					input.on('paste cut input drop', function(){
						let _input = $(this);
						_input.css({width:'', height:''}).height(this.scrollHeight);
						_input.parent().height(_input.height());
						grid.height(td.innerHeight()-border.bottom);
						if(opt.column > 0)sheet.find('.excel-side').children('section').find('tbody').find('tr').eq(row).find('th, td').children('div').height(div.height());
					});
				}else{
					input.onkey(function(code){
						if(code === 13){
							submitInput();
							return false;
						}
					});
				}
			}).on('contextmenu', function(e){
				showContextmenu('.excel-select-td', e);
				return false;
			});
			sheet.append(gridClone.css('pointer-events', 'none'));
			contextmenu = $('<div class="excel-contextmenu">\
				<a href="javascript:void(0)" class="excel-copy">复制</a>\
				<a href="javascript:void(0)" class="excel-paste">粘贴</a>\
				<em></em>\
				<a href="javascript:void(0)" class="excel-insert-up">单元格上方插入行</a>\
				<a href="javascript:void(0)" class="excel-insert-down">单元格下方插入行</a>\
				<em></em>\
				<a href="javascript:void(0)" class="excel-insert-left">单元格左侧插入列</a>\
				<a href="javascript:void(0)" class="excel-insert-right">单元格右侧插入列</a>\
				<em></em>\
				<a href="javascript:void(0)" class="excel-delete-row">删除行</a>\
				<a href="javascript:void(0)" class="excel-delete-column">删除列</a>\
				<em></em>\
				<a href="javascript:void(0)" class="excel-about">关于</a>\
			</div>').css({position:'absolute', 'z-index':14, top:-99999, left:-99999, 'box-sizing':'border-box'});
			sheet.append(contextmenu);
			contextmenu.find('a').on('click', function(){
				let td = table.find('.excel-select-td');
				if(!td.length)return false;
				let tr = td.parent(), row = tr.index(), column = td.index(), cls = $(this).attr('class'), html = '', object = '';
				switch(cls){
					case 'excel-copy':
						keydown({target:td[0], which:67, keyCode:67, metaKey:true, ctrlKey:true});
						break;
					case 'excel-paste':
						if(!!$(this).attr('disabled'))return false;
						keydown({target:td[0], which:86, keyCode:86, metaKey:true, ctrlKey:true});
						break;
					case 'excel-copy-up':
						copyUp();
						break;
					case 'excel-insert-up':
					case 'excel-insert-down':
						let tag = tr.find('th, td')[0].tagName;
						html = '<tr>';
						for(let i=0; i<tr.find('th, td').length; i++)html += '<'+tag+'><div></div></'+tag+'>';
						html += '</tr>';
						object = $(html);
						cls === 'excel-insert-down' ? tr.after(object) : tr.before(object);
						if(opt.row > 0){
							let freezeTr = sheet.find('.excel-side').children('section').find('tbody').find('tr').eq(row), freezeHtml = '<tr>';
							let tag = freezeTr.find('th, td')[0].tagName;
							for(let i=0; i<freezeTr.find('th, td').length; i++)freezeHtml += '<'+tag+'><div></div></'+tag+'>';
							freezeHtml += '</tr>';
							cls === 'excel-insert-down' ? freezeTr.after(freezeHtml) : freezeTr.before(freezeHtml);
						}
						if($.isFunction(opt.insertRow))opt.insertRow.call(sheet, object); //判断点击插入 typeof e.isTrigger === 'undefined'
						if($.isFunction(opt.getData))getData();
						break;
					case 'excel-insert-left':
					case 'excel-insert-right':
						object = [];
						tbody.find('tr').each(function(){
							let tag = $(this).find('th, td')[0].tagName;
							html = $('<'+tag+'><div></div></'+tag+'>');
							cls === 'excel-insert-right' ? $(this).find('th, td').eq(column).after(html) : $(this).find('th, td').eq(column).before(html);
							object.push(html);
						});
						if(opt.column > 0){
							let freezeTr = sheet.find('.excel-head').children('section').find('tbody').find('tr:eq(0)');
							let tag = freezeTr.find('th, td')[0].tagName;
							cls === 'excel-insert-right' ? freezeTr.find('th, td').eq(column).after('<'+tag+'><div></div></'+tag+'>') : freezeTr.find('th, td').eq(column).before('<'+tag+'><div></div></'+tag+'>');
						}
						if($.isFunction(opt.insertColumn))opt.insertColumn.call(sheet, object);
						if($.isFunction(opt.getData))getData();
						setFreezeSize(sheet);
						break;
					case 'excel-delete-row':
						let deleteRow = function(){
							tr.remove();
							if(opt.column > 0)sheet.find('.excel-side').children('section').find('tbody').find('tr').eq(row - (thead ? 0 : opt.row)).remove();
							sheet.find('.excel-select-row').removeClass('excel-select-row');
							sheet.find('.excel-select-column').removeClass('excel-select-column');
							grid.css({top:-99999, left:-99999, display:'none'});
							if($.isFunction(opt.deleteRow))opt.deleteRow.call(sheet, row - (thead ? 0 : opt.row));
							if($.isFunction(opt.getData))getData();
							setFreezeSize(sheet);
						};
						if($.isFunction(opt.deleteRowBefore)){
							let res = opt.deleteRowBefore.call(sheet, tr);
							if( !(typeof res === 'boolean' && !res) )deleteRow();
						}else{
							deleteRow();
						}
						break;
					case 'excel-delete-column':
						let deleteColumn = function(){
							tbody.find('tr').each(function(){
								$(this).find('th, td').eq(column).remove();
							});
							if(opt.row > 0)sheet.find('.excel-head').children('section').find('tbody').find('tr:eq(0)').find('th, td').eq(column).remove();
							if(thead)thead.find('tr:eq(0)').find('th, td').eq(column).remove();
							sheet.find('.excel-select-row').removeClass('excel-select-row');
							sheet.find('.excel-select-column').removeClass('excel-select-column');
							grid.css({top:-99999, left:-99999, display:'none'});
							if($.isFunction(opt.deleteColumn))opt.deleteColumn.call(sheet, row - (thead ? 0 : opt.row), column - opt.column);
							if($.isFunction(opt.getData))getData();
							setFreezeSize(sheet);
						};
						if($.isFunction(opt.deleteColumnBefore)){
							let res = opt.deleteColumnBefore.call(sheet, td);
							if( !(typeof res === 'boolean' && !res) )deleteColumn();
						}else{
							deleteColumn();
						}
						break;
					case 'excel-about':
						hideContextmenu();
						setTimeout(function(){
							keydown({which:191, keyCode:191, shiftKey:true});
						}, 50);
						break;
				}
				hideContextmenu();
				return false;
			});
			contextmenuWidth = contextmenu.outerWidth(false);
			contextmenuHeight = contextmenu.outerHeight(false);
			contextmenu.hide();
			let keydown = function(e){
				let code = e.which||e.keyCode, meta = e.metaKey, ctrl = e.ctrlKey, alt = e.altKey, o = $.etarget(e), isMac = navigator.platform.toLowerCase().indexOf('mac') > -1,
					td = table.find('.excel-select-td'), headTd = table.find('.excel-head-select-td'), sideTd = table.find('.excel-side-select-td');
				hideContextmenu();
				if( e.shiftKey && code === 191 ){ //提示说明 Shift+?
					alert('excelTable developed by @mario v1.0.20210104\n' +
						'\n' +
						'双击单元格进行编辑\n' +
						'按Enter键或点击其他单元格完成编辑\n' +
						'向上复制单元格：'+(isMac?'⌘':'Ctrl')+'+D\n' +
						'整行、整列、单元格复制：'+(isMac?'⌘':'Ctrl')+'+C\n' +
						'粘贴内容：'+(isMac?'⌘':'Ctrl')+'+V\n' +
						'整行、整列、单元格清空：Delete/Backspace\n' +
						'单元格编辑求和公式：=A1+B2 或 =SUM(A1:A4)');
					return false;
				}
				if( (!td.length && !headTd.length && !sideTd.length) || $(o).is('input, textarea') )return true;
				if( (isMac && meta && code === 68) || (!isMac && ctrl && code === 68) ){ //复制上面单元格内容 Ctrl+D
					copyUp();
					return false;
				}
				if( (isMac && meta && code === 67) || (!isMac && ctrl && code === 67) ){ //复制(单元格|行|列)内容到copyObject Ctrl+C
					grid.css({top:-99999, left:-99999, display:'none'});
					if(td.length){
						table.find('[data-grid-clone-td]').removeAttr('data-grid-clone-td');
						let offset = td.position(), border = td.border();
						copyObject = td.clone();
						gridClone.removeAttr('data-grid-clone-type').css('display', 'block').css({
							left:offset.left - wrap.scrollLeft(), top:offset.top - wrap.scrollTop(), width:td.outerWidth(false) + border.right, height:td.outerHeight(false) + border.bottom
						});
					}else if(sideTd.length){
						let tr = table.find('.excel-side-select-td').eq(0).parent(), offset = tr.position(), border = sideTd.border();
						copyObject = tr.clone();
						tr.find('td:eq(0)').attr('data-grid-clone-td', 'column');
						copyObject.find('.excel-side-select-td').removeClass('excel-side-select-td');
						gridClone.attr('data-grid-clone-type', 'column').css('display', 'block').css({
							left:0, top:offset.top - wrap.scrollTop(), width:'100%', height:sideTd.outerHeight(false) + border.bottom
						});
					}else if(headTd.length){
						let column = headTd.index(), offset = headTd.position(), border = headTd.border(), columns = [];
						tbody.find('tr').each(function(row){
							let curTd = $(this).find('th, td').eq(column);
							columns.push(curTd.clone().removeClass('excel-head-select-td'));
							if(row === 0)curTd.attr('data-grid-clone-td', 'row');
						});
						copyObject = columns;
						gridClone.attr('data-grid-clone-type', 'row').css('display', 'block').css({
							left:offset.left - wrap.scrollLeft(), top:0, width:headTd.outerWidth(false) + border.right, height:'100%'
						});
					}
					return false;
				}
				if( ((isMac && meta && code === 86) || (!isMac && ctrl && code === 86)) ){ //粘贴内容到(单元格|新建行|新建列) Ctrl+V
					if(copyObject){
						let cloneObject = null;
						if($.isArray(copyObject)){
							cloneObject = [];
							for(let row=0; row<copyObject.length; row++){
								cloneObject.push(copyObject[row].clone());
							}
							sheet.find('.excel-head-select-td').removeClass('excel-head-select-td');
							sheet.find('.excel-side-select-td').removeClass('excel-side-select-td');
							sheet.find('.excel-select-column').removeClass('excel-select-column');
							if(td.length){
								let column = td.index();
								for(let row=0; row<copyObject.length; row++){
									tbody.find('tr').eq(row).find('td').eq(column).before(copyObject[row]);
								}
								if(opt.column > 0){
									let freezeTr = sheet.find('.excel-head').children('section').find('tbody').find('tr:eq(0)');
									let tag = freezeTr.find('th, td')[0].tagName;
									freezeTr.find('th, td').eq(column).before('<'+tag+' class="excel-select-column"><div></div></'+tag+'>');
								}
							}else if(headTd.length){
								let column = headTd.index();
								for(let row=0; row<copyObject.length; row++){
									tbody.find('tr').eq(row).find('td').eq(column).before(copyObject[row]);
								}
								if(opt.column > 0){
									let freezeTr = sheet.find('.excel-head').children('section').find('tbody').find('tr:eq(0)');
									let tag = freezeTr.find('th, td')[0].tagName;
									freezeTr.find('th, td').eq(column).before('<'+tag+'><div></div></'+tag+'>');
								}
							}else if(sideTd.length){
								return false;
							}
							if($.isFunction(opt.insertColumn))opt.insertColumn.call(sheet, copyObject);
							if($.isFunction(opt.getData))getData();
							setFreezeSize(sheet);
						}else if(copyObject.is('th, td')){
							cloneObject = copyObject.clone();
							if(!td.length)return false;
							let row = td.parent().index(), column = td.index(), padding = td.padding(), border = td.border(), max = getMax(tbody, thead), maxRow = max.row, maxColumn = max.column;
							td.html(copyObject.children('div').outerHTML());
							let div = td.children('div');
							grid.css({width:td.outerWidth(false) + border.right, height:td.outerHeight(false) + border.bottom});
							if(opt.row > 0)sheet.find('.excel-head').children('section').find('tbody').find('tr:eq(0)').find('th, td').eq(column).children('div').width(div.width());
							if(opt.column > 0)sheet.find('.excel-side').children('section').find('tbody').find('tr').eq(row).find('th, td').eq(0).children('div').height(td.outerHeight(false)-padding.top-padding.bottom-border.bottom);
							opt.edit.call(sheet, td, div.html(), sheet.attr('data-sheet-title'), row - (thead ? 0 : opt.row), column - opt.column, maxRow, maxColumn);
						}else if(copyObject.is('tr')){
							cloneObject = copyObject.clone();
							sheet.find('.excel-head-select-td').removeClass('excel-head-select-td');
							sheet.find('.excel-side-select-td').removeClass('excel-side-select-td');
							sheet.find('.excel-select-row').removeClass('excel-select-row');
							if(td.length){
								let row = td.parent().index();
								td.parent().before(copyObject);
								if(opt.row > 0){
									let freezeTr = sheet.find('.excel-side').children('section').find('tbody').find('tr').eq(row), tag = copyObject.find('th, td')[0].tagName, freezeHtml = '<tr class="excel-select-row">';
									for(let i=0; i<freezeTr.find('th, td').length; i++)freezeHtml += '<'+tag+'><div></div></'+tag+'>';
									freezeHtml += '</tr>';
									freezeTr.before(freezeHtml);
								}
							}else if(sideTd.length){
								let row = sideTd.parent().index();
								tbody.find('tr').eq(row).before(copyObject);
								if(opt.row > 0){
									let freezeTr = sheet.find('.excel-side').children('section').find('tbody').find('tr').eq(row), tag = copyObject.find('th, td')[0].tagName, freezeHtml = '<tr>';
									for(let i=0; i<freezeTr.find('th, td').length; i++)freezeHtml += '<'+tag+'><div></div></'+tag+'>';
									freezeHtml += '</tr>';
									freezeTr.before(freezeHtml);
								}
							}else if(headTd.length){
								return false;
							}
							if($.isFunction(opt.insertRow))opt.insertRow.call(sheet, copyObject);
							if($.isFunction(opt.getData))getData();
						}
						copyObject = cloneObject;
						gridClone.css({top:-99999, left:-99999, display:'none'});
						return false;
					}
					/*if(document.queryCommandSupported && document.queryCommandSupported('paste')){
						let text = document.execCommand('paste');
						if(text){
							td.children('div').html(text);
							return false;
						}
					}
					meta = false;
					ctrl = false;*/
				}
				if(!meta && !ctrl && !alt && e.key !== 'Shift' && e.key !== 'Escape'){
					let setContent = function(div, row, column, padding, border, isSingle){
						if(code !== 8 && !isSingle)return;
						if(code === 8){ //清空单元格内容 delete
							div.html('');
						}else{ //选中单元格后直接输入内容(不显示input,textarea)
							div.html(div.html() + e.key);
						}
						if(isSingle)grid.css({width:td.outerWidth(false) + border.right, height:td.outerHeight(false) + border.bottom});
						if(opt.row > 0)sheet.find('.excel-head').children('section').find('tbody').find('tr:eq(0)').find('th, td').eq(column).children('div').width(div.width());
						if(opt.column > 0)sheet.find('.excel-side').children('section').find('tbody').find('tr').eq(row).find('th, td').eq(0).children('div').height(td.outerHeight(false)-padding.top-padding.bottom-border.bottom);
					};
					if(td.length){
						let row = td.parent().index(), column = td.index(), max = getMax(tbody, thead), maxRow = max.row, maxColumn = max.column;
						setContent(td.children('div'), row, column, td.padding(), td.border(), true);
						opt.edit.call(sheet, td, td.children('div').html(), sheet.attr('data-sheet-title'), row - (thead ? 0 : opt.row), column - opt.column, maxRow, maxColumn);
					}else if(headTd.length){
						headTd.each(function(){
							let _td = $(this);
							setContent(_td.children('div'), _td.parent().index(), _td.index(), _td.padding(), _td.border());
						});
						if($.isFunction(opt.getData))getData();
					}else if(sideTd.length){
						sideTd.each(function(){
							let _td = $(this);
							setContent(_td.children('div'), _td.parent().index(), _td.index(), _td.padding(), _td.border());
						});
						if($.isFunction(opt.getData))getData();
					}
					return false;
				}
			};
			doc.on('keydown', keydown);
			if(!thisInited){
				doc.on('click', function(e){
					let o = $.etarget(e), origin = $(o);
					do{
						if($(o).is('.excel-head') || $(o).is('.excel-side')){
							restoreTable();
							origin.parent().trigger('click');
							return;
						}
						if($(o).is('.excel-wrap'))return;
						if((/^(html|body)$/i).test(o.tagName)){
							restoreTable();
							return;
						}
						o = o.parentNode;
					}while(o.parentNode);
				});
			}
			if(opt.columnType.length){
				$.each(opt.columnType, function(column, item){
					tbody.find('tr'+(thead?'':':gt('+(opt.row-1)+')')).each(function(){
						let td = $(this).find('td').eq(opt.column + column), div = td.children('div'), row = td.parent().index(), max = getMax(tbody, thead), maxRow = max.row, maxColumn = max.column,
							value = div.html(), mark = item.split('|'), val = '', html = '';
						switch(mark[0]){
							case 'select':
								val = '';
								html = '<span class="excel-select-text"></span><ul class="excel-select-list">';
								for(let i=1; i<mark.length; i++){
									if(value === mark[i])val = value;
									html += '<li'+(value === mark[i] ? ' class="this"' : '')+'>' + mark[i] + '</li>';
								}
								html += '</ul>';
								div.addClass('excel-select').html(html).find('.excel-select-text').html(val);
								break;
							case 'checkbox':
								html = '<span class="excel-checkbox-label"><input type="checkbox"'+(value.toLowerCase() === 'true' ? ' checked' : '')+' /><em></em></span>';
								div.addClass('excel-checkbox').html(html);
								break;
							case 'date':
							case 'time':
							case 'datetime':
								val = '';
								html = '<span class="excel-date-text"></span>';
								if(/^(?:(\d{4})-(\d{1,2})(?:-(\d{1,2}))?)?(?: ?(\d{1,2}))?(?::(\d{1,2}))?(?::(\d{1,2}))?$/.test(value))val = value;
								div.addClass('excel-date').attr('initdate', val).html(html).find('.excel-date-text').html(val);
								let showCal = true, showTime = false, format = 'yyyy-m-d';
								if(mark[0] === 'time'){
									showCal = false;
									format = 'hh:nn';
								}else if(mark[0] === 'datetime'){
									showTime = true;
									format = 'yyyy-m-d hh:nn';
								}
								format = mark.length > 1 ? mark[1] : format;
								if(div.datepicker)div.datepicker({
									parent: div.parent(),
									partner: '.excel-grid',
									reverseTarget: wrap,
									useClick: false,
									breakClick: true,
									showCal: showCal,
									showTime: showTime,
									format: format,
									callback: function(dates){
										let value = dates[0].formatDate(format);
										this.attr('initdate', value).find('.excel-date-text').html(value);
										div.trigger('datepicker.hidden');
										if(opt.row > 0)sheet.find('.excel-head').children('section').find('tbody').find('tr:eq(0)').find('th, td').eq(column + opt.column).children('div').width(div.width());
										opt.edit.call(sheet, td, value, sheet.attr('data-sheet-title'), row - (thead ? 0 : opt.row), column, maxRow, maxColumn);
									}
								});
								break;
							case 'color':
								val = '';
								html = '<span class="excel-color-text"></span>';
								div.addClass('excel-color').html(html).find('.excel-color-text').html(val);
								if(div.colorpicker)div.colorpicker({
									type: mark.length > 1 ? mark[1] : 'big',
									partner: '.excel-grid',
									reverseTarget: wrap,
									useClick: false,
									callback: function(color){
										this.find('.excel-color-text').css('background', color);
										if(opt.row > 0)sheet.find('.excel-head').children('section').find('tbody').find('tr:eq(0)').find('th, td').eq(column + opt.column).children('div').width(div.width());
										opt.edit.call(sheet, td, color, sheet.attr('data-sheet-title'), row - (thead ? 0 : opt.row), column, maxRow, maxColumn);
									}
								});
								break;
						}
					});
				});
				tbody.find('.excel-select').on('click', 'li', function(){
					let li = $(this), value = li.html(), td = li.parents('.excel-select').eq(0).parent(), div = td.children('div'), row = td.parent().index(), column = td.index(),
						max = getMax(tbody, thead), maxRow = max.row, maxColumn = max.column;
					li.addClass('this').siblings().removeClass('this');
					li.parent().prev().html(value);
					if(opt.row > 0)sheet.find('.excel-head').children('section').find('tbody').find('tr:eq(0)').find('th, td').eq(column).children('div').width(div.width());
					opt.edit.call(sheet, td, value, sheet.attr('data-sheet-title'), row - (thead ? 0 : opt.row), column - opt.column, maxRow, maxColumn);
				});
			}
		}
		setFreezeSize(sheet);
		if($.isFunction(opt.complete))opt.complete.call(sheet, thisInited);
	});
};

//获取当前最大行列
$.fn.excelTableGetMax = function(){
	let sheet = this.children('.excel-sheet:visible'), maxRow = 0, maxColumn = 0;
	if(sheet.length){
		let thead = sheet.data('excel.thead'), tbody = sheet.data('excel.tbody'), getMax = this.data('excel.getMax'), max = getMax(tbody, thead);
		maxRow = max.row;
		maxColumn = max.column;
	}
	return {row:maxRow, column:maxColumn};
};

//交换行列数据
$.fn.excelTableConvert = function(){
	let sheet = this.children('.excel-sheet:visible');
	if(sheet.length){
		let thead = sheet.data('excel.thead'), tbody = sheet.data('excel.tbody'), getData = this.data('excel.getData'), opt = this.data('excel.options');
		if(opt.row <= 0 || opt.column <= 0)return this;
		let data = getData(true), tr = tbody.find('tr'+(thead?'':':gt('+(opt.row-1)+')'));
		if(!data.length || (data.length === 1 && data[0].length === 1 && !data[0][0].length))return this;
		tr.each(function(){
			$(this).find('th:gt('+(opt.column-1)+'), td:gt('+(opt.column-1)+')').children('div').html('');
		});
		for(let row=0; row<data.length; row++){
			for(let column=0; column<data[row].length; column++){
				tr.eq(column).find('th, td').eq(opt.column + row).children('div').html(data[row][column]);
			}
		}
	}
	return this;
};

//获取数据
$.fn.excelTableGetData = function(){
	let getData = this.data('excel.getData');
	if(!$.isFunction(getData))return null;
	return getData(true);
};

//填充数据, 必须是 object(key:二维数组) 或 array(二维数组)
$.fn.excelTableSetData = function(data){
	if(typeof data === 'undefined' || (!$.isPlainObject(data) && !$.isArray(data)))return this;
	return this.each(function(){
		let _this = $(this), options = _this.data('excel.options'), freezeRow = options.row, freezeColumn = options.column, sheets = _this.data('excel.sheets'), insertSheet = _this.data('excel.insertSheet');
		if(!!!sheets || !$.isArray(sheets))return true;
		let setData = function(data, sheetIndex){
			let sheet = sheets[sheetIndex], thead = sheet.data('excel.thead')||null, tbody = sheet.data('excel.tbody');
			$.each(data, function(r, rowItem){
				if(!$.isArray(rowItem) || !rowItem.length)return true;
				$.each(rowItem, function(c, columnItem){
					if(tbody.find('tr').length <= r){
						tbody.find('tr').last().find('th, td').last().addClass('excel-select-td');
						sheet.find('.excel-contextmenu .excel-insert-down').trigger('click');
					}
					if(tbody.find('tr').last().find('th, td').length <= c){
						tbody.find('tr').last().find('th, td').last().addClass('excel-select-td');
						sheet.find('.excel-contextmenu .excel-insert-right').trigger('click');
					}
					tbody.find('tr').eq(r + (thead ? 0 : freezeRow)).find('th, td').eq(c + freezeColumn).children('div').html(String(columnItem));
				});
			});
		};
		if($.isPlainObject(data)){
			$.each(data, function(key, sheetItem){
				if(!$.isArray(sheetItem) || !sheetItem.length)return true;
				if(_this.children('.excel-sheet[data-sheet-title="'+key+'"]').length){
					setData(sheetItem, _this.children('.excel-sheet[data-sheet-title="'+key+'"]').index());
				}else{
					insertSheet(_this.children('.excel-tab'), key, sheetItem, true);
				}
			});
		}else if($.isArray(data)){
			setData(data, 0);
		}
	});
};

//excelTable数据辅助转换
$.extend({
	excelTable: function(data){
		//字符串格式 sheet1=s0r0c0:s0r0c1,s0r1c0:s0r1c1|sheet2=s1r0c0:s1r0c1,s1r1c0:s1r1c1 或 s0r0c0:s0r0c1,s0r1c0:s0r1c1|s1r0c0:s1r0c1,s1r1c0:s1r1c1(自动填充工作表名称) 或 r0c0:r0c1,r1c0:r1c1(单工作表)
		if(typeof data === 'string'){ //把字符串转换为object,array
			if(data.indexOf('|') > -1){
				let obj = {};
				$.each(data.split('|'), function(){
					let arr = this.split('='), sheet = arr.length>1 ? arr[0] : 'Sheet'+(Object.keys(obj).length+1), rows = arr.length>1 ? arr[1] : arr[0];
					obj[sheet] = [];
					$.each(rows.split(','), function(i, item){
						let row = [];
						$.each(item.split(':'), function(j, column){
							row.push(column);
						});
						obj[sheet].push(row);
					});
				});
				return obj;
			}else{
				let obj = [];
				$.each(data.split(','), function(i, item){
					let row = [];
					$.each(item.split(':'), function(j, column){
						row.push(column);
					});
					obj.push(row);
				});
				return obj;
			}
		}else if($.isPlainObject(data)){ //把object(key:二维数组)转换为字符串
			let str = '';
			$.each(data, function(sheet, item){
				str += sheet + '=';
				$.each(item, function(i, row){
					str += row.join(':') + ',';
				});
				str = str.trim(',') + '|';
			});
			return str.trim('|');
		}else if($.isArray(data)){ //把array(二维数组)转换为字符串
			let str = '';
			$.each(data, function(i, row){
				str += row.join(':') + ',';
			});
			return str.trim(',');
		}
	}
});

})(jQuery);
