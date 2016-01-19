<?php

class bdGoogleDrive_Option
{
    public static function set($key, $value)
    {
        /** @var XenForo_Model_Option $optionModel */
        $optionModel = XenForo_Model::create('XenForo_Model_Option');
        return $optionModel->updateOption('bdGoogleDrive_' . $key, $value);
    }

    public static function get($key, $subKey = null)
    {
        $options = XenForo_Application::getOptions();

        switch ($key) {
            case 'clientId':
            case 'clientSecret':
                $clientConfig = self::getGoogleClientConfig();
                return $clientConfig[$key];
        }

        return $options->get('bdGoogleDrive_' . $key, $subKey);
    }

    public static function getGoogleClientConfig()
    {
        $options = XenForo_Application::getOptions();

        $clientDefault = $options->get('bdGoogleDrive_clientDefault');
        if ($clientDefault) {
            return array(
                'clientId' => $options->get('googleClientId'),
                'clientSecret' => $options->get('googleClientSecret'),
            );
        } else {
            return array(
                'clientId' => $options->get('bdGoogleDrive_clientId'),
                'clientSecret' => $options->get('bdGoogleDrive_clientSecret'),
            );
        }
    }

    public static function getAccessToken()
    {
        static $accessToken = null;

        if ($accessToken === null) {
            $accessToken = '';
            $defaultFolderId = self::getDefaultFolderId();

            if (!empty($defaultFolderId)) {
                $accounts = self::get('accounts');
                foreach ($accounts as $account) {
                    if (!empty($account['folders'][$defaultFolderId])) {
                        $accessToken = $account['accessToken'];
                    }
                }
            }
        }

        return $accessToken;
    }

    public static function getDefaultFolderId()
    {
        static $defaultFolderId = null;

        if ($defaultFolderId === null) {
            $defaultFolderId = '';

            $accounts = self::get('accounts');
            if (isset($accounts['default'])
                && !empty($accounts['default']['folderId'])
            ) {
                $defaultFolderId = $accounts['default']['folderId'];
            }
        }

        return $defaultFolderId;
    }

    public static function renderAccounts(XenForo_View $view, $fieldPrefix, array $preparedOption, $canEdit)
    {
        $accounts = $preparedOption['option_value'];

        foreach ($accounts as &$accountRef) {
            try {
                $accountRef['about'] = bdGoogleDrive_Helper_Api::fetchAbout($accountRef['accessToken']);
            } catch (Exception $e) {
                XenForo_Error::logException($e, false);
            }
        }

        $editLink = $view->createTemplateObject('option_list_option_editlink', array(
            'preparedOption' => $preparedOption,
            'canEditOptionDefinition' => $canEdit
        ));

        return $view->createTemplateObject('bdgoogledrive_option_accounts', array(
            'fieldPrefix' => $fieldPrefix,
            'listedFieldName' => $fieldPrefix . '_listed[]',
            'preparedOption' => $preparedOption,
            'formatParams' => $preparedOption['formatParams'],
            'editLink' => $editLink,

            'accounts' => $accounts,
        ));
    }
}