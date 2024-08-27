<?php
session_start();

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

if (!isset($_SESSION['userID']) || !isset($_SESSION['branch'])) {
    exit();
}

$userID = $_SESSION['userID'];
$branch = $_SESSION['branch'];

// getMenuQuantity 함수 정의
function getMenuQuantity($conn, $menuID) {
    $sql = "SELECT Quantity FROM MenuplanTBL WHERE MenuID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $menuID);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc()['Quantity'];
    } else {
        return 0;
    }
}

// 시작 시간 측정
$start_time = microtime(true);

// 오늘 날짜에 해당하는 메뉴 정보 가져오기
$sql = "SELECT m.*, 
               SUM(mp.Quantity) AS TotalQuantitySold, 
               ANY_VALUE(m.SpicinessLevel) AS SpicinessLevel 
        FROM Menutbl m
        JOIN MenuplanTBL mp ON m.MenuID = mp.MenuID
        LEFT JOIN SalesAnalysisTBL sa ON m.MenuID = sa.MenuID
        WHERE mp.planDate = '$today' 
          AND mp.Branch = '$branch'"; // 세션에서 branch 값을 가져와서 사용

if(isset($_GET['category'])) {
    $category = $_GET['category'];
    if ($category != '추천메뉴') {
        $sql .= " AND m.MainCategory = '$category'";
    }
}

$sql .= " GROUP BY mp.MenuID, m.MenuItem, m.MainCategory, m.SubCategory, m.Price";

$result = $conn->query($sql);

$menus = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $isSoldOut = getMenuQuantity($conn, $row['MenuID']) <= 0; // 메뉴의 재고량을 확인하여 품절 여부 판단
        $row['isSoldOut'] = $isSoldOut;
        $menus[] = $row;
    }
} else {
    echo "0 results";
}

$conn->close();

// 종료 시간 측정
$end_time = microtime(true);

// 시간 차이 계산 (초 단위)
$time_diff = $end_time - $start_time;

// 성능 측정 결과를 로그 파일에 기록
$logFile = 'performance_log_menu.txt';
$logMessage = date('Y-m-d H:i:s') . " - Processing time: " . $time_diff . " seconds\n";
file_put_contents($logFile, $logMessage, FILE_APPEND);
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>스마트 오더 키오스크 메뉴화면</title>
    <style>
        /* 스타일은 여기에 복사해주세요 */
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
        }
        .menu-buttons {
            padding: 10px 0;
            display: flex;
            justify-content: flex-end; 
            overflow-x: auto;
            white-space: nowrap;
            margin-left: 20px; 
            margin-right: 700px; 
        }
        .menu-button {
            background-color: #ddd;
            border: none;
            padding: 15px 40px;
            margin: 0 10px;
            cursor: pointer;
            font-size: 30px;
            transition: background-color 0.3s, color 0.3s;
        }
        .menu-button.active {
            background-color: #ffa500;
            color: white;
        }
        .menu-items {
            display: flex;
            flex-wrap: wrap;
            justify-content: center; /* 중앙 정렬로 변경 */
            padding: 5px;
            margin-top: 30px;
            margin-left: 1px; /* 변경: 왼쪽 여백 추가 */
            width: 80%; /* 변경: 원하는 너비로 조정 */
        }
        .menu-row {
            text-align: center;
            width: 100%;
            display: flex;
            justify-content: center;
            margin-bottom: 15px; 
        }
        .menu-item-button {
            background-color: #fff;
            border: 1px solid #ddd;
            padding: 20px;
            margin: 10px;
            cursor: pointer;
            text-align: center;
            box-sizing: border-box;
            transition: transform 0.3s;
            width: calc(25% - 20px);
            max-width: 300px; 
            position: relative;
        }
        .menu-item-button:hover {
            transform: scale(1.05);
        }
        .shopping-cart {
             position: fixed;
             top: 0;
             right: 0;
             width: 25%;
             background-color: #fff;
             padding: 20px;
             box-sizing: border-box;
             overflow-y: auto;
             max-height: calc(100vh - 100px);
         }
        ul#cartItems {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }
        ul#cartItems li {
            background-color: #f9f9f9;
            padding: 10px;
            margin-bottom: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .item-quantity {
            display: flex;
            align-items: center;
        }
        .item-quantity button {
            padding: 4px 6px;
            margin: 0 2px;
        }
        .remove-item {
            background-color: #ff6961;
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
        }
        .checkout {
            background-color: transparent;
            text-align:right; 
            position: fixed;
            bottom: 0;
            right: 0;
            width: 25%;
            padding: 20px;
            margin-top: 20px; 
        }
        .checkout button {
            background-color: #ffa500;
            color: white;
            border: none;
            padding: 15px 30px;
            font-size: 30px;
            cursor: pointer;
            width: 290px;
        }

        /* 추가된 CSS 부분: 품절 메뉴 표시 */
        .menu-item-button.sold-out {
            opacity: 0.5; /* 품절된 메뉴를 흐리게 표시 */
            pointer-events: none; /* 품절된 메뉴는 클릭 불가능하게 설정 */
        }

        .sold-out-label {
            position: absolute;
            top: 5px;
            right: 5px;
            background-color: #ff6961; /* 빨간색 배경색 */
            color: white;
            padding: 5px;
            border-radius: 5px;
            font-size: 14px;
        }
         /* 총 합계 스타일 */
         .total {
            font-size: 30px; /* 크기를 원하는 만큼 조정 */
            margin-bottom: 10px;
        }
        .total span {
            font-weight: bold;
        }
    </style>
</head>
<body>
<div class="menu-buttons" style="text-align: left;">
    <button class="menu-button" onclick="loadMenuByCategory('추천메뉴')">추천메뉴</button>
    <button class="menu-button" onclick="loadMenuByCategory('한식')">한식</button>
    <button class="menu-button" onclick="loadMenuByCategory('일식')">일식</button>
    <button class="menu-button" onclick="loadMenuByCategory('중식')">중식</button>
    <button class="menu-button" onclick="loadMenuByCategory('양식')">양식</button>
</div>

<div id="menuItems" class="menu-items">
    <?php 
        $count = 0; // 카운터 초기화
        foreach ($menus as $menu): 
            if ($count % 3 == 0) echo '<div class="menu-row">'; // 새로운 행 시작
    ?>
            <div class="menu-item-button <?php echo $menu['isSoldOut'] ? 'sold-out' : ''; ?>" onclick="<?php echo $menu['isSoldOut'] ? 'alert(\'이 상품은 품절되었습니다.\')' : 'addToCart(' . $menu['MenuID'] . ', \'' . $menu['MenuItem'] . '\', ' . $menu['Price'] . ')' ?>">
                <p><?php echo $menu['MenuItem']; ?></p>
                <p><?php echo $menu['Price']; ?>원</p>
                <p><?php for($i = 0; $i < $menu['SpicinessLevel']; $i++) echo "🔥"; ?></p>
                <?php if ($menu['isSoldOut']) echo '<p class="sold-out-label">품절</p>'; // 품절인 경우 표시 ?>
            </div>
    <?php 
            $count++; // 카운터 증가
            if ($count % 3 == 0) echo '</div>'; // 행 종료
        endforeach; 
        if ($count % 3 != 0) echo '</div>'; // 마지막 행 종료 처리
    ?>
</div>


<div class="shopping-cart">
    <h2>장바구니</h2>
    <ul id="cartItems"></ul> 
</div>

<div class="checkout">
    <div class="total">총 합계: <span id="totalAmount">0</span>원</div>
    <button onclick="goToCheckout()">결제하기</button>
</div>

<script>
    document.querySelectorAll('.menu-button').forEach(button => {
        button.addEventListener('click', function() {
            document.querySelectorAll('.menu-button').forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            var category = this.textContent.trim();
            loadMenuByCategory(category);
        });
    });

    function loadMenuByCategory(category) {
        var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                document.getElementById("menuItems").innerHTML = this.responseText;
            }
        };
        xhttp.open("GET", "스마트오더키오스크_getMenuItemsByCategory.php?category=" + encodeURIComponent(category), true);
        xhttp.send();
    }

function updateTotal() {
    var total = 0;
    var cartItems = document.querySelectorAll('#cartItems li');
    cartItems.forEach(function(item) {
        var quantity1 = item.querySelector('.item-quantity').value;
        var price = parseFloat(item.querySelector('.item-price').textContent.replace('원', ''));
        total += quantity1 * price;
    });
    document.getElementById('totalAmount').textContent = total;
}

function changeQuantity(item, price) {
    updateTotal();
}

function removeItemFromCart(itemElement) {
    itemElement.parentElement.removeChild(itemElement);
    updateTotal();
}

function addToCart(menuID, item, price, availableQuantity) {
    var cartItems = document.getElementById('cartItems');
    var existingItem = Array.from(cartItems.children).find(li => li.querySelector('.item-id').textContent == menuID);

    if (existingItem) {
        var quantityInput = existingItem.querySelector('.item-quantity');
        var newQuantity = parseInt(quantityInput.value) + 1;
        if (newQuantity > availableQuantity) {
            alert('해당 메뉴는 ' + availableQuantity + '개까지만 주문 가능합니다.');
            return;
        }
        quantityInput.value = newQuantity;
    } else {
        if (availableQuantity < 1) {
            alert('해당 메뉴는 품절되었습니다.');
            return;
        }
        var newItem = document.createElement('li');
        newItem.innerHTML = `
            <span class="item-id" style="display: none;">${menuID}</span>
            <span class="item-name" style="flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${item}</span>
            <span class="item-price" style="margin-left: 10px;">${price}원</span>
            <input type="number" class="item-quantity" value="1" style="width: 50px; margin-left: 10px;" onchange="checkQuantity(this, ${availableQuantity}); updateTotal()">
            <button class="remove-item" onclick="removeItemFromCart(this.parentElement)">삭제</button>
        `;
        cartItems.appendChild(newItem);
    }
    updateTotal();
}




function goToCheckout() {
    var cartItems = document.querySelectorAll('#cartItems li');
    var itemsToSend = [];
    cartItems.forEach(function(item) {
        var itemInfo = {
            id: item.querySelector('.item-id').textContent,
            name: item.querySelector('.item-name').textContent,
            price: parseFloat(item.querySelector('.item-price').textContent.replace('원', '')),
            quantity: parseInt(item.querySelector('.item-quantity').value)
        };
        itemsToSend.push(itemInfo);
    });

    fetch('스마트오더키오스크_결제중.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({cartItems: itemsToSend})
    })
    .then(response => response.text())
    .then(response => {
        console.log(response);
        setTimeout(function() {
            window.location.href = '스마트오더키오스크_결제완료.php';
        }, 2000); // 결제 완료 페이지로 넘어갑니다.
    })
    .catch(error => console.error('Error:', error));
}
</script>
</body>
</html>