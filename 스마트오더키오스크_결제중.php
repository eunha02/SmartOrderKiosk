<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');

if (!isset($_SESSION['userID']) || !isset($_SESSION['branch'])) {
    echo "<script>alert('Session information not found. Redirecting to login.'); window.location.href='login.php';</script>";
    exit;
}

$servername = "localhost";
$username = "root";
$password = "1234";
$dbname = "kioskDB";

// 데이터베이스 연결
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$userID = $_SESSION['userID'];
$branch = $_SESSION['branch'];
$today = date("Y-m-d");  // 오늘 날짜 설정

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cartItems = json_decode(file_get_contents('php://input'), true)['cartItems'];

    // 주문 테이블에 주문을 추가합니다.
    $orderInsertQuery = "INSERT INTO OrderTBL (SaleDate, Branch, OrderNumber) VALUES (CURDATE(), ?, NULL)";
    $stmt = $conn->prepare($orderInsertQuery);
    $stmt->bind_param("s", $branch);
    $stmt->execute();
    $orderID = $stmt->insert_id; // 방금 삽입한 주문의 ID 가져오기
    $stmt->close();

    foreach ($cartItems as $item) {
        $menuID = $item['id']; // 수정: 메뉴 ID 사용

        // 메뉴의 지점 정보를 가져옵니다.
        $menuBranchQuery = "SELECT Branch FROM Menutbl WHERE MenuID = ?";
        $stmt = $conn->prepare($menuBranchQuery);
        $stmt->bind_param("i", $menuID);
        $stmt->execute();
        $menuBranchResult = $stmt->get_result();

        if ($menuBranchResult->num_rows > 0) {
            $menuBranchRow = $menuBranchResult->fetch_assoc();
            $menuBranch = $menuBranchRow['Branch'];

            // 해당 메뉴의 지점과 주문하는 지점이 일치하는지 확인합니다.
            if ($menuBranch === $branch) {
                // 판매 분석 테이블에 판매 정보를 추가합니다.
                $salesAnalysisInsertQuery = "INSERT INTO SalesAnalysisTBL (OrderID, MenuID, UserID, QuantitySold)
                VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($salesAnalysisInsertQuery);
                $stmt->bind_param("iiii", $orderID, $menuID, $userID, $item['quantity']);
                $stmt->execute();
                $stmt->close();

                // 식단 테이블에서 수량을 조정합니다.
                $menuPlanUpdateQuery = "UPDATE MenuplanTBL SET Quantity = Quantity - ? 
                WHERE MenuID = ? AND Branch = ? AND planDate = ?";
                $stmt = $conn->prepare($menuPlanUpdateQuery);
                $stmt->bind_param("iiss", $item['quantity'], $menuID, $branch, $today);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    // 주문 처리가 완료된 후에 데이터베이스 연결을 닫습니다.
    $conn->close();

    // 결제 완료 페이지로 이동합니다.
    echo "<script>window.location.href = '스마트오더키오스크_결제완료.php';</script>";
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>결제 중</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            flex-direction: column;
        }
        .loading-message {
            font-size: 24px;
            color: black;
            text-align: center;
        }
        .loader {
            border: 16px solid #f3f3f3;
            border-radius: 50%;
            border-top: 16px solid #3498db;
            width: 120px;
            height: 120px;
            animation: spin 2s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .complete-message {
            display: none;
            font-size: 24px;
            color: black;
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="loading-message">결제 처리 중입니다. 잠시만 기다려주세요.</div>
    <div class="complete-message" id="completeMessage">결제가 완료되었습니다.</div>
    <div class="loader"></div>
</body>
</html>
