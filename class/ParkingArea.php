<?php
class ParkingArea
{
     private $conn;

     public function __construct($dbConnection)
     {
          $this->conn = $dbConnection;
     }

     // Mendapatkan daftar area parkir
     public function getParkingAreas($limit = 10, $offset = 0)
     {
          $sql = "SELECT a.id, a.nama, a.kapasitas, k.nama as kampus_nama
                FROM area_parkir a
                JOIN kampus k ON a.kampus_id = k.id
                ORDER BY a.id DESC
                LIMIT ? OFFSET ?";
          $stmt = $this->conn->prepare($sql);
          $stmt->bind_param("ii", $limit, $offset);
          $stmt->execute();
          $result = $stmt->get_result();
          $areas = [];
          while ($row = $result->fetch_assoc()) {
               $areas[] = $row;
          }
          $stmt->close();
          return $areas;
     }

     // Mendapatkan area parkir berdasarkan ID
     public function getParkingAreaById($id)
     {
          $sql = "SELECT a.id, a.nama, a.kapasitas, a.keterangan, a.kampus_id, k.nama as kampus_nama
                FROM area_parkir a
                JOIN kampus k ON a.kampus_id = k.id
                WHERE a.id = ?";
          $stmt = $this->conn->prepare($sql);
          $stmt->bind_param("i", $id);
          $stmt->execute();
          $result = $stmt->get_result();
          $area = $result->fetch_assoc();
          $stmt->close();
          return $area;
     }

     // Menghitung total area parkir
     public function getTotalParkingAreas()
     {
          $sql = "SELECT COUNT(*) as total FROM area_parkir";
          $result = $this->conn->query($sql);
          return $result ? ($result->fetch_assoc()['total'] ?? 0) : 0;
     }

     // Membuat area parkir baru
     public function create($nama, $kapasitas, $kampus_id, $keterangan)
     {
          $sql = "INSERT INTO area_parkir (nama, kapasitas, kampus_id, keterangan) VALUES (?, ?, ?, ?)";
          $stmt = $this->conn->prepare($sql);
          $stmt->bind_param("siis", $nama, $kapasitas, $kampus_id, $keterangan);
          $success = $stmt->execute();
          $stmt->close();
          return $success;
     }

     // Memperbarui area parkir
     public function update($id, $nama, $kapasitas, $kampus_id, $keterangan)
     {
          $sql = "UPDATE area_parkir SET nama = ?, kapasitas = ?, kampus_id = ?, keterangan = ? WHERE id = ?";
          $stmt = $this->conn->prepare($sql);
          $stmt->bind_param("siisi", $nama, $kapasitas, $kampus_id, $keterangan, $id);
          $success = $stmt->execute();
          $stmt->close();
          return $success;
     }

     // Menghapus area parkir
     public function delete($id)
     {
          $sql = "DELETE FROM area_parkir WHERE id = ?";
          $stmt = $this->conn->prepare($sql);
          $stmt->bind_param("i", $id);
          $success = $stmt->execute();
          $stmt->close();
          return $success;
     }

     // Mendapatkan daftar kampus untuk form
     public function getCampuses()
     {
          $sql = "SELECT id, nama FROM kampus ORDER BY nama";
          $result = $this->conn->query($sql);
          $campuses = [];
          while ($row = $result->fetch_assoc()) {
               $campuses[] = $row;
          }
          return $campuses;
     }
}
