<?php
/**
 * @package plugins.venodr
 * @subpackage model.zoom
 */

class kZoomEngine
{
	const ADMIN_TAG_ZOOM = 'zoomentry';
	const PHP_INPUT = 'php://input';
	const URL_ACCESS_TOKEN = '?access_token=';
	const REFERENCE_FILTER = '_eq_reference_id';
	const ZOOM_PREFIX = 'Zoom_';

	protected static $FILE_VIDEO_TYPES = array('MP4');
	protected static $FILE_CAPTION_TYPES = array('TRANSCRIPT');
	protected $zoomConfiguration;
	protected $zoomClient;

	/**
	 * kZoomEngine constructor.
	 * @param $zoomConfiguration
	 */
	public function __construct($zoomConfiguration)
	{
		$this->zoomConfiguration = $zoomConfiguration;
		$this->zoomClient = new kZoomClient($zoomConfiguration[kZoomClient::ZOOM_BASE_URL]);
	}

	/**
	 * @return kZoomEvent
	 */
	public function parseEvent()
	{
		kZoomOauth::verifyHeaderToken($this->zoomConfiguration);
		$data = $this->getRequestData();
		KalturaLog::debug('Zoom event data is ' . print_r($data, true));
		$event = new kZoomEvent();
		$event->parseData($data);
		return $event;
	}

	/**
	 * @param kZoomEvent $event
	 */
	public function processEvent($event)
	{
		switch($event->eventType)
		{
			case kEventType::RECORDING_VIDEO_COMPLETED:
				$this->handleRecordingVideoComplete($event);
				break;
			case kEventType::RECORDING_TRANSCRIPT_COMPLETED:
				$this->handleRecordingTranscriptComplete($event);
				break;
		}
	}

	/**
	 * @return mixed
	 * @throws Exception
	 */
	protected function getRequestData()
	{
		$request_body = file_get_contents(self::PHP_INPUT);
		$data = json_decode($request_body, true);
		return $data;
	}

	/**
	 * @param kZoomEvent $event
	 */
	protected function handleRecordingTranscriptComplete($event)
	{
		/* @var kZoomTranscriptCompleted $transcript */
		$transcript = $event->object;
		$entry = $this->getZoomEntryByReferenceId($transcript->id);
		$captionAssetService = new CaptionAssetService();
		$captionAssetService->initService('caption_captionasset', 'captionasset', 'setContent');
		foreach ($transcript->recordingFiles as $recordingFile)
		{
			/* @var kZoomRecordingFile $recordingFile */

			if (!in_array ($recordingFile->fileType, self::$FILE_CAPTION_TYPES))
			{
				continue;
			}

			$captionAsset = $this->createAssetForTranscription($entry);
			$captionAssetResource = new KalturaUrlResource();
			$captionAssetResource->url = $recordingFile->download_url . self::URL_ACCESS_TOKEN . $event->downloadToken;
			$captionAssetService->setContentAction($captionAsset->getId(), $captionAssetResource);
		}
	}

	protected function getZoomEntryByReferenceId($meetingId)
	{
		$entryFilter = new entryFilter();
		$pager = new KalturaFilterPager();
		$entryFilter->setPartnerSearchScope(baseObjectFilter::MATCH_KALTURA_NETWORK_AND_PRIVATE);
		$entryFilter->set(self::REFERENCE_FILTER, self::ZOOM_PREFIX . $meetingId);
		$c = KalturaCriteria::create(entryPeer::OM_CLASS);
		$pager->attachToCriteria($c);
		$entryFilter->attachToCriteria($c);
		$c->add(entryPeer::DISPLAY_IN_SEARCH, mySearchUtils::DISPLAY_IN_SEARCH_SYSTEM, Criteria::NOT_EQUAL);
		if (kEntitlementUtils::getEntitlementEnforcement() && !kCurrentContext::$is_admin_session && entryPeer::getUserContentOnly())
		{
			entryPeer::setFilterResults(true);
		}

		return entryPeer::doSelectOne($c);
	}

	/**
	 * @param kZoomEvent $event
	 */
	protected function handleRecordingVideoComplete($event)
	{
		$zoomIntegration = ZoomHelper::getZoomIntegration();
		/* @var kZoomMeeting $meeting */
		$meeting = $event->object;
		$dbUser = $this->getEntryOwner($meeting->hostEmail, $zoomIntegration);
		$this->initUserPermissions($dbUser);
		$participantsUsersNames = $this->extractMeetingParticipants($meeting->id, $zoomIntegration);
		$validatedUsers = $this->getValidatedUsers($participantsUsersNames, $zoomIntegration->getPartnerId(), $zoomIntegration->getCreateUserIfNotExist());
		foreach ($meeting->recordingFiles as $recordingFile)
		{
			/* @var kZoomRecordingFile $recordingFile */

			if (!in_array ($recordingFile->fileType, self::$FILE_VIDEO_TYPES))
			{
				continue;
			}

			$entry = $this->createEntryFromMeeting($meeting, $dbUser);
			$this->setEntryCategory($zoomIntegration, $entry);
			$this->handleParticipants($entry, $validatedUsers, $zoomIntegration);
			$entry->save();
			$url = $recordingFile->download_url . self::URL_ACCESS_TOKEN . $event->downloadToken;
			kJobsManager::addImportJob(null, $entry->getId(), $entry->getPartnerId(), $url);
		}

	}

	/**
	 * @param entry $entry
	 * @param array $validatedUsers
	 * @param ZoomVendorIntegration $zoomIntegration
	 */
	protected function handleParticipants($entry, $validatedUsers, $zoomIntegration)
	{
		$handleParticipantMode = $zoomIntegration->getHandleParticipantsMode();
		if ($validatedUsers && $handleParticipantMode != kHandleParticipantsMode::IGNORE)
		{
			switch ($handleParticipantMode)
			{
				case kHandleParticipantsMode::ADD_AS_CO_PUBLISHERS:
					$entry->setEntitledPusersPublish(implode(",", array_unique($validatedUsers)));
					break;
				case kHandleParticipantsMode::ADD_AS_CO_VIEWERS:
					$entry->setEntitledPusersView(implode(",", array_unique($validatedUsers)));
					break;
			}
		}
	}

	/**
	 * @param $entryId
	 * @param $categoryId
	 * @param $partnerId
	 * @throws PropelException
	 */
	protected function createCategoryEntry($entryId, $categoryId, $partnerId)
	{
		$categoryEntry = new categoryEntry();
		$categoryEntry->setEntryId($entryId);
		$categoryEntry->setCategoryId($categoryId);
		$categoryEntry->setPartnerId($partnerId);
		$categoryEntry->setStatus(CategoryEntryStatus::ACTIVE);
		$categoryEntry->save();
	}

	protected function getValidatedUsers($usersNames, $partnerId, $createIfNotFound)
	{
		$validatedUsers=array();
		if(!$usersNames)
		{
			return $usersNames;
		}

		foreach ($usersNames as $userName)
		{
			if(kuserPeer::getKuserByPartnerAndUid($partnerId, $userName, true))
			{
				$validatedUsers[] = $userName;
			}
			elseif($createIfNotFound)
			{
				kuserPeer::createKuserForPartner($partnerId, $userName);
				$validatedUsers[] = $userName;
			}
		}

		return $validatedUsers;
	}

	/**
	 * @param kZoomMeeting $meeting
	 * @return string
	 */
	protected function createEntryDescriptionFromMeeting($meeting)
	{
		return "Zoom Recording ID: {$meeting->id}\nMeeting Time: {$meeting->startTime}";
	}

	/**
	 * @param ZoomVendorIntegration $zoomIntegration
	 * @param entry $entry
	 */
	protected function setEntryCategory($zoomIntegration, $entry)
	{
		if ($zoomIntegration->getZoomCategory())
		{
			$entry->setCategories($zoomIntegration->getZoomCategory());
		}
	}

	/**
	 * @param kZoomMeeting $meeting
	 * @param kuser $owner
	 * @return entry
	 */
	protected function createEntryFromMeeting($meeting, $owner)
	{
		$entry = new entry();
		$entry->setType(entryType::MEDIA_CLIP);
		$entry->setSourceType(EntrySourceType::URL);
		$entry->setMediaType(entry::ENTRY_MEDIA_TYPE_VIDEO);
		$entry->setDescription($this->createEntryDescriptionFromMeeting($meeting));
		$entry->setName($meeting->topic);
		$entry->setPartnerId($owner->getPartnerId());
		$entry->setStatus(entryStatus::NO_CONTENT);
		$entry->setPuserId($owner->getPuserId());
		$entry->setKuserId($owner->getKuserId());
		$entry->setConversionProfileId(myPartnerUtils::getConversionProfile2ForPartner($owner->getPartnerId())->getId());
		$entry->setAdminTags(self::ADMIN_TAG_ZOOM);
		return $entry;
	}

	/**
	 * @param entry $entry
	 * @return CaptionAsset
	 */
	public function createAssetForTranscription($entry)
	{
		$caption = new CaptionAsset();
		$caption->setEntryId($entry->getId());
		$caption->setPartnerId($entry->getPartnerId());
		$caption->setContainerFormat(CaptionType::WEBVTT);
		$caption->setStatus(CaptionAsset::ASSET_STATUS_QUEUED);
		$caption->save();
		return $caption;
	}

	/**
	 * @param $meetingId
	 * @param ZoomVendorIntegration $zoomIntegration
	 * @return array participants users names
	 */
	protected function extractMeetingParticipants($meetingId, $zoomIntegration)
	{
		if ($zoomIntegration->getHandleParticipantsMode() == kHandleParticipantsMode::IGNORE)
		{
			return null;
		}

		$accessToken = kZoomOauth::getValidAccessToken($zoomIntegration);
		$participantsData = $this->zoomClient->retrieveMeetingParticipant($accessToken, $meetingId);
		$participants = new kZoomParticipants();
		$participants->parseData($participantsData);
		$participantsEmails = $participants->getParticipantsEmails();
		if($participantsEmails)
		{
			$result = array();
			foreach ($participantsEmails as $participantEmail)
			{
				$result[] = $this->matchZoomUserName($participantEmail, $zoomIntegration);
			}
		}
		else
		{
			$result = null;
		}

		return $result;
	}

	/**
	* @param string $hostEmail
	* @param ZoomVendorIntegration $zoomIntegration
	* @return kuser
	*/
	public function getEntryOwner($hostEmail, $zoomIntegration)
	{
		$partnerId = $zoomIntegration->getPartnerId();
		$hostEmail = $this->matchZoomUserName($hostEmail, $zoomIntegration);
		$dbUser = kuserPeer::getKuserByPartnerAndUid($partnerId, $hostEmail, true);
		if (!$dbUser)
		{
			if ($zoomIntegration->getCreateUserIfNotExist())
			{
				$dbUser = kuserPeer::createKuserForPartner($partnerId, $hostEmail);
			}
			else
			{
				$dbUser = kuserPeer::getKuserByPartnerAndUid($partnerId, $zoomIntegration->getDefaultUserEMail(), true);
			}
		}

		return $dbUser;
	}


	/**
	 * @param string $userName
	 * @param ZoomVendorIntegration $zoomIntegration
	 * @return string kalturaUserName
	 */
	public function matchZoomUserName($userName, $zoomIntegration)
	{
		$result = $userName;
		switch ($zoomIntegration->getUserMatching())
		{
			case kZoomUsersMatching::DO_NOT_MODIFY:
				break;
			case kZoomUsersMatching::ADD_POSTFIX:
				$postFix = $zoomIntegration->getUserPostfix();
				if (!kString::endsWith($result, $postFix))
				{
					$result = $result . $postFix;
				}

				break;
			case kZoomUsersMatching::REMOVE_POSTFIX:
				$postFix = $zoomIntegration->getUserPostfix();
				if (kString::endsWith($result, $postFix))
				{
					$result = substr($result, 0, strlen($result) - strlen($postFix));
				}

				break;
		}

		return $result;
	}

	/**
	 * user logged in - need to re-init kPermissionManager in order to determine current user's permissions
	 * @param kuser $dbUser
	 */
	protected function initUserPermissions($dbUser)
	{
		$ks = null;
		kSessionUtils::createKSessionNoValidations($dbUser->getPartnerId(), $dbUser->getPuserId() , $ks, 86400 , false , "" , '*' );
		kCurrentContext::initKsPartnerUser($ks);
		kPermissionManager::init();
	}
}