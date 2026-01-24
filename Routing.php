<?php

require_once 'src/controllers/SecurityController.php';
require_once 'src/controllers/AdminController.php';
require_once 'src/controllers/MapDashboardController.php';
require_once 'src/controllers/CreatorController.php';
require_once 'src/controllers/ProfileController.php';
require_once 'src/controllers/ReelController.php';

class Routing {

    //sesja uzytkownika
    public static $routing = [
        '' => [
            'controller' => 'MapDashboardController',
            'action' => 'index'
        ],
        'login' => [
            'controller' => 'SecurityController',
            'action' => 'login'
        ],
        'register' => [
            'controller' => 'SecurityController',
            'action' => 'register'
        ],
        'logout' => [
            'controller' => 'SecurityController',
            'action' => 'logout'
        ],
        'dashboard' => [
            'controller' => 'MapDashboardController',
            'action' => 'index'
        ],
        'creator' => [
            'controller' => 'CreatorController',
            'action' => 'index'
        ],
        'profile' => [
            'controller' => 'ProfileController',
            'action' => 'index'
        ],
        'reel' => [
            'controller' => 'ReelController',
            'action' => 'index'
        ],
        'generateReel' => [
            'controller' => 'CreatorController',
            'action' => 'generateReel'
        ],
        'upload_pfp' => [
            'controller' => 'ProfileController',
            'action' => 'uploadProfilePicture'
        ],
        'getCountryReels' => [
            'controller' => 'MapDashboardController',
            'action' => 'getCountryReels'
        ],
        'getVisitedCountries' => [
            'controller' => 'MapDashboardController',
            'action' => 'getVisitedCountries'
        ],
        'createShareLink' => [
            'controller' => 'ReelController',
            'action' => 'createShareLink'
        ],
        'share' => [
            'controller' => 'ReelController',
            'action' => 'share'
        ],
        'editReel' => [
            'controller' => 'ReelController',
            'action' => 'editReel'
        ],
        'deleteReel' => [
            'controller' => 'ReelController',
            'action' => 'deleteReel'
        ],
        'updateReel' => [
            'controller' => 'ReelController',
            'action' => 'updateReel'
        ],


        //Admin
        'adminPanel' => [
            'controller' => 'AdminController',
            'action' => 'adminPanel'
        ],
        'deleteUser' => [
            'controller' => 'AdminController',
            'action' => 'deleteUser'
        ],
        'updateUser' => [
            'controller' => 'AdminController',
            'action' => 'updateUser'
        ],
    ];

    public static function run(string $path) {

        $path = trim($path, '/');
        $id = 0; 
        $parts = explode('/', $path);

        $route = $parts[0];

        if (isset($parts[1])) {
            $id = $parts[1];
        }

        if(!array_key_exists($route, self::$routing)) {

            include 'public/views/404.html';

        } else {

            $controller = self::$routing[$route]['controller'];
            $action = self::$routing[$route]['action'];

            $controllerObcjet = $controller::getInstance();
            $controllerObcjet->$action($id);
        }
    }
}
