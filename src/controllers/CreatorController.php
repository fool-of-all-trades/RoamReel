<?php 

require_once 'AppController.php';
require_once __DIR__ . '/../repository/ReelsRepository.php';
require_once __DIR__ . '/../repository/CountryRepository.php';

class CreatorController extends AppController {

    private static $instance = null;

    public static function getInstance(): CreatorController {
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
        $countryRepository = CountryRepository::getInstance();
        
        $countries = $countryRepository->getCountries();

        return $this->render('creator', ['countries' => $countries]);
    }

    public function generateReel() {
        ob_start();
        header('Content-Type: application/json');

        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            if (!isset($_SESSION['username'])) {
                throw new Exception('Użytkownik nie jest zalogowany.', 401);
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Wymagana metoda POST.', 405);
            }

            $username = $_SESSION['username'];
            $country = $_POST['country'] ?? 'Unknown';
            $date = !empty($_POST['date-select']) ? $_POST['date-select'] : date('Y-m-d');

            // Konfiguracja ścieżek
            $baseDir = __DIR__ . '/../../media/' . $username;
            $uploaddir = $baseDir . '/temp/';
            
            if (!is_dir($uploaddir)) {
                if (!mkdir($uploaddir, 0777, true)) {
                    throw new Exception("Nie udało się utworzyć katalogu tymczasowego.", 500);
                }
            }
            if (!is_dir($baseDir)) {
                mkdir($baseDir, 0777, true);
            }

            // Walidacja plików
            if (!isset($_FILES['photos']) || empty($_FILES['photos']['name'][0])) {
                throw new Exception("Nie przesłano żadnych zdjęć.", 400);
            }

            // Czyszczenie temp i upload
            array_map('unlink', glob("$uploaddir/*.*"));
            
            $files = $_FILES['photos'];
            $savedCount = 0;

            foreach ($files['tmp_name'] as $index => $tmpName) {
                if ($files['error'][$index] === UPLOAD_ERR_OK) {
                    $fileName = sprintf("img_%03d.jpg", $index);
                    if (move_uploaded_file($tmpName, $uploaddir . $fileName)) {
                        $savedCount++;
                    }
                }
            }

            if ($savedCount === 0) {
                throw new Exception("Błąd zapisu plików na serwerze.", 500);
            }

            // Upload muzyki (opcjonalnie)
            $audioPath = null;

            if (isset($_FILES['music']) && $_FILES['music']['error'] !== UPLOAD_ERR_NO_FILE) {

                if ($_FILES['music']['error'] !== UPLOAD_ERR_OK) {
                    throw new Exception("Błąd przesyłania pliku muzycznego.", 400);
                }

                // limit rozmiaru 20MB
                $maxBytes = 20 * 1024 * 1024;
                if ($_FILES['music']['size'] > $maxBytes) {
                    throw new Exception("Plik muzyczny jest za duży (max 20MB).", 400);
                }

                // prosta walidacja MIME dla bezpieczeństwa
                $allowedMime = [
                    'audio/mpeg',
                    'audio/mp3',
                    'audio/wav',
                    'audio/x-wav',
                    'audio/mp4',
                    'audio/aac',
                    'audio/ogg',
                    'application/ogg',
                    'audio/webm'
                ];

                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($_FILES['music']['tmp_name']);

                if (!in_array($mime, $allowedMime, true)) {
                    throw new Exception("Nieobsługiwany format muzyki: {$mime}", 400);
                }

                // rozszerzenie z nazwy (fallback jeśli MIME nie zmapuje)
                $ext = strtolower(pathinfo($_FILES['music']['name'], PATHINFO_EXTENSION));
                if ($ext === '') {
                    $mimeToExt = [
                        'audio/mpeg' => 'mp3',
                        'audio/mp4'  => 'm4a',
                        'audio/wav'  => 'wav',
                        'audio/x-wav'=> 'wav',
                        'audio/aac'  => 'aac',
                        'audio/ogg'  => 'ogg',
                        'application/ogg' => 'ogg',
                        'audio/webm' => 'webm'
                    ];
                    $ext = $mimeToExt[$mime] ?? 'audio';
                }

                // zapisujemy w temp obok zdjęć
                $audioFileName = 'music_' . time() . '.' . $ext;
                $audioPath = $uploaddir . $audioFileName;

                if (!move_uploaded_file($_FILES['music']['tmp_name'], $audioPath)) {
                    throw new Exception("Nie udało się zapisać pliku muzycznego na serwerze.", 500);
                }
            }


            // Python - Generowanie wideo
            $timestamp = time();
            $videoName = 'reel_' . $timestamp . '.mp4';
            $thumbName = 'thumb_reel_' . $timestamp . '.jpg';

            $fullVideoPath = $baseDir . '/' . $videoName;
            $fullThumbPath = $baseDir . '/' . $thumbName;

            // Ścieżki relatywne do bazy danych
            $dbVideoPath = 'media/' . $username . '/' . $videoName;
            $dbThumbPath = 'media/' . $username . '/' . $thumbName;
            
            // Generowanie miniatury z pierwszego zdjęcia
            $firstImage = $uploaddir . 'img_000.jpg';
            $finalThumbDbPath = null;

            if (file_exists($firstImage)) {
                if (copy($firstImage, $fullThumbPath)) {
                    $finalThumbDbPath = $dbThumbPath;
                }
            }

            $pythonScript = __DIR__ . '/../services/video_maker.py';
            
            $args = [
                "python3",
                escapeshellarg($pythonScript),
                escapeshellarg($uploaddir),
                escapeshellarg($fullVideoPath),
                escapeshellarg($fullThumbPath)
            ];

            if ($audioPath !== null) {
                $args[] = escapeshellarg($audioPath);
            }

            $command = implode(' ', $args) . " 2>&1";
            $pythonOutput = shell_exec($command);


            if (!file_exists($fullVideoPath)) {
                throw new Exception("Skrypt Python nie wygenerował pliku. Output: " . $pythonOutput, 500);
            }

            // Sprzątanie
            array_map('unlink', glob("$uploaddir/*.*"));

            // Zapis do bazy
            $reelsRepository = ReelsRepository::getInstance();
            $reelsRepository->addReel($dbVideoPath, $finalThumbDbPath, $country, $date);

            // Sukces - czysty JSON
            ob_clean();
            http_response_code(200);
            echo json_encode([
            'status' => 'success',
            'videoPath' => $dbVideoPath,
            'thumbnailPath' => $finalThumbDbPath,
            'pythonOutput' => $pythonOutput,
            'audioProvided' => $audioPath !== null
            ]);
        } catch (Exception $e) {
            // Błąd - czysty JSON + kod HTTP
            ob_clean();
            
            $code = $e->getCode();
            if ($code < 100 || $code > 599) $code = 500;
            
            http_response_code($code);
            echo json_encode([
                'status' => 'error', 
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }
}
?>