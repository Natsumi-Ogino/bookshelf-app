<?php

return [
    'accepted' => ':attributeを承認してください。',
    'array' => ':attributeは配列で指定してください。',
    'between' => [
        'array' => ':attributeの項目数は:min個から:max個にしてください。',
        'numeric' => ':attributeは:minから:maxの間で指定してください。',
        'string' => ':attributeは:min文字から:max文字にしてください。',
    ],
    'confirmed' => ':attributeと確認用の入力が一致しません。',
    'date' => ':attributeには有効な日付を指定してください。',
    'date_format' => ':attributeは:format形式で指定してください。',
    'digits' => ':attributeは:digits桁で指定してください。',
    'email' => ':attributeには有効なメールアドレスを指定してください。',
    'exists' => '選択された:attributeは存在しません。',
    'image' => ':attributeには画像ファイルを指定してください。',
    'integer' => ':attributeは整数で指定してください。',
    'max' => [
        'array' => ':attributeの項目数は:max個以下にしてください。',
        'numeric' => ':attributeは:max以下で指定してください。',
        'string' => ':attributeは:max文字以下で入力してください。',
    ],
    'min' => [
        'array' => ':attributeの項目数は:min個以上にしてください。',
        'numeric' => ':attributeは:min以上で指定してください。',
        'string' => ':attributeは:min文字以上で入力してください。',
    ],
    'numeric' => ':attributeは数値で指定してください。',
    'required' => ':attributeは必須です。',
    'string' => ':attributeは文字列で入力してください。',
    'unique' => 'この:attributeはすでに使用されています。',
    'url' => ':attributeには有効なURLを指定してください。',

    'custom' => [
        'name' => [
            'required' => 'お名前を入力してください',
            'max' => '名前は255文字以内で入力してください。',
        ],
        'email' => [
            'required' => 'メールアドレスを入力してください',
            'email' => 'メールアドレスはメール形式で入力してください',
            'max' => 'メールアドレスは255文字以内で入力してください。',
            'unique' => 'そのメールアドレスは既に使用されています。',
        ],
        'password' => [
            'required' => 'パスワードを入力してください',
            'min' => 'パスワードは8文字以上で入力してください。',
            'confirmed' => 'パスワードと一致しません',
        ],
    ],

    'attributes' => [
        'name' => '氏名',
        'email' => 'メールアドレス',
        'password' => 'パスワード',
        'password_confirmation' => 'パスワード確認',
        'title' => 'タイトル',
        'author' => '著者',
        'isbn' => 'ISBN',
        'published_date' => '出版日',
        'description' => '説明',
        'image_url' => '画像URL',
        'genres' => 'ジャンル',
        'genres.*' => 'ジャンル',
        'rating' => '評価',
        'comment' => 'コメント',
    ],
];
