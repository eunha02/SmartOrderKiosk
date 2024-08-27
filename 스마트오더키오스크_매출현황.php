<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['userID']) || !isset($_SESSION['branch'])) {
    header("Location: login.php");
    exit();
}

$userID = $_SESSION['userID'];
$branch = $_SESSION['branch'];

$servername = "localhost";
$username = "root";
$password = "1234";
$dbname = "kioskDB";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 현재 날짜 가져오기 (한국 시간대로 설정)
date_default_timezone_set('Asia/Seoul');
$currentDate = date('Y-m-d');

// 현재 일자의 요일 구하기
$currentWeekDay = date('w', strtotime($currentDate));
$weekDays = ['일요일', '월요일', '화요일', '수요일', '목요일', '금요일', '토요일'];
$currentWeekDayName = $weekDays[$currentWeekDay];

// 지난 주 같은 요일 구하기
$lastWeekSameDay = date('Y-m-d', strtotime('-1 week', strtotime($currentDate)));
$lastWeekDayName = $weekDays[date('w', strtotime($lastWeekSameDay))];

// 현재 주의 매출 데이터 가져오기
$sqlCurrentWeekSales = "SELECT DATE(SaleDate) AS Date, SUM(QuantitySold * Price) AS DailySales
                         FROM SalesAnalysisTBL S
                         JOIN Menutbl M ON S.MenuID = M.MenuID
                         JOIN OrderTBL O ON S.OrderID = O.OrderID
                         WHERE O.SaleDate BETWEEN DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND CURDATE()
                         AND O.Branch = '$branch'
                         GROUP BY DATE(O.SaleDate)
                         ORDER BY Date ASC";

$resultCurrentWeekSales = $conn->query($sqlCurrentWeekSales);

// 지난 주 같은 요일의 매출 데이터 가져오기
$sqlLastWeekSales = "SELECT DATE(SaleDate) AS Date, SUM(QuantitySold * Price) AS DailySales
                      FROM SalesAnalysisTBL S
                      JOIN Menutbl M ON S.MenuID = M.MenuID
                      JOIN OrderTBL O ON S.OrderID = O.OrderID
                      WHERE O.SaleDate BETWEEN DATE_SUB('$lastWeekSameDay', INTERVAL 6 DAY) AND '$lastWeekSameDay'
                      AND O.Branch = '$branch'
                      GROUP BY DATE(O.SaleDate)
                      ORDER BY Date ASC";

$resultLastWeekSales = $conn->query($sqlLastWeekSales);

$lastWeekSales = [];
if ($resultLastWeekSales->num_rows > 0) {
    while ($row = $resultLastWeekSales->fetch_assoc()) {
        $lastWeekSales[$row['Date']] = $row['DailySales'];
    }
}

// 현재 일자의 매출 데이터 가져오기
$currentDaySales = 0;
$weeklySalesData = [];
while ($row = $resultCurrentWeekSales->fetch_assoc()) {
    $weeklySalesData[$row['Date']] = $row['DailySales'];
    if ($row['Date'] === $currentDate) {
        $currentDaySales = $row['DailySales'];
    }
}

// 주문 건과 매출 증가율 계산하기
$sqlOrderCount = "SELECT COUNT(*) AS OrderCount
                    FROM OrderTBL
                    WHERE DATE(SaleDate) = CURDATE()
                    AND Branch = '$branch'";
$resultOrderCount = $conn->query($sqlOrderCount);
$orderCount = ($resultOrderCount->num_rows > 0) ? $resultOrderCount->fetch_assoc()['OrderCount'] : 0;

$salesIncrease = isset($lastWeekSales[$lastWeekSameDay]) ? $currentDaySales - $lastWeekSales[$lastWeekSameDay] : 0;
$salesIncreaseRate = isset($lastWeekSales[$lastWeekSameDay]) && $lastWeekSales[$lastWeekSameDay] > 0 ? ($salesIncrease / $lastWeekSales[$lastWeekSameDay]) * 100 : 0;

// 메시지 생성
if ($salesIncrease > 0) {
    $message = "실매출이 지난 $lastWeekDayName 보다 ₩" . number_format($salesIncrease) . " 늘었어요 😊";
} else {
    $message = "실매출이 지난 $lastWeekDayName 보다 ₩" . number_format(abs($salesIncrease)) . " 줄었어요 😢";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>스마트 오더 키오스크 매출 현황</title>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
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

        .container {
            max-width: 800px;
            margin: 50px auto;
            background-color: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .center {
            text-align: center;
        }

        h2 {
            margin-bottom: 20px;
            color: #333;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #333;
        }

        input[type="text"],
        select {
            width: calc(100% - 20px);
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }

        input[type="submit"] {
            width: 100%;
            padding: 15px;
            background-color: #FFD700;
            color: #333;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }

        input[type="submit"]:hover {
            background-color: #FFCA00;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 30px;
        }
        th, td {
            border: 1px solid #dddddd;
            text-align: center;
            padding: 8px;
        }
        th {
            background-color: #f2f2f2;
        }

        /* 추가된 스타일 */
        .sales-summary {
            display: flex;
            justify-content: space-around;
            margin-bottom: 20px;
        }
        .sales-summary div {
            background-color: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 30%;
            text-align: center;
        }
        .sales-summary div h3 {
            margin-bottom: 10px;
            color: #333;
        }
        .sales-summary div p {
            font-size: 24px;
            color: #333;
            margin: 0;
        }
        .sales-summary div p span {
            color: #FF0000;
            font-size: 14px;
        }
        .sales-summary div p.discount {
            color: #007BFF; /* 파란색 */
        }
        .message {
            text-align: center;
            font-size: 18px;
            color: #333;
            margin-top: 20px;
        }
    </style>
    <script>
    google.charts.load('current', {'packages':['corechart']});
    google.charts.setOnLoadCallback(drawChart);

    function drawChart() {
        var data = new google.visualization.DataTable();
        data.addColumn('string', 'Date');
        data.addColumn('number', 'Last Week Sales');
        data.addColumn('number', 'Current Week Sales');
        

        data.addRows([
            <?php
            // 주간 매출 데이터 배열을 순회하며 차트 데이터로 변환
            foreach ($weeklySalesData as $date => $sales) {
                $lastWeekSalesValue = isset($lastWeekSales[date('Y-m-d', strtotime('-1 week', strtotime($date)))]) ? $lastWeekSales[date('Y-m-d', strtotime('-1 week', strtotime($date)))] : 0;
                echo "['" . date('j일', strtotime($date)) . "', " . $lastWeekSalesValue . ", " . $sales . "],";
            }
            ?>
        ]);

        var options = {
            title: 'Weekly vs. Daily Sales',
            legend: { position: 'bottom' },
            bar: { groupWidth: '60%' },
            height: 350, // 그래프 영역의 세로 크기를 조절합니다.
            series: {
                0: { color: '#CCCCCC' }, // Last Week Sales (Gray)
                1: { color: '#FFD700' }  // Current Week Sales (Yellow)
            },
            tooltip: { isHtml: true } // 툴팁을 HTML 형식
        };

        var chart = new google.visualization.BarChart(document.getElementById('chart_div'));
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

    <h2 style="text-align: center;">매출 현황</h2>

    <div class="sales-summary">
        <div>
            <h3>실매출</h3>
            <p>₩<?php echo number_format($currentDaySales); ?><br><span>지난주 <?php echo $lastWeekDayName; ?>보다 ₩<?php echo number_format($salesIncrease); ?></span></p>
        </div>
        <div>
            <h3>주문건</h3>
            <p><?php echo $orderCount; ?>건<br><span>지난주 <?php echo $lastWeekDayName; ?>보다 +<?php echo $orderCount; ?>건</span></p>
        </div>
        <div>
         <h3>매출 증가율</h3>
            <p class="discount"><?php echo number_format($salesIncreaseRate, 2); ?>%<br><span>지난주 <?php echo $lastWeekDayName; ?>보다 +<?php echo number_format($salesIncreaseRate, 2); ?>%</span></p>
        </div>
    </div>

    <div id="chart_div"></div>

    <div class="message">
        <?php echo $message; ?>
    </div>

</div>

</body>
</html>