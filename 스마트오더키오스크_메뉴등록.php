<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "1234";
$dbname = "kioskDB";

// 데이터베이스 연결
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("연결 실패: " . $conn->connect_error);
}

if (!isset($_SESSION['userID']) || !isset($_SESSION['branch'])) {
    exit();
}

$userID = $_SESSION['userID'];
$branch = $_SESSION['branch'];

// 메뉴 등록 처리
if(isset($_POST['register'])) {
    // 세션에서 지점 정보 가져오기
    $branch = $_SESSION['branch'];

    // 다른 입력값들도 가져오기
    $menuItem = $_POST['menuItem'];
    $mainCategory = $_POST['mainCategory'];
    $subCategory = $_POST['subCategory'];
    $spicinessLevel = $_POST['spicinessLevel'];
    $price = $_POST['price'];

    // 중복 메뉴 이름 확인
    $checkSql = "SELECT COUNT(*) as count FROM Menutbl WHERE MenuItem = ? AND Branch = ?";
    $stmt = $conn->prepare($checkSql);
    $stmt->bind_param("ss", $menuItem, $branch);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    if ($result['count'] > 0) {
        echo "<script>alert('동일한 이름의 메뉴가 이미 존재합니다.');</script>";
    } else {
        // 메뉴 등록 쿼리 실행
        $registerMenuSql = "INSERT INTO Menutbl (MenuItem, MainCategory, SubCategory, SpicinessLevel, Price, Branch) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($registerMenuSql);
        $stmt->bind_param("ssssis", $menuItem, $mainCategory, $subCategory, $spicinessLevel, $price, $branch);
        if ($stmt->execute()) {
            echo "<script>alert('메뉴가 성공적으로 등록되었습니다.');</script>";
        } else {
            echo "<script>alert('메뉴 등록에 실패했습니다: " . $conn->error . "');</script>";
        }
    }
}


$conn->close();
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>메뉴 등록</title>
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
            text-align: left;
            padding-left: 20px;
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

        input[type="text"], select {
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
    <div class="container">
        <div class="center">
            <h2>메뉴 등록</h2>
            <form id="registerForm" method="post">
                <label for="menuItem">메뉴 이름:</label>
                <input type="text" id="menuItem" name="menuItem" required>

                <label for="mainCategory">대분류:</label>
                <select id="mainCategory" name="mainCategory" required>
                    <option value="한식">한식</option>
                    <option value="일식">일식</option>
                    <option value="중식">중식</option>
                    <option value="양식">양식</option>
                </select>

                <label for="subCategory">소분류:</label>
                <select id="subCategory" name="subCategory" required>
                    <option value="밥">밥</option>
                    <option value="면">면</option>
                    <option value="튀김">튀김</option>
                </select>

                <label for="spicinessLevel">매운 정도:</label>
                <select id="spicinessLevel" name="spicinessLevel" required>
                    <?php
                    for ($i = 0; $i <= 5; $i++) {
                        echo "<option value='$i'>$i</option>";
                    }
                    ?>
                </select>

                <label for="price">가격:</label>
                <input type="text" id="price" name="price" required>

                <input type="submit" name="register" value="등록">
            </form>
        </div>
    </div>
</div>

</body>
</html>
