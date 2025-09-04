<div class="row">
    <div class="col-12">
        <h2 class="mb-4">連携設定</h2>
        
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
        
        <!-- 統計情報 -->
        <div class="row mb-4">
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h4 class="text-primary"><?= $stats['total_sites'] ?></h4>
                        <p class="card-text">登録サイト数</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h4 class="text-success"><?= $stats['ga_connected'] ?></h4>
                        <p class="card-text">GA連携済み</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h4 class="text-info"><?= $stats['gsc_connected'] ?></h4>
                        <p class="card-text">GSC連携済み</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h4 class="text-warning"><?= $stats['fully_connected'] ?></h4>
                        <p class="card-text">完全連携</p>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (empty($sites)): ?>
            <div class="text-center py-5">
                <h4>サイトが登録されていません</h4>
                <p class="text-muted">連携設定をするには、まずサイトを登録してください。</p>
                <a href="<?= url('sites/add') ?>" class="btn btn-primary">サイトを追加</a>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">分析ツール連携状況</h5>
                    <a href="<?= url('sites/add') ?>" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-circle"></i> サイト追加
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>サイト名</th>
                                    <th>ドメイン</th>
                                    <th>GA連携</th>
                                    <th>GSC連携</th>
                                    <th>登録日</th>
                                    <th>アクション</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sites as $site): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($site['name']) ?></strong>
                                        </td>
                                        <td>
                                            <code><?= htmlspecialchars($site['domain']) ?></code>
                                        </td>
                                        <td>
                                            <?php if ($site['ga_connected']): ?>
                                                <span class="badge bg-success">連携済み</span>
                                                <br><small class="text-muted"><?= htmlspecialchars($site['ga_property_id']) ?></small>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">未連携</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($site['gsc_connected']): ?>
                                                <span class="badge bg-success">連携済み</span>
                                                <br><small class="text-muted"><?= htmlspecialchars(substr($site['gsc_property_url'], 0, 30)) ?>...</small>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">未連携</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= date('Y/m/d', strtotime($site['created_at'])) ?></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="<?= url('sites/analytics/' . $site['id']) ?>" 
                                                   class="btn btn-sm btn-outline-primary" title="連携設定">
                                                    <i class="bi bi-gear"></i> 設定
                                                </a>
                                                <a href="<?= url('analysis?site_id=' . $site['id']) ?>" 
                                                   class="btn btn-sm btn-outline-success" title="分析実行">
                                                    <i class="bi bi-search"></i> 分析
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Google Analytics について</h6>
                        </div>
                        <div class="card-body">
                            <p class="small">Google Analytics 4（GA4）との連携により、以下のデータを取得できます：</p>
                            <ul class="small">
                                <li>ページビュー数</li>
                                <li>ユーザー数</li>
                                <li>セッション数</li>
                                <li>直帰率</li>
                                <li>コンバージョン データ</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Google Search Console について</h6>
                        </div>
                        <div class="card-body">
                            <p class="small">Google Search Console（GSC）との連携により、以下のデータを取得できます：</p>
                            <ul class="small">
                                <li>検索クエリ</li>
                                <li>表示回数</li>
                                <li>クリック数</li>
                                <li>平均CTR</li>
                                <li>平均掲載順位</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="alert alert-info mt-4" role="alert">
                <h6 class="alert-heading">⚠️ 現在の実装状況</h6>
                <p class="mb-0">
                    現在、分析ツールとの実際のAPI連携は実装されていません。
                    設定情報は保存されますが、データの取得は今後のアップデートで対応予定です。
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>