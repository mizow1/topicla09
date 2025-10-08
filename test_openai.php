<?php
// OpenAI API接続テスト

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';

echo "<!DOCTYPE html><html><head><title>OpenAI API Test</title></head><body>";
echo "<h1>OpenAI API 接続テスト</h1>";

echo "<h2>設定確認</h2>";
echo "<p>OPENAI_MODEL: " . htmlspecialchars(OPENAI_MODEL) . "</p>";
echo "<p>OPENAI_API_KEY: " . (empty(OPENAI_API_KEY) ? 'NOT SET' : substr(OPENAI_API_KEY, 0, 10) . '...') . "</p>";

echo "<h2>API接続テスト</h2>";

try {
    require_once 'includes/openai_client.php';

    echo "<p>OpenAIClient クラス読み込み成功</p>";

    $client = new OpenAIClient();
    echo "<p>OpenAIClient インスタンス作成成功</p>";

    // 簡単なテストプロンプト
    $testPrompt = "こんにちは。1+1は？短く答えてください。";

    echo "<p>テストプロンプト送信中...</p>";

    // callOpenAIAPIメソッドを直接呼ぶためにリフレクションを使用
    $reflectionClass = new ReflectionClass($client);
    $method = $reflectionClass->getMethod('callOpenAIAPI');
    $method->setAccessible(true);

    $response = $method->invoke($client, $testPrompt);

    echo "<p>✅ API接続成功！</p>";
    echo "<p>レスポンス: " . htmlspecialchars($response) . "</p>";

} catch (Exception $e) {
    echo "<p style='color:red'>❌ エラー: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "</body></html>";
?>
