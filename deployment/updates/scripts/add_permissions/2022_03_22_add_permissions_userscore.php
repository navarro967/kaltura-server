<?php
/**
 * @package deployment
 * @subpackage rigel.roles_and_permissions
 */

$script = realpath(dirname(__FILE__) . '/../../../../') . '/alpha/scripts/utils/permissions/addPermissionsAndItems.php';

$config = realpath(dirname(__FILE__)) . '/../../../permissions/service.game.userscore.ini';
passthru("php $script $config");