<?php
$con = mysqli_connect("localhost", "root", "1234", "kioskDB") or die("MySQL 접속 실패");

// 테이블 삭제
$dropTables = "DROP TABLE IF EXISTS MenuplanTBL, SalesAnalysisTBL, MenuTBL, UserTBL, OrderTBL";
if (mysqli_query($con, $dropTables)) {
    echo "기존 테이블 삭제 완료<br>";
} else {
    echo "테이블 삭제 실패: " . mysqli_error($con) . "<br>";
}

// 사용자테이블 (UserTBL)
$sqlUsertbl = "CREATE TABLE IF NOT EXISTS Usertbl (
    UserID INT PRIMARY KEY, 
    UserRole VARCHAR(20) NOT NULL,
    DOB DATE NOT NULL,
    Gender VARCHAR(10) NOT NULL, 
    Branch VARCHAR(50) NOT NULL,
    INDEX (Branch)
)";

// 메뉴테이블(MenuTBL)
$sqlMenutbl = "CREATE TABLE IF NOT EXISTS Menutbl (
    MenuID INT AUTO_INCREMENT PRIMARY KEY, 
    MenuItem VARCHAR(100) NOT NULL, 
    MainCategory VARCHAR(50) NOT NULL,
    SubCategory VARCHAR(50) NOT NULL, 
    SpicinessLevel VARCHAR(50) NOT NULL,
    Price DECIMAL(10) NOT NULL,
    Branch VARCHAR(50) NOT NULL,
    FOREIGN KEY (Branch) REFERENCES Usertbl(Branch)
)";

// 주문테이블(OrderTBL)
$sqlOrdertbl = "CREATE TABLE IF NOT EXISTS OrderTBL (
    OrderID INT AUTO_INCREMENT PRIMARY KEY,
    SaleDate DATE NOT NULL,
    Branch VARCHAR(50) NOT NULL, 
    OrderNumber INT NOT NULL,
    FOREIGN KEY (Branch) REFERENCES Usertbl(Branch)
)";

// 식단테이블(MenuplanTBL)
$sqlMenuplantbl = "CREATE TABLE IF NOT EXISTS MenuplanTBL (
    MenuPlanID INT AUTO_INCREMENT PRIMARY KEY,
    planDate DATE Not NULL,
    MenuID INT Not NULL,
    Branch VARCHAR(50) NOT NULL, 
    Quantity INT NOT NULL,
    FOREIGN KEY (MenuID) REFERENCES Menutbl(MenuID),
    FOREIGN KEY (Branch) REFERENCES Usertbl(Branch)
)";

// 매출분석테이블(SalesAnalysisTBL)
$sqlSalesAnalysistbl = "CREATE TABLE IF NOT EXISTS SalesAnalysisTBL (
    SaleID INT AUTO_INCREMENT PRIMARY KEY,
    OrderID INT NOT NULL,
    MenuID INT NOT NULL,
    UserID INT NOT NULL,
    QuantitySold INT NOT NULL,
    FOREIGN KEY (OrderID) REFERENCES Ordertbl(OrderID),
    FOREIGN KEY (MenuID) REFERENCES Menutbl(MenuID),
    FOREIGN KEY (UserID) REFERENCES Usertbl(UserID)
)";

//UserTBL 테이블 생성
if (mysqli_query($con, $sqlUsertbl)) {
    echo "UserTBL 테이블 생성 완료<br>";
} else {
    echo "UserTBL 테이블 생성 실패: " . mysqli_error($con) . "<br>";
}

//MenuTBL 테이블 생성
if (mysqli_query($con, $sqlMenutbl)) {
    echo "MenuTBL 테이블 생성 완료<br>";
} else {
    echo "MenuTBL 테이블 생성 실패: " . mysqli_error($con) . "<br>";
}

//OrderTBL 테이블 생성
if (mysqli_query($con, $sqlOrdertbl)) {
    echo "OrderTBL 테이블 생성 완료<br>";
} else {
    echo "OrderTBL 테이블 생성 실패: " . mysqli_error($con) . "<br>";
}

//MenuplanTBL 테이블 생성
if (mysqli_query($con, $sqlMenuplantbl)) {
    echo "MenuplanTBL 테이블 생성 완료<br>";
} else {
    echo "MenuplanTBL 테이블 생성 실패: " . mysqli_error($con) . "<br>";
}

//SalesAnalysisTBL 테이블 생성
if (mysqli_query($con, $sqlSalesAnalysistbl)) {
    echo "SalesAnalysisTBL 테이블 생성 완료<br>";
} else {
    echo "SalesAnalysisTBL 테이블 생성 실패: " . mysqli_error($con) . "<br>";
}

// 트리거 생성 쿼리
$triggerQuery = "CREATE TRIGGER assign_order_number
BEFORE INSERT ON OrderTBL
FOR EACH ROW
BEGIN
    DECLARE max_order_number INT;
    SET max_order_number = (SELECT MAX(OrderNumber) FROM OrderTBL WHERE Branch = NEW.Branch AND SaleDate = CURDATE());
    IF max_order_number IS NULL THEN
        SET NEW.OrderNumber = 1;
    ELSE
        SET NEW.OrderNumber = max_order_number + 1;
    END IF;
END;";

// 트리거 생성 실행
if (mysqli_query($con, $triggerQuery)) {
    echo "트리거 생성 완료<br>";
} else {
    echo "트리거 생성 실패: " . mysqli_error($con) . "<br>";
}

mysqli_close($con);
?>