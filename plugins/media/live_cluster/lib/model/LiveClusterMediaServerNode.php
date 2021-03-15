<?php


class LiveClusterMediaServerNode extends MediaServerNode
{
    const ENVIRONMENT = 'env';
    const SESSION_TYPE = 'st';
    const TIMELINE_URL_PARAM = 'tl';

    /**
     * Applies default values to this object.
     * This method should be called from the object's constructor (or equivalent initialization method).
     * @see __construct()
     */
    public function applyDefaultValues()
    {
        parent::applyDefaultValues();

        $this->setType(LiveClusterPlugin::getLiveClusterMediaServerTypeCoreValue(LiveClusterMediaServerNodeType::LIVE_CLUSTER_MEDIA_SERVER));
    }

    /**
     * @param string $serviceName
     * @return KalturaMediaServerClient
     */
    public function getWebService($serviceName)
    {
        return null;
    }

    public function getLiveWebServiceName()
    {
        return 'NOT_IMPLEMENTED';
    }

    public function getPlaybackHost($protocol = 'http', $format = null, $baseUrl = null, $deliveryType = null)
    {
        $domain = rtrim(kString::removeHttp($baseUrl), '/'); // extract only the domain from the base url
        $domain = str_replace('{hostName}', $this->getHostname(), $domain); // if the domain contain place-holder replace it with the server-node host name

        return "$protocol://$domain/";
    }

    public function getEnvDc()
    {
        return self::ENVIRONMENT . '/' . $this->getEnvironment();
    }

    public function getSessionType($entryServerNode)
    {
        return self::SESSION_TYPE . '/' . $entryServerNode->getServerType() . '/';
    }

	public function getEntryIdUrl(DeliveryProfileDynamicAttributes $da)
	{
		if ($da->getServeVodFromLive())
		{
			$recordingEntryId = $da->getServeLiveAsVodEntryId();
			$entryId =  $da->getEntryId();
			return '/' . self::ENTRY_ID_URL_PARAM . '/' . $entryId . '/' . self::TIMELINE_URL_PARAM . '/' . $recordingEntryId . '/';
//			returns "/e/$entryId/tl/$recordingEntryId/"
		}

		return parent::getEntryIdUrl($da);
	}
}