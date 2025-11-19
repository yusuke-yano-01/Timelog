# PHPUnit テスト実行ガイド

## 基本的なテスト実行方法

### Docker 環境を使用する場合（推奨）

このプロジェクトは Docker 環境で動作しているため、コンテナ内でテストを実行することを推奨します。

#### 1. すべてのテストを実行

```bash
docker-compose exec php php artisan test
```

または

```bash
docker-compose exec php ./vendor/bin/phpunit
```

#### 2. 特定のテストクラスを実行

```bash
docker-compose exec php php artisan test --filter RegistrationValidationTest
```

#### 3. 特定のテストメソッドを実行

```bash
docker-compose exec php php artisan test --filter test_name_validation_when_name_is_empty
```

#### 4. Feature テストのみ実行

```bash
docker-compose exec php php artisan test tests/Feature
```

#### 5. Unit テストのみ実行

```bash
docker-compose exec php php artisan test tests/Unit
```

#### コンテナに入って実行する場合

```bash
# コンテナに入る
docker-compose exec php bash

# コンテナ内でテストを実行
php artisan test --filter RegistrationValidationTest
```

### ローカル環境で実行する場合

ローカルに PHP 環境が整っている場合：

#### 1. すべてのテストを実行

```bash
cd src
php artisan test
```

または

```bash
cd src
./vendor/bin/phpunit
```

#### 2. 特定のテストクラスを実行

```bash
cd src
php artisan test --filter RegistrationValidationTest
```

または

```bash
cd src
./vendor/bin/phpunit tests/Feature/RegistrationValidationTest.php
```

#### 3. 特定のテストメソッドを実行

```bash
cd src
php artisan test --filter test_name_validation_when_name_is_empty
```

## テスト結果の見方

### 成功した場合

```
 PASS  Tests\Feature\RegistrationValidationTest
 ✓ test name validation when name is empty

Tests:  1 passed
Time:   0.05s
```

### 失敗した場合

```
 FAIL  Tests\Feature\RegistrationValidationTest
 ✗ test name validation when name is empty
 Failed asserting that the session has errors for key "name".

Tests:  1 failed
Time:   0.05s
```

## よく使うオプション

### 詳細な出力を表示（verbose）

```bash
docker-compose exec php php artisan test --filter RegistrationValidationTest -v
```

### カバレッジレポートを生成（Xdebug が必要）

```bash
docker-compose exec php ./vendor/bin/phpunit --coverage-html coverage
```

### 特定のテストスイートを実行

```bash
docker-compose exec php php artisan test --testsuite Feature
```

## テストのデバッグ

### テスト実行中に変数を確認

テストコード内で `dd()` や `dump()` を使用：

```php
$response = $this->post('/auth/register', $data);
dd($response->getSession()->all()); // セッションの内容を確認
```

### データベースの状態を確認

```php
$this->assertDatabaseHas('users', [
    'email' => 'test@example.com'
]);
```

### レスポンスの内容を確認

```php
$response->dump(); // レスポンスの内容を表示
$response->assertStatus(302); // リダイレクトを確認
```

## 実際のテスト実行例

### 作成したテストケースを実行

```bash
# Docker環境の場合
docker-compose exec php php artisan test --filter RegistrationValidationTest

# 実行結果の例
# PASS  Tests\Feature\RegistrationValidationTest
# ✓ test name validation when name is empty
#
# Tests:  1 passed
# Time:   0.05s
```

## 注意事項

-   テストは `phpunit.xml` の設定に従って実行されます
-   `RefreshDatabase` トレイトを使用している場合、テストごとにデータベースがリセットされます
-   テスト環境では `.env.testing` または `phpunit.xml` の設定が使用されます
-   Docker 環境を使用する場合、コンテナが起動していることを確認してください（`docker-compose up -d`）

## トラブルシューティング

### エラー: "mbstring extension is not available"

→ Docker コンテナ内で実行してください

### エラー: "Database connection failed"

→ テスト用のデータベース設定を確認してください（`phpunit.xml`の設定）

### テストが遅い

→ `RefreshDatabase` の代わりに `DatabaseTransactions` を使用することを検討してください
