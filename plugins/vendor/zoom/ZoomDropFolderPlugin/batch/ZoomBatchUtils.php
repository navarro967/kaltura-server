<?php

class ZoomBatchUtils
{
	const HOST_ID = 'host_id';
	const EMAIL = 'email';
	const CMS_USER_FIELD = 'cms_user_id';
	
	public static function shouldExcludeUserRecordingIngest (string $userId, $groupParticipationType, $optInGroupNames, $optOutGroupNames)
	{
		if ($groupParticipationType == KalturaZoomGroupParticipationType::NO_CLASSIFICATION)
		{
			return false;
		}
		if ($groupParticipationType == KalturaZoomGroupParticipationType::OPT_IN)
		{
			$vendorGroupsNamesArray = explode("\r\n", $optInGroupNames);
		}
		else
		{
			$vendorGroupsNamesArray = explode("\r\n", $optOutGroupNames);
		}
		
		$userGroupsArray = self::getUserGroupNames($userId);
		if (!empty(array_intersect($userGroupsArray, $vendorGroupsNamesArray)))
		{
			if ($groupParticipationType == 2)
			{
				return true;
			}
		}
		return false;
	}
	
	protected static function getUserGroupNames($userId)
	{
		$userPager = new KalturaFilterPager();
		$userPager->pageSize = 1;
		$userPager->pageIndex = 1;
		
		$userFilter = new KalturaGroupUserFilter();
		$userFilter->userIdEqual = $userId;
		
		$userGroupsResponse = KBatchBase::$kClient->userGroup->listAction($userFilter, $userPager);
		$userGroupsArray = $userGroupsResponse->objects;
		
		$userGroupNames = array();
		foreach ($userGroupsArray as $userGroup)
		{
			array_push($userGroupNames, $userGroup->groupId);
		}
		return $userGroupNames;
	}
	
	public static function getUserId ($zoomClient, $partnerId, $meetingFile, $zoomVendorIntegration)
	{
		$hostId = $meetingFile[self::HOST_ID];
		$zoomUser = $zoomClient->retrieveZoomUser($hostId);
		if (!$zoomUser)
		{
			KalturaLog::err('Zoom User not found');
			return null;
		}
		$hostEmail = '';
		if(isset($zoomUser[self::EMAIL]) && !empty($zoomUser[self::EMAIL]))
		{
			$hostEmail = $zoomUser[self::EMAIL];
		}
		return self::getEntryOwnerId($hostEmail, $partnerId, $zoomVendorIntegration, $zoomClient);
	}
	
	public static function getEntryOwnerId($hostEmail, $partnerId, $zoomVendorIntegration, $zoomClient)
	{
		$zoomUser = new kZoomUser();
		$zoomUser->setOriginalName($hostEmail);
		$zoomUser->setProcessedName(self::processZoomUserName($hostEmail, $zoomVendorIntegration, $zoomClient));
		KBatchBase::impersonate($partnerId);
		/* @var $user KalturaUser */
		$user = self::getKalturaUser($partnerId, $zoomUser);
		KBatchBase::unimpersonate();
		$userId = '';
		if ($user)
		{
			$userId = $user->id;
		}
		else
		{
			if ($zoomVendorIntegration->createUserIfNotExist)
			{
				$userId = $zoomUser->getProcessedName();
			}
			else if ($zoomVendorIntegration->defaultUserId)
			{
				$userId = $zoomVendorIntegration->defaultUserId;
			}
		}
		return $userId;
	}
	
	public static function getKalturaUser($partnerId, $kZoomUser)
	{
		$pager = new KalturaFilterPager();
		$pager->pageSize = 1;
		$pager->pageIndex = 1;
		
		$filter = new KalturaUserFilter();
		$filter->partnerIdEqual = $partnerId;
		$filter->idEqual = $kZoomUser->getProcessedName();
		$kalturaUser = KBatchBase::$kClient->user->listAction($filter, $pager);
		if (!$kalturaUser->objects)
		{
			$email = $kZoomUser->getOriginalName();
			$filterUser = new KalturaUserFilter();
			$filterUser->partnerIdEqual = $partnerId;
			$filterUser->emailStartsWith = $email;
			$kalturaUser = KBatchBase::$kClient->user->listAction($filterUser, $pager);
			if (!$kalturaUser->objects || strcasecmp($kalturaUser->objects[0]->email, $email) != 0)
			{
				return null;
			}
		}
		
		if($kalturaUser->objects)
		{
			return $kalturaUser->objects[0];
		}
		return null;
	}
	
	public static function processZoomUserName($userName, $zoomVendorIntegration, $zoomClient)
	{
		$result = $userName;
		switch ($zoomVendorIntegration->zoomUserMatchingMode)
		{
			case kZoomUsersMatching::ADD_POSTFIX:
				$postFix = $zoomVendorIntegration->zoomUserPostfix;
				if (!kString::endsWith($result, $postFix, false))
				{
					$result = $result . $postFix;
				}
				
				break;
			case kZoomUsersMatching::REMOVE_POSTFIX:
				$postFix = $zoomVendorIntegration->zoomUserPostfix;
				if (kString::endsWith($result, $postFix, false))
				{
					$result = substr($result, 0, strlen($result) - strlen($postFix));
				}
				
				break;
			case kZoomUsersMatching::CMS_MATCHING:
				$zoomUser = $zoomClient->retrieveZoomUser($userName);
				if(isset($zoomUser[self::CMS_USER_FIELD]) && !empty($zoomUser[self::CMS_USER_FIELD]))
				{
					$result = $zoomUser[self::CMS_USER_FIELD];
				}
				break;
			case kZoomUsersMatching::DO_NOT_MODIFY:
			default:
				break;
		}
		
		return $result;
	}
}