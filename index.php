<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "pgweb acara 8";

// Buat koneksi
$conn = new mysqli($servername, $username, $password, $dbname);

// Periksa koneksi
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fungsi untuk menghapus data
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM database_kecamatan WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        echo "Data berhasil dihapus.";
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}

// Fungsi untuk memperbarui data
if (isset($_POST['update'])) {
    $id = intval($_POST['id']);
    $kecamatan = $_POST['kecamatan'];
    $longitude = $_POST['longitude'];
    $latitude = $_POST['latitude'];
    $luas = $_POST['luas'];
    $jumlah_penduduk = $_POST['jumlah_penduduk'];

    $stmt = $conn->prepare("UPDATE database_kecamatan SET Kecamatan = ?, Longitude = ?, Latitude = ?, Luas = ?, Jumlah_Penduduk = ? WHERE id = ?");
    $stmt->bind_param("ssssii", $kecamatan, $longitude, $latitude, $luas, $jumlah_penduduk, $id);
    if ($stmt->execute()) {
        echo "Data berhasil diperbarui.";
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}

// Ambil data dari database
$sql = "SELECT * FROM database_kecamatan";
$result = $conn->query($sql);

$markers = array();
$tableHtml = "";
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $markers[] = $row;

        $tableHtml .= "<tr>";
        $tableHtml .= "<td>" . htmlspecialchars($row["Kecamatan"]) . "</td>";
        $tableHtml .= "<td>" . htmlspecialchars($row["Longitude"]) . "</td>";
        $tableHtml .= "<td>" . htmlspecialchars($row["Latitude"]) . "</td>";
        $tableHtml .= "<td>" . htmlspecialchars($row["Luas"]) . "</td>";
        $tableHtml .= "<td align='right'>" . htmlspecialchars($row["Jumlah_Penduduk"]) . "</td>";
        $tableHtml .= "<td><a href='index.php?delete_id=" . $row["id"] . "' onclick=\"return confirm('Apakah Anda yakin ingin menghapus data ini?')\">Hapus</a></td>";
        $tableHtml .= "<td><a href='index.php?edit_id=" . $row["id"] . "'>Edit</a></td>";
        $tableHtml .= "</tr>";
    }
    $tableHtml .= "</table>";
} else {
    $tableHtml = "<tr><td colspan='7'>0 results found.</td></tr>";
}

// Ambil data untuk formulir edit
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $edit_sql = "SELECT * FROM database_kecamatan WHERE id = $edit_id";
    $edit_result = $conn->query($edit_sql);
    if ($edit_result && $edit_result->num_rows > 0) {
        $edit_row = $edit_result->fetch_assoc();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Web Map Kecamatan di Yogyakarta</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        #map { width: 100%; height: 400px; float: left; margin-right: 10px; margin-left: 10px; }
        table { width: 30%; border-collapse: collapse; margin-top: 20px; float: left; }
        table, th, td { border: 1px solid black; }
        th, td { padding: 10px; text-align: left; }
        h2 { clear: both; }
    </style>
</head>
<body>
    <h1>Web  GIS Kecamatan di Yogyakarta</h1>

    <div id="map"></div>
    
    <h2>Data Kecamatan</h2>
    <table>
        <tr>
            <th>Kecamatan</th>
            <th>Longitude</th>
            <th>Latitude</th>
            <th>Luas</th>
            <th>Jumlah Penduduk</th>
            <th>Aksi</th>
            <th>Edit</th>
        </tr>
        <?php echo $tableHtml; ?>
    </table>

    <?php if (isset($edit_row)): ?>
        <h2>Edit Data Kecamatan</h2>
        <form method="post" action="index.php">
            <input type="hidden" name="id" value="<?php echo $edit_row['id']; ?>">
            <label for="kecamatan">Kecamatan:</label>
            <input type="text" name="kecamatan" value="<?php echo htmlspecialchars($edit_row['Kecamatan']); ?>"><br>
            <label for="longitude">Longitude:</label>
            <input type="text" name="longitude" value="<?php echo htmlspecialchars($edit_row['Longitude']); ?>"><br>
            <label for="latitude">Latitude:</label>
            <input type="text" name="latitude" value="<?php echo htmlspecialchars($edit_row['Latitude']); ?>"><br>
            <label for="luas">Luas:</label>
            <input type="text" name="luas" value="<?php echo htmlspecialchars($edit_row['Luas']); ?>"><br>
            <label for="jumlah_penduduk">Jumlah Penduduk:</label>
            <input type="text" name="jumlah_penduduk" value="<?php echo htmlspecialchars($edit_row['Jumlah_Penduduk']); ?>"><br>
            <input type="submit" name="update" value="Update">
        </form>
    <?php endif; ?>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script>
        // Inisialisasi peta
        var map = L.map("map").setView([-7.4726204, 110.2197571], 11);

        // Tile Layer Base Map
        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        <?php
        foreach ($markers as $marker) {
            $kecamatan = htmlspecialchars($marker['Kecamatan']);
            $longitude = htmlspecialchars($marker['Longitude']);
            $latitude = htmlspecialchars($marker['Latitude']);
            $luas = htmlspecialchars($marker['Luas']);
            $jumlah_penduduk = htmlspecialchars($marker['Jumlah_Penduduk']);

            echo "var marker = L.marker([$latitude, $longitude]).addTo(map);";
            echo "marker.bindPopup('<b>$kecamatan</b><br>Longitude: $longitude<br>Latitude: $latitude<br>Luas: $luas<br>Jumlah Penduduk: $jumlah_penduduk');";
            echo "marker.bindTooltip('$kecamatan');";
        }
        ?>
    </script>
</body>
</html>
