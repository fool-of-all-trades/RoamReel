<?php

require_once 'AppController.php';
require_once __DIR__ . '/../repository/AdminRepository.php';

class AdminController extends AppController {

    private static $instance = null;
    private $adminRepo;

    public function __construct()
    {
        $this->adminRepo = AdminRepository::getInstance();
    }

    public static function getInstance(): AdminController {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function checkAuth() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Sprawdzenie czy user ma rolę 1
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 1) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);

            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/login");
            exit();
        }
    }

    public function adminPanel() {
        $this->checkAuth();

        $stats = $this->adminRepo->getStats();
        $users = $this->adminRepo->getAllUsers();

        $this->render('adminPanel', [
            'stats' => $stats,
            'users' => $users
        ]);
    }

    public function deleteUser() {
        $this->checkAuth();

        if (isset($_POST['user_id'])) {
            $this->adminRepo->deleteUser($_POST['user_id']);
        }
        

        $url = "http://$_SERVER[HTTP_HOST]";
        header("Location: {$url}/adminPanel");
        exit;
    }

    public function updateUser() {
        $this->checkAuth();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id']) && isset($_POST['username'])) {
            $id = (int)$_POST['user_id'];
            $username = trim($_POST['username']);

            if (!empty($username)) {
                $this->adminRepo->updateUsername($id, $username);
            }
        }
        
        $url = "http://$_SERVER[HTTP_HOST]";
        header("Location: {$url}/adminPanel");
        exit;
    }

    public function statsApi() {
        $this->checkAuth();

        $generalStats = $this->adminRepo->getStats();
        
        $countryStats = $this->adminRepo->getCountryStats();
        
        $usersList = $this->adminRepo->getAllUsers(); 

        header('Content-Type: application/json');
        echo json_encode([
            'general' => $generalStats,
            'chart' => $countryStats,
            'users_list' => $usersList
        ]);
        exit();
    }
}