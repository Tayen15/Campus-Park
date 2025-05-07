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

// Ambil data untuk laporan (contoh: pendapatan harian)
$revenues = [];
$sql = "SELECT tanggal, SUM(biaya) as total_revenue
        FROM transaksi
        GROUP BY tanggal
        ORDER BY tanggal DESC
        LIMIT 7";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
     $revenues[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
     <meta charset="UTF-8">
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <title>Reports - Campus Parking Admin</title>
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

     <!-- Content -->
     <div class="flex h-screen">
          <!-- Sidebar -->
          <div id="sidebar" class="bg-blue-800 text-white w-64 py-6 flex flex-col fixed inset-y-0 left-0 transform -translate-x-full lg:translate-x-0 lg:static transition-transform duration-300 ease-in-out z-50">
               <div class="px-6 mb-8">
                    <h2 class="text-2xl font-bold">Parking System</h2>
                    <p class="text-sm text-blue-200">Admin Dashboard</p>
               </div>
               <nav class="flex-1 px-3">
                    <a href="dashboard.php" class="flex items-center px-3 py-3 text-blue-200 hover:bg-blue-700 rounded-md mb-1">
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
                    <a href="reports.php" class="flex items-center px-3 py-3 bg-blue-900 rounded-md mb-1">
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
               <!-- Top Bar -->
               <div class="bg-white shadow-sm p-4 flex justify-between items-center">
                    <h1 class="text-xl font-semibold">Reports</h1>
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

               <!-- Reports Content -->
               <div class="p-6">
                    <div class="bg-white rounded-lg shadow">
                         <div class="p-4 border-b border-gray-200">
                              <h2 class="text-lg font-semibold">Daily Revenue Report (Last 7 Days)</h2>
                         </div>
                         <div class="p-4 overflow-x-auto">
                              <table class="w-full">
                                   <thead class="bg-gray-50">
                                        <tr>
                                             <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                             <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Revenue</th>
                                        </tr>
                                   </thead>
                                   <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($revenues as $row): ?>
                                             <tr>
                                                  <td class="px-4 py-2 whitespace-nowrap"><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                                                  <td class="px-4 py-2 whitespace-nowrap">Rp <?php echo number_format($row['total_revenue'], 0, ',', '.'); ?></td>
                                             </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($revenues)): ?>
                                             <tr>
                                                  <td colspan='2' class='px-4 py-2 text-center'>No revenue data found</td>
                                             </tr>
                                        <?php endif; ?>
                                   </tbody>
                              </table>
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