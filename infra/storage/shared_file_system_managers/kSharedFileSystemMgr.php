<?php
/**
 * Created by IntelliJ IDEA.
 * User: yossi.papiashvili
 * Date: 5/26/19
 * Time: 4:19 PM
 */

/**
 * List of classes that extend 'kFileTransferMgr'.
 * Instances of these classes can be created using the 'getInstance($type)' function.
 *
 * @package infra
 * @subpackage Storage
 */

require_once(dirname(__FILE__) . '/kS3SharedFileSystemMgr.php');
require_once(dirname(__FILE__) . '/kNfsSharedFileSystemMgr.php');
require_once(dirname(__FILE__) . '/../kFileBase.php');

interface kSharedFileSystemMgrType
{
	const NFS = "NFS";
	const S3 = "S3";
}

abstract class kSharedFileSystemMgr
{
	/* @var $kSharedFsMgr kSharedFileSystemMgr */
	protected static $kSharedFsMgr;
	
	private static $kSharedRootPath;
	
	public static $storageConfig = array();
	
	public function __construct(array $options = null)
	{
		return;
	}
	
	/**
	 * Should create the directory tree of the given file path
	 *
	 * @param $filePath the file path
	 *
	 * @return true / false according to success
	 */
	abstract protected function doCreateDirForPath($filePath);
	
	/**
	 * Check if the given file path exists in the destination storage
	 *
	 * @param $filePath the file path
	 *
	 * @return true / false according to success
	 */
	abstract protected function doCheckFileExists($filePath);
	
	/**
	 * Get the content of the given file path
	 *
	 * @param $filePath the file path
	 *
	 * @return content | error on failure
	 */
	abstract protected function doGetFileContent($filePath, $from_byte = 0, $to_byte = -1);
	
	
	/**
	 * Remove the symlink of the given file path
	 *
	 * @param $filePath the file path
	 *
	 * @return true / false according to success
	 */
	abstract protected function doUnlink($filePath);
	
	
	/**
	 * Write a file in atomic way
	 *
	 * @param $filePath the file path
	 * @param $fileContent the content to put in the filePath
	 *
	 * @return true / false according to success
	 */
	abstract protected function doPutFileContentAtomic($filePath, $fileContent);
	
	/**
	 * Write a file to the given file path
	 *
	 * @param $filePath the file path
	 * @param $fileContent the content to put in the filePath
	 * @param $flags file_put_contents flags
	 * @param $context A valid context resource created with stream_context_create().
	 *
	 * @return true / false according to success
	 */
	abstract protected function doPutFileContent($filePath, $fileContent, $flags = 0, $context = null);
	
	/**
	 * Rename a file
	 *
	 * @param $filePath the current file path
	 * @param $newFilePath the new file path
	 *
	 * @return true / false according to success
	 */
	abstract protected function doRename($filePath, $newFilePath);
	
	/**
	 * Copy a file
	 *
	 * @param $fromFilePath the current file path
	 * @param $toFilePath the new file path
	 *
	 * @return true / false according to success
	 */
	abstract protected function doCopy($fromFilePath, $toFilePath);
	
	
	/**
	 * Copy a file
	 *
	 * @param $resource resource to fetch
	 * @param $destFilePath file name to save remote content to
	 *
	 * @return true / false according to success
	 */
	abstract protected function doGetFileFromResource($resource, $destFilePath = null, $allowInternalUrl = false);
	
	/**
	 * creates a directory using the dirname of the specified path
	 *
	 * @param string $path path to create dir
	 * @param int $rights mode for the dir
	 * @param bool $recursive should we make the dir path recursively
	 * @return bool true on success or false on failure.
	 */
	abstract protected function doFullMkdir($path, $rights = 0755, $recursive = true);
	
	/**
	 * creates a directory using the specified path
	 * @param string $path path to create dir
	 * @param int $rights mode for the dir
	 * @param bool $recursive should we make the dir path recursively
	 * @return bool true on success or false on failure.
	 */
	abstract protected function doFullMkfileDir($path, $rights = 0777, $recursive = true);
	
	/**
	 * move path from one directory to another
	 *
	 * @param $from source path
	 * @param $to dest path
	 * @param bool $override_if_exists
	 * @param bool $copy
	 * @return true / false according to success
	 */
	abstract protected function doMoveFile($from, $to, $override_if_exists = false, $copy = false);
	
	/**
	 * check if path is dir
	 *
	 * @param $path dir path
	 * @return true / false according to success
	 */
	abstract protected function doIsDir($path);
	
	/**
	 * creates path directory
	 *
	 * @param $path dir path
	 * @param $mode mode for the dir
	 * @param $recursive should create recursively
	 * @return true / false according to success
	 */
	abstract protected function doMkdir($path, $mode, $recursive);
	
	/**
	 * removes path directory
	 *
	 * @param $path dir path
	 * @return true / false according to success
	 */
	abstract protected function doRmdir($path);
	
	/**
	 * chmod path with given mode
	 *
	 * @param $path path to change mode
	 * @param $mode mode for the dir
	 * @param $user the user tho change to
	 * @param $group the group tho change to
	 * @return true / false according to success
	 */
	abstract protected function doChown($path, $user, $group);
	
	/**
	 * chmod path with given mode
	 *
	 * @param $path path to change mode
	 * @param $mode mode for the dir
	 * @return true / false according to success
	 */
	abstract protected function doChmod($path, $mode);
	
	/**
	 * return the file size of given file
	 *
	 * @param $filename file to check the size
	 * @return mixed size on success false on failure
	 */
	abstract protected function doFileSize($filename);
	
	/**
	 * delete file
	 *
	 * @param $filename file to delete
	 * @return true / false according to success
	 */
	abstract protected function doDeleteFile($filename);
	
	/**
	 * copy single file from local source to shared destination
	 *
	 * @param $src local source path
	 * @param $dest shared destination
	 * @param $deleteSrc should we delete the source
	 * @return true / false according to success
	 */
	abstract protected function doCopySingleFile($src, $dest, $deleteSrc);
	
	/**
	 * returns maximum parts num allowed for upload in multipart
	 *
	 * @return int
	 */
	abstract protected function doGetMaximumPartsNum();
	
	/**
	 * returns file minimum size for upload
	 *
	 * @return int
	 */
	abstract protected function doGetUploadMinimumSize();
	
	/**
	 * returns file max size for upload
	 *
	 * @return int
	 */
	abstract protected function doGetUploadMaxSize();

	/**
	 * returns list of files under given file path
	 *
	 * @param $filePath file path to list dir content for
	 * @param string $pathPrefix
	 * @param bool $recursive
	 * @param bool $fileNamesOnly
	 * @return array
	 */
	abstract protected function doListFiles($filePath, $pathPrefix = '', $recursive = true, $fileNamesOnly = false);
	
	/**
	 * returns true/false if the givven file path exists and is a regular file
	 *
	 * @param $filePath file path to check
	 *
	 * @return int
	 */
	abstract protected function doIsFile($filePath);
	
	/**
	 * Returns the canonicalized absolute pathname on success.
	 *
	 * @param $filePath file path
	 *
	 * @return int
	 */
	abstract protected function doRealPath($filePath, $getRemote = true);
	
	/**
	 * Returns the mime_type of the file.
	 *
	 * @param $filePath file path
	 *
	 * @return string
	 */
	abstract protected function doMimeType($filePath);
	
	/**
	 * dump file in parts
	 *
	 * @param $filePath
	 * @param $range_from
	 * @param $range_length
	 * @return mixed
	 */
	abstract protected function doDumpFilePart($filePath, $range_from, $range_length);
	
	/**
	 * Chgrp path with content group
	 *
	 * @param $filePath
	 * @param $contentGroup
	 * @return mixed
	 */
	abstract protected function doChgrp($filePath, $contentGroup);
	
	/**
	 * Get file path last modified time
	 *
	 * @param $filePath
	 * @return mixed
	 */
	abstract protected function doFilemtime($filePath);
	
	/**
	 * Move local file to shared dir
	 *
	 * @param $from - From file path
	 * @param to - to file path
	 * @param $copy - should copy
	 * @return mixed
	 */
	abstract protected function doMoveLocalToShared($from, $to, $copy = false);
	
	/**
	 *
	 * @param $filePath
	 * @return mixed
	 */
	abstract protected function doDir($filePath);
	
	/**
	 * copy dir from src to dest
	 *
	 * @param $src
	 * @param $dest
	 * @param $deleteSrc
	 * @return bool
	 */
	abstract protected function doCopyDir($src, $dest, $deleteSrc);
	
	/**
	 * @return bool
	 */
	abstract protected function doShouldPollFileExists();
	
	/**
	 * @return bool
	 */
	abstract protected function doCopySharedToSharedAllowed();
	
	
	public function createDirForPath($filePath)
	{
		kFile::fixPath($filePath);
		return $this->doCreateDirForPath($filePath);
	}
	
	public function checkFileExists($filePath)
	{
		$filePath = kFileBase::fixPath($filePath);
		return $this->doCheckFileExists($filePath);
	}
	
	public function getFile($filePath)
	{
		kFile::fixPath($filePath);
		return $this->doGetFile($filePath);
	}
	
	public function getFileFromResource($url, $destFilePath = null, $allowInternalUrl = false)
	{
		$destFilePath = kFile::fixPath($destFilePath);
		return $this->doGetFileFromResource($url, $destFilePath, $allowInternalUrl);
	}
	
	public function unlink($filePath)
	{
		$filePath = kFile::fixPath($filePath);
		return $this->doUnlink($filePath);
	}
	
	public function putFileContentAtomic($filePath, $fileContent)
	{
		$filePath = kFile::fixPath($filePath);
		return $this->doPutFileContentAtomic($filePath, $fileContent);
	}
	
	public function putFileContent($filePath, $fileContent, $flags = 0, $context = null)
	{
		$filePath = kFile::fixPath($filePath);
		return $this->doPutFileContent($filePath, $fileContent, $flags, $context);
	}
	
	public function rename($filePath, $newFilePath)
	{
		$filePath = kFile::fixPath($filePath);
		return $this->doRename($filePath, $newFilePath);
	}
	
	public function copy($fromFilePath, $toFilePath)
	{
		$fromFilePath = kFileBase::fixPath($fromFilePath);
		$toFilePath = kFileBase::fixPath($toFilePath);
		
		if(kFile::isSharedPath($fromFilePath) && !kFile::isSharedPath($toFilePath))
		{
			return kFile::getExternalFile(kFile::realPath($fromFilePath), dirname($toFilePath), basename($toFilePath));
		}
		
		if(!kFile::isSharedPath($fromFilePath) && kFile::isSharedPath($toFilePath))
		{
			return $this->doMoveLocalToShared($fromFilePath, $toFilePath, true);
		}
		
		if(kFile::isSharedPath($fromFilePath) && kFile::isSharedPath($toFilePath))
		{
			return $this->doCopy($fromFilePath, $toFilePath);
		}
		
		return $this->doCopy($fromFilePath, $toFilePath);
	}
	
	public function getFileContent($filePath, $from_byte = 0, $to_byte = -1)
	{
		$filePath = kFileBase::fixPath($filePath);
		return $this->doGetFileContent($filePath, $from_byte, $to_byte);
	}
	
	public function fullMkdir($path, $rights = 0755, $recursive = true)
	{
		$path = kFile::fixPath($path);
		return $this->doFullMkdir($path, $rights, $recursive);
	}
	
	public function moveFile($from, $to, $override_if_exists = false, $copy = false)
	{
		$from = kFileBase::fixPath($from);
		$to = kFileBase::fixPath($to);
		
		if (!kString::beginsWith($from, self::$kSharedRootPath)) {
			return $this->doMoveLocalToShared($from, $to);
		}
		
		return $this->doMoveFile($from, $to, $override_if_exists, $copy);
	}
	
	public function isDir($path)
	{
		$path = kFileBase::fixPath($path);
		return $this->doIsDir($path);
	}
	
	public function mkdir($path, $mode = 0777, $recursive = false)
	{
		$path = kFileBase::fixPath($path);
		return $this->doMkdir($path, $mode, $recursive);
	}
	
	public function rmdir($path)
	{
		$path = kFileBase::fixPath($path);
		return $this->doRmdir($path);
	}
	
	public function chmod($path, $mode)
	{
		$path = kFileBase::fixPath($path);
		return $this->doChmod($path, $mode);
	}
	
	public function chown($path, $user, $group)
	{
		$path = kFileBase::fixPath($path);
		return $this->doChown($path, $user, $group);
	}
	
	public function fileSize($filename)
	{
		$filename = kFileBase::fixPath($filename);
		return $this->doFileSize($filename);
	}
	
	public function deleteFile($filename)
	{
		$filename = kFileBase::fixPath($filename);
		return $this->doDeleteFile($filename);
	}
	
	public function getUploadMinimumSize()
	{
		return $this->doGetUploadMinimumSize();
	}
	
	public function getMaximumPartsNum()
	{
		return $this->doGetMaximumPartsNum();
	}
	
	public function getUploadMaxSize()
	{
		return $this->doGetUploadMaxSize();
	}
	
	function copySingleFile($from, $to, $deleteSrc)
	{
		$from = kFileBase::fixPath($from);
		$to = kFileBase::fixPath($to);
		
		if (!kString::beginsWith($from, self::$kSharedRootPath)) {
			return $this->doMoveLocalToShared($from, $to, !$deleteSrc);
		}
		
		return $this->doRename($from, $to);
	}
	
	public function listFiles($filePath, $pathPrefix = '', $recursive = true, $fileNamesOnly = false)
	{
		$filePath = kFileBase::fixPath($filePath);
		return $this->doListFiles($filePath, $pathPrefix, $recursive, $fileNamesOnly);
	}
	
	public function isFile($filePath)
	{
		$filePath = kFileBase::fixPath($filePath);
		return $this->doIsFile($filePath);
	}
	
	public function realPath($filePath, $getRemote = true)
	{
		$filePath = kFileBase::fixPath($filePath);
		return $this->doRealPath($filePath, $getRemote);
	}
	
	public function mimeType($filePath)
	{
		$filePath = kFileBase::fixPath($filePath);
		return $this->doMimeType($filePath);
	}
	
	
	public function shouldPollFileExists()
	{
		return $this->doShouldPollFileExists();
	}
	/**
	 * copies local src to shared destination.
	 * Doesn't support non-flat directories!
	 * One can't use rename because rename isn't supported between partitions.
	 */
	protected function copyRecursively($src, $dest, $deleteSrc = false)
	{
		// src expected to be local file
		if (is_dir($src)) {
			// Generate target directory
			if ($this->checkFileExists($dest)) {
				if (!$this->isDir($dest)) {
					KalturaLog::err("Can't override a file with a directory [$dest]");
					return false;
				}
			}
			else {
				if (!$this->mkdir($dest)) {
					KalturaLog::err("Failed to create directory [$dest]");
					return false;
				}
			}
			// Copy files
			$dir = dir($src);
			while (false !== $entry = $dir->read()) {
				if ($entry == '.' || $entry == '..') {
					continue;
				}
				$newSrc = $src . DIRECTORY_SEPARATOR . $entry;
				if ($this->is_dir($newSrc)) {
					KalturaLog::err("Copying of non-flat directroeis is illegal");
					return false;
				}
				$res = $this->copySingleFile($newSrc, $dest . DIRECTORY_SEPARATOR . $entry, $deleteSrc);
				if (!$res) {
					return false;
				}
			}
			// Delete source
			if ($deleteSrc && (!rmdir($src))) {
				KalturaLog::err("Failed to delete source directory : [$src]");
				return false;
			}
		}
		else {
			$res = $this->copySingleFile($src, $dest, $deleteSrc);
			if (!$res) {
				return false;
			}
		}
		return true;
	}
	
	public static function getInstance($type = null)
	{
		if (!$type) {
			$dc_config = kConf::getMap("dc_config");
			$type = isset($dc_config['fileSystemType']) ? $dc_config['fileSystemType'] : kSharedFileSystemMgrType::NFS;
		}
		
		if (isset(self::$kSharedFsMgr[$type])) {
			return self::$kSharedFsMgr[$type];
		}
		
		switch ($type) {
			case kSharedFileSystemMgrType::NFS:
				self::$kSharedFsMgr[$type] = new kNfsSharedFileSystemMgr(self::$storageConfig);
				break;
			
			case kSharedFileSystemMgrType::S3:
				self::$kSharedFsMgr[$type] = new kS3SharedFileSystemMgr(self::$storageConfig);
				break;
		}
		
		return self::$kSharedFsMgr[$type];
	}
	
	public static function getInstanceFromPath($path)
	{
		$path = kFile::fixPath($path);
		$storageTypeMap = kFile::getStorageTypeMap();
		
		foreach (array_keys($storageTypeMap) as $pathPrefix) {
			if (kString::beginsWith($path, $pathPrefix)) {
				self::$kSharedRootPath = $pathPrefix;
				return self::getInstance($storageTypeMap[$pathPrefix]);
			}
		}
		
		return new kNfsSharedFileSystemMgr();
	}
	
	public static function getDcById($dc_id)
	{
		$dc_config = kConf::getMap("dc_config");
		$dc_list = $dc_config["list"];
		if (isset($dc_list[$dc_id])) {
			$dc = $dc_list[$dc_id];
		}
		else {
			throw new Exception ("Cannot find DC with id [$dc_id]");
		}
		
		$dc["id"] = $dc_id;
		return $dc;
	}
	
	public function dumpFilePart($file_name, $range_from, $range_length)
	{
		$filePath = kFileBase::fixPath($file_name);
		
		return $this->doDumpFilePart($filePath, $range_from, $range_length);
	}
	
	public function dir($filePath)
	{
		return $this->doDir($filePath);
	}
	
	public function chgrp($filePath, $contentGroup)
	{
		return $this->doChgrp($filePath, $contentGroup);
	}
	
	public function filemtime($filePath)
	{
		return $this->doFilemtime($filePath);
	}
	
	public function copyDir($src, $dest, $deleteSrc)
	{
		return $this->doCopyDir($src, $dest, $deleteSrc);
	}
	
	public static function getIsMoveAtomic($temp_file_path)
	{
		$temp_file_path = kFile::fixPath($temp_file_path);
		/* @var $fsMge kSharedFileSystemMgr */
		
		$fsMge = self::getInstanceFromPath($temp_file_path);
		return $fsMge->doCopySharedToSharedAllowed();
	}
	
	public static function getSharedRootByType($storageType)
	{
		$storageTypeMap = kFile::getStorageTypeMap();
		foreach ($storageTypeMap as $key => $value)
		{
			if($value == $storageType)
			{
				return $key;
			}
		}
		return myContentStorage::getFSContentRootPath();
	}
	
	public static function setFileSystemOptions($key, $value)
	{
		self::$storageConfig[$key] = $value;
	}
	
	public static function restoreStreamWrappers()
	{
		stream_wrapper_restore('http');
		stream_wrapper_restore('https');
	}
	
	public static function unRegisterStreamWrappers()
	{
		stream_wrapper_unregister('http');
		stream_wrapper_unregister('https');
	}
	
	/**
	 * This function is required since this code can run before the autoloader
	 *
	 * @param string $msg
	 */
	public static function safeLog($msg, $logType = "log")
	{
		if (class_exists('KalturaLog') && KalturaLog::isInitialized())
		{
			KalturaLog::$logType($msg);
		}
	}
}