<?php
/**
 * Convert text read from Subversion repositories to the UTF-8 used by the UI.
 */
class USVN_TextEncoding
{
	const DEFAULT_ENCODINGS = 'UTF-8,GBK,GB2312,Big5';

	/**
	 * Return the configured encoding candidates in their detection order.
	 *
	 * @return array
	 */
	public static function getEncodings()
	{
		$encodings = self::DEFAULT_ENCODINGS;
		try {
			if (class_exists('Zend_Registry')) {
				$config = Zend_Registry::get('config');
				if (isset($config->subversion->text_encodings)) {
					$encodings = (string)$config->subversion->text_encodings;
				}
			}
		} catch (Exception $e) {
			// Keep defaults when called before the application config is loaded.
		}

		$result = array();
		foreach (explode(',', $encodings) as $encoding) {
			$encoding = trim($encoding);
			if ($encoding !== '' && !in_array(strtolower($encoding), array_map('strtolower', $result), true)) {
				$result[] = $encoding;
			}
		}
		if (!in_array('utf-8', array_map('strtolower', $result), true)) {
			array_unshift($result, 'UTF-8');
		}
		return $result;
	}

	/**
	 * Decode text using the requested encoding or detect one from the configured list.
	 *
	 * @param string $text Raw Subversion output
	 * @param string|null $requestedEncoding Explicit encoding selected by the user
	 * @return array Decoded text and the encoding used
	 */
	public static function decode($text, $requestedEncoding = null)
	{
		$encodings = self::getEncodings();
		if ($requestedEncoding !== null && in_array($requestedEncoding, $encodings, true)) {
			$decoded = self::convert($text, $requestedEncoding);
			return array('text' => ($decoded === false ? $text : $decoded), 'encoding' => $requestedEncoding);
		}

		foreach ($encodings as $encoding) {
			$decoded = self::convert($text, $encoding);
			if ($decoded === false) {
				continue;
			}
			if (strcasecmp($encoding, 'UTF-8') === 0 || self::isRoundTrip($text, $decoded, $encoding)) {
				return array('text' => $decoded, 'encoding' => $encoding);
			}
		}

		return array('text' => $text, 'encoding' => 'UTF-8');
	}

	private static function convert($text, $encoding)
	{
		if (strcasecmp($encoding, 'UTF-8') === 0) {
			return (preg_match('//u', $text) === 1 ? $text : false);
		}
		if (function_exists('iconv')) {
			return @iconv($encoding, 'UTF-8', $text);
		}
		if (function_exists('mb_convert_encoding')) {
			return @mb_convert_encoding($text, 'UTF-8', $encoding);
		}
		return false;
	}

	private static function isRoundTrip($original, $decoded, $encoding)
	{
		if (!function_exists('iconv')) {
			return true;
		}
		$encoded = @iconv('UTF-8', $encoding, $decoded);
		return $encoded !== false && $encoded === $original;
	}
}
