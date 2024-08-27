<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>결제 선택</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            flex-direction: column;
        }
        .message {
            font-size: 100px; 
            margin-bottom: 30px; 
        }
        .buttons {
            display: flex; 
        }
        .button {
            padding: 20px 40px;
            font-size: 80px;
            margin: 0 30px; 
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .yes-button {
            background-color: #007bff; 
            color: white;
        }
        .no-button {
            background-color: #6c757d; 
            color: white;
        }
    </style>
</head>
<body>

<div class="message">결제하시겠습니까?</div>

<div class="buttons">
    <form action="스마트오더키오스크_결제중.php" method="post">
        <button type="submit" class="button yes-button">Yes</button>
    </form>

    <form action="스마트오더키오스크_메뉴화면.php" method="post">
        <button type="submit" class="button no-button">No</button>
    </form>
</div>

</body>
</html>
