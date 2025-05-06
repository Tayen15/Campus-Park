<?php
// Database connection
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

// Get area ID from URL if present
$area_id = isset($_GET['area']) ? intval($_GET['area']) : null;
$area = null;

// Get all campuses for the dropdown
$campuses = $conn->query("SELECT * FROM kampus ORDER BY nama");

// Get user's vehicles filtered by user ID with type and fee
$vehicles_query = "SELECT k.*, j.nama AS jenis_nama, j.fee AS jenis_fee 
                  FROM kendaraan k 
                  JOIN jenis j ON k.jenis_kendaraan_id = j.id 
                  WHERE k.user_id = ? 
                  ORDER BY k.merk, k.nopol";
$stmt = $conn->prepare($vehicles_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$vehicles = $stmt->get_result();
$stmt->close();

// Handle form submission
$success_message = null;
$error_message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_logged_in) {
     // Process the reservation
     $selected_area = intval($_POST['area_id']);
     $selected_vehicle = intval($_POST['vehicle_id']);
     $reservation_date = $_POST['date'];
     $start_time = $_POST['start_time'];
     $end_time = $_POST['end_time'];
     $notes = $_POST['notes'];

     // Get the fee for the selected vehicle type
     $stmt = $conn->prepare("
        SELECT j.fee 
        FROM kendaraan k 
        JOIN jenis j ON k.jenis_kendaraan_id = j.id 
        WHERE k.id = ? AND k.user_id = ?
    ");
     $stmt->bind_param("ii", $selected_vehicle, $user_id);
     $stmt->execute();
     $result = $stmt->get_result();
     $vehicle_type = $result->fetch_assoc();
     $stmt->close();

     if ($vehicle_type) {
          // Calculate fee based on duration and vehicle type fee
          $start = strtotime($start_time);
          $end = strtotime($end_time);
          $duration_hours = ($end - $start) / 3600;
          if ($duration_hours < 0) {
               $duration_hours += 24; // Handle cases where end time is past midnight
          }
          $hourly_rate = $vehicle_type['fee']; // Fee per hour from jenis table
          $fee = ceil($duration_hours) * $hourly_rate; // Round up to the nearest hour

          // Insert reservation into database
          $sql = "INSERT INTO transaksi (tanggal, mulai, akhir, keterangan, biaya, kendaraan_id, area_parkir_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";

          $stmt = $conn->prepare($sql);
          $stmt->bind_param("ssssdii", $reservation_date, $start_time, $end_time, $notes, $fee, $selected_vehicle, $selected_area);

          if ($stmt->execute()) {
               $success_message = "Parking reservation successful! Your reservation ID is: " . $stmt->insert_id . ". Total fee: Rp " . number_format($fee, 0, ',', '.');
          } else {
               $error_message = "Error making reservation: " . $stmt->error;
          }

          $stmt->close();
     } else {
          $error_message = "Invalid vehicle selected.";
     }
}

// If area ID is provided, get area details
if ($area_id) {
     $area_query = "
        SELECT a.*, k.nama as kampus_nama,
        (SELECT COUNT(*) FROM transaksi t 
         WHERE t.area_parkir_id = a.id 
         AND DATE(t.tanggal) = CURDATE() 
         AND ((CURTIME() BETWEEN t.mulai AND t.akhir) 
             OR (t.akhir < t.mulai AND (CURTIME() BETWEEN t.mulai AND '23:59:59' 
                                       OR CURTIME() BETWEEN '00:00:00' AND t.akhir)))
        ) as used_spaces
        FROM area_parkir a
        JOIN kampus k ON a.kampus_id = k.id
        WHERE a.id = ?
    ";

     $stmt = $conn->prepare($area_query);
     $stmt->bind_param("i", $area_id);
     $stmt->execute();
     $result = $stmt->get_result();

     if ($result->num_rows > 0) {
          $area = $result->fetch_assoc();
     }

     $stmt->close();
}

// Get all parking areas for dropdown
$parking_areas = $conn->query("
    SELECT a.id, a.nama, k.nama AS kampus_nama 
    FROM area_parkir a
    JOIN kampus k ON a.kampus_id = k.id
    ORDER BY k.nama, a.nama
");
?>

<!DOCTYPE html>
<html lang="en">

<head>
     <meta charset="UTF-8">
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <title>Reserve Parking - Campus Parking Management System</title>
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
                         <a href="reserve-parking.php" class="border-b-2 border-white hover:text-blue-200">Reserve</a>
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
               <h1 class="text-3xl font-bold mb-2">Reserve Parking Space</h1>
               <p class="text-lg">Secure your parking spot in advance for hassle-free campus visits.</p>
          </div>
     </header>

     <!-- Reservation Form Section -->
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
                                   <p class="mt-2">Thank you for using CampusPark! Your reservation has been confirmed.</p>
                                   <div class="mt-4">
                                        <a href="index.php" class="text-sm font-medium text-green-600 hover:text-green-800">Back to Home</a>
                                        <span class="mx-2">|</span>
                                        <a href="find-parking.php" class="text-sm font-medium text-green-600 hover:text-green-800">Find More Parking</a>
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
                                   <p class="font-medium">There was an error processing your reservation:</p>
                                   <p class="mt-1"><?php echo $error_message; ?></p>
                              </div>
                         </div>
                    </div>
               <?php endif; ?>

               <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="md:flex">
                         <!-- Left side - form or login message -->
                         <div class="md:w-2/3 p-6">
                              <h2 class="text-2xl font-bold mb-6">Reservation Details</h2>

                              <?php if (!$is_logged_in): ?>
                                   <!-- Show login message for guests -->
                                   <div class="p-8 text-center">
                                        <i class="fas fa-user-lock text-5xl text-gray-300 mb-4"></i>
                                        <h3 class="text-xl font-bold mb-2">Login Required</h3>
                                        <p class="text-gray-600 mb-6">Please login to view and manage your vehicles.</p>
                                        <a href="login.php" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-md">
                                             Login Now
                                        </a>
                                   </div>
                              <?php else: ?>
                                   <form action="reserve-parking.php" method="POST">
                                        <!-- Area selection -->
                                        <div class="mb-4">
                                             <label for="area_id" class="block text-sm font-medium text-gray-700 mb-1">Parking Area</label>
                                             <select name="area_id" id="area_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" required>
                                                  <option value="">-- Select Parking Area --</option>
                                                  <?php if ($parking_areas && $parking_areas->num_rows > 0): ?>
                                                       <?php while ($parking_area = $parking_areas->fetch_assoc()): ?>
                                                            <option value="<?php echo $parking_area['id']; ?>" <?php echo ($area && $area['id'] == $parking_area['id']) ? 'selected' : ''; ?>>
                                                                 <?php echo htmlspecialchars($parking_area['nama']); ?> (<?php echo htmlspecialchars($parking_area['kampus_nama']); ?>)
                                                            </option>
                                                       <?php endwhile; ?>
                                                  <?php endif; ?>
                                             </select>
                                        </div>

                                        <!-- Vehicle selection -->
                                        <div class="mb-4">
                                             <label for="vehicle_id" class="block text-sm font-medium text-gray-700 mb-1">Vehicle</label>
                                             <select name="vehicle_id" id="vehicle_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" required>
                                                  <option value="">-- Select Vehicle --</option>
                                                  <?php if ($vehicles && $vehicles->num_rows > 0): ?>
                                                       <?php while ($vehicle = $vehicles->fetch_assoc()): ?>
                                                            <option value="<?php echo $vehicle['id']; ?>" data-fee="<?php echo $vehicle['jenis_fee']; ?>">
                                                                 <?php echo htmlspecialchars($vehicle['nopol']); ?> - <?php echo htmlspecialchars($vehicle['merk']); ?> (<?php echo htmlspecialchars($vehicle['jenis_nama']); ?>)
                                                            </option>
                                                       <?php endwhile; ?>
                                                  <?php endif; ?>
                                             </select>
                                             <div class="mt-1 text-sm text-gray-500">
                                                  Don't see your vehicle? <a href="register-vehicle.php" class="text-blue-600 hover:underline">Register a new vehicle</a>
                                             </div>
                                        </div>

                                        <!-- Date and time selection -->
                                        <div class="mb-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                                             <div>
                                                  <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                                                  <input type="date" name="date" id="date" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" required min="<?php echo date('Y-m-d'); ?>">
                                             </div>
                                             <div>
                                                  <label for="start_time" class="block text-sm font-medium text-gray-700 mb-1">Start Time</label>
                                                  <input type="time" name="start_time" id="start_time" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" required>
                                             </div>
                                             <div>
                                                  <label for="end_time" class="block text-sm font-medium text-gray-700 mb-1">End Time</label>
                                                  <input type="time" name="end_time" id="end_time" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" required>
                                             </div>
                                        </div>

                                        <!-- Fee display -->
                                        <div class="mb-4">
                                             <label class="block text-sm font-medium text-gray-700 mb-1">Estimated Fee</label>
                                             <div id="fee-display" class="text-lg font-medium text-blue-600">Rp 0</div>
                                        </div>

                                        <!-- Notes -->
                                        <div class="mb-6">
                                             <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes (Optional)</label>
                                             <textarea name="notes" id="notes" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" placeholder="Add any special requirements or notes..."></textarea>
                                        </div>

                                        <!-- Submit button -->
                                        <div>
                                             <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-md transition duration-150 ease-in-out">
                                                  Complete Reservation
                                             </button>
                                        </div>
                                   </form>
                              <?php endif; ?>
                         </div>

                         <!-- Right side - area details or information -->
                         <div class="md:w-1/3 bg-gray-50 p-6 border-l border-gray-200">
                              <?php if ($area): ?>
                                   <h3 class="text-lg font-bold mb-2"><?php echo htmlspecialchars($area['nama']); ?></h3>
                                   <p class="text-gray-600 text-sm mb-4"><?php echo htmlspecialchars($area['kampus_nama']); ?></p>

                                   <div class="mb-4">
                                        <div class="text-sm text-gray-700 mb-1">Description:</div>
                                        <p><?php echo htmlspecialchars($area['keterangan']); ?></p>
                                   </div>

                                   <div class="mb-4">
                                        <div class="text-sm text-gray-700 mb-1">Capacity:</div>
                                        <div class="font-medium"><?php echo $area['kapasitas']; ?> spaces</div>
                                   </div>

                                   <div class="mb-4">
                                        <div class="text-sm text-gray-700 mb-1">Current Availability:</div>
                                        <div class="font-medium">
                                             <?php
                                             $available = $area['kapasitas'] - $area['used_spaces'];
                                             echo $available . " of " . $area['kapasitas'] . " spaces";
                                             ?>
                                        </div>
                                        <?php
                                        $availabilityPercentage = ($available / $area['kapasitas']) * 100;
                                        $bgColor = 'bg-red-600';
                                        if ($availabilityPercentage > 30) $bgColor = 'bg-yellow-500';
                                        if ($availabilityPercentage > 60) $bgColor = 'bg-green-500';
                                        ?>
                                        <div class="w-full bg-gray-200 rounded-full h-2.5 mt-2">
                                             <div class="<?php echo $bgColor; ?> h-2.5 rounded-full" style="width: <?php echo $availabilityPercentage; ?>%"></div>
                                        </div>
                                   </div>
                              <?php else: ?>
                                   <div class="text-center py-8">
                                        <i class="fas fa-info-circle text-4xl text-blue-300 mb-3"></i>
                                        <h3 class="text-lg font-bold mb-2">Parking Information</h3>
                                        <p class="text-gray-600 mb-4">Select a parking area to see details and availability.</p>
                                        <a href="find-parking.php" class="text-blue-600 hover:underline">Find Parking Areas</a>
                                   </div>

                                   <div class="border-t border-gray-200 mt-8 pt-6">
                                        <h4 class="font-medium mb-2">Reservation Guidelines:</h4>
                                        <ul class="text-sm text-gray-600 space-y-2">
                                             <li><i class="fas fa-check-circle text-green-500 mr-2"></i>Reservations can be made up to 7 days in advance</li>
                                             <li><i class="fas fa-check-circle text-green-500 mr-2"></i>Minimum reservation time is 1 hour</li>
                                             <li><i class="fas fa-check-circle text-green-500 mr-2"></i>Cancellations must be made at least 2 hours before reservation time</li>
                                             <li><i class="fas fa-check-circle text-green-500 mr-2"></i>Payment is calculated based on the reserved duration and vehicle type</li>
                                        </ul>
                                   </div>
                              <?php endif; ?>
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
                         <h3 class="font-bold mb-2">How much does parking cost?</h3>
                         <p class="text-gray-600">Parking rates depend on the vehicle type. For example, cars are typically Rp 5,000 per hour, motorcycles Rp 2,000 per hour, and other vehicles may have different rates.</p>
                    </div>

                    <div class="bg-white rounded-lg shadow-md p-4">
                         <h3 class="font-bold mb-2">What if I arrive late for my reservation?</h3>
                         <p class="text-gray-600">We hold your reserved spot for up to 30 minutes past your scheduled arrival time. After that, the space may be released for other users.</p>
                    </div>

                    <div class="bg-white rounded-lg shadow-md p-4">
                         <h3 class="font-bold mb-2">Can I extend my parking time?</h3>
                         <p class="text-gray-600">Yes, you can extend your parking time through the app as long as the space is available for the extended period. Additional charges will apply based on the extension duration.</p>
                    </div>

                    <div class="bg-white rounded-lg shadow-md p-4">
                         <h3 class="font-bold mb-2">How do I cancel my reservation?</h3>
                         <p class="text-gray-600">You can cancel your reservation through your account dashboard. Full refunds are provided for cancellations made at least 2 hours before the reservation time.</p>
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
          // JavaScript for calculating and displaying fee based on vehicle type and time selection
          document.addEventListener('DOMContentLoaded', function() {
               const vehicleSelect = document.getElementById('vehicle_id');
               const startTimeInput = document.getElementById('start_time');
               const endTimeInput = document.getElementById('end_time');
               const feeDisplay = document.getElementById('fee-display');

               function calculateFee() {
                    const vehicleOption = vehicleSelect.options[vehicleSelect.selectedIndex];
                    const hourlyRate = vehicleOption ? parseFloat(vehicleOption.getAttribute('data-fee')) : 0;
                    const startTime = startTimeInput.value;
                    const endTime = endTimeInput.value;

                    if (hourlyRate && startTime && endTime) {
                         const start = new Date(`1970-01-01T${startTime}:00`);
                         const end = new Date(`1970-01-01T${endTime}:00`);
                         let durationHours = (end - start) / 1000 / 3600;
                         if (durationHours < 0) {
                              durationHours += 24; // Handle cases where end time is past midnight
                         }
                         const fee = Math.ceil(durationHours) * hourlyRate;
                         feeDisplay.textContent = `Rp ${fee.toLocaleString('id-ID')}`;
                    } else {
                         feeDisplay.textContent = 'Rp 0';
                    }
               }

               vehicleSelect.addEventListener('change', calculateFee);
               startTimeInput.addEventListener('change', calculateFee);
               endTimeInput.addEventListener('change', calculateFee);
          
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