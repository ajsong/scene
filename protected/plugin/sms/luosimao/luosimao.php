<?php
//https://luosimao.com
class luosimao_api{
    //Luosimao api key
    private $_api_key = '';
	private $_voice_api_key = '';
    
    private $_sign = '【铁壳测试】';

    private $_last_error = array();

    private $_use_ssl = FALSE;

    private $_ssl_api_url = array(
        'send'       => 'https://sms-api.luosimao.com/v1/send.json',
        'send_batch' => 'https://sms-api.luosimao.com/v1/send_batch.json',
	    'status'     => 'https://sms-api.luosimao.com/v1/status.json',
	    'voice'     => 'https://voice-api.luosimao.com/v1/verify.json',
	    'voice_status'     => 'https://voice-api.luosimao.com/v1/status.json',
    );

    private $_api_url = array(
        'send'       => 'http://sms-api.luosimao.com/v1/send.json',
        'send_batch' => 'http://sms-api.luosimao.com/v1/send_batch.json',
        'status'     => 'http://sms-api.luosimao.com/v1/status.json',
	    'voice'     => 'http://voice-api.luosimao.com/v1/verify.json',
	    'voice_status'     => 'http://voice-api.luosimao.com/v1/status.json',
    );

    /**
     * @param array $param 配置参数
     * api_key api秘钥，在luosimao短信后台短信->触发发送下面可查看
     * use_ssl 启用HTTPS地址，HTTPS有一定性能损耗，可选，默认不启用
     */
    public function __construct( $param =  array() ){
        if ( !isset( $param['api_key'] ) && !isset( $param['voice_api_key'] ) ) {
            die("api key error.");
        }
	
	    if ( isset( $param['api_key'] ) ) {
		    $this->_api_key = $param['api_key'];
	    }
	
	    if ( isset( $param['voice_api_key'] ) ) {
		    $this->_voice_api_key = $param['voice_api_key'];
	    }
	
	    if ( isset( $param['use_ssl'] ) ) {
		    $this->_use_ssl = $param['use_ssl'];
	    }
	
	    if ( isset( $param['sign'] ) ) {
		    $this->_sign = $param['sign'];
	    }

    }

    //触发，单发，适用于验证码，订单触发提醒类
    public function send( $mobile , $message = '' ){
        return $this->send_batch($mobile, $message);
    }

    //批量发送，用于大批量发送
    public function send_batch( $mobile_list = array() , $message = '' , $time = '' ){
        $api_url = !$this->_use_ssl ? $this->_api_url['send_batch'] : $this->_ssl_api_url['send_batch'];
        $mobile_list = is_array( $mobile_list ) ? implode( ',' , $mobile_list ) : $mobile_list;
        $param = array(
            'mobile_list' => $mobile_list ,
	        'message' => "{$message}{$this->_sign}", //公共签名为【铁壳测试】, 在螺丝帽后台的短信->签名管理下可添加自定义签名
            'time'    => $time,
        );
        $res = $this->http_post( $this->_api_key, $api_url ,$param );
        return @json_decode( $res ,TRUE );
    }

    //获取短信账号余额
    public function get_deposit(){
        $api_url = !$this->_use_ssl ? $this->_api_url['status'] : $this->_ssl_api_url['status'];
        $res = $this->http_get( $this->_api_key, $api_url );
        return @json_decode( $res ,TRUE );
    }
    
    //发送语音验证码, 支持4-6位数字验证码
	public function voice( $mobile , $code = '' ){
		$api_url = !$this->_use_ssl ? $this->_api_url['voice'] : $this->_ssl_api_url['voice'];
		$param = array(
			'mobile' => "{$mobile}",
			'code' => "{$code}",
		);
		$res = $this->http_post( $this->_voice_api_key, $api_url ,$param );
		return @json_decode( $res ,TRUE );
	}
	
	//获取语音验证码余额
	public function get_voice_deposit(){
		$api_url = !$this->_use_ssl ? $this->_api_url['voice_status'] : $this->_ssl_api_url['voice_status'];
		$res = $this->http_get( $this->_voice_api_key, $api_url );
		return @json_decode( $res ,TRUE );
	}

    /**
     * @param string $type 接收类型，用于在服务器端接收上行和发送状态，接收地址需要在luosimao后台设置
     * @param array $param  传入的参数，从推送的url中获取，官方文档：https://luosimao.com/docs/api/
     */
    public function recv( $type = 'status' , $param = array() ){
        if ( $type == 'status' ) {
            if ( $param['batch_id'] && $param['mobile'] && $param['status'] ) { //状态
                // do record
            }
        } else if ( $type == 'incoming' ) { //上行回复
            if ( $param['mobile'] && $param['message'] ) {
                // do record
            }
        }
    }

    /**
     * @param string $api_url 接口地址
     * @param array $param post参数
     * @param int $timeout 超时时间
     * @return bool
     */
    private function http_post( $api_key = '' , $api_url = '' , $param = array() , $timeout = 5 ){
        if ( !$api_url ) {
            die("error api_url");
        }

        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $api_url );

        curl_setopt( $ch, CURLOPT_HTTP_VERSION  , CURL_HTTP_VERSION_1_0 );
        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $timeout );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt( $ch, CURLOPT_HEADER, FALSE);

        $scheme = parse_url( $api_url );
        if( $scheme['scheme'] == 'https' ){
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST , FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER , FALSE);
        }
        curl_setopt( $ch, CURLOPT_HTTPAUTH , CURLAUTH_BASIC);
        curl_setopt( $ch, CURLOPT_USERPWD  , 'api:key-'.$api_key );
        curl_setopt( $ch, CURLOPT_POST, TRUE);
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $param );

        $res    = curl_exec( $ch );
        $error  = curl_error( $ch );
        curl_close( $ch );
        if ( $error ) {
            $this->_last_error[] =  $error;
            return FALSE;
        }
        return $res;
    }

    /**
     * @param string $api_url 接口地址
     * @param string $timeout 超时时间
     * @return bool
     */
    private function http_get( $api_key = '' , $api_url = '' , $timeout = 5 ){
        if ( !$api_url ) {
            die("error api_url");
        }

        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $api_url );

        curl_setopt( $ch, CURLOPT_HTTP_VERSION  , CURL_HTTP_VERSION_1_0 );
        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $timeout );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt( $ch, CURLOPT_HEADER, FALSE);

        $scheme = parse_url( $api_url );
        if( $scheme['scheme'] == 'https' ){
            curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST , FALSE);
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER , FALSE);
        }
        curl_setopt( $ch, CURLOPT_HTTPAUTH , CURLAUTH_BASIC);
        curl_setopt( $ch, CURLOPT_USERPWD  , 'api:key-'.$api_key );

        $res    = curl_exec( $ch );
        $error  = curl_error( $ch );
        curl_close( $ch );
        if ( $error ) {
            $this->_last_error[] =  curl_error( $ch );
            return FALSE;
        }
        return $res;
    }
	
	public function get_error( $error = 0 ) {
		$msg = '';
		switch ( intval($error) ) {
			case -10:$msg = '验证信息失败';break; //检查api key是否和各种中心内的一致，调用传入是否正确
			case -11:$msg = '接口禁用';break; //滥发违规内容，验证码被刷等，请联系客服解除
			case -20:$msg = '短信余额不足';break; //进入个人中心购买充值
			case -30:$msg = '短信内容为空';break; //检查调用传入参数：message
			case -31:$msg = '短信内容存在敏感词';break; //修改短信内容，更换词语
			case -32:$msg = '短信内容缺少签名信息';break; //短信内容末尾增加签名信息，格式为：【公司名称】
			case -33:$msg = '短信内容缺少签名信息';break; //短信内容末尾增加签名信息eg.【公司名称】
			case -34:$msg = '签名不可用';break; //在后台 短信->签名管理下进行添加签名
			case -40:$msg = '错误的手机号';break; //检查手机号是否正确
			case -41:$msg = '号码在黑名单中';break; //号码因频繁发送或其他原因暂停发送，请联系客服确认
			case -42:$msg = '验证码类短信发送频率过快';break; //前台增加60秒获取限制
			case -43:$msg = '号码数量太多';break; //单次提交控制在10万个号码以内
			case -50:$msg = '请求发送IP不在白名单内';break; //查看触发短信IP白名单的设置
			case -60:$msg = '定时时间为过去';break; //检查定时的时间，取消定时或重新设定定时时间
		}
		return $msg;
	}

    public function last_error(){
        return $this->_last_error;
    }
}
