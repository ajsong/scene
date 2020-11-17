<?php
/**
 * 使用openssl实现非对称加密
 * 
 * @since 2016-6-26
 */
class Rsa {
	/**
	 * 私钥
	 * 
	 */
	private $_privKey;
	
	/**
	 * 公钥
	 * 
	 */
	private $_pubKey;
	
	/**
	 * 保存文件地址
	 */
	private $_keyPath;
	
	/**
	 * 密钥后缀
	 */
	private $_keySuffix = '.pem';
	
	/**
	 * 指定密钥文件地址
	 * 
	 */
	public function __construct($path, $privName='private', $pubName='public')
	{
		if (!function_exists('openssl_pkey_get_private')) {
			exit('服务器不支持openssl组件');
		}
		$this->_keyPath = $path;
		$privFile = $path . DIRECTORY_SEPARATOR . $privName.$this->_keySuffix;
		$pubFile = $path . DIRECTORY_SEPARATOR . $pubName.$this->_keySuffix;
		if (!is_dir($path)) {
			$this->makeDir($path);
			$this->createKey($privName, $pubName);
		} else if (!file_exists($privFile) || !file_exists($pubFile)) {
			$this->createKey($privName, $pubName);
		} else {
			$this->privKey($privName);
			$this->pubKey($pubName);
		}
	}
	
	/**
	 * 创建公钥和私钥
	 * 
	 */
	public function createKey($privName='private', $pubName='public')
	{
		$privFile = $this->_keyPath . DIRECTORY_SEPARATOR . $privName.$this->_keySuffix;
		$pubFile = $this->_keyPath . DIRECTORY_SEPARATOR . $pubName.$this->_keySuffix;
		if (file_exists($privFile) && file_exists($pubFile)) return;
		if (file_exists($privFile)) unlink($privFile);
		if (file_exists($pubFile)) unlink($pubFile);
		$config = array(
			'digest_alg' => 'sha512',
			'private_key_bits' => 1024,
			'private_key_type' => OPENSSL_KEYTYPE_RSA,
		);
		//生成私钥
		$res = openssl_pkey_new($config);
		openssl_pkey_export($res, $privKey);
		file_put_contents($privFile, $privKey);
		$this->_privKey = openssl_pkey_get_private($privKey);
		//生成公钥
		$details = openssl_pkey_get_details($res);
		$pubKey = $details['key'];
		file_put_contents($pubFile, $pubKey);
		$this->_pubKey = openssl_pkey_get_public($pubKey);
	}
	
	/**
	 * 循环创建目录
	 * 
	 */
	public function makeDir($foldePath)
	{
		if (is_dir($foldePath)) {
			return true;
		}
		$foldePath = str_replace('\\', '/', $foldePath);
		$root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
		$relative = str_replace($root, '', $foldePath);
		$each = explode('/', $relative);
		$path = $root;
		foreach ($each as $p) {
			if ($p) {
				$path .= '/'.$p;
				if (!is_dir($path)) {
					if (mkdir($path, 0777)) {
						chmod($path, 0777);
						return true;
						//@fclose(@fopen($path . '/index.htm', 'w'));
					} else {
						return false;
					}
				} else {
					chmod($path, 0777);
				}
			}
		}
		return true;
	}
	
	/**
	 * 字符串按指定长度分割为数组
	 * 
	 */
	private function explodeStr($str, $step = 100) {
		$length = strlen($str);
		$res = array();
		for ($i=0; $i<$length; $i+=$step) {
			if (strlen($str) >= $step) {
				$res[] = substr($str, 0, $step);
				$str = substr($str, $step);
			} else {
				$res[] = $str;
			}
		}
		return $res;
	}
	
	/**
	 * 设置私钥
	 * 
	 */
	public function privKey($privName='private')
	{
		if (is_resource($this->_privKey)) {
			return true;
		}
		$file = $this->_keyPath . DIRECTORY_SEPARATOR . $privName.$this->_keySuffix;
		$privKey = file_get_contents($file);
		$this->_privKey = openssl_pkey_get_private($privKey);
		return true;
	}
	
	/**
	 * 设置公钥
	 * 
	 */
	public function pubKey($pubName='public')
	{
		if (is_resource($this->_pubKey)) {
			return true;
		}
		$file = $this->_keyPath . DIRECTORY_SEPARATOR . $pubName.$this->_keySuffix;
		$pubKey = file_get_contents($file);
		$this->_pubKey = openssl_pkey_get_public($pubKey);
		return true;
	}
	
	/**
	 * 用私钥加密
	 * 
	 */
	public function privEncode($data)
	{
		if (!is_string($data)) {
			return NULL;
		}
		$this->privKey();
		$arr = $this->explodeStr($data, 100);
		$datas = array();
		foreach ($arr as $str) {
			$result = openssl_private_encrypt($str, $encrypted, $this->_privKey);
			if ($result) {
				$datas[] = base64_encode($encrypted);
			}
		}
		return implode('', $datas);
	}
	
	/**
	 * 私钥解密
	 * 
	 */
	public function privDecode($encrypted)
	{
		if (!is_string($encrypted)) {
			return NULL;
		}
		$this->privKey();
		$arr = $this->explodeStr($encrypted, 684);
		$datas = array();
		foreach ($arr as $str) {
			$encrypted = base64_decode($str);
			$result = openssl_private_decrypt($encrypted, $decrypted, $this->_privKey);
			if ($result) {
				$datas[] = $decrypted;
			}
		}
		return implode('', $datas);
	}
	
	/**
	 * 公钥加密
	 * 
	 */
	public function pubEncode($data)
	{
		if (!is_string($data)) {
			return NULL;
		}
		$this->pubKey();
		$arr = $this->explodeStr($data, 100);
		$datas = array();
		foreach ($arr as $str) {
			$result = openssl_public_encrypt($str, $encrypted, $this->_pubKey);
			if ($result) {
				$datas[] = base64_encode($encrypted);
			}
		}
		return implode('', $datas);
	}
	
	/**
	 * 公钥解密
	 * 
	 */
	public function pubDecode($crypted)
	{
		if (!is_string($crypted)) {
			return NULL;
		}
		$this->pubKey();
		$arr = $this->explodeStr($crypted, 684);
		$datas = array();
		foreach ($arr as $str) {
			$crypted = base64_decode($str);
			$result = openssl_public_decrypt($crypted, $decrypted, $this->_pubKey);
			if ($result) {
				$datas[] = $decrypted;
			}
		}
		return implode('', $datas);
	}
	
	/**
	 * __destruct
	 * 
	 */
	public function __destruct() {
		@fclose($this->_privKey);
		@fclose($this->_pubKey);
	}
}


//header("Content-type: text/html; charset=utf-8");
//$rsa = new Rsa('keypath');
//$rsa->createKey();
/*/
//私钥加密，公钥解密
echo "待加密数据：segmentfault.com<br>";
$pre = $rsa->privEncode("segmentfault.com");
echo "加密后的密文：<br>" . $pre . "<br>";
$pud = $rsa->pubDecode($pre);
echo "解密后数据：" . $pud . "<br><br>";

//公钥加密，私钥解密(每次加密后密文会改变)
echo "待加密数据：segmentfault.com<br>";
$pue = $rsa->pubEncode("segmentfault.com");
echo "加密后的密文：<br>" . $pue . "<br>";
$prd = $rsa->privDecode($pue);
echo "解密后数据：" . $prd;
//*/
