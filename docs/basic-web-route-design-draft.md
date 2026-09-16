# BookShelf Basic版 Web機能・ルート設計案

## この文書の位置付け

この文書は、要件シートのシート5「画面設計」、シート6「デザインUI」、シート7「機能要件」、提供されたBasic版Blade、2026年9月8日および9月15日のコーチ回答を照合した設計案です。

画面遷移、成功メッセージ、自分のレビューへのいいね禁止、レビューコメントの仕様は確認済みです。G20のバリデーションエラーメッセージは、コーチからの回答を得てから確定します。

## アクセス制御

| 区分 | 対象機能 |
|---|---|
| ゲスト閲覧可 | 書籍一覧、書籍詳細、ランキング |
| ログイン必須 | 書籍登録、お気に入り一覧・切り替え、レビュー投稿・いいね、ジャンル管理 |
| ログインかつ所有者本人 | 書籍の編集・更新・削除、レビューの編集・更新・削除 |
| Fortifyが担当 | 会員登録、ログイン、ログアウト |

存在しない書籍、レビュー、ジャンルは404とし、「お探しのページが見つかりません。」と表示します。書籍またはレビューに対して所有者以外が編集・更新・削除を試みた場合は、Policyによって403とし、「この操作を行う権限がありません。」と表示します。どちらのエラー画面にも書籍一覧画面（`/`）へ戻るリンクを設けます。

## ルート設計

### ゲストも利用できるルート

| HTTP | URI案 | ルート名 | Controller | 用途 |
|---|---|---|---|---|
| GET | `/` | `books.index` | `BookController@index` | 書籍一覧 |
| GET | `/books/{book}` | `books.show` | `BookController@show` | 書籍詳細 |
| GET | `/ranking` | `ranking.index` | `RankingController@index` | 評価ランキングTOP10 |

### ログインが必要なルート

| HTTP | URI案 | ルート名 | Controller | 追加条件 |
|---|---|---|---|---|
| GET | `/books/create` | `books.create` | `BookController@create` | なし |
| POST | `/books` | `books.store` | `BookController@store` | なし |
| GET | `/books/{book}/edit` | `books.edit` | `BookController@edit` | 書籍所有者 |
| PUT | `/books/{book}` | `books.update` | `BookController@update` | 書籍所有者 |
| DELETE | `/books/{book}` | `books.destroy` | `BookController@destroy` | 書籍所有者 |
| POST | `/books/{book}/reviews` | `reviews.store` | `ReviewController@store` | 同一ユーザー・同一書籍は1件まで |
| GET | `/reviews/{review}/edit` | `reviews.edit` | `ReviewController@edit` | レビュー投稿者 |
| PUT | `/reviews/{review}` | `reviews.update` | `ReviewController@update` | レビュー投稿者 |
| DELETE | `/reviews/{review}` | `reviews.destroy` | `ReviewController@destroy` | レビュー投稿者 |
| GET | `/favorites` | `favorites.index` | `FavoriteController@index` | なし |
| POST | `/books/{book}/favorite` | `favorites.toggle` | `FavoriteController@toggle` | 重複登録を防止 |
| POST | `/reviews/{review}/like` | `reviews.like` | `ReviewLikeController@toggle` | 重複登録を防止し、自分のレビューへのいいねを禁止 |
| GET | `/genres` | `genres.index` | `GenreController@index` | なし |
| GET | `/genres/create` | `genres.create` | `GenreController@create` | なし |
| POST | `/genres` | `genres.store` | `GenreController@store` | なし |
| GET | `/genres/{genre}` | `genres.show` | `GenreController@show` | なし |
| GET | `/genres/{genre}/edit` | `genres.edit` | `GenreController@edit` | なし |
| PUT | `/genres/{genre}` | `genres.update` | `GenreController@update` | なし |
| DELETE | `/genres/{genre}` | `genres.destroy` | `GenreController@destroy` | 紐付く書籍がある場合は削除拒否 |

すべてのログイン必須ルートには`auth`ミドルウェアを設定します。未ログイン時はFortifyのログイン画面へリダイレクトします。

## ControllerとBladeの対応

### BookController

| メソッド | Blade | 渡すデータ | 主な取得条件 |
|---|---|---|---|
| `index` | `books.index` | `$books` | ジャンルと平均評価を取得し、10件ずつページネーション |
| `show` | `books.show` | `$book` | ジャンル、レビュー投稿者、レビューへのいいねをまとめて取得 |
| `create` | `books.create` | `$genres` | 全ジャンルを取得 |
| `store` | なし | なし | 書籍を登録し、選択されたジャンルを紐付け |
| `edit` | `books.edit` | `$book`, `$genres` | Policyで所有者認可し、全ジャンルを取得 |
| `update` | なし | なし | Policyで所有者認可し、書籍とジャンル紐付けを更新 |
| `destroy` | なし | なし | Policyで所有者認可し、書籍を削除 |

`index`と`show`ではEager Loadingと集計機能を使い、N+1問題を防止します。

### ReviewController

| メソッド | Blade | 渡すデータ | 主な処理 |
|---|---|---|---|
| `store` | なし | なし | ログインユーザーのレビューを対象書籍へ投稿 |
| `edit` | `reviews.edit` | `$review` | Policyで投稿者認可して編集画面を表示 |
| `update` | なし | なし | Policyで投稿者認可して更新 |
| `destroy` | なし | なし | Policyで投稿者認可して削除 |

レビュー登録時はDBの複合ユニーク制約に加え、入力処理でも同一ユーザー・同一書籍への重複投稿を防止します。

### FavoriteController

| メソッド | Blade | 渡すデータ | 主な処理 |
|---|---|---|---|
| `index` | `favorites.index` | `$books` | ログインユーザーのお気に入り書籍を10件ずつ表示 |
| `toggle` | なし | なし | 対象書籍のお気に入りを追加または解除 |

一覧取得時は書籍のジャンルをEager Loadingします。

### ReviewLikeController

| メソッド | Blade | 渡すデータ | 主な処理 |
|---|---|---|---|
| `toggle` | なし | なし | 対象レビューへのいいねを追加または解除 |

自分が投稿したレビューにはいいねできないようにします。

### GenreController

| メソッド | Blade | 渡すデータ | 主な処理 |
|---|---|---|---|
| `index` | `genres.index` | `$genres` | 各ジャンルに紐付く書籍数を集計して表示 |
| `show` | `genres.show` | `$genre`, `$books` | 対象ジャンルの書籍をジャンル情報付きで10件ずつ表示 |
| `create` | `genres.create` | なし | 登録画面を表示 |
| `store` | なし | なし | ジャンルを登録 |
| `edit` | `genres.edit` | `$genre` | 編集画面を表示 |
| `update` | なし | なし | ジャンルを更新 |
| `destroy` | なし | なし | 書籍が紐付いていない場合だけ削除 |

ジャンル管理はログインユーザー全員が利用でき、所有者による区別はありません。

### RankingController

| メソッド | Blade | 渡すデータ | 主な処理 |
|---|---|---|---|
| `index` | `ranking.index` | `$rankedBooks` | レビューがある書籍だけを平均評価の降順、レビュー件数の降順、書籍IDの昇順で最大10冊取得 |

平均評価が同じ場合はレビュー件数の多い順、さらに同じ場合は書籍IDの昇順で並べます。

## FormRequestとPolicy

| 処理 | FormRequest | Policy |
|---|---|---|
| 書籍登録 | `StoreBookRequest` | 認証ミドルウェア |
| 書籍更新 | `UpdateBookRequest` | `BookPolicy@update` |
| 書籍削除 | なし | `BookPolicy@delete` |
| レビュー投稿 | `StoreReviewRequest`（実装時に作成） | 認証ミドルウェア |
| レビュー更新 | `UpdateReviewRequest`（実装時に作成） | `ReviewPolicy@update` |
| レビュー削除 | なし | `ReviewPolicy@delete` |
| ジャンル登録 | `StoreGenreRequest` | 認証ミドルウェア |
| ジャンル更新 | `UpdateGenreRequest` | 認証ミドルウェア |
| ジャンル削除 | なし | 認証ミドルウェア |

## 操作後の遷移先と成功メッセージ

| 操作 | 遷移先 | 成功メッセージ |
|---|---|---|
| 会員登録 | 書籍一覧画面（`/`） | 会員登録が完了しました。 |
| ログイン | 認証が必要な画面から移動した場合は元の画面、それ以外は書籍一覧画面（`/`） | ログインしました。 |
| ログアウト | 書籍一覧画面（`/`） | ログアウトしました。 |
| 書籍登録 | 登録した書籍の詳細画面 | 書籍を登録しました。 |
| 書籍更新 | 更新した書籍の詳細画面 | 書籍を更新しました。 |
| 書籍削除 | 書籍一覧画面（`/`） | 書籍を削除しました。 |
| レビュー投稿 | 対象書籍の詳細画面 | レビューを投稿しました。 |
| レビュー更新 | 対象書籍の詳細画面 | レビューを更新しました。 |
| レビュー削除 | 対象書籍の詳細画面 | レビューを削除しました。 |
| お気に入り追加 | 操作元の画面 | お気に入りに追加しました。 |
| お気に入り解除 | 操作元の画面 | お気に入りを解除しました。 |
| レビューへのいいね追加 | 対象書籍の詳細画面 | レビューにいいねしました。 |
| レビューへのいいね解除 | 対象書籍の詳細画面 | レビューのいいねを解除しました。 |
| ジャンル登録 | ジャンル一覧画面 | ジャンルを登録しました。 |
| ジャンル更新 | ジャンル一覧画面 | ジャンルを更新しました。 |
| ジャンル削除 | ジャンル一覧画面 | ジャンルを削除しました。 |

書籍が紐付いているジャンルの削除を試みた場合は削除せず、ジャンル一覧画面へ戻して「書籍が紐づいているため、このジャンルは削除できません。」と表示します。

同じ書籍へ2件目のレビューを投稿しようとした場合は、対象書籍の詳細画面へ戻して「この書籍には既にレビューを投稿しています。」と表示します。

バリデーションエラー時は、パスワードとパスワード確認を除く入力内容とエラーメッセージを保持し、入力元の画面へ戻します。個別のバリデーションエラーメッセージはG20の回答後に確定します。

## Basic版完成時の確認

- ゲストが書籍一覧、書籍詳細、ランキングを閲覧できる。
- 未ログインで認証必須機能へアクセスするとログイン画面へ移動する。
- 書籍とレビューの所有者以外による変更操作は403になる。
- 存在しない書籍、レビュー、ジャンルは404になる。
- 一覧画面は要件どおり10件ずつ表示される。
- 関連データをEager Loadingし、一覧と詳細でN+1問題が発生しない。
- FormRequest、Controller、Blade、DB制約、Featureテストのルールが一致している。
