<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Crime Management Portal - Splash</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body, html {
      height: 100%;
      font-family: 'Arial', sans-serif;
      background-color: #0b0b0b;
      background-image: url('https://www.transparenttextures.com/patterns/asfalt-dark.png');
      background-repeat: repeat;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      overflow: hidden;
    }

    .splash {
      text-align: center;
      color: #fff;
      z-index: 1;
    }

    .splash h1 {
      font-size: 3em;
      letter-spacing: 3px;
      text-transform: uppercase;
      text-shadow: 2px 2px 8px #000;
      margin-bottom: 20px;
    }

    .splash p {
      font-size: 1.3em;
      color: #ccc;
    }

    .overlay {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.6);
      z-index: 0;
    }

    .sound-toggle {
      position: absolute;
      bottom: 20px;
      right: 20px;
      background: rgba(255, 255, 255, 0.1);
      color: white;
      border: 1px solid #ccc;
      padding: 8px 14px;
      cursor: pointer;
      z-index: 2;
    }
  </style>
</head>
<body>
  <div class="overlay"></div>

  <div class="splash">
    <h1>Welcome to Crime Portal</h1>
    <p>Securing Justice... Please Wait</p>
  </div>

  <script>
    setTimeout(() => {
      window.location.href = "main.php";
    }, 4000);
  </script>
  
</body>
</html>
