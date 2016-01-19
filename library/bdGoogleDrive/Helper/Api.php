<?php

class bdGoogleDrive_Helper_Api
{
    public static function createAuthUrl($redirectUri)
    {
        $client = self::_newGoogleClient();
        $client->setRedirectUri($redirectUri);
        return $client->createAuthUrl();
    }

    public static function fetchAccount($redirectUri, $code)
    {
        $client = self::_newGoogleClient();
        $client->setRedirectUri($redirectUri);

        if ($client->authenticate($code)) {
            $accessToken = $client->getAccessToken();

            if (strpos($accessToken, '"refresh_token"') === false) {
                throw new XenForo_Exception(sprintf('Unable to find `refresh_token` from access token ($accessToken=%s)',
                    $accessToken));
            }
            if (strpos($accessToken, '"id_token"') === false) {
                throw new XenForo_Exception(sprintf('Unable to find `id_token` from access token ($accessToken=%s)',
                    $accessToken));
            }

            $userId = self::_parseUserId($client, $accessToken);

            self::_log('access token granted ($accessToken=%s, $userId=%s)', $accessToken, $userId);

            $account = array(
                'accessToken' => $accessToken,
                'parsedUserId' => $userId,
            );

            return $account;
        } else {
            throw new XenForo_Exception(sprintf('Unable to get access token from Google (redirectUri=%s, code=%s)',
                $redirectUri, $code));
        }
    }

    public static function fetchUserInfo($accessToken)
    {
        $client = self::_newGoogleClient();
        self::_setAccessToken($client, $accessToken);
        $oauth2Service = new Google_Service_Oauth2($client);
        $userInfo = $oauth2Service->userinfo_v2_me->get();

        if (is_object($userInfo)
            && $userInfo instanceof Google_Service_Oauth2_Userinfoplus
        ) {
            $userInfoArray = array();
            $userInfoArray['id'] = $userInfo->getId();
            $userInfoArray['link'] = $userInfo->getLink();
            $userInfoArray['name'] = $userInfo->getName();
            $userInfoArray['picture'] = $userInfo->getPicture();

            self::_log('user info fetched ($accessToken=%s, $userInfo=%s', $accessToken, $userInfoArray);

            return $userInfoArray;
        } else {
            throw new XenForo_Exception(sprintf('Unable to get user info from Google (accessToken=%s)', $accessToken));
        }
    }

    public static function fetchAbout($accessToken)
    {
        $client = self::_newGoogleClient();
        self::_setAccessToken($client, $accessToken);
        $driveService = new Google_Service_Drive($client);
        $about = $driveService->about->get();

        if (is_object($about)
            && $about instanceof Google_Service_Drive_About
        ) {
            $aboutArray = array();
            $aboutArray['quotaType'] = $about->getQuotaType();
            $aboutArray['quotaBytesTotal'] = $about->getQuotaBytesTotal();
            $aboutArray['quotaBytesUsedAggregate'] = $about->getQuotaBytesUsedAggregate();

            $aboutArray['parsed']['quotaTotal'] = bdGoogleDrive_ShippableHelper_String::formatBytes($aboutArray['quotaBytesTotal']);
            $aboutArray['parsed']['quotaFree'] = bdGoogleDrive_ShippableHelper_String::formatBytes(
                $aboutArray['quotaBytesTotal'] - $aboutArray['quotaBytesUsedAggregate']);
            $aboutArray['parsed']['quotaUsedPercent']
                = $aboutArray['quotaBytesUsedAggregate'] / $aboutArray['quotaBytesTotal'] * 100;

            self::_log('about fetched ($accessToken=%s, $about=%s', $accessToken, $aboutArray);

            return $aboutArray;
        } else {
            throw new XenForo_Exception(sprintf('Unable to get about from Google (accessToken=%s)', $accessToken));
        }
    }

    public static function fetchSubFolders($accessToken, $folderId)
    {
        $client = self::_newGoogleClient();
        self::_setAccessToken($client, $accessToken);
        $driveService = new Google_Service_Drive($client);

        $subFolders = array();
        $pageToken = null;

        do {
            /** @var Google_Service_Drive_FileList $response */
            $response = $driveService->files->listFiles(array(
                'q' => sprintf('mimeType=\'application/vnd.google-apps.folder\' and \'%s\' in parents', $folderId),
                'pageToken' => $pageToken,
            ));

            /** @var Google_Service_Drive_DriveFile $file */
            foreach ($response->getItems() as $file) {
                $subFolders[$file->getId()] = array(
                    'id' => $file->getId(),
                    'title' => $file->getTitle(),
                    'link' => $file->getAlternateLink(),
                );
            }

            $pageToken = $response->getNextPageToken();
        } while ($pageToken != null);

        return $subFolders;
    }

    public static function makeFolder($accessToken, $parentId, $folderTitle)
    {
        return self::uploadFile($accessToken, $folderTitle, '', array(
            'mimeType' => 'application/vnd.google-apps.folder',
            'parentId' => $parentId,
            'uploadType' => '',
        ));
    }

    public static function uploadFile($accessToken, $fileName, $fileData, array $options)
    {
        $client = self::_newGoogleClient();
        self::_setAccessToken($client, $accessToken);

        $mimeType = 'application/octet-stream';
        if (isset($options['mimeType'])) {
            $mimeType = $options['mimeType'];
        }

        $file = new Google_Service_Drive_DriveFile();
        $file->setTitle($fileName);
        if (!empty($options['description'])) {
            $file->setDescription($options['description']);
        }
        $file->setMimeType($mimeType);

        if (!empty($options['parentId'])) {
            $parentId = $options['parentId'];
            $parent = new Google_Service_Drive_ParentReference();
            $parent->setId($parentId);
            $file->setParents(array($parent));
        }

        $uploadType = 'multipart';
        if (isset($options['uploadType'])) {
            $uploadType = $options['uploadType'];
        }

        $insertParams = array();
        if (!empty($uploadType)) {
            $insertParams['uploadType'] = $uploadType;
        }
        if (!empty($fileData)) {
            $insertParams['data'] = $fileData;
        }

        $driveService = new Google_Service_Drive($client);
        $createdFile = $driveService->files->insert($file, $insertParams);

        if (is_object($createdFile)
            && $createdFile instanceof Google_Service_Drive_DriveFile
        ) {
            $fileArray = array();
            $fileArray['id'] = $createdFile->getId();
            $fileArray['title'] = $createdFile->getTitle();
            $fileArray['link'] = $createdFile->getWebContentLink();

            return $fileArray;
        } else {
            throw new XenForo_Exception(sprintf('Unable to get upload file to Google (accessToken=%s)', $accessToken));
        }
    }

    public static function makeFilePublic($accessToken, $fileId)
    {
        $client = self::_newGoogleClient();
        self::_setAccessToken($client, $accessToken);

        $permission = new Google_Service_Drive_Permission();
        $permission->setType('anyone');
        $permission->setRole('reader');

        $driveService = new Google_Service_Drive($client);
        $createdPermission = $driveService->permissions->insert($fileId, $permission);

        if (is_object($createdPermission)
            && $createdPermission instanceof Google_Service_Drive_Permission
        ) {
            return true;
        } else {
            throw new XenForo_Exception(sprintf('Unable to make file public (accessToken=%s, fileId=%s)', $accessToken,
                $fileId));
        }
    }

    protected static function _newGoogleClient()
    {
        self::_autoload();

        static $client = null;

        if ($client === null) {
            $client = new Google_Client();

            $auth = $client->getAuth();
            if (!($auth instanceof Google_Auth_OAuth2)) {
                throw new XenForo_Exception(sprintf('Unexpected Google Auth instance: %s', get_class($auth)));
            }

            $clientConfig = bdGoogleDrive_Option::getGoogleClientConfig();
            $client->setClassConfig('Google_Auth_OAuth2', 'access_type', 'offline');
            $client->setClassConfig('Google_Auth_OAuth2', 'client_id', $clientConfig['clientId']);
            $client->setClassConfig('Google_Auth_OAuth2', 'client_secret', $clientConfig['clientSecret']);
            $client->setClassConfig('Google_Auth_OAuth2', 'include_granted_scopes', 'true');
            $client->setClassConfig('Google_Auth_OAuth2', 'prompt', 'consent');

            $client->addScope(Google_Service_Oauth2::USERINFO_PROFILE);

            if (bdGoogleDrive_Option::get('scopeDrive')) {
                $client->addScope(Google_Service_Drive::DRIVE);
            } else {
                $client->addScope(Google_Service_Drive::DRIVE_FILE);
            }
        }

        return $client;
    }

    protected static function _setAccessToken(Google_Client $client, $accessToken)
    {
        $client->setAccessToken($accessToken);

        if ($client->isAccessTokenExpired()) {
            $refreshToken = $client->getRefreshToken();
            if ($refreshToken) {
                self::_log('attempting to refresh token ($refreshToken=%s)', $refreshToken);

                $client->refreshToken($refreshToken);

                $newAccessToken = $client->getAccessToken();
                $userId = self::_parseUserId($client, $accessToken);

                $accounts = bdGoogleDrive_Option::get('accounts');
                if (isset($accounts[$userId])) {
                    $accounts[$userId]['accessToken'] = $newAccessToken;
                    bdGoogleDrive_Option::set('accounts', $accounts);

                    self::_log('token refreshed (`accounts` option updated, $newAccessToken=%s)', $newAccessToken);
                } else {
                    self::_log('token refreshed (no option updated, $newAccessToken=%s)', $newAccessToken);
                }
            } else {
                self::_log('access token expired but no refresh token available ($accessToken=%s)', $accessToken);
            }
        }
    }

    public static function _parseUserId(Google_Client $client, $accessToken)
    {
        $auth = $client->getAuth();
        if (!($auth instanceof Google_Auth_OAuth2)) {
            throw new XenForo_Exception(sprintf('Unexpected Google Auth instance: %s', get_class($auth)));
        }

        $token = json_decode($accessToken, true);
        if (empty($token)) {
            throw new XenForo_Exception(sprintf('Invalid Google access token: %s', $accessToken));
        }

        if (!isset($token['id_token'])) {
            throw new XenForo_Exception(sprintf('Unexpected Google access token (`id_token` missing): %s',
                $accessToken));
        }

        $segments = explode('.', $token['id_token']);
        if (count($segments) != 3) {
            throw new XenForo_Exception(sprintf('Unexpected number of segments in id_token: %s', $token['id_token']));
        }

        $jsonBody = Google_Utils::urlSafeB64Decode($segments[1]);
        $payload = json_decode($jsonBody, true);
        if (empty($payload)) {
            throw new XenForo_Exception(sprintf('Unable to parse id_token payload: %s', $segments[1]));
        }

        if (empty($payload['sub'])) {
            throw new XenForo_Exception(sprintf('Unexpected id_token payload (`sub` missing): %s',
                $jsonBody));
        }
        return $payload['sub'];
    }

    protected static function _autoload()
    {
        require_once(dirname(dirname(__FILE__)) . '/Lib/google-api-php-client/src/Google/autoload.php');
    }

    private static function _log()
    {
        if (!XenForo_Application::debugMode()) {
            return;
        }

        $args = func_get_args();
        foreach ($args as &$argRef) {
            if (is_array($argRef)) {
                $argRef = json_encode($argRef);
            } elseif (is_object($argRef)) {
                $argRef = strval($argRef);
            }
        }
        $message = call_user_func_array('sprintf', $args);

        XenForo_Helper_File::log(__CLASS__, $message);
    }
}