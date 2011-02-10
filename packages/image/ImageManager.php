<?php

// namespace image;

/**
 * This class has utility methods for image manipulation.
 *
 * @package image
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
class ImageManager {
	/**
	 * @var string
	 */
	protected $imagePath;

	/**
	 * Create an instance of this class.
	 *
	 * @param string $imagePath Image file path.
	 * @throws FileNotFoundException if $imagePath is invalid.
	 */
	public function __construct($imagePath)
	{
		if (!file_exists($imagePath)) {
			import('io.FileNotFoundException');
			throw new FileNotFoundException("\"$imagePath\" is not a valid font source file.");
		}

		$this->imagePath = rtrim($imagePath, "\\/");
	}

	/**
	 * Resize image keeping aspect ratio. Does not upscale image for best fit.
	 * This method fixes native Imagick#thumbnailImage because it sucks.
	 *
	 * @param int $width
	 * @param int $height
	 * @param string $targetFile Target file name. If null the source image will be replaced.
	 * @return void
	 */
	public function resize($width, $height, $targetFile = null)
	{
		if (!class_exists('Imagick')) {
			throw new RuntimeException("Imagick class not found!");
		}

		$imagick = new Imagick($this->imagePath);
		$ratio = $imagick->getImageWidth() / $imagick->getImageHeight();
		$w = $ratio >= 1 && $width < $imagick->getImageWidth() ? $width : 0;
		$h = $ratio < 1 && $height < $imagick->getImageHeight() ? $height : 0;
		if ($w || $h) $imagick->thumbnailImage($w, $h, false);
		$imagick->writeImage($targetFile ? $targetFile : $this->imagePath);
	}
}
?>
