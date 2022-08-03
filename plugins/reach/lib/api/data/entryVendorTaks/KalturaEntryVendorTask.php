<?php
/**
 * @package plugins.reach
 * @subpackage api.objects
 * @relatedService EntryVendorTaskService
 */
class KalturaEntryVendorTask extends KalturaObject implements IRelatedFilterable
{
	/**
	 * @var bigint
	 * @readonly
	 * @filter eq,in,notin,order
	 */
	public $id;
	
	/**
	 * @var int
	 * @readonly
	 */
	public $partnerId;
	
	/**
	 * @var int
	 * @filter eq,in
	 * @readonly
	 */
	public $vendorPartnerId;
	
	/**
	 * @var time
	 * @readonly
	 * @filter gte,lte,order
	 */
	public $createdAt;
	
	/**
	 * @var time
	 * @readonly
	 * @filter gte,lte,order
	 */
	public $updatedAt;
	
	/**
	 * @var time
	 * @readonly
	 * @filter gte,lte,order
	 */
	public $queueTime;
	
	/**
	 * @var time
	 * @readonly
	 * @filter gte,lte,order
	 */
	public $finishTime;
	
	/**
	 * @var string
	 * @filter eq
	 * @insertonly
	 */
	public $entryId;
	
	/**
	 * @var KalturaEntryVendorTaskStatus
	 * @filter eq,in, order
	 * @requiresPermission insert, update
	 */
	public $status;
	
	/**
	 * The profile id from which this task base config is taken from
	 * @var int
	 * @filter eq,in
	 * @insertonly
	 */
	public $reachProfileId;
	
	/**
	 * The catalog item Id containing the task description 
	 * @var int
	 * @filter eq,in
	 * @insertonly
	 */
	public $catalogItemId;
	
	/**
	 * The charged price to execute this task
	 * @var float
	 * @filter order
	 * @readonly
	 */
	public $price;
	
	/**
	 * The ID of the user who created this task
	 * @var string
	 * @filter eq
	 * @readonly
	 */
	public $userId;
	
	/**
	 * The user ID that approved this task for execution (in case moderation is requested)
	 * @var string
	 * @readonly
	 */
	public $moderatingUser;
	
	/**
	 * Err description provided by provider in case job execution has failed
	 * @var string
	 * @requiresPermission insert, update
	 */
	public $errDescription;
	
	/**
	 * Access key generated by Kaltura to allow vendors to ingest the end result to the destination
	 * @var string
	 * @readonly
	 */
	public $accessKey;

	/**
	 * Vendor generated by Kaltura representing the entry vendor task version correlated to the entry version
	 * @var string
	 * @readonly
	 */
	public $version;
	
	/**
	 * User generated notes that should be taken into account by the vendor while executing the task
	 * @var string
	 */
	public $notes;

	/**
	 * @var string
	 * @readonly
	 */
	public $dictionary;
	
	/**
	 * Task context
	 * @var string
	 * @filter eq
	 */
	public $context;
	
	/**
	 * Task result accuracy percentage 
	 * @requiresPermission insert, update
	 * @var int
	 */
	public $accuracy;
	
	/**
	 * Task main object generated by executing the task
	 * @var string
	 * @requiresPermission insert, update
	 */
	public $outputObjectId;
	
	/**
	 * Json object containing extra task data required by the requester 
	 * @var string
	 */
	public $partnerData;
	
	/**
	 * Task creation mode
	 * @var KalturaEntryVendorTaskCreationMode
	 * @readonly
	 */
	public $creationMode;
	
	/**
	 * @var KalturaVendorTaskData
	 */
	public $taskJobData;

	/**
	 * @var time
	 * @readonly
	 * @filter gte,lte,order
	 */
	public $expectedFinishTime;

	/**
	 * @var KalturaVendorServiceType
	 * @readonly
	 */
	public $serviceType;

	/**
	 * @var KalturaVendorServiceFeature
	 * @readonly
	 */
	public $serviceFeature;

	/**
	 * @var KalturaVendorServiceTurnAroundTime
	 * @readonly
	 */
	public $turnAroundTime;

	/**
	 * The vendor's task internal Id
	 * @requiresPermission insert, update
	 * @var string
	 */
	public $externalTaskId;

	private static $map_between_objects = array
	(
		'id',
		'partnerId',
		'vendorPartnerId',
		'createdAt',
		'updatedAt',
		'queueTime',
		'finishTime',
		'entryId',
		'status',
		'reachProfileId',
		'catalogItemId',
		'price',
		'userId',
		'moderatingUser',
		'errDescription',
		'accessKey',
		'notes',
		'version',
		'context',
		'accuracy',
		'outputObjectId',
		'dictionary',
		'partnerData',
		'creationMode',
		'taskJobData',
		'expectedFinishTime',
		'serviceType',
		'serviceFeature',
		'turnAroundTime',
		'externalTaskId'
	);
	
	/* (non-PHPdoc)
	 * @see KalturaCuePoint::getMapBetweenObjects()
	 */
	public function getMapBetweenObjects()
	{
		return array_merge(parent::getMapBetweenObjects(), self::$map_between_objects);
	}
	
	/* (non-PHPdoc)
 	 * @see KalturaObject::toInsertableObject()
 	 */
	public function toInsertableObject($object_to_fill = null, $props_to_skip = array())
	{
		if (is_null($object_to_fill))
		{
			$object_to_fill = new EntryVendorTask();
		}
		
		return parent::toInsertableObject($object_to_fill, $props_to_skip);
	}
	
	public function validateForInsert($propertiesToSkip = array())
	{
		$this->validatePropertyNotNull("reachProfileId");
		$this->validatePropertyNotNull("catalogItemId");
		$this->validatePropertyNotNull("entryId");
		$this->validateEntryId();
		
		if($this->partnerData && !$this->checkIsValidJson($this->partnerData))
		{
			throw new KalturaAPIException(KalturaReachErrors::PARTNER_DATA_NOT_VALID_JSON_STRING);
		}
		
		if(isset($this->taskJobData))
		{
			$this->taskJobData->validateForInsert();
		}

		$this->validateCatalogLimitations();
		
		return parent::validateForInsert($propertiesToSkip);
	}
	
	public function validateForUpdate($sourceObject, $propertiesToSkip = array())
	{
		$closedStatuses = array(
			EntryVendorTaskStatus::ABORTED,
			EntryVendorTaskStatus::READY,
			EntryVendorTaskStatus::REJECTED,
			EntryVendorTaskStatus::ERROR,
		);
		
		/* @var $sourceObject EntryVendorTask */
		if($this->status && $this->status != $sourceObject->getStatus() && in_array($sourceObject->getStatus(), $closedStatuses))
		{
			throw new KalturaAPIException(KalturaReachErrors::CANNOT_UPDATE_STATUS_OF_TASK_WHICH_IS_IN_FINAL_STATE, $sourceObject->getId(), $sourceObject->getStatus(), $this->status);
		}
		
		if($this->partnerData && !$this->checkIsValidJson($this->partnerData))
		{
			throw new KalturaAPIException(KalturaReachErrors::PARTNER_DATA_NOT_VALID_JSON_STRING);
		}
		
		if(isset($this->taskJobData))
		{
			$this->taskJobData->validateForUpdate($sourceObject->getTaskJobData(), $propertiesToSkip);
		}
		
		return parent::validateForUpdate($sourceObject, $propertiesToSkip);
	}
	
	private function validateEntryId()
	{
		$dbEntry = entryPeer::retrieveByPK($this->entryId);
		if (!$dbEntry)
		{
			throw new KalturaAPIException(KalturaErrors::ENTRY_ID_NOT_FOUND, $this->entryId);
		}
		
		if ($dbEntry->getStatus() != entryStatus::READY)
		{
			throw new KalturaAPIException(KalturaErrors::ENTRY_NOT_READY, $this->entryId);
		}
		if (!ReachPlugin::isEntryTypeSupportedForReach($dbEntry->getType()))
		{
			throw new KalturaAPIException(KalturaReachErrors::ENTRY_TYPE_NOT_SUPPORTED, $dbEntry->getType());
		}

		if ($this->isScheduled())
		{
			$connectedEvent = ScheduleEventPeer::retrieveByPK($this->taskJobData->scheduledEventId);

			if ($this->entryId !== $connectedEvent->getTemplateEntryId())
			{
				throw new KalturaAPIException(KalturaReachErrors::TASK_EVENT_ENTRY_ID_MISMATCH, $this->entryId, $connectedEvent->getId());
			}
		}
	}

	private function validateCatalogLimitations()
	{
		$vendorCatalogItem = VendorCatalogItemPeer::retrieveByPK($this->catalogItemId);
		//currently a param for simplicity should be made a const with more complex requirement options
		$featureToDataMap = array(VendorServiceFeature::LIVE_CAPTION => 'KalturaScheduledVendorTaskData');
		$featureType = $vendorCatalogItem->getServiceFeature();

		if (key_exists($featureType, $featureToDataMap))
		{
			$this->validatePropertyNotNull('taskJobData');
			if (!$this->taskJobData instanceof $featureToDataMap[$featureType])
			{
				throw new KalturaAPIException(KalturaReachErrors::CATALOG_ITEM_AND_JOB_DATA_MISMATCH, get_class($vendorCatalogItem), get_class($this->taskJobData));
			}
		}

		if (isset($this->taskJobData))
		{
			$this->taskJobData->validateCatalogLimitations($vendorCatalogItem);
		}
	}
	
	/* (non-PHPdoc)
	 * @see KalturaObject::fromObject()
	 */
	public function doFromObject($dbObject, KalturaDetachedResponseProfile $responseProfile = null)
	{
		/* @var $dbObject EntryVendorTask */
		parent::doFromObject($dbObject, $responseProfile);

		if ($this->shouldGet('taskJobData', $responseProfile) && !is_null($dbObject->getTaskJobData()))
		{
			$this->taskJobData = KalturaVendorTaskData::getInstance($dbObject->getTaskJobData(), $responseProfile);
		}
	}

	public function getExtraFilters()
	{
		return array();
	}
	
	public function getFilterDocs()
	{
		return array();
	}
	
	private function checkIsValidJson($string)
	{
		$json = json_decode($string);
		return (is_object($json) && json_last_error() == JSON_ERROR_NONE) ? true : false;
	}

	public function isScheduled()
	{
		return $this->taskJobData instanceof KalturaScheduledVendorTaskData;
	}
}
