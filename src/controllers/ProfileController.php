<?php 

require_once 'AppController.php';
require_once __DIR__ . '/../repository/UserRepository.php';
require_once __DIR__ . '/../repository/ReelsRepository.php';

class ProfileController extends AppController {

    private static $instance = null;
    private $userRepository; 

    public function __construct()
    {
        $this->userRepository = UserRepository::getInstance();
    }

    public static function getInstance(): ProfileController {
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

        $userId = $_SESSION['user_id'];
        $username = $_SESSION['username'] ?? null;

        $pfpPath = $this->userRepository->getUserProfilePicture($userId);

        $reelsRepository = ReelsRepository::getInstance();
        $reels = $reelsRepository->getReelsByUserId($userId);
        $reelsCount = count($reels);

        $groupedReels = [];
        foreach ($reels as $reel) {
            $dateKey = date("F Y", strtotime($reel['created_at']));
            $groupedReels[$dateKey][] = $reel;
        }

        $placesCount = count($reelsRepository->getVisitedCountriesByUserId($userId));

        return $this->render('profile', [
            'username' => $username,
            'pfpPath' => $pfpPath,
            'reels' => $groupedReels, 
            'reelsCount' => $reelsCount,
            'placesCount' => $placesCount
        ]);

    }

    public function uploadProfilePicture() {
        ob_start(); 

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        try {
            if (!isset($_SESSION['user_id'])) {
                
                throw new Exception('Nie jesteś zalogowany.');
            }

            $userId = $_SESSION['user_id'];

            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/media/profile_pictures/';
            $webDir = '/media/profile_pictures/';

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $fileName = 'pfp_user_' . $userId . '.png';
            $targetFilePath = $uploadDir . $fileName;
            $dbPath = $webDir . $fileName;

            if (!isset($_FILES['pfp']) || $_FILES['pfp']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Błąd przesyłania pliku.');
            }

            if (move_uploaded_file($_FILES['pfp']['tmp_name'], $targetFilePath)) {
                ob_clean(); 

                $this->userRepository->updateUserProfilePicture($userId, $dbPath);
                
                echo json_encode([
                    'status' => 'success', 
                    'url' => $webDir . $fileName
                ]);
                exit; 
            } else {
                throw new Exception('Nie udało się zapisać pliku na dysku.');
            }

        } catch (Exception $e) {
            ob_clean(); 
            http_response_code(500); 
            echo json_encode([
                'status' => 'error', 
                'message' => $e->getMessage()
            ]);
            exit;
        }
    }
}
?>