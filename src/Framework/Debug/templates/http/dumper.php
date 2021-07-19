<!DOCTYPE html>
<html lang="en">

<head>
    <title><?= $dump_function ?></title>
    <meta charset="utf-8">
    <meta name="robots" content="noindex,nofollow" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <style>
        <?php require __DIR__ . '/../css/styles.css' ?>
        <?php require __DIR__ . '/../css/dumper.css' ?>
    </style>
</head>

<body>
    <div class="container">
        <?php foreach ($args as $arg) : ?>
            <div class="code-preview">
                <?php if($dump_function === 'print_r'):?>
                    <pre><code><?= $dump_function($arg, 1) ?></code></pre>
                <?php endif ?>

                <?php if($dump_function === 'var_dump'):?>
                    <pre><code><?= $dump_function($arg) ?></code></pre>
                <?php endif ?>
            </div>
        <?php endforeach ?>
    </div>
</body>

</html>