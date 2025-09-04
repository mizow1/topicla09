<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= url('css/style.css') ?>" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?= url() ?>"><?= APP_NAME ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= url() ?>">ホーム</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= url('sites') ?>">サイト管理</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= url('analysis') ?>">SEO分析</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= url('analytics') ?>">連携設定</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container mt-4">
        <?= $content ?>
    </main>

    <footer class="bg-light text-center py-3 mt-5">
        <div class="container">
            <p>&copy; 2024 <?= APP_NAME ?>. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= url('js/app.js') ?>"></script>
</body>
</html>