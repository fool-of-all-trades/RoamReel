<?php
require_once 'Repository.php';

class ReelsRepository extends Repository {

    private static $instance;
    
    public static function getInstance(): ReelsRepository
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function addReel(
        $path,
        $thumbnailPath,
        $country,
        $createdAt
        ) :void {
        $reel = $this->database->connect()->prepare('
            INSERT INTO reels (user_id, country, video_name, thumbnail_name, created_at) VALUES (?, ?, ?, ?, ?)
            ');
        $userId = $_SESSION['user_id'];
        $reel->execute([
            $userId,
            $country,
            $path,
            $thumbnailPath,
            $createdAt
        ]);
    }

    public function getReelsByUserId(int $userId) {
        $reels = $this->database->connect()->prepare('
            SELECT thumbnail_name, created_at, country, video_name FROM reels WHERE user_id = ?
            ORDER BY created_at DESC
        ');
        $reels->execute([$userId]);
        return $reels->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getVisitedCountriesByUserId(int $userId) {
        $countries = $this->database->connect()->prepare('
            SELECT DISTINCT country FROM reels WHERE user_id = ?
        ');
        $countries->execute([$userId]);
        return $countries->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getReelsByCountryAndUserId(string $country, int $userId) {
        $reels = $this->database->connect()->prepare('
            SELECT thumbnail_name, video_name, created_at FROM reels WHERE user_id = ? AND country = ?
            ORDER BY created_at DESC
        ');
        $reels->execute([$userId, $country]);
        return $reels->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getReelIdByVideoNameAndUserId(string $videoName, int $userId): ?int {
        $stmt = $this->database->connect()->prepare('
            SELECT id FROM reels WHERE user_id = ? AND video_name = ? LIMIT 1
        ');
        $stmt->execute([$userId, $videoName]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['id'] : null;
    }

    public function getReelById(int $reelId): ?array {
        $stmt = $this->database->connect()->prepare('
            SELECT id, video_name, thumbnail_name, created_at, country
            FROM reels
            WHERE id = ?
            LIMIT 1
        ');
        $stmt->execute([$reelId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
