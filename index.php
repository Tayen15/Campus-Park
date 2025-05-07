<?php
include_once 'koneksi.php';

// Initialize variables
$user_id = null;
$is_logged_in = false;

// Check if user is logged in
session_start();
if (isset($_SESSION['user_id'])) {
     $user_id = $_SESSION['user_id'];
     $is_logged_in = true;
}

// Fetch summary data
$totalCampuses = $conn->query("SELECT COUNT(*) as count FROM kampus")->fetch_assoc()['count'];
$totalAreas = $conn->query("SELECT COUNT(*) as count FROM area_parkir")->fetch_assoc()['count'];
$totalVehicles = $conn->query("SELECT COUNT(*) as count FROM kendaraan")->fetch_assoc()['count'];
$totalTransactions = $conn->query("SELECT COUNT(*) as count FROM transaksi")->fetch_assoc()['count'];

// Get recent transactions
$recentTransactions = $conn->query("
    SELECT t.id, t.tanggal, t.mulai, t.akhir, t.biaya, k.nopol, k.merk, a.nama as area_nama, ka.nama as kampus_nama
    FROM transaksi t
    JOIN kendaraan k ON t.kendaraan_id = k.id
    JOIN area_parkir a ON t.area_parkir_id = a.id
    JOIN kampus ka ON a.kampus_id = ka.id
    ORDER BY t.tanggal DESC, t.mulai DESC
    LIMIT 5
");

// Get available parking areas
$parkingAreas = $conn->query("
    SELECT a.id, a.nama, a.kapasitas, a.keterangan, ka.nama as kampus_nama,
    (SELECT COUNT(*) FROM transaksi t WHERE t.area_parkir_id = a.id AND DATE(t.tanggal) = CURDATE() AND 
    ((CURTIME() BETWEEN t.mulai AND t.akhir) OR (t.akhir < t.mulai AND (CURTIME() BETWEEN t.mulai AND '23:59:59' OR CURTIME() BETWEEN '00:00:00' AND t.akhir)))) as used_spaces
    FROM area_parkir a
    JOIN kampus ka ON a.kampus_id = ka.id
    ORDER BY ka.nama, a.nama
");

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
     <meta charset="UTF-8">
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <title>Campus Parking Management System</title>
     <!-- Tailwind CSS via CDN -->
     <script src="https://cdn.tailwindcss.com"></script>
     <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
     <style>
          .hero-pattern {
               background-color: #1e40af;
               background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='80' height='80' viewBox='0 0 80 80'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath fill-rule='evenodd' d='M0 0h40v40H0V0zm40 40h40v40H40V40zm0-40h2l-2 2V0zm0 4l4-4h2l-6 6V4zm0 4l8-8h2L40 10V8zm0 4L52 0h2L40 14v-2zm0 4L56 0h2L40 18v-2zm0 4L60 0h2L40 22v-2zm0 4L64 0h2L40 26v-2zm0 4L68 0h2L40 30v-2zm0 4L72 0h2L40 34v-2zm0 4L76 0h2L40 38v-2zm0 4L80 0v2L42 40h-2zm4 0L80 4v2L46 40h-2zm4 0L80 8v2L50 40h-2zm4 0l28-28v2L54 40h-2zm4 0l24-24v2L58 40h-2zm4 0l20-20v2L62 40h-2zm4 0l16-16v2L66 40h-2zm4 0l12-12v2L70 40h-2zm4 0l8-8v2l-6 6h-2zm4 0l4-4v2l-2 2h-2z'/%3E%3C/g%3E%3C/svg%3E");
          }
     </style>
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

     <!-- Hero Section -->
     <header class="hero-pattern text-white py-16">
          <div class="container mx-auto px-4">
               <div class="flex flex-col md:flex-row items-center">
                    <div class="md:w-1/2 mb-8 md:mb-0">
                         <h1 class="text-4xl font-bold leading-tight mb-4">Smart Campus Parking Management</h1>
                         <p class="text-xl mb-6">Easily find, reserve, and manage parking spaces across all campus locations.</p>
                         <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-4">
                              <a href="find-parking.php" class="bg-white text-blue-800 hover:bg-blue-100 font-bold py-3 px-6 rounded-lg text-center">Find Parking</a>
                              <a href="register-vehicle.php" class="border border-white hover:bg-blue-700 font-bold py-3 px-6 rounded-lg text-center">Register Vehicle</a>
                         </div>
                    </div>
                    <div class="md:w-1/2 flex justify-center">
                         <img src="/images/parkiran.JPG" alt="Campus Parking" class="rounded-lg shadow-xl">
                    </div>
               </div>
          </div>
     </header>

     <!-- Stats Section -->
     <section class="py-10 bg-white">
          <div class="container mx-auto px-4">
               <div class="flex flex-wrap -mx-4">
                    <div class="w-full md:w-1/4 px-4 mb-6">
                         <div class="bg-blue-50 rounded-lg p-6 text-center shadow-md">
                              <div class="text-4xl font-bold text-blue-800 mb-2"><?php echo $totalCampuses; ?></div>
                              <div class="text-gray-600">Campuses</div>
                         </div>
                    </div>
                    <div class="w-full md:w-1/4 px-4 mb-6">
                         <div class="bg-blue-50 rounded-lg p-6 text-center shadow-md">
                              <div class="text-4xl font-bold text-blue-800 mb-2"><?php echo $totalAreas; ?></div>
                              <div class="text-gray-600">Parking Areas</div>
                         </div>
                    </div>
                    <div class="w-full md:w-1/4 px-4 mb-6">
                         <div class="bg-blue-50 rounded-lg p-6 text-center shadow-md">
                              <div class="text-4xl font-bold text-blue-800 mb-2"><?php echo $totalVehicles; ?></div>
                              <div class="text-gray-600">Registered Vehicles</div>
                         </div>
                    </div>
                    <div class="w-full md:w-1/4 px-4 mb-6">
                         <div class="bg-blue-50 rounded-lg p-6 text-center shadow-md">
                              <div class="text-4xl font-bold text-blue-800 mb-2"><?php echo $totalTransactions; ?></div>
                              <div class="text-gray-600">Total Transactions</div>
                         </div>
                    </div>
               </div>
          </div>
     </section>

     <!-- Parking Availability Section -->
     <section class="py-12 bg-gray-100">
          <div class="container mx-auto px-4">
               <h2 class="text-3xl font-bold text-center mb-10">Parking Availability</h2>

               <div class="overflow-x-auto bg-white rounded-lg shadow-lg">
                    <table class="min-w-full">
                         <thead>
                              <tr class="bg-blue-800 text-white">
                                   <th class="py-3 px-4 text-left">Campus</th>
                                   <th class="py-3 px-4 text-left">Parking Area</th>
                                   <th class="py-3 px-4 text-left">Description</th>
                                   <th class="py-3 px-4 text-center">Availability</th>
                                   <th class="py-3 px-4 text-center">Action</th>
                              </tr>
                         </thead>
                         <tbody class="divide-y divide-gray-200">
                              <?php if ($parkingAreas && $parkingAreas->num_rows > 0): ?>
                                   <?php while ($area = $parkingAreas->fetch_assoc()): ?>
                                        <?php
                                        $used = $area['used_spaces'];
                                        $capacity = $area['kapasitas'];
                                        $available = $capacity - $used;
                                        $availabilityPercentage = ($available / $capacity) * 100;

                                        // Determine color based on availability
                                        $bgColor = 'bg-red-600';
                                        $textColor = 'text-red-800';
                                        if ($availabilityPercentage > 30) {
                                             $bgColor = 'bg-yellow-500';
                                             $textColor = 'text-yellow-800';
                                        }
                                        if ($availabilityPercentage > 60) {
                                             $bgColor = 'bg-green-500';
                                             $textColor = 'text-green-800';
                                        }
                                        ?>
                                        <tr class="hover:bg-gray-50">
                                             <td class="py-3 px-4"><?php echo $area['kampus_nama']; ?></td>
                                             <td class="py-3 px-4 font-medium"><?php echo $area['nama']; ?></td>
                                             <td class="py-3 px-4"><?php echo $area['keterangan']; ?></td>
                                             <td class="py-3 px-4">
                                                  <div class="flex items-center justify-center">
                                                       <div class="mr-2 font-medium <?php echo $textColor; ?>">
                                                            <?php echo $available; ?>/<?php echo $capacity; ?>
                                                       </div>
                                                       <div class="w-24 bg-gray-200 rounded-full h-2.5">
                                                            <div class="<?php echo $bgColor; ?> h-2.5 rounded-full" style="width: <?php echo $availabilityPercentage; ?>%"></div>
                                                       </div>
                                                  </div>
                                             </td>
                                             <td class="py-3 px-4 text-center">
                                                  <a href="reserve-parking.php" class="bg-blue-600 hover:bg-blue-700 text-white py-1 px-3 rounded-lg text-sm">Reserve</a>
                                             </td>
                                        </tr>
                                   <?php endwhile; ?>
                              <?php else: ?>
                                   <tr>
                                        <td colspan="5" class="py-4 px-4 text-center text-gray-500">No parking areas available</td>
                                   </tr>
                              <?php endif; ?>
                         </tbody>
                    </table>
               </div>
          </div>
     </section>

     <!-- Recent Transactions -->
     <section class="py-12 bg-white">
          <div class="container mx-auto px-4">
               <h2 class="text-3xl font-bold text-center mb-10">Recent Parking Activity</h2>

               <div class="overflow-x-auto bg-white rounded-lg shadow-lg">
                    <table class="min-w-full">
                         <thead>
                              <tr class="bg-blue-800 text-white">
                                   <th class="py-3 px-4 text-left">Date</th>
                                   <th class="py-3 px-4 text-left">Vehicle</th>
                                   <th class="py-3 px-4 text-left">Location</th>
                                   <th class="py-3 px-4 text-left">Time</th>
                                   <th class="py-3 px-4 text-right">Fee</th>
                              </tr>
                         </thead>
                         <tbody class="divide-y divide-gray-200">
                              <?php if ($recentTransactions && $recentTransactions->num_rows > 0): ?>
                                   <?php while ($transaction = $recentTransactions->fetch_assoc()): ?>
                                        <?php
                                        $date = date('M d, Y', strtotime($transaction['tanggal']));
                                        $start = date('H:i', strtotime($transaction['mulai']));
                                        $end = date('H:i', strtotime($transaction['akhir']));
                                        ?>
                                        <tr class="hover:bg-gray-50">
                                             <td class="py-3 px-4"><?php echo $date; ?></td>
                                             <td class="py-3 px-4">
                                                  <div class="font-medium"><?php echo $transaction['nopol']; ?></div>
                                                  <div class="text-gray-500 text-sm"><?php echo $transaction['merk']; ?></div>
                                             </td>
                                             <td class="py-3 px-4">
                                                  <div><?php echo $transaction['area_nama']; ?></div>
                                                  <div class="text-gray-500 text-sm"><?php echo $transaction['kampus_nama']; ?></div>
                                             </td>
                                             <td class="py-3 px-4"><?php echo $start; ?> - <?php echo $end; ?></td>
                                             <td class="py-3 px-4 text-right font-medium">Rp <?php echo number_format($transaction['biaya'], 0, ',', '.'); ?></td>
                                        </tr>
                                   <?php endwhile; ?>
                              <?php else: ?>
                                   <tr>
                                        <td colspan="5" class="py-4 px-4 text-center text-gray-500">No recent transactions</td>
                                   </tr>
                              <?php endif; ?>
                         </tbody>
                    </table>
               </div>
          </div>
     </section>

     <!-- Features Section -->
     <section class="py-12 bg-gray-100">
          <div class="container mx-auto px-4">
               <h2 class="text-3xl font-bold text-center mb-10">Our Features</h2>

               <div class="flex flex-wrap -mx-4">
                    <div class="w-full md:w-1/3 px-4 mb-8">
                         <div class="bg-white rounded-lg p-6 h-full shadow-md">
                              <div class="text-blue-800 mb-4">
                                   <i class="fas fa-map-marker-alt text-4xl"></i>
                              </div>
                              <h3 class="text-xl font-bold mb-2">Real-time Availability</h3>
                              <p class="text-gray-600">Monitor parking space availability across all campus locations in real-time. Know exactly where to park before you arrive.</p>
                         </div>
                    </div>
                    <div class="w-full md:w-1/3 px-4 mb-8">
                         <div class="bg-white rounded-lg p-6 h-full shadow-md">
                              <div class="text-blue-800 mb-4">
                                   <i class="fas fa-calendar-alt text-4xl"></i>
                              </div>
                              <h3 class="text-xl font-bold mb-2">Parking Reservations</h3>
                              <p class="text-gray-600">Reserve your parking spot in advance for important events or busy days. Guarantees you'll have a place to park.</p>
                         </div>
                    </div>
                    <div class="w-full md:w-1/3 px-4 mb-8">
                         <div class="bg-white rounded-lg p-6 h-full shadow-md">
                              <div class="text-blue-800 mb-4">
                                   <i class="fas fa-credit-card text-4xl"></i>
                              </div>
                              <h3 class="text-xl font-bold mb-2">Easy Payment System</h3>
                              <p class="text-gray-600">Cashless payment options for all parking transactions. Pay using your student account or digital payment methods.</p>
                         </div>
                    </div>
               </div>
          </div>
     </section>

     <!-- CTA Section -->
     <section class="bg-blue-800 text-white py-12">
          <div class="container mx-auto px-4 text-center">
               <h2 class="text-3xl font-bold mb-4">Ready to simplify your campus parking?</h2>
               <p class="text-xl mb-8 max-w-2xl mx-auto">Register your vehicle and start enjoying hassle-free parking across all our campus locations.</p>
               <a href="login.php" class="bg-white text-blue-800 hover:bg-blue-100 font-bold py-3 px-8 rounded-lg inline-block">Get Started Today</a>
          </div>
     </section>

     <!-- Footer -->
     <footer class="bg-gray-800 text-gray-300 py-8">
          <div class="container mx-auto px-4">
               <div class="flex flex-col md:flex-row justify-between mb-6">
                    <div class="mb-6 md:mb-0">
                         <div class="flex items-center mb-4">
                              <i class="fas fa-parking text-2xl mr-2 text-white"></i>
                              <span class="font-bold text-xl text-white">CampusPark</span>
                         </div>
                         <p class="max-w-md">The comprehensive solution for campus parking management. Simplifying the parking experience for students, staff, and visitors.</p>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-8">
                         <div>
                              <h3 class="text-white font-semibold mb-4">Quick Links</h3>
                              <ul class="space-y-2">
                                   <li><a href="index.php" class="hover:text-white">Home</a></li>
                                   <li><a href="find-parking.php" class="hover:text-white">Parking Areas</a></li>
                                   <li><a href="my-vehicles.php" class="hover:text-white">Vehicles</a></li>
                                   <li><a href="my-vehicles.php" class="hover:text-white">Transactions</a></li>
                              </ul>
                         </div>
                         <div>
                              <h3 class="text-white font-semibold mb-4">Support</h3>
                              <ul class="space-y-2">
                                   <li><a href="#" class="hover:text-white">Help Center</a></li>
                                   <li><a href="#" class="hover:text-white">Contact Us</a></li>
                                   <li><a href="#" class="hover:text-white">Privacy Policy</a></li>
                                   <li><a href="#" class="hover:text-white">Terms of Service</a></li>
                              </ul>
                         </div>
                         <div class="col-span-2 md:col-span-1">
                              <h3 class="text-white font-semibold mb-4">Contact</h3>
                              <ul class="space-y-2">
                                   <li><i class="fas fa-envelope mr-2"></i> noreply@oktaa.my.id </li>
                                   <li><i class="fas fa-phone mr-2"></i> +62 1131 5202</li>
                                   <li><i class="fas fa-map-marker-alt mr-2"></i> STT Terpadu Nurul Fikri, Building A</li>
                              </ul>
                         </div>
                    </div>
               </div>
               <div class="border-t border-gray-700 pt-6 text-center">
                    <p>&copy; <?php echo date('Y'); ?> <a href="https://github.com/Tayen15/Campus-Park" >CampusPark</a>. All rights reserved.</p>
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