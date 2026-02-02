<?php 

require_once 'AppController.php';

class ReelController extends AppController {

    private static $instance = null;

    public static function getInstance(): ReelController {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function index() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);

            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/login");
            exit();
        }

        return $this->render('reel');
    }

    public function editReel($id) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);

            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/login");
            exit();
        }

        if (!$id) {
            http_response_code(400);
            header('Location: /profile');
            exit;
        }

        $reelsRepo = ReelsRepository::getInstance();
        $reel = $reelsRepo->getReelById($id);

        if (!$reel) {
            header('Location: /profile'); 
            exit;
        }

        $mapRepo = new CountryRepository(); 
        $countries = $mapRepo->getCountries(); 

        if ($reel['user_id'] !== $_SESSION['user_id']) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            header('Location: /profile');
            exit;
        }

        $this->render('editor', [
            'reel' => $reel,
            'countries' => $countries 
        ]);
    }

    public function deleteReel() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            header('Location: /login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
            http_response_code(405);
            $id = (int)$_POST['id'];
            
            $reelsRepo = ReelsRepository::getInstance();
            $reelsRepo->deleteReel($id);
            http_response_code(200);
        }
        else {
            http_response_code(400);
        }
        
        header('Location: /profile');
        exit;
    }
    
    public function updateReel() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            header('Location: /login');
            exit;
        }

        $id = $_POST['id'] ?? null;
        $country = $_POST['country'] ?? null;
        $date = $_POST['date'] ?? null;
        $reelsRepo = ReelsRepository::getInstance();

        if ($id && $country && $date) {
            $reelsRepo->updateReel(
                (int)$id, 
                $country, 
                $date, 
                $_SESSION['user_id']
            );
            http_response_code(200);
        }
        else {
            http_response_code(400);
        }
        
        header('Location: /profile');
        exit;
    }
}