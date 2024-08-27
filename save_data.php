<?php
session_start();
header('Content-Type: application/json');

// 성능 측정 결과를 로그 파일에 기록
$logFile = 'performance_log_qr.txt';
$logMessage = date('Y-m-d H:i:s') . " - Processing time: " . $_POST['processingTime'] . " seconds\n";
file_put_contents($logFile, $logMessage, FILE_APPEND);

$servername = "localhost";
$username = "root";
$password = "1234";
$dbname = "kioskDB";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die(json_encode(['error' => "데이터베이스 연결 실패: " . $conn->connect_error]));
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['scannedData'])) {
    $scannedData = $_POST['scannedData'];
    $dataParts = explode(',', $scannedData);

    if (count($dataParts) === 5) {
        list($userID, $DOB, $genderCode, $roleCode, $branch) = $dataParts;
        // 생년월일을 직접 입력된 데이터에서 받아옴
        $DOB = date('Y-m-d', strtotime($DOB));
        $gender = $genderCode == '2' ? '남성' : '여성';
        $role = $roleCode == '0' ? '고객' : '관리자';

        $stmt = $conn->prepare("SELECT * FROM Usertbl WHERE UserID = ?");
        $stmt->bind_param("i", $userID);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $result_row = $result->fetch_assoc();
            $_SESSION['userID'] = $result_row['UserID'];
            $_SESSION['branch'] = $branch; // 지점 정보 세션에 저장
            $_SESSION['userRole'] = $result_row['UserRole'];
            echo json_encode(['success' => true, 'userID' => $userID, 'role' => $_SESSION['userRole']]);
        } else {
            $stmt = $conn->prepare("INSERT INTO Usertbl (UserID, UserRole, DOB, Gender, Branch) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issss", $userID, $role, $DOB, $gender, $branch);
            if ($stmt->execute()) {
                $_SESSION['userID'] = $userID;
                $_SESSION['branch'] = $branch;
                $_SESSION['userRole'] = $role;
                echo json_encode(['success' => true, 'userID' => $userID, 'role' => $_SESSION['userRole']]);
            } else {
                echo json_encode(['error' => "SQL 실행 오류: " . $stmt->error]);
            }
            $stmt->close();
        }
        $conn->close();
    } else {
        echo json_encode(['error' => "잘못된 데이터 형식입니다."]);
    }
} else {
    echo json_encode(['error' => "스캔된 데이터가 없습니다."]);
}
?>