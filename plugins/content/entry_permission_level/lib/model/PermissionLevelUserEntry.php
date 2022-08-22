<?php
/**
 * @package plugins.entryPermissionLevel
 * @subpackage model
 */
class PermissionLevelUserEntry extends UserEntry
{
	const PERMISSION_LEVEL_OM_CLASS = 'PermissionLevelUserEntry';
	
	const CUSTOM_DATA_PERMISSION_LEVELS = 'permission_levels';

	public function __construct()
	{
		$this->setType(EntryPermissionLevelPlugin::getPermissionLevelUserEntryTypeCoreValue(PermissionLevelUserEntryType::PERMISSION_LEVEL));
		parent::__construct();
	}
	
	public function getPermissionLevels ()
	{
		$serialized = $this->getFromCustomData(self::CUSTOM_DATA_PERMISSION_LEVELS);
		return unserialize($serialized);
	}
	
	public function setPermissionLevels ($permissionLevels)
	{
		if(!count($permissionLevels))
			return;
		
		$serialized = serialize($permissionLevels);
		return $this->putInCustomData(self::CUSTOM_DATA_PERMISSION_LEVELS, $serialized);
	}
}