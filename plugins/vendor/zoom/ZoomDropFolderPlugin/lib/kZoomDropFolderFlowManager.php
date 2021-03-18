<?php
/**
 * @package plugins.Vendor
 * @subpackage zoom.zoomDropFolderPlugin
 */
class kZoomDropFolderFlowManager implements kObjectChangedEventConsumer
{
	const MAX_ZOOM_DROP_FOLDERS = 4; //Temporary
	/**
	 * @inheritDoc
	 */
	public function objectChanged(BaseObject $object, array $modifiedColumns)
	{
		if ( self::wasStatusChanged($object, $modifiedColumns))
		{
			//Update the status of the Drop Folder
			$criteria = new Criteria();
			$criteria->add(DropFolderPeer::PARTNER_ID, $object->getPartnerId());
			$criteria->add(DropFolderPeer::TYPE, ZoomDropFolderPlugin::getCoreValue('DropFolderType',
			                                                                        ZoomDropFolderType::ZOOM));
			$allPartnerZoomDropFolders = DropFolderPeer::doSelect($criteria);
			$partnerZoomDropFoldersCount = count($allPartnerZoomDropFolders);
			$currentVendorId = $object->getId();
			$foundZoomDropFolder = false;
			foreach ($allPartnerZoomDropFolders as $partnerZoomDropFolder)
			{
				/* @var $partnerZoomDropFolder ZoomDropFolder */
				if ($partnerZoomDropFolder->getFromCustomData(ZoomDropFolder::ZOOM_VENDOR_INTEGRATION_ID) == $currentVendorId)
				{
					$foundZoomDropFolder = true;
					$partnerZoomDropFolder -> setStatus(self::getDropFolderStatus($object -> getStatus()));
					$partnerZoomDropFolder -> save();
					KalturaLog ::debug('ZoomDropFolder with vendorId ' . $currentVendorId . ' updated status to ' .
					                   $partnerZoomDropFolder->getStatus());
					break;
				}
			}
			if (!$foundZoomDropFolder && $partnerZoomDropFoldersCount < self::MAX_ZOOM_DROP_FOLDERS)
			{
				self::createNewZoomDropFolder($object);
			}
			else
			{
				if (!$foundZoomDropFolder)
				{
					throw new KalturaAPIException(KalturaZoomDropFolderErrors::EXCEEDED_MAX_ZOOM_DROP_FOLDERS);
				}
			}
		}
	}
	
	/**
	 * @inheritDoc
	 */
	public function shouldConsumeChangedEvent(BaseObject $object, array $modifiedColumns)
	{
		if ( self::wasStatusChanged($object, $modifiedColumns))
		{
			return true;
		}
		if ( self::hasRefreshTokenChanged($object, $modifiedColumns)){
			return true;
		}
		return false;
	}
	
	public static function wasStatusChanged(BaseObject $object, array $modifiedColumns)
	{
		if ( ($object instanceof ZoomVendorIntegration)
			&& in_array('vendor_integration.STATUS', $modifiedColumns) )
		{
			return true;
		}
		return false;
	}
	
	public static function hasRefreshTokenChanged(BaseObject $object, array $modifiedColumns)
	{
		if ( ($object instanceof ZoomVendorIntegration)
			&& in_array(entryPeer::CUSTOM_DATA, $modifiedColumns)
			&& $object->isColumnModified('refreshToken'))
		{
			return true;
		}
		return false;
	}
	
	private static function getDropFolderStatus($v)
	{
		switch ($v)
		{
			case 1:
			{
				return DropFolderStatus::DISABLED;
			}
			case 2:
			{
				return DropFolderStatus::ENABLED;
			}
			case 3:
			{
				return DropFolderStatus::DELETED;
			}
			default:
			{
				return DropFolderStatus::ERROR;
			}
		}
	}
	
	protected static function createNewZoomDropFolder($zoomVendorIntegrationObject)
	{
		KalturaLog::debug('Creating new ZoomDropFolder');
		// Create new Zoom Drop Folder
		$newZoomDropFolder = new ZoomDropFolder();
		$newZoomDropFolder->setZoomVendorIntegrationId($zoomVendorIntegrationObject->getId());
		$newZoomDropFolder->setPartnerId($zoomVendorIntegrationObject->getPartnerId());
		$newZoomDropFolder->setStatus(self::getDropFolderStatus($zoomVendorIntegrationObject -> getStatus()));
		$newZoomDropFolder->setType(ZoomDropFolderPlugin::getCoreValue('DropFolderType',
		                                                               ZoomDropFolderType::ZOOM));
		$newZoomDropFolder->setName('zoom_' . $zoomVendorIntegrationObject->getPartnerId() . '_' . $zoomVendorIntegrationObject->getAccountId());
		$newZoomDropFolder->setTags('zoom');
		$conversionProfileId = $zoomVendorIntegrationObject->getConversionProfileId();
		if (!$conversionProfileId)
		{
			$partner = PartnerPeer::retrieveByPK($newZoomDropFolder->getPartnerId());
			$conversionProfileId = $partner->getDefaultConversionProfileId();
		}
		$newZoomDropFolder->setConversionProfileId($conversionProfileId);
		$fileHandler = new DropFolderContentFileHandlerConfig();
		$fileHandler->setSlugRegex('/(?P<referenceId>.+)[.]\w{2,}/');
		$fileHandler->setHandlerType(DropFolderFileHandlerType::CONTENT);
		$fileHandler->setContentMatchPolicy(DropFolderContentFileHandlerMatchPolicy::ADD_AS_NEW);
		$newZoomDropFolder->setFileHandlerType(DropFolderFileHandlerType::CONTENT);
		$newZoomDropFolder->setFileHandlerConfig($fileHandler);
		
		$newZoomDropFolder->setDc(kDataCenterMgr::getCurrentDcId());
		$newZoomDropFolder->setPath(0);
		$newZoomDropFolder->setFileSizeCheckInterval(30);
//              TODO get the info from the ZoomRegistrationPage
//				if ($automaticDeletePolicyInDays)
//				{
//					$newZoomDropFolder->setAutoFileDeleteDays($automaticDeletePolicyInDays);
//				}
//				$newZoomDropFolder->setFileDeletePolicy();
		$newZoomDropFolder->setAutoFileDeleteDays(DropFolder::AUTO_FILE_DELETE_DAYS_DEFAULT_VALUE);
		$newZoomDropFolder->setFileDeletePolicy(3);
		$newZoomDropFolder->setLastFileTimestamp(0);
		$newZoomDropFolder->setMetadataProfileId(0);
		$newZoomDropFolder->save();
	}
	
}