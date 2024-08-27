<?php
session_start();

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

// 한국 시간 기준으로 현재 날짜와 시간을 가져옵니다.
date_default_timezone_set('Asia/Seoul');
$selectedYear = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$selectedMonth = isset($_GET['month']) ? intval($_GET['month']) : date('m');
$today = date('j');

// 매출 데이터 조회
$sqlSales = "SELECT DAY(O.SaleDate) AS Day, SUM(S.QuantitySold * M.Price) AS TotalSales
             FROM SalesAnalysisTBL S
             JOIN Menutbl M ON S.MenuID = M.MenuID
             JOIN OrderTBL O ON S.OrderID = O.OrderID
             WHERE YEAR(O.SaleDate) = ? AND MONTH(O.SaleDate) = ? AND O.Branch = ?
             GROUP BY DAY(O.SaleDate)";
$stmtSales = $conn->prepare($sqlSales);
$stmtSales->bind_param("iis", $selectedYear, $selectedMonth, $branch);
$stmtSales->execute();
$resultSales = $stmtSales->get_result();

$salesData = [];
while ($row = $resultSales->fetch_assoc()) {
    $salesData[$row['Day']] = $row['TotalSales'];
}

// 주문 건수 조회
$sqlOrderCount = "SELECT DAY(O.SaleDate) AS Day, COUNT(O.OrderID) AS OrderCount
                  FROM OrderTBL O
                  WHERE YEAR(O.SaleDate) = ? AND MONTH(O.SaleDate) = ? AND O.Branch = ?
                  GROUP BY DAY(O.SaleDate)";
$stmtOrderCount = $conn->prepare($sqlOrderCount);
$stmtOrderCount->bind_param("iis", $selectedYear, $selectedMonth, $branch);
$stmtOrderCount->execute();
$resultOrderCount = $stmtOrderCount->get_result();

$orderCountData = [];
while ($row = $resultOrderCount->fetch_assoc()) {
    $orderCountData[$row['Day']] = $row['OrderCount'];
}

$stmtSales->close();
$stmtOrderCount->close();
$conn->close();

function drawCalendar($year, $month, $salesData, $orderCountData, $today) {
    $firstDayOfMonth = date('w', strtotime("$year-$month-01"));
    $daysInMonth = date('t', strtotime("$year-$month-01"));
    $day = 1;
    $calendar = '<table><thead><tr>';
    $daysOfWeek = ['일', '월', '화', '수', '목', '금', '토'];

    foreach ($daysOfWeek as $dayName) {
        $calendar .= "<th>$dayName</th>";
    }
    $calendar .= '</tr></thead><tbody>';

    for ($i = 0; $i < 6; $i++) {
        $calendar .= '<tr>';
        for ($j = 0; $j < 7; $j++) {
            if ($i === 0 && $j < $firstDayOfMonth) {
                $calendar .= '<td></td>';
            } else if ($day > $daysInMonth) {
                $calendar .= '<td></td>';
            } else {
                $isToday = ($day == $today && $month == date('m') && $year == date('Y')) ? 'today' : '';
                $sales = isset($salesData[$day]) ? '₩' . number_format($salesData[$day], 0, '', ',') : '₩0';
                $orderCount = isset($orderCountData[$day]) ? $orderCountData[$day] . '건' : '0건';
                
                $yesterday = $day - 1;
                $yesterdaySales = isset($salesData[$yesterday]) ? $salesData[$yesterday] : 0;
                $todaySales = isset($salesData[$day]) ? $salesData[$day] : 0;
                
                $emoji = '';
                if ($yesterdaySales !== 0 && $day <= $today) {
                    $emoji = $todaySales > $yesterdaySales ? '😍' : '😭';
                }

                $dayCircle = $isToday ? "<div class='day-circle'>$day</div>" : "<div class='day-no-circle'>$day</div>";
                $calendar .= "<td class='$isToday' data-date='$year-$month-$day'>
                              $dayCircle
                              <div class='sales'>$sales $emoji</div>
                              <div class='orders'>$orderCount</div>
                              </td>";
                $day++;
            }
        }
        $calendar .= '</tr>';
    }
    $calendar .= '</tbody></table>';
    return $calendar;
}

$calendarHtml = drawCalendar($selectedYear, $selectedMonth, $salesData, $orderCountData, $today);
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

        .dashboard {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .dashboard-section {
            background-color: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .dashboard-section h2 {
            font-size: 20px;
            margin-bottom: 15px;
        }

        .calendar {
            width: 80%;
            margin: 0 auto;
            background-color: #fff;
            border-radius: 10px;
            padding: 50px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .calendar table {
            width: 100%;
            border-collapse: collapse;
        }

        .calendar th,
        .calendar td {
            width: 14.28%;
            border: 1px solid #ccc;
            padding: 20px; /* 패딩을 20px로 설정 */
            text-align: center;
            vertical-align: top;
            position: relative;
        }

        .calendar th {
            background-color: #FFD700;
            color: #333;
        }

        .calendar td .day-circle {
            display: inline-block;
            width: 24px;
            height: 24px;
            line-height: 24px;
            border-radius: 50%;
            background-color: #ffeb3b;
            color: #333;
            font-weight: bold;
        }

        .calendar td .day-no-circle {
            display: inline-block;
            width: 24px;
            height: 24px;
            line-height: 24px;
            border-radius: 50%;
            background-color: transparent;
            color: #333;
            font-weight: bold;
        }

        .calendar td .sales {
            display: block;
            font-size: 14px;
            color: #007BFF;
            margin-top: 5px;
        }

        .calendar td .orders {
            display: block;
            font-size: 12px;
            color: #333;
            margin-top: 5px;
        }

        .month-selector form {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 20px;
        }

        .month-selector select,
        .month-selector button {
            padding: 10px;
            margin-right: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }

        .month-selector button {
            background-color: #FFD700;
            color: black;
            border: none;
            cursor: pointer;
        }

        .month-selector button:hover {
            background-color: #FFCA00;
        }
    </style>
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
    <div class="dashboard">
        <div class="dashboard-section calendar">
            <div class="month-selector">
                <form method="get" action="">
                    <select name="year">
                        <?php
                        $currentYear = date('Y');
                        for ($i = $currentYear; $i >= $currentYear - 5; $i--) {
                            echo "<option value='$i'" . ($selectedYear == $i ? ' selected' : '') . ">$i</option>";
                        }
                        ?>
                    </select>
                    <select name="month">
                        <?php
                        for ($m = 1; $m <= 12; $m++) {
                            $selected = ($m == $selectedMonth) ? 'selected' : '';
                            $monthText = $m . '월';
                            echo "<option value='$m' $selected>$monthText</option>";
                        }
                        ?>
                    </select>
                    <button type="submit">확인</button>
                </form>
            </div>
            <?php echo $calendarHtml; ?>
        </div>
    </div>
</div>
</body>
</html>