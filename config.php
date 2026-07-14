<?php
// ============================================================
// إعدادات قاعدة البيانات
// ============================================================
$servername = "sql312.infinityfree.com";
$username   = "if0_42361976";
$password   = "Rayan5013";
$dbname     = "if0_42361976_account";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// ============================================================
// معالجة طلبات API
// ============================================================
$action = $_GET['action'] ?? '';

if (!empty($action)) {

    header('Content-Type: application/json; charset=utf-8');

    // ============================================================
    // جلب جميع العملاء
    // ============================================================
    if ($action === "fetch") {

        $sql = "SELECT * FROM customer ORDER BY customer_id DESC";
        $result = $conn->query($sql);

        if (!$result) {
            echo json_encode([
                "status" => "error",
                "message" => $conn->error
            ]);
            exit;
        }

        $rows = [];

        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        echo json_encode([
            "status" => "success",
            "data" => $rows
        ]);

        exit;
    }

    // ============================================================
    // إضافة عميل
    // ============================================================
    if ($action === "insert") {

        $customer_id = trim($_POST["customer_id"] ?? "");
        $firstname   = trim($_POST["firstname"] ?? "");
        $lastname    = trim($_POST["lastname"] ?? "");
        $age         = intval($_POST["age"] ?? 0);

        if (
            empty($customer_id) ||
            empty($firstname) ||
            empty($lastname) ||
            $age < 1 ||
            $age > 130
        ) {
            echo json_encode([
                "status" => "error",
                "message" => "البيانات غير صحيحة"
            ]);
            exit;
        }

        // التحقق من عدم تكرار رقم العميل
        $check = $conn->prepare("SELECT customer_id FROM customer WHERE customer_id = ?");

        if (!$check) {
            echo json_encode([
                "status" => "error",
                "message" => $conn->error
            ]);
            exit;
        }

        $check->bind_param("s", $customer_id);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            echo json_encode([
                "status" => "error",
                "message" => "معرف العميل موجود مسبقاً"
            ]);
            $check->close();
            exit;
        }

        $check->close();

        // إضافة العميل
        $stmt = $conn->prepare("INSERT INTO customer (customer_id, firstname, lastname, age, status)
                                VALUES (?, ?, ?, ?, 0)");

        if (!$stmt) {
            echo json_encode([
                "status" => "error",
                "message" => $conn->error
            ]);
            exit;
        }

        $stmt->bind_param(
            "sssi",
            $customer_id,
            $firstname,
            $lastname,
            $age
        );

        if ($stmt->execute()) {

            echo json_encode([
                "status" => "success",
                "message" => "تمت إضافة العميل بنجاح"
            ]);

        } else {

            echo json_encode([
                "status" => "error",
                "message" => $stmt->error
            ]);
        }

        $stmt->close();
        exit;
    }

    // ============================================================
    // تغيير حالة العميل
    // ============================================================
    if ($action === "toggle") {

        $customer_id = $_POST["customer_id"] ?? "";
        $status = intval($_POST["status"] ?? 0);

        $stmt = $conn->prepare("UPDATE customer SET status = ? WHERE customer_id = ?");

        if (!$stmt) {
            echo json_encode([
                "status" => "error",
                "message" => $conn->error
            ]);
            exit;
        }

        $stmt->bind_param("is", $status, $customer_id);

        if ($stmt->execute()) {

            echo json_encode([
                "status" => "success",
                "message" => "تم تحديث الحالة"
            ]);

        } else {

            echo json_encode([
                "status" => "error",
                "message" => $stmt->error
            ]);
        }

        $stmt->close();
        exit;
    }

    echo json_encode([
        "status" => "error",
        "message" => "إجراء غير صحيح"
    ]);

    exit;
}

$conn->close();
?>