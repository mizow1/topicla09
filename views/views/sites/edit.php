<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0">サイト情報の編集</h4>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?= $site['id'] ?>)">
                    削除
                </button>
            </div>
            <div class="card-body">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger" role="alert">
                        <?= $_SESSION['error'] ?>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label for="name" class="form-label">サイト名 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required
                               value="<?= htmlspecialchars($site['name']) ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="domain" class="form-label">ドメイン <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="domain" name="domain" required
                               value="<?= htmlspecialchars($site['domain']) ?>">
                        <div class="form-text">ドメイン変更は慎重に行ってください。分析履歴との整合性が取れなくなる場合があります。</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">説明</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($site['description'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <h6>連携状況</h6>
                        <div class="row">
                            <div class="col-6">
                                <span class="analytics-status <?= $site['ga_connected'] ? 'status-connected' : 'status-not-connected' ?>">
                                    Google Analytics: <?= $site['ga_connected'] ? '連携済み' : '未連携' ?>
                                </span>
                            </div>
                            <div class="col-6">
                                <span class="analytics-status <?= $site['gsc_connected'] ? 'status-connected' : 'status-not-connected' ?>">
                                    Search Console: <?= $site['gsc_connected'] ? '連携済み' : '未連携' ?>
                                </span>
                            </div>
                        </div>
                        <div class="mt-2">
                            <a href="/sites/analytics/<?= $site['id'] ?>" class="btn btn-sm btn-outline-primary">
                                連携設定を変更
                            </a>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="/sites" class="btn btn-secondary me-md-2">キャンセル</a>
                        <button type="submit" class="btn btn-primary">更新</button>
                    </div>
                </form>
            </div>
        </div>
        
        <?php
        // このサイトの分析履歴を表示
        $db = Database::getInstance();
        $analyses = $db->fetchAll("
            SELECT * FROM analysis_history 
            WHERE site_id = ? 
            ORDER BY created_at DESC 
            LIMIT 5
        ", [$site['id']]);
        
        if (!empty($analyses)): ?>
            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="mb-0">最近の分析履歴</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>URL</th>
                                    <th>ステータス</th>
                                    <th>分析日時</th>
                                    <th>アクション</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($analyses as $analysis): ?>
                                    <tr>
                                        <td>
                                            <a href="<?= htmlspecialchars($analysis['url']) ?>" target="_blank" class="text-decoration-none">
                                                <?= htmlspecialchars(substr($analysis['url'], 0, 30)) ?>...
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $analysis['status'] === 'completed' ? 'success' : ($analysis['status'] === 'failed' ? 'danger' : 'warning') ?>">
                                                <?= $analysis['status'] ?>
                                            </span>
                                        </td>
                                        <td><?= date('m/d H:i', strtotime($analysis['created_at'])) ?></td>
                                        <td>
                                            <?php if ($analysis['status'] === 'completed'): ?>
                                                <a href="/analysis/result/<?= $analysis['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                    結果
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- 削除確認モーダル -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">サイト削除の確認</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>本当に「<?= htmlspecialchars($site['name']) ?>」を削除しますか？</p>
                <div class="alert alert-warning">
                    <strong>注意:</strong> この操作は取り消せません。関連する分析履歴もすべて削除されます。
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <form method="POST" action="/sites/delete/<?= $site['id'] ?>" style="display: inline;">
                    <button type="submit" class="btn btn-danger">削除実行</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(siteId) {
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}
</script>