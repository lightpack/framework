<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Oops!!</title>
        <style>
            body {
                font-family: arial, sans-serif;
                font-size: 14px;
            }
            .container {
                margin: 30px auto;
                border: 2px solid #795548;
                border-radius: 5px;
                max-width: 380px;
                padding: 0 30px 15px;
                background: #f1f1f1;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <?php if($error_template_not_found): ?>
                <?php require __DIR__ . '/500.php' ?>
            <?php else: ?>
                <?php require __DIR__ . '/' . $status_code . '.php' ?>
            <?php endif; ?>
        </div>
    </body>
</html>