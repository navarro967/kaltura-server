<?php
/**
 * @package plugins.quickPlayDistribution
 * @subpackage lib
 */
class QuickPlayDistributionEngine extends DistributionEngine implements 
	IDistributionEngineSubmit,
	IDistributionEngineUpdate
{
	/* (non-PHPdoc)
	 * @see IDistributionEngineSubmit::submit()
	 */
	public function submit(KalturaDistributionSubmitJobData $data)
	{
		$this->validateJobDataObjectTypes($data);
		
		$this->handleSubmit($data, $data->distributionProfile, $data->providerData);
		
		return true;
	}

	/**
	 * (non-PHPdoc)
	 * @see IDistributionEngineUpdate::update()
	 */
	public function update(KalturaDistributionUpdateJobData $data)
	{
		$this->validateJobDataObjectTypes($data);
		
		$this->handleSubmit($data, $data->distributionProfile, $data->providerData);
		
		return true;
	}
	
	/**
	 * @param KalturaDistributionJobData $data
	 * @throws Exception
	 */
	protected function validateJobDataObjectTypes(KalturaDistributionJobData $data)
	{
		if(!$data->distributionProfile || !($data->distributionProfile instanceof KalturaQuickPlayDistributionProfile))
			throw new Exception("Distribution profile must be of type KalturaQuickPlayDistributionProfile");
	
		if(!$data->providerData || !($data->providerData instanceof KalturaQuickPlayDistributionJobProviderData))
			throw new Exception("Provider data must be of type KalturaQuickPlayDistributionJobProviderData");
	}
	
	/**
	 * @param string $path
	 * @param KalturaDistributionJobData $data
	 * @param KalturaVerizonDistributionProfile $distributionProfile
	 * @param KalturaVerizonDistributionJobProviderData $providerData
	 */
	public function handleSubmit(KalturaDistributionJobData $data, KalturaQuickPlayDistributionProfile $distributionProfile, KalturaQuickPlayDistributionJobProviderData $providerData)
	{
		KalturaLog::debug("Submiting data");
		
		$fileName = $data->entryDistribution->entryId . '_' . date('Y-m-d_H-i-s') . '.xml';
		KalturaLog::debug('Sending file '. $fileName);
		KalturaLog::debug('XML data:'. $providerData->xml);
		
		$sftpManager = $this->getSFTPManager($distributionProfile);
		
		// upload the thumbnails
		foreach($providerData->thumbnailFilePaths as $thumbnailFilePath)
		{
			/* @var $thumbnailFilePath KalturaString */
			if (!file_exists($thumbnailFilePath->value))
				throw new KalturaDistributionException('Thumbnail file path ['.$thumbnailFilePath.'] not found, assuming it wasn\'t synced and the job will retry');
				
			$sftpManager->putFile(pathinfo($thumbnailFilePath->value, PATHINFO_BASENAME), $thumbnailFilePath->value);
		}
		
		// upload the video files
		foreach($providerData->videoFilePaths as $videoFilePath)
		{
			/* @var $videoFilePath KalturaString */
			if (!file_exists($videoFilePath->value))
				throw new KalturaDistributionException('Video file path ['.$videoFilePath.'] not found, assuming it wasn\'t synced and the job will retry');
				
			$sftpManager->putFile(pathinfo($videoFilePath->value, PATHINFO_BASENAME), $videoFilePath->value);
		}
		
		// upload the metadata file
		$res = $sftpManager->filePutContents($fileName, $providerData->xml);
				
		if ($res === false)
			throw new Exception('Failed to upload metadata file to sftp');
			
		KalturaLog::info('Package was sent successfully');
			
		$data->remoteId = $fileName;
		$data->sentData = $providerData->xml;
	}
	
	/* (non-PHPdoc)
	 * @see DistributionEngine::configure()
	 */
	public function configure(KSchedularTaskConfig $taskConfig)
	{
	}
	
	/**
	 * 
	 * @param KalturaQuickPlayDistributionProfile $distributionProfile
	 * @return sftpMgr
	 */
	protected function getSFTPManager(KalturaQuickPlayDistributionProfile $distributionProfile)
	{
		$host = $distributionProfile->sftpHost;
		$login = $distributionProfile->sftpLogin;
		$pass = $distributionProfile->sftpPass;
		$sftpManager = kFileTransferMgr::getInstance(kFileTransferMgrType::SFTP);
		$sftpManager->login($host, $login, $pass);
		return $sftpManager;
	}
}