<?php
class ImagickClass {
	private $image = null;
	private $type = null;
	
	//构造函数
	public function __construct($path='') {
		if (!class_exists('Imagick')) die('Imagick plugin is not install');
		if (strlen($path)) $this->open($path);
		return $this;
	}
	
	//析构函数
	public function __destruct() {
		if ($this->image!==null) $this->image->destroy();
	}
	
	//载入图像
	public function open($path) {
		$this->image = new Imagick($path);
		if ($this->image) {
			$this->type = strtolower($this->image->getImageFormat());
		}
		return $this;
	}
	
	//裁剪图像
	public function crop($x=0, $y=0, $width=null, $height=null) {
		if ($width==null) $width = $this->image->getImageWidth()-$x;
		if ($height==null) $height = $this->image->getImageHeight()-$y;
		if ($width<=0 || $height<=0) return;
		if ($this->type=='gif') {
			$image = $this->image;
			$canvas = new Imagick();
			$images = $image->coalesceImages();
			foreach ($images as $frame) {
				$img = new Imagick();
				$img->readImageBlob($frame);
				$img->cropImage($width, $height, $x, $y);
				$canvas->addImage($img);
				$canvas->setImageDelay($img->getImageDelay());
				$canvas->setImagePage($width, $height, 0, 0);
			}
			$image->destroy();
			$this->image = $canvas;
		} else {
			$this->image->cropImage($width, $height, $x, $y);
		}
		return $this;
	}
	
	/*
	* 更改图像大小
	$fit: 适应大小方式
	'force': 把图片强制变形成 $width * $height 大小
	'scale': 按比例在安全框 $width * $height 内缩放图片, 输出缩放后图像大小 不完全等于 $width X $height
	'scale_fill': 按比例在 $width * $height 内缩放图片，没有像素的地方填充色, 使用此参数时可设置背景填充色 $bg_color=array(255,255,255,0)(红,绿,蓝, 透明度)(0不透明-127完全透明))
	其它: 智能模能 缩放图像并载取图像的中间部分 $width * $height 像素大小
	$fit = 'force','scale','scale_fill' 时： 输出完整图像
	$fit = 图像方位值 时, 输出指定位置部分图像
	字母与图像的对应关系如下:
	north_west   north   north_east
	
	west         center        east
	
	south_west   south   south_east
	*/
	public function resize($width=100, $height=100, $fit='center', $fill_color=array(255,255,255,0)) {
		switch ($fit) {
			case 'force':
				if ($this->type=='gif') {
					$image = $this->image;
					$canvas = new Imagick();
					$images = $image->coalesceImages();
					foreach ($images as $frame) {
						$img = new Imagick();
						$img->readImageBlob($frame);
						$img->thumbnailImage($width, $height, false);
						$canvas->addImage($img);
						$canvas->setImageDelay($img->getImageDelay());
					}
					$image->destroy();
					$this->image = $canvas;
				} else {
					$this->image->thumbnailImage($width, $height, false);
				}
				break;
			case 'scale':
				if ($this->type=='gif') {
					$image = $this->image;
					$images = $image->coalesceImages();
					$canvas = new Imagick();
					foreach ($images as $frame) {
						$img = new Imagick();
						$img->readImageBlob($frame);
						$img->thumbnailImage($width, $height, true);
						$canvas->addImage($img);
						$canvas->setImageDelay($img->getImageDelay());
					}
					$image->destroy();
					$this->image = $canvas;
				} else {
					$this->image->thumbnailImage($width, $height, true);
				}
				break;
			case 'scale_fill':
				$size = $this->image->getImagePage();
				$src_width = $size['width'];
				$src_height = $size['height'];
				$x = 0;
				$y = 0;
				$dst_width = $width;
				$dst_height = $height;
				if ($src_width*$height > $src_height*$width) {
					$dst_height = intval($width*$src_height/$src_width);
					$y = intval(($height-$dst_height)/2);
				} else {
					$dst_width = intval($height*$src_width/$src_height);
					$x = intval(($width-$dst_width)/2);
				}
				$image = $this->image;
				$canvas = new Imagick();
				$color = 'rgba('.$fill_color[0].','.$fill_color[1].','.$fill_color[2].','.$fill_color[3].')';
				if ($this->type=='gif') {
					$images = $image->coalesceImages();
					foreach ($images as $frame) {
						$frame->thumbnailImage($width, $height, true);
						$draw = new ImagickDraw();
						$draw->composite($frame->getImageCompose(), $x, $y, $dst_width, $dst_height, $frame);
						$img = new Imagick();
						$img->newImage($width, $height, $color, 'gif');
						$img->drawImage($draw);
						$canvas->addImage($img);
						$canvas->setImageDelay($img->getImageDelay());
						$canvas->setImagePage($width, $height, 0, 0);
					}
				} else {
					$image->thumbnailImage($width, $height, true);
					$draw = new ImagickDraw();
					$draw->composite($image->getImageCompose(), $x, $y, $dst_width, $dst_height, $image);
					$canvas->newImage($width, $height, $color, $this->getType());
					$canvas->drawImage($draw);
					$canvas->setImagePage($width, $height, 0, 0);
				}
				$image->destroy();
				$this->image = $canvas;
				break;
			default:
				$size = $this->image->getImagePage();
				$src_width = $size['width'];
				$src_height = $size['height'];
				$crop_x = 0;
				$crop_y = 0;
				$crop_w = $src_width;
				$crop_h = $src_height;
				if ($src_width*$height > $src_height*$width) {
					$crop_w = intval($src_height*$width/$height);
				} else {
					$crop_h = intval($src_width*$height/$width);
				}
				switch ($fit) {
					case 'north_west':
						$crop_x = 0;
						$crop_y = 0;
						break;
					case 'north':
						$crop_x = intval(($src_width-$crop_w)/2);
						$crop_y = 0;
						break;
					case 'north_east':
						$crop_x = $src_width-$crop_w;
						$crop_y = 0;
						break;
					case 'west':
						$crop_x = 0;
						$crop_y = intval(($src_height-$crop_h)/2);
						break;
					case 'center':
						$crop_x = intval(($src_width-$crop_w)/2);
						$crop_y = intval(($src_height-$crop_h)/2);
						break;
					case 'east':
						$crop_x = $src_width-$crop_w;
						$crop_y = intval(($src_height-$crop_h)/2);
						break;
					case 'south_west':
						$crop_x = 0;
						$crop_y = $src_height-$crop_h;
						break;
					case 'south':
						$crop_x = intval(($src_width-$crop_w)/2);
						$crop_y = $src_height-$crop_h;
						break;
					case 'south_east':
						$crop_x = $src_width-$crop_w;
						$crop_y = $src_height-$crop_h;
						break;
					default:
						$crop_x = intval(($src_width-$crop_w)/2);
						$crop_y = intval(($src_height-$crop_h)/2);
				}
				$image = $this->image;
				$canvas = new Imagick();
				if ($this->type=='gif') {
					$images = $image->coalesceImages();
					foreach ($images as $frame) {
						$img = new Imagick();
						$img->readImageBlob($frame);
						$img->cropImage($crop_w, $crop_h, $crop_x, $crop_y);
						$img->thumbnailImage($width, $height, true);
						$canvas->addImage($img);
						$canvas->setImageDelay($img->getImageDelay());
						$canvas->setImagePage($width, $height, 0, 0);
					}
				} else {
					$image->cropImage($crop_w, $crop_h, $crop_x, $crop_y);
					$image->thumbnailImage($width, $height, true);
					$canvas->addImage($image);
					$canvas->setImagePage($width, $height, 0, 0);
				}
				$image->destroy();
				$this->image = $canvas;
				break;
		}
		return $this;
	}
	
	//添加水印
	public function watermark($path, $x=0, $y=0) {
		$watermark = new Imagick($path);
		$draw = new ImagickDraw();
		$draw->composite($watermark->getImageCompose(), $x, $y, $watermark->getImageWidth(), $watermark->getimageheight(), $watermark);
		if ($this->type=='gif') {
			$image = $this->image;
			$canvas = new Imagick();
			$images = $image->coalesceImages();
			foreach ($image as $frame) {
				$img = new Imagick();
				$img->readImageBlob($frame);
				$img->drawImage($draw);
				$canvas->addImage($img);
				$canvas->setImageDelay($img->getImageDelay());
			}
			$image->destroy();
			$this->image = $canvas;
		} else {
			$this->image->drawImage($draw);
		}
		return $this;
	}
	
	//添加文字
	public function text($text, $x=0 , $y=0, $angle=0, $style=array()) {
		$draw = new ImagickDraw();
		if (isset($style['font'])) $draw->setFont($style['font']);
		if (isset($style['font_size'])) $draw->setFontSize($style['font_size']);
		if (isset($style['fill_color'])) $draw->setFillColor($style['fill_color']);
		if (isset($style['under_color'])) $draw->setTextUnderColor($style['under_color']);
		if ($this->type=='gif') {
			foreach ($this->image as $frame) {
				$frame->annotateImage($draw, $x, $y, $angle, $text);
			}
		} else {
			$this->image->annotateImage($draw, $x, $y, $angle, $text);
		}
		return $this;
	}
	
	//保存到指定路径
	public function save($path) {
		if ($this->type=='gif') {
			$this->image->writeImages($path, true);
		} else {
			$this->image->writeImage($path);
		}
		return $this;
	}
	
	//输出图像
	public function output($header=true) {
		if ($header) header('Content-type: '.$this->type);
		echo $this->image->getImagesBlob();
	}
	
	//图像宽度
	public function width() {
		$size = $this->image->getImagePage();
		return $size['width'];
	}
	
	//图像高度
	public function height() {
		$size = $this->image->getImagePage();
		return $size['height'];
	}
	
	//设置图像类型
	public function setType($type='png') {
		$this->type = $type;
		$this->image->setImageFormat($type);
		return $this;
	}
	
	//获取源图像类型
	public function getType() {
		return $this->type;
	}
	
	//当前对象是否为图片
	public function isImage() {
		if ($this->image) return true;
		else return false;
	}
	
	//生成缩略图, $fit为真时将保持比例并在安全框 $width * $height 内生成缩略图片
	public function thumbnail($width=100, $height=100, $fit=true) {
		if ($width<=0 || $height<=0) {
			if ($width<=0) $width = 0;
			if ($height<=0) $height = 0;
			$size = $this->image->getImagePage();
			$w = $size['width'];
			$h = $size['height'];
			if ($width==0) {
				$width = $w*$height/$h;
			} else if ($height==0) {
				$height = $h*$width/$w;
			}
		}
		$this->image->thumbnailImage($width, $height, $fit);
		return $this;
	}
	
	//边框, $width:左右边框宽度, $height:上下边框宽度, $color: 颜色:RGB颜色 'rgb(255,0,0)' 或 16进制颜色 '#FF0000' 或颜色单词 'white'/'red'...
	public function border($width, $height, $color='rgb(220, 220, 220)') {
		$c = new ImagickPixel();
		$c->setColor($color);
		$this->image->borderImage($c, $width, $height);
		return $this;
	}
	
	//圆角
	public function radius($x_rounding, $y_rounding) {
		$this->image->roundCorners($x_rounding, $y_rounding);
		return $this;
	}
	
	//圆角边框
	public function radiusBorder($width, $height, $color='rgb(220, 220, 220)', $x_rounding=0, $y_rounding=0) {
		$image = $this->image;
		$border = new Imagick(); //边框
		$border->newImage($image->getImageWidth()+$width*2, $image->getImageHeight()+$height*2, $color, $this->getType());
		$border->roundCorners($x_rounding, $y_rounding);
		$border->compositeImage($image, Imagick::COMPOSITE_OVER, $width, $height);
		$image->destroy();
		$this->image = $border;
		return $this;
	}
	
	//模糊
	public function blur($radius, $sigma) {
		$this->image->blurImage($radius, $sigma);
		return $this;
	}
	//高斯模糊
	public function gaussian_blur($radius, $sigma) {
		$this->image->gaussianBlurImage($radius, $sigma);
		return $this;
	}
	//运动模糊
	public function motion_blur($radius, $sigma, $angle) {
		$this->image->motionBlurImage($radius, $sigma, $angle);
		return $this;
	}
	//径向模糊
	public function radial_blur($radius) {
		$this->image->radialBlurImage($radius);
		return $this;
	}
	
	//添加噪点
	public function noise($type=null) {
		$this->image->addNoiseImage($type==null?imagick::NOISE_IMPULSE:$type);
		return $this;
	}
	
	//调整色阶
	public function level($black_point, $gamma, $white_point) {
		$this->image->levelImage($black_point, $gamma, $white_point);
		return $this;
	}
	//调整亮度、饱和度、色调
	public function modulate($brightness, $saturation, $hue) {
		$this->image->modulateImage($brightness, $saturation, $hue);
		return $this;
	}
	
	//素描
	public function charcoal($radius, $sigma) {
		$this->image->charcoalImage($radius, $sigma);
		return $this;
	}
	//油画效果
	public function oilPaint($radius) {
		$this->image->oilPaintImage($radius);
		return $this;
	}
	
	//水平翻转
	public function flop() {
		$this->image->flopImage();
		return $this;
	}
	//垂直翻转
	public function flip() {
		$this->image->flipImage();
		return $this;
	}
}
