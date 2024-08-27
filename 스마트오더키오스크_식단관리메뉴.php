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

// 데이터베이스 연결
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

date_default_timezone_set('Asia/Seoul');
$daysOfWeek = array("월요일" => "Monday", "화요일" => "Tuesday", "수요일" => "Wednesday", "목요일" => "Thursday", "금요일" => "Friday");
$categories = ['한식', '중식', '일식', '양식'];

// 각 카테고리별 모든 메뉴 가져오기
function getAllMenusByCategory($conn, $category, $branch) {
    $menus = [];
    $sql = "SELECT MenuID, MenuItem FROM Menutbl WHERE MainCategory = ? AND Branch = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $category, $branch);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $menus[] = ['MenuID' => $row['MenuID'], 'MenuItem' => $row['MenuItem']];
    }
    $stmt->close();
    return $menus;
}

// 메뉴 옵션 생성
$menusOptions = [];
foreach ($categories as $category) {
    $menusOptions[$category] = getAllMenusByCategory($conn, $category, $branch);
}

// 메뉴 업데이트 로직
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['updateMenu'])) {
    foreach ($_POST['selectedMenus'] as $day => $categoryMenus) {
        foreach ($categoryMenus as $category => $menuDetails) {
            $date = date('Y-m-d', strtotime("next " . $daysOfWeek[$day]));

            // 메뉴 삭제
            $stmt = $conn->prepare("DELETE FROM MenuplanTBL WHERE planDate = ? AND branch = ? AND MenuID IN (SELECT MenuID FROM Menutbl WHERE MainCategory = ?)");
            $stmt->bind_param("sss", $date, $branch, $category);
            $stmt->execute();

            // 메뉴 추가
            foreach ($menuDetails as $details) {
                $menuID = $details['MenuID'];
                $quantity = $details['Quantity'];

                // 메뉴의 수량이 0 이상인 경우에만 추가
                if ($quantity > 0) {
                    // MenuID가 Menutbl에 존재하는지 확인
                    $checkMenuID = $conn->prepare("SELECT COUNT(*) FROM Menutbl WHERE MenuID = ? AND Branch = ?");
                    $checkMenuID->bind_param("is", $menuID, $branch);
                    $checkMenuID->execute();
                    $checkMenuID->bind_result($count);
                    $checkMenuID->fetch();
                    $checkMenuID->close();

                    if ($count > 0) {
                        $stmt = $conn->prepare("INSERT INTO MenuplanTBL (planDate, MenuID, branch, Quantity) VALUES (?, ?, ?, ?)");
                        $stmt->bind_param("sisi", $date, $menuID, $branch, $quantity);
                        $stmt->execute();
                    } else {
                        echo "MenuID " . $menuID . " does not exist in Menutbl.<br>";
                    }
                }
            }
        }
    }

    $conn->close();
    header("Location: 스마트오더키오스크_식단관리표완료.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>스마트 오더 키오스크 식단 관리 페이지</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            background-color: #f4f4f4;
        }
        .sidebar {
            width: 390px; /* 가로 길이를 300px로 늘림 */
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
            position: relative;
            margin: 20px;
            margin-top: 0; /* 페이지 상단과의 간격을 줄임 */
            margin-top: -20px; /* 추가적으로 위로 올림 */
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 3px; /* 마진을 줄여서 위로 올림 */
        }
        .header h2 {
            font-size: 23px;
            color: #333;
            font-weight: 700;
        }
        .custom-button {
            padding: 8px 15px;
            background-color: #ffeb3b;
            color: black;
            font-size: 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 1px auto 20px; /* 가운데 정렬 */
            background-color: #ffffff; /* 표의 배경색을 흰색으로 설정 */
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        th, td {
            border: 1px solid #dddddd;
            text-align: center;
            padding: 10px; /* 패딩을 줄임 */
        }
        th {
            background-color: #ffeb3b; /* 노란색 헤더 */
            font-weight: 700;
            color: #333;
        }
        td {
            background-color: #ffffff; /* 흰색 배경 */
        }
        select, input[type="number"] {
            width: calc(70% - 8px);
            padding: 3.5px; /* 패딩을 줄임 */
            font-size: 15px; /* 폰트 크기를 줄임 */
            background-color: #ffffff; /* 메뉴 선택 드롭다운과 수량 입력 필드의 배경색을 흰색으로 설정 */
            color: #333;
            border-radius: 4px;
            border: 1px solid #ddd;
            float: left;
            margin-right: 8px;
        }
        input[type="number"] {
            width: 20%;
        }
        input[type="submit"] {
            width: 100%;
            padding: 12px; /* 패딩을 줄임 */
            background-color: #ffeb3b;
            color: black;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px; /* 폰트 크기를 줄임 */
            transition: background-color 0.3s;
        }
        input[type="submit"]:hover {
            background-color: #ffeb3b;
        }
        select,
        input[type="number"] {
            border: 1px solid #ffffff;
            box-shadow: 0 0 0 1px #ffffff; /* 테두리 효과 */
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
    <h2><?php echo $branch; ?>지점 식단추천 메뉴 수정</h2>
    <form method="post">
        <input type="submit" name="updateMenu" value="메뉴 등록하기" class="custom-button" style="background-color: #FFD700; color: black; width: 150px; position: absolute; top: 30px; right: 20px;">
        <table>
            <tr>
                <th>카테고리 / 날짜</th>
                <?php foreach ($daysOfWeek as $day => $engDay): ?>
                    <th><?php echo $day . ' (' . date('Y-m-d', strtotime("next " . $engDay)) . ')'; ?></th>
                <?php endforeach; ?>
            </tr>
            <?php foreach ($categories as $category): ?>
                <tr>
                    <td><?php echo $category; ?></td>
                    <?php foreach ($daysOfWeek as $day => $engDay): ?>
                        <td>
                            <?php for ($i = 0; $i < 6; $i++): ?>
                                <?php $randomKey = array_rand($menusOptions[$category]); ?>
                                <?php $menu = $menusOptions[$category][$randomKey]; ?>
                                <div style="margin-bottom: 10px;">
                                    <select name="selectedMenus[<?php echo $day; ?>][<?php echo $category; ?>][<?php echo $i; ?>][MenuID]">
                                        <option value="<?php echo $menu['MenuID']; ?>"><?php echo htmlspecialchars($menu['MenuItem']); ?></option>
                                        <?php foreach ($menusOptions[$category] as $menuItem): ?>
                                            <?php if ($menuItem['MenuID'] != $menu['MenuID']): ?>
                                                <option value="<?php echo $menuItem['MenuID']; ?>"><?php echo htmlspecialchars($menuItem['MenuItem']); ?></option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="number" name="selectedMenus[<?php echo $day; ?>][<?php echo $category; ?>][<?php echo $i; ?>][Quantity]" placeholder="수량" value="100">
                                </div>
                            <?php endfor; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </table>
    </form>
</div>

</body>
</html>
