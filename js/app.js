// グローバルアプリケーション関数
const App = {
    // ローディング表示
    showLoading: function(target) {
        const loadingHTML = `
            <div class="loading">
                <div class="spinner"></div>
                <p class="mt-3">分析中です...</p>
            </div>
        `;
        document.querySelector(target).innerHTML = loadingHTML;
    },
    
    // エラー表示
    showError: function(message) {
        const alertHTML = `
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        document.querySelector('#alerts').innerHTML = alertHTML;
    },
    
    // 成功メッセージ表示
    showSuccess: function(message) {
        const alertHTML = `
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        document.querySelector('#alerts').innerHTML = alertHTML;
    },
    
    // SEO分析実行
    runAnalysis: function(url, siteId) {
        this.showLoading('#analysis-results');
        
        fetch('https://mizy.sakura.ne.jp/topicla09/analysis/run', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ url: url, site_id: siteId })
        })
        .then(async response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers.get('content-type'));
            
            if (!response.ok) {
                const text = await response.text();
                console.log('Error response:', text);
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            // レスポンスがJSONかチェック
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.log('Non-JSON response:', text);
                throw new Error('サーバーからの応答が正しくありません');
            }
            return response.json();
        })
        .then(data => {
            console.log('Analysis response:', data);
            if (data.success) {
                // 分析結果が配列かチェック
                const results = data.results || [];
                if (Array.isArray(results)) {
                    this.displayAnalysisResults(results);
                } else {
                    // resultsがない場合は分析ID経由で結果を取得
                    if (data.analysis_id) {
                        this.fetchAnalysisResults(data.analysis_id);
                    } else {
                        this.showError('分析結果の形式が正しくありません');
                    }
                }
            } else {
                this.showError(data.error || '分析エラーが発生しました');
            }
        })
        .catch(error => {
            console.error('Analysis error:', error);
            this.showError('分析中にエラーが発生しました');
        });
    },
    
    // 分析ID経由で結果を取得
    fetchAnalysisResults: function(analysisId) {
        fetch(`https://mizy.sakura.ne.jp/topicla09/api/analysis/${analysisId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.recommendations) {
                this.displayAnalysisResults(data.recommendations);
            } else {
                this.showError('分析結果の取得に失敗しました');
            }
        })
        .catch(error => {
            console.error('Fetch analysis results error:', error);
            this.showError('分析結果の取得中にエラーが発生しました');
        });
    },

    // 分析結果表示
    displayAnalysisResults: function(results) {
        // 安全性チェック
        if (!results || !Array.isArray(results)) {
            this.showError('分析結果が見つかりません');
            return;
        }
        
        if (results.length === 0) {
            document.querySelector('#analysis-results').innerHTML = '<p class="text-muted">分析結果がありません。</p>';
            return;
        }
        
        let html = '<div class="analysis-results">';
        
        results.forEach(result => {
            const priorityClass = `priority-${result.priority}`;
            html += `
                <div class="analysis-result ${priorityClass}">
                    <h5>${result.title}</h5>
                    <p><strong>結論:</strong> ${result.conclusion}</p>
                    <div class="recommendation">
                        <h6>詳細説明:</h6>
                        <p>${result.explanation}</p>
                        ${result.implementation ? `
                            <h6>実装コード:</h6>
                            <div class="implementation-code">${result.implementation}</div>
                        ` : ''}
                    </div>
                    <div class="mt-2">
                        <span class="badge bg-${result.priority === 'high' ? 'danger' : result.priority === 'medium' ? 'warning' : 'success'}">
                            優先度: ${result.priority === 'high' ? '高' : result.priority === 'medium' ? '中' : '低'}
                        </span>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        document.querySelector('#analysis-results').innerHTML = html;
    },
    
    // URL妥当性チェック
    isValidUrl: function(string) {
        try {
            new URL(string);
            return true;
        } catch (_) {
            return false;
        }
    }
};

// DOMロード時の初期化
document.addEventListener('DOMContentLoaded', function() {
    // アラートエリア作成
    if (!document.querySelector('#alerts')) {
        const alertsDiv = document.createElement('div');
        alertsDiv.id = 'alerts';
        document.querySelector('main').prepend(alertsDiv);
    }
    
    // フォーム送信イベント
    const analysisForm = document.querySelector('#analysis-form');
    if (analysisForm) {
        analysisForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const url = formData.get('url');
            const siteId = formData.get('site_id');
            
            if (!App.isValidUrl(url)) {
                App.showError('有効なURLを入力してください');
                return;
            }
            
            App.runAnalysis(url, siteId);
        });
    }
    
    // サイト追加フォーム
    const siteForm = document.querySelector('#site-form');
    if (siteForm) {
        siteForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('https://mizy.sakura.ne.jp/topicla09/api/sites', {
                method: 'POST',
                body: formData
            })
            .then(async response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers.get('content-type'));
                
                if (!response.ok) {
                    const text = await response.text();
                    console.log('Error response:', text);
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                // レスポンスがJSONかチェック
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    console.log('Non-JSON response:', text);
                    throw new Error('サーバーからの応答が正しくありません');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    App.showSuccess('サイトが追加されました');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    App.showError(data.error || 'サイト追加エラー');
                }
            })
            .catch(error => {
                console.error('Site creation error:', error);
                if (error.message.includes('status: 404')) {
                    App.showError('APIエンドポイントが見つかりません');
                } else {
                    App.showError(error.message || 'サイト追加中にエラーが発生しました');
                }
            });
        });
    }
});