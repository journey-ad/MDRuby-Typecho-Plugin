Parsedown 青空文庫ルビ形式extension
====
`\Noi\Parsedown\AozoraRubyTextTrait`は、[Parsedown](http://parsedown.org)
を継承したクラスにルビ指定用の拡張記法として「青空文庫ルビ形式」を導入します。

青空文庫ルビ形式は、[青空文庫](http://www.aozora.gr.jp)が採用しているルビ指定の書式です。
この書式は視覚障碍者読書支援協会(BBA)の原文入力ルールが元になっています。


Markdown
----

  1. `親文字《ルビ》`   -- 青空文庫ルビ基本形式
  2. `｜親文字《ルビ》` -- ルビ範囲指定形式（"`｜`"は全角）

これらは次の`<ruby>`タグを生成します:

```html
<ruby>親文字<rp>（</rp><rt>ルビ</rt><rp>）</rp></ruby>
```

親文字の前の "`｜`" は、ルビを振る範囲が文字種で特定できるなら省略可能です:

  * `これは青空文庫《あおぞらぶんこ》のルビ形式《けいしき》`
  * `簡単にMarkdown《マークダウン》で使用可能`

一方、同一文字種の途中までルビを振りたい場合や、複数文字種や空白を含めて指定したい場合は、
以下のように "`｜`" を親文字の前に置いてルビのかかる範囲を指定してください:

  * `青空｜文庫《ぶんこ》`
  * `｜Y2K 問題《ミレニアムバグ》`

青空文庫公式ページではルビ入力の具体例が解説されています。

  * <http://www.aozora.gr.jp/>
  * <http://www.aozora.gr.jp/KOSAKU/MANUAL_2.html#ruby>
  * <http://www.aozora.gr.jp/annotation/etc.html#ruby>


青空文庫ルビ形式を使う利点
----
Markdownでルビを振りたい場合に青空文庫ルビ形式を採用すると

  * ルビを指定する多くのケースで入力が簡単
  * 文書をMarkdown形式のまま閲覧した時もルビ用記号が少なくて読みやすい

という利点が得られます。

青空文庫ルビ形式では、他のルビ記法と違って、親文字側を括弧で括る必要がありません。
そして、親文字の前の "`｜`" も親文字が同一文字種で構成されていれば省略できます。

ルビを振りたい単語は、漢字のみか英数字のみであることが多く、
ほとんどが送り仮名(ひらがな)や空白・記号で区切られています。
そのため、"`｜`" が必要になる状況は限られています。

結果として記号が少なくなり、入力しやすく読みやすいMarkdown文書を作れます。


同一文字種の判定ルール
----
青空文庫ルビ形式で認識する文字種は以下の6グループです:

  1. 漢字
  2. ひらがな
  3. 全角カタカナ
  4. 半角カタカナ
  5. 全角英数字
  6. 半角英数字

ルビ範囲指定の "`｜`" を省略できるのは、対象の単語が上記グループのいずれかの文字種だけで構成されている場合です。

このエクステンションが認識する各グループの具体的な文字は、
青空文庫公式ページの [組版案内](http://kumihan.aozora.gr.jp/)
で公開されているXHTML変換スクリプトの仕様を基準にしています。
実装の詳細は `AozoraRubyTextTrait.php` 内の正規表現リスト
`$aozora_ruby_text_CharTypePatterns` の値を確認してください。


Usage - 使い方
----

このエクステンションは `\Noi\ParsedownRubyText` および `\Noi\ParsedownExtraRubyText`
には組み込まれていません。

以下のように独自のParsedown派生クラスを定義して組み込んでください:

```php
class YourParsedown extends Parsedown /* or ParsedownExtra or etc. */ {
  // 1. RubyTextTrait と AozoraRubyTextTrait の両方をuse
  use \Noi\Parsedown\RubyTextTrait;
  use \Noi\Parsedown\AozoraRubyTextTrait;

  // 2. registerAozoraRubyTextExtension()をコンストラクタかどこかで実行
  public function __construct() {
    parent::__construct(); // 必要であれば呼ぶ

    $this->registerAozoraRubyTextExtension();

    // 3. RubyTextTraitのルビ記法 "[親文字]^(ルビ)" 形式も必要なら以下を実行する
    // $this->registerRubyTextExtension();
  }
}

$p = new YourParsedown();
echo $p->text('Parsedownはとても便利《べんり》');

// Output:
<p>Parsedownはとても<ruby>便利<rp>（</rp><rt>べんり</rt><rp>）</rp></ruby></p>
```

このエクステンションの動作は `\Noi\Parsedown\RubyTextTrait` に依存していますので、
上記のとおり忘れずに `use` してください。

ただし、`\Noi\ParsedownRubyText` と `\Noi\ParsedownExtraRubyText` は、
既に `RubyTextTrait` を組み込み済みです。
これらの派生クラスに組み込むときは、`use`するtraitは `AozoraRubyTextTrait` だけでOKです。

```php
class YourSuperDuperParsedown extends \Noi\ParsedownRubyText /* or \Noi\ParsedownExtraRubyText */ {
  // 1. AozoraRubyTextTrait だけをuse
  use \Noi\Parsedown\AozoraRubyTextTrait;

  public function __construct() {
    parent::__construct();
    $this->registerAozoraRubyTextExtension();
  }
}
```


青空文庫ルビ形式の公式仕様にない振る舞い
----
このエクステンションは `RubyTextTrait` のルビ振り機能に依存して動作するため、
以下の機能も使用できます:

  * モノルビ割り当て(`日本語《に ほん ご》`)
  * 属性値の指定(`日本語《にほんご》{#id .classA .classB lang=ja}`)
  * ふりがなの小書き文字(捨て仮名)自動変換


Licence
----
License等については`README.md`を確認してください。
