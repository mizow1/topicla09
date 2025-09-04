<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/analysis">SEO分析</a></li>
        <li class="breadcrumb-item active">分析結果</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h2>SEO分析結果</h2>
        <p class="text-muted mb-0">
            <strong><?= htmlspecialchars($analysis['site_name']) ?></strong> - 
            <a href="<?= htmlspecialchars($analysis['url']) ?>" target="_blank" class="text-decoration-none">
                <?= htmlspecialchars($analysis['url']) ?>
            </a>
        </p>
        <small class="text-muted">
            分析日時: <?= date('Y年m月d日 H:i', strtotime($analysis['created_at'])) ?> 
            (処理時間: <?= $analysis['processing_time'] ?>秒)
        </small>
    </div>
    <div>
        <a href="/analysis?site_id=<?= $analysis['site_id'] ?>" class="btn btn-primary">
            新しい分析を実行
        </a>
    </div>
</div>

<?php if ($analysis['status'] !== 'completed'): ?>
    <div class="alert alert-warning" role="alert">
        <h5>分析が完了していません</h5>
        <p>ステータス: <?= ucfirst($analysis['status']) ?></p>
        <?php if ($analysis['error_message']): ?>
            <p>エラー: <?= htmlspecialchars($analysis['error_message']) ?></p>
        <?php endif; ?>
    </div>
<?php elseif (empty($recommendations)): ?>
    <div class="alert alert-info" role="alert">
        <h5>推奨事項がありません</h5>
        <p>この分析では具体的な改善提案が生成されませんでした。</p>
    </div>
<?php else: ?>
    <!-- サマリー統計 -->
    <div class="row mb-4">
        <?php
        $priorityCounts = array_count_values(array_column($recommendations, 'priority'));
        $categoryCounts = array_count_values(array_column($recommendations, 'category'));
        $totalEstimatedHours = array_sum(array_column($recommendations, 'estimated_hours'));
        ?>
        
        <div class="col-md-3 mb-3">
            <div class="card text-center border-danger">
                <div class="card-body">
                    <h3 class="text-danger"><?= $priorityCounts['high'] ?? 0 ?></h3>
                    <p class="card-text">高優先度</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center border-warning">
                <div class="card-body">
                    <h3 class="text-warning"><?= $priorityCounts['medium'] ?? 0 ?></h3>
                    <p class="card-text">中優先度</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center border-success">
                <div class="card-body">
                    <h3 class="text-success"><?= $priorityCounts['low'] ?? 0 ?></h3>
                    <p class="card-text">低優先度</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center border-info">
                <div class="card-body">
                    <h3 class="text-info"><?= round($totalEstimatedHours, 1) ?></h3>
                    <p class="card-text">予想作業時間</p>
                </div>
            </div>
        </div>
    </div>

    <!-- フィルター -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h6 class="mb-2">フィルター:</h6>
                    <div class="btn-group" role="group">
                        <input type="radio" class="btn-check" name="priorityFilter" id="all" value="all" checked>
                        <label class="btn btn-outline-secondary btn-sm" for="all">すべて</label>
                        
                        <input type="radio" class="btn-check" name="priorityFilter" id="high" value="high">
                        <label class="btn btn-outline-danger btn-sm" for="high">高優先度</label>
                        
                        <input type="radio" class="btn-check" name="priorityFilter" id="medium" value="medium">
                        <label class="btn btn-outline-warning btn-sm" for="medium">中優先度</label>
                        
                        <input type="radio" class="btn-check" name="priorityFilter" id="low" value="low">
                        <label class="btn btn-outline-success btn-sm" for="low">低優先度</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <h6 class="mb-2">カテゴリ:</h6>
                    <select class="form-select form-select-sm" id="categoryFilter">
                        <option value="all">すべてのカテゴリ</option>
                        <option value="meta">メタ要素</option>
                        <option value="technical">技術的SEO</option>
                        <option value="content">コンテンツ</option>
                        <option value="performance">パフォーマンス</option>
                        <option value="mobile">モバイル</option>
                        <option value="accessibility">アクセシビリティ</option>
                        <option value="structure">構造</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- 改善提案リスト -->
    <div id="recommendations-container">
        <?php foreach ($recommendations as $index => $rec): ?>
            <div class="recommendation-item mb-4" 
                 data-priority="<?= $rec['priority'] ?>" 
                 data-category="<?= $rec['category'] ?>">
                <div class="card border-<?= $rec['priority'] === 'high' ? 'danger' : ($rec['priority'] === 'medium' ? 'warning' : 'success') ?>">
                    <div class="card-header d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <h5 class="mb-1"><?= htmlspecialchars($rec['title']) ?></h5>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge bg-<?= $rec['priority'] === 'high' ? 'danger' : ($rec['priority'] === 'medium' ? 'warning' : 'success') ?>">
                                    優先度: <?= $rec['priority'] === 'high' ? '高' : ($rec['priority'] === 'medium' ? '中' : '低') ?>
                                </span>
                                <span class="badge bg-secondary">
                                    <?= ucfirst($rec['category']) ?>
                                </span>
                                <span class="badge bg-info">
                                    難易度: <?= $rec['difficulty'] === 'easy' ? '易' : ($rec['difficulty'] === 'medium' ? '中' : '難') ?>
                                </span>
                                <span class="badge bg-dark">
                                    予想時間: <?= $rec['estimated_hours'] ?>時間
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-primary">📌 結論</h6>
                                <div class="bg-light p-3 rounded mb-3">
                                    <?= nl2br(htmlspecialchars($rec['conclusion'])) ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-info">💡 詳細説明</h6>
                                <div class="mb-3">
                                    <?= nl2br(htmlspecialchars($rec['explanation'])) ?>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (!empty($rec['implementation_code'])): ?>
                            <div class="mt-3">
                                <h6 class="text-success">🔧 実装コード</h6>
                                <div class="implementation-code border rounded p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <small class="text-muted">以下のコードをコピーして実装してください</small>
                                        <button class="btn btn-sm btn-outline-secondary copy-btn" 
                                                onclick="copyCode(this)" 
                                                data-code="<?= htmlspecialchars($rec['implementation_code']) ?>">
                                            📋 コピー
                                        </button>
                                    </div>
                                    <pre><code><?= htmlspecialchars($rec['implementation_code']) ?></code></pre>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div id="no-results" class="alert alert-info" style="display: none;">
        <p class="mb-0">選択した条件に該当する推奨事項がありません。</p>
    </div>
<?php endif; ?>

<div class="text-center mt-5">
    <a href="/analysis" class="btn btn-primary me-2">別のページを分析</a>
    <a href="/analysis/history" class="btn btn-outline-secondary">分析履歴を見る</a>
</div>

<script>
// フィルタリング機能
function filterRecommendations() {
    const priorityFilter = document.querySelector('input[name="priorityFilter"]:checked').value;
    const categoryFilter = document.getElementById('categoryFilter').value;
    const items = document.querySelectorAll('.recommendation-item');
    const noResults = document.getElementById('no-results');
    let visibleCount = 0;
    
    items.forEach(item => {
        const priority = item.dataset.priority;
        const category = item.dataset.category;
        
        const priorityMatch = priorityFilter === 'all' || priority === priorityFilter;
        const categoryMatch = categoryFilter === 'all' || category === categoryFilter;
        
        if (priorityMatch && categoryMatch) {
            item.style.display = 'block';
            visibleCount++;
        } else {
            item.style.display = 'none';
        }
    });
    
    noResults.style.display = visibleCount === 0 ? 'block' : 'none';
}

// フィルターイベントリスナー
document.querySelectorAll('input[name="priorityFilter"]').forEach(radio => {
    radio.addEventListener('change', filterRecommendations);
});

document.getElementById('categoryFilter').addEventListener('change', filterRecommendations);

// コードコピー機能
function copyCode(button) {
    const code = button.dataset.code;
    
    if (navigator.clipboard) {
        navigator.clipboard.writeText(code).then(() => {
            button.textContent = '✅ コピー済み';
            setTimeout(() => {
                button.textContent = '📋 コピー';
            }, 2000);
        }).catch(() => {
            fallbackCopyCode(code, button);
        });
    } else {
        fallbackCopyCode(code, button);
    }
}

function fallbackCopyCode(text, button) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    document.body.appendChild(textArea);
    textArea.select();
    
    try {
        document.execCommand('copy');
        button.textContent = '✅ コピー済み';
        setTimeout(() => {
            button.textContent = '📋 コピー';
        }, 2000);
    } catch (err) {
        console.error('コピーに失敗しました:', err);
        alert('コピーに失敗しました。手動でコードをコピーしてください。');
    }
    
    document.body.removeChild(textArea);
}

// ページロード時にフィルターを初期化
document.addEventListener('DOMContentLoaded', function() {
    filterRecommendations();
});
</script>