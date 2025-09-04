<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">新しいサイトの追加</h4>
            </div>
            <div class="card-body">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger" role="alert">
                        <?= $_SESSION['error'] ?>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
                
                <form method="POST" id="site-form">
                    <div class="mb-3">
                        <label for="name" class="form-label">サイト名 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required
                               placeholder="例: 株式会社サンプル コーポレートサイト"
                               value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                        <div class="form-text">分析結果での識別に使用されます</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="domain" class="form-label">ドメイン <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="domain" name="domain" required
                               placeholder="例: example.com または https://example.com"
                               value="<?= htmlspecialchars($_POST['domain'] ?? '') ?>">
                        <div class="form-text">プロトコル（http://、https://）は自動で除去されます</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">説明</label>
                        <textarea class="form-control" id="description" name="description" rows="3"
                                  placeholder="サイトの概要や特徴を記述してください（任意）"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                        <div class="form-text">SEO分析時の参考情報として活用されます</div>
                    </div>
                    
                    <div class="alert alert-info" role="alert">
                        <h6 class="alert-heading">次のステップ</h6>
                        <p class="mb-0">
                            サイトを追加した後、より精密な分析のために Google Analytics と Search Console の連携設定を行うことをお勧めします。
                        </p>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="/sites" class="btn btn-secondary me-md-2">キャンセル</a>
                        <button type="submit" class="btn btn-primary">サイトを追加</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('site-form').addEventListener('submit', function(e) {
    const domain = document.getElementById('domain').value.trim();
    
    // 簡易的なドメイン形式チェック
    if (domain && !domain.match(/^[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/) && !domain.match(/^https?:\/\/[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/)) {
        e.preventDefault();
        alert('有効なドメイン形式を入力してください\n例: example.com または https://example.com');
        return false;
    }
});
</script>