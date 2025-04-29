<!DOCTYPE html>
<html lang="id">

<head>
     <meta charset="UTF-8">
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <title>Sistem Parkir Kampus</title>
     <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen">
     <div class="container mx-auto px-4 py-8 text-center">
          <h1 class="text-3xl font-bold mb-8">Sistem Parkir Kampus</h1>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
               <div class="bg-white p-6 rounded-lg shadow-lg">
                    <h2 class="text-xl font-semibold mb-4">Masuk Parkir</h2>
                    <p class="text-gray-600 mb-4">Ambil tiket untuk masuk area parkir.</p>
                    <a href="ambil-tiket.php" class="bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700">Ambil Tiket</a>
               </div>
               <div class="bg-white p-6 rounded-lg shadow-lg">
                    <h2 class="text-xl font-semibold mb-4">Bayar Parkir</h2>
                    <p class="text-gray-600 mb-4">Bayar biaya parkir berdasarkan tiket.</p>
                    <a href="bayar.php" class="bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700">Bayar</a>
               </div>
               <div class="bg-white p-6 rounded-lg shadow-lg">
                    <h2 class="text-xl font-semibold mb-4">Cek Status</h2>
                    <p class="text-gray-600 mb-4">Lihat waktu masuk dan status parkir.</p>
                    <a href="status.php" class="bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700">Cek Status</a>
               </div>
          </div>
     </div>
</body>

</html>