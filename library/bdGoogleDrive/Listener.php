<?php

XenForo_Model_Attachment::$dataColumns .= ', bdgoogledrive_data';

class bdGoogleDrive_Listener
{
    public static function file_health_check(XenForo_ControllerAdmin_Abstract $controller, array &$hashes)
    {
        $hashes += bdGoogleDrive_FileSums::getHashes();
    }

    public static function load_class_XenForo_DataWriter_AttachmentData($class, array &$extend)
    {
        if ($class === 'XenForo_DataWriter_AttachmentData') {
            $extend[] = 'bdGoogleDrive_XenForo_DataWriter_AttachmentData';
        }
    }

    public static function load_class_XenForo_Model_Attachment($class, array &$extend)
    {
        if ($class === 'XenForo_Model_Attachment') {
            $extend[] = 'bdGoogleDrive_XenForo_Model_Attachment';
        }
    }

    public static function load_class_XenForo_ViewPublic_Attachment_View($class, array &$extend)
    {
        if ($class === 'XenForo_ViewPublic_Attachment_View') {
            $extend[] = 'bdGoogleDrive_XenForo_ViewPublic_Attachment_View';
        }
    }
}