<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>키오스크 시작화면</title>
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f4f4f4;
        }
        .container {
            text-align: center;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            width: 80%;
            max-width: 1000px;
        }
        .qr-section {
            margin-bottom: 40px;
        }
        .qr-section img {
            max-width: 150px;
            margin: 0 auto;
            display: block;
        }
        .qr-section p {
            font-size: 24px;
            color: #333;
            margin-top: 20px;
        }
        .guide-section {
            display: flex;
            justify-content: space-around;
            align-items: center;
            flex-wrap: wrap;
        }
        .guide-item {
            flex: 1;
            min-width: 150px;
            text-align: center;
            margin: 10px;
        }
        .guide-item .emoji {
            font-size: 50px;
        }
        .guide-item p {
            font-size: 18px;
            color: #333;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="qr-section">
            <img src="your_qr_code_image_path" alt="QR 코드">
            <p>QR코드를 카메라에 비춰주세요</p>
        </div>
        <div class="guide-section">
            <div class="guide-item">
                <div class="emoji">📋</div>
                <p>구매할 메뉴 선택</p>
            </div>
            <div class="guide-item">
                <div class="emoji">💳</div>
                <p>카드 또는 현금 선택</p>
            </div>
            <div class="guide-item">
                <div class="emoji">🧾</div>
                <p>카드 또는 현금 투입</p>
            </div>
            <div class="guide-item">
                <div class="emoji">🖨️</div>
                <p>영수증 출력 확인</p>
            </div>
        </div>
    </div>
</body>
</html>
