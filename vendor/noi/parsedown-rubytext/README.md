Parsedown ルビ記法extension
====
noi/parsedown-rubytextは、[Parsedown](http://parsedown.org)のルビ(ふりがな)用エクステンションです。

以下の拡張記法用エクステンションを含んでいます:

  1. `\Noi\Parsedown\RubyTextTrait`           -- ルビ指定(inline)
  2. `\Noi\Parsedown\RubyTextDefinitionTrait` -- ルビ定義(reference-style)
  3. `\Noi\Parsedown\AozoraRubyTextTrait`     -- 青空文庫ルビ形式(inline)
  4. `\Noi\Parsedown\PixivRubyTextTrait`      -- Pixivルビ形式(inline)

また、上記の1と2のtraitを組み込んだ実装クラスも含んでいます:

  * `\Noi\ParsedownRubyText`      -- `Parsedown`から派生
  * `\Noi\ParsedownExtraRubyText` -- `ParsedownExtra`から派生

以降では1と2の拡張記法の使い方について主に説明します。
3の説明は`README-Aozora.md`、4の説明は`README-Pixiv.md`を確認してください。


Markdown
----

### inlineルビ指定

  1. `[親文字]^(ルビ)`
  2. `[親文字]^（ルビ）` （全角カッコを使用）
  3. `[親文字]（ルビ）` （全角カッコを使用）

これらは次の`<ruby>`タグを生成します:

```html
<ruby>親文字<rp>（</rp><rt>ルビ</rt><rp>）</rp></ruby>
```

このパッケージは、上記の書式の他にもルビ記法エクステンションを同梱しています。
青空文庫ルビ形式(`README-Aozora.md`)とPixivルビ形式(`README-Pixiv.md`)も参照してください。

### reference-styleルビ定義

  * `**[親文字]: ルビ`

上記の書式を使って文書内のどこかの行でルビを定義します。
Markdownのリンク定義や、MarkdownExtraの "Abbreviations" のように、
ルビ定義行自体は変換後のHTMLに含みません。

ルビを定義した単語は、ルビ指定がない箇所も自動でルビ振り対象になります(inline codeやcode block内を除く)。

デフォルトの動作では、ルビ定義済み単語は文書内のすべての出現箇所でルビが振られます。
この動作を変更し、各単語の最初の出現箇所だけに限定することも可能です。

自動ルビ振りを初出箇所限定モードにする場合は以下のメソッドを実行してください:

  * `setRubyTextDefinitionMarkupAll(false)`

自動ルビ振りの設定に関係なく、inlineルビで明示的にふりがなを指定した箇所は常に指定のルビを振ります。


### モノルビ割り当て

ルビは分かち書きすることでルビ対象の各文字にモノルビとして割り当てることもできます:

  * `[日本語]^(に ほん ご)` -- ルビ指定
  * `**[日本語]: に ほん ご` -- ルビ定義

上の例は次の`<ruby>`タグを生成します:

    <ruby>
      日<rp>（</rp><rt>に<rt><rp>）</rp>
      本<rp>（</rp><rt>ほん<rt><rp>）</rp>
      語<rp>（</rp><rt>ご<rt><rp>）</rp>
    </ruby>
    (実際の出力は1行)


### 属性値の指定

ルビには属性値を追加設定することもできます。
Markdown Extra "Special Attributes" の書式と同じ `{...}` 形式で指定してください:

  * `[日本語]^(にほんご){#id .classA .classB lang=ja}`

上の例は次の`<ruby>`タグを生成します:

    <ruby id="id" class="classA classB" lang="ja">日本語<rp>（</rp><rt>にほんご</rt><rp>）</rp></ruby>


### ふりがなの小書き文字(捨て仮名)自動変換

HTML変換時、ふりがなに含まれる小書き文字(捨て仮名「ぁ」「っ」など)を並字(普通の大きさ「あ」「つ」など)に自動変換することも可能です。

このモードに設定すると、次のように小字を並字に自動変換します:

  * `[東京都]^(とうきょうと)`     => `<ruby>東京都<rp>（</rp><rt>とうきようと</rt><rp>）</rp></ruby>`
  * `[百科事典]^(ひゃっかじてん)` => `<ruby>百科事典<rp>（</rp><rt>ひやつかじてん</rt><rp>）</rp></ruby>`

自動変換モードに変更する場合は以下のメソッドを実行してください:

  * `setRubyTextSuteganaAllowed(false)`

ふりがなは小さいフォントサイズで表示されるため、印刷媒体などの慣例では読みやすさの観点から並字を使う傾向にあります。
変換後のHTMLを小さめのフォントサイズで表示する場合や、印刷目的でも利用する場合には、自動変換モードを使うと便利です。

デフォルトでは自動変換しません。小書き文字があってもそのまま使用します。


Installation - インストール方法
----
[Composer](http://getcomposer.org/) で以下を実行してください。

```sh
$ php /path/to/composer.phar require noi/parsedown-rubytext "*"
$ php /path/to/composer.phar require erusev/parsedown-extra "*"
```

または、`composer.json` に以下の行を含めてください。

```json
{
    "require": {
        "noi/parsedown-rubytext": "*",
        "erusev/parsedown-extra": "*"
    }
}
```

MarkdownExtraを使わない場合は、`erusev/parsedown-extra`の行は必要ありません。

Composerによるパッケージ管理をしていない場合は、`include_path` のいずれかに
`Noi/` ディレクトリを作り、そこへ `lib/` 以下のファイルを置いてください。


Usage - 使い方
----

### 使い方1: `Noi\ParsedownRubyText` / `Noi\ParsedownExtraRubyText`を使う

これらのクラスは、それぞれ`Parsedown`と`ParsedownExtra`にルビ用エクステンションを組み込んだ実装クラスです。

派生元クラスの機能に加えてルビ指定とルビ定義用の拡張記法が使用できます。
メソッドの使い方は`Parsedown`クラスと同じです。

```php
$pd = new \Noi\ParsedownRubyText(); // or new \Noi\ParsedownExtraRubyText();
echo $pd->text('[日本語]^(にほんご)');

// Output:
<p><ruby>日本語<rp>（</rp><rt>にほんご</rt><rp>）</rp></ruby></p>
```

どちらのクラスも「青空文庫ルビ形式」と「Pixivルビ形式」のエクステンションは組み込んでいません。
これらの拡張記法を使うときは、使い方2を参考に独自派生クラスを作って組み込む必要があります。


### 使い方2: 独自の`Parsedown`派生クラスにルビ記法を導入する

あなた独自の`Parsedown`派生クラスにルビ用エクステンションを組み込む場合は、
以下のように必要なtraitを組み込んでください:

```php
class YourParsedown extends Parsedown /* or ParsedownExtra or etc. */ {
    // 1. ルビ用エクステンションのtraitをuse
    use \Noi\Parsedown\RubyTextTrait;
    use \Noi\Parsedown\RubyTextDefinitionTrait;

    // 2. registerメソッドをコンストラクタかどこかで実行
    public function __construct()
    {
        parent::__construct(); // 必要に応じて

        $this->registerRubyTextExtension();
        $this->registerRubyTextDefinitionExtension();
    }
}

$pd = new YourParsedown();
echo $pd->text('[日本語]^(にほんご)');

// Output:
<p><ruby>日本語<rp>（</rp><rt>にほんご</rt><rp>）</rp></ruby></p>
```

traitを組み込む際、`use`だけでなく**`register`**メソッドの実行も必要です。忘れるとエクステンションは動作しません。
忘れやすいので注意してください！


### PHP5.3以前の場合

PHP5.3まではtrait未対応のためそのままでは使用できません。

組み込みたいtraitのファイルを開き、中身をコピペするなどしてクラスを作ってください。
またはPHPのバージョンアップをぜひお願いします。


### 動作の詳細

  * Markdownと変換結果の具体例は `tests/data/` 以下にあります。
  * 各クラスやtraitファイルのDocCommentも確認してください。

License
----
MITライセンスです。ライセンスの制限の範囲内であれば商用・非商用を問わず自由にお使いください。

Code released under the MIT License - see the `LICENSE` file for details.
