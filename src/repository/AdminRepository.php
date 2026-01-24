<?php

require_once 'Repository.php';

class AdminRepository extends Repository {

    private static $instance;

    public static function getInstance(): AdminRepository
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getStats() {
        $conn = $this->database->connect();

        // Liczba użytkowników
        $stmtUsers = $conn->prepare("SELECT COUNT(*) as count FROM users");
        $stmtUsers->execute();
        $userCount = $stmtUsers->fetch(PDO::FETCH_ASSOC)['count'];

        // Liczba reelsów
        $stmtReels = $conn->prepare("SELECT COUNT(*) as count FROM reels"); 
        $stmtReels->execute();
        $reelsCount = $stmtReels->fetch(PDO::FETCH_ASSOC)['count'];

        return [
            'users' => $userCount,
            'reels' => $reelsCount
        ];
    }

    public function getAllUsers() {
        $stmt = $this->database->connect()->prepare('
            SELECT u.id, u.username, u.email, u.role, COUNT(r.id) as reels_count
            FROM users u
            LEFT JOIN reels r ON u.id = r.user_id
            GROUP BY u.id, u.username, u.email, u.role
            ORDER BY u.id DESC
        ');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteUser($id) {
        $pdo = $this->database->connect();
        
        try {
            // Usuwanie użytkownika i jego reelsów w transakcji
            $pdo->beginTransaction();

            $stmtReels = $pdo->prepare('DELETE FROM reels WHERE user_id = ?');
            $stmtReels->execute([$id]);

            $stmtUser = $pdo->prepare('DELETE FROM users WHERE id = ? AND role != 1');
            $stmtUser->execute([$id]);

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function updateUsername(int $id, string $newUsername) {
        $stmt = $this->database->connect()->prepare('
            UPDATE users SET username = ? WHERE id = ?
        ');
        $stmt->execute([$newUsername, $id]);
    }
}