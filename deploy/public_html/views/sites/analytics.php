<div class="row justify-content-center">
    <div class="col-md-10 col-lg-8">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/sites">サイト管理</a></li>
                <li class="breadcrumb-item active">分析ツール連携設定</li>
            </ol>
        </nav>
        
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">分析ツール連携設定: <?= htmlspecialchars($site['name']) ?></h4>
                <small class="text-muted"><?= htmlspecialchars($site['domain']) ?></small>
            </div>
            <div class="card-body">
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success" role="alert">
                        <?= $_SESSION['success'] ?>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger" role="alert">
                        <?= $_SESSION['error'] ?>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">Google Analytics</h6>
                                    <span class="badge bg-<?= $site['ga_connected'] ? 'success' : 'secondary' ?>">
                                        <?= $site['ga_connected'] ? '連携済み' : '未連携' ?>
                                    </span>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="ga_property_id" class="form-label">プロパティID</label>
                                        <input type="text" class="form-control" id="ga_property_id" name="ga_property_id"
                                               placeholder="例: 123456789"
                                               value="<?= htmlspecialchars($site['ga_property_id'] ?? '') ?>">
                                        <div class="form-text">Google Analytics 4のプロパティIDを入力してください</div>
                                    </div>
                                    
                                    <div class="alert alert-info" role="alert">
                                        <h6 class="alert-heading">プロパティIDの確認方法</h6>
                                        <ol class="mb-0">
                                            <li>Google Analytics にログイン</li>
                                            <li>対象プロパティを選択</li>
                                            <li>管理 → プロパティ設定</li>
                                            <li>「プロパティ ID」を確認</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">Google Search Console</h6>
                                    <span class="badge bg-<?= $site['gsc_connected'] ? 'success' : 'secondary' ?>">
                                        <?= $site['gsc_connected'] ? '連携済み' : '未連携' ?>
                                    </span>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="gsc_property_url" class="form-label">プロパティURL</label>
                                        <input type="text" class="form-control" id="gsc_property_url" name="gsc_property_url"
                                               placeholder="例: https://example.com/"
                                               value="<?= htmlspecialchars($site['gsc_property_url'] ?? '') ?>">
                                        <div class="form-text">Search Consoleに登録されているプロパティのURLを入力してください</div>
                                    </div>
                                    
                                    <div class="alert alert-info" role="alert">
                                        <h6 class="alert-heading">プロパティURLの確認方法</h6>
                                        <ol class="mb-0">
                                            <li>Google Search Console にログイン</li>
                                            <li>対象プロパティを選択</li>
                                            <li>左上のプロパティ名を確認</li>
                                            <li>完全なURLを入力</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <div class="alert alert-warning" role="alert">
                            <h6 class="alert-heading">⚠️ 重要な注意事項</h6>
                            <ul class="mb-0">
                                <li>現在、実際のAPI連携は実装されていません。設定は保存されますが、データの取得は行われません。</li>
                                <li>将来のアップデートで、OAuth認証を通じた安全な連携機能が追加予定です。</li>
                                <li>入力された情報は暗号化されて安全に保存されます。</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="/sites" class="btn btn-secondary me-md-2">戻る</a>
                        <button type="submit" class="btn btn-primary">設定を保存</button>
                    </div>
                </form>
            </div>
        </div>
        
        <?php if ($site['ga_connected'] || $site['gsc_connected']): ?>
            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="mb-0">連携状況の詳細</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Google Analytics</h6>
                            <?php if ($site['ga_connected']): ?>
                                <p class="text-success">✓ プロパティID: <?= htmlspecialchars($site['ga_property_id']) ?></p>
                                <p class="small text-muted">最終更新: <?= date('Y/m/d H:i', strtotime($site['updated_at'])) ?></p>
                            <?php else: ?>
                                <p class="text-muted">未設定</p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <h6>Google Search Console</h6>
                            <?php if ($site['gsc_connected']): ?>
                                <p class="text-success">✓ プロパティURL: <?= htmlspecialchars($site['gsc_property_url']) ?></p>
                                <p class="small text-muted">最終更新: <?= date('Y/m/d H:i', strtotime($site['updated_at'])) ?></p>
                            <?php else: ?>
                                <p class="text-muted">未設定</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>