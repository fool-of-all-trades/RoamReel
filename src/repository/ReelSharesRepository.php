<?php
require_once 'Repository.php';

class ReelSharesRepository extends Repository {

    private static $instance;

    public static function getInstance(): ReelSharesRepository {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getActiveTokenByReelId(int $reelId): ?string {
        $stmt = $this->database->connect()->prepare('
            SELECT token
            FROM reel_shares
            WHERE reel_id = ? AND is_active = TRUE
              AND (expires_at IS NULL OR expires_at > NOW())
            ORDER BY created_at DESC
            LIMIT 1
        ');
        $stmt->execute([$reelId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['token'] : null;
    }

    public function createShare(int $reelId, string $token, ?string $expiresAt = null): void {
        $stmt = $this->database->connect()->prepare('
            INSERT INTO reel_shares (reel_id, token, expires_at)
            VALUES (?, ?, ?)
        ');
        $stmt->execute([$reelId, $token, $expiresAt]);
    }

    public function getShareByToken(string $token): ?array {
        $stmt = $this->database->connect()->prepare('
            SELECT rs.token, rs.is_active, rs.expires_at,
                   r.video_name, r.thumbnail_name, r.created_at, r.country
            FROM reel_shares rs
            JOIN reels r ON r.id = rs.reel_id
            WHERE rs.token = ?
              AND rs.is_active = TRUE
              AND (rs.expires_at IS NULL OR rs.expires_at > NOW())
            LIMIT 1
        ');
        $stmt->execute([$token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
