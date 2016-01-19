<?php

class bdGoogleDrive_DevHelper_Config extends DevHelper_Config_Base
{
    protected $_dataClasses = array();
    protected $_dataPatches = array(
        'xf_attachment_data' => array(
            'bdgoogledrive_data' => array('name' => 'bdgoogledrive_data', 'type' => 'serialized'),
        ),
    );
    protected $_exportPath = '/Users/sondh/XenForo/_idoc.vn/bdGoogleDrive';
    protected $_exportIncludes = array();
    protected $_exportExcludes = array(
        'library/bdGoogleDrive/Lib/google-api-php-client/examples',
        'library/bdGoogleDrive/Lib/google-api-php-client/tests',
    );
    protected $_exportAddOns = array();
    protected $_exportStyles = array();
    protected $_options = array();

    /**
     * Return false to trigger the upgrade!
     **/
    protected function _upgrade()
    {
        return true; // remove this line to trigger update

        /*
        $this->addDataClass(
            'name_here',
            array( // fields
                'field_here' => array(
                    'type' => 'type_here',
                    // 'length' => 'length_here',
                    // 'required' => true,
                    // 'allowedValues' => array('value_1', 'value_2'),
                    // 'default' => 0,
                    // 'autoIncrement' => true,
                ),
                // other fields go here
            ),
            array('primary_key_1', 'primary_key_2'), // or 'primary_key', both are okie
            array( // indeces
                array(
                    'fields' => array('field_1', 'field_2'),
                    'type' => 'NORMAL', // UNIQUE or FULLTEXT
                ),
            ),
        );
        */
    }
}