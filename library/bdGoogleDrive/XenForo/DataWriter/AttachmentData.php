<?php

class bdGoogleDrive_XenForo_DataWriter_AttachmentData extends XFCP_bdGoogleDrive_XenForo_DataWriter_AttachmentData
{
    public function bdGoogleDrive_saveFiles()
    {
        $savedFiles = array();
        if (!$this->isInsert()) {
            $savedFiles = $this->get('bdgoogledrive_data');
            if (!is_array($savedFiles)) {
                $savedFiles = @unserialize($savedFiles);
            }
            if (!is_array($savedFiles)) {
                $savedFiles = array();
            }
        }

        $accessToken = bdGoogleDrive_Option::getDefaultAccessToken();
        $folderId = bdGoogleDrive_Option::getDefaultFolderId();
        if (empty($accessToken)
            || empty($folderId)
        ) {
            return $savedFiles;
        }

        $tempFile = $this->getExtraData(self::DATA_TEMP_FILE);
        $fileData = $this->getExtraData(self::DATA_FILE_DATA);
        if ($tempFile) {
            $savedFiles['full'] = $this->_bdGoogleDrive_getFileModel()->saveFilePath($tempFile, $folderId,
                $this->get('filename'), $this->get('file_hash'), $accessToken);
        } elseif ($fileData) {
            $savedFiles['full'] = $this->_bdGoogleDrive_getFileModel()->saveFileData($fileData, $folderId,
                $this->get('filename'), $this->get('file_hash'), $accessToken);
        }
        if (empty($savedFiles['full'])) {
            return $savedFiles;
        }

        $tempThumbFile = $this->getExtraData(self::DATA_TEMP_THUMB_FILE);
        $thumbData = $this->getExtraData(self::DATA_THUMB_DATA);
        $fileNameExt = XenForo_Helper_File::getFileExtension($this->get('filename'));
        $fileNameWithoutExt = strlen($fileNameExt) > 0
            ? substr($this->get('filename'), 0, -1 * strlen($fileNameExt) - 1)
            : $this->get('filename');
        $thumbFileName = $fileNameWithoutExt . '_thumb.jpg';

        $thumbnailAccessToken = bdGoogleDrive_Option::getThumbnailAccessToken();
        $thumbnailFolderId = bdGoogleDrive_Option::getThumbnailFolderId();
        if ($tempThumbFile) {
            if (file_exists($tempThumbFile)
                && is_readable($tempThumbFile)
            ) {
                $thumbFileHash = md5_file($tempThumbFile);
                $savedFiles['thumbnail'] = $this->_bdGoogleDrive_getFileModel()->saveFilePath($tempThumbFile,
                    $thumbnailFolderId, $thumbFileName, $thumbFileHash, $thumbnailAccessToken);
            }
        } elseif ($thumbData) {
            $thumbFileHash = md5($thumbData);
            $savedFiles['thumbnail'] = $this->_bdGoogleDrive_getFileModel()->saveFileData($fileData, $thumbnailFolderId,
                $thumbFileName, $thumbFileHash, $thumbnailAccessToken);
        }

        return $savedFiles;
    }

    protected function _getFields()
    {
        $fields = parent::_getFields();

        $fields['xf_attachment_data']['bdgoogledrive_data'] = array('type' => XenForo_DataWriter::TYPE_SERIALIZED);

        return $fields;
    }

    protected function _preSave()
    {
        parent::_preSave();

        $attachmentData = $this->getMergedData();
        if (!$this->_bdGoogleDrive_getFileModel()->isIgnored($attachmentData)) {
            $savedFiles = $this->bdGoogleDrive_saveFiles();
            if (!empty($savedFiles) || $this->get('bdgoogledrive_data')) {
                $this->set('bdgoogledrive_data', $savedFiles);
            }
        }
    }

    protected function _postDelete()
    {
        $mergedData = $this->getMergedData();

        if (!empty($mergedData['bdgoogledrive_data'])) {
            $fileModel = $this->_bdGoogleDrive_getFileModel();
            $data = @unserialize($mergedData['bdgoogledrive_data']);

            if (isset($data['full'])) {
                $fileModel->deleteFile($data['full']);
            }

            if (isset($data['thumbnail'])) {
                $fileModel->deleteFile($data['thumbnail']);
            }
        }

        parent::_postDelete();
    }

    protected function _writeAttachmentFile($tempFile, array $data, $thumbnail = false)
    {
        if (empty($data['bdgoogledrive_data'])
            || bdGoogleDrive_Option::get('keepLocalCopy')
        ) {
            // let the default implementation run
            return parent::_writeAttachmentFile($tempFile, $data, $thumbnail);
        } else {
            return true;
        }
    }

    protected function _writeAttachmentFileData($fileData, array $data, $thumbnail = false)
    {
        if (empty($data['bdgoogledrive_data'])
            || bdGoogleDrive_Option::get('keepLocalCopy')
        ) {
            // let the default implementation run
            return parent::_writeAttachmentFileData($fileData, $data, $thumbnail);
        } else {
            return true;
        }
    }

    /**
     * @return bdGoogleDrive_Model_File
     */
    protected function _bdGoogleDrive_getFileModel()
    {
        return $this->getModelFromCache('bdGoogleDrive_Model_File');
    }
}