<?php
// Database connection
include_once '../koneksi.php';

// Initialize variables
$user_id = null;
$is_logged_in = false;

// Check if user is logged in
session_start();
if (isset($_SESSION['user_id']) && isset($_SESSION['user_role'])) {
     $user_id = $_SESSION['user_id'];
     $user_role = $_SESSION['user_role'];
     $is_logged_in = true;

     // Check if the user is an admin
     if ($user_role !== 'admin') {
          header('Location: ../login.php');
          exit();
     }
} else {
     header('Location: ../login.php');
     exit();
}

// Get summary data
// Count of parking areas
$sql_area_count = "SELECT COUNT(*) as total_areas FROM area_parkir";
$result_area_count = $conn->query($sql_area_count);
$area_count = $result_area_count->fetch_assoc()['total_areas'] ?? 0;

// Count of vehicles
$sql_vehicle_count = "SELECT COUNT(*) as total_vehicles FROM kendaraan";
$result_vehicle_count = $conn->query($sql_vehicle_count);
$vehicle_count = $result_vehicle_count->fetch_assoc()['total_vehicles'] ?? 0;

// Count of transactions today
$today = date('Y-m-d');
$sql_today_trans = "SELECT COUNT(*) as today_transactions FROM transaksi WHERE tanggal = '$today'";
$result_today_trans = $conn->query($sql_today_trans);
$today_transactions = $result_today_trans->fetch_assoc()['today_transactions'] ?? 0;

// Total revenue today
$sql_today_revenue = "SELECT SUM(biaya) as today_revenue FROM transaksi WHERE tanggal = '$today'";
$result_today_revenue = $conn->query($sql_today_revenue);
$today_revenue = $result_today_revenue->fetch_assoc()['today_revenue'] ?? 0;

// Get recent transactions
$sql_recent_trans = "SELECT t.id, t.tanggal, t.mulai, t.akhir, t.biaya, k.nopol, k.pemilik, a.nama as area_nama
                     FROM transaksi t
                     JOIN kendaraan k ON t.kendaraan_id = k.id
                     JOIN area_parkir a ON t.area_parkir_id = a.id
                     ORDER BY t.tanggal DESC, t.mulai DESC
                     LIMIT 10";
$result_recent_trans = $conn->query($sql_recent_trans);

// Get parking area status
$sql_areas = "SELECT a.id, a.nama, a.kapasitas, k.nama as kampus_nama,
              (SELECT COUNT(*) FROM transaksi t WHERE t.area_parkir_id = a.id AND t.tanggal = '$today' AND t.akhir > NOW()) as occupied
              FROM area_parkir a
              JOIN kampus k ON a.kampus_id = k.id";
$result_areas = $conn->query($sql_areas);

// Get vehicle types distribution
$sql_vehicle_types = "SELECT j.nama, COUNT(k.id) as count
                      FROM jenis j
                      LEFT JOIN kendaraan k ON j.id = k.jenis_kendaraan_id
                      GROUP BY j.id";
$result_vehicle_types = $conn->query($sql_vehicle_types);
?>

<!DOCTYPE html>
<html lang="en">

<head>
     <meta charset="UTF-8">
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <title>Campus Parking Admin Dashboard</title>
     <script src="https://cdn.tailwindcss.com"></script>
     <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>

<body class="bg-gray-100">
     <!-- Sidebar -->
     <div class="flex h-screen">
          <div class="bg-blue-800 text-white w-64 py-6 flex flex-col">
               <div class="px-6 mb-8">
                    <h2 class="text-2xl font-bold">Parking System</h2>
                    <p class="text-sm text-blue-200">Admin Dashboard</p>
               </div>
               <nav class="flex-1 px-3">
                    <a href="#" class="flex items-center px-3 py-3 bg-blue-900 rounded-md mb-1">
                         <i class="fas fa-tachometer-alt mr-3"></i>
                         <span>Dashboard</span>
                    </a>
                    <a href="#" class="flex items-center px-3 py-3 text-blue-200 hover:bg-blue-700 rounded-md mb-1">
                         <i class="fas fa-car mr-3"></i>
                         <span>Vehicles</span>
                    </a>
                    <a href="#" class="flex items-center px-3 py-3 text-blue-200 hover:bg-blue-700 rounded-md mb-1">
                         <i class="fas fa-parking mr-3"></i>
                         <span>Parking Areas</span>
                    </a>
                    <a href="#" class="flex items-center px-3 py-3 text-blue-200 hover:bg-blue-700 rounded-md mb-1">
                         <i class="fas fa-university mr-3"></i>
                         <span>Campuses</span>
                    </a>
                    <a href="#" class="flex items-center px-3 py-3 text-blue-200 hover:bg-blue-700 rounded-md mb-1">
                         <i class="fas fa-exchange-alt mr-3"></i>
                         <span>Transactions</span>
                    </a>
                    <a href="#" class="flex items-center px-3 py-3 text-blue-200 hover:bg-blue-700 rounded-md mb-1">
                         <i class="fas fa-chart-bar mr-3"></i>
                         <span>Reports</span>
                    </a>
                    <a href="#" class="flex items-center px-3 py-3 text-blue-200 hover:bg-blue-700 rounded-md mb-1">
                         <i class="fas fa-cog mr-3"></i>
                         <span>Settings</span>
                    </a>
               </nav>
               <div class="px-6 py-4 border-t border-blue-700">
                    <a href="../logout.php" class="flex items-center text-blue-200 hover:text-white">
                         <i class="fas fa-sign-out-alt mr-3"></i>
                         <span>Logout</span>
                    </a>
               </div>
          </div>

          <!-- Main Content -->
          <div class="flex-1 overflow-y-auto">
               <!-- Top Bar -->
               <div class="bg-white shadow-sm p-4 flex justify-between items-center">
                    <h1 class="text-xl font-semibold">Dashboard</h1>
                    <div class="flex items-center space-x-4">
                         <button class="bg-gray-100 p-2 rounded-full">
                              <i class="fas fa-bell text-gray-500"></i>
                         </button>
                         <div class="flex items-center">
                              <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white">
                                   <span>A</span>
                              </div>
                              <span class="ml-2"><?php echo $_SESSION['user_name']; ?></span>
                         </div>
                    </div>
               </div>

               <!-- Dashboard Content -->
               <div class="p-6">
                    <!-- KPI Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                         <div class="bg-white rounded-lg shadow p-6">
                              <div class="flex items-center">
                                   <div class="bg-blue-100 p-3 rounded-full">
                                        <i class="fas fa-parking text-blue-600"></i>
                                   </div>
                                   <div class="ml-4">
                                        <h3 class="text-gray-500 text-sm">Parking Areas</h3>
                                        <p class="text-2xl font-bold"><?php echo $area_count; ?></p>
                                   </div>
                              </div>
                         </div>
                         <div class="bg-white rounded-lg shadow p-6">
                              <div class="flex items-center">
                                   <div class="bg-green-100 p-3 rounded-full">
                                        <i class="fas fa-car text-green-600"></i>
                                   </div>
                                   <div class="ml-4">
                                        <h3 class="text-gray-500 text-sm">Total Vehicles</h3>
                                        <p class="text-2xl font-bold"><?php echo $vehicle_count; ?></p>
                                   </div>
                              </div>
                         </div>
                         <div class="bg-white rounded-lg shadow p-6">
                              <div class="flex items-center">
                                   <div class="bg-purple-100 p-3 rounded-full">
                                        <i class="fas fa-ticket-alt text-purple-600"></i>
                                   </div>
                                   <div class="ml-4">
                                        <h3 class="text-gray-500 text-sm">Today's Transactions</h3>
                                        <p class="text-2xl font-bold"><?php echo $today_transactions; ?></p>
                                   </div>
                              </div>
                         </div>
                         <div class="bg-white rounded-lg shadow p-6">
                              <div class="flex items-center">
                                   <div class="bg-yellow-100 p-3 rounded-full">
                                        <i class="fas fa-money-bill-wave text-yellow-600"></i>
                                   </div>
                                   <div class="ml-4">
                                        <h3 class="text-gray-500 text-sm">Today's Revenue</h3>
                                        <p class="text-2xl font-bold">Rp <?php echo number_format($today_revenue, 0, ',', '.'); ?></p>
                                   </div>
                              </div>
                         </div>
                    </div>

                    <!-- Parking Areas Status -->
                    <div class="bg-white rounded-lg shadow mb-6">
                         <div class="p-4 border-b border-gray-200">
                              <h2 class="text-lg font-semibold">Parking Areas Status</h2>
                         </div>
                         <div class="p-4 overflow-x-auto">
                              <table class="w-full">
                                   <thead class="bg-gray-50">
                                        <tr>
                                             <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                             <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Campus</th>
                                             <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Capacity</th>
                                             <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Occupied</th>
                                             <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Available</th>
                                             <th class="px-6
                                     py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        </tr>
                                   </thead>
                                   <tbody class="bg-white divide-y divide-gray-200">
                                        <?php
                                        if ($result_areas && $result_areas->num_rows > 0) {
                                             while ($row = $result_areas->fetch_assoc()) {
                                                  $occupied = $row['occupied'] ?? 0;
                                                  $available = $row['kapasitas'] - $occupied;
                                                  $percentage = ($row['kapasitas'] > 0) ? ($occupied / $row['kapasitas']) * 100 : 0;
                                                  $status_color = "bg-green-100 text-green-800";
                                                  $status_text = "Available";

                                                  if ($percentage >= 90) {
                                                       $status_color = "bg-red-100 text-red-800";
                                                       $status_text = "Full";
                                                  } elseif ($percentage >= 70) {
                                                       $status_color = "bg-yellow-100 text-yellow-800";
                                                       $status_text = "Filling Up";
                                                  }
                                        ?>
                                                  <tr>
                                                       <td class="px-6 py-4 whitespace-nowrap"><?php echo $row['nama']; ?></td>
                                                       <td class="px-6 py-4 whitespace-nowrap"><?php echo $row['kampus_nama']; ?></td>
                                                       <td class="px-6 py-4 whitespace-nowrap"><?php echo $row['kapasitas']; ?></td>
                                                       <td class="px-6 py-4 whitespace-nowrap"><?php echo $occupied; ?></td>
                                                       <td class="px-6 py-4 whitespace-nowrap"><?php echo $available; ?></td>
                                                       <td class="px-6 py-4 whitespace-nowrap">
                                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_color; ?>">
                                                                 <?php echo $status_text; ?>
                                                            </span>
                                                       </td>
                                                  </tr>
                                        <?php
                                             }
                                        } else {
                                             echo "<tr><td colspan='6' class='px-6 py-4 text-center'>No parking areas found</td></tr>";
                                        }
                                        ?>
                                   </tbody>
                              </table>
                         </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                         <!-- Recent Transactions -->
                         <div class="bg-white rounded-lg shadow lg:col-span-2">
                              <div class="p-4 border-b border-gray-200 flex justify-between items-center">
                                   <h2 class="text-lg font-semibold">Recent Transactions</h2>
                                   <a href="#" class="text-blue-600 hover:text-blue-800 text-sm">View All</a>
                              </div>
                              <div class="p-4 overflow-x-auto">
                                   <table class="w-full">
                                        <thead class="bg-gray-50">
                                             <tr>
                                                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vehicle</th>
                                                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Owner</th>
                                                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Area</th>
                                                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fee</th>
                                             </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                             <?php
                                             if ($result_recent_trans && $result_recent_trans->num_rows > 0) {
                                                  while ($row = $result_recent_trans->fetch_assoc()) {
                                             ?>
                                                       <tr>
                                                            <td class="px-4 py-2 whitespace-nowrap"><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                                                            <td class="px-4 py-2 whitespace-nowrap"><?php echo $row['mulai'] . ' - ' . $row['akhir']; ?></td>
                                                            <td class="px-4 py-2 whitespace-nowrap"><?php echo $row['nopol']; ?></td>
                                                            <td class="px-4 py-2 whitespace-nowrap"><?php echo $row['pemilik']; ?></td>
                                                            <td class="px-4 py-2 whitespace-nowrap"><?php echo $row['area_nama']; ?></td>
                                                            <td class="px-4 py-2 whitespace-nowrap">Rp <?php echo number_format($row['biaya'], 0, ',', '.'); ?></td>
                                                       </tr>
                                             <?php
                                                  }
                                             } else {
                                                  echo "<tr><td colspan='6' class='px-4 py-2 text-center'>No transactions found</td></tr>";
                                             }
                                             ?>
                                        </tbody>
                                   </table>
                              </div>
                         </div>

                         <!-- Vehicle Types -->
                         <div class="bg-white rounded-lg shadow">
                              <div class="p-4 border-b border-gray-200">
                                   <h2 class="text-lg font-semibold">Vehicle Types</h2>
                              </div>
                              <div class="p-4">
                                   <?php
                                   if ($result_vehicle_types && $result_vehicle_types->num_rows > 0) {
                                        while ($row = $result_vehicle_types->fetch_assoc()) {
                                             // Calculate percentage
                                             $percentage = ($vehicle_count > 0) ? ($row['count'] / $vehicle_count) * 100 : 0;
                                   ?>
                                             <div class="mb-4">
                                                  <div class="flex justify-between mb-1">
                                                       <span class="text-sm font-medium"><?php echo $row['nama']; ?></span>
                                                       <span class="text-sm text-gray-500"><?php echo $row['count']; ?> (<?php echo round($percentage); ?>%)</span>
                                                  </div>
                                                  <div class="w-full bg-gray-200 rounded-full h-2">
                                                       <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
                                                  </div>
                                             </div>
                                   <?php
                                        }
                                   } else {
                                        echo "<p class='text-center text-gray-500'>No vehicle types found</p>";
                                   }
                                   ?>
                                   <div class="mt-6">
                                        <h3 class="text-sm font-medium mb-3">Quick Actions</h3>
                                        <div class="grid grid-cols-2 gap-2">
                                             <a href="#" class="bg-blue-100 text-blue-700 py-2 px-3 rounded text-xs font-medium text-center hover:bg-blue-200">
                                                  <i class="fas fa-plus-circle mr-1"></i> Add Vehicle
                                             </a>
                                             <a href="#" class="bg-green-100 text-green-700 py-2 px-3 rounded text-xs font-medium text-center hover:bg-green-200">
                                                  <i class="fas fa-ticket-alt mr-1"></i> New Entry
                                             </a>
                                             <a href="#" class="bg-yellow-100 text-yellow-700 py-2 px-3 rounded text-xs font-medium text-center hover:bg-yellow-200">
                                                  <i class="fas fa-sign-out-alt mr-1"></i> Process Exit
                                             </a>
                                             <a href="#" class="bg-purple-100 text-purple-700 py-2 px-3 rounded text-xs font-medium text-center hover:bg-purple-200">
                                                  <i class="fas fa-chart-line mr-1"></i> Reports
                                             </a>
                                        </div>
                                   </div>
                              </div>
                         </div>
                    </div>
               </div>
          </div>
     </div>
</body>

</html>

<?php
$conn->close();
?>