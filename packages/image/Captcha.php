<?php

// namespace image;

/**
 * This class creates a captcha image (a random generated code) using the GD extension.
 * 
 * Example usage:
 * <code>
 * // Create
 * $_SESSION['captcha'] = new Captcha();
 * $imageURL = $_SESSION['captcha']->create();
 * 
 * // Validate on form submit
 * if ($_SESSION['captcha']->isValid($_POST['captcha_code'])) {
 * 		// Valid code, delete image and proceed...
 * 		$_SESSION['captcha']->destroy();
 * }
 * </code>
 * 
 * @package image
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
class Captcha
{
	/**
	 * Directory path where the captcha image will be created.
	 *
	 * @var string
	 */
	protected $imagePath;
	/**
	 * Base url path of the image.
	 *
	 * @var string
	 */
	protected $imageBaseURL;
	/**
	 * Path to a .ttf font file.
	 *
	 * @var string
	 */
	protected $fontPath;
	/**
	 * The generated code.
	 *
	 * @var string
	 */
	protected $code = null;
	/**
	 * Name of the created image file.
	 *
	 * @var string
	 */
	protected $imageName;
	/**
	 * URL of the created image
	 *
	 * @var string
	 */
	protected $imageURL;
	
	/**
	 * Generate a random code.
	 *
	 * @param int $characters Number of characters
	 * @return void
	 */
	private function _generateCode($characters)
	{
		/* list all possible characters, similar looking characters and vowels have been removed */
		$possible = '23456789bcdfghjkmnpqrstvwxyz';
		$code = '';
		$i = 0;
		while ($i < $characters) {
			$code .= substr($possible, mt_rand(0, strlen($possible)-1), 1);
			$i++;
		}
		$this->code = $code;
	}
	
	/**
	 * Create an instance of this class.
	 *
	 * @param string $imagePath Directory path where the captcha image will be created.
	 * @param string $imageBaseURL Base url path of the image.
	 * @param string $fontPath Path to a .ttf font file. If not provided, a default file is used.
	 * @throws FileNotFoundException if $imagePath or $fontPath are invalid.
	 */
	public function __construct($imagePath = './images/captcha', $imageBaseURL = '/images/captcha', $fontPath = null)
	{
		if (!function_exists('imagecreate')) {
			throw new RuntimeException("GD extension not found.");
		}
		
		if (!is_dir($imagePath) || !is_writable($imagePath)) {
			import('io.FileNotFoundException');
			throw new FileNotFoundException("\"$imagePath\" is not a valid directory. It does not exist or is not writable.");
		}
		
		if (!$fontPath) {
			$fontPath = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'monofont.ttf';
		}
		
		if (!file_exists($fontPath)) {
			import('io.FileNotFoundException');
			throw new FileNotFoundException("\"$fontPath\" is not a valid font source file.");
		}
		
		$this->imagePath = rtrim($imagePath, "\\/");
		$this->imageBaseURL = rtrim($imageBaseURL, "\\/");
		$this->fontPath = rtrim($fontPath, "\\/");
	}
	
	/**
	 * Check if the code is valid
	 *
	 * @param string $code
	 * @return boolean
	 */
	public function isValid($code)
	{
		return $this->code == $code;
	}
	
	/**
	 * Delete the created image file.
	 *
	 * @return void
	 */
	public function destroy()
	{
		@unlink($this->imagePath . DIRECTORY_SEPARATOR . $this->imageName);
	}
	
	/**
	 * Create the image and return the URL.
	 *
	 * @param int $width The image width in pixels
	 * @param int $height The image height in pixels
	 * @param int $characters The numbe of characters
	 * @return string
	 */
	public function create($width = 80, $height = 30, $characters = 4)
	{
		$this->_generateCode($characters);
		
		/* font size will be 75% of the image height */
		$font_size = $height * 0.75;

		$image = imagecreate($width, $height);

		/* set the colours */
		$background_color = imagecolorallocate($image, 255, 255, 255);
		$text_color = imagecolorallocate($image, 20, 40, 100);
		$noise_color = imagecolorallocate($image, 200, 120, 180);

		/* generate random dots in background */
		for($i = 0; $i < ($width*$height)/3; $i++) {
			imagefilledellipse($image, mt_rand(0,$width), mt_rand(0,$height), 1, 1, $noise_color);
		}
		
		/* generate random lines in background */
		for($i = 0; $i < ($width*$height)/150; $i++) {
			imageline($image, mt_rand(0,$width), mt_rand(0,$height), mt_rand(0,$width), mt_rand(0,$height), $noise_color);
		}

		/* create textbox and add text */
		$textbox = imagettfbbox($font_size, 0, $this->fontPath, $this->code);
		$x = ($width - $textbox[4])/2;
		$y = ($height - $textbox[5])/2;
		imagettftext($image, $font_size, 0, $x, $y, $text_color, $this->fontPath , $this->code);
		
		// create image file
		$this->imageName = uniqid("captcha_") . '.jpg';
		imagejpeg($image, $this->imagePath . DIRECTORY_SEPARATOR . $this->imageName);
		imagedestroy($image);
		
		$this->imageURL = $this->imageBaseURL . DIRECTORY_SEPARATOR . $this->imageName;

		return $this->imageURL; 
	}
	
	/**
	 * Get the generated code.
	 *
	 * @return string
	 */
	public function getCode()
	{
		return $this->code;
	}
	
	/**
	 * Return the HTML source of the image.
	 *
	 * @return string
	 */
	public function getImage()
	{
		return '<img src="'.$this->imageURL.'" alt="" />';
	}
}
?>
