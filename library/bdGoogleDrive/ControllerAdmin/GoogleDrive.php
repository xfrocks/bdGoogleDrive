<?php

class bdGoogleDrive_ControllerAdmin_GoogleDrive extends XenForo_ControllerAdmin_Abstract
{
    public function actionAccountsAdd()
    {
        $this->assertAdminPermission('option');
        $redirectUri = XenForo_Link::buildAdminLink('canonical:google-drive/accounts/add');

        $code = $this->_input->filterSingle('code', XenForo_Input::STRING);
        if ($code === '') {
            $authUrl = bdGoogleDrive_Helper_Api::createAuthUrl($redirectUri);
            return $this->responseRedirect(
                XenForo_ControllerResponse_Redirect::SUCCESS,
                $authUrl
            );
        }

        $account = bdGoogleDrive_Helper_Api::fetchAccount($redirectUri, $code);
        $userId = $account['parsedUserId'];
        $userInfo = bdGoogleDrive_Helper_Api::fetchUserInfo($account['accessToken']);

        $folders = bdGoogleDrive_Helper_Api::fetchSubFolders($account['accessToken'], 'root');
        if (empty($folders)) {
            bdGoogleDrive_Helper_Api::makeFolder($account['accessToken'], 'root',
                XenForo_Application::getOptions()->get('boardUrl'));
            $folders = bdGoogleDrive_Helper_Api::fetchSubFolders($account['accessToken'], 'root');
        }

        $accounts = bdGoogleDrive_Option::get('accounts');
        if (empty($accounts)) {
            $accounts = array('default' => array('userId' => $userId));
        }
        $accounts[$userId] = array(
            'accessToken' => $account['accessToken'],
            'userInfo' => $userInfo,
            'folders' => $folders,
        );
        bdGoogleDrive_Option::set('accounts', $accounts);

        return $this->responseRedirect(
            XenForo_ControllerResponse_Redirect::RESOURCE_UPDATED,
            XenForo_Link::buildAdminLink('options/list/bdGoogleDrive')
        );
    }

    public function actionAccountsDelete()
    {
        $this->assertAdminPermission('option');
        $account = $this->_getAccountOrError();

        if ($this->isConfirmedPost()) {
            $accounts = bdGoogleDrive_Option::get('accounts');
            unset($accounts[$account['userInfo']['id']]);
            bdGoogleDrive_Option::set('accounts', $accounts);

            return $this->responseRedirect(
                XenForo_ControllerResponse_Redirect::RESOURCE_UPDATED,
                XenForo_Link::buildAdminLink('options/list/bdGoogleDrive')
            );
        } else {
            $viewParams = array(
                'account' => $account,
            );

            return $this->responseView(
                'bdGoogleDrive_ViewAdmin_Accounts_Delete',
                'bdgoogledrive_accounts_delete',
                $viewParams
            );
        }
    }

    protected function _getAccountOrError()
    {
        $userId = $this->_input->filterSingle('userId', XenForo_Input::STRING);
        if (empty($userId)) {
            return $this->responseNoPermission();
        }

        $accounts = bdGoogleDrive_Option::get('accounts');
        if (!isset($accounts[$userId])) {
            return $this->responseNoPermission();
        }

        $account = $accounts[$userId];
        if (empty($account['accessToken'])
            || empty($account['userInfo'])
        ) {
            return $this->responseNoPermission();
        }

        return $account;
    }
}