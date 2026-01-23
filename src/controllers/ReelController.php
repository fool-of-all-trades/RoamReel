<?php 

require_once 'AppController.php';
require_once __DIR__ . '/../repository/ReelsRepository.php';
require_once __DIR__ . '/../repository/ReelSharesRepository.php';

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

            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/login");
            exit();
        }

        return $this->render('reel');
    }

    // GET /share/<token>  (public)
    public function share($token = null) {
        if (!$token || !preg_match('/^[a-f0-9]{32,64}$/i', $token)) {
            http_response_code(404);
            echo "Invalid share link";
            exit;
        }

        $sharesRepo = ReelSharesRepository::getInstance();
        $share = $sharesRepo->getShareByToken($token);

        if (!$share) {
            http_response_code(404);
            echo "Share link not found or expired";
            exit;
        }

        // publiczny view
        return $this->render('share', [
            'videoUrl' => $share['video_name'],
            'thumbUrl' => $share['thumbnail_name'],
            'country'  => $share['country'],
            'createdAt'=> $share['created_at']
        ]);
    }
}