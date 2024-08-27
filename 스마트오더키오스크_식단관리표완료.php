<?php
session_start();

if (!isset($_SESSION['userID']) || !isset($_SESSION['branch'])) {
    exit;
}

$userID = $_SESSION['userID'];
$branch = $_SESSION['branch'];

$servername = "localhost";
$username = "root";
$password = "1234";
$dbname = "kioskDB";

// 데이터베이스 연결
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

date_default_timezone_set('Asia/Seoul');

$deleteMessage = null;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['deletePlan'])) {
    $deleteDate = $_POST['deleteDate'];

    // 선택한 날짜의 식단 정보 삭제
    if ($deleteDate === "전체") {
        $sql = "DELETE FROM MenuplanTBL";
    } else {
        $sql = "DELETE FROM MenuplanTBL WHERE planDate = ?";
    }

    $stmt = $conn->prepare($sql);
    if ($deleteDate !== "전체") {
        $stmt->bind_param("s", $deleteDate);
    }

    if ($stmt->execute()) {
        $deleteMessage = "삭제되었습니다.";
    } else {
        $deleteMessage = "삭제 실패했습니다.";
    }
}

// 카테고리 목록 및 이모티콘
$categories = [
    '한식' => '🍚',
    '일식' => '🍣',
    '중식' => '🍛',
    '양식' => '🍔'
];

// 식단 정보 조회
$currentDate = date('Y-m-d');
$sql = "SELECT MenuplanTBL.planDate, GROUP_CONCAT(Menutbl.MainCategory, ': ', Menutbl.MenuItem SEPARATOR '<br>') AS MenuItems,
               GROUP_CONCAT(MenuplanTBL.Quantity SEPARATOR '<br>') AS Quantities
        FROM MenuplanTBL
        INNER JOIN Menutbl ON MenuplanTBL.MenuID = Menutbl.MenuID
        WHERE DATE(MenuplanTBL.planDate) >= ? AND MenuplanTBL.Branch = ?
        GROUP BY MenuplanTBL.planDate";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $currentDate, $branch);
$stmt->execute();
$result = $stmt->get_result();
$plans = array();
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $plans[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>스마트 오더 키오스크 식단 관리 페이지</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            display: flex;
        }

        .sidebar {
            width: 300px;
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
            transition: background-color 0.3s;
        }

        .sidebar a:hover {
            background-color: #575757;
        }

        .main-content {
            flex-grow: 1;
            padding: 5px 20px; /* 패딩-탑을 5px로 줄임 */
            background-color: #f4f4f4;
            display: flex;
            flex-wrap: wrap;
        }

        .admin-info {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            font-size: 15px;
            color: #333;
        }

        .admin-info div {
            font-size: 24px; /* 글씨 크기 키움 */
            font-weight: bold; /* 글씨 굵게 */
        }

        .today-menu {
            background-color: #ffffff;
            padding: 15px; /* 패딩 더 줄임 */
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-right: 20px;
            flex: 1 1 auto; /* flex-grow, flex-shrink, flex-basis 속성 설정 */
            min-width: 250px;
            max-width: 300px;
            position: relative;
            margin-top: 0; /* 상단 여백 제거 */
            margin-bottom: 0; /* 하단 여백 제거 */
            height: auto; /* 높이를 자동으로 조절 */
            align-self: flex-start; /* 내용에 맞게 높이 조절 */
        }

        .today-menu h3 {
            margin-top: 0;
            font-size: 21.5px;
            color: #333;
        }

        .today-menu p {
            font-size: 16.7px;
            margin: 6px 0;
            color: #666;
        }

        .menu-table {
            flex: 2;
            min-width: 300px;
            margin-left: 20px;
            overflow-x: auto;
            margin-top: 0; /* 상단 여백 제거 */
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13.5px;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-top: 0; /* 상단 여백 제거 */
        }

        th, td {
            border: 1px solid #dddddd;
            text-align: center;
            padding: 10px;
        }

        th {
            background-color: #ffeb3b; /* 노란색 헤더 */
        }

        .delete-form {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .delete-form label {
            font-size: 16px; /* 폰트 크기 키움 */
            font-weight: bold; /* 글씨 굵게 */
            color: #333; /* 글씨 색상 */
            margin-right: 10px; /* 오른쪽 여백 */
        }

        .delete-form label, .delete-form select, .delete-form input {
            margin: 0 5px;
        }

        .delete-form input[type="submit"] {
            padding: 5px 10px;
            font-size: 14px;
            cursor: pointer;
            background-color: red;
            color: white;
            border: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .delete-form input[type="submit"]:hover {
            background-color: darkred;
        }

        .notification {
            text-align: center;
            margin-top: 10px;
            color: #333;
        }

        .flower-icon {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 24px;
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
        <div><?php echo $branch; ?>지점 식단 관리 표 완료</div>
        <form method="post" class="delete-form">
            <label for="deleteDate" style="font-size: 20px; font-weight: bold;">삭제할 날짜 선택:</label>
            <select name="deleteDate" id="deleteDate">
                <option value="전체">전체</option>
                <?php foreach ($plans as $plan): ?>
                    <option value="<?php echo $plan['planDate']; ?>"><?php echo $plan['planDate']; ?></option>
                <?php endforeach; ?>
            </select>
            <input type="submit" name="deletePlan" value="삭제하기">
        </form>
    </div>

    <?php
    // 오늘의 식단 표시
    $todayMenu = null;
    foreach ($plans as $plan) {
        if ($plan['planDate'] == $currentDate) {
            $todayMenu = $plan;
            break;
        }
    }
    if ($todayMenu) {
        echo '<div class="today-menu">';
        echo '<h3>🌸오늘의 식단 🍽️🌸</h3>';
        $menuItems = explode('<br>', $todayMenu['MenuItems']);
        $menuQuantities = explode('<br>', $todayMenu['Quantities']);
        $categorizedMenus = [];
        foreach ($menuItems as $index => $menuItem) {
            $menuItemParts = explode(': ', $menuItem);
            $menuCategory = $menuItemParts[0];
            $menuName = $menuItemParts[1];
            $menuQuantity = $menuQuantities[$index];
            if (!isset($categorizedMenus[$menuCategory])) {
                $categorizedMenus[$menuCategory] = [];
            }
            $categorizedMenus[$menuCategory][] = "$menuName [수량: $menuQuantity]";
        }
        foreach ($categorizedMenus as $category => $menus) {
            echo "<p><strong>$category " . $categories[$category] . "</strong>:<br>" . implode('<br>', $menus) . "</p><br>";
        }
        echo '</div>';
    }
    ?>

    <div class="menu-table">
        <table>
            <tr>
                <th>날짜</th>
                <?php foreach ($categories as $category => $emoji): ?>
                    <th><?php echo $category . " " . $emoji; ?></th>
                <?php endforeach; ?>
            </tr>
            <?php foreach ($plans as $plan): ?>
                <?php if ($plan['planDate'] !== $currentDate): ?>
                    <tr>
                        <td><?php echo $plan['planDate']; ?></td>
                        <?php foreach ($categories as $category => $emoji): ?>
                            <td>
                                <?php
                                $menuItems = explode('<br>', $plan['MenuItems']);
                                $menuQuantities = explode('<br>', $plan['Quantities']);
                                $categoryMenus = [];
                                foreach ($menuItems as $index => $menuItem) {
                                    $menuItemParts = explode(': ', $menuItem);
                                    $menuCategory = $menuItemParts[0];
                                    $menuName = $menuItemParts[1];
                                    $menuQuantity = $menuQuantities[$index];
                                    if ($menuCategory === $category) {
                                        $categoryMenus[] = "$menuName [수량: $menuQuantity]";
                                    }
                                }
                                echo implode('<br>', $categoryMenus);
                                ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endif; ?>
            <?php endforeach; ?>
        </table>
        <?php if (isset($deleteMessage)): ?>
            <p class="notification" style="margin-top: 10px;"><?php echo $deleteMessage; ?></p>
        <?php endif; ?>
    </div>

</div>
</body>
</html>
