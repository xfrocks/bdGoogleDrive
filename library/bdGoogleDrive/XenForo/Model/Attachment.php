<?php

class bdGoogleDrive_XenForo_Model_Attachment extends XFCP_bdGoogleDrive_XenForo_Model_Attachment
{
    protected static $_bdGoogleStorage_useTempFile = 0;

    public function bdAttachmentStore_useTempFile($enabled)
    {
        // the method name is used to act like [bd] Attachment Store
        // and gain support for third party add-ons
        if ($enabled) {
            self::$_bdGoogleStorage_useTempFile++;
        } else {
            self::$_bdGoogleStorage_useTempFile--;
        }
    }

    public function bdGoogleDrive_getDummyFilePath()
    {
        $path = XenForo_Helper_File::getInternalDataPath() . '/bdGoogleDrive/dummy.data';
        if (!file_exists($path)) {
            $dir = dirname($path);
            if (XenForo_Helper_File::createDirectory($dir)) {
                file_put_contents($path, '');
            }
        }

        return $path;
    }

    public function bdGoogleDrive_getAttachmentDataFilePath(array $data)
    {
        if (!empty($data['bdgoogledrive_data'])) {
            if (self::$_bdGoogleStorage_useTempFile > 0) {
                $googleDriveData = @unserialize($data['bdgoogledrive_data']);
                $fileUrl = $this->_bdGoogleDrive_getFileModel()->getFileUrl($googleDriveData['full']);
                $tempFile = bdGoogleDrive_ShippableHelper_TempFile::download($fileUrl);
                return $tempFile;
            } else {
                return $this->bdGoogleDrive_getDummyFilePath();
            }
        }

        return false;
    }

    public function getAttachmentThumbnailUrl(array $data)
    {
        if (!empty($data['bdgoogledrive_data'])) {
            $googleDriveData = @unserialize($data['bdgoogledrive_data']);

            if (!empty($googleDriveData['thumbnail'])) {
                return $this->_bdGoogleDrive_getFileModel()->getFileUrl($googleDriveData['thumbnail']);
            }
        }

        return parent::getAttachmentThumbnailUrl($data);
    }

    public function getAttachmentDataFilePath(array $data, $internalDataPath = null)
    {
        $ours = $this->bdGoogleDrive_getAttachmentDataFilePath($data);

        if ($ours !== false) {
            return $ours;
        }

        return parent::getAttachmentDataFilePath($data, $internalDataPath);
    }

    /**
     * @return bdGoogleDrive_Model_File
     */
    protected function _bdGoogleDrive_getFileModel()
    {
        return $this->getModelFromCache('bdGoogleDrive_Model_File');
    }

}
