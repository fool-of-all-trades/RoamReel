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

    public function deleteReel(int $reelId): void {
        $reel = $this->database->connect()->prepare('
            DELETE FROM reels WHERE id = ?
        ');
        $reel->execute([$reelId]);
    }

    public function updateReel(int $id, string $country, string $date, int $userId) {
        $reel = $this->database->connect()->prepare('
            UPDATE reels SET country = ?, created_at = ? WHERE id = ? AND user_id = ?
        ');
        $reel->execute([$country, $date, $id, $userId]);
    }

    public function getReelsByUserId(int $userId) {
        $reels = $this->database->connect()->prepare('
            SELECT id, thumbnail_name, created_at, country, video_name 
            FROM reels 
            WHERE user_id = ?
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

    public function getReelById(int $reelId): ?array {
        $reel = $this->database->connect()->prepare('
            SELECT id, user_id, video_name, thumbnail_name, created_at, country
            FROM reels
            WHERE id = ?
            LIMIT 1
        ');
        $reel->execute([$reelId]);
        $row = $reel->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

}
