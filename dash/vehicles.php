<?php
include_once '../koneksi.php';
include_once '../class/Auth.php';
include_once '../class/Vehicle.php';

session_start();
$auth = new Auth($conn);
$vehicle = new Vehicle($conn);

// Cek login dan role admin
if (!$auth->isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
     header('Location: ../login.php');
     exit;
}

// Inisialisasi CSRF token
if (!isset($_SESSION['csrf_token'])) {
     $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Proses form CRUD
$errors = [];
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
     // Verifikasi CSRF token
     if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
          $errors[] = 'Invalid CSRF token.';
     } else {
          $action = $_POST['action'] ?? '';
          $nopol = $_POST['nopol'] ?? '';
          $merk = $_POST['merk'] ?? '';
          $thn_beli = intval($_POST['thn_beli'] ?? '');
          $pemilik = $_POST['pemilik'] ?? '';
          $jenis_kendaraan_id = (int)($_POST['jenis_kendaraan_id'] ?? 0);
          $deskripsi = $_POST['deskripsi'] ?? '';
          $id = (int)($_POST['id'] ?? 0);

          if ($action === 'create') {
               if (empty($nopol) || empty($merk) || empty($pemilik) || $jenis_kendaraan_id <= 0 || empty($thn_beli)) {
                    $errors[] = 'All fields are required.';
               }
          } elseif ($action === 'update') {
               if (empty($nopol) || empty($merk) || $jenis_kendaraan_id <= 0 || empty($thn_beli)) {
                    $errors[] = 'All fields except owner are required.';
               }
          }

          if (empty($errors)) {
               if ($action === 'create') {
                    if ($vehicle->create($merk, $pemilik, $nopol, $thn_beli, $jenis_kendaraan_id, $_SESSION['user_id'], $deskripsi)) {
                         // Redirect to the same page to avoid resubmission
                         $success = 'Vehicle added successfully.';
                    } else {
                         $errors[] = 'Failed to add vehicle.';
                    }
               } elseif ($action === 'update' && $id > 0) {
                    if ($vehicle->update($id, $nopol, $merk, $jenis_kendaraan_id, $thn_beli, $_SESSION['user_id'], $deskripsi)) {
                         $success = 'Vehicle updated successfully.';
                    } else {
                         $errors[] = 'Failed to update vehicle.';
                    }
               } elseif ($action === 'delete' && $id > 0) {
                    if ($vehicle->delete($id)) {
                         $success = 'Vehicle deleted successfully.';
                    } else {
                         $errors[] = 'Failed to delete vehicle.';
                    }
               }
          }
     }
}

// Ambil data kendaraan (dengan paginasi)
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$vehicles = $vehicle->getVehicles($limit, $offset);
$total_vehicles = $vehicle->getTotalVehicles();
$total_pages = ceil($total_vehicles / $limit);

// Ambil jenis kendaraan untuk form
$vehicle_types = $vehicle->getVehicleTypes();

// Ambil data untuk edit
$edit_vehicle = null;
if (isset($_GET['edit']) && (int)$_GET['edit'] > 0) {
     $edit_vehicle = $vehicle->getVehicleById((int)$_GET['edit']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
     <meta charset="UTF-8">
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <title>Vehicles - Campus Parking Admin</title>
     <script src="https://cdn.tailwindcss.com"></script>
     <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
     <script>
          function openModal(modalId) {
               document.getElementById(modalId).classList.remove('hidden');
          }

          function closeModal(modalId) {
               document.getElementById(modalId).classList.add('hidden');
          }

          function confirmDelete(formId) {
               return confirm('Are you sure you want to delete this vehicle?');
          }
     </script>
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
                    <a href="vehicles.php" class="flex items-center px-3 py-3 bg-blue-900 rounded-md mb-1">
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
               <!-- Top Bar -->
               <div class="bg-white shadow-sm p-4 flex justify-between items-center">
                    <h1 class="text-xl font-semibold">Vehicles</h1>
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

               <!-- Vehicles Content -->
               <div class="p-6">
                    <!-- Notifikasi -->
                    <?php if ($success): ?>
                         <div class="bg-green-100 border border-green-200 text-green-800 rounded-lg p-4 mb-6">
                              <p class="font-medium"><?php echo htmlspecialchars($success); ?></p>
                         </div>
                         <script>
                              document.addEventListener('DOMContentLoaded', function () {
                                   closeModal('addVehicleModal');
                                   closeModal('editVehicleModal');
                              });
                         </script>
                    <?php endif; ?>
                    <?php if (!empty($errors)): ?>
                         <div class="bg-red-100 border border-red-200 text-red-800 rounded-lg p-4 mb-6">
                              <ul class="list-disc ml-5">
                                   <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                   <?php endforeach; ?>
                              </ul>
                         </div>
                    <?php endif; ?>

                    <div class="bg-white rounded-lg shadow">
                         <div class="p-4 border-b border-gray-200 flex justify-between items-center">
                              <h2 class="text-lg font-semibold">Vehicle List</h2>
                              <button onclick="openModal('addVehicleModal')" class="bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 text-sm">Add Vehicle</button>
                         </div>
                         <div class="p-4 overflow-x-auto">
                              <table class="w-full">
                                   <thead class="bg-gray-50">
                                        <tr>
                                             <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plate Number</th>
                                             <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Brand</th>
                                             <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Owner</th>
                                             <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                             <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                   </thead>
                                   <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($vehicles as $row): ?>
                                             <tr>
                                                  <td class="px-4 py-2 whitespace-nowrap"><?php echo htmlspecialchars($row['nopol']); ?></td>
                                                  <td class="px-4 py-2 whitespace-nowrap"><?php echo htmlspecialchars($row['merk']); ?></td>
                                                  <td class="px-4 py-2 whitespace-nowrap"><?php echo htmlspecialchars($row['pemilik']); ?></td>
                                                  <td class="px-4 py-2 whitespace-nowrap"><?php echo htmlspecialchars($row['jenis_nama']); ?></td>
                                                  <td class="px-4 py-2 whitespace-nowrap">
                                                       <a href="?edit=<?php echo $row['id']; ?>" class="text-blue-600 hover:text-blue-800">Edit</a>
                                                       <form action="" method="POST" class="inline" onsubmit="return confirmDelete('delete_<?php echo $row['id']; ?>')">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                            <button type="submit" class="text-red-600 hover:text-red-800 ml-3">Delete</button>
                                                       </form>
                                                  </td>
                                             </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($vehicles)): ?>
                                             <tr>
                                                  <td colspan='5' class='px-4 py-2 text-center'>No vehicles found</td>
                                             </tr>
                                        <?php endif; ?>
                                   </tbody>
                              </table>
                         </div>
                         <!-- Pagination -->
                         <div class="p-4 flex justify-between items-center">
                              <span class="text-sm text-gray-600">Showing <?php echo count($vehicles); ?> of <?php echo $total_vehicles; ?> vehicles</span>
                              <div class="flex space-x-2">
                                   <?php if ($page > 1): ?>
                                        <a href="?page=<?php echo $page - 1; ?>" class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">Previous</a>
                                   <?php endif; ?>
                                   <?php if ($page < $total_pages): ?>
                                        <a href="?page=<?php echo $page + 1; ?>" class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">Next</a>
                                   <?php endif; ?>
                              </div>
                         </div>
                    </div>
               </div>
          </div>
     </div>

     <!-- Modal: Add Vehicle -->
     <div id="addVehicleModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden">
          <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
               <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold">Add Vehicle</h2>
                    <button onclick="closeModal('addVehicleModal')" class="text-gray-500 hover:text-gray-700">
                         <i class="fas fa-times"></i>
                    </button>
               </div>
               <form method="POST">
                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div class="mb-4">
                         <label class="block text-sm font-medium text-gray-700">Plate Number</label>
                         <input type="text" name="nopol" required class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="mb-4">
                         <label class="block text-sm font-medium text-gray-700">Brand</label>
                         <input type="text" name="merk" required class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="mb-4">
                         <label class="block text-sm font-medium text-gray-700">Owner</label>
                         <input type="text" name="pemilik" required class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="mb-4">
                         <label class="block text-sm font-medium text-gray-700">Purchase Year</label>
                         <input type="number" name="thn_beli" required class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" min="1900" max="<?php echo date('Y'); ?>" value="<?php echo date('Y'); ?>">
                    </div>
                    <div class="mb-4">
                         <label class="block text-sm font-medium text-gray-700">Vehicle Type</label>
                         <select name="jenis_kendaraan_id" required class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                              <option value="">Select Type</option>
                              <?php foreach ($vehicle_types as $type): ?>
                                   <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['nama']); ?></option>
                              <?php endforeach; ?>
                         </select>
                    </div>
                    <div class="flex justify-end">
                         <button type="button" onclick="closeModal('addVehicleModal')" class="bg-gray-300 text-gray-700 py-2 px-4 rounded hover:bg-gray-400 mr-2">Cancel</button>
                         <button type="submit" class="bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700">Add</button>
                    </div>
               </form>
          </div>
     </div>

     <!-- Modal: Edit Vehicle -->
     <?php if ($edit_vehicle): ?>
          <div id="editVehicleModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center">
               <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
                    <div class="flex justify-between items-center mb-4">
                         <h2 class="text-lg font-semibold">Edit Vehicle</h2>
                         <button onclick="window.location.href='vehicles.php'" class="text-gray-500 hover:text-gray-700">
                              <i class="fas fa-times"></i>
                         </button>
                    </div>
                    <form method="POST">
                         <input type="hidden" name="action" value="update">
                         <input type="hidden" name="id" value="<?php echo $edit_vehicle['id']; ?>">
                         <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                         <div class="mb-4">
                              <label class="block text-sm font-medium text-gray-700">Plate Number</label>
                              <input type="text" name="nopol" value="<?php echo htmlspecialchars($edit_vehicle['nopol']); ?>" required class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                         </div>
                         <div class="mb-4">
                              <label class="block text-sm font-medium text-gray-700">Brand</label>
                              <input type="text" name="merk" value="<?php echo htmlspecialchars($edit_vehicle['merk']); ?>" required class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                         </div>
                         <div class="mb-4">
                              <label class="block text-sm font-medium text-gray-700">Owner</label>
                              <input type="text" disabled value="<?php echo htmlspecialchars($edit_vehicle['pemilik']); ?>" class="w-full px-3 py-2 border rounded-md bg-gray-100 cursor-not-allowed">
                         </div>
                         <div class="mb-4">
                              <label class="block text-sm font-medium text-gray-700">Purchase Year</label>
                              <input type="number" name="thn_beli" value="<?php echo htmlspecialchars($edit_vehicle['thn_beli']); ?>" required class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" min="1900" max="<?php echo date('Y'); ?>">
                         </div>
                         <div class="mb-4">
                              <label class="block text-sm font-medium text-gray-700">Vehicle Type</label>
                              <select name="jenis_kendaraan_id" required class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                   <option value="">Select Type</option>
                                   <?php foreach ($vehicle_types as $type): ?>
                                        <option value="<?php echo $type['id']; ?>" <?php echo $type['id'] == $edit_vehicle['jenis_kendaraan_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($type['nama']); ?></option>
                                   <?php endforeach; ?>
                              </select>
                         </div>
                         <div class="flex justify-end">
                              <button type="button" onclick="window.location.href='vehicles.php'" class="bg-gray-300 text-gray-700 py-2 px-4 rounded hover:bg-gray-400 mr-2">Cancel</button>
                              <button type="submit" class="bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700">Update</button>
                         </div>
                    </form>
               </div>
          </div>
     <?php endif; ?>

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