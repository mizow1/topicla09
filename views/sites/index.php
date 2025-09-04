<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>サイト管理</h2>
    <a href="/sites/add" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> サイト追加
    </a>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= $_SESSION['success'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= $_SESSION['error'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<?php if (empty($sites)): ?>
    <div class="text-center py-5">
        <h4>サイトが登録されていません</h4>
        <p class="text-muted">SEO分析を開始するには、まずサイトを登録してください。</p>
        <a href="/sites/add" class="btn btn-primary">最初のサイトを追加</a>
    </div>
<?php else: ?>
    <div class="row">
        <?php foreach ($sites as $site): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card site-card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($site['name']) ?></h5>
                        <p class="card-text">
                            <small class="text-muted"><?= htmlspecialchars($site['domain']) ?></small>
                        </p>
                        
                        <?php if ($site['description']): ?>
                            <p class="card-text"><?= nl2br(htmlspecialchars($site['description'])) ?></p>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <div class="row">
                                <div class="col-6">
                                    <span class="analytics-status <?= $site['ga_connected'] ? 'status-connected' : 'status-not-connected' ?>">
                                        GA: <?= $site['ga_connected'] ? '連携済み' : '未連携' ?>
                                    </span>
                                </div>
                                <div class="col-6">
                                    <span class="analytics-status <?= $site['gsc_connected'] ? 'status-connected' : 'status-not-connected' ?>">
                                        GSC: <?= $site['gsc_connected'] ? '連携済み' : '未連携' ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="btn-group w-100" role="group">
                            <a href="/analysis?site_id=<?= $site['id'] ?>" class="btn btn-primary btn-sm">
                                分析実行
                            </a>
                            <a href="/sites/analytics/<?= $site['id'] ?>" class="btn btn-outline-secondary btn-sm">
                                連携設定
                            </a>
                            <a href="/sites/edit/<?= $site['id'] ?>" class="btn btn-outline-secondary btn-sm">
                                編集
                            </a>
                        </div>
                    </div>
                    <div class="card-footer text-muted">
                        <small>作成日: <?= date('Y/m/d', strtotime($site['created_at'])) ?></small>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <?php
    // 各サイトの分析履歴を取得
    $analysisHistory = $db->fetchAll("
        SELECT ah.*, s.name as site_name 
        FROM analysis_history ah 
        JOIN sites s ON ah.site_id = s.id 
        WHERE ah.status = 'completed'
        ORDER BY ah.created_at DESC 
        LIMIT 10
    ");
    
    if (!empty($analysisHistory)): ?>
        <div class="mt-5">
            <h4 class="mb-3">最近の分析履歴</h4>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>サイト名</th>
                            <th>分析URL</th>
                            <th>分析日時</th>
                            <th>処理時間</th>
                            <th>アクション</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($analysisHistory as $analysis): ?>
                            <tr>
                                <td><?= htmlspecialchars($analysis['site_name']) ?></td>
                                <td>
                                    <a href="<?= htmlspecialchars($analysis['url']) ?>" target="_blank" class="text-decoration-none">
                                        <?= htmlspecialchars(substr($analysis['url'], 0, 40)) ?>...
                                    </a>
                                </td>
                                <td><?= date('Y/m/d H:i', strtotime($analysis['created_at'])) ?></td>
                                <td><?= $analysis['processing_time'] ?>秒</td>
                                <td>
                                    <a href="/analysis/result/<?= $analysis['id'] ?>" class="btn btn-sm btn-outline-primary">
                                        結果表示
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>