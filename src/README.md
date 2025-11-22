# 勤怠管理システム

## 環境構築

### Docker ビルド

1. リポジトリをクローン

```bash
git clone https://github.com/yusuke-yano-01/Timelog.git
cd Timelog
```

2. Docker サービスを起動

```bash
docker-compose up -d --build
```

> **注意**: MySQL は、OS によって起動しない場合があるのでそれぞれの PC に合わせて `docker-compose.yml` ファイルを編集してください。

### Laravel 環境構築

1. PHP コンテナにアクセス

```bash
docker-compose exec php bash
```

2. 依存関係をインストール

```bash
composer install
```

3. `.env.example`ファイルから`.env`を作成し、環境変数を変更

```bash
cp .env.example .env
```

4. アプリケーションキーを生成

```bash
php artisan key:generate
```

5. データベースマイグレーション実行

```bash
php artisan migrate
```

6. データベースシーディング実行

```bash
php artisan db:seed
```

## 使用技術(実行環境)

-   PHP 8.1
-   Laravel 8.75
-   MySQL 8.0.26
-   Nginx 1.21.1
-   Docker & Docker Compose

## データベース構成

### テーブル一覧

-   `actors` - アクター（管理者、従業員など）
-   `users` - ユーザー情報
-   `times` - 勤怠記録
-   `breaktimes` - 休憩時間
-   `applications` - 修正申請
-   `application_breaktimes` - 申請の休憩時間

### テーブル詳細

#### actors テーブル

| カラム名   | 型        | 説明               |
| ---------- | --------- | ------------------ |
| id         | bigint    | 主キー（自動増分） |
| name       | string    | アクター名         |
| created_at | timestamp | 作成日時           |
| updated_at | timestamp | 更新日時           |

#### users テーブル

| カラム名          | 型        | 説明                               |
| ----------------- | --------- | ---------------------------------- |
| id                | bigint    | 主キー（自動増分）                 |
| actor_id          | bigint    | アクター ID（外部キー：actors.id） |
| name              | string    | ユーザー名                         |
| registeredflg     | boolean   | 登録フラグ（デフォルト：false）    |
| break_flg         | boolean   | 休憩フラグ（デフォルト：false）    |
| email             | string    | メールアドレス（ユニーク）         |
| password          | string    | パスワード（ハッシュ化）           |
| email_verified_at | timestamp | メール認証日時（nullable）         |
| remember_token    | string    | リメンバートークン                 |
| created_at        | timestamp | 作成日時                           |
| updated_at        | timestamp | 更新日時                           |

#### times テーブル

| カラム名       | 型        | 説明                              |
| -------------- | --------- | --------------------------------- |
| id             | bigint    | 主キー（自動増分）                |
| user_id        | bigint    | ユーザー ID（外部キー：users.id） |
| date           | date      | 日付                              |
| arrival_time   | string    | 出勤時間（nullable）              |
| departure_time | string    | 退勤時間（nullable）              |
| note           | string    | 備考（nullable）                  |
| created_at     | timestamp | 作成日時                          |
| updated_at     | timestamp | 更新日時                          |

#### breaktimes テーブル

| カラム名         | 型        | 説明                                              |
| ---------------- | --------- | ------------------------------------------------- |
| id               | bigint    | 主キー（自動増分）                                |
| time_id          | bigint    | 勤怠記録 ID（外部キー：times.id、カスケード削除） |
| start_break_time | string    | 休憩開始時間                                      |
| end_break_time   | string    | 休憩終了時間（nullable）                          |
| created_at       | timestamp | 作成日時                                          |
| updated_at       | timestamp | 更新日時                                          |

#### applications テーブル

| カラム名        | 型        | 説明                                   |
| --------------- | --------- | -------------------------------------- |
| id              | bigint    | 主キー（自動増分）                     |
| user_id         | bigint    | ユーザー ID（外部キー：users.id）      |
| time_id         | bigint    | 勤怠記録 ID（外部キー：times.id）      |
| date            | date      | 日付                                   |
| arrival_time    | string    | 出勤時間                               |
| departure_time  | string    | 退勤時間                               |
| note            | string    | 備考（nullable）                       |
| application_flg | integer   | 申請フラグ（1：承認待ち、0：承認済み） |
| created_at      | timestamp | 作成日時                               |
| updated_at      | timestamp | 更新日時                               |

#### application_breaktimes テーブル

| カラム名         | 型        | 説明                                                 |
| ---------------- | --------- | ---------------------------------------------------- |
| id               | bigint    | 主キー（自動増分）                                   |
| application_id   | bigint    | 申請 ID（外部キー：applications.id、カスケード削除） |
| start_break_time | string    | 休憩開始時間                                         |
| end_break_time   | string    | 休憩終了時間                                         |
| created_at       | timestamp | 作成日時                                             |
| updated_at       | timestamp | 更新日時                                             |

### リレーション

-   `actors` 1 対多 `users`（1 つのアクターに複数のユーザーが属する）
-   `users` 1 対多 `times`（1 人のユーザーに複数の勤怠記録が属する）
-   `times` 1 対多 `breaktimes`（1 つの勤怠記録に複数の休憩時間が属する）
-   `users` 1 対多 `applications`（1 人のユーザーに複数の申請が属する）
-   `times` 1 対多 `applications`（1 つの勤怠記録に複数の申請が属する）
-   `applications` 1 対多 `application_breaktimes`（1 つの申請に複数の休憩時間が属する）

## ER 図

<--- 作成した ER 図の画像 --- >

## URL

-   開発環境: http://localhost/
-   phpMyAdmin: http://localhost:8080/
-   MailHog Web UI: http://localhost:8025/
