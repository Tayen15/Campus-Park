<?php
include_once '../koneksi.php';
include_once '../class/Auth.php';
include_once '../class/Dashboard.php';

session_start();
$auth = new Auth($conn);
$dashboard = new Dashboard($conn);

// Cek login dan role admin
if (!$auth->isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
     header('Location: ../login.php');
     exit;
}

// Ambil data dashboard
$area_count = $dashboard->getParkingAreaCount();
$vehicle_count = $dashboard->getVehicleCount();
$today_transactions = $dashboard->getTodayTransactionCount();
$today_revenue = $dashboard->getTodayRevenue();
$recent_transactions = $dashboard->getRecentTransactions();
$parking_areas = $dashboard->getParkingAreasStatus();
$vehicle_types = $dashboard->getVehicleTypesDistribution();
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
     <!-- Mobile Header with Hamburger Menu -->
     <div class="lg:hidden bg-white shadow-sm p-4 flex justify-between items-center">
          <h1 class="text-xl font-semibold">Dashboard</h1>
          <button id="menu-toggle" class="text-gray-500 focus:outline-none">
               <i class="fas fa-bars text-2xl"></i>
          </button>
     </div>

     <!-- Sidebar -->
     <div class="flex h-screen">
          <div id="sidebar" class="bg-blue-800 text-white w-64 py-6 flex flex-col fixed inset-y-0 left-0 transform -translate-x-full lg:translate-x-0 lg:static transition-transform duration-300 ease-in-out z-50">
               <div class="px-6 mb-8">
                    <h2 class="text-2xl font-bold">Parking System</h2>
                    <p class="text-sm text-blue-200">Admin Dashboard</p>
               </div>
               <nav class="flex-1 px-3">
                    <a href="dashboard.php" class="flex items-center px-3 py-3 bg-blue-900 rounded-md mb-1">
                         <i class="fas fa-tachometer-alt mr-3"></i>
                         <span>Dashboard</span>
                    </a>
                    <a href="vehicles.php" class="flex items-center px-3 py-3 text-blue-200 hover:bg-blue-700 rounded-md mb-1">
                         <i class="fas fa-car mr-3"></i>
                         <span>Vehicles</span>
                    </a>
                    <a href="parking-areas.php" class="flex items-center px-3 py-3 text-blue-200 hover:bg-blue-700 rounded-md mb-1">
                         <i class="fas fa-parking mr-3"></i>
                         <span>Parking Areas</span>
                    </a>
                    <a href="transactions.php" class="flex items-center px-3 py-3 text-blue-200 hover:bg-blue-700 rounded-md mb-1">
                         <i class="fas fa-exchange-alt mr-3"></i>
                         <span>Transactions</span>
                    </a>
                    <a href="reports.php" class="flex items-center px-3 py-3 text-blue-200 hover:bg-blue-700 rounded-md mb-1">
                         <i class="fas fa-chart-bar mr-3"></i>
                         <span>Reports</span>
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
               <!-- Top Bar (Hidden on Mobile) -->
               <div class="hidden lg:block bg-white shadow-sm p-4 lg:flex justify-between items-center">
                    <h1 class="text-xl font-semibold">Dashboard</h1>
                    <div class="flex items-center space-x-4">
                         <button class="bg-gray-100 p-2 rounded-full">
                              <i class="fas fa-bell text-gray-500"></i>
                         </button>
                         <div class="flex items-center">
                              <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white">
                                   <span><?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?></span>
                              </div>
                              <span class="ml-2"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
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
                                             <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        </tr>
                                   </thead>
                                   <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($parking_areas as $row): ?>
                                             <?php
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
                                                  <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($row['nama']); ?></td>
                                                  <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($row['kampus_nama']); ?></td>
                                                  <td class="px-6 py-4 whitespace-nowrap"><?php echo $row['kapasitas']; ?></td>
                                                  <td class="px-6 py-4 whitespace-nowrap"><?php echo $occupied; ?></td>
                                                  <td class="px-6 py-4 whitespace-nowrap"><?php echo $available; ?></td>
                                                  <td class="px-6 py-4 whitespace-nowrap">
                                                       <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_color; ?>">
                                                            <?php echo $status_text; ?>
                                                       </span>
                                                  </td>
                                             </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($parking_areas)): ?>
                                             <tr>
                                                  <td colspan='6' class='px-6 py-4 text-center'>No parking areas found</td>
                                             </tr>
                                        <?php endif; ?>
                                   </tbody>
                              </table>
                         </div>
                    </div>

                    <!-- Recent Transactions and Vehicle Types -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                         <!-- Recent Transactions -->
                         <div class="bg-white rounded-lg shadow lg:col-span-2">
                              <div class="p-4 border-b border-gray-200 flex justify-between items-center">
                                   <h2 class="text-lg font-semibold">Recent Transactions</h2>
                                   <a href="transactions.php" class="text-blue-600 hover:text-blue-800 text-sm">View All</a>
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
                                             <?php foreach ($recent_transactions as $row): ?>
                                                  <tr>
                                                       <td class="px-4 py-2 whitespace-nowrap"><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                                                       <td class="px-4 py-2 whitespace-nowrap"><?php echo $row['mulai'] . ' - ' . $row['akhir']; ?></td>
                                                       <td class="px-4 py-2 whitespace-nowrap"><?php echo htmlspecialchars($row['nopol']); ?></td>
                                                       <td class="px-4 py-2 whitespace-nowrap"><?php echo htmlspecialchars($row['pemilik']); ?></td>
                                                       <td class="px-4 py-2 whitespace-nowrap"><?php echo htmlspecialchars($row['area_nama']); ?></td>
                                                       <td class="px-4 py-2 whitespace-nowrap">Rp <?php echo number_format($row['biaya'], 0, ',', '.'); ?></td>
                                                  </tr>
                                             <?php endforeach; ?>
                                             <?php if (empty($recent_transactions)): ?>
                                                  <tr>
                                                       <td colspan='6' class='px-4 py-2 text-center'>No transactions found</td>
                                                  </tr>
                                             <?php endif; ?>
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
                                   <?php foreach ($vehicle_types as $row): ?>
                                        <?php
                                        $percentage = ($vehicle_count > 0) ? ($row['count'] / $vehicle_count) * 100 : 0;
                                        ?>
                                        <div class="mb-4">
                                             <div class="flex justify-between mb-1">
                                                  <span class="text-sm font-medium"><?php echo htmlspecialchars($row['nama']); ?></span>
                                                  <span class="text-sm text-gray-500"><?php echo $row['count']; ?> (<?php echo round($percentage); ?>%)</span>
                                             </div>
                                             <div class="w-full bg-gray-200 rounded-full h-2">
                                                  <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
                                             </div>
                                        </div>
                                   <?php endforeach; ?>
                                   <?php if (empty($vehicle_types)): ?>
                                        <p class='text-center text-gray-500'>No vehicle types found</p>
                                   <?php endif; ?>
                                   <div class="mt-6">
                                        <h3 class="text-sm font-medium mb-3">Quick Actions</h3>
                                        <div class="grid grid-cols-2 gap-2">
                                             <a href="vehicles.php" class="bg-blue-100 text-blue-700 py-2 px-3 rounded text-xs font-medium text-center hover:bg-blue-200">
                                                  <i class="fas fa-plus-circle mr-1"></i> Add Vehicle
                                             </a>
                                             <a href="#" class="bg-green-100 text-green-700 py-2 px-3 rounded text-xs font-medium text-center hover:bg-green-200">
                                                  <i class="fas fa-ticket-alt mr-1"></i> New Entry
                                             </a>
                                             <a href="../logout.php" class="bg-yellow-100 text-yellow-700 py-2 px-3 rounded text-xs font-medium text-center hover:bg-yellow-200">
                                                  <i class="fas fa-sign-out-alt mr-1"></i> Process Exit
                                             </a>
                                             <a href="reports.php" class="bg-purple-100 text-purple-700 py-2 px-3 rounded text-xs font-medium text-center hover:bg-purple-200">
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

     <!-- JavaScript for Sidebar Toggle -->
     <script>
          const menuToggle = document.getElementById('menu-toggle');
          const sidebar = document.getElementById('sidebar');

          menuToggle.addEventListener('click', () => {
               sidebar.classList.toggle('-translate-x-full');
          });

          // Close sidebar when clicking outside on mobile
          document.addEventListener('click', (e) => {
               if (!sidebar.contains(e.target) && !menuToggle.contains(e.target) && !sidebar.classList.contains('-translate-x-full')) {
                    sidebar.classList.add('-translate-x-full');
               }
          });
     </script>
</body>

</html>
<?php $conn->close(); ?>