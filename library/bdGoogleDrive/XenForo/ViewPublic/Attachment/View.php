<?php

class bdGoogleDrive_XenForo_ViewPublic_Attachment_View extends XFCP_bdGoogleDrive_XenForo_ViewPublic_Attachment_View
{
    public function renderRaw()
    {
        $attachment = $this->_params['attachment'];

        if (!empty($attachment['bdgoogledrive_data'])) {
            $googleDriveData = @unserialize($attachment['bdgoogledrive_data']);
            if (!empty($googleDriveData['full'])) {
                /** @var bdGoogleDrive_Model_File $fileModel */
                $fileModel = XenForo_Model::create('bdGoogleDrive_Model_File');
                $url = $fileModel->getTemporaryFileUrl($googleDriveData['full']);

                $this->_response->setHttpResponseCode(302);
                $this->_response->setHeader('Location', $url);
                return '';
            }
        }

        return parent::renderRaw();
    }

}