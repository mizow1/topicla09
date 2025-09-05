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
                                    data-site-url="<?= htmlspecialchars($analysis['url']) ?>">
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
    // MarkdownをHTMLに変換
    const htmlContent = markdownToHtml(content);
    
    // モーダルのHTML
    const modalHtml = `
        <div class="modal fade" id="contentModal" tabindex="-1" aria-labelledby="contentModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="contentModalLabel">📝 生成された記事本文</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <h6>📖 見出し構造:</h6>
                            <pre class="bg-light p-2 border rounded"><code>${headingStructure.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</code></pre>
                        </div>
                        <div class="mb-3">
                            <h6>✍️ 本文内容（HTML表示）:</h6>
                            <div class="border rounded p-3" style="max-height: 400px; overflow-y: auto;">
                                ${htmlContent}
                            </div>
                        </div>
                        <div class="mb-3">
                            <h6>📄 本文内容（Markdown形式）:</h6>
                            <pre class="bg-light p-2 border rounded" style="max-height: 300px; overflow-y: auto;"><code>${content.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</code></pre>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary copy-content-btn" data-content="${content.replace(/'/g, "\\'")}">
                            📋 本文をコピー（Markdown）
                        </button>
                        <button type="button" class="btn btn-secondary copy-all-btn" data-content="${headingStructure.replace(/'/g, "\\'")}\\n\\n${content.replace(/'/g, "\\'")}">
                            📋 構造+本文をコピー
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // 既存のモーダルがあれば削除
    const existingModal = document.getElementById('contentModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // 新しいモーダルを追加
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // モーダルを表示
    const modal = new bootstrap.Modal(document.getElementById('contentModal'));
    modal.show();
    
    // モーダル内のコピーボタンのイベントリスナーを追加
    document.getElementById('contentModal').addEventListener('click', function(e) {
        if (e.target.classList.contains('copy-content-btn') || e.target.classList.contains('copy-all-btn')) {
            const textToCopy = e.target.dataset.content;
            const button = e.target;
            const originalText = button.textContent;
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(textToCopy).then(() => {
                    button.textContent = '✅ コピー済み';
                    setTimeout(() => {
                        button.textContent = originalText;
                    }, 2000);
                }).catch(err => {
                    console.error('コピーに失敗しました:', err);
                    fallbackCopyFromModal(textToCopy, button, originalText);
                });
            } else {
                fallbackCopyFromModal(textToCopy, button, originalText);
            }
        }
    });
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

function fallbackCopyFromModal(text, button, originalText) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    document.body.appendChild(textArea);
    textArea.select();
    
    try {
        document.execCommand('copy');
        button.textContent = '✅ コピー済み';
        setTimeout(() => {
            button.textContent = originalText;
        }, 2000);
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
    document.getElementById('contentHtmlDisplay').innerHTML = '<div class="text-center p-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">本文生成中...</span></div><p class="mt-2">記事本文を生成中です...</p></div>';
    document.getElementById('contentMarkdownDisplay').textContent = '';
    
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
            const htmlContent = markdownToHtml(data.content);
            document.getElementById('contentHtmlDisplay').innerHTML = htmlContent;
            document.getElementById('contentMarkdownDisplay').textContent = data.content;
        } else {
            document.getElementById('contentHtmlDisplay').innerHTML = '<div class="alert alert-danger">本文生成に失敗しました: ' + (data.error || '不明なエラー') + '</div>';
        }
    } catch (error) {
        console.error('本文生成エラー:', error);
        document.getElementById('contentHtmlDisplay').innerHTML = '<div class="alert alert-danger">本文生成中にエラーが発生しました</div>';
    }
}

// モーダル内のコピーボタンイベント
document.getElementById('copyMarkdownBtn').addEventListener('click', function() {
    const markdown = document.getElementById('contentMarkdownDisplay').textContent;
    if (markdown) {
        copyToClipboard(markdown);
    } else {
        alert('コピーするコンテンツがありません');
    }
});

document.getElementById('copyAllContentBtn').addEventListener('click', function() {
    const structure = document.getElementById('contentHeadingStructure').textContent;
    const markdown = document.getElementById('contentMarkdownDisplay').textContent;
    if (structure && markdown) {
        const combined = structure + '\n\n' + markdown;
        copyToClipboard(combined);
    } else {
        alert('コピーするコンテンツがありません');
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

