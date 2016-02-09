<?php
/**
 * @package api
 * @subpackage objects
 * @abstract
 */
abstract class KalturaEntryServerNode extends KalturaObject implements IRelatedFilterable
{
	/**
	 * unique auto-generated identifier
	 * @var int
	 * @readonly
	 * @filter eq,in,notin
	 */
	public $id;

	/**
	 * @var string
	 * @insertonly
	 * @filter eq,in,notin
	 */
	public $entryId;

	/**
	 * @var int
	 * @insertonly
	 * @filter eq,in,notin
	 */
	public $serverNodeId;

	/**
	 * @var int
	 * @readonly
	 */
	public $partnerId;

	/**
	 * @var time
	 * @readonly
	 * @filter lte,gte,order
	 */
	public $createdAt;

	/**
	 * @var time
	 * @readonly
	 * @filter lte,gte,order
	 */
	public $updatedAt;

	/**
	 * @var KalturaEntryServerNodeStatus
	 * @readonly
	 * @filter eq
	 */
	public $status;

	/**
	 * @var KalturaEntryServerNodeType
	 * @readonly
	 * @filter eq
	 */
	public $serverType;

	private static $map_between_objects = array
	(
		"id",
		"entryId",
		"serverNodeId",
		"partnerId",
		"createdAt",
		"updatedAt",
		"status",
		"serverType"
	);

	public function getMapBetweenObjects ( )
	{
		return array_merge ( parent::getMapBetweenObjects() , self::$map_between_objects );
	}


	/**
	 * Function returns KalturaEntryServerNode sub-type according to protocol
	 * @var string $type
	 * @return KalturaEntryServerNode
	 *
	 */
	public static function getInstanceByType ($type)
	{
		// TODO once there is a LiveEntryServerNode we should examine this function again
		$obj = KalturaPluginManager::loadObject("KalturaEntryServerNode",$type);
		if (is_null($obj))
		{
			KalturaLog::err("The type '$type' is unknown");
		}
		return $obj;
	}

	/* (non-PHPdoc)
	 * @see KalturaObject::toInsertableObject()
	 */
	public function toInsertableObject ( $object_to_fill = null , $props_to_skip = array() )
	{
		$object_to_fill = parent::toInsertableObject($object_to_fill, $props_to_skip);
		$object_to_fill->setPartnerId(kCurrentContext::getCurrentPartnerId());
		return $object_to_fill;
	}

	/**
	 * Should return the extra filters that are using more than one field
	 * On inherited classes, do not merge the array with the parent class
	 *
	 * @return array
	 */
	function getExtraFilters()
	{
		return array();
	}

	/**
	 * Should return the filter documentation texts
	 *
	 */
	function getFilterDocs()
	{
		return array();
	}



}