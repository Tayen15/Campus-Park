<?php
class Auth
{
     private $conn;
     private $error_message = '';

     // Constructor menerima koneksi database
     public function __construct($dbConnection)
     {
          $this->conn = $dbConnection;
     }

     // Getter untuk error message
     public function getErrorMessage()
     {
          return $this->error_message;
     }

     // Cek apakah pengguna sudah login
     public function isLoggedIn()
     {
          return isset($_SESSION['user_id']);
     }

     public function register($name, $email, $password, $role = 'user')
     {
          if (!in_array($role, ['admin', 'user'])) {
               $this->error_message = 'Invalid role. Must be "admin" or "user".';
               return false;  
          }

          $hashed_password = password_hash($password, PASSWORD_DEFAULT);

          $sql = "INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)";
          $stmt = $this->conn->prepare($sql);

          if (!$stmt) {
               $this->error_message = 'Database error: Failed to prepare statement.';
               return false;
          }

          $stmt->bind_param("ssss", $name, $email, $hashed_password, $role);
          $success = $stmt->execute();
          $stmt->close();

          if (!$success) {
               $this->error_message = 'Failed to register user.';
          }

          return $success;
     }

     // Proses login
     public function login($email, $password)
     {
          // Validasi input
          if (empty($email) || empty($password)) {
               $this->error_message = "Please enter both email and password.";
               return false;
          }

          // Siapkan query untuk memeriksa kredensial pengguna
          $query = "SELECT id, nama, email, role, password FROM users WHERE email = ?";
          $stmt = $this->conn->prepare($query);
          if (!$stmt) {
               $this->error_message = "Database error: Failed to prepare statement.";
               return false;
          }

          $stmt->bind_param("s", $email);
          $stmt->execute();
          $result = $stmt->get_result();

          if ($result->num_rows === 1) {
               $user = $result->fetch_assoc();

               // Verifikasi password
               if (password_verify($password, $user['password'])) {
                    // Password benar, buat sesi
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['nama'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'];
                    $stmt->close();
                    return true;
               } else {
                    $this->error_message = "Invalid email or password.";
                    $stmt->close();
                    return false;
               }
          } else {
               $this->error_message = "Invalid email or password.";
               $stmt->close();
               return false;
          }
     }

     // Mendapatkan halaman redirect berdasarkan role
     public function getRedirectPage()
     {
          if (isset($_SESSION['user_role'])) {
               if ($_SESSION['user_role'] === 'admin') {
                    return 'dash/dashboard.php';
               } else {
                    return 'my-vehicles.php'; // Halaman default untuk user
               }
          }
          return 'index.php';
     }

     // Proses logout
     public function logout()
     {
          // Hapus semua data sesi
          session_unset();
          session_destroy();

          header("Location: login.php");
     }
}
