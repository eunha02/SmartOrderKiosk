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

$servername = "localhost";
$username = "root";
$password = "1234";
$dbname = "kioskDB";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$selectedYear = isset($_POST['year']) ? intval($_POST['year']) : date('Y');

function getSalesData($conn, $selectedYear, $branch) {
    $sqlSales = "SELECT MONTH(O.SaleDate) AS Month, SUM(S.QuantitySold * M.Price) AS TotalSales
                 FROM SalesAnalysisTBL S
                 JOIN Menutbl M ON S.MenuID = M.MenuID
                 JOIN OrderTBL O ON S.OrderID = O.OrderID
                 WHERE YEAR(O.SaleDate) = ? AND O.Branch = ?
                 GROUP BY MONTH(O.SaleDate)
                 ORDER BY Month ASC";
    $stmtSales = $conn->prepare($sqlSales);
    $stmtSales->bind_param("is", $selectedYear, $branch);
    $stmtSales->execute();
    $resultSales = $stmtSales->get_result();

    $salesData = [];
    while ($row = $resultSales->fetch_assoc()) {
        $salesData[] = [$row['Month'], (int)$row['TotalSales']];
    }

    return $salesData;
}

function calculateNextMonthSalesPrediction($salesData) {
    $monthsToAverage = 3; // 지난 3개월의 매출을 사용
    $smoothingFactor = 2 / ($monthsToAverage + 1);

    // 초기 SMA 계산
    $totalInitialSales = 0;
    $initialMonths = array_slice($salesData, -$monthsToAverage);

    foreach ($initialMonths as $monthSales) {
        $totalInitialSales += $monthSales[1];
    }
    $initialSMA = $totalInitialSales / $monthsToAverage;

    // 초기 EMA 설정
    $previousEMA = $initialSMA;

    // 최근 매출 데이터를 기반으로 EMA 계산
    foreach ($initialMonths as $monthSales) {
        $currentSales = $monthSales[1];
        $currentEMA = ($currentSales * $smoothingFactor) + ($previousEMA * (1 - $smoothingFactor));
        $previousEMA = $currentEMA;
    }

    return $previousEMA;
}

function getCurrentMonthSales($conn, $selectedYear, $branch) {
    $sqlCurrentMonthSales = "SELECT SUM(S.QuantitySold * M.Price) AS CurrentMonthSales
                             FROM SalesAnalysisTBL S
                             JOIN Menutbl M ON S.MenuID = M.MenuID
                             JOIN OrderTBL O ON S.OrderID = O.OrderID
                             WHERE YEAR(O.SaleDate) = ? AND MONTH(O.SaleDate) = MONTH(CURRENT_DATE())
                             AND O.Branch = ?";
    $stmtCurrentMonthSales = $conn->prepare($sqlCurrentMonthSales);
    $stmtCurrentMonthSales->bind_param("is", $selectedYear, $branch);
    $stmtCurrentMonthSales->execute();
    $resultCurrentMonthSales = $stmtCurrentMonthSales->get_result();
    $currentMonthSales = ($resultCurrentMonthSales->num_rows > 0) ? $resultCurrentMonthSales->fetch_assoc()['CurrentMonthSales'] : 0;

    return $currentMonthSales;
}

function getPopularMenus($conn, $selectedYear, $branch) {
    $sqlPopularity = "SELECT U.Gender, FLOOR(TIMESTAMPDIFF(YEAR, U.DOB, CURDATE()) / 10) * 10 AS Decade, M.MenuItem, M.SpicinessLevel, SUM(S.QuantitySold) AS TotalQuantity
                      FROM SalesAnalysisTBL S
                      JOIN Menutbl M ON S.MenuID = M.MenuID
                      JOIN Usertbl U ON S.UserID = U.UserID
                      JOIN OrderTBL O ON S.OrderID = O.OrderID
                      WHERE O.Branch = ? AND YEAR(O.SaleDate) = ?
                      GROUP BY U.Gender, Decade, M.MenuItem, M.SpicinessLevel
                      ORDER BY U.Gender, Decade, TotalQuantity DESC";
    $stmtPopularity = $conn->prepare($sqlPopularity);
    $stmtPopularity->bind_param("si", $branch, $selectedYear);
    $stmtPopularity->execute();
    $resultPopularity = $stmtPopularity->get_result();

    $popularMenus = [];
    while ($row = $resultPopularity->fetch_assoc()) {
        $gender = $row['Gender'];
        $decade = $row['Decade'] . '대';
        if (!isset($popularMenus[$gender])) {
            $popularMenus[$gender] = [];
        }
        if (!isset($popularMenus[$gender][$decade])) {
            $popularMenus[$gender][$decade] = $row;
        }
    }

    return $popularMenus;
}

$salesData = getSalesData($conn, $selectedYear, $branch);
$nextMonthSalesPrediction = calculateNextMonthSalesPrediction($salesData);
$currentMonthSales = getCurrentMonthSales($conn, $selectedYear, $branch);
$popularMenus = getPopularMenus($conn, $selectedYear, $branch);

// 종료 시간 측정
$end_time = microtime(true);

// 시간 차이 계산 (초 단위)
$time_diff = $end_time - $start_time;

// 성능 측정 결과를 로그 파일에 기록
$logFile = 'performance_log_sales.txt';
$logMessage = date('Y-m-d H:i:s') . " - Processing time: " . $time_diff . " seconds\n";
file_put_contents($logFile, $logMessage, FILE_APPEND);

$conn->close();
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>스마트 오더 키오스크 관리자 화면</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            min-height: 100vh; /* 화면의 최소 높이 설정 */
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
            display: block;
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
            flex-grow: 1; /* 남은 공간을 모두 차지 */
            padding: 20px;
            background-color: #f4f4f4;
            display: flex;
            flex-direction: column; /* 자식 요소들을 세로로 배치 */
        }
        .admin-info {
            margin-bottom: 20px;
            font-size: 24px;
            color: #333;
        }
        .chart-container {
            flex: 1; /* 자식 요소들이 동일한 공간을 차지하도록 설정 */
            margin-bottom: 20px;
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            color: #333; /* 표 내 글씨색을 검은색으로 변경 */
        }
        th {
            background-color: #FFD700;
            color: black;
        }
        .flex-container {
            display: flex;
            flex-direction: row;
            justify-content: space-between; /* 요소들을 좌우로 최대한 펼쳐서 배치 */
        }
        .chart-container + .chart-container {
            margin-left: 20px; /* 두 차트 사이에 살짝 공간을 띄우기 */
        }
        .form-container {
            margin-bottom: 20px;
        }
        button[type="submit"] {
            padding: 10px 15px; /* 버튼의 세로 길이를 줄임 */
            background-color: #FFD700;
            color: black; /* 버튼의 텍스트 색상을 검은색으로 변경 */
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        button[type="submit"]:hover {
            background-color: #FFCA00;
        }
    </style>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
        google.charts.load('current', {'packages':['corechart']});
        google.charts.setOnLoadCallback(drawChart);

        function drawChart() {
            var data = new google.visualization.DataTable();
            data.addColumn('string', 'Month');
            data.addColumn('number', 'Sales');
            data.addRows([
                <?php foreach ($salesData as $item): ?>
                ['<?= $item[0] ?>월', <?= $item[1] ?>],
                <?php endforeach; ?>
            ]);

            var options = {
                title: '연간 매출 추이',
                hAxis: {title: 'Month'},
                vAxis: {title: 'Sales', format: 'currency'},
                legend: 'none'
            };

            var chart = new google.visualization.LineChart(document.getElementById('sales_chart'));
            chart.draw(data, options);
        }
    </script>
</head>
<body>

<div class="sidebar">
    <a href="스마트오더키오스크_매출현황.php">매출 현황</a>
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
    <div class="form-container">
        <form method="post" action="">
            <label for="year">조회 년도 선택:</label>
            <select id="year" name="year">
                <?php
                // 현재 연도부터 5년 전까지의 옵션을 생성
                $currentYear = date('Y');
                for ($i = $currentYear; $i >= $currentYear - 5; $i--) {
                    echo "<option value='$i'" . ($selectedYear == $i ? ' selected' : '') . ">$i</option>";
                }
                ?>
            </select>
            <button type="submit">조회</button>
        </form>
    </div>
    <div class="flex-container">
        <div class="chart-container">
            <h2>연간 매출 통계</h2>
            <div id="sales_chart" style="width: 100%; height: 400px;"></div>
            <table>
                <thead>
                    <tr>
                        <th>이번 달 매출 금액</th>
                        <th>총 매출 금액</th>
                        <th>다음 달 매출 예측</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?= '₩' . number_format($currentMonthSales, 0, '', ',') ?></td>
                        <td><?= '₩' . number_format(array_sum(array_column($salesData, 1)), 0, '', ',') ?></td>
                        <td><?= '₩' . number_format($nextMonthSalesPrediction, 0, '', ',') ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="chart-container">
            <h2>인기 메뉴 통계</h2>
            <?php foreach ($popularMenus as $gender => $ages): ?>
                <h3><?= $gender ?>별 인기 메뉴</h3>
                <table>
                    <thead>
                        <tr>
                            <th>연령대</th>
                            <th>메뉴</th>
                            <th>맵기</th>
                            <th>총 판매 수량</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ages as $age => $menu): ?>
                            <tr>
                                <td><?= $age ?></td>
                                <td><?= $menu['MenuItem'] ?></td>
                                <td><?= $menu['SpicinessLevel'] ?></td>
                                <td><?= $menu['TotalQuantity'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endforeach; ?>
        </div>
    </div>
</div>

</body>
</html>