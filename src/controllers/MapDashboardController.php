<?php 

require_once 'AppController.php';

class MapDashboardController extends AppController {

    private static $instance = null;

    public static function getInstance(): MapDashboardController {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function index(?int $id) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {

            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/login");
            exit();
        }

        return $this->render('map');
    }

    public function getCountryReels() {
        header('Content-Type: application/json');
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $userId = $_SESSION['user_id'];
        $country = $_POST['country'] ?? '';

        if (empty($country)) {
            http_response_code(400);
            echo json_encode(['error' => 'Country not specified']);
            return;
        }

        $reelsRepository = ReelsRepository::getInstance();
        $reels = $reelsRepository->getReelsByCountryAndUserId($country, $userId);

        echo json_encode(['reels' => $reels]);
    }

}
?>