<?php 

require_once 'AppController.php';
require_once __DIR__ . '/../repository/UserRepository.php';

class SecurityController extends AppController {

    private static $instance = null;

    public static function getInstance(): SecurityController {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private $userRepository;

    public function __construct() {
        $this->userRepository = UserRepository::getInstance();
    }

    private function generateCsrfToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
                'lifetime' => 3600,
                'path' => '/',
                'domain' => '',     
                'secure' => true,   
                'httponly' => true,  
                'samesite' => 'Strict' 
        ]);
        session_start();
    }
  
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32)); // 64 znakow token
    }
}

    public function login() {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => 3600,
                'path' => '/',
                'domain' => '',     
                'secure' => true,   
                'httponly' => true,  
                'samesite' => 'Strict' 
            ]);
            session_start();
        }

        if (!$this->isPost()) { //early return
            $this->generateCsrfToken();
            return $this->render('login'); 
        }

        $email = $_POST["email"] ?? '';
        $password = $_POST["password"] ?? '';

        if (empty($email) || empty($password)) {
            return $this->render('login', ['message' => 'Fill all fields']);
        }

        if (strlen($email) > 100) {
            return $this->render('login', ['message' => 'Invalid input length']);
        };

        if (strlen($password) > 100) {
            return $this->render('login', ['message' => 'Invalid input length']);
        };

        $clientToken = $_POST['csrf'] ?? '';
        $serverToken = $_SESSION['csrf'] ?? '';

        if ($clientToken !== $serverToken) {
            return $this->render('login', ['message' => 'CSRF detected']);
        }

        $userRow = $this->userRepository->getUserByEmail($email);

        if (!$userRow) {
            return $this->render('login', ['message' => 'Wrong email or password']);
        }

        if (!password_verify($password, $userRow['password'])) {
            error_log("Login failed for email $email");
            return $this->render('login', ['message' => 'Wrong email or password']);
        }

        session_regenerate_id(true);

        $_SESSION['user_id'] = $userRow['id'];
        $_SESSION['username'] = $userRow['username'];
        $_SESSION['role'] = $userRow['role'];

        error_log("zalogowano $email");
        
        if ($userRow['role'] === 1) { //admin
            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/adminPanel");
            exit();
        }
                                                                             
        $url = "http://$_SERVER[HTTP_HOST]";
        header("Location: {$url}/dashboard");
        exit();
    }

    public function register() {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => 3600,
                'path' => '/',
                'domain' => '',     
                'secure' => true,   
                'httponly' => true,  
                'samesite' => 'Strict' 
            ]);
            session_start();
        }

        if (!$this->isPost()) { //early return
            $this->generateCsrfToken();
            return $this->render('register'); 
        }

        $email = $_POST["email"] ?? '';
        $username = $_POST["username"] ?? '';
        $password = $_POST["password"] ?? '';
        $confPassword = $_POST["confPassword"] ?? '';

        if (empty($email) || empty($username) || empty($password) || empty($confPassword)) {
            return $this->render('register', ['message' => 'Fill all fields']);
        }

        if (strlen($email) > 100 || strlen($password) > 100 || strlen($confPassword) > 100 || strlen($username) > 50) {
            return $this->render('register', ['message' => 'Invalid input length']);
        };

        if ($password !== $confPassword) {
            return $this->render('register', ['message' => 'Passwords do not match']);
        }

        $passErr = $this->validatePasswordStrength($password, $username, $email);
        if ($passErr !== null) {
            return $this->render('register', ['message' => $passErr]);
        }

        $clientToken = $_POST['csrf'] ?? '';
        $serverToken = $_SESSION['csrf'] ?? '';

        if ($clientToken !== $serverToken) {
            return $this->render('login', ['message' => 'CSRF detected']);
        }

        $userRow = $this->userRepository->getUserByEmail($email);
        if ($userRow !== null) {
            return $this->render('register', ['message' => 'Something went wrong']);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->render('register', ['messages' => 'Invalid email format']);
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        $this->userRepository->createUser(
            $username,
            $email,
            $hashedPassword
        );

        return $this->render('login', ['message' => 'Registration successful! You can log in now.']);
    }

    private function validatePasswordStrength(string $password, string $username, string $email): ?string
    {
        $minLen = 8;

        if (strlen($password) < $minLen) {
            return "Password must be at least {$minLen} characters long.";
        }

        // mała + duża + cyfra + znak specjalny
        if (!preg_match('/[a-z]/', $password)) {
            return "Password must include a lowercase letter.";
        }
        if (!preg_match('/[A-Z]/', $password)) {
            return "Password must include an uppercase letter.";
        }
        if (!preg_match('/\d/', $password)) {
            return "Password must include a number.";
        }
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            return "Password must include a special character.";
        }

        // Nie może zawierać username/email
        $u = mb_strtolower($username);
        $e = mb_strtolower($email);
        $p = mb_strtolower($password);

        if ($u !== '' && strpos($p, $u) !== false) {
            return "Password should not contain your username.";
        }

        $emailLocal = explode('@', $e)[0] ?? '';
        if ($emailLocal !== '' && strlen($emailLocal) >= 3 && strpos($p, $emailLocal) !== false) {
            return "Password should not contain parts of your email.";
        }

        return null;
    }


    public function logout() {
        session_start();
        
        $_SESSION = [];
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        session_destroy();

        $url = "http://$_SERVER[HTTP_HOST]";
        header("Location: {$url}/login");
        exit();
    }

    
}
?>