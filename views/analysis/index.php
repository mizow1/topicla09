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
    resultsDiv.innerHTML = `
        <div class="text-center p-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">分析中...</span>
            </div>
            <p class="mt-2">SEO分析を実行中です...</p>
        </div>
    `;
    
    // スムーズにスクロール
    analysisSection.scrollIntoView({ behavior: 'smooth' });
    
    fetch('<?= url("analysis/run") ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // 分析結果を直接表示
            displayAnalysisResults(data);
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
            <div class="alert alert-info" role="alert">
                <h6>分析処理中</h6>
                <div class="d-flex align-items-center">
                    <div class="spinner-border spinner-border-sm me-2" role="status">
                        <span class="visually-hidden">分析中...</span>
                    </div>
                    <span>分析を実行しています。完了まで1-2分程度かかる場合があります。そのままお待ちください。</span>
                </div>
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

// 分析結果表示関数
function displayAnalysisResults(data) {
    const resultsDiv = document.getElementById('analysis-results');
    const recommendations = data.results || [];
    
    if (!recommendations || recommendations.length === 0) {
        resultsDiv.innerHTML = `
            <div class="alert alert-info" role="alert">
                <h5>改善提案がありません</h5>
                <p>この分析では具体的な改善提案が生成されませんでした。</p>
            </div>
            <div class="text-center mt-4">
                <a href="<?= url('analysis/result/') ?>${data.analysis_id}" class="btn btn-primary">
                    詳細結果ページへ
                </a>
            </div>
        `;
        return;
    }

    // 優先度別カウント
    const priorityCounts = {high: 0, medium: 0, low: 0};
    let totalEstimatedHours = 0;
    
    recommendations.forEach(rec => {
        priorityCounts[rec.priority || 'medium']++;
        totalEstimatedHours += parseFloat(rec.estimated_hours || 1.0);
    });

    const headerHtml = `
        <div class="d-flex justify-content-between align-items-start mb-4">
            <div>
                <h2>SEO分析結果</h2>
                <p class="text-muted mb-0">
                    <strong>分析完了</strong>
                </p>
                <small class="text-muted">
                    分析日時: ${new Date().toLocaleString('ja-JP')} 
                </small>
            </div>
            <div>
                <a href="<?= url('analysis/result/') ?>${data.analysis_id}" class="btn btn-primary">
                    詳細結果ページへ
                </a>
            </div>
        </div>
    `;

    const summaryHtml = `
        <!-- サマリー統計 -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card text-center border-danger">
                    <div class="card-body">
                        <h3 class="text-danger">${priorityCounts.high}</h3>
                        <p class="card-text">高優先度</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center border-warning">
                    <div class="card-body">
                        <h3 class="text-warning">${priorityCounts.medium}</h3>
                        <p class="card-text">中優先度</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center border-success">
                    <div class="card-body">
                        <h3 class="text-success">${priorityCounts.low}</h3>
                        <p class="card-text">低優先度</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center border-info">
                    <div class="card-body">
                        <h3 class="text-info">${totalEstimatedHours.toFixed(1)}</h3>
                        <p class="card-text">予想作業時間</p>
                    </div>
                </div>
            </div>
        </div>
    `;

    let recommendationsHtml = '';
    recommendations.forEach((rec, index) => {
        // proposalsの処理
        let proposals = [];
        if (rec.proposals && Array.isArray(rec.proposals)) {
            proposals = rec.proposals;
        } else if (rec.conclusion) {
            // conclusionから数字付きリストを抽出
            const matches = rec.conclusion.match(/\d+\.\s*([^\n]+)/g);
            if (matches) {
                proposals = matches.map(match => match.replace(/^\d+\.\s*/, ''));
            }
        }

        const priorityClass = rec.priority === 'high' ? 'danger' : (rec.priority === 'medium' ? 'warning' : 'success');
        const priorityText = rec.priority === 'high' ? '高' : (rec.priority === 'medium' ? '中' : '低');
        const difficultyText = rec.difficulty === 'easy' ? '易' : (rec.difficulty === 'medium' ? '中' : '難');

        recommendationsHtml += `
            <div class="recommendation-item mb-4" data-priority="${rec.priority}" data-category="${rec.category}">
                <div class="card border-${priorityClass}">
                    <div class="card-header d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <h5 class="mb-1">${escapeHtml(rec.title)}</h5>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge bg-${priorityClass}">
                                    優先度: ${priorityText}
                                </span>
                                <span class="badge bg-secondary">
                                    ${rec.category}
                                </span>
                                <span class="badge bg-info">
                                    難易度: ${difficultyText}
                                </span>
                                <span class="badge bg-dark">
                                    予想時間: ${rec.estimated_hours}時間
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                ${proposals.length > 0 ? `
                                <div class="section-header">
                                    <span class="section-icon">💡</span>
                                    <h6 class="text-success">改善提案オプション（${proposals.length}案）</h6>
                                </div>
                                <div class="proposal-options">
                                    <div class="proposal-grid">
                                        ${proposals.map((proposal, pIndex) => `
                                        <div class="proposal-card">
                                            <div class="proposal-number">${pIndex + 1}</div>
                                            <div class="proposal-text">
                                                ${escapeHtml(proposal).replace(/\n/g, '<br>')}
                                            </div>
                                            <div class="proposal-actions">
                                                <button class="proposal-select-btn copy-proposal" 
                                                        data-proposal="${escapeHtml(proposal)}"
                                                        onclick="copyToClipboard('${escapeHtml(proposal)}')">
                                                    📋 コピー
                                                </button>
                                            </div>
                                        </div>
                                        `).join('')}
                                    </div>
                                </div>
                                ` : ''}
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-info">💡 詳細説明</h6>
                                <div class="mb-3">
                                    ${escapeHtml(rec.explanation || '').replace(/\n/g, '<br>')}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });

    resultsDiv.innerHTML = headerHtml + summaryHtml + recommendationsHtml;
}

// HTMLエスケープ関数
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// クリップボードコピー関数（簡易版）
function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            alert('✅ クリップボードにコピーしました');
        }).catch(err => {
            console.error('コピーに失敗しました:', err);
            fallbackCopy(text);
        });
    } else {
        fallbackCopy(text);
    }
}

function fallbackCopy(text) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    document.body.appendChild(textArea);
    textArea.select();
    
    try {
        document.execCommand('copy');
        alert('✅ クリップボードにコピーしました');
    } catch (err) {
        console.error('コピーに失敗しました:', err);
        alert('コピーに失敗しました。手動でコピーしてください。');
    }
    
    document.body.removeChild(textArea);
}
</script>

<style>
.proposal-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 15px;
}

.proposal-card {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 15px;
    background: #f8f9fa;
    position: relative;
    transition: all 0.2s ease;
}

.proposal-card:hover {
    border-color: #007bff;
    box-shadow: 0 2px 8px rgba(0,123,255,0.15);
}

.proposal-number {
    position: absolute;
    top: -10px;
    left: 15px;
    background: #007bff;
    color: white;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: bold;
}

.proposal-text {
    margin: 10px 0 15px 0;
    line-height: 1.5;
    font-size: 14px;
}

.proposal-actions {
    display: flex;
    gap: 8px;
    justify-content: flex-end;
}

.proposal-select-btn {
    background: #28a745;
    color: white;
    border: none;
    border-radius: 4px;
    padding: 6px 12px;
    font-size: 12px;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.proposal-select-btn:hover {
    background: #218838;
}

.section-header {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
}

.section-icon {
    margin-right: 8px;
    font-size: 18px;
}
</style>