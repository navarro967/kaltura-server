<?php

class myKuserUtils
{
	const SPECIAL_CHARS = array('+', '=', '-', '@', ',');
	const NON_EXISTING_USER_ID = -1;
	const USERS_DELIMITER = ',';

	public static function preparePusersToKusersFilter( $puserIdsCsv )
	{
		$kuserIdsArr = array();
		$puserIdsArr = explode(self::USERS_DELIMITER, $puserIdsCsv);
		$kuserArr = kuserPeer::getKuserByPartnerAndUids(kCurrentContext::getCurrentPartnerId(), $puserIdsArr);

		foreach($kuserArr as $kuser)
		{
			$kuserIdsArr[] = $kuser->getId();
		}

		if(!empty($kuserIdsArr))
		{
			return implode(self::USERS_DELIMITER, $kuserIdsArr);
		}

		return self::NON_EXISTING_USER_ID; // no result will be returned if no puser exists
	}
	
	public static function startsWithSpecialChar($str)
	{
		return $str && in_array($str[0], self::SPECIAL_CHARS);
	}
}