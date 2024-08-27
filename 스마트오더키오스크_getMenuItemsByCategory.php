<?php
session_start();
date_default_timezone_set('Asia/Seoul');
$servername = "localhost";
$username = "root";
$password = "1234";
$dbname = "kioskDB";
$today = date("Y-m-d");

// 데이터베이스 연결
$conn = new mysqli($servername, $username, $password, $dbname);

// 연결 체크
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 세션에서 사용자 정보 확인
if (!isset($_SESSION['userID']) || !isset($_SESSION['branch'])) {
    exit("필요한 세션 정보가 설정되지 않았습니다.");
}

$userID = $_SESSION['userID'];
$branch = $_SESSION['branch'];

// 카테고리가 전달되었는지 확인
if (isset($_GET['category'])) {
    $category = $_GET['category'];

    if ($category != '추천메뉴') {
        displayCategoryMenus($conn, $category, $branch, $today);
    } else if ($category == '추천메뉴') {

        displayRecommendation($conn, $userID, $branch, $today);
       
    }
} else {
    echo "카테고리가 전달되지 않았습니다.";
}

$conn->close();

function displayCategoryMenus($conn, $category, $branch, $today) {
    $sql = "SELECT m.*, 
    SUM(mp.Quantity) AS TotalQuantitySold, 
    ANY_VALUE(m.SpicinessLevel) AS SpicinessLevel 
    FROM Menutbl m
    JOIN MenuplanTBL mp ON m.MenuID = mp.MenuID
    LEFT JOIN SalesAnalysisTBL sa ON m.MenuID = sa.MenuID
    JOIN Usertbl u ON mp.Branch = u.Branch
    WHERE mp.planDate = ? 
    AND u.Branch = ?
    AND m.MainCategory = ?
    GROUP BY m.MenuID, m.MenuItem, m.MainCategory, m.SubCategory, m.Price";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("SQL 쿼리 준비 오류: " . $conn->error);
    }
    $stmt->bind_param("sss", $today, $branch, $category);
    if (!$stmt->execute()) {
        die("SQL 실행 오류: " . $stmt->error);
    }
    $result = $stmt->get_result();
    displayMenus($conn, $result);
}

function displayRecommendation($conn, $userID, $branch, $today) {
    // 추천메뉴 1: 당일 많이 팔린 메뉴
    $sql = "SELECT m.*, 
    SUM(sa.QuantitySold) AS TotalQuantitySold, 
    ANY_VALUE(m.SpicinessLevel) AS SpicinessLevel 
    FROM Menutbl m
    LEFT JOIN SalesAnalysisTBL sa ON m.MenuID = sa.MenuID
    LEFT JOIN OrderTBL o ON sa.OrderID = o.OrderID
    WHERE o.SaleDate = ? AND o.Branch = ?
    GROUP BY m.MenuID
    ORDER BY TotalQuantitySold DESC
    LIMIT 1";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("SQL 쿼리 준비 오류: " . $conn->error);
    }
    $stmt->bind_param("ss", $today, $branch);
    if (!$stmt->execute()) {
        die("SQL 실행 오류: " . $stmt->error);
    }
    $result = $stmt->get_result();

    // 메뉴가 없으면 표시하지 않음
    if ($result && $result->num_rows > 0) {
        echo'<style>h4 {font-size: 24px;text-align: center;margin-top: 5px;}</style>';
        echo '<h4>  📈 오늘 가장 많이 팔린 메뉴</h4>';
        echo '<div class="menu-items" style="text-align: left;">';
        while ($row = $result->fetch_assoc()) {
            $isSoldOut = getMenuQuantity($conn, $row['MenuID']) <= 0; // 메뉴의 재고량을 확인하여 품절 여부 판단
            $row['isSoldOut'] = $isSoldOut;
            $menu = $row;
            $spiciness = str_repeat('🔥', $menu['SpicinessLevel']);
            $style = 'width: calc(33% - 20px); margin: 10px; padding: 20px; display: inline-block ';
            // 재고 확인
            if ($menu['isSoldOut']) {
                echo '<div class="menu-item-button out-of-stock" style="' . $style . 'opacity: 0.5; pointer-events: none;">';
                echo '<p>' . htmlspecialchars($menu['MenuItem']) . '</p>';
                echo '<p>' . htmlspecialchars($menu['Price']) . '원</p>';
                echo '<p>' . $spiciness . '</p>';
                echo '<div class="sold-out-label" style="position: absolute; top: 5px; right: 5px; background-color: #ff6961; color: white; padding: 5px; border-radius: 5px; font-size: 14px;">';
                echo '<p>품절</p>';
                echo '</div>';
                echo '</div>';
            } else {
                echo '<div class="menu-item-button" onclick="addToCart(' . htmlspecialchars(json_encode($menu['MenuID'])) . ', \'' . htmlspecialchars(addslashes($menu['MenuItem'])) . '\', ' . htmlspecialchars($menu['Price']) . ')" style="' . $style . '">';
                echo '<p>' . htmlspecialchars($menu['MenuItem']) . '</p>';
                echo '<p>' . htmlspecialchars($menu['Price']) . '원</p>';
                echo '<p>' . $spiciness . '</p>';
                echo '<p>🧡오늘의 best!🧡</p>';
                echo '</div>';
            }
        }
        echo '</div>';
    } else {
        echo "오늘 가장 많이 팔린 메뉴가 없습니다.";
    }

    // 사용자 선호도 기반 추천 메뉴
    $preferencesQuery = "
    SELECT m.MainCategory, m.SubCategory, m.SpicinessLevel, SUM(sa.QuantitySold) AS PreferenceScore
    FROM Menutbl m
    JOIN SalesAnalysisTBL sa ON m.MenuID = sa.MenuID
    WHERE sa.UserID = ?
    GROUP BY m.MainCategory, m.SubCategory, m.SpicinessLevel
    ORDER BY PreferenceScore DESC
    ";
    $stmt = $conn->prepare($preferencesQuery);
    if (!$stmt) {
        die("SQL 쿼리 준비 오류: " . $conn->error);
    }
    $stmt->bind_param("i", $userID);
    if (!$stmt->execute()) {
        die("SQL 실행 오류: " . $stmt->error);
    }
    $preferencesResult = $stmt->get_result();

    $mainCategoryScores = [];
    $subCategoryScores = [];
    $totalSpiciness = 0;
    $totalSpicinessCount = 0;

    while ($row = $preferencesResult->fetch_assoc()) {
        // 메인 카테고리 점수 계산
        if (!isset($mainCategoryScores[$row['MainCategory']])) {
            $mainCategoryScores[$row['MainCategory']] = 0;
        }
        $mainCategoryScores[$row['MainCategory']] += $row['PreferenceScore'];

        // 서브 카테고리 점수 계산
        if (!isset($subCategoryScores[$row['SubCategory']])) {
            $subCategoryScores[$row['SubCategory']] = 0;
        }
        $subCategoryScores[$row['SubCategory']] += $row['PreferenceScore'];

        // 총 맵기와 횟수 계산
        $totalSpiciness += $row['SpicinessLevel'] * $row['PreferenceScore'];
        $totalSpicinessCount += $row['PreferenceScore'];
    }

    // 점수에 따라 메인 카테고리 정렬
    arsort($mainCategoryScores);
    arsort($subCategoryScores);

    // 평균 맵기 계산
    $averageSpiciness = $totalSpicinessCount > 0 ? $totalSpiciness / $totalSpicinessCount : 0;

    // 오늘의 메뉴 가져오기
    $todayMenuQuery = "
    SELECT m.MenuID, m.MenuItem, m.MainCategory, m.SubCategory, m.SpicinessLevel, mp.Quantity, m.Price
    FROM Menutbl m
    JOIN MenuplanTBL mp ON m.MenuID = mp.MenuID
    WHERE mp.planDate = ? AND mp.Branch = ?
    ";
    $stmt = $conn->prepare($todayMenuQuery);
    if (!$stmt) {
        die("SQL 쿼리 준비 오류: " . $conn->error);
    }
    $stmt->bind_param("ss", $today, $branch);
    if (!$stmt->execute()) {
        die("SQL 실행 오류: " . $stmt->error);
    }
    $todayMenuResult = $stmt->get_result();

    $todayMenu = [];
    while ($row = $todayMenuResult->fetch_assoc()) {
        $todayMenu[] = $row;
    }

    // 메뉴 추천 알고리즘
    $recommendedMenus = [];
    foreach ($mainCategoryScores as $mainCategory => $score) {
        foreach ($subCategoryScores as $subCategory => $subScore) {
            foreach ($todayMenu as $menu) {
                $matchCount = 0;
                if ($menu['MainCategory'] == $mainCategory) {
                    $matchCount++;
                }
                if ($menu['SubCategory'] == $subCategory) {
                    $matchCount++;
                }
                if ($menu['SpicinessLevel'] == round($averageSpiciness, 0)) {
                    $matchCount++;
                }
                if ($matchCount > 0) {
                    $recommendedMenus[] = [
                        'MenuID' => $menu['MenuID'],
                        'MenuItem' => $menu['MenuItem'],
                        'MainCategory' => $menu['MainCategory'],
                        'SubCategory' => $menu['SubCategory'],
                        'SpicinessLevel' => $menu['SpicinessLevel'],
                        'Price' => $menu['Price'],
                        'MatchCount' => $matchCount
                    ];
                }
            }
        }
    }

    // 매칭 카운트에 따라 메뉴 정렬 (우선순위 높은 순으로)
    usort($recommendedMenus, function ($a, $b) {
        return $b['MatchCount'] - $a['MatchCount'];
    });
    // 중복 메뉴 제거
    $recommendedMenus = array_unique($recommendedMenus, SORT_REGULAR);

    // 상위 2개의 메뉴 추천
    $topRecommendations = array_slice($recommendedMenus, 0, 2);

    // 추천 메뉴 출력
    if (!empty($topRecommendations)) {
        echo'<style>h4 {font-size: 24px;text-align: center;margin-top: 20px;margin-bottom: 5px;}</style>';
        echo '<h4> 📣 고객님께 추천드리는 메뉴 </h4>';
        echo '<div class="menu-items" style="text-align: left;">';
        foreach ($topRecommendations as $menu) {
            $spiciness = str_repeat('🔥', $menu['SpicinessLevel']);
            $style = 'width: calc(33% - 20px); margin: 10px; padding: 20px; display: inline-block;';
            $isSoldOut = getMenuQuantity($conn, $menu['MenuID']) <= 0; // 메뉴의 재고량을 확인하여 품절 여부 판단
            $menu['isSoldOut'] = $isSoldOut;

            // 재고 확인
            if ($menu['isSoldOut']) {
                echo '<div class="menu-item-button out-of-stock" style="' . $style . 'opacity: 0.5; pointer-events: none;">';
                echo '<p>' . htmlspecialchars($menu['MenuItem']) . '</p>';
                echo '<p>' . htmlspecialchars($menu['Price']) . '원</p>';
                echo '<p>' . $spiciness . '</p>';
                echo '<div class="sold-out-label" style="position: absolute; top: 5px; right: 5px; background-color: #ff6961; color: white; padding: 5px; border-radius: 5px; font-size: 14px;">';
                echo '<p>품절</p>';
                echo '</div>';
                echo '</div>';
            } else {
                echo '<div class="menu-item-button" onclick="addToCart(' . htmlspecialchars(json_encode($menu['MenuID'])) . ', \'' . htmlspecialchars(addslashes($menu['MenuItem'])) . '\', ' . htmlspecialchars($menu['Price']) . ')" style="' . $style . '">';
                echo '<p>' . htmlspecialchars($menu['MenuItem']) . '</p>';
                echo '<p>' . htmlspecialchars($menu['Price']) . '원</p>';
                echo '<p>' . $spiciness . '</p>';
                echo '<p>⭐추천!⭐</p>';
                echo '</div>';
            }
        }
        echo '</div>';
    } else {
        echo '<p>고객님의 맞춤 추천 메뉴를 준비 중입니다!</p>';
    }
}


function displayMenus($conn, $result) {
    if ($result->num_rows > 0) {
        echo '<div class="menu-items" style="text-align: left;">';
        while ($row = $result->fetch_assoc()) {
            $isSoldOut = getMenuQuantity($conn, $row['MenuID']) <= 0;
            $row['isSoldOut'] = $isSoldOut;
            $menu = $row;
            $spiciness = str_repeat('🔥', $menu['SpicinessLevel']);
            $style = 'width: calc(33% - 20px); margin: 10px; padding: 20px; display: inline-block;';

            echo '<div class="menu-item-button ' . ($menu['isSoldOut'] ? 'sold-out' : '') . '" onclick="' . ($menu['isSoldOut'] ? 'alert(\'이 상품은 품절되었습니다.\')' : 'addToCart(' . htmlspecialchars(json_encode($menu['MenuID'])) . ', \'' . htmlspecialchars(addslashes($menu['MenuItem'])) . '\', ' . htmlspecialchars($menu['Price']) . ')') . '" style="' . $style . '">';
            echo '<p>' . htmlspecialchars($menu['MenuItem']) . '</p>';
            echo '<p>' . htmlspecialchars($menu['Price']) . '원</p>';
            echo '<p>' . $spiciness . '</p>';
            if ($menu['isSoldOut']) {
                echo '<p class="sold-out-label">품절</p>';
            }
            echo '</div>';
        }
        echo '</div>';
    } else {
        echo "해당 카테고리의 메뉴가 없습니다.";
    }
}

function getMenuQuantity($conn, $menuID) {
    $sql = "SELECT Quantity FROM MenuplanTBL WHERE MenuID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $menuID);
    if (!$stmt->execute()) {
        die("SQL 실행 오류: " . $stmt->error);
    }
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc()['Quantity'];
    } else {
        return 0;
    }
}
?>