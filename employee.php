<?php
// ตรวจสอบว่ามีการเรียก API หรือไม่
$isApi = isset($_GET['action']) || isset($_POST['action']);
if (!$isApi) {
    // ถ้าไม่ใช่ API call ให้แสดงหน้า HTML
    goto html_output;
}

// Database connection
function get_connection() {
    // $host = "dpg-cqofhjggph6c73b8ohng-a.singapore-postgres.render.com";
    // // $host = "dpg-cqofhjggph6c73b8ohng-a";
    // $db   = "truewallet_db";
    // $user = "truewallet_db_user";
    // $pass = "x7liT4P9dZS58ESjbimpct3H5ARCAeel";
    // $port = "5432";

    $host = getenv('DB_HOST');
    $db   = getenv('DB_NAME');
    $user = getenv('DB_USER');
    $pass = getenv('DB_PASS');
    $port = getenv('DB_PORT');
    $dsn = "pgsql:host=$host;port=$port;dbname=$db;";
    try {
        $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    } catch (PDOException $e) {
        throw new Exception("Connection failed: " . $e->getMessage());
    }
    return $pdo;
}

// function get_connection() {
//     $host = getenv('DB_HOST') ?: "dpg-cqofhjggph6c73b8ohng-a.singapore-postgres.render.com";
//     $db   = getenv('DB_NAME') ?: "truewallet_db";
//     $user = getenv('DB_USER') ?: "truewallet_db_user";
//     $pass = getenv('DB_PASS') ?: "x7liT4P9dZS58ESjbimpct3H5ARCAeel";
//     $port = getenv('DB_PORT') ?: "5432";
    
//     $dsn = "pgsql:host=$host;port=$port;dbname=$db;";
//     try {
//         $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
//     } catch (PDOException $e) {
//         throw new Exception("Connection failed: " . $e->getMessage());
//     }
//     return $pdo;
// }

// function get_connection() {
//     $host = "dpg-cqofhjggph6c73b8ohng-a.singapore-postgres.render.com";
//     // $host = "dpg-cqofhjggph6c73b8ohng-a";
//     $db   = "truewallet_db";
//     $user = "truewallet_db_user";
//     $pass = "x7liT4P9dZS58ESjbimpct3H5ARCAeel";
//     $port = "5432";

//     // $host = getenv('DB_HOST');
//     // $db   = getenv('DB_NAME');
//     // $user = getenv('DB_USER');
//     // $pass = getenv('DB_PASS');
//     // $port = getenv('DB_PORT');
//     $dsn = "pgsql:host=$host;port=$port;dbname=$db;";
//     try {
//         $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
//     } catch (PDOException $e) {
//         throw new Exception("Connection failed: " . $e->getMessage());
//     }
//     return $pdo;
// }


// Database functions
function getEmployeeTables($pdo) {
    $query = "SELECT table_name FROM information_schema.tables 
              WHERE table_schema = 'public' 
              AND table_name LIKE 'employee_%'";
    return $pdo->query($query)->fetchAll(PDO::FETCH_COLUMN);
}

function getEmployeeCount($pdo, $table) {
    $query = "SELECT COUNT(*) as count FROM " . $table;
    return $pdo->query($query)->fetch(PDO::FETCH_ASSOC)['count'];
}

function getEmployees($pdo, $table) {
    $query = "SELECT * FROM " . $table;
    return $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
}

function addEmployee($pdo, $table, $id, $name) {
    $query = "INSERT INTO $table (employee_id, employee_name) VALUES (:id, :name)";
    $stmt = $pdo->prepare($query);
    return $stmt->execute([
        ':id' => $id,
        ':name' => $name
    ]);
}

function deleteEmployee($pdo, $table, $id) {
    $query = "DELETE FROM $table WHERE employee_id = :id";
    $stmt = $pdo->prepare($query);
    return $stmt->execute([':id' => $id]);
}

// API Handling
try {
    if (isset($_GET['action']) || isset($_POST['action'])) {
        $pdo = get_connection();
        $action = $_GET['action'] ?? $_POST['action'];
        $response = ['status' => 'error', 'message' => 'Invalid action'];

        switch ($action) {
            case 'getTables':
                $response = ['status' => 'success', 'data' => getEmployeeTables($pdo)];
                break;

            case 'getEmployees':
                $table = $_GET['table'];
                $response = ['status' => 'success', 'data' => getEmployees($pdo, $table)];
                break;

            case 'addEmployee':
                $table = $_POST['table'];
                $id = $_POST['id'];
                $name = $_POST['name'];
                $detail = $_POST['detail'];
                if (addEmployee($pdo, $table, $id, $name, $detail)) {
                    $response = ['status' => 'success'];
                }
                break;

            case 'deleteEmployee':
                $table = $_POST['table'];
                $id = $_POST['id'];
                if (deleteEmployee($pdo, $table, $id)) {
                    $response = ['status' => 'success'];
                }
                break;
        }

        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}

// HTML Output
html_output:
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>ระบบจัดการข้อมูลพนักงาน</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-4">
        <h1 class="mb-4">ระบบจัดการข้อมูลพนักงาน</h1>
        <div id="tableList" class="row mb-4"></div>
        <div id="employeeList"></div>
    </div>

    <!-- Modal สำหรับเพิ่มพนักงาน -->
    <div class="modal fade" id="addEmployeeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">เพิ่มข้อมูลพนักงาน</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addEmployeeForm">
                        <input type="hidden" id="tableInput">
                        <div class="mb-3">
                            <label class="form-label">รหัสพนักงาน</label>
                            <input type="text" class="form-control" id="employeeId" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ชื่อพนักงาน</label>
                            <input type="text" class="form-control" id="employeeName" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">รายละเอียด</label>
                            <input type="text" class="form-control" id="employeeDetail">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                    <button type="button" class="btn btn-primary" onclick="saveEmployee()">บันทึก</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
    // โหลดรายการตารางเมื่อโหลดหน้าเว็บ
    document.addEventListener('DOMContentLoaded', loadTables);

    function loadTables() {
        fetch('employee.php?action=getTables')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const tableList = document.getElementById('tableList');
                    tableList.innerHTML = data.data.map(table => `
                            <div class='col-md-3 mb-3'>
                                <div class='card'>
                                    <div class='card-body'>
                                        <h5 class='card-title'>${table}</h5>
                                        <button class='btn btn-primary' onclick='showEmployees("${table}")'>ดูข้อมูล</button>
                                    </div>
                                </div>
                            </div>
                        `).join('');
                }
            })
            .catch(error => console.error('Error:', error));
    }

    function showEmployees(table) {
        fetch(`employee.php?action=getEmployees&table=${encodeURIComponent(table)}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const employeeList = document.getElementById('employeeList');
                    employeeList.innerHTML = `
                            <h2>${table}</h2>
                            <button class='btn btn-success mb-3' onclick='addEmployee("${table}")'>เพิ่มพนักงาน</button>
                            <table class='table table-striped'>
                                <thead>
                                    <tr>
                                        <th>รหัสพนักงาน</th>
                                        <th>ชื่อพนักงาน</th>
                                        <th>รายละเอียด</th>
                                        <th>การจัดการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${data.data.map(emp => `
                                        <tr>
                                            <td>${emp.employee_id}</td>
                                            <td>${emp.employee_name}</td>
                                            <td>${emp.detail || ''}</td>
                                            <td>
                                                <button class='btn btn-danger btn-sm' onclick='deleteEmployee("${table}", "${emp.employee_id}")'>ลบ</button>
                                            </td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        `;
                }
            })
            .catch(error => console.error('Error:', error));
    }

    function addEmployee(table) {
        document.getElementById('tableInput').value = table;
        new bootstrap.Modal(document.getElementById('addEmployeeModal')).show();
    }

    function saveEmployee() {
        const table = document.getElementById('tableInput').value;
        const id = document.getElementById('employeeId').value;
        const name = document.getElementById('employeeName').value;
        const detail = document.getElementById('employeeDetail').value;

        const formData = new FormData();
        formData.append('action', 'addEmployee');
        formData.append('table', table);
        formData.append('id', id);
        formData.append('name', name);
        formData.append('detail', detail);

        fetch('employee.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    bootstrap.Modal.getInstance(document.getElementById('addEmployeeModal')).hide();
                    showEmployees(table);
                } else {
                    alert('เกิดข้อผิดพลาด: ' + data.message);
                }
            })
            .catch(error => console.error('Error:', error));
    }

    function deleteEmployee(table, id) {
        if (confirm('ต้องการลบข้อมูลพนักงานใช่หรือไม่?')) {
            const formData = new FormData();
            formData.append('action', 'deleteEmployee');
            formData.append('table', table);
            formData.append('id', id);

            fetch('employee.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showEmployees(table);
                    } else {
                        alert('เกิดข้อผิดพลาด: ' + data.message);
                    }
                })
                .catch(error => console.error('Error:', error));
        }
    }
    </script>
</body>

</html>