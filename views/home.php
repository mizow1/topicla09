<div class="row">
    <div class="col-lg-8 mx-auto text-center">
        <h1 class="display-4 mb-4">SEO改善提案サービス</h1>
        <p class="lead mb-5">Gemini AIを活用してサイトのSEOを分析し、具体的な改善提案を提供します</p>
        
        <div class="row mb-5">
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">🔍 詳細分析</h5>
                        <p class="card-text">各ページのSEO要素を詳細に分析し、具体的な改善ポイントを特定</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">📊 データ連携</h5>
                        <p class="card-text">Google AnalyticsとSearch Consoleのデータを活用した精密な分析</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">💡 実装提案</h5>
                        <p class="card-text">コピペで使える実装コード付きの改善提案を優先度順で表示</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6 mb-3">
                <a href="<?= url('sites') ?>" class="btn btn-primary btn-lg w-100">
                    📱 サイト管理
                </a>
                <p class="small text-muted mt-2">サイトを登録して分析を開始</p>
            </div>
            <div class="col-md-6 mb-3">
                <a href="<?= url('analysis') ?>" class="btn btn-outline-primary btn-lg w-100">
                    🔎 SEO分析
                </a>
                <p class="small text-muted mt-2">ページのSEO分析を実行</p>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($recentAnalyses)): ?>
<div class="row mt-5">
    <div class="col-12">
        <h3 class="mb-4">最近の分析結果</h3>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>サイト名</th>
                        <th>分析URL</th>
                        <th>分析日時</th>
                        <th>アクション</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentAnalyses as $analysis): ?>
                    <tr>
                        <td><?= htmlspecialchars($analysis['site_name']) ?></td>
                        <td>
                            <a href="<?= htmlspecialchars($analysis['url']) ?>" target="_blank" class="text-decoration-none">
                                <?= htmlspecialchars(substr($analysis['url'], 0, 50)) ?>...
                            </a>
                        </td>
                        <td><?= date('Y/m/d H:i', strtotime($analysis['created_at'])) ?></td>
                        <td>
                            <a href="<?= url('analysis/result/' . $analysis['id']) ?>" class="btn btn-sm btn-outline-primary">
                                結果を見る
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>