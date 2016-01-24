<?php

class bdGoogleDrive_Deferred_MoveAttachmentData extends XenForo_Deferred_Abstract
{
    public function execute(array $deferred, array $data, $targetRunTime, &$status)
    {
        $data = array_merge(array(
            'batch' => 50,
            'position' => 0
        ), $data);

        $db = XenForo_Application::getDb();

        /* @var $fileModel bdGoogleDrive_Model_File */
        $fileModel = XenForo_Model::create('bdGoogleDrive_Model_File');
        /* @var $attachmentModel XenForo_Model_Attachment */
        $attachmentModel = $fileModel->getModelFromCache('XenForo_Model_Attachment');

        $s = microtime(true);

        $dataIds = $attachmentModel->getAttachmentDataIdsInRange($data['position'], $data['batch']);
        if (sizeof($dataIds) == 0) {
            return false;
        }

        $attachmentDatas = $db->fetchAll('
            SELECT  *
            FROM  `xf_attachment_data`
            WHERE data_id IN (' . $db->quote($dataIds) . ')
        ');

        $folderId = bdGoogleDrive_Option::getDefaultFolderId();

        foreach ($attachmentDatas AS $attachmentData) {
            $data['position'] = max($data['position'], $attachmentData['data_id']);

            if ($fileModel->isIgnored($attachmentData)) {
                continue;
            }

            if (empty($attachmentData['attach_count'])) {
                continue;
            }

            bdGoogleDrive_Helper_Mover::move($attachmentData, $folderId, $attachmentModel);

            if ($targetRunTime
                && microtime(true) - $s > $targetRunTime
            ) {
                break;
            }
        }

        bdGoogleDrive_ShippableHelper_TempFile::deleteAllCached();

        $actionPhrase = new XenForo_Phrase('rebuilding');
        $typePhrase = new XenForo_Phrase('attachments');
        $status = sprintf('%s... %s (%s)', $actionPhrase, $typePhrase, XenForo_Locale::numberFormat($data['position']));

        return $data;
    }
}