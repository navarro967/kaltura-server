<?php
class kChtHttpUrlTokenizer extends kUrlTokenizer
{
	
	/**
	 * @param string $url
	 * @param string $urlPrefix
	 * @return string
	 */
	public function tokenizeSingleUrl($url, $urlPrefix = null)
	{
		return $this->tokenizeUrl($url);
	}
	
	/**
	 * @param string $url
	 * @param string $baseUrl
	 * @param string $fileExtension
	 * @return string
	 */
	public function tokenizeUrl($url, $baseUrl = null, $fileExtension = null)
	{
		$expiryTime = time() + $this->window;

		$hashData = $url . $this->key . $expiryTime	;
		$token = base64_encode(md5($hashData, true));
		$token = strtr($token, '+/', '-_');
		$token = str_replace('=', '', $token);
		
		
		if (strpos($url, '?') !== false)
			$s = '&';
		else
			$s = '?';
		
		return $url.$s.'token='.$token.'&expires='.$expiryTime;
	}
}