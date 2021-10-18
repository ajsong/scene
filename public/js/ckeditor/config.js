/**
 * @license Copyright (c) 2003-2015, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function( config ) {
	//Define changes to default configuration here. For example:
	//config.language = 'fr';
	//config.uiColor = '#AADC6E';
	//config.extraPlugins = 'filebrowser';
	//config.extraPlugins = 'imgupload';
	//config.skin = 'moono';
	config.skin = 'moono-lisa';
	config.toolbar = 'Full';
	config.toolbar_Full = [
		['Source','-','Maximize', 'ShowBlocks','-','Preview','Templates','-','Cut','Copy','Paste','PasteText','PasteFromWord'],
		['Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat'],
		['Bold','Italic','Underline','Strike','-','Subscript','Superscript'],
		['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock','-','TextColor','BGColor','-','NumberedList','BulletedList','-','Outdent','Indent','Blockquote'],
		'/',
		['Styles','Format','Font','FontSize'],
		['Link','Unlink','Anchor','-','Image','Collect','Video','Table','HorizontalRule','SpecialChar','PageBreak']
	];
	config.toolbar_Simple = [
		['Source','-','Bold','Italic','Underline','Strike'],
		['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock','-','TextColor','BGColor'],
		['Format','Font','FontSize','-','Link','Unlink','Image']
	];
	config.allowedContent = true;
	config.extraPlugins = 'collect,video';
	config.filebrowserUploadUrl = '/index/ckediter_upload';
	config.wechatCollectUrl = '/index/ckediter_wechat_collect';
	let width = (window.jQuery && typeof(CKEDITOR.instances)!=='undefined' && typeof(CKEDITOR.instances.content)!=='undefined') ? window.jQuery(CKEDITOR.instances.content.element.$).width() : 410;
	let height = (window.jQuery && typeof(CKEDITOR.instances)!=='undefined' && typeof(CKEDITOR.instances.content)!=='undefined') ? window.jQuery(CKEDITOR.instances.content.element.$).height() : 300;
	config.width = width>410 ? width : 410;
	config.height = height>300 ? height : 300;
};
