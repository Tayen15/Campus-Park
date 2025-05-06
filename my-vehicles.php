<?php
// Database connection
include_once 'koneksi.php';

// Initialize variables
$vehicles = null;
$transactions = null;
$user_id = null;
$is_logged_in = false;
$delete_message = null;

// Check if user is logged in
session_start();
if (isset($_SESSION['user_id'])) {
     $is_logged_in = true;
     $user_id = $_SESSION['user_id'];

     // First, let's alter the kendaraan table if user_id column doesn't exist yet
     // This is a one-time operation that should be done in a separate migration script
     // but for simplicity, we're including it here
     $check_column = "SHOW COLUMNS FROM kendaraan LIKE 'user_id'";
     $column_result = $conn->query($check_column);

     if ($column_result->num_rows == 0) {
          // Add user_id column to kendaraan table
          $alter_table = "ALTER TABLE kendaraan ADD COLUMN user_id INT(11) DEFAULT NULL AFTER jenis_kendaraan_id";
          $conn->query($alter_table);

          // Add foreign key constraint
          $add_foreign_key = "ALTER TABLE kendaraan ADD CONSTRAINT kendaraan_ibfk_2 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE";
          $conn->query($add_foreign_key);
     }

     // Get user's vehicles - filtered by user ID
     $query = "SELECT k.*, j.nama as jenis_nama 
              FROM kendaraan k
              JOIN jenis j ON k.jenis_kendaraan_id = j.id
              WHERE k.user_id = ?
              ORDER BY k.merk, k.nopol";
     $stmt = $conn->prepare($query);
     $stmt->bind_param("i", $user_id);
     $stmt->execute();
     $vehicles = $stmt->get_result();

     // Handle vehicle deletion if requested
     if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
          $vehicle_id = intval($_GET['delete']);

          // Verify the vehicle belongs to this user
          $check_ownership = "SELECT COUNT(*) as count FROM kendaraan WHERE id = ? AND user_id = ?";
          $check_stmt = $conn->prepare($check_ownership);
          $check_stmt->bind_param("ii", $vehicle_id, $user_id);
          $check_stmt->execute();
          $ownership_result = $check_stmt->get_result();
          $ownership_row = $ownership_result->fetch_assoc();

          if ($ownership_row['count'] > 0) {
               // Check if the vehicle is used in any transactions
               $check_query = "SELECT COUNT(*) as count FROM transaksi WHERE kendaraan_id = ?";
               $check_stmt = $conn->prepare($check_query);
               $check_stmt->bind_param("i", $vehicle_id);
               $check_stmt->execute();
               $result = $check_stmt->get_result();
               $row = $result->fetch_assoc();

               if ($row['count'] > 0) {
                    $delete_message = "Cannot delete vehicle as it is used in parking transactions.";
               } else {
                    // Delete the vehicle
                    $delete_query = "DELETE FROM kendaraan WHERE id = ? AND user_id = ?";
                    $delete_stmt = $conn->prepare($delete_query);
                    $delete_stmt->bind_param("ii", $vehicle_id, $user_id);

                    if ($delete_stmt->execute()) {
                         $delete_message = "Vehicle successfully deleted.";
                         // Refresh the page to update the list
                         header("Location: my-vehicles.php?deleted=true");
                         exit;
                    } else {
                         $delete_message = "Error deleting vehicle: " . $delete_stmt->error;
                    }

                    $delete_stmt->close();
               }

               $check_stmt->close();
          } else {
               $delete_message = "You don't have permission to delete this vehicle.";
          }
     }

     // Show delete success message if redirected after deletion
     if (isset($_GET['deleted']) && $_GET['deleted'] == 'true') {
          $delete_message = "Vehicle successfully deleted.";
     }

     // Get recent parking transactions for this user
     $transactions_query = "SELECT t.*, k.nopol, k.merk, a.nama as area_nama, kp.nama as kampus_nama 
                FROM transaksi t
                JOIN kendaraan k ON t.kendaraan_id = k.id
                JOIN area_parkir a ON t.area_parkir_id = a.id
                JOIN kampus kp ON a.kampus_id = kp.id
                WHERE k.user_id = ?
                ORDER BY t.tanggal DESC, t.mulai DESC
                LIMIT 5";
     $trans_stmt = $conn->prepare($transactions_query);
     $trans_stmt->bind_param("i", $user_id);
     $trans_stmt->execute();
     $transactions = $trans_stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
     <meta charset="UTF-8">
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <title>My Vehicles - Campus Parking Management System</title>
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
                         <a href="my-vehicles.php" class="border-b-2 border-white hover:text-blue-200">My Vehicles</a>
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
               <h1 class="text-3xl font-bold mb-2">My Vehicles</h1>
               <p class="text-lg">Manage your registered vehicles and parking history.</p>
          </div>
     </header>

     <!-- Vehicles Section -->
     <section class="py-8">
          <div class="container mx-auto px-4">
               <?php if ($delete_message): ?>
                    <div class="bg-blue-50 border border-blue-200 text-blue-800 rounded-lg p-4 mb-6">
                         <div class="flex">
                              <div class="flex-shrink-0">
                                   <i class="fas fa-info-circle text-blue-600"></i>
                              </div>
                              <div class="ml-3">
                                   <p class="font-medium"><?php echo $delete_message; ?></p>
                              </div>
                         </div>
                    </div>
               <?php endif; ?>

               <?php if (!$is_logged_in): ?>
                    <!-- Show login message for guests -->
                    <div class="bg-white rounded-lg shadow-md p-8 text-center">
                         <i class="fas fa-user-lock text-5xl text-gray-300 mb-4"></i>
                         <h3 class="text-xl font-bold mb-2">Login Required</h3>
                         <p class="text-gray-600 mb-6">Please login to view and manage your vehicles.</p>
                         <a href="login.php" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-md">
                              Login Now
                         </a>
                    </div>
               <?php else: ?>
                    <div class="flex justify-between items-center mb-6">
                         <h2 class="text-2xl font-bold">Registered Vehicles</h2>
                         <a href="register-vehicle.php" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md flex items-center">
                              <i class="fas fa-plus mr-2"></i> Register New Vehicle
                         </a>
                    </div>

                    <?php if ($vehicles && $vehicles->num_rows > 0): ?>
                         <div class="bg-white rounded-lg shadow-md overflow-hidden">
                              <div class="overflow-x-auto">
                                   <table class="min-w-full">
                                        <thead class="bg-gray-50">
                                             <tr>
                                                  <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">License Plate</th>
                                                  <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Make/Model</th>
                                                  <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Owner</th>
                                                  <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                                  <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Year</th>
                                                  <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                             </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200">
                                             <?php while ($vehicle = $vehicles->fetch_assoc()): ?>
                                                  <tr>
                                                       <td class="py-4 px-4 whitespace-nowrap font-medium"><?php echo htmlspecialchars($vehicle['nopol']); ?></td>
                                                       <td class="py-4 px-4 whitespace-nowrap"><?php echo htmlspecialchars($vehicle['merk']); ?></td>
                                                       <td class="py-4 px-4 whitespace-nowrap"><?php echo htmlspecialchars($vehicle['pemilik']); ?></td>
                                                       <td class="py-4 px-4 whitespace-nowrap"><?php echo htmlspecialchars($vehicle['jenis_nama']); ?></td>
                                                       <td class="py-4 px-4 whitespace-nowrap"><?php echo htmlspecialchars($vehicle['thn_beli']); ?></td>
                                                       <td class="py-4 px-4 whitespace-nowrap space-x-2">
                                                            <a href="#" class="text-red-600 hover:text-red-900" onclick="confirmDelete(<?php echo $vehicle['id']; ?>, '<?php echo htmlspecialchars($vehicle['nopol']); ?>')">
                                                                 <i class="fas fa-trash"></i> Delete
                                                            </a>
                                                       </td>
                                                  </tr>
                                             <?php endwhile; ?>
                                        </tbody>
                                   </table>
                              </div>
                         </div>
                    <?php else: ?>
                         <div class="bg-white rounded-lg shadow-md p-8 text-center">
                              <i class="fas fa-car text-5xl text-gray-300 mb-4"></i>
                              <h3 class="text-xl font-bold mb-2">No Vehicles Registered</h3>
                              <p class="text-gray-600 mb-6">You haven't registered any vehicles yet. Register a vehicle to start using the parking services.</p>
                              <a href="register-vehicle.php" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-md">
                                   Register Your First Vehicle
                              </a>
                         </div>
                    <?php endif; ?>
               <?php endif; ?>
          </div>
     </section>

     <!-- Parking History Section - Only show if logged in -->
     <?php if ($is_logged_in): ?>
          <section class="py-8 bg-gray-50">
               <div class="container mx-auto px-4">
                    <h2 class="text-2xl font-bold mb-6">Recent Parking History</h2>

                    <?php if ($transactions && $transactions->num_rows > 0): ?>
                         <div class="bg-white rounded-lg shadow-md overflow-hidden">
                              <div class="overflow-x-auto">
                                   <table class="min-w-full">
                                        <thead class="bg-gray-50">
                                             <tr>
                                                  <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                                  <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vehicle</th>
                                                  <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                                                  <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                                  <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fee</th>
                                             </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200">
                                             <?php while ($transaction = $transactions->fetch_assoc()): ?>
                                                  <tr>
                                                       <td class="py-4 px-4 whitespace-nowrap">
                                                            <?php echo date('d M Y', strtotime($transaction['tanggal'])); ?>
                                                       </td>
                                                       <td class="py-4 px-4 whitespace-nowrap">
                                                            <?php echo htmlspecialchars($transaction['nopol']); ?> - <?php echo htmlspecialchars($transaction['merk']); ?>
                                                       </td>
                                                       <td class="py-4 px-4 whitespace-nowrap">
                                                            <?php echo htmlspecialchars($transaction['area_nama']); ?> (<?php echo htmlspecialchars($transaction['kampus_nama']); ?>)
                                                       </td>
                                                       <td class="py-4 px-4 whitespace-nowrap">
                                                            <?php
                                                            echo date('H:i', strtotime($transaction['mulai'])) . ' - ' .
                                                                 date('H:i', strtotime($transaction['akhir']));
                                                            ?>
                                                       </td>
                                                       <td class="py-4 px-4 whitespace-nowrap">
                                                            Rp <?php echo number_format($transaction['biaya'], 0, ',', '.'); ?>
                                                       </td>
                                                  </tr>
                                             <?php endwhile; ?>
                                        </tbody>
                                   </table>
                              </div>
                              <div class="bg-gray-50 px-4 py-3 text-right">
                                   <a href="parking-history.php" class="text-blue-600 hover:text-blue-900 font-medium">
                                        View Full History <i class="fas fa-arrow-right ml-1"></i>
                                   </a>
                              </div>
                         </div>
                    <?php else: ?>
                         <div class="bg-white rounded-lg shadow-md p-6 text-center">
                              <p class="text-gray-600">No parking history available.</p>
                         </div>
                    <?php endif; ?>
               </div>
          </section>
     <?php endif; ?>

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
          function confirmDelete(vehicleId, licensePlate) {
               if (confirm(`Are you sure you want to delete vehicle with license plate ${licensePlate}?`)) {
                    window.location.href = `my-vehicles.php?delete=${vehicleId}`;
               }
          }
     </script>
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