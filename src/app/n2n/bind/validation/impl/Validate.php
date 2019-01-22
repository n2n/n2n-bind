<?php
namespace n2n\util;

use n2n\util\uri\Url;

class Validate {
	/**
	 * checks a string, if it is a valid e-mail address
	 *
	 * @param string $email
	 * @return bool
	 */
	public static function email(string $email) {
		return false !== filter_var($email, FILTER_VALIDATE_EMAIL);
	}
	
	/**
	 * checks a string, if it is a valid url address
	 *
	 * @param string $url
	 * @return bool
	 */
	public static function url(string $url, bool $schemeRequired = true) {
		try {
			$url = Url::create($url)->toIdnaAsciiString();
		} catch (\InvalidArgumentException $e) {
			return false;
		}
		
		if ($schemeRequired) {
			if (false !== filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED)) {
				return true;
			}
		} else {
			if (false !== filter_var($url, FILTER_VALIDATE_URL)) {
				return true;
			}
		}
		
		return false;
	}
}