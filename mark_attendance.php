<?php
include "db.php";

// Get selected date (default today)
$date = $_POST['date'] ?? $_GET['date'] ?? date('Y-m-d');

// Handle form submit
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['attendance'])) {
    foreach ($_POST['attendance'] as $student_id => $status) {
        if ($status == "N") {
            // If "No Record" → delete entry if exists
            $del = $conn->prepare("DELETE FROM attendance WHERE student_id=? AND date=?");
            $del->bind_param("is", $student_id, $date);
            $del->execute();
            continue;
        }

        // Otherwise insert/update
        $check = $conn->prepare("SELECT id FROM attendance WHERE student_id=? AND date=?");
        $check->bind_param("is", $student_id, $date);
        $check->execute();
        $res = $check->get_result();

        if ($res->num_rows > 0) {
            // Update existing record
            $row = $res->fetch_assoc();
            $update = $conn->prepare("UPDATE attendance SET status=? WHERE id=?");
            $update->bind_param("si", $status, $row['id']);
            $update->execute();
        } else {
            // Insert new record
            $insert = $conn->prepare("INSERT INTO attendance (student_id, date, status) VALUES (?, ?, ?)");
            $insert->bind_param("iss", $student_id, $date, $status);
            $insert->execute();
        }
    }
    echo "<p style='color:green; text-align:center;'>Attendance updated for $date ✅</p>";
}

// Fetch all students
$students = $conn->query("SELECT student_id, name, roll_no FROM students ORDER BY name");

// Fetch existing attendance for this date
$att = [];
$get = $conn->prepare("SELECT student_id, status FROM attendance WHERE date=?");
$get->bind_param("s", $date);
$get->execute();
$res = $get->get_result();
while ($row = $res->fetch_assoc()) {
    $att[$row['student_id']] = $row['status'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mark Attendance</title>
    <link rel="stylesheet" href="style.css">
    <style>
        table { border-collapse: collapse; width: 80%; margin: 20px auto; }
        th, td { padding: 8px; border: 1px solid #ccc; text-align: center; }
        h2 { text-align: center; }
        select { padding: 5px; }
        .top-bar { text-align: center; margin: 15px; }
    </style>
</head>
<body>
    <h2>Mark Attendance</h2>

    <div class="top-bar">
        <form method="GET">
            <label>Select Date:</label>
            <input type="date" name="date" value="<?php echo $date; ?>">
            <button type="submit">Load</button>
        </form>
    </div>

    <form method="POST">
        <input type="hidden" name="date" value="<?php echo $date; ?>">
        <table>
            <tr><th>Roll No</th><th>Name</th><th>Status</th></tr>
            <?php while ($s = $students->fetch_assoc()) {
                $selected = $att[$s['student_id']] ?? "N"; // Default No Record
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($s['roll_no']); ?></td>
                    <td><?php echo htmlspecialchars($s['name']); ?></td>
                    <td>
                        <select name="attendance[<?php echo $s['student_id']; ?>]">
                            <option value="P" <?php if ($selected=="P") echo "selected"; ?>>Present</option>
                            <option value="H" <?php if ($selected=="H") echo "selected"; ?>>Home</option>
                            <option value="L" <?php if ($selected=="L") echo "selected"; ?>>Leave</option>
                            <option value="A" <?php if ($selected=="A") echo "selected"; ?>>Absent</option>
                            <option value="N" <?php if ($selected=="N") echo "selected"; ?>>No Record</option>
                        </select>
                    </td>
                </tr>
            <?php } ?>
        </table>

        <div style="text-align:center;">
            <button type="submit">Save Attendance</button>
        </div>
    </form>
</body>
</html>
