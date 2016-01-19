<?php

class bdGoogleDrive_Route_PrefixAdmin_GoogleDrive implements XenForo_Route_Interface
{
    public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
    {
        return $router->getRouteMatch('bdGoogleDrive_ControllerAdmin_GoogleDrive', $routePath);
    }
}