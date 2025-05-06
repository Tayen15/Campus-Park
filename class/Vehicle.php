<?php
class Vehicle
{
     private $conn;

     public function __construct($dbConnection)
     {
          $this->conn = $dbConnection;
     }

     // Mendapatkan daftar kendaraan
     public function getVehicles($limit = 10, $offset = 0)
     {
          $sql = "SELECT k.id, k.nopol, k.merk, k.pemilik, j.nama as jenis_nama
                FROM kendaraan k
                JOIN jenis j ON k.jenis_kendaraan_id = j.id
                ORDER BY k.id DESC
                LIMIT ? OFFSET ?";
          $stmt = $this->conn->prepare($sql);
          $stmt->bind_param("ii", $limit, $offset);
          $stmt->execute();
          $result = $stmt->get_result();
          $vehicles = [];
          while ($row = $result->fetch_assoc()) {
               $vehicles[] = $row;
          }
          $stmt->close();
          return $vehicles;
     }

     // Mendapatkan kendaraan berdasarkan ID
     public function getVehicleById($id)
     {
          $sql = "SELECT k.id, k.nopol, k.merk, k.pemilik, k.thn_beli, k.jenis_kendaraan_id, j.nama as jenis_nama
                FROM kendaraan k
                JOIN jenis j ON k.jenis_kendaraan_id = j.id
                WHERE k.id = ?";
          $stmt = $this->conn->prepare($sql);
          $stmt->bind_param("i", $id);
          $stmt->execute();
          $result = $stmt->get_result();
          $vehicle = $result->fetch_assoc();
          $stmt->close();
          return $vehicle;
     }

     // Menghitung total kendaraan
     public function getTotalVehicles()
     {
          $sql = "SELECT COUNT(*) as total FROM kendaraan";
          $result = $this->conn->query($sql);
          return $result ? ($result->fetch_assoc()['total'] ?? 0) : 0;
     }

     // Membuat kendaraan baru
     public function create($merk, $pemilik, $nopol, $thn_beli, $jenis_kendaraan_id, $user_id, $deskripsi)
     {
          $sql = "INSERT INTO kendaraan (merk, pemilik, nopol, thn_beli, jenis_kendaraan_id, user_id, deskripsi) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
          $stmt = $this->conn->prepare($sql);
          $stmt->bind_param("sssiiis", $merk, $pemilik, $nopol, $thn_beli, $jenis_kendaraan_id, $user_id, $deskripsi);
          $success = $stmt->execute();
          $stmt->close();
          return $success;
     }

     // Memperbarui kendaraan
     public function update($id, $nopol, $merk, $jenis_kendaraan_id, $thn_beli, $deskripsi)
     {
          $sql = "UPDATE kendaraan SET nopol = ?, merk = ?, jenis_kendaraan_id = ?, thn_beli = ?, deskripsi = ? WHERE id = ?";
          $stmt = $this->conn->prepare($sql);
          $stmt->bind_param("ssiisi", $nopol, $merk, $jenis_kendaraan_id, $thn_beli, $deskripsi, $id);
          $success = $stmt->execute();
          $stmt->close();
          return $success;
     }

     // Menghapus kendaraan
     public function delete($id)
     {
          $sql = "DELETE FROM kendaraan WHERE id = ?";
          $stmt = $this->conn->prepare($sql);
          $stmt->bind_param("i", $id);
          $success = $stmt->execute();
          $stmt->close();
          return $success;
     }

     // Mendapatkan daftar jenis kendaraan untuk form
     public function getVehicleTypes()
     {
          $sql = "SELECT id, nama FROM jenis ORDER BY nama";
          $result = $this->conn->query($sql);
          $types = [];
          while ($row = $result->fetch_assoc()) {
               $types[] = $row;
          }
          return $types;
     }
}
