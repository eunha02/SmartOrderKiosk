<?php
$localhost = "localhost";
$root = "root";
$password = "1234"; 

$conn = new mysqli($localhost, $root, $password);


if ($conn->connect_error) {
  die("연결 실패: " . $conn->connect_error);
}

$sql = "CREATE DATABASE kioskdb";
if ($conn->query($sql) === TRUE) {
  echo "데이터베이스가 성공적으로 생성되었습니다.";
} else {
  echo "데이터베이스 생성 에러: " . $conn->error;
}

$conn->close();
?>
