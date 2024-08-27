<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>주문 확인</title>
    <style>
        @font-face {
            font-family: 'GmarketSansMedium';
            src: url('https://fastly.jsdelivr.net/gh/projectnoonnu/noonfonts_2001@1.1/GmarketSansMedium.woff') format('woff');
            font-weight: normal;
            font-style: normal;
        }

        body {
            margin: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background-image: url('결제완료배경.png');
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
            font-family: 'GmarketSansMedium', monospace;
        }

        .receipt-container {
            position: relative;
            max-width: 375px;
            width: 100%;
            padding: 0;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            align-items: flex-start; /* 주문번호와 코너를 왼쪽 정렬 */
        }

        .order-number {
            font-size: 45px;
            color: black;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            margin-top: 300px;
            margin-bottom: 0px;
            margin-left : 110px;
        }

        .branch-info {
            font-size: 45px;
            margin-top: 0px;
            margin-left : 110px;
            margin-bottom: 210px;
        }
        /*확인 버튼*/ 
        .complete-button {
            margin-top: 50px; /* 확인 버튼과의 간격 조정 */
            margin-right : 20px;
            margin-bottom: 10px;
            align-self: flex-end; /* 확인 버튼을 오른쪽 정렬 */
            width: 80%;
            padding: 12px;
            background-color: transparent;
            color: black;
            font-size: 40px;
            border-radius: 5px;
            text-decoration: none;
            text-align: center;
            
        }
        
        .complete-message {
            font-size: 30px;
            margin-top: 20px; 
            align-self: left; 
            margin-left: -115px; 
            margin-bottom: -45px; 
        }
    </style>
</head>
<body>
<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');

$servername = "localhost";
$username = "root";
$password = "1234";
$dbname = "kioskDB";

date_default_timezone_set('Asia/Seoul');
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$userID = $_SESSION['userID'];
$branch = isset($_SESSION['branch']) ? $_SESSION['branch'] : "";
$today = date("Y-m-d");

$orderQuery = "SELECT OrderNumber, branch FROM OrderTBL WHERE SaleDate = '$today' AND Branch = '$branch' ORDER BY OrderID DESC LIMIT 1";
$orderResult = $conn->query($orderQuery);
$orderNumber = 0;
if ($orderResult->num_rows > 0) {
    $row = $orderResult->fetch_assoc();
    $orderNumber = $row["OrderNumber"];
    $branch = $row["branch"];
}
$conn->close();
?>
<div class="receipt-container">
    <div class="order-number"><?php echo $orderNumber; ?>번</div>
    <div class="branch-info"><?php echo nl2br($branch); ?></div>
    <a href="scanner.html" class="complete-button">확인</a>
</div>
</body>
</html>