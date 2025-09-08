<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= url('analysis') ?>">SEO分析</a></li>
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
        <a href="<?= url('analysis?site_id=' . $analysis['site_id']) ?>" class="btn btn-primary">
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
                        <?php 
                        // proposalsデータがない場合、conclusionから提案を抽出
                        $proposals = [];
                        if (!empty($rec['proposals']) && is_array($rec['proposals'])) {
                            $proposals = $rec['proposals'];
                        } else {
                            // conclusionから数字付きリストを抽出
                            if (preg_match_all('/\d+\.\s*([^\n]+)/', $rec['conclusion'], $matches)) {
                                $proposals = $matches[1];
                            }
                        }
                        ?>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <?php if (!empty($proposals)): ?>
                                <div class="section-header">
                                    <span class="section-icon">💡</span>
                                    <h6 class="text-success">改善提案オプション（<?= count($proposals) ?>案）</h6>
                                </div>
                                <div class="proposal-options" data-rec-id="<?= $index ?>">
                                    <div class="proposal-grid">
                                        <?php foreach ($proposals as $proposalIndex => $proposal): ?>
                                        <?php
                                        // 見出し構造の最適化提案はHTMLタグ形式に変換してエスケープし、pre+codeで表示
                                        $isHeadingStructure = (strpos($rec['category'], 'structure') !== false || strpos($rec['title'], '見出し') !== false);
                                        $htmlProposal = $proposal;
                                        
                                        if ($isHeadingStructure) {
                                            // h1: テキスト, h2: テキスト形式を<h1>テキスト</h1>, <h2>テキスト</h2>に変換
                                            $htmlProposal = preg_replace('/h(\d):\s*([^,\n]+)/', '<h$1>$2</h$1>', $proposal);
                                            $htmlProposal = str_replace(', ', "\n", $htmlProposal);
                                        }
                                        ?>
                                        <div class="proposal-card" data-proposal-id="<?= $proposalIndex ?>">
                                            <div class="proposal-number"><?= $proposalIndex + 1 ?></div>
                                            <div class="proposal-text">
                                                <?php if ($isHeadingStructure): ?>
                                                    <pre style="background: #f8f9fa; padding: 10px; border-radius: 4px; white-space: pre-wrap; margin: 0;"><code><?= htmlspecialchars(trim($htmlProposal)) ?></code></pre>
                                                <?php else: ?>
                                                    <?= nl2br(htmlspecialchars(trim($proposal))) ?>
                                                <?php endif; ?>
                                            </div>
                                            <div class="proposal-actions">
                                                <button class="proposal-select-btn copy-proposal" 
                                                        data-proposal="<?= htmlspecialchars(trim($htmlProposal)) ?>"
                                                        data-rec-id="<?= $index ?>">
                                                    📋 コピー
                                                </button>
                                                <?php if ($isHeadingStructure): ?>
                                                <button class="proposal-select-btn generate-content-btn" 
                                                        data-proposal="<?= htmlspecialchars(trim($htmlProposal)) ?>"
                                                        data-rec-id="<?= $index ?>"
                                                        data-proposal-index="<?= $proposalIndex ?>"
                                                        data-site-url="<?= htmlspecialchars($analysis['url']) ?>">
                                                    ✍️ 本文作成
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="proposal-regenerate-section">
                                        <button class="regenerate-btn regenerate-proposals" 
                                                data-category="<?= htmlspecialchars($rec['category']) ?>"
                                                data-title="<?= htmlspecialchars($rec['title']) ?>"
                                                data-rec-id="<?= $index ?>">
                                            🔄 別の提案を生成
                                        </button>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-info">💡 詳細説明</h6>
                                <div class="mb-3">
                                    <?= nl2br($rec['explanation']) ?>
                                </div>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div id="no-results" class="alert alert-info" style="display: none;">
        <p class="mb-0">選択した条件に該当する推奨事項がありません。</p>
    </div>
<?php endif; ?>

<!-- 内部リンク最適化セクション -->
<?php if ($analysis['status'] === 'completed'): ?>
<div class="card mt-5">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="mb-0">🔗 内部リンク最適化提案</h4>
            <button type="button" class="btn btn-primary" id="generateInternalLinkBtn" 
                    data-analysis-id="<?= $analysis['id'] ?>" 
                    data-site-url="<?= htmlspecialchars($analysis['url']) ?>">
                💡 リンク最適化案を生成
            </button>
        </div>
    </div>
    <div class="card-body">
        <p class="text-muted">
            現在のサイト内容を分析し、親和性の高いページとのリンク提案と、新たに作成すべきページの提案を行います。
        </p>
        
        <!-- ローディング表示 -->
        <div id="linkLoadingSection" class="text-center p-4" style="display: none;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">生成中...</span>
            </div>
            <p class="mt-2">内部リンク最適化案を生成中です...</p>
        </div>
        
        <!-- 提案結果セクション -->
        <div id="linkResultsSection" style="display: none;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="text-primary mb-0">内部リンク最適化提案</h5>
                <button type="button" class="btn btn-outline-primary btn-sm" id="regenerateLinkBtn">
                    🔄 新しい提案を生成
                </button>
            </div>
            
            <div class="row">
                <!-- 既存ページとのリンク -->
                <div class="col-md-6">
                    <div class="card border-success">
                        <div class="card-header bg-success bg-opacity-10">
                            <h6 class="mb-0 text-success">🔗 既存ページとの内部リンク</h6>
                        </div>
                        <div class="card-body" id="existingPagesLinks">
                            <!-- 既存ページリンクがここに表示される -->
                        </div>
                    </div>
                </div>
                
                <!-- 新規ページ提案 -->
                <div class="col-md-6">
                    <div class="card border-info">
                        <div class="card-header bg-info bg-opacity-10">
                            <h6 class="mb-0 text-info">✨ 新規作成すべきページ</h6>
                        </div>
                        <div class="card-body" id="newPagesProposals">
                            <!-- 新規ページ提案がここに表示される -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- トピッククラスター提案セクション -->
<div class="card mt-4">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="mb-0">📚 トピッククラスター提案</h4>
            <button type="button" class="btn btn-primary" id="generateTopicClusterBtn" 
                    data-analysis-id="<?= $analysis['id'] ?>" 
                    data-site-url="<?= htmlspecialchars($analysis['url']) ?>">
                💡 クラスター案を生成
            </button>
        </div>
    </div>
    <div class="card-body">
        <p class="text-muted">
            この記事を中心としたトピッククラスターを作成することで、SEO効果を大幅に向上させることができます。
            記事から抽出したメインキーワードを基に、ピラー記事とクラスター記事の構成を提案します。
        </p>
        
        <!-- ローディング表示 -->
        <div id="clusterLoadingSection" class="text-center p-4" style="display: none;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">生成中...</span>
            </div>
            <p class="mt-2">トピッククラスター案を生成中です...</p>
        </div>
        
        <!-- 提案結果セクション -->
        <div id="clusterResultsSection" style="display: none;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 id="extractedKeywords" class="text-primary mb-0"></h5>
                <button type="button" class="btn btn-outline-primary btn-sm" id="regenerateClusterBtn">
                    🔄 新しい5案を生成
                </button>
            </div>
            <div id="clusterProposals" class="row"></div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="text-center mt-5">
    <a href="<?= url('analysis') ?>" class="btn btn-primary me-2">別のページを分析</a>
    <a href="<?= url('analysis/history') ?>" class="btn btn-outline-secondary">分析履歴を見る</a>
</div>

<!-- 記事構成モーダル -->
<div class="modal fade" id="structureModal" tabindex="-1" aria-labelledby="structureModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="structureModalLabel">📝 記事構成提案</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 id="structureModalSubtitle" class="text-muted mb-0"></h6>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="regenerateStructuresBtn">
                        🔄 新しい構成案を生成
                    </button>
                </div>
                <div id="structureProposals" class="row"></div>
                <div id="structureLoadingSection" class="text-center p-4" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">構成案生成中...</span>
                    </div>
                    <p class="mt-2">記事構成案を生成中です...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 記事本文モーダル -->
<div class="modal fade" id="contentModal" tabindex="-1" aria-labelledby="contentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="contentModalLabel">✍️ 記事本文</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <h6>📖 見出し構造:</h6>
                    <pre id="contentHeadingStructure" class="bg-light p-2 border rounded" style="white-space: pre-wrap;"></pre>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">📝 本文編集（Markdown形式）:</h6>
                        <div>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="previewToggleBtn">👁️ プレビュー</button>
                        </div>
                    </div>
                    <textarea id="contentMarkdownEditor" class="form-control" style="height: 300px; font-family: 'Consolas', monospace;" placeholder="記事本文をMarkdown形式で編集してください..."></textarea>
                    <div id="contentMarkdownPreview" class="border rounded p-3 bg-light" style="height: 300px; overflow-y: auto; display: none;"></div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="me-auto">
                    <div class="input-group" style="width: 300px;">
                        <input type="text" class="form-control form-control-sm" id="wordpressUrlInput" placeholder="WordPress記事URL（更新用）">
                        <button class="btn btn-outline-info btn-sm" type="button" id="updateWordPressBtn">🔄 記事更新</button>
                    </div>
                </div>
                <button type="button" class="btn btn-secondary" id="copyMarkdownBtn">
                    📋 Markdownをコピー
                </button>
                <button type="button" class="btn btn-secondary" id="copyAllContentBtn">
                    📋 構造+本文をコピー
                </button>
                <button type="button" class="btn btn-success" id="createNewPostBtn">
                    ✨ 新規記事として作成
                </button>
                <button type="button" class="btn btn-primary" id="saveArticleBtn">
                    💾 記事を保存
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
            </div>
        </div>
    </div>
</div>

<script>
// サイトURLの定数
const siteUrl = "<?= htmlspecialchars($analysis['url'], ENT_QUOTES, 'UTF-8') ?>";

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

function fallbackCopyProposal(text, button) {
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
        alert('コピーに失敗しました。手動でテキストをコピーしてください。');
    }
    
    document.body.removeChild(textArea);
}

// 提案コピー機能
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('copy-proposal')) {
        const proposal = e.target.dataset.proposal;
        const button = e.target;
        
        // コピー機能
        if (navigator.clipboard) {
            navigator.clipboard.writeText(proposal).then(() => {
                button.textContent = '✅ コピー済み';
                setTimeout(() => {
                    button.textContent = '📋 コピー';
                }, 2000);
            }).catch(err => {
                console.error('コピーに失敗しました:', err);
                fallbackCopyProposal(proposal, button);
            });
        } else {
            fallbackCopyProposal(proposal, button);
        }
    }
    
    if (e.target.classList.contains('generate-content-btn')) {
        const button = e.target;
        const proposal = button.dataset.proposal;
        const recId = button.dataset.recId;
        const proposalIndex = button.dataset.proposalIndex;
        const siteUrl = button.dataset.siteUrl;
        
        button.disabled = true;
        const originalText = button.textContent;
        button.textContent = '✍️ 作成中...';
        
        // 本文作成APIを呼び出し
        fetch('<?= url("analysis/generate-content") ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                headingStructure: proposal,
                siteUrl: siteUrl,
                recId: recId,
                proposalIndex: proposalIndex
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.content) {
                // 結果を新しいウィンドウまたはモーダルで表示
                showContentModal(data.content, proposal);
            } else {
                alert('本文作成に失敗しました: ' + (data.error || '不明なエラー'));
            }
        })
        .catch(error => {
            console.error('本文作成エラー:', error);
            alert('本文作成に失敗しました');
        })
        .finally(() => {
            button.disabled = false;
            button.textContent = originalText;
        });
    }
    
    if (e.target.classList.contains('regenerate-proposals')) {
        const button = e.target;
        const recId = button.dataset.recId;
        const category = button.dataset.category;
        const title = button.dataset.title;
        const container = button.closest('.proposal-options');
        
        button.disabled = true;
        button.textContent = '🔄 生成中...';
        
        // 現在の提案を取得
        const currentProposals = [];
        container.querySelectorAll('.proposal-text').forEach(item => {
            currentProposals.push(item.textContent.trim());
        });
        
        // 新しい提案を生成
        fetch('<?= url("analysis/regenerate-proposals") ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                category: category,
                title: title,
                currentProposals: currentProposals
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.proposals) {
                // 提案カードグリッドを更新
                const proposalGrid = container.querySelector('.proposal-grid');
                proposalGrid.innerHTML = '';
                
                data.proposals.forEach((proposal, index) => {
                    const isHeadingStructure = (category === 'structure' || title.includes('見出し'));
                    
                    let htmlProposal = proposal;
                    if (isHeadingStructure) {
                        // h1: テキスト, h2: テキスト形式を<h1>テキスト</h1>, <h2>テキスト</h2>に変換
                        htmlProposal = proposal.replace(/h(\d):\s*([^,\n]+)/g, '<h$1>$2</h$1>');
                        htmlProposal = htmlProposal.replace(/, /g, '\n');
                    }
                    
                    const card = document.createElement('div');
                    card.className = 'proposal-card';
                    card.dataset.proposalId = index;
                    
                    let proposalHtml;
                    if (isHeadingStructure) {
                        // HTMLタグをエスケープしてpre+codeで表示
                        const escapedHtml = htmlProposal.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                        proposalHtml = `<pre style="background: #f8f9fa; padding: 10px; border-radius: 4px; white-space: pre-wrap; margin: 0;"><code>${escapedHtml}</code></pre>`;
                    } else {
                        proposalHtml = proposal.replace(/\n/g, '<br>');
                    }
                    
                    card.innerHTML = `
                        <div class="proposal-number">${index + 1}</div>
                        <div class="proposal-text">
                            ${proposalHtml}
                        </div>
                        <div class="proposal-actions">
                            <button class="proposal-select-btn copy-proposal" 
                                    data-proposal="${htmlProposal.replace(/"/g, '&quot;')}"
                                    data-rec-id="${recId}">
                                📋 コピー
                            </button>
                            ${isHeadingStructure ? `
                            <button class="proposal-select-btn generate-content-btn" 
                                    data-proposal="${htmlProposal.replace(/"/g, '&quot;')}"
                                    data-rec-id="${recId}"
                                    data-proposal-index="${index}"
                                    data-site-url="${siteUrl}">
                                ✍️ 本文作成
                            </button>
                            ` : ''}
                        </div>
                    `;
                    proposalGrid.appendChild(card);
                });
            }
        })
        .catch(error => {
            console.error('再提案生成エラー:', error);
        })
        .finally(() => {
            button.disabled = false;
            button.textContent = '🔄 別案を生成';
        });
    }
});

// Markdown to HTML converter（簡易版）
function markdownToHtml(markdown) {
    let html = markdown;
    
    // 見出しの変換
    html = html.replace(/^### (.+)$/gm, '<h3>$1</h3>');
    html = html.replace(/^## (.+)$/gm, '<h2>$1</h2>');
    html = html.replace(/^# (.+)$/gm, '<h1>$1</h1>');
    
    // 改行の処理
    html = html.replace(/\n\n/g, '</p><p>');
    html = html.replace(/\n/g, '<br>');
    
    // 段落の処理
    html = '<p>' + html + '</p>';
    html = html.replace(/<p><h([1-6])>/g, '<h$1>');
    html = html.replace(/<\/h([1-6])><\/p>/g, '</h$1>');
    html = html.replace(/<p><\/p>/g, '');
    
    // 太字
    html = html.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
    
    // イタリック
    html = html.replace(/\*(.+?)\*/g, '<em>$1</em>');
    
    return html;
}

// 本文表示モーダル
function showContentModal(content, headingStructure) {
    // 既存のモーダルの要素に値を設定
    document.getElementById('contentModalLabel').textContent = '📝 生成された記事本文';
    document.getElementById('contentHeadingStructure').textContent = headingStructure;
    document.getElementById('contentMarkdownEditor').value = content;
    
    // プレビュー状態をリセット
    const editor = document.getElementById('contentMarkdownEditor');
    const preview = document.getElementById('contentMarkdownPreview');
    const toggleBtn = document.getElementById('previewToggleBtn');
    
    editor.style.display = 'block';
    preview.style.display = 'none';
    toggleBtn.textContent = '👁️ プレビュー';
    isHtmlTagView = false;
    
    // 現在の記事タイトルと構造を保存
    window.currentArticleTitle = '生成された記事';
    window.currentHeadingStructure = headingStructure;
    
    // モーダルを表示
    const modal = new bootstrap.Modal(document.getElementById('contentModal'));
    modal.show();
}

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


// ページロード時にフィルターを初期化
document.addEventListener('DOMContentLoaded', function() {
    filterRecommendations();
});

// トピッククラスター関連の変数
let currentTopicClusterProposals = [];

// トピッククラスター生成ボタンのイベント
document.getElementById('generateTopicClusterBtn').addEventListener('click', generateTopicCluster);

// トピッククラスター再生成ボタンのイベント
document.addEventListener('click', function(e) {
    if (e.target.id === 'regenerateClusterBtn') {
        generateTopicCluster(true);
    }
});

// トピッククラスター生成関数
async function generateTopicCluster(isRegenerate = false) {
    const button = document.getElementById('generateTopicClusterBtn');
    const analysisId = button.dataset.analysisId;
    const siteUrl = button.dataset.siteUrl;
    
    showClusterLoading();
    
    try {
        const response = await fetch('<?= url("analysis/generate-topic-cluster-from-analysis") ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                analysisId: analysisId,
                siteUrl: siteUrl,
                regenerate: isRegenerate,
                currentProposals: isRegenerate ? currentTopicClusterProposals : []
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            currentTopicClusterProposals = data.proposals;
            displayTopicClusterProposals(data.proposals, data.extractedKeywords || []);
        } else {
            alert('生成に失敗しました: ' + (data.error || '不明なエラー'));
        }
    } catch (error) {
        console.error('生成エラー:', error);
        alert('生成中にエラーが発生しました');
    } finally {
        hideClusterLoading();
    }
}

// クラスターローディング表示
function showClusterLoading() {
    document.getElementById('clusterLoadingSection').style.display = 'block';
    document.getElementById('clusterResultsSection').style.display = 'none';
}

// クラスターローディング非表示
function hideClusterLoading() {
    document.getElementById('clusterLoadingSection').style.display = 'none';
    document.getElementById('clusterResultsSection').style.display = 'block';
}

// トピッククラスター提案表示
function displayTopicClusterProposals(proposals, extractedKeywords) {
    // 抽出されたキーワードを表示
    const keywordsElement = document.getElementById('extractedKeywords');
    if (extractedKeywords && extractedKeywords.length > 0) {
        keywordsElement.textContent = `抽出キーワード: ${extractedKeywords.join(', ')}`;
    } else {
        keywordsElement.textContent = 'キーワードベースの提案';
    }
    
    const container = document.getElementById('clusterProposals');
    container.innerHTML = '';
    
    proposals.forEach((proposal, index) => {
        const card = createTopicClusterCard(proposal, index);
        container.appendChild(card);
    });
}

// トピッククラスターカード作成
function createTopicClusterCard(proposal, index) {
    const col = document.createElement('div');
    col.className = 'col-md-6 mb-4';
    
    col.innerHTML = `
        <div class="card h-100 border-info">
            <div class="card-header bg-light">
                <div class="d-flex align-items-center">
                    <div class="bg-info text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 30px; height: 30px; font-weight: bold;">
                        ${index + 1}
                    </div>
                    <h6 class="mb-0">提案${index + 1}</h6>
                </div>
            </div>
            <div class="card-body">
                <div class="mb-3 p-3 bg-primary bg-opacity-10 border-start border-primary border-4 rounded">
                    <h6 class="text-primary mb-2">🏛️ ピラー記事（メイン記事）</h6>
                    <strong class="text-dark">${proposal.pillarTitle}</strong>
                </div>
                
                <div class="bg-light p-3 rounded">
                    <h6 class="text-success mb-2">🔗 クラスター記事（関連記事）</h6>
                    <div class="cluster-articles">
                        ${proposal.clusterTitles.map((title, clusterIndex) => `
                            <div class="py-1 border-bottom border-light-subtle d-flex justify-content-between align-items-center">
                                <span>• ${title}</span>
                                <div class="btn-group" role="group">
                                    <button class="btn btn-outline-success btn-sm" onclick="generateArticleStructuresFromCluster('${title.replace(/'/g, "\\'")}', 'cluster')">
                                        📝 構成
                                    </button>
                                    <button class="btn btn-outline-info btn-sm" onclick="regenerateClusterArticle(${index}, ${clusterIndex}, '${title.replace(/'/g, "\\'")}')">
                                        🔄 再生成
                                    </button>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
                
                <div class="mt-3 text-center">
                    <button class="btn btn-success btn-sm me-2" onclick="generateArticleStructuresFromCluster('${proposal.pillarTitle.replace(/'/g, "\\'")}', 'pillar')">
                        📝 ピラー記事構成を作成
                    </button>
                    <button class="btn btn-outline-secondary btn-sm me-2" onclick="copyClusterToClipboard(\`${JSON.stringify(proposal).replace(/`/g, '\\`')}\`)">
                        📋 提案をコピー
                    </button>
                    <button class="btn btn-outline-warning btn-sm" onclick="regenerateTopicCluster(${index})">
                        🔄 このクラスターを再生成
                    </button>
                </div>
            </div>
        </div>
    `;
    
    return col;
}


// クラスター提案をクリップボードにコピー
function copyClusterToClipboard(proposalJson) {
    try {
        const proposal = JSON.parse(proposalJson);
        let text = `■ ピラー記事\n${proposal.pillarTitle}\n\n■ クラスター記事\n`;
        proposal.clusterTitles.forEach((title, index) => {
            text += `${index + 1}. ${title}\n`;
        });
        
        copyToClipboard(text);
    } catch (error) {
        console.error('コピーエラー:', error);
        alert('コピーに失敗しました');
    }
}

// 既存の記事構成とコンテンツ生成の変数とモーダル機能を再利用
let currentStructures = [];
let currentArticleTitle = '';

// 記事構成再生成ボタンのイベント
document.getElementById('regenerateStructuresBtn').addEventListener('click', function() {
    if (currentArticleTitle) {
        generateArticleStructuresFromCluster(currentArticleTitle, 'pillar', true);
    }
});

// 記事構成表示
function displayStructures(structures, articleTitle) {
    const container = document.getElementById('structureProposals');
    container.innerHTML = '';
    
    structures.forEach((structure, index) => {
        const card = createStructureCard(structure, index, articleTitle);
        container.appendChild(card);
    });
}

// 記事構成カード作成
function createStructureCard(structure, index, articleTitle) {
    const col = document.createElement('div');
    col.className = 'col-md-6 mb-3';
    
    col.innerHTML = `
        <div class="card border-success">
            <div class="card-header bg-light">
                <div class="d-flex align-items-center">
                    <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 30px; height: 30px; font-weight: bold;">
                        ${index + 1}
                    </div>
                    <div>
                        <h6 class="mb-0">構成案${index + 1}</h6>
                        <small class="text-muted">${structure.type || '基本構成'}</small>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="bg-light p-3 rounded mb-3" style="font-family: 'Consolas', 'Monaco', monospace; white-space: pre-line; line-height: 1.4;">
${structure.headings}
                </div>
                
                <div class="text-center">
                    <button class="btn btn-primary btn-sm me-2" onclick="generateArticleContentFromStructure('${articleTitle.replace(/'/g, "\\'")}', \`${structure.headings.replace(/`/g, '\\`')}\`)">
                        ✍️ 本文を作成
                    </button>
                    <button class="btn btn-outline-secondary btn-sm me-2" onclick="copyToClipboard(\`${structure.headings.replace(/`/g, '\\`')}\`)">
                        📋 構成をコピー
                    </button>
                    <button class="btn btn-outline-info btn-sm" onclick="regenerateArticleStructure('${articleTitle.replace(/'/g, "\\'")}', \`${structure.headings.replace(/`/g, '\\`')}\`, ${index})">
                        🔄 この構成を再生成
                    </button>
                </div>
            </div>
        </div>
    `;
    
    return col;
}

// 構成ローディング表示
function showStructureLoading() {
    document.getElementById('structureLoadingSection').style.display = 'block';
    document.getElementById('structureProposals').style.display = 'none';
}

// 構成ローディング非表示
function hideStructureLoading() {
    document.getElementById('structureLoadingSection').style.display = 'none';
    document.getElementById('structureProposals').style.display = 'block';
}

// 記事本文生成（構成から）
async function generateArticleContentFromStructure(articleTitle, headingStructure) {
    document.getElementById('contentModalLabel').textContent = '✍️ 記事本文';
    document.getElementById('contentHeadingStructure').textContent = headingStructure;
    
    // エディターに生成中メッセージを表示
    document.getElementById('contentMarkdownEditor').value = '記事本文を生成中です...\n\n生成が完了するまでしばらくお待ちください。';
    
    // プレビュー状態をリセット
    const editor = document.getElementById('contentMarkdownEditor');
    const preview = document.getElementById('contentMarkdownPreview');
    const toggleBtn = document.getElementById('previewToggleBtn');
    
    editor.style.display = 'block';
    preview.style.display = 'none';
    toggleBtn.textContent = '👁️ プレビュー';
    isHtmlTagView = false;
    
    // モーダル表示
    const modal = new bootstrap.Modal(document.getElementById('contentModal'));
    modal.show();
    
    try {
        const response = await fetch('<?= url("analysis/generate-article-content") ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                articleTitle: articleTitle,
                headingStructure: headingStructure,
                topic: document.getElementById('extractedKeywords').textContent.replace('抽出キーワード: ', '')
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('contentMarkdownEditor').value = data.content;
            
            // 現在の記事タイトルと構造を保存
            window.currentArticleTitle = articleTitle;
            window.currentHeadingStructure = headingStructure;
        } else {
            document.getElementById('contentMarkdownEditor').value = '本文生成に失敗しました: ' + (data.error || '不明なエラー');
        }
    } catch (error) {
        console.error('本文生成エラー:', error);
        document.getElementById('contentMarkdownEditor').value = '本文生成中にエラーが発生しました';
    }
}

// モーダル内のコピーボタンイベント
document.getElementById('copyMarkdownBtn').addEventListener('click', function() {
    const markdown = document.getElementById('contentMarkdownEditor').value;
    if (markdown) {
        copyToClipboard(markdown);
    } else {
        alert('コピーするコンテンツがありません');
    }
});

document.getElementById('copyAllContentBtn').addEventListener('click', function() {
    const structure = document.getElementById('contentHeadingStructure').textContent;
    const markdown = document.getElementById('contentMarkdownEditor').value;
    if (structure && markdown) {
        const combined = structure + '\n\n' + markdown;
        copyToClipboard(combined);
    } else {
        alert('コピーするコンテンツがありません');
    }
});

// プレビュー・編集モード切り替え機能（トグル）
let isHtmlTagView = false; // HTMLタグ表示状態を管理

document.getElementById('previewToggleBtn').addEventListener('click', function() {
    const editor = document.getElementById('contentMarkdownEditor');
    const preview = document.getElementById('contentMarkdownPreview');
    const toggleBtn = document.getElementById('previewToggleBtn');
    
    if (editor.style.display !== 'none') {
        // エディターを隠してプレビューを表示
        const markdownContent = editor.value;
        const htmlContent = markdownToHtml(markdownContent);
        
        if (isHtmlTagView) {
            // HTMLタグを表示（エスケープされた状態）
            preview.innerHTML = '<pre style="white-space: pre-wrap; font-family: monospace;">' + 
                               htmlContent.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;') + 
                               '</pre>';
            toggleBtn.textContent = '🖼️ HTMLプレビュー';
        } else {
            // HTMLプレビューを表示
            preview.innerHTML = htmlContent;
            toggleBtn.textContent = '⚡ HTMLタグ表示';
        }
        
        editor.style.display = 'none';
        preview.style.display = 'block';
    } else {
        // プレビュー表示中の場合
        if (!isHtmlTagView) {
            // HTMLプレビュー -> HTMLタグ表示
            const markdownContent = editor.value;
            const htmlContent = markdownToHtml(markdownContent);
            preview.innerHTML = '<pre style="white-space: pre-wrap; font-family: monospace;">' + 
                               htmlContent.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;') + 
                               '</pre>';
            toggleBtn.textContent = '✏️ 編集モード';
            isHtmlTagView = true;
        } else {
            // HTMLタグ表示 -> 編集モードに戻る
            editor.style.display = 'block';
            preview.style.display = 'none';
            toggleBtn.textContent = '👁️ プレビュー';
            isHtmlTagView = false;
        }
    }
});

// 記事保存機能
document.getElementById('saveArticleBtn').addEventListener('click', async function() {
    const button = this;
    const originalText = button.textContent;
    
    const title = window.currentArticleTitle || 'Unknown Title';
    const structure = document.getElementById('contentHeadingStructure').textContent;
    const content = document.getElementById('contentMarkdownEditor').value;
    
    if (!content.trim()) {
        alert('記事内容が空です');
        return;
    }
    
    button.disabled = true;
    button.textContent = '💾 保存中...';
    
    try {
        const response = await fetch('<?= url("analysis/save-article") ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                title: title,
                structure: structure,
                content: content,
                siteUrl: siteUrl
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('✅ 記事を保存しました');
            window.savedArticleId = data.articleId;
        } else {
            alert('保存に失敗しました: ' + (data.error || '不明なエラー'));
        }
    } catch (error) {
        console.error('保存エラー:', error);
        alert('保存中にエラーが発生しました');
    } finally {
        button.disabled = false;
        button.textContent = originalText;
    }
});

// WordPress新規記事作成機能
document.getElementById('createNewPostBtn').addEventListener('click', async function() {
    const button = this;
    const originalText = button.textContent;
    
    const title = window.currentArticleTitle || 'Unknown Title';
    const structure = document.getElementById('contentHeadingStructure').textContent;
    const content = document.getElementById('contentMarkdownEditor').value;
    
    if (!content.trim()) {
        alert('記事内容が空です');
        return;
    }
    
    button.disabled = true;
    button.textContent = '✨ 作成中...';
    
    try {
        const response = await fetch('<?= url("analysis/create-wordpress-post") ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                title: title,
                content: content,
                structure: structure
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('✅ WordPress記事を作成しました\nURL: ' + data.postUrl);
        } else {
            alert('WordPress記事作成に失敗しました: ' + (data.error || '不明なエラー'));
        }
    } catch (error) {
        console.error('WordPress作成エラー:', error);
        alert('WordPress記事作成中にエラーが発生しました');
    } finally {
        button.disabled = false;
        button.textContent = originalText;
    }
});

// WordPress記事更新機能
document.getElementById('updateWordPressBtn').addEventListener('click', async function() {
    const button = this;
    const originalText = button.textContent;
    
    const wordpressUrl = document.getElementById('wordpressUrlInput').value.trim();
    const title = window.currentArticleTitle || 'Unknown Title';
    const structure = document.getElementById('contentHeadingStructure').textContent;
    const content = document.getElementById('contentMarkdownEditor').value;
    
    if (!wordpressUrl) {
        alert('WordPress記事URLを入力してください');
        return;
    }
    
    if (!content.trim()) {
        alert('記事内容が空です');
        return;
    }
    
    button.disabled = true;
    button.textContent = '🔄 更新中...';
    
    try {
        const response = await fetch('<?= url("analysis/update-wordpress-post") ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                wordpressUrl: wordpressUrl,
                title: title,
                content: content,
                structure: structure
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('✅ WordPress記事を更新しました');
        } else {
            alert('WordPress記事更新に失敗しました: ' + (data.error || '不明なエラー'));
        }
    } catch (error) {
        console.error('WordPress更新エラー:', error);
        alert('WordPress記事更新中にエラーが発生しました');
    } finally {
        button.disabled = false;
        button.textContent = originalText;
    }
});

// 内部リンク最適化の変数
let currentInternalLinkProposals = [];

// 内部リンク最適化生成ボタンのイベント
document.getElementById('generateInternalLinkBtn').addEventListener('click', generateInternalLinkOptimization);

// 内部リンク最適化再生成ボタンのイベント
document.addEventListener('click', function(e) {
    if (e.target.id === 'regenerateLinkBtn') {
        generateInternalLinkOptimization(true);
    }
});

// 内部リンク最適化生成関数
async function generateInternalLinkOptimization(isRegenerate = false) {
    const button = document.getElementById('generateInternalLinkBtn');
    const analysisId = button.dataset.analysisId;
    const siteUrl = button.dataset.siteUrl;
    
    showLinkLoading();
    
    try {
        const response = await fetch('<?= url("analysis/generate-internal-link-optimization") ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                analysisId: analysisId,
                siteUrl: siteUrl,
                regenerate: isRegenerate,
                currentProposals: isRegenerate ? currentInternalLinkProposals : []
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            currentInternalLinkProposals = {
                existingPages: data.existingPages,
                newPageProposals: data.newPageProposals
            };
            displayInternalLinkProposals(data.existingPages, data.newPageProposals);
        } else {
            alert('生成に失敗しました: ' + (data.error || '不明なエラー'));
        }
    } catch (error) {
        console.error('生成エラー:', error);
        alert('生成中にエラーが発生しました');
    } finally {
        hideLinkLoading();
    }
}

// 内部リンクローディング表示
function showLinkLoading() {
    document.getElementById('linkLoadingSection').style.display = 'block';
    document.getElementById('linkResultsSection').style.display = 'none';
}

// 内部リンクローディング非表示
function hideLinkLoading() {
    document.getElementById('linkLoadingSection').style.display = 'none';
    document.getElementById('linkResultsSection').style.display = 'block';
}

// 内部リンク提案表示
function displayInternalLinkProposals(existingPages, newPageProposals) {
    // 既存ページリンク表示
    const existingPagesContainer = document.getElementById('existingPagesLinks');
    existingPagesContainer.innerHTML = '';
    
    if (existingPages && existingPages.length > 0) {
        existingPages.forEach((page, index) => {
            const pageElement = document.createElement('div');
            pageElement.className = 'border-bottom pb-2 mb-2';
            pageElement.innerHTML = `
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <h6 class="mb-1">${page.title}</h6>
                        <small class="text-muted">${page.url}</small>
                        <p class="text-sm mt-1">${page.reason}</p>
                    </div>
                    <button class="btn btn-outline-primary btn-sm" onclick="copyToClipboard('${page.linkText}')">
                        📋 コピー
                    </button>
                </div>
            `;
            existingPagesContainer.appendChild(pageElement);
        });
    } else {
        existingPagesContainer.innerHTML = '<p class="text-muted">既存ページとの関連リンクが見つかりませんでした。</p>';
    }
    
    // 新規ページ提案表示
    const newPagesContainer = document.getElementById('newPagesProposals');
    newPagesContainer.innerHTML = '';
    
    if (newPageProposals && newPageProposals.length > 0) {
        newPageProposals.forEach((page, index) => {
            const pageElement = document.createElement('div');
            pageElement.className = 'border-bottom pb-3 mb-3';
            pageElement.innerHTML = `
                <div class="mb-2">
                    <h6 class="mb-1">${page.title}</h6>
                    <p class="text-sm text-muted mb-2">${page.description}</p>
                    <small class="badge bg-info">${page.category}</small>
                </div>
                <div class="text-center">
                    <button class="btn btn-success btn-sm me-2" onclick="generateNewPageStructure('${page.title.replace(/'/g, "\\'")}', '${page.description.replace(/'/g, "\\'")}')">
                        📝 記事構成を作成
                    </button>
                    <button class="btn btn-outline-secondary btn-sm" onclick="copyToClipboard('${page.title}')">
                        📋 タイトルコピー
                    </button>
                </div>
            `;
            newPagesContainer.appendChild(pageElement);
        });
    } else {
        newPagesContainer.innerHTML = '<p class="text-muted">新規ページの提案が見つかりませんでした。</p>';
    }
}

// 新規ページの記事構成生成
async function generateNewPageStructure(title, description) {
    currentArticleTitle = title;
    
    document.getElementById('structureModalLabel').textContent = '📝 新規ページ記事構成提案';
    document.getElementById('structureModalSubtitle').textContent = `記事タイトル: ${title}`;
    
    // モーダル表示
    const modal = new bootstrap.Modal(document.getElementById('structureModal'));
    modal.show();
    
    showStructureLoading();
    
    try {
        const response = await fetch('<?= url("analysis/generate-article-structures") ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                articleTitle: title,
                articleType: 'new-page',
                description: description,
                topic: document.getElementById('extractedKeywords') ? document.getElementById('extractedKeywords').textContent.replace('抽出キーワード: ', '') : '',
                regenerate: false,
                currentStructures: []
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            currentStructures = data.structures;
            displayStructures(data.structures, title);
        } else {
            alert('構成生成に失敗しました: ' + (data.error || '不明なエラー'));
        }
    } catch (error) {
        console.error('構成生成エラー:', error);
        alert('構成生成中にエラーが発生しました');
    } finally {
        hideStructureLoading();
    }
}

// クラスター記事再生成
async function regenerateClusterArticle(proposalIndex, clusterIndex, currentTitle) {
    try {
        const response = await fetch('<?= url("analysis/regenerate-cluster-article") ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                proposalIndex: proposalIndex,
                clusterIndex: clusterIndex,
                currentTitle: currentTitle,
                topic: document.getElementById('extractedKeywords').textContent.replace('抽出キーワード: ', '')
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // 該当するクラスター記事を更新
            currentTopicClusterProposals[proposalIndex].clusterTitles[clusterIndex] = data.newTitle;
            
            // 表示を更新
            displayTopicClusterProposals(currentTopicClusterProposals, document.getElementById('extractedKeywords').textContent.replace('抽出キーワード: ', '').split(', '));
            
            alert('クラスター記事を再生成しました');
        } else {
            alert('再生成に失敗しました: ' + (data.error || '不明なエラー'));
        }
    } catch (error) {
        console.error('再生成エラー:', error);
        alert('再生成中にエラーが発生しました');
    }
}

// トピッククラスター単体再生成
async function regenerateTopicCluster(proposalIndex) {
    try {
        const currentProposal = currentTopicClusterProposals[proposalIndex];
        
        const response = await fetch('<?= url("analysis/regenerate-single-topic-cluster") ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                proposalIndex: proposalIndex,
                currentProposal: currentProposal,
                topic: document.getElementById('extractedKeywords').textContent.replace('抽出キーワード: ', '')
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // 該当する提案を更新
            currentTopicClusterProposals[proposalIndex] = data.newProposal;
            
            // 表示を更新
            displayTopicClusterProposals(currentTopicClusterProposals, document.getElementById('extractedKeywords').textContent.replace('抽出キーワード: ', '').split(', '));
            
            alert('トピッククラスターを再生成しました');
        } else {
            alert('再生成に失敗しました: ' + (data.error || '不明なエラー'));
        }
    } catch (error) {
        console.error('再生成エラー:', error);
        alert('再生成中にエラーが発生しました');
    }
}

// 記事構成生成（クラスター用）を修正
async function generateArticleStructuresFromCluster(articleTitle, articleType = 'pillar', isRegenerate = false) {
    currentArticleTitle = articleTitle; // 現在の記事タイトルを保存
    
    const modalLabel = articleType === 'pillar' ? '📝 ピラー記事構成提案' : '📝 クラスター記事構成提案';
    document.getElementById('structureModalLabel').textContent = modalLabel;
    document.getElementById('structureModalSubtitle').textContent = `記事タイトル: ${articleTitle}`;
    
    // モーダル表示
    const modal = new bootstrap.Modal(document.getElementById('structureModal'));
    modal.show();
    
    showStructureLoading();
    
    try {
        const response = await fetch('<?= url("analysis/generate-article-structures") ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                articleTitle: articleTitle,
                articleType: articleType,
                topic: document.getElementById('extractedKeywords') ? document.getElementById('extractedKeywords').textContent.replace('抽出キーワード: ', '') : '',
                regenerate: isRegenerate,
                currentStructures: isRegenerate ? currentStructures : []
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            currentStructures = data.structures;
            displayStructures(data.structures, articleTitle);
        } else {
            alert('構成生成に失敗しました: ' + (data.error || '不明なエラー'));
        }
    } catch (error) {
        console.error('構成生成エラー:', error);
        alert('構成生成中にエラーが発生しました');
    } finally {
        hideStructureLoading();
    }
}

// 記事構成の単体再生成
async function regenerateArticleStructure(articleTitle, currentStructure, structureIndex) {
    try {
        const response = await fetch('<?= url("analysis/regenerate-article-structure") ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                articleTitle: articleTitle,
                currentStructure: currentStructure,
                topic: document.getElementById('extractedKeywords') ? document.getElementById('extractedKeywords').textContent.replace('抽出キーワード: ', '') : ''
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // 該当する構成を新しいものに置き換え
            currentStructures[structureIndex] = {
                type: '再生成',
                headings: data.structure
            };
            
            // 表示を更新
            displayStructures(currentStructures, articleTitle);
            
            alert('記事構成を再生成しました');
        } else {
            alert('再生成に失敗しました: ' + (data.error || '不明なエラー'));
        }
    } catch (error) {
        console.error('再生成エラー:', error);
        alert('再生成中にエラーが発生しました');
    }
}

</script>

<style>
.cluster-articles .border-bottom:last-child {
    border-bottom: none !important;
}

.bg-primary.bg-opacity-10 {
    background-color: rgba(13, 110, 253, 0.1) !important;
}

.border-primary.border-4 {
    border-width: 4px !important;
}

.border-light-subtle {
    border-color: rgba(0,0,0,0.125) !important;
}
</style>