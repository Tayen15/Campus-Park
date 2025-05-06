<?php
// Database connection
include_once 'koneksi.php';

// initialize variables
$user_id = null;
$is_logged_in = false;

// Check if user is logged in
session_start();
if (isset($_SESSION['user_id'])) {
     $user_id = $_SESSION['user_id'];
     $is_logged_in = true;
}

// Get all campuses
$campuses = $conn->query("SELECT * FROM kampus ORDER BY nama");

// Get parking areas with availability info
$query = "
    SELECT a.id, a.nama, a.kapasitas, a.keterangan, 
           k.nama as kampus_nama, k.id as kampus_id,
           (SELECT COUNT(*) FROM transaksi t 
            WHERE t.area_parkir_id = a.id 
            AND DATE(t.tanggal) = CURDATE() 
            AND ((CURTIME() BETWEEN t.mulai AND t.akhir) 
                OR (t.akhir < t.mulai AND (CURTIME() BETWEEN t.mulai AND '23:59:59' 
                                          OR CURTIME() BETWEEN '00:00:00' AND t.akhir)))
           ) as used_spaces
    FROM area_parkir a
    JOIN kampus k ON a.kampus_id = k.id
";

// If a campus filter is applied
if (isset($_GET['campus']) && !empty($_GET['campus'])) {
     $campus_id = intval($_GET['campus']);
     $query .= " WHERE k.id = $campus_id";
}

$query .= " ORDER BY k.nama, a.nama";
$parkingAreas = $conn->query($query);

// Check if search was performed
$searchPerformed = isset($_GET['campus']) || isset($_GET['keyword']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
     <meta charset="UTF-8">
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <title>Find Parking - Campus Parking Management System</title>
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
                         <a href="find-parking.php" class="border-b-2 border-white hover:text-blue-200">Find Parking</a>
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

     <!-- Page Header -->
     <header class="bg-blue-700 text-white py-10">
          <div class="container mx-auto px-4">
               <h1 class="text-3xl font-bold mb-2">Find Available Parking</h1>
               <p class="text-lg">Locate and reserve parking spaces across all campus locations.</p>
          </div>
     </header>

     <!-- Search Form -->
     <section class="py-8">
          <div class="container mx-auto px-4">
               <div class="bg-white rounded-lg shadow-md p-6">
                    <form action="find-parking.php" method="GET" class="flex flex-col md:flex-row md:items-end space-y-4 md:space-y-0 md:space-x-4">
                         <div class="flex-1">
                              <label for="campus" class="block text-sm font-medium text-gray-700 mb-1">Campus</label>
                              <select name="campus" id="campus" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                   <option value="">All Campuses</option>
                                   <?php if ($campuses->num_rows > 0): ?>
                                        <?php while ($campus = $campuses->fetch_assoc()): ?>
                                             <option value="<?php echo $campus['id']; ?>" <?php echo (isset($_GET['campus']) && $_GET['campus'] == $campus['id']) ? 'selected' : ''; ?>>
                                                  <?php echo $campus['nama']; ?>
                                             </option>
                                        <?php endwhile; ?>
                                   <?php endif; ?>
                              </select>
                         </div>
                         <div class="flex-1">
                              <label for="keyword" class="block text-sm font-medium text-gray-700 mb-1">Search by Keyword</label>
                              <input type="text" name="keyword" id="keyword" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" placeholder="Enter keywords" value="<?php echo isset($_GET['keyword']) ? htmlspecialchars($_GET['keyword']) : ''; ?>">
                         </div>
                         <div>
                              <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-md">
                                   <i class="fas fa-search mr-2"></i> Search
                              </button>
                         </div>
                    </form>
               </div>
          </div>
     </section>

     <!-- Map View (Placeholder) -->
     <section class="py-6">
          <div class="container mx-auto px-4">
               <div class="bg-white rounded-lg shadow-md p-4 mb-8">
                    <div class="bg-gray-200 rounded-lg h-64 flex items-center justify-center">
                         <div class="text-center">
                              <i class="fas fa-map-marked-alt text-4xl text-gray-500 mb-2"></i>
                              <p class="text-gray-600">Interactive campus map would be displayed here.</p>
                              <p class="text-gray-500 text-sm">Map shows parking locations and real-time availability</p>
                         </div>
                    </div>
               </div>
          </div>
     </section>

     <!-- Results Section -->
     <section class="py-6">
          <div class="container mx-auto px-4">
               <h2 class="text-2xl font-bold mb-6">Available Parking Areas</h2>

               <?php if ($searchPerformed && $parkingAreas->num_rows == 0): ?>
                    <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4 text-center">
                         <p class="text-yellow-700">No parking areas match your search criteria. Please try different search terms.</p>
                    </div>
               <?php elseif ($parkingAreas->num_rows > 0): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                         <?php while ($area = $parkingAreas->fetch_assoc()): ?>
                              <?php
                              $used = $area['used_spaces'];
                              $capacity = $area['kapasitas'];
                              $available = $capacity - $used;
                              $availabilityPercentage = ($available / $capacity) * 100;

                              // Determine color based on availability
                              $bgColor = 'bg-red-600';
                              $borderColor = 'border-red-200';
                              $bgCardColor = 'bg-red-50';

                              if ($availabilityPercentage > 30) {
                                   $bgColor = 'bg-yellow-500';
                                   $borderColor = 'border-yellow-200';
                                   $bgCardColor = 'bg-yellow-50';
                              }
                              if ($availabilityPercentage > 60) {
                                   $bgColor = 'bg-green-500';
                                   $borderColor = 'border-green-200';
                                   $bgCardColor = 'bg-green-50';
                              }
                              ?>
                              <div class="border <?php echo $borderColor; ?> rounded-lg overflow-hidden shadow-md">
                                   <div class="<?php echo $bgCardColor; ?> p-4">
                                        <h3 class="text-lg font-bold mb-1"><?php echo $area['nama']; ?></h3>
                                        <p class="text-gray-600 text-sm mb-2"><?php echo $area['kampus_nama']; ?></p>
                                        <p class="text-gray-700 mb-4"><?php echo $area['keterangan']; ?></p>

                                        <div class="flex items-center justify-between mb-2">
                                             <div class="text-sm text-gray-700">Availability:</div>
                                             <div class="font-medium"><?php echo $available; ?> of <?php echo $capacity; ?> spaces</div>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2.5 mb-4">
                                             <div class="<?php echo $bgColor; ?> h-2.5 rounded-full" style="width: <?php echo $availabilityPercentage; ?>%"></div>
                                        </div>

                                        <div class="flex space-x-2">
                                             <a href="reserve-parking.php?area=<?php echo $area['id']; ?>" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-center py-2 px-4 rounded-md">
                                                  Reserve
                                             </a>
                                             <a href="#" class="flex-1 border border-blue-600 text-blue-600 hover:bg-blue-50 text-center py-2 px-4 rounded-md">
                                                  Details
                                             </a>
                                        </div>
                                   </div>
                              </div>
                         <?php endwhile; ?>
                    </div>
               <?php else: ?>
                    <div class="bg-gray-50 border border-gray-200 rounded-md p-4 text-center">
                         <p class="text-gray-600">Use the search form above to find available parking areas.</p>
                    </div>
               <?php endif; ?>
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