<?php

class bdGoogleDrive_Helper_Mover
{
    public static function move(
        array $attachmentData,
        $folderId,
        XenForo_Model_Attachment $attachmentModel
    ) {
        if (!empty($folderId)) {
            if (!empty($attachmentData['bdgoogledrive_data'])) {
                // file already on Google Drive, does nothing
                return true;
            }
        } else {
            if (empty($attachmentData['bdgoogledrive_data'])) {
                // file already on local file system, does nothing
                return true;
            }
        }

        /** @var bdGoogleDrive_XenForo_Model_Attachment $attachmentModel */
        $attachmentModel->bdAttachmentStore_useTempFile(true);
        $dataFilePath = $attachmentModel->getAttachmentDataFilePath($attachmentData);
        $attachmentModel->bdAttachmentStore_useTempFile(false);

        $thumbnailFilePath = false;
        if ($attachmentData['thumbnail_width'] > 0) {
            $thumbnailFilePath = $attachmentModel->getAttachmentThumbnailFilePath($attachmentData);
            if (!file_exists($thumbnailFilePath) || !is_readable($thumbnailFilePath)) {
                $thumbnailFilePath = bdGoogleDrive_ShippableHelper_TempFile::download(
                    $attachmentModel->getAttachmentThumbnailUrl($attachmentData));
                if ($thumbnailFilePath === false) {
                    // unable to download
                    return false;
                }
            }
        }

        $attachmentDataNew = $attachmentData;
        unset($attachmentDataNew['data_id']);
        unset($attachmentDataNew['bdgoogledrive_data']);
        $newDw = XenForo_DataWriter::create('XenForo_DataWriter_AttachmentData');
        $newDw->bulkSet($attachmentDataNew, array('ignoreInvalidFields' => true));
        $newDw->setExtraData(XenForo_DataWriter_AttachmentData::DATA_TEMP_FILE, $dataFilePath);
        if ($thumbnailFilePath) {
            $newDw->setExtraData(XenForo_DataWriter_AttachmentData::DATA_TEMP_THUMB_FILE, $thumbnailFilePath);
        }

        XenForo_Db::beginTransaction();

        try {
            $newDw->save();

            // skip deleting old data file
            // this will be done by the hourly cron job
            // $oldDw->delete();

            $db = XenForo_Application::getDb();
            $db->update('xf_attachment_data', array('attach_count' => 0),
                array('data_id = ?' => $attachmentData['data_id']));
            $db->update('xf_attachment', array('data_id' => $newDw->get('data_id')),
                array('data_id = ?' => $attachmentData['data_id']));
        } catch (XenForo_Exception $e) {
            // ignore
            XenForo_Db::rollback();

            return false;
        }

        XenForo_Db::commit();

        return true;
    }

}
