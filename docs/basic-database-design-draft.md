# BookShelf Basic版 データベース設計案

## この文書の位置付け

この文書は、要件シートのシート11「データ要件」、シート12「テーブル仕様書」、2026年9月8日のコーチ回答を基にした面談前の下書きです。

2026年9月15日の面談で確認する項目は「確認待ち」と記載しています。確認待ちの内容は、回答を得るまで確定仕様として実装しません。

## 確定済みの設計方針

- 1ユーザーが同じ書籍へ投稿できるレビューは1件までとする。
- `reviews`の`user_id`と`book_id`には複合ユニーク制約を設定する。
- `book_genre`は`id`とtimestampsを持たせず、`book_id`と`genre_id`を複合主キーとする。
- `favorites`と`review_likes`は`id`を主キーとして持たせる。
- ユーザー、書籍、レビューを削除した場合、従属する関連データはcascadeで削除する。
- 書籍が紐付いているジャンルは削除できないようにする。
- `books.image_url`はNULLを許可し、最大長は2048文字とする。
- Basic版の`users`にはFortifyの二要素認証用3カラムを保持する。

## 確認待ちの設計項目

1. `books`の各カラム型が、次の案で問題ないか。
2. `favorites`と`review_likes`に複合ユニーク制約とtimestampsを持たせるか。
3. `reviews.comment`を任意入力、最大1000文字、NULL許可とするか。

## ER図（面談前の下書き）

```mermaid
erDiagram
    USERS ||--o{ BOOKS : registers
    USERS ||--o{ REVIEWS : posts
    USERS ||--o{ FAVORITES : creates
    USERS ||--o{ REVIEW_LIKES : creates
    BOOKS ||--o{ REVIEWS : receives
    BOOKS ||--o{ BOOK_GENRE : classified_as
    GENRES ||--o{ BOOK_GENRE : contains
    BOOKS ||--o{ FAVORITES : favorited_in
    REVIEWS ||--o{ REVIEW_LIKES : liked_in

    USERS {
        bigint_unsigned id PK
        varchar_255 name
        varchar_255 email UK
        timestamp email_verified_at "NULL可"
        varchar_255 password
        varchar_100 remember_token "NULL可"
        text two_factor_secret "NULL可"
        text two_factor_recovery_codes "NULL可"
        timestamp two_factor_confirmed_at "NULL可"
        timestamp created_at "NULL可"
        timestamp updated_at "NULL可"
    }

    BOOKS {
        bigint_unsigned id PK
        bigint_unsigned user_id FK
        varchar_255 title
        varchar_255 author
        char_13 isbn UK
        date published_date
        text description "NULL可"
        varchar_2048 image_url "NULL可"
        timestamp created_at "NULL可"
        timestamp updated_at "NULL可"
    }

    GENRES {
        bigint_unsigned id PK
        varchar_255 name UK
        timestamp created_at "NULL可"
        timestamp updated_at "NULL可"
    }

    BOOK_GENRE {
        bigint_unsigned book_id PK,FK
        bigint_unsigned genre_id PK,FK
    }

    REVIEWS {
        bigint_unsigned id PK
        bigint_unsigned user_id FK
        bigint_unsigned book_id FK
        tinyint_unsigned rating
        text comment "確認待ち・NULL可案"
        timestamp created_at "NULL可"
        timestamp updated_at "NULL可"
    }

    FAVORITES {
        bigint_unsigned id PK
        bigint_unsigned user_id FK
        bigint_unsigned book_id FK
        timestamp created_at "確認待ち"
        timestamp updated_at "確認待ち"
    }

    REVIEW_LIKES {
        bigint_unsigned id PK
        bigint_unsigned user_id FK
        bigint_unsigned review_id FK
        timestamp created_at "確認待ち"
        timestamp updated_at "確認待ち"
    }
```

`books`の型、`reviews.comment`のNULL可否、`favorites`と`review_likes`のtimestampsは、2026年9月15日の回答によって確定します。

## テーブル仕様案

### users

| カラム | 型 | NULL | 制約・補足 |
|---|---|---|---|
| id | BIGINT UNSIGNED | 不可 | 主キー |
| name | VARCHAR(255) | 不可 |  |
| email | VARCHAR(255) | 不可 | ユニーク |
| email_verified_at | TIMESTAMP | 可 |  |
| password | VARCHAR(255) | 不可 | ハッシュ化して保存 |
| remember_token | VARCHAR(100) | 可 |  |
| two_factor_secret | TEXT | 可 | Fortify用 |
| two_factor_recovery_codes | TEXT | 可 | Fortify用 |
| two_factor_confirmed_at | TIMESTAMP | 可 | Fortify用 |
| created_at | TIMESTAMP | 可 | Laravel timestamps |
| updated_at | TIMESTAMP | 可 | Laravel timestamps |

### books（型は2026年9月15日に確認）

| カラム | 型の案 | NULL | 制約・補足 |
|---|---|---|---|
| id | BIGINT UNSIGNED | 不可 | 主キー |
| user_id | BIGINT UNSIGNED | 不可 | `users.id`への外部キー、ユーザー削除時cascade |
| title | VARCHAR(255) | 不可 |  |
| author | VARCHAR(255) | 不可 |  |
| isbn | CHAR(13) | 不可 | ユニーク |
| published_date | DATE | 不可 | Advanced版ではNULL許可へ変更予定 |
| description | TEXT | 可 |  |
| image_url | VARCHAR(2048) | 可 | 最大長は承認済み |
| created_at | TIMESTAMP | 可 | Laravel timestamps |
| updated_at | TIMESTAMP | 可 | Laravel timestamps |

### genres

| カラム | 型 | NULL | 制約・補足 |
|---|---|---|---|
| id | BIGINT UNSIGNED | 不可 | 主キー |
| name | VARCHAR(255) | 不可 | ユニーク |
| created_at | TIMESTAMP | 可 | Laravel timestamps |
| updated_at | TIMESTAMP | 可 | Laravel timestamps |

書籍が紐付いているジャンルの削除は拒否し、日本語エラーメッセージを表示してジャンル一覧へ戻します。

### book_genre

| カラム | 型 | NULL | 制約・補足 |
|---|---|---|---|
| book_id | BIGINT UNSIGNED | 不可 | 複合主キー、`books.id`への外部キー、書籍削除時cascade |
| genre_id | BIGINT UNSIGNED | 不可 | 複合主キー、`genres.id`への外部キー、ジャンル削除時restrict |

`id`、`created_at`、`updated_at`は持たせません。

### reviews

| カラム | 型 | NULL | 制約・補足 |
|---|---|---|---|
| id | BIGINT UNSIGNED | 不可 | 主キー |
| user_id | BIGINT UNSIGNED | 不可 | `users.id`への外部キー、ユーザー削除時cascade |
| book_id | BIGINT UNSIGNED | 不可 | `books.id`への外部キー、書籍削除時cascade |
| rating | TINYINT UNSIGNED | 不可 | 1から5まで |
| comment | TEXT | 確認待ち | 任意入力、最大1000文字、NULL許可の案 |
| created_at | TIMESTAMP | 可 | Laravel timestamps |
| updated_at | TIMESTAMP | 可 | Laravel timestamps |

`user_id`と`book_id`の組み合わせに複合ユニーク制約を設定します。

### favorites

| カラム | 型 | NULL | 制約・補足 |
|---|---|---|---|
| id | BIGINT UNSIGNED | 不可 | 主キー、保持する方針は承認済み |
| user_id | BIGINT UNSIGNED | 不可 | `users.id`への外部キー、ユーザー削除時cascade |
| book_id | BIGINT UNSIGNED | 不可 | `books.id`への外部キー、書籍削除時cascade |
| created_at | TIMESTAMP | 確認待ち | 保持する案 |
| updated_at | TIMESTAMP | 確認待ち | 保持する案 |

`user_id`と`book_id`への複合ユニーク制約は確認待ちです。

### review_likes

| カラム | 型 | NULL | 制約・補足 |
|---|---|---|---|
| id | BIGINT UNSIGNED | 不可 | 主キー、保持する方針は承認済み |
| user_id | BIGINT UNSIGNED | 不可 | `users.id`への外部キー、ユーザー削除時cascade |
| review_id | BIGINT UNSIGNED | 不可 | `reviews.id`への外部キー、レビュー削除時cascade |
| created_at | TIMESTAMP | 確認待ち | 保持する案 |
| updated_at | TIMESTAMP | 確認待ち | 保持する案 |

`user_id`と`review_id`への複合ユニーク制約は確認待ちです。

## Laravel標準認証補助テーブル

次のテーブルはLaravel標準Migrationの定義を維持し、アプリ独自のカラムは追加しません。

### password_reset_tokens

| カラム | 型 | NULL | 制約・補足 |
|---|---|---|---|
| email | VARCHAR(255) | 不可 | 主キー |
| token | VARCHAR(255) | 不可 |  |
| created_at | TIMESTAMP | 可 |  |

### personal_access_tokens

| カラム | 型 | NULL | 制約・補足 |
|---|---|---|---|
| id | BIGINT UNSIGNED | 不可 | 主キー |
| tokenable_type | VARCHAR(255) | 不可 | ポリモーフィック関連 |
| tokenable_id | BIGINT UNSIGNED | 不可 | ポリモーフィック関連 |
| name | VARCHAR(255) | 不可 |  |
| token | VARCHAR(64) | 不可 | ユニーク |
| abilities | TEXT | 可 |  |
| last_used_at | TIMESTAMP | 可 |  |
| expires_at | TIMESTAMP | 可 |  |
| created_at | TIMESTAMP | 可 | Laravel timestamps |
| updated_at | TIMESTAMP | 可 | Laravel timestamps |

`tokenable_type`と`tokenable_id`には複合インデックスが設定されます。

### failed_jobs

| カラム | 型 | NULL | 制約・補足 |
|---|---|---|---|
| id | BIGINT UNSIGNED | 不可 | 主キー |
| uuid | VARCHAR(255) | 不可 | ユニーク |
| connection | TEXT | 不可 |  |
| queue | TEXT | 不可 |  |
| payload | LONGTEXT | 不可 |  |
| exception | LONGTEXT | 不可 |  |
| failed_at | TIMESTAMP | 不可 | 登録時に現在日時を設定 |

## 削除時の挙動

| 削除対象 | 関連データの扱い |
|---|---|
| ユーザー | 登録書籍、投稿レビュー、お気に入り、レビューへのいいねをcascade削除 |
| 書籍 | レビュー、ジャンル紐付け、お気に入りをcascade削除。レビュー削除に伴いレビューへのいいねもcascade削除 |
| レビュー | レビューへのいいねをcascade削除 |
| ジャンル | 書籍が紐付いている場合は削除を拒否。紐付いていない場合だけ削除可能 |

## 2026年9月15日の回答後に更新する場所

| 回答内容 | 更新対象 |
|---|---|
| `books`の型 | `books`のMigration、この文書のER図とテーブル仕様 |
| `reviews.comment`の仕様 | `reviews`のMigration、Review用FormRequest、Blade、テスト、この文書 |
| `favorites`の制約とtimestamps | `favorites`のMigration、User・Bookモデル、Seeder、テスト、この文書 |
| `review_likes`の制約とtimestamps | `review_likes`のMigration、User・Reviewモデル、Seeder、テスト、この文書 |
