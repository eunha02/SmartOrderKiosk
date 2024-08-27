<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "1234";
$dbname = "kioskDB";

// 데이터베이스 연결
$con = mysqli_connect($servername, $username, $password, $dbname);

// 연결 확인
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

// 사용자 ID를 세션에서 가져오기
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['item']) && isset($_POST['price'])) {
        $item = $_POST['item'];
        $price = $_POST['price'];
        
        // 카테고리를 세션에 저장
        $category = isset($_SESSION['orderCategory']) ? $_SESSION['orderCategory'] : '';
        
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = array();
        }

        $cartItem = array(
            'item' => $item,
            'price' => $price,
            'category' => $category
        );
        $_SESSION['cart'][] = $cartItem;

        // 상품이 장바구니에 추가되었음을 성공 메시지로 알림
        echo "Item added to cart successfully.";
    } else {
        // 상품이나 가격이 누락된 경우에는 에러 메시지 표시
        echo "Error: Missing item or price.";
    }
} else {
    header("HTTP/1.1 405 Method Not Allowed");
    header("Allow: POST");
    echo "Error: POST method required.";
}
?>