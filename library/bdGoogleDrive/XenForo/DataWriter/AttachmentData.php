<?php

class bdGoogleDrive_XenForo_DataWriter_AttachmentData extends XFCP_bdGoogleDrive_XenForo_DataWriter_AttachmentData
{
    public function bdGoogleDrive_saveFiles()
    {
        $fullGoogleFile = null;
        $thumbnailGoogleFile = null;

        $accessToken = bdGoogleDrive_Option::getDefaultAccessToken();
        $folderId = bdGoogleDrive_Option::getDefaultFolderId();
        if (empty($accessToken)
            || empty($folderId)
        ) {
            return array();
        }

        $tempFile = $this->getExtraData(self::DATA_TEMP_FILE);
        $fileData = $this->getExtraData(self::DATA_FILE_DATA);
        if ($tempFile) {
            $fullGoogleFile = $this->_bdGoogleDrive_getFileModel()->saveFilePath($tempFile, $folderId,
                $this->get('filename'), $this->get('file_hash'), $accessToken);
        } elseif ($fileData) {
            $fullGoogleFile = $this->_bdGoogleDrive_getFileModel()->saveFileData($fileData, $folderId,
                $this->get('filename'), $this->get('file_hash'), $accessToken);
        }
        if (empty($fullGoogleFile)) {
            return false;
        }

        $tempThumbFile = $this->getExtraData(self::DATA_TEMP_THUMB_FILE);
        $thumbData = $this->getExtraData(self::DATA_THUMB_DATA);
        $fileNameExt = XenForo_Helper_File::getFileExtension($this->get('filename'));
        $fileNameWithoutExt = strlen($fileNameExt) > 0
            ? substr($this->get('filename'), 0, -1 * strlen($fileNameExt) - 1)
            : $this->get('filename');
        $thumbFileName = $fileNameWithoutExt . '_thumb.jpg';

        if ($tempThumbFile) {
            if (file_exists($tempThumbFile)
                && is_readable($tempThumbFile)
            ) {
                $thumbFileHash = md5_file($tempThumbFile);
                $thumbnailGoogleFile = $this->_bdGoogleDrive_getFileModel()->saveFilePath($tempThumbFile, $folderId,
                    $thumbFileName, $thumbFileHash, $accessToken);
            }
        } elseif ($thumbData) {
            $thumbFileHash = md5($thumbData);
            $thumbnailGoogleFile = $this->_bdGoogleDrive_getFileModel()->saveFileData($fileData, $folderId,
                $thumbFileName, $thumbFileHash, $accessToken);
        }

        $savedFiles = array();
        if (!empty($fullGoogleFile)) {
            $savedFiles['full'] = $fullGoogleFile;
        }
        if (!empty($thumbnailGoogleFile)) {
            $savedFiles['thumbnail'] = $thumbnailGoogleFile;
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

        if ($this->isInsert()) {
            $attachmentData = $this->getMergedData();

            $data = $this->get('bdgoogledrive_data');
            $fileModel = $this->_bdGoogleDrive_getFileModel();

            if (empty($data)
                && !$fileModel->isIgnored($attachmentData)
            ) {
                $savedFiles = $this->bdGoogleDrive_saveFiles();
                if (!empty($savedFiles)) {
                    $this->set('bdgoogledrive_data', $savedFiles);
                }
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
        if (empty($data['bdgoogledrive_data'])) {
            // let the default implementation run
            return parent::_writeAttachmentFile($tempFile, $data, $thumbnail);
        } else {
            return true;
        }
    }

    protected function _writeAttachmentFileData($fileData, array $data, $thumbnail = false)
    {
        if (empty($data['bdgoogledrive_data'])) {
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