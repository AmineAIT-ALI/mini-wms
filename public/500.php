<?php
declare(strict_types=1);
if (!headers_sent()) {
    http_response_code(500);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 – Server Error – Mini WMS</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f5f5f5; display: flex;
               align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .box { background: #fff; border-radius: 8px; padding: 40px; text-align: center;
               box-shadow: 0 2px 12px rgba(0,0,0,.1); max-width: 400px; }
        h1 { color: #dc2626; font-size: 3rem; margin: 0 0 .5rem; }
        p  { color: #555; }
        a  { color: #2563eb; }
    </style>
</head>
<body>
<div class="box">
    <h1>500</h1>
    <h2>Server Error</h2>
    <p>An unexpected error occurred. It has been logged.</p>
    <p><a href="dashboard.php">Back to dashboard</a></p>
</div>
</body>
</html>
