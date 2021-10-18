
( function() {

CKEDITOR.plugins.add( 'collect',
{
	// Translations, available at the end of this file, without extra requests
	lang : [ 'en', 'es' ],

	init : function( editor )
	{
		var lang = editor.lang.collect;

		editor.addCommand( 'Collect', {
			exec : function( editor ) {
				var url = prompt( lang.tips, '' );
				if ( !url || !url.length ) return;
				if ( !/^https:\/\/mp\.weixin\.qq\.com\//.test( url ) ) {
					alert( lang.nonUrl );
					return;
				}
				var isCoo = ( typeof ( $ ) !== 'undefined' && typeof ( $.overload ) === 'function' );
				if ( isCoo ) $.overload( lang.collecting );
				function createXMLHttpRequest() {
					if ( !CKEDITOR.env.ie || location.protocol !== 'file:' ) {
						try {
							return new XMLHttpRequest();
						} catch ( e ) {}
					}
					try {
						return new ActiveXObject( 'Msxml2.XMLHTTP' );
					} catch ( e ) {}
					try {
						return new ActiveXObject( 'Microsoft.XMLHTTP' );
					} catch ( e ) {}
					return null;
				}
				var xhr = createXMLHttpRequest();
				if ( !xhr ){
					alert( 'Create XMLHttpRequest fail!' );
					return;
				}
				xhr.open( 'POST', editor.config.wechatCollectUrl, true );
				xhr.onreadystatechange = function() {
					if ( xhr.readyState === 4 ) {
						if ( isCoo ) $.overload( false );
						try {
							var json = JSON.parse( xhr.responseText );
							if ( json ) {
								if ( typeof ( json.data ) !== 'undefined' ) {
									if ( typeof ( json.error ) !== 'undefined' && typeof ( json.msg ) !== 'undefined' && Number( json.error ) !== 0 ) {
										if ( json.msg.length ) {
											isCoo ? $.overloadError( json.msg ) : alert( json.msg );
										}
									} else if ( typeof ( json.data.content ) !== 'undefined' ) {
										editor.insertHtml( json.data.content );
										var fn = eval( 'ckediterWechatCollect' );
										if ( typeof ( fn ) === 'function' ) fn( json.data );
									} else {
										alert( 'Lost "json.data.content" structure!' );
									}
								}
							} else {
								alert( 'JSON data error!' );
							}
						} catch( e ) {
							alert( e );
						}
						xhr = null;
					}
				};
				xhr.setRequestHeader( 'Content-type', 'application/x-www-form-urlencoded; charset=UTF-8' );
				xhr.send( 'url=' + encodeURIComponent( url ) );
			}
		} );
		editor.ui.addButton( 'Collect', {
			label : lang.toolbar,
			command : 'Collect',
			icon : this.path + 'images/icon.png'
		} );
	} //Init

} ); // plugins.add

var en = {
	toolbar	: 'Collect WeChat article',
	tips : 'As long as it is a WeChat article can be collected, start with https://mp.weixin.qq.com/',
	collecting : 'Please wait while the images in the article are being collected...',
	dialogTitle : 'Collect WeChat article',
	nonUrl : 'The url is not WeChat article!'
};
var cn = {
	toolbar	: '采集微信文章',
	tips : '只要是微信文章即可采集，即以 https://mp.weixin.qq.com/ 开始',
	collecting : '正在采集微信文章的图片，请稍候...',
	dialogTitle : '采集微信文章',
	nonUrl : '该网址不是微信文章！'
};
var es = {
	toolbar	: 'Collect WeChat article',
	tips : 'As long as it is a WeChat article can be collected, start with https://mp.weixin.qq.com/',
	collecting : 'Please wait while the images in the article are being collected...',
	dialogTitle : 'Collect WeChat article',
	nonUrl : 'The url is not WeChat article!'
};

// v3
if (CKEDITOR.skins)
{
	en = { collect : en} ;
	es = { collect : es} ;
	cn = { collect : cn} ;
}

// Translations
CKEDITOR.plugins.setLang( 'collect', 'en', en );
CKEDITOR.plugins.setLang( 'collect', 'es', es );
CKEDITOR.plugins.setLang( 'collect', 'zh-cn', cn );

})();