<?php

class bdGoogleDrive_Option
{
    protected static $_defaultFolderId = null;
    protected static $_defaultAccessToken = null;
    protected static $_thumbnailFolderId = null;
    protected static $_thumbnailAccessToken = null;

    public static function set($key, $value)
    {
        if ($key === 'accounts') {
            self::$_defaultAccessToken = null;
            self::$_defaultFolderId = null;
            self::$_thumbnailAccessToken = null;
            self::$_thumbnailFolderId = null;
        }

        $optionKey = 'bdGoogleDrive_' . $key;
        XenForo_Application::getOptions()->set($optionKey, false, $value);

        /** @var XenForo_Model_Option $optionModel */
        $optionModel = XenForo_Model::create('XenForo_Model_Option');
        return $optionModel->updateOption($optionKey, $value);
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

    public static function getUserAccessToken($userId)
    {
        $accounts = self::get('accounts');
        if (isset($accounts[$userId])
            && !empty($accounts[$userId]['accessToken'])
        ) {
            return $accounts[$userId]['accessToken'];
        }

        return '';
    }

    public static function getDefaultAccessToken()
    {
        if (self::$_defaultAccessToken === null) {
            self::$_defaultAccessToken = '';
            $defaultFolderId = self::getDefaultFolderId();

            $accounts = self::get('accounts');
            foreach ($accounts as $account) {
                if (empty($account['accessToken'])) {
                    continue;
                }

                if (empty($defaultFolderId)
                    || isset($account['folders'][$defaultFolderId])
                ) {
                    self::$_defaultAccessToken = $account['accessToken'];
                }
            }
        }

        return self::$_defaultAccessToken;
    }

    public static function getDefaultFolderId()
    {
        if (self::$_defaultFolderId === null) {
            self::$_defaultFolderId = '';

            $accounts = self::get('accounts');
            if (isset($accounts['default'])
                && !empty($accounts['default']['folderId'])
            ) {
                self::$_defaultFolderId = $accounts['default']['folderId'];
            }
        }

        return self::$_defaultFolderId;
    }

    public static function getThumbnailAccessToken()
    {
        if (self::$_thumbnailAccessToken === null) {
            self::$_thumbnailAccessToken = '';
            $thumbnailFolderId = self::getDefaultFolderId();

            $accounts = self::get('accounts');
            foreach ($accounts as $account) {
                if (empty($account['accessToken'])) {
                    continue;
                }

                if (isset($account['folders'][$thumbnailFolderId])) {
                    self::$_thumbnailAccessToken = $account['accessToken'];
                }
            }
        }

        return self::$_thumbnailAccessToken;
    }

    public static function getThumbnailFolderId()
    {
        if (self::$_thumbnailFolderId === null) {
            self::$_thumbnailFolderId = '';

            $accounts = self::get('accounts');
            if (isset($accounts['thumbnail'])
                && !empty($accounts['thumbnail']['folderId'])
            ) {
                self::$_defaultFolderId = $accounts['thumbnail']['folderId'];
            }

            if (self::$_thumbnailFolderId === '') {
                self::$_thumbnailFolderId = self::getDefaultFolderId();
            }
        }

        return self::$_thumbnailFolderId;
    }

    public static function renderAccounts(XenForo_View $view, $fieldPrefix, array $preparedOption, $canEdit)
    {
        $accounts = $preparedOption['option_value'];
        $folders = array();

        foreach ($accounts as &$accountRef) {
            if (empty($accountRef['accessToken'])) {
                continue;
            }

            try {
                $accountRef['about'] = bdGoogleDrive_Helper_Api::fetchAbout($accountRef['accessToken']);
            } catch (Exception $e) {
                XenForo_Error::logException($e, false);
            }

            if (!empty($accountRef['folders'])) {
                foreach ($accountRef['folders'] as $folderId => &$accountFolderRef) {
                    $folders[$accountRef['userInfo']['name']][$folderId] = $accountFolderRef['title'];
                }
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
            'folders' => $folders,
        ));
    }
}