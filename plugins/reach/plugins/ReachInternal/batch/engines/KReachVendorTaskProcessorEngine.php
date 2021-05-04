<?php

/**
 * @package plugins.eventNotification
 * @subpackage Scheduler
 */
abstract class KReachVendorTaskProcessorEngine
{

    /**
     * @var KalturaReachClientPlugin
     */
    protected $reachPlugin;

    function __construct()
    {

        $this->reachPlugin = KalturaReachClientPlugin::get(KBatchBase::$kClient);
    }

    /**
     * @param $engineType
     * @return KReachVendorTaskProcessorEngine
     */
    public static function getInstance ($engineType)
    {
        switch ($engineType)
        {
            case KalturaReachVendorEngineType::HELLO_WORLD:
                return new KReachVendorTaskHelloWorldProcessorEngine();
            break;
        }

        return KalturaPluginManager::loadObject('KReachVendorTaskProcessorEngine', $engineType);

    }

    /**
     * @param KalturaEntryVendorTask $vendorTask
     * @return mixed
     */
    abstract function handleTask (KalturaEntryVendorTask $vendorTask);

    /**
     * In certain situations, it will be beneficial to detect whether a newer version of the task has become available during the current one's processing.
     * In such a case we may want to cancel the current task in order to avoid race conditions.
     *
     * @return bool
     */
    public function ensureUniqueness(KalturaEntryVendorTask $vendorTask)
    {
        KBatchBase::unimpersonate();

        KBatchBase::impersonate($vendorTask->partnerId);

        $filter = new KalturaEntryVendorTaskFilter();
        $filter->entryIdEqual = $vendorTask->entryId;
        $filter->catalogItemIdEqual = $vendorTask->catalogItemId;
        $filter->idNotIn = $vendorTask->id;
        $filter->statusIn = KalturaEntryVendorTaskStatus::PENDING . ',' . KalturaEntryVendorTaskStatus::PENDING_MODERATION . ',' . KalturaEntryVendorTaskStatus::PROCESSING;
        $filter->createdAtGreaterThanOrEqual = $vendorTask->createdAt;

        $response = $this->reachPlugin->entryVendorTask->listAction($filter);

        if($response->totalCount)
        {
            KalturaLog::info('Newer entry vendor tasks are available for entry ' . $vendorTask->entryId . ' and catalog item ID ' . $vendorTask->catalogItemId . '. This task will be aborted.');
            try {
                $update = new KalturaEntryVendorTask();
                $update->status = KalturaEntryVendorTaskStatus::ABORTED;
                $this->reachPlugin->entryVendorTask->update($vendorTask->id, $update);
            } catch (Exception $e)
            {
                KalturaLog::err('Failed to update the task with error message: ' . $e->getMessage());
            }

            KBatchBase::unimpersonate();
            return false;
        }
        KBatchBase::unimpersonate();
        return true;

    }

    public function moveTaskToStatus(KalturaEntryVendorTask $task, $status)
    {
        KBatchBase::impersonate($task->partnerId);

        $updateTask = new KalturaEntryVendorTask();
        $updateTask->status = $status;
        try {
            $this->reachPlugin->entryVendorTask->update($task->id, $updateTask);
        } catch (Exception $e)
        {
            KalturaLog::err("Unable to move task ID {$task->id} to PROCESSING status");
        }

        KBatchBase::unimpersonate();
    }

    public function endTaskOnError ($task)
    {
        $this->moveTaskToStatus($task, KalturaEntryVendorTaskStatus::ERROR);
    }

    public function endTaskSuccess($task)
    {
        $this->moveTaskToStatus($task, KalturaEntryVendorTaskStatus::READY);
    }
}