<?php
class Transaction
{
     private $conn;

     public function __construct($dbConnection)
     {
          $this->conn = $dbConnection;
     }

     // Mendapatkan daftar transaksi
     public function getTransactions($limit = 10, $offset = 0)
     {
          $sql = "SELECT t.id, t.tanggal, t.mulai, t.akhir, t.biaya, k.nopol, k.pemilik, a.nama as area_nama
                FROM transaksi t
                JOIN kendaraan k ON t.kendaraan_id = k.id
                JOIN area_parkir a ON t.area_parkir_id = a.id
                ORDER BY t.tanggal DESC, t.mulai DESC
                LIMIT ? OFFSET ?";
          $stmt = $this->conn->prepare($sql);
          $stmt->bind_param("ii", $limit, $offset);
          $stmt->execute();
          $result = $stmt->get_result();
          $transactions = [];
          while ($row = $result->fetch_assoc()) {
               $transactions[] = $row;
          }
          $stmt->close();
          return $transactions;
     }

     // Mendapatkan transaksi berdasarkan ID
     public function getTransactionById($id)
     {
          $sql = "SELECT t.id, t.tanggal, t.mulai, t.akhir, t.biaya, t.kendaraan_id, t.area_parkir_id, k.nopol, a.nama as area_nama
                FROM transaksi t
                JOIN kendaraan k ON t.kendaraan_id = k.id
                JOIN area_parkir a ON t.area_parkir_id = a.id
                WHERE t.id = ?";
          $stmt = $this->conn->prepare($sql);
          $stmt->bind_param("i", $id);
          $stmt->execute();
          $result = $stmt->get_result();
          $transaction = $result->fetch_assoc();
          $stmt->close();
          return $transaction;
     }

     // Menghitung total transaksi
     public function getTotalTransactions()
     {
          $sql = "SELECT COUNT(*) as total FROM transaksi";
          $result = $this->conn->query($sql);
          return $result ? ($result->fetch_assoc()['total'] ?? 0) : 0;
     }

     // Membuat transaksi baru
     public function create($tanggal, $mulai, $akhir, $biaya, $kendaraan_id, $area_parkir_id)
     {
          $sql = "INSERT INTO transaksi (tanggal, mulai, akhir, biaya, kendaraan_id, area_parkir_id) VALUES (?, ?, ?, ?, ?, ?)";
          $stmt = $this->conn->prepare($sql);
          $stmt->bind_param("sssiii", $tanggal, $mulai, $akhir, $biaya, $kendaraan_id, $area_parkir_id);
          $success = $stmt->execute();
          $stmt->close();
          return $success;
     }

     // Memperbarui transaksi (misalnya, waktu akhir dan biaya)
     public function update($id, $akhir, $biaya)
     {
          $sql = "UPDATE transaksi SET akhir = ?, biaya = ? WHERE id = ?";
          $stmt = $this->conn->prepare($sql);
          $stmt->bind_param("sii", $akhir, $biaya, $id);
          $success = $stmt->execute();
          $stmt->close();
          return $success;
     }

     // Menghapus transaksi (opsional, untuk admin)
     public function delete($id)
     {
          $sql = "DELETE FROM transaksi WHERE id = ?";
          $stmt = $this->conn->prepare($sql);
          $stmt->bind_param("i", $id);
          $success = $stmt->execute();
          $stmt->close();
          return $success;
     }

     // Mendapatkan daftar kendaraan untuk form
     public function getVehicles()
     {
          $sql = "SELECT id, nopol FROM kendaraan ORDER BY nopol";
          $result = $this->conn->query($sql);
          $vehicles = [];
          while ($row = $result->fetch_assoc()) {
               $vehicles[] = $row;
          }
          return $vehicles;
     }

     // Mendapatkan daftar area parkir untuk form
     public function getParkingAreas()
     {
          $sql = "SELECT id, nama FROM area_parkir ORDER BY nama";
          $result = $this->conn->query($sql);
          $areas = [];
          while ($row = $result->fetch_assoc()) {
               $areas[] = $row;
          }
          return $areas;
     }
}
