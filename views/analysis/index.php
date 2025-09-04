<div class="row">
    <div class="col-lg-8 mx-auto">
        <h2 class="mb-4">SEO分析</h2>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger" role="alert">
                <?= $_SESSION['error'] ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if (empty($sites)): ?>
            <div class="alert alert-warning" role="alert">
                <h5>サイトが登録されていません</h5>
                <p>SEO分析を開始するには、まずサイトを登録してください。</p>
                <a href="<?= url('sites/add') ?>" class="btn btn-primary">サイトを登録</a>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">ページ分析</h5>
                </div>
                <div class="card-body">
                    <form id="analysis-form">
                        <div class="mb-3">
                            <label for="site_id" class="form-label">サイト選択 <span class="text-danger">*</span></label>
                            <select class="form-select" id="site_id" name="site_id" required>
                                <option value="">サイトを選択してください</option>
                                <?php foreach ($sites as $site): ?>
                                    <option value="<?= $site['id'] ?>" <?= ($selectedSiteId == $site['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($site['name']) ?> (<?= htmlspecialchars($site['domain']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="url" class="form-label">分析対象URL <span class="text-danger">*</span></label>
                            <input type="url" class="form-control" id="url" name="url" required
                                   placeholder="https://example.com/page">
                            <div class="form-text">分析したいページの完全なURLを入力してください</div>
                        </div>
                        
                        <div class="alert alert-info" role="alert">
                            <h6 class="alert-heading">分析内容</h6>
                            <ul class="mb-0">
                                <li>メタ要素（title, description, OGタグ等）の最適化</li>
                                <li>技術的SEO（表示速度、構造化データ等）</li>
                                <li>コンテンツの質とキーワード最適化</li>
                                <li>モバイルフレンドリー・アクセシビリティ</li>
                                <li>パフォーマンス（Core Web Vitals等）</li>
                            </ul>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                🔍 SEO分析を開始
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- 分析結果表示エリア -->
<div class="row mt-5" id="analysis-section" style="display: none;">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">分析結果</h5>
            </div>
            <div class="card-body" id="analysis-results">
                <!-- 分析結果がここに表示されます -->
            </div>
        </div>
    </div>
</div>

<?php if (!empty($sites)): ?>
    <div class="row mt-5">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4>分析履歴</h4>
                <a href="<?= url('analysis/history') ?>" class="btn btn-outline-secondary">すべて表示</a>
            </div>
            
            <?php if (!empty($recentAnalyses)): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>サイト名</th>
                                <th>分析URL</th>
                                <th>提案数</th>
                                <th>分析日時</th>
                                <th>処理時間</th>
                                <th>アクション</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentAnalyses as $analysis): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($analysis['site_name']) ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($analysis['domain']) ?></small>
                                    </td>
                                    <td>
                                        <a href="<?= htmlspecialchars($analysis['url']) ?>" target="_blank" class="text-decoration-none">
                                            <?= htmlspecialchars(substr($analysis['url'], 0, 40)) ?>...
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary"><?= $analysis['recommendation_count'] ?>件</span>
                                    </td>
                                    <td><?= date('Y/m/d H:i', strtotime($analysis['created_at'])) ?></td>
                                    <td><?= $analysis['processing_time'] ?>秒</td>
                                    <td>
                                        <a href="<?= url('analysis/result/' . $analysis['id']) ?>" class="btn btn-sm btn-primary">
                                            結果を見る
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted">まだ分析履歴がありません。上記のフォームから最初の分析を実行してください。</p>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<script>
document.getElementById('analysis-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const analysisSection = document.getElementById('analysis-section');
    const resultsDiv = document.getElementById('analysis-results');
    
    // 分析結果セクションを表示
    analysisSection.style.display = 'block';
    
    // ローディング表示
    App.showLoading('#analysis-results');
    
    // スムーズにスクロール
    analysisSection.scrollIntoView({ behavior: 'smooth' });
    
    fetch('/analysis/run', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // 結果ページにリダイレクト
            window.location.href = '/analysis/result/' + data.analysis_id;
        } else {
            resultsDiv.innerHTML = `
                <div class="alert alert-danger" role="alert">
                    <h6>分析エラー</h6>
                    <p>${data.error}</p>
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Analysis error:', error);
        resultsDiv.innerHTML = `
            <div class="alert alert-danger" role="alert">
                <h6>システムエラー</h6>
                <p>分析中にシステムエラーが発生しました。しばらく時間をおいて再試行してください。</p>
            </div>
        `;
    });
});

// URLフィールドにサイトのドメインを自動入力
document.getElementById('site_id').addEventListener('change', function() {
    const siteId = this.value;
    if (siteId) {
        const selectedOption = this.options[this.selectedIndex];
        const domain = selectedOption.textContent.match(/\((.*?)\)/);
        if (domain && domain[1]) {
            const urlField = document.getElementById('url');
            if (!urlField.value) {
                urlField.value = 'https://' + domain[1] + '/';
            }
        }
    }
});
</script>