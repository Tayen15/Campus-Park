<?php
class Dashboard
{
     private $conn;
     private $today;

     public function __construct($dbConnection)
     {
          $this->conn = $dbConnection;
          $this->today = date('Y-m-d');
     }

     // Mendapatkan jumlah area parkir
     public function getParkingAreaCount()
     {
          $sql = "SELECT COUNT(*) as total_areas FROM area_parkir";
          $result = $this->conn->query($sql);
          return $result ? ($result->fetch_assoc()['total_areas'] ?? 0) : 0;
     }

     // Mendapatkan jumlah kendaraan
     public function getVehicleCount()
     {
          $sql = "SELECT COUNT(*) as total_vehicles FROM kendaraan";
          $result = $this->conn->query($sql);
          return $result ? ($result->fetch_assoc()['total_vehicles'] ?? 0) : 0;
     }

     // Mendapatkan jumlah transaksi hari ini
     public function getTodayTransactionCount()
     {
          $sql = "SELECT COUNT(*) as today_transactions FROM transaksi WHERE tanggal = ?";
          $stmt = $this->conn->prepare($sql);
          $stmt->bind_param("s", $this->today);
          $stmt->execute();
          $result = $stmt->get_result();
          $count = $result->fetch_assoc()['today_transactions'] ?? 0;
          $stmt->close();
          return $count;
     }

     // Mendapatkan pendapatan hari ini
     public function getTodayRevenue()
     {
          $sql = "SELECT SUM(biaya) as today_revenue FROM transaksi WHERE tanggal = ?";
          $stmt = $this->conn->prepare($sql);
          $stmt->bind_param("s", $this->today);
          $stmt->execute();
          $result = $stmt->get_result();
          $revenue = $result->fetch_assoc()['today_revenue'] ?? 0;
          $stmt->close();
          return $revenue;
     }

     // Mendapatkan transaksi terbaru
     public function getRecentTransactions($limit = 10)
     {
          $sql = "SELECT t.id, t.tanggal, t.mulai, t.akhir, t.biaya, k.nopol, k.pemilik, a.nama as area_nama
                FROM transaksi t
                JOIN kendaraan k ON t.kendaraan_id = k.id
                JOIN area_parkir a ON t.area_parkir_id = a.id
                ORDER BY t.tanggal DESC, t.mulai DESC
                LIMIT ?";
          $stmt = $this->conn->prepare($sql);
          $stmt->bind_param("i", $limit);
          $stmt->execute();
          $result = $stmt->get_result();
          $transactions = [];
          while ($row = $result->fetch_assoc()) {
               $transactions[] = $row;
          }
          $stmt->close();
          return $transactions;
     }

     // Mendapatkan status area parkir
     public function getParkingAreasStatus()
     {
          $sql = "SELECT a.id, a.nama, a.kapasitas, k.nama as kampus_nama,
                (SELECT COUNT(*) FROM transaksi t WHERE t.area_parkir_id = a.id AND t.tanggal = ? AND t.akhir > NOW()) as occupied
                FROM area_parkir a
                JOIN kampus k ON a.kampus_id = k.id";
          $stmt = $this->conn->prepare($sql);
          $stmt->bind_param("s", $this->today);
          $stmt->execute();
          $result = $stmt->get_result();
          $areas = [];
          while ($row = $result->fetch_assoc()) {
               $areas[] = $row;
          }
          $stmt->close();
          return $areas;
     }

     // Mendapatkan distribusi jenis kendaraan
     public function getVehicleTypesDistribution()
     {
          $sql = "SELECT j.nama, COUNT(k.id) as count
                FROM jenis j
                LEFT JOIN kendaraan k ON j.id = k.jenis_kendaraan_id
                GROUP BY j.id";
          $result = $this->conn->query($sql);
          $types = [];
          while ($row = $result->fetch_assoc()) {
               $types[] = $row;
          }
          return $types;
     }
}
