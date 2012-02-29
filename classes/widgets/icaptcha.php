<?php
AutoLoad::path(dirname(__FILE__) . '/iinput.php');

Utils::define('CAPTCHA_DIFFICULTY', 20);
Utils::define('CAPTCHA_TTL', 120);

class iCaptcha extends iInput {
	public function __construct() {
		parent::__construct();

		if (isset($_REQUEST['captcha']))
			$this->show_captcha();
	}

	public function check() {
		$captcha_id = $_COOKIE['captcha_id'];
		$file = XEN_TMP_DIR.'/'.$captcha_id.'.tmp';

		if (file_exists($file)) {
			if ((filectime($file) + CAPTCHA_TTL) < time()) 
				unlink($file);
			elseif  (file_get_contents($file) == strtolower($this->_value))
				return true;
		}
		return $this->error('Wrong captcha');
	}

	protected function show_captcha() {
		$Imagick = new Imagick();
		$bg = new ImagickPixel();

		/* Set the pixel color to white */
		$bg->setColor('white');

		/* Create a drawing object and set the font size */
		$ImagickDraw = new ImagickDraw();

		/* Set font and font size. You can also specify /path/to/font.ttf */
		//$ImagickDraw->setFont('Helvetica Regular');
		$ImagickDraw->setFont(dirname(__FILE__).'/icaptcha.ttf');
		$ImagickDraw->setFontSize(20);

		/* Create the text */
		$alphanum = 'ABXZRMHTL23456789';
		$string = substr(str_shuffle($alphanum), 2, 6);

		$captcha_id = random_string(10);
		file_put_contents(XEN_TMP_DIR.'/'.$captcha_id.'.tmp', strtolower($string));
		
		setcookie('captcha_id', $captcha_id, 0, '/');

		/* Create new empty image */
		$Imagick->newImage(90, 30, $bg); 

		/* Write the text on the image */
		$Imagick->annotateImage($ImagickDraw, 4, 20, 0, $string);

		/* Add some swirl */
		$Imagick->swirlImage(CAPTCHA_DIFFICULTY);

		/* Create a few random lines */
		$ImagickDraw->line(rand(0, 80), rand(0, 30), rand(0, 70), rand(0, 30));
		$ImagickDraw->line(rand(0, 80), rand(0, 30), rand(0, 70), rand(0, 30));
		$ImagickDraw->line(rand(0, 80), rand(0, 30), rand(0, 70), rand(0, 30));
		$ImagickDraw->line(rand(0, 80), rand(0, 30), rand(0, 70), rand(0, 30));
		$ImagickDraw->line(rand(0, 80), rand(0, 30), rand(0, 70), rand(0, 30));

		/* Draw the ImagickDraw object contents to the image. */
		$Imagick->drawImage($ImagickDraw);

		/* Give the image a format */
		$Imagick->setImageFormat('png');
		
		/* Send headers and output the image */
		header('Content-Type: image/'.$Imagick->getImageFormat());
		echo $Imagick->getImageBlob();

		exit;
	}
}
?>
