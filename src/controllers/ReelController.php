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

    // POST /createShareLink
    public function createShareLink() {
        header('Content-Type: application/json');

        try {
            if (session_status() === PHP_SESSION_NONE) session_start();
            if (!isset($_SESSION['user_id'])) throw new Exception('Unauthorized', 401);
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Method not allowed', 405);

            $userId = (int)$_SESSION['user_id'];
            $videoPath = $_POST['videoPath'] ?? '';

            if (empty($videoPath) || strlen($videoPath) > 255) {
                throw new Exception('Invalid videoPath', 400);
            }
            if (strpos($videoPath, '..') !== false || !str_starts_with($videoPath, 'media/')) {
                throw new Exception('Invalid videoPath', 400);
            }

            $reelsRepo = ReelsRepository::getInstance();
            $reelId = $reelsRepo->getReelIdByVideoNameAndUserId($videoPath, $userId);
            if ($reelId === null) throw new Exception('Reel not found', 404);

            $sharesRepo = ReelSharesRepository::getInstance();
            $token = $sharesRepo->getActiveTokenByReelId($reelId);

            if ($token === null) {
                $token = bin2hex(random_bytes(16));
                $sharesRepo->createShare($reelId, $token, null);
            }

            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            $shareUrl = "{$scheme}://{$host}/share/{$token}";

            echo json_encode(['status' => 'success', 'url' => $shareUrl]);
        } catch (Exception $e) {
            $code = $e->getCode();
            if ($code < 100 || $code > 599) $code = 500;
            http_response_code($code);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
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