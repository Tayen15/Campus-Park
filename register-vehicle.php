<?php
// Database connection
include_once 'koneksi.php';

// initialize variables
$user_id = null;
$is_logged_in = false;
$current_username = null;

// Check if user is logged in
session_start();
if (isset($_SESSION['user_id'])) {
     $user_id = $_SESSION['user_id'];
     $is_logged_in = true;
     $current_username = $_SESSION['user_name'];
}

// Get vehicle types from database
$vehicle_types = $conn->query("SELECT * FROM jenis ORDER BY nama");

// Handle form submission
$success_message = null;
$error_message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
     // Process form data
     $merk = $_POST['merk'];
     $pemilik = $_POST['pemilik'];
     $nopol = $_POST['nopol'];
     $thn_beli = intval($_POST['thn_beli']);
     $deskripsi = $_POST['deskripsi'];
     $jenis_id = intval($_POST['jenis_id']);
     $user_id = $_SESSION['user_id'];

     // Validate data
     $valid = true;

     if (empty($merk) || empty($pemilik) || empty($nopol) || $thn_beli <= 0 || $jenis_id <= 0 || empty($user_id)) {
          $valid = false;
          $error_message = "All required fields must be filled in correctly.";
     }

     // Check if license plate already exists
     $check_sql = "SELECT id FROM kendaraan WHERE nopol = ?";
     $check_stmt = $conn->prepare($check_sql);
     $check_stmt->bind_param("s", $nopol);
     $check_stmt->execute();
     $check_result = $check_stmt->get_result();

     if ($check_result->num_rows > 0) {
          $valid = false;
          $error_message = "A vehicle with this license plate is already registered.";
     }
     $check_stmt->close();

     // If valid, insert into database
     if ($valid) {
          $sql = "INSERT INTO kendaraan (merk, pemilik, nopol, thn_beli, deskripsi, jenis_kendaraan_id, user_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";

          $stmt = $conn->prepare($sql);
          $stmt->bind_param("sssisii", $merk, $pemilik, $nopol, $thn_beli, $deskripsi, $jenis_id, $user_id);

          if ($stmt->execute()) {
               $success_message = "Vehicle successfully registered!";
          } else {
               $error_message = "Error registering vehicle: " . $stmt->error;
          }

          $stmt->close();
     }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
     <meta charset="UTF-8">
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <title>Register Vehicle - Campus Parking Management System</title>
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
                         <a href="register-vehicle.php" class="border-b-2 border-white hover:text-blue-200">Register Vehicle</a>
                         <a href="my-vehicles.php" class="hover:text-blue-200">My Vehicles</a>
                         <div>
                              <?php if ($is_logged_in): ?>
                                   <a href="logout.php" class="bg-blue-600 hover:bg-blue-700 py-2 px-4 rounded-lg font-medium">Logout</a>
                              <?php else: ?>
                                   <a href="login.php" class="bg-blue-600 hover:bg-blue-700 py-2 px-4 rounded-lg font-medium">Login</a>
                              <?php endif; ?>
                         </div>
                    </div>
               </div>
               <!-- Mobile Menu -->
               <div id="mobile-menu" class="md:hidden hidden flex-col space-y-4 pb-4">
                    <a href="index.php" class="hover:text-blue-200">Home</a>
                    <a href="find-parking.php" class="hover:text-blue-200">Find Parking</a>
                    <a href="reserve-parking.php" class=" hovered:text-blue-200">Reserve</a>
                    <a href="register-vehicle.php" class="hover:text-blue-200">Register Vehicle</a>
                    <a href="my-vehicles.php" class="hover:text-blue-200">My Vehicles</a>
                    <div>
                         <?php if ($is_logged_in): ?>
                              <a href="logout.php" class="bg-blue-600 hover:bg-blue-700 py-2 px-4 rounded-lg font-medium">Logout</a>
                         <?php else: ?>
                              <a href="login.php" class="bg-blue-600 hover:bg-blue-700 py-2 px-4 rounded-lg font-medium">Login</a>
                         <?php endif; ?>
                    </div>
               </div>
          </div>
     </nav>

     <!-- Page Header -->
     <header class="bg-blue-700 text-white py-10">
          <div class="container mx-auto px-4">
               <h1 class="text-3xl font-bold mb-2">Register Your Vehicle</h1>
               <p class="text-lg">Add your vehicle details to enable easy parking reservations across campus.</p>
          </div>
     </header>

     <!-- Registration Form Section -->
     <section class="py-8">
          <div class="container mx-auto px-4">
               <?php if ($success_message): ?>
                    <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg p-4 mb-6">
                         <div class="flex">
                              <div class="flex-shrink-0">
                                   <i class="fas fa-check-circle text-green-600"></i>
                              </div>
                              <div class="ml-3">
                                   <p class="font-medium"><?php echo $success_message; ?></p>
                                   <p class="mt-2">You can now use this vehicle for parking reservations.</p>
                                   <div class="mt-4">
                                        <a href="reserve-parking.php" class="text-sm font-medium text-green-600 hover:text-green-800">Make a Reservation</a>
                                        <span class="mx-2">|</span>
                                        <a href="register-vehicle.php" class="text-sm font-medium text-green-600 hover:text-green-800">Register Another Vehicle</a>
                                   </div>
                              </div>
                         </div>
                    </div>
               <?php endif; ?>

               <?php if ($error_message): ?>
                    <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg p-4 mb-6">
                         <div class="flex">
                              <div class="flex-shrink-0">
                                   <i class="fas fa-exclamation-circle text-red-600"></i>
                              </div>
                              <div class="ml-3">
                                   <p class="font-medium">There was an error registering your vehicle:</p>
                                   <p class="mt-1"><?php echo $error_message; ?></p>
                              </div>
                         </div>
                    </div>
               <?php endif; ?>

               <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="md:flex">
                         <!-- Left side - form -->
                         <div class="md:w-2/3 p-6">
                              <h2 class="text-2xl font-bold mb-6">Vehicle Information</h2>

                              <form action="register-vehicle.php" method="POST">
                                   <!-- Vehicle type -->
                                   <div class="mb-4">
                                        <label for="jenis_id" class="block text-sm font-medium text-gray-700 mb-1">Vehicle Type</label>
                                        <select name="jenis_id" id="jenis_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" required>
                                             <option value="">-- Select Vehicle Type --</option>
                                             <?php if ($vehicle_types && $vehicle_types->num_rows > 0): ?>
                                                  <?php while ($type = $vehicle_types->fetch_assoc()): ?>
                                                       <option value="<?php echo $type['id']; ?>">
                                                            <?php echo $type['nama']; ?>
                                                       </option>
                                                  <?php endwhile; ?>
                                             <?php endif; ?>
                                        </select>
                                   </div>

                                   <!-- Vehicle Make/Model -->
                                   <div class="mb-4">
                                        <label for="merk" class="block text-sm font-medium text-gray-700 mb-1">Make/Model</label>
                                        <input type="text" name="merk" id="merk" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" placeholder="e.g. Honda Civic" required>
                                   </div>

                                   <!-- Vehicle Owner -->
                                   <div class="mb-4">
                                        <label for="pemilik" class="block text-sm font-medium text-gray-700 mb-1">Owner Name</label>
                                        <input type="text" name="pemilik" id="pemilik" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" placeholder="Full name" value="<?php echo htmlspecialchars($current_username); ?>" readonly>
                                   </div>

                                   <!-- License Plate -->
                                   <div class="mb-4">
                                        <label for="nopol" class="block text-sm font-medium text-gray-700 mb-1">License Plate</label>
                                        <input type="text" name="nopol" id="nopol" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" placeholder="e.g. B 1234 CD" required>
                                   </div>

                                   <!-- Purchase Year -->
                                   <div class="mb-4">
                                        <label for="thn_beli" class="block text-sm font-medium text-gray-700 mb-1">Purchase Year</label>
                                        <input type="number" name="thn_beli" id="thn_beli" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" min="1900" max="<?php echo date('Y'); ?>" value="<?php echo date('Y'); ?>" required>
                                   </div>

                                   <!-- Description -->
                                   <div class="mb-6">
                                        <label for="deskripsi" class="block text-sm font-medium text-gray-700 mb-1">Description (Optional)</label>
                                        <textarea name="deskripsi" id="deskripsi" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" placeholder="Color, distinctive features, etc."></textarea>
                                   </div>

                                   <!-- Submit button -->
                                   <div>
                                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-md transition duration-150 ease-in-out">
                                             Register Vehicle
                                        </button>
                                   </div>
                              </form>
                         </div>

                         <!-- Right side - information -->
                         <div class="md:w-1/3 bg-gray-50 p-6 border-l border-gray-200">
                              <div class="text-center py-4">
                                   <i class="fas fa-car text-4xl text-blue-300 mb-3"></i>
                                   <h3 class="text-lg font-bold mb-2">Why Register Your Vehicle?</h3>
                                   <p class="text-gray-600 mb-4">Registering your vehicle makes parking reservation quick and easy across all campus locations.</p>
                              </div>

                              <div class="border-t border-gray-200 mt-6 pt-6">
                                   <h4 class="font-medium mb-2">Benefits:</h4>
                                   <ul class="text-sm text-gray-600 space-y-2">
                                        <li><i class="fas fa-check-circle text-green-500 mr-2"></i>Faster reservation process</li>
                                        <li><i class="fas fa-check-circle text-green-500 mr-2"></i>Vehicle-specific parking recommendations</li>
                                        <li><i class="fas fa-check-circle text-green-500 mr-2"></i>Automated payment processing</li>
                                        <li><i class="fas fa-check-circle text-green-500 mr-2"></i>Digital parking history and receipts</li>
                                   </ul>
                              </div>

                              <div class="border-t border-gray-200 mt-6 pt-6">
                                   <h4 class="font-medium mb-2">Parking Tips:</h4>
                                   <ul class="text-sm text-gray-600 space-y-2">
                                        <li><i class="fas fa-info-circle text-blue-500 mr-2"></i>Vehicle dimensions help us suggest appropriate parking spaces</li>
                                        <li><i class="fas fa-info-circle text-blue-500 mr-2"></i>Keep your registration details updated for accurate notifications</li>
                                        <li><i class="fas fa-info-circle text-blue-500 mr-2"></i>You can register multiple vehicles under your account</li>
                                   </ul>
                              </div>
                         </div>
                    </div>
               </div>
          </div>
     </section>

     <!-- FAQ Section -->
     <section class="py-8 bg-gray-50">
          <div class="container mx-auto px-4">
               <h2 class="text-2xl font-bold mb-8 text-center">Frequently Asked Questions</h2>

               <div class="max-w-3xl mx-auto space-y-4">
                    <div class="bg-white rounded-lg shadow-md p-4">
                         <h3 class="font-bold mb-2">Why do I need to register my vehicle?</h3>
                         <p class="text-gray-600">Registering your vehicle allows for seamless parking reservations and helps us manage campus parking efficiently. It's a one-time process that simplifies all future parking needs.</p>
                    </div>

                    <div class="bg-white rounded-lg shadow-md p-4">
                         <h3 class="font-bold mb-2">Can I register multiple vehicles?</h3>
                         <p class="text-gray-600">Yes, you can register as many vehicles as you need under your account. Each vehicle can have its own parking history and preferences.</p>
                    </div>

                    <div class="bg-white rounded-lg shadow-md p-4">
                         <h3 class="font-bold mb-2">How do I update my vehicle information?</h3>
                         <p class="text-gray-600">You can update your vehicle details anytime by visiting your account dashboard and selecting the "Manage Vehicles" option.</p>
                    </div>

                    <div class="bg-white rounded-lg shadow-md p-4">
                         <h3 class="font-bold mb-2">Is my vehicle information secure?</h3>
                         <p class="text-gray-600">Yes, all vehicle information is securely stored and only used for parking management purposes. We do not share your data with third parties.</p>
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
                    <p>&copy; <?php echo date('Y'); ?> CampusPark. All rights reserved.</p>
               </div>
          </div>
     </footer>

     <script>
          // Form validation
          document.addEventListener('DOMContentLoaded', function() {
               const form = document.querySelector('form');
               const plateInput = document.getElementById('nopol');

               form.addEventListener('submit', function(event) {
                    // Additional client-side validation could be added here
                    // This is just a basic example
                    if (plateInput.value.trim().length < 4) {
                         event.preventDefault();
                         alert('Please enter a valid license plate number');
                         plateInput.focus();
                    }
               });

               // Optional: Format license plate as user types
               plateInput.addEventListener('input', function() {
                    // You could implement license plate formatting here
                    // Example: Convert to uppercase
                    this.value = this.value.toUpperCase();
               });

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