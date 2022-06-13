<?php

/**
 * Define client request optional configurations
 */
class KalturaRequestConfiguration extends KalturaObject
{
	/**
	 * Impersonated partner id
	 * @var int
	 */
	public $partnerId;
	
	/**
	 * Kaltura API session
	 * @alias sessionId
	 * @var string
	 */
	public $ks;
	
	/**
	 * The language in which to serve the response
	 * @var string
	 */
	public $requestLanguage;
	
	/**
	 * Response profile - this attribute will be automatically unset after every API call.
	 * @var KalturaBaseResponseProfile
	 * @volatile
	 */
	public $responseProfile;
}