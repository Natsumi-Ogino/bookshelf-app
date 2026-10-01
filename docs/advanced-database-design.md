# BookShelf Advanced版 データベース設計

## 設計方針

- 主キーはLaravel標準の`id`を使用する。
- 外部キーには参照整合性制約を設定する。
- 親データの削除時に不要になる関連データはカスケード削除する。
- 書籍とジャンルの関連は複合主キーで重複を防止する。
- お気に入り、レビュー、いいねは複合ユニーク制約で重複を防止する。
- APIトークンと通知にはLaravel標準のテーブル構造を使用する。

## テーブル一覧

### users

| カラム | 型 | NULL | 制約・用途 |
|---|---|---|---|
| id | bigint unsigned | 不可 | 主キー |
| name | varchar(255) | 不可 | ユーザー名 |
| email | varchar(255) | 不可 | UNIQUE |
| email_verified_at | timestamp | 可 | メール確認日時 |
| password | varchar(255) | 不可 | ハッシュ化済みパスワード |
| two_factor_secret | text | 可 | Fortify標準 |
| two_factor_recovery_codes | text | 可 | Fortify標準 |
| two_factor_confirmed_at | timestamp | 可 | Fortify標準 |
| remember_token | varchar(100) | 可 | ログイン維持用 |
| created_at / updated_at | timestamp | 可 | Laravel標準日時 |

### books

| カラム | 型 | NULL | 制約・用途 |
|---|---|---|---|
| id | bigint unsigned | 不可 | 主キー |
| user_id | bigint unsigned | 不可 | users.id、削除時CASCADE |
| title | varchar(255) | 不可 | 書籍名 |
| author | varchar(255) | 不可 | 著者名 |
| isbn | varchar(13) | 可 | UNIQUE、ISBN-13 |
| published_date | date | 可 | 出版日 |
| description | text | 可 | 説明 |
| image_url | varchar(2048) | 可 | 画像URL |
| created_at / updated_at | timestamp | 可 | Laravel標準日時 |

### genres

| カラム | 型 | NULL | 制約・用途 |
|---|---|---|---|
| id | bigint unsigned | 不可 | 主キー |
| name | varchar(255) | 不可 | UNIQUE |
| created_at / updated_at | timestamp | 可 | Laravel標準日時 |

### book_genre

| カラム | 型 | NULL | 制約・用途 |
|---|---|---|---|
| book_id | bigint unsigned | 不可 | books.id、削除時CASCADE、複合主キー |
| genre_id | bigint unsigned | 不可 | genres.id、削除時RESTRICT、複合主キー |

### reviews

| カラム | 型 | NULL | 制約・用途 |
|---|---|---|---|
| id | bigint unsigned | 不可 | 主キー |
| user_id | bigint unsigned | 不可 | users.id、削除時CASCADE |
| book_id | bigint unsigned | 不可 | books.id、削除時CASCADE |
| rating | tinyint unsigned | 不可 | 1から5 |
| comment | text | 不可 | レビュー本文 |
| created_at / updated_at | timestamp | 可 | Laravel標準日時 |

`user_id`と`book_id`の組み合わせにUNIQUE制約を設定する。

### favorites

| カラム | 型 | NULL | 制約・用途 |
|---|---|---|---|
| id | bigint unsigned | 不可 | 主キー |
| user_id | bigint unsigned | 不可 | users.id、削除時CASCADE |
| book_id | bigint unsigned | 不可 | books.id、削除時CASCADE |

`user_id`と`book_id`の組み合わせにUNIQUE制約を設定する。

### review_likes

| カラム | 型 | NULL | 制約・用途 |
|---|---|---|---|
| id | bigint unsigned | 不可 | 主キー |
| user_id | bigint unsigned | 不可 | users.id、削除時CASCADE |
| review_id | bigint unsigned | 不可 | reviews.id、削除時CASCADE |

`user_id`と`review_id`の組み合わせにUNIQUE制約を設定する。

### reading_plans

| カラム | 型 | NULL | 制約・用途 |
|---|---|---|---|
| id | bigint unsigned | 不可 | 主キー |
| user_id | bigint unsigned | 不可 | users.id、削除時CASCADE |
| book_id | bigint unsigned | 不可 | books.id、削除時CASCADE |
| target_date | date | 不可 | 読書目標日 |
| status | varchar(255) | 不可 | in_progress、completed、expired |
| completed_at | timestamp | 可 | 読了日時 |
| created_at / updated_at | timestamp | 可 | Laravel標準日時 |

`user_id, status, target_date`と`user_id, book_id, status`に検索用インデックスを設定する。

### notifications

| カラム | 型 | NULL | 制約・用途 |
|---|---|---|---|
| id | char(36) | 不可 | UUID主キー |
| type | varchar(255) | 不可 | Notificationクラス名 |
| notifiable_type | varchar(255) | 不可 | 通知先モデル種別 |
| notifiable_id | bigint unsigned | 不可 | 通知先ID |
| data | text | 不可 | 通知内容のJSON |
| read_at | timestamp | 可 | 既読日時 |
| created_at / updated_at | timestamp | 可 | Laravel標準日時 |

`notifiable_type`と`notifiable_id`に複合インデックスを設定する。

### personal_access_tokens

| カラム | 型 | NULL | 制約・用途 |
|---|---|---|---|
| id | bigint unsigned | 不可 | 主キー |
| tokenable_type | varchar(255) | 不可 | トークン所有モデル種別 |
| tokenable_id | bigint unsigned | 不可 | トークン所有者ID |
| name | varchar(255) | 不可 | 端末名 |
| token | varchar(64) | 不可 | UNIQUE、ハッシュ化済みトークン |
| abilities | text | 可 | 権限 |
| last_used_at | timestamp | 可 | 最終使用日時 |
| expires_at | timestamp | 可 | 有効期限 |
| created_at / updated_at | timestamp | 可 | Laravel標準日時 |

## Laravel標準補助テーブル

- `password_reset_tokens`
- `failed_jobs`

これらはパスワード再設定と失敗ジョブの管理に使用する。
