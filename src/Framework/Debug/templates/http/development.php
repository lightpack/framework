<!DOCTYPE html>
<html lang="en">

<head>
    <title>😱 Whoops!! We got some problems to deal with.</title>
    <meta charset="utf-8">
    <meta name="robots" content="noindex,nofollow"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"/>
    <style>
        <?php require __DIR__ . '/../css/styles.css' ?>
    </style>
</head>

<body>
    <div class="container">

        <div class="header bg-error bg-<?= strtolower($type) ?>">
            <h1 class="title">
                <?= $type ?> (<?= $code ?>)
            </h1>

            <div class="message">
                <?= $message ?>
            </div>
        </div>

        <div class="file-path">
            <span class="file-label">File:</span> <?= $file ?>
        </div>

        <?php if($code_preview) : ?>
        <div class="code-preview">
            <code>
                <?= $code_preview ?>
            </code>
        </div>
        <?php endif; ?>

        <?php if(trim($trace)) { ?>
            <div class="trace-container">
                <div class="title">
                    Backtrace Details
                </div>
                <div class="trace-list">
                    <?= $trace ?>
                </div>
            </div>
        <?php } ?>
    </div>
</body>

</html>