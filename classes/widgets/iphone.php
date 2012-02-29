<?php
AutoLoad::path(dirname(__FILE__).'/istring.php');

Utils::define('DEFAULT_COUNTRY_CODE', 7);

class iPhone extends iString {
	
	private $_countryCode = null;
	
	public function __construct() {
		parent::__construct();

		$this->title(__('phone_widget_title')); //Номер телефона
		$this->alert('[^0-9 +-]',
			__('phone_widget_alert_characters'), self::ALERT_MATCH); //Недопустимые символы в номере телефона, номер телефона должен состоять только из цифр
		$this->alert('(^7|^[+]7|^8|^)([^0-9]*[0-9][^0-9]*){10}$',
			__('phone_widget_alert_format')); //Неправильный формат номера телефона. Номер должен состоять из 10-ти цифр
	}

	/**
	* Какой бы ни был введён телефон, возвращаем его "чистую версию"
	*/
	public function get() {
		$phone = parent::get();

		if (!$phone)
			return '';

		return sprintf("%s", preg_replace('/(^\+7|^7|^8|[^0-9])/', '', $phone));
	}

	public function __toString() {
		return $this->get();
	}
	
	public function & set($value) {
		if (is_array($value)) {
			if (count($value) == 2) {
				list($this->_countryCode, $value) = $value;
			} else {
				$value = array_pop($value);
			}
		}
		return parent::set($value);
	}
	
	/**
	 * Return phone number with country code
	 * @return string
	 */
	public function getFullNumber() {
		if ($this->_countryCode === null)
			$this->_countryCode = (string) DEFAULT_COUNTRY_CODE;
		
		return $this->_countryCode . $this->get();
	}
}
?>
