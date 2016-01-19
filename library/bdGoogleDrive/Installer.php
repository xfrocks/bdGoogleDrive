<?php

class bdGoogleDrive_Installer
{
    /* Start auto-generated lines of code. Change made will be overwriten... */

    protected static $_tables = array();
    protected static $_patches = array(
        array(
            'table' => 'xf_attachment_data',
            'tableCheckQuery' => 'SHOW TABLES LIKE \'xf_attachment_data\'',
            'field' => 'bdgoogledrive_data',
            'checkQuery' => 'SHOW COLUMNS FROM `xf_attachment_data` LIKE \'bdgoogledrive_data\'',
            'addQuery' => 'ALTER TABLE `xf_attachment_data` ADD COLUMN `bdgoogledrive_data` MEDIUMBLOB',
            'dropQuery' => 'ALTER TABLE `xf_attachment_data` DROP COLUMN `bdgoogledrive_data`',
        ),
    );

    public static function install($existingAddOn, $addOnData)
    {
        $db = XenForo_Application::get('db');

        foreach (self::$_tables as $table) {
            $db->query($table['createQuery']);
        }

        foreach (self::$_patches as $patch) {
            $tableExisted = $db->fetchOne($patch['tableCheckQuery']);
            if (empty($tableExisted)) {
                continue;
            }

            $existed = $db->fetchOne($patch['checkQuery']);
            if (empty($existed)) {
                $db->query($patch['addQuery']);
            }
        }

        self::installCustomized($existingAddOn, $addOnData);
    }

    public static function uninstall()
    {
        $db = XenForo_Application::get('db');

        foreach (self::$_patches as $patch) {
            $tableExisted = $db->fetchOne($patch['tableCheckQuery']);
            if (empty($tableExisted)) {
                continue;
            }

            $existed = $db->fetchOne($patch['checkQuery']);
            if (!empty($existed)) {
                $db->query($patch['dropQuery']);
            }
        }

        foreach (self::$_tables as $table) {
            $db->query($table['dropQuery']);
        }

        self::uninstallCustomized();
    }

    /* End auto-generated lines of code. Feel free to make changes below */

    public static function installCustomized($existingAddOn, $addOnData)
    {
        // customized install script goes here
    }

    public static function uninstallCustomized()
    {
        // customized uninstall script goes here
    }

}