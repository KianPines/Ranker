<?php
include 'db_connect.php';

if (isset($_POST['action']) && $_POST['action'] === 'add') {
  $name = trim($_POST['name']);
  $grade = floatval($_POST['grade']);
  if ($name !== "" && $grade >= 0 && $grade <= 100) {
    $stmt = $conn->prepare("INSERT INTO students (name, grade) VALUES (?, ?)");
    $stmt->bind_param("sd", $name, $grade);
    $stmt->execute();
    $stmt->close();
  }
  exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'delete') {
  $id = intval($_POST['id']);
  $conn->query("DELETE FROM students WHERE id=$id");
  exit;
}

if (isset($_GET['fetch'])) {
  $search = strtolower(trim($_GET['search'] ?? ''));
  $students = [];
  $result = $conn->query("SELECT * FROM students ORDER BY grade DESC");
  while ($row = $result->fetch_assoc()) {
    if ($search === '' || strpos(strtolower($row['name']), $search) !== false) {
      $students[] = $row;
    }
  }
  header("Content-Type: application/json");
  echo json_encode($students);
  exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>🏆 Student Rankings</title>
  <style>
    body { font-family: Arial, sans-serif; background-color: #f8f9fa; margin: 0; padding: 0; }
    header { background-color: #007bff; color: white; text-align: center; padding: 20px; }
    main { padding: 20px; text-align: center; padding-bottom: 100px; }
    input, button { padding: 8px; margin: 5px; border: 1px solid #ccc; border-radius: 4px; }
    button { background-color: #007bff; color: white; cursor: pointer; }
    button:hover { background-color: #0056b3; }
    table { width: 80%; margin: 30px auto; border-collapse: collapse; background-color: #ffffff; box-shadow: 0 0 10px rgba(0,0,0,0.1); border-spacing: 0 15px; }
    th, td { border: 1px solid #dddddd; text-align: center; padding: 12px 20px; }
    th { background-color: #007bff; color: white; }
    tr:nth-child(even) { background-color: #f2f2f2; }
    a { color: red; cursor: pointer; text-decoration: none; }
    footer { position: fixed; bottom: 0; left: 0; width: 100%; text-align: center; padding: 15px; background-color: #007bff; color: white; }
    @media (max-width: 700px) { table { width: 95%; } }
  </style>
</head>
<body>
  <header>
    <h1>🏆 Student Rankings</h1>
    <p>Top performing students based on grades</p>
  </header>

  <main>
    <div>
      <input id="name" placeholder="Student Name" />
      <input id="grade" type="number" step="0.1" placeholder="Grade" />
      <button onclick="addStudent()">Add Student</button>
    </div>

    <div style="margin-top: 30px;">
      <input id="search" placeholder="Search student name..." oninput="liveSearch()" />
      <button onclick="clearSearch()">Clear</button>
    </div>

    <table id="rankingTable">
      <tr><th>Rank</th><th>Name</th><th>Grade</th><th>Action</th></tr>
    </table>

    <h3 style="text-align:center; margin-top: 100px;">🏆 Students with Grades 85–100</h3>
    <table id="aboveAverageTable">
      <tr><th>Name</th><th>Grade</th></tr>
    </table>
  </main>

  <footer>&copy; 2025 Student Rankings Project</footer>

  <script>
    let searchQuery = "";

    async function fetchStudents() {
      const res = await fetch(`<?php echo basename(__FILE__); ?>?fetch=1&search=${encodeURIComponent(searchQuery)}`);
      return await res.json();
    }

    async function displayStudents() {
      const students = await fetchStudents();
      const table = document.getElementById('rankingTable');
      table.innerHTML = `<tr><th>Rank</th><th>Name</th><th>Grade</th><th>Action</th></tr>`;
      if (students.length === 0) {
        table.innerHTML += `<tr><td colspan="4">No students found</td></tr>`;
      }
      students.forEach((s, i) => {
        table.innerHTML += `
          <tr>
            <td>${i + 1}</td>
            <td>${escapeHtml(s.name)}</td>
            <td>${parseFloat(s.grade).toFixed(1)}</td>
            <td><a onclick="deleteStudent(${s.id})">Delete</a></td>
          </tr>
        `;
      });
      displayAboveAverage(students);
    }

    function displayAboveAverage(students) {
      const table = document.getElementById('aboveAverageTable');
      table.innerHTML = '<tr><th>Name</th><th>Grade</th></tr>';
      const above = students.filter(s => s.grade >= 85 && s.grade <= 100);
      if (above.length === 0) {
        table.innerHTML += `<tr><td colspan="2">No students found (85–100)</td></tr>`;
        return;
      }
      above.forEach(s => {
        table.innerHTML += `<tr><td>${escapeHtml(s.name)}</td><td>${parseFloat(s.grade).toFixed(1)}</td></tr>`;
      });
    }

    async function addStudent() {
      const name = document.getElementById('name').value.trim();
      const grade = parseFloat(document.getElementById('grade').value);
      if (!name || isNaN(grade) || grade < 0 || grade > 100) {
        alert("Enter a valid name and grade (0–100)");
        return;
      }
      await fetch("<?php echo basename(__FILE__); ?>", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ action: "add", name, grade })
      });
      document.getElementById('name').value = '';
      document.getElementById('grade').value = '';
      displayStudents();
    }

    async function deleteStudent(id) {
      if (!confirm("Delete this student?")) return;
      await fetch("<?php echo basename(__FILE__); ?>", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ action: "delete", id })
      });
      displayStudents();
    }

    function liveSearch() {
      searchQuery = document.getElementById('search').value.trim();
      displayStudents();
    }

    function clearSearch() {
      document.getElementById('search').value = '';
      searchQuery = '';
      displayStudents();
    }

    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }

    displayStudents();
  </script>
</body>
</html>
