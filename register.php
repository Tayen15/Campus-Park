<?php
include_once 'koneksi.php';
include_once 'class/Auth.php';

session_start();
$auth = new Auth($conn);

// Inisialisasi CSRF token
if (!isset($_SESSION['csrf_token'])) {
     $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Proses form registrasi
$errors = [];
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
     // Verifikasi CSRF token
     if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
          $errors[] = 'Invalid CSRF token.';
     } else {
          $name = $_POST['name'] ?? '';
          $email = trim($_POST['email'] ?? '');
          $password = $_POST['password'] ?? '';
          $confirm_password = $_POST['confirm_password'] ?? '';
          $role = 'user';

          // Validasi input
          if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
               $errors[] = 'All fields are required.';
          }
          if (strlen($email) < 3) {
               $errors[] = 'Email must be at least 3 characters long.';
          }
          if (strlen($password) < 6) {
               $errors[] = 'Password must be at least 6 characters long.';
          }
          if ($password !== $confirm_password) {
               $errors[] = 'Passwords do not match.';
          }

          // Cek apakah Email sudah ada
          if (empty($errors)) {
               $check_sql = "SELECT id FROM users WHERE email = ?";
               $check_stmt = $conn->prepare($check_sql);
               $check_stmt->bind_param("s", $email);
               $check_stmt->execute();
               if ($check_stmt->get_result()->num_rows > 0) {
                    $errors[] = 'Email already exists.';
               }
               $check_stmt->close();
          }

          // Jika tidak ada error, lakukan registrasi
          if (empty($errors)) {
               if ($auth->register($name, $email, $password, $role)) {
                    $success = 'Registration successful! Please <a href="login.php" class="text-blue-600 hover:underline">login</a>.';
               } else {
                    $errors[] = 'Failed to register. Please try again.';
               }
          }
     }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
     <meta charset="UTF-8">
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <title>Register - Campus Parking Management System</title>
     <!-- Tailwind CSS via CDN -->
     <script src="https://cdn.tailwindcss.com"></script>
     <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>

<body class="bg-gray-100 font-sans leading-normal tracking-normal">
     <!-- Navigation -->
     <nav class="bg-blue-800 text-white shadow-lg">
          <div class="container mx-auto px-4">
               <div class="flex items-center justify-between py-4">
                    <div class="flex items-center">
                         <i class="fas fa-parking text-3xl mr-3"></i>
                         <span class="font-bold text-xl">CampusPark</span>
                    </div>
                    <!-- Hamburger Menu Button for Mobile -->
                    <button id="mobile-menu-button" class="md:hidden focus:outline-none">
                         <i class="fas fa-bars text-2xl"></i>
                    </button>
                    <!-- Desktop Menu -->
                    <div class="hidden md:flex space-x-6">
                         <a href="index.php" class="hover:text-blue-200">Home</a>
                         <a href="find-parking.php" class="hover:text-blue-200">Find Parking</a>
                         <a href="reserve-parking.php" class="hover:text-blue-200">Reserve</a>
                         <a href="register-vehicle.php" class="hover:text-blue-200">Register Vehicle</a>
                         <a href="my-vehicles.php" class="hover:text-blue-200">My Vehicles</a>

                    </div>
               </div>
               <!-- Mobile Menu -->
               <div id="mobile-menu" class="md:hidden hidden flex-col space-y-4 pb-4">
                    <a href="index.php" class="hover:text-blue-200">Home</a>
                    <a href="find-parking.php" class="hover:text-blue-200">Find Parking</a>
                    <a href="reserve-parking.php" class=" hovered:text-blue-200">Reserve</a>
                    <a href="register-vehicle.php" class="hover:text-blue-200">Register Vehicle</a>
                    <a href="my-vehicles.php" class="hover:text-blue-200">My Vehicles</a>
               </div>
          </div>
     </nav>

     <!-- Page Header -->
     <header class="bg-blue-700 text-white py-10">
          <div class="container mx-auto px-4">
               <h1 class="text-3xl font-bold mb-2">Register to Your Account</h1>
               <p class="text-lg">Create an account to manage your vehicles and parking reservations.</p>
          </div>
     </header>

     <!-- Register Form Section -->
     <section class="py-12">
          <div class="container mx-auto px-4">
               <div class="max-w-md mx-auto bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="py-8 px-8">
                         <div class="text-center mb-8">
                              <i class="fas fa-user-plus text-5xl text-blue-500 mb-4"></i>
                              <h2 class="text-2xl font-bold text-gray-800">User Registration</h2>
                         </div>

                         <?php if ($success): ?>
                              <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg p-4 mb-6">
                                   <div class="flex">
                                        <div class="flex-shrink-0">
                                             <i class="fas fa-check-circle text-green-600"></i>
                                        </div>
                                        <div class="ml-3">
                                             <p class="font-medium"><?php echo $success; ?></p>
                                        </div>
                                   </div>
                              </div>
                         <?php endif; ?>

                         <?php if (!empty($errors)): ?>
                              <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg p-4 mb-6">
                                   <div class="flex">
                                        <div class="flex-shrink-0">
                                             <i class="fas fa-exclamation-circle text-red-600"></i>
                                        </div>
                                        <div class="ml-3">
                                             <ul class="list-disc ml-5">
                                                  <?php foreach ($errors as $error): ?>
                                                       <li><?php echo htmlspecialchars($error); ?></li>
                                                  <?php endforeach; ?>
                                             </ul>
                                        </div>
                                   </div>
                              </div>
                         <?php endif; ?>

                         <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                              <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                              <div class="mb-6">
                                   <label for="name" class="block text-gray-700 font-medium mb-2">Name</label>
                                   <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                             <i class="fas fa-user text-gray-400"></i>
                                        </div>
                                        <input type="text" id="name" name="name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required class="w-full py-3 pl-10 pr-4 text-gray-700 bg-gray-50 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Your Name">
                                   </div>
                              </div>

                              <div class="mb-6">
                                   <label for="email" class="block text-gray-700 font-medium mb-2">Email</label>
                                   <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                             <i class="fas fa-user text-gray-400"></i>
                                        </div>
                                        <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required class="w-full py-3 pl-10 pr-4 text-gray-700 bg-gray-50 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Your Email">
                                   </div>
                              </div>

                              <div class="mb-6">
                                   <label for="password" class="block text-gray-700 font-medium mb-2">Password</label>
                                   <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                             <i class="fas fa-lock text-gray-400"></i>
                                        </div>
                                        <input type="password" id="password" name="password" required class="w-full py-3 pl-10 pr-4 text-gray-700 bg-gray-50 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="••••••••">
                                   </div>
                              </div>

                              <div class="mb-6">
                                   <label for="confirm_password" class="block text-gray-700 font-medium mb-2">Confirm Password</label>
                                   <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                             <i class="fas fa-lock text-gray-400"></i>
                                        </div>
                                        <input type="password" id="confirm_password" name="confirm_password" required class="w-full py-3 pl-10 pr-4 text-gray-700 bg-gray-50 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="••••••••">
                                   </div>
                              </div>

                              <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                   Register
                              </button>
                         </form>

                         <div class="mt-6 text-center">
                              <p class="text-gray-600">Already have an account? <a href="login.php" class="text-blue-600 hover:underline font-medium">Login</a></p>
                         </div>
                    </div>
               </div>
          </div>
     </section>

     <!-- Footer -->
     <footer class="bg-gray-800 text-gray-300 py-8 mt-12">
          <div class="container mx-auto px-4">
               <div class="flex flex-col md:flex-row justify-between mb-6">
                    <div class="mb-6 md:mb-0">
                         <div class="flex items-center mb-4">
                              <i class="fas fa-parking text-2xl mr-2 text-white"></i>
                              <span class="font-bold text-xl text-white">CampusPark</span>
                         </div>
                         <p class="max-w-md">The comprehensive solution for campus parking management.</p>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-8">
                         <div>
                              <h3 class="text-white font-semibold mb-4">Quick Links</h3>
                              <ul class="space-y-2">
                                   <li><a href="index.php" class="hover:text-white">Home</a></li>
                                   <li><a href="find-parking.php" class="hover:text-white">Find Parking</a></li>
                                   <li><a href="reserve-parking.php" class="hover:text-white">Reserve</a></li>
                                   <li><a href="register-vehicle.php" class="hover:text-white">Register Vehicle</a></li>
                              </ul>
                         </div>
                         <div>
                              <h3 class="text-white font-semibold mb-4">Support</h3>
                              <ul class="space-y-2">
                                   <li><a href="#" class="hover:text-white">Help Center</a></li>
                                   <li><a href="#" class="hover:text-white">Contact Us</a></li>
                                   <li><a href="#" class="hover:text-white">Privacy Policy</a></li>
                              </ul>
                         </div>
                         <div class="col-span-2 md:col-span-1">
                              <h3 class="text-white font-semibold mb-4">Contact</h3>
                              <ul class="space-y-2">
                                   <li><i class="fas fa-envelope mr-2"></i> info@campuspark.com</li>
                                   <li><i class="fas fa-phone mr-2"></i> +123 456 7890</li>
                              </ul>
                         </div>
                    </div>
               </div>
               <div class="border-t border-gray-700 pt-6 text-center">
                    <p>© <?php echo date('Y'); ?> CampusPark. All rights reserved.</p>
               </div>
          </div>
     </footer>
     <script>
          // JavaScript for mobile menu toggle
          document.addEventListener('DOMContentLoaded', function() {
               const mobileMenuButton = document.getElementById('mobile-menu-button');
               const mobileMenu = document.getElementById('mobile-menu');

               mobileMenuButton.addEventListener('click', function() {
                    mobileMenu.classList.toggle('hidden');
                    mobileMenu.classList.toggle('flex');
               });
          });
     </script>
</body>

</html>
<?php $conn->close(); ?>