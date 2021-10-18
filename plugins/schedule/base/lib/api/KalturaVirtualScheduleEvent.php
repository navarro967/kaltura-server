<?php

class KalturaVirtualScheduleEvent extends KalturaScheduleEvent
{
	/**
	 * The ID of the virtual event connected to this Schedule Event
	 * @var int
	 * @filter eq,in,notin
	 */
	public $virtualEventId;
	
	/**
	 * The type of the Virtual Schedule Event
	 * @var KalturaVirtualScheduleEventType
	 * @insertonly
	 * @filter eq,in,notin
	 */
	public $virtualScheduleEventType;
	
	
	private static $map_between_objects = array
	(
		'virtualEventId',
		'virtualScheduleEventType',
	);
	
	/* (non-PHPdoc)
	 * @see KalturaObject::getMapBetweenObjects()
	 */
	public function getMapBetweenObjects()
	{
		return array_merge(parent::getMapBetweenObjects(), self::$map_between_objects);
	}
	
	/* (non-PHPdoc)
	 * @see KalturaObject::toObject($object_to_fill, $props_to_skip)
	 */
	public function toObject ($sourceObject = null, $propertiesToSkip = array())
	{
		if (is_null($sourceObject))
		{
			$sourceObject = new VirtualScheduleEvent();
		}
		
		return parent ::toObject($sourceObject, $propertiesToSkip);
	}
	
	/**
	 * {@inheritDoc}
	 * @see KalturaScheduleEvent::getScheduleEventType()
	 */
	public function getScheduleEventType ()
	{
		return ScheduleEventType::VIRTUAL;
	}
}
