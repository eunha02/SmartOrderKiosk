<?php
session_start();

if (!isset($_SESSION['userID']) || !isset($_SESSION['branch'])) {
    header("Location: login.php");
    exit();
}

$userID = $_SESSION['userID'];
$branch = $_SESSION['branch'];

// 시작 시간 측정
$start_time = microtime(true);

// 데이터베이스 설정
$servername = "localhost";
$username = "root";
$password = "1234";
$dbname = "kioskDB";

// 데이터베이스 연결
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 년도 선택 처리
$year = isset($_POST['year']) ? intval($_POST['year']) : date('Y');

$sql = "SELECT MONTH(SaleDate) AS Month, SUM(S.QuantitySold * M.Price) AS TotalSales
        FROM SalesAnalysisTBL S
        JOIN Menutbl M ON S.MenuID = M.MenuID
        JOIN OrderTBL O ON S.OrderID = O.OrderID
        WHERE YEAR(SaleDate) = ? AND O.Branch = ?
        GROUP BY MONTH(SaleDate)
        ORDER BY Month ASC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die('MySQL prepare error: ' . $conn->error);
}

$stmt->bind_param("is", $year, $branch);
if (!$stmt->execute()) {
    die('Execute error: ' . $stmt->error);
}

$result = $stmt->get_result();
if (!$result) {
    die('Result fetch error: ' . $conn->error);
}

$monthlySales = [];
$totalSales = 0;
while ($row = $result->fetch_assoc()) {
    $monthlySales[] = $row;
    $totalSales += $row['TotalSales'];
}

// 최근 3개월 총 매출금액 구하기
$sql_recent = "SELECT SUM(S.QuantitySold * M.Price) AS TotalRecentSales
               FROM SalesAnalysisTBL S
               JOIN Menutbl M ON S.MenuID = M.MenuID
               JOIN OrderTBL O ON S.OrderID = O.OrderID
               WHERE YEAR(SaleDate) = ?
               AND SaleDate >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
               AND O.Branch = ?";

$stmt_recent = $conn->prepare($sql_recent);
if (!$stmt_recent) {
    die('Prepare error: ' . $conn->error);
}
$stmt_recent->bind_param("is", $year, $branch);
$stmt_recent->execute();
$result_recent = $stmt_recent->get_result();
if (!$result_recent) {
    die('Result fetch error: ' . $stmt_recent->error);
}
$row_recent = $result_recent->fetch_assoc();
$totalRecentSales = $row_recent ? $row_recent['TotalRecentSales'] : 0;

// 다음달 매출 예측: 최근 3개월 평균으로 계산
$averageSales = $totalRecentSales / 3; // 최근 3개월 평균 매출
$averageSales = round($averageSales); // 소수점 제거

// 종료 시간 측정
$end_time = microtime(true);

// 시간 차이 계산 (초 단위)
$time_diff = $end_time - $start_time;

// 성능 측정 결과를 로그 파일에 기록
$logFile = 'performance_log_sales.txt';
$logMessage = date('Y-m-d H:i:s') . " - Processing time: " . $time_diff . " seconds\n";
file_put_contents($logFile, $logMessage, FILE_APPEND);

$stmt_recent->close();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>매출 요약</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            display: flex;
        }

        .sidebar {
            width: 250px;
            background-color: #1a1a1a;
            height: 100vh;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .sidebar a {
            color: white;
            padding: 10px;
            text-decoration: none;
            font-size: 18px;
            margin-bottom: 10px;
        }

        .sidebar a:hover {
            background-color: #575757;
        }

        .main-content {
            flex-grow: 1;
            padding: 20px;
            background-color: #f4f4f4;
        }

        .admin-info {
            margin-bottom: 20px;
            font-size: 24px;
            color: #333;
        }

        table {
            border-collapse: collapse;
            width: 80%;
            margin-top: 20px;
            float: left;
            margin-right: 20px;
        }

        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
            color: black; /* 검은색 텍스트로 변경 */
        }

        th {
            background-color: #FFD700;
            cursor: pointer;
            color: black;
        }

        th:hover {
            background-color: #3c64c9;
        }

        form {
            margin-bottom: 20px;
            clear: both; /* 추가된 부분 */
        }

        select {
            font-size: 18px;
            padding: 8px;
            color: black;
            background-color: white;
            border: 1px solid #000;
            cursor: pointer;
        }

        input[type="submit"] {
            font-size: 18px;
            padding: 8px;
            background-color: #FFD700;
            color: black; /* 검은색 텍스트로 변경 */
            border: none;
            cursor: pointer;
        }

        .total-sales {
            font-size: 24px;
            margin-top: 20px;
            color: black; /* 검은색 텍스트로 변경 */
        }

        .prediction {
            margin-top: 10px;
            margin-left: 10px;
            font-size: 18px;
            color: #3c64c9; /* 파란색 텍스트로 변경 */
        }
    </style>
    <script>
        function sortTable(n) {
            var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
            table = document.getElementById("salesTable");
            switching = true;
            dir = "asc";
            while (switching) {
                switching = false;
                rows = table.rows;
                for (i = 1; i < (rows.length - 1); i++) {
                    shouldSwitch = false;
                    x = rows[i].getElementsByTagName("TD")[n];
                    y = rows[i + 1].getElementsByTagName("TD")[n];
                    if (dir == "asc") {
                        if (n === 0) {
                            if (Number(x.innerHTML.replace("월", "")) > Number(y.innerHTML.replace("월", ""))) {
                                shouldSwitch = true;
                                break;
                            }
                        } else {
                            if (Number(x.innerHTML.replace("원", "").replace(",", "")) > Number(y.innerHTML.replace("원", "").replace(",", ""))) {
                                shouldSwitch = true;
                                break;
                            }
                        }
                    } else if (dir == "desc") {
                        if (n === 0) {
                            if (Number(x.innerHTML.replace("월", "")) < Number(y.innerHTML.replace("월", ""))) {
                                shouldSwitch = true;
                                break;
                            }
                        } else {
                            if (Number(x.innerHTML.replace("원", "").replace(",", "")) < Number(y.innerHTML.replace("원", "").replace(",", ""))) {
                                shouldSwitch = true;
                                break;
                            }
                        }
                    }
                }
                if (shouldSwitch) {
                    rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
                    switching = true;
                    switchcount++;
                } else {
                    if (switchcount == 0 && dir == "asc") {
                        dir = "desc";
                        switching = true;
                    }
                }
            }
        }

        window.onload = function() {
            sortTable(0);
        };
    </script>
</head>
<body>

<div class="sidebar">
    <a href="스마트오더키오스크_매출요약.php">매출 요약 보기</a>
    <a href="스마트오더키오스크_통계.php">통계표 확인</a>
    <a href="스마트오더키오스크_식단관리메뉴.php">식단 관리 표</a>
    <a href="스마트오더키오스크_식단관리표완료.php">식단 표 확인</a>
    <a href="스마트오더키오스크_메뉴등록.php">메뉴 등록</a>
    <a href="scanner.html">시작 화면으로 이동</a>
</div>

<div class="main-content">
    <div class="admin-info">
        <?php echo $userID; ?> 님, 안녕하세요. | 지점: <?php echo $branch; ?>
    </div>

    <h1>매출 요약</h1>
    <form action="" method="post">
        <span>년도 선택:</span>
        <select name="year" id="year">
            <?php
            for ($i = date('Y'); $i >= date('Y') - 5; $i--) {
                echo "<option value='$i' " . ($i == $year ? "selected" : "") . ">$i</option>";
            }
            ?>
        </select>
        <input type="submit" value="조회">
    </form>

    <?php if (!empty($monthlySales)): ?>
        <table id="salesTable">
            <tr>
                <th onclick='sortTable(0)'>월</th>
                <th onclick='sortTable(1)'>매출액</th>
            </tr>
            <?php foreach ($monthlySales as $sales): ?>
                <tr>
                    <td><?php echo $sales['Month']; ?>월</td>
                    <td><?php echo number_format($sales['TotalSales']); ?>원</td>
                </tr>
            <?php endforeach; ?>
        </table>
        <div class='total-sales'>총매출금액: <?php echo number_format($totalSales); ?>원</div>
        <div class='prediction'>다음달 매출 예측: <?php echo number_format($averageSales); ?>원</div>
    <?php else: ?>
        <p>해당 연도의 매출 정보가 없습니다.</p>
    <?php endif; ?>
</div>

</body>
</html>