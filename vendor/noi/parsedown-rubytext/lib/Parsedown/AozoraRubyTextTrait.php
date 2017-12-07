<?php
namespace Noi\Parsedown;

/**
 * Parsedown 青空文庫ルビ形式Extension
 *
 * Parsedownを継承したクラスに「青空文庫ルビ形式」を
 * Markdownの拡張記法として導入します。
 * traitなので親クラスは自由に選択できます。
 *
 * Markdown:
 *
 *   1. 親文字《ルビ》   -- 青空文庫ルビ基本形式
 *   2. ｜親文字《ルビ》 -- 親文字範囲指定形式（"｜"は全角）
 *
 *   // HTML:
 *   <ruby>親文字<rp>（</rp><rt>ルビ<rt><rp>）</rp></ruby>
 *
 * 親文字の前の "｜" は、ルビを振る範囲が同一文字種であれば
 * 省略可能です。親文字を構成する文字がすべて、1.漢字のみ、
 * 2.ひらがなのみ、3.全角カタカナのみ、4.半角カタカナのみ、
 * 5.全角英数字記号のみ、6.半角英数字のみの場合、入力が簡単です。
 *
 * 同一文字種の途中までルビを振りたい場合や(例: "青空｜文庫《ぶんこ》")
 * 複数文字種や空白を含めて指定したい場合は(例: "｜Y2K 問題《ミレニアムバグ》")
 * "｜" を親文字の前に置いてルビのかかる範囲を指定してください。
 *
 * ルビ指定の記法に青空文庫ルビ形式を使うと
 *
 *  * ルビを指定する多くのケースで入力が簡単
 *  * 文書をMarkdown形式のまま閲覧した時もルビ用記号が最小限で読みやすい
 *
 * という利点が得られます。
 *
 * 青空文庫ルビ形式は、視覚障碍者読書支援協会(BBA)の
 * 原文入力ルールに合わせたものです。
 *
 * 青空文庫公式ページではルビ入力の具体例が解説されています。
 * @see http://www.aozora.gr.jp/
 * @see http://www.aozora.gr.jp/KOSAKU/MANUAL_2.html#ruby
 * @see http://www.aozora.gr.jp/annotation/etc.html#ruby
 *
 * このtraitは \Noi\Parsedown\RubyTextTrait に依存しています。
 * あなた独自のParsedown派生クラスに「青空文庫ルビ形式」を導入するには
 * 以下のUsageを参考にして両方のtraitを組み込んでください。
 *
 * Usage:
 *
 *   class YourParsedown extends Parsedown [ or ParsedownExtra or etc. ] {
 *     // 1. RubyTextTrait と AozoraRubyTextTrait をuse
 *     use \Noi\Parsedown\RubyTextTrait;
 *     use \Noi\Parsedown\AozoraRubyTextTrait;
 *
 *     // 2. registerAozoraRubyTextExtension()をコンストラクタかどこかで実行
 *     public function __construct() {
 *       parent::__construct(); // 必要であれば呼ぶ
 *
 *       $this->registerAozoraRubyTextExtension();
 *
 *       // 3. RubyTextTraitの拡張記法 "[親文字]^(ルビ)" が不要なら以下は省略可能
 *       // $this->registerRubyTextExtension();
 *     }
 *   }
 *
 *   $p = new YourParsedown();
 *   echo $p->text('Parsedownはとても便利《べんり》');
 *   // Output:
 *   <p>Parsedownはとても<ruby>便利<rp>（</rp><rt>べんり</rt><rp>）</rp></ruby></p>
 *
 * \Noi\ParsedownRubyText または \Noi\ParsedownExtraRubyText は
 * 既に RubyTextTrait を組み込み済みです。これらから派生させた
 * 独自クラスに本Extensionを組み込む場合、useするtraitは
 * \Noi\Parsedown\AozoraRubyTextTrait だけでOKです。
 *
 * AozoraRubyTextTrait は RubyTextTrait のルビ振り機能に
 * 依存して動作するため、モノルビ割り当ても使用可能です。
 * (これは青空文庫ルビ形式の公式仕様ではありません)
 *
 * 例:
 *   // markdown:
 *   日本語《に ほん ご》
 *
 *   // html:
 *   <ruby>
 *     日<rp>（</rp><rt>に<rt><rp>）</rp>
 *     本<rp>（</rp><rt>ほん<rt><rp>）</rp>
 *     語<rp>（</rp><rt>ご<rt><rp>）</rp>
 *   </ruby>
 *   (実際の出力は1行)
 *
 * @see \Noi\Parsedown\RubyText
 * @see \Noi\ParsedownRubyText
 * @see \Noi\ParsedownExtraRubyText
 *
 * @copyright Copyright (c) 2015 Akihiro Yamanoi
 * @license MIT
 *
 * For the full license information, view the LICENSE file that was distributed
 * with this source code.
 */
trait AozoraRubyTextTrait
{
    private $aozora_ruby_text_Triggers = array(
        'AozoraRubyText'         => '《',
        'AozoraRubyTextExplicit' => '｜',
    );

    private $aozora_ruby_text_ExtensionEnabled    = true;
    private $aozora_ruby_text_ExtensionRegistered = false;

    public function registerAozoraRubyTextExtension()
    {
        if ($this->aozora_ruby_text_ExtensionRegistered) {
            return;
        }

        $this->aozora_ruby_text_ExtensionRegistered = true;

        /* "｜" と "《" の各3byte目の文字コードをトリガー文字として登録する。
         *
         * "｜" はルビ記法としては省略可能だが、トリガーに登録しておく必要がある。
         * これを登録しないと以下のテストで示すような親文字側の装飾ができなくなる。
         * @see tests/Aozora/data/nest.md
         * @see tests/Aozora/data/nest.html
         *
         * ParsedownではMarkdown記号として1byte文字を想定しているが
         * このExtensionでは全角文字の1byte分を強引にトリガーとして使う。
         *
         * UTF8では全角文字の1byte目は他の全角文字と重複が多く、無駄に
         * inline処理メソッドを呼んでしまったので避けた。3byte目を使う。
         *
         * 全角文字の一部をトリガーに使うと、代償としてinline処理メソッドに
         * 面倒な処理を実装しなければならなくなる。
         * 詳細はinlineAozoraRubyText()のコメントを確認すること。
         * @see inlineAozoraRubyText()
         * @see buildAozoraRubyTextContext()
         *
         * このクラスは全体的にmultibyte絡みで多少無理やりな実装をしているため
         * 忘れないようにコメントを多めに残した。
         */
        foreach ($this->aozora_ruby_text_Triggers as $inlineType => $trigger) {
            $this->InlineTypes[$trigger[2]][] = $inlineType;

            // 先頭byteはエスケープ対象文字に登録しておく
            $this->specialCharacters[] = $trigger[0];
        }

        $this->inlineMarkerList = join('', array_keys($this->InlineTypes));
    }

    /*
     * 青空文庫ルビ形式を解析してルビ情報を返す。
     *
     * このinline処理メソッドはmultibyte文字の一部をトリガーとして
     * 動作するので、ASCII文字を使う場合とは違った実装が必要になる。
     *
     * 以下の3つの面倒なことに注意しなけばならない:
     *
     *   A. 他の全角文字の一部と偶然一致しても呼ばれること。
     *   B. トリガーのエスケープも考慮する必要があること。
     *   C. Aの場合にnullを返してはならないこと。
     *
     * Aの状況が発生するため、inline処理メソッドが呼ばれた時点でまず
     * 意図したトリガー文字("《")に一致したのかチェックすることになる。
     * このとき、マッチした文字がトリガー文字なら書式チェックに進み、
     * 他の全角文字であればCの問題に注意して処理しなければならない。
     *
     * BとCは、Parsedownの実装と全角文字の相性の悪さから発生する不具合を
     * 回避するために必要。Bについてはメソッド内のコメントを参照。
     *
     * Cを忘れてnullを返すと、解析中の文字列はトリガーに偶然マッチした
     * 全角文字の箇所で意図せず分断されてしまう(全角文字の分断問題)。
     *
     * これに対処しないと、以下の不具合が発生する:
     *
     *   * $Excerpt['context']の値を逆戻りして解析するExtensionで
     *     本来あるはずの文字列が見つけられなくなる。
     *     (このExtensionでも発生する)
     *   * unmarkedText()をoverrideして独自の置換処理をするExtensionで
     *     本来あるはずの置換対象が見つけられなくなる。
     *     ("Abbreviations" や RubyTextDefinition などで発生する)
     *
     * nullを返したい場面では以下のように無理やり解決する:
     *
     *   1. 同じトリガー文字に反応する他のinline処理メソッドが待機している場合は
     *      思い切ってnullを返す。そして、後の対策をしてくれることを祈る。
     *   2. 1以外の場合は、buildAozoraRubyTextContext()を呼び、解析中の文字列の
     *      次のmarkdown記号までを処理してしまう。このとき全角文字の分断を抑制する。
     *   3. 処理結果は 'element' ではなく 'markup' で返す。
     *
     * この問題の対処方法の詳細はbuildAozoraRubyTextContext()も確認すること。
     * @see buildAozoraRubyTextContext()
     */
    protected function inlineAozoraRubyText($Excerpt)
    {
        if (!$this->isAozoraRubyTextEnabled()) {
            return ($this->isAozoraRubyTextLast('AozoraRubyText')) ? $this->buildAozoraRubyTextContext($Excerpt) : null;
        }

        $context_len = strlen($Excerpt['context']);
        $text_len    = strlen($Excerpt['text']);
        $diff_len    = $context_len - $text_len;

        // エスケープ処理が必要かチェックする
        if (($diff_len === 1) and ($Excerpt['context'][0] === $this->aozora_ruby_text_Triggers['AozoraRubyText'][1])) {
            /* "《" の1byte目がない:
             * "\《" とエスケープされて1byte目だけ先に確定済みになっている。
             *
             * 残された2-3byte目もここで追加のエスケープ処理として一旦確定する。
             * これをやらずに以降の処理を続けると、文字列に不完全な全角文字の
             * 文字コードが残っているため正規表現が正しく動作しないことがある。
             *
             * また、ここでnullを返すと、全角文字として不完全な2byteが
             * unmarkedText()で処理されてしまう。unmarkedText()の実装によっては
             * 不完全な文字コードを処理させると不具合に繋がる危険がある。
             *
             * 'markup'で返した値はunmarkedText()を通らないのでこれを使っておく。
             */
            return array(
                'position' => 0,
                'extent'   => 2,
                'markup'   => $Excerpt['context'][0] . $Excerpt['context'][1],
            );
        }

        /* "《" の3byte目になっているoffsetを1byte目の前に調整:
         * assert(substr($Excerpt['context'], $offset) === "《...ルビ...》...");
         */
        $offset = max(0, $diff_len - 2);

        // Markdown書式チェック
        if (!$this->matchAozoraRubyTextMarkdown($Excerpt['context'], $offset, $kanji, $furigana, $position, $extent)) {
            return ($this->isAozoraRubyTextLast('AozoraRubyText')) ? $this->buildAozoraRubyTextContext($Excerpt) : null;
        }

        /*
         * 追加の属性値があれば切り出す。
         *
         * 属性値は閉じ括弧の直後に "{...}" の形式で指定する:
         *
         *     親文字《ふりがな》{#id .class lang=ja}
         *
         * 属性値指定は青空文庫ルビ形式の公式仕様ではなく
         * このExtensionの独自仕様。
         */
        if ($this->matchRubyTextAttributes($Excerpt['context'], $position + $extent, $attributes, $additional)) {
            $extent += $additional;
        }

        return array(
            // 親文字を得るために$Excerpt['context']を逆戻りして解析したので'position'が必要
            'position' => $position,
            'extent'   => $extent,
            'element'  => $this->buildRubyTextElement($kanji, $furigana, $attributes, $Excerpt['context'], $position, $extent),
        );
    }

    /**
     * 親文字を範囲指定した青空文庫ルビ形式を解析してルビ情報を返す。
     *
     * "｜" を使ったルビ指定はこのメソッドで解析する。
     *
     * このメソッドは親文字側の書式チェックだけを担当し、
     * ルビ情報の解析自体はinlineAozoraRubyText()に任せる。
     * @see inlineAozoraRubyText()
     *
     * "｜" で親文字を範囲指定するとmarkdownのネストも可能になる。
     * (例: "｜**親文字だけ強調**《ふりがな》")
     */
    protected function inlineAozoraRubyTextExplicit($Excerpt)
    {
        if (!$this->isAozoraRubyTextEnabled()) {
            return ($this->isAozoraRubyTextLast('AozoraRubyTextExplicit')) ? $this->buildAozoraRubyTextContext($Excerpt) : null;
        }

        $context_len = strlen($Excerpt['context']);
        $text_len    = strlen($Excerpt['text']);
        $diff_len    = $context_len - $text_len;

        // エスケープ処理が必要かチェックする(inlineAozoraRubyText()側のコメントも参照)
        if (($diff_len === 1) and ($Excerpt['context'][0] === $this->aozora_ruby_text_Triggers['AozoraRubyTextExplicit'][1])) {
            // "｜" の1byte目がない: 2-3byte目も追加エスケープ処理として確定する。
            return array(
                'position' => 0,
                'extent'   => 2,
                'markup'   => $Excerpt['context'][0] . $Excerpt['context'][1],
            );
        }

        // "｜" の3byte目になっているoffsetを1byte目の前に調整
        $offset = max(0, $diff_len - 2);
        $left   = substr($Excerpt['context'], $offset);

        // "｜" にマッチしたかチェックする
        if (strncmp($left, $this->aozora_ruby_text_Triggers['AozoraRubyTextExplicit'], 3) !== 0) {
            // 他の全角文字に偶然マッチしただけだった
            return ($this->isAozoraRubyTextLast('AozoraRubyTextExplicit')) ? $this->buildAozoraRubyTextContext($Excerpt) : null;
        }

        // 親文字指定の書式チェック
        if (!$this->matchAozoraRubyTextBase($left, $length)) {
            /* 正しい書式ではなかった:
             * トリガー文字 "｜" を平文として確定するため、ここでだけは、nullを返せる。
             */
            return null;
        }

        /* 正しい書式だった:
         * "《" の3byte目にoffsetを移動して、後はinlineAozoraRubyText()に任せる。
         */
        $Excerpt['text'] = substr($Excerpt['context'], $offset + $length + 2);

        return $this->inlineAozoraRubyText($Excerpt);
    }

    protected function matchAozoraRubyTextBase($text, /* out */ &$length)
    {
        /* 書式チェック1:
         * 親文字が空ではなく後ろにエスケープされていない "《" があること。
         *
         * "(?<!\x5C)(\x5C\x5C)*《" は奇数個のバックスラッシュが
         * 前置されていない "《" にマッチするはず。
         */
        if (!preg_match('/｜(.+?(?<!\x5C)(?:\x5C\x5C)*)(《)/Au', $text, $m, PREG_OFFSET_CAPTURE)) {
            return false;
        }

        /* 書式チェック2:
         * "｜" から "《" までの間に他の "｜" が *ない* こと。
         * (エスケープされた "\｜" はOK)
         */
        if (preg_match('/(?<!\x5C)(\x5C\x5C)*｜/u', $m[1][0])) {
            return false;
        }

        $length = $m[2][1];
        return true;
    }

    protected function matchAozoraRubyTextMarkdown(
        $text,
        $offset,
        /* out */ &$kanji,
        /* out */ &$furigana,
        /* out */ &$position,
        /* out */ &$extent
    ) {
        if ($offset === 0) {
            // 行頭なので親文字がない
            return false;
        }

        if (!preg_match('/《((?:(?>[^《》]+)|(?R))*)》/Au', $text, $m, 0, $offset)) {
            // 対応括弧がない
            return false;
        }

        if (!$this->matchAozoraRubyTextBaseCharType(substr($text, 0, $offset), $kanji, $position, $extent)) {
            // 親文字の文字種が不適切なのでルビ指定ではない
            return false;
        }

        // 親文字が切り出せた。ふりがなも確定
        $furigana = $m[1];
        $extent  += strlen($m[0]);
        return true;
    }

    /*
     * 青空文庫ルビ形式の親文字部分を切り出す。
     *
     * 親文字の切り出し方を変更したい場合は、
     * setAozoraRubyTextCharTypePatterns()で正規表現リストを入れ替えるか
     * このメソッド自体をoverrideしてアルゴリズムを変える。
     */
    protected function matchAozoraRubyTextBaseCharType($text, /* out */ &$kanji, /* out */ &$position, /* out */ &$extent)
    {
        foreach ($this->getAozoraRubyTextCharTypePatterns() as $pattern) {
            if (preg_match($pattern, $text, $m, PREG_OFFSET_CAPTURE)) {
                $kanji    = $m[1][0];
                $position = $m[0][1];
                $extent   = strlen($m[0][0]);

                return true;
            }
        }

        return false;
    }

    /*
     * 解析中の文字列の全角文字を分断せず維持しながら次のmarkdown記号まで処理する。
     *
     * 処理内容はline()とほぼ同じだが、全角文字の分断問題を考慮して
     * $Excerpt['context']を途中で切らないように抑制する点が異なる。
     *
     * この酷いメソッドは、全角文字の一部を強引にトリガーとする場合に
     * いやらしい不具合を出さないため必要となる。
     *
     * Parsedownの仕様:
     *   inline処理メソッドがnullを返すと、その箇所のトリガー文字は
     *   (markdown記号ではないとみなして)unmarkedTextに足し戻す。
     *   そして、それを平文として確定するためunmarkedText()を実行する。
     *
     * この仕様により、トリガー部分に偶然マッチしてしまっただけの
     * 全角文字は一度そこで分断されunmarkedText()で処理されてしまう。
     *
     * 例:
     *   "半" の3byte目は "《" の3byte目と一致しているため
     *   "半" の位置でもinlineAozoraRubyText()が呼ばれる。
     *   このときnullを返してしまうと、以下の不具合が発生する:
     *
     *     * 処理中の行     == "明日は｜半休《はんきゅう》だ"
     *     * unmarkedText   == '明日は｜半'
     *     * 処理待ちのtext == '休《はんきゅう》だ'
     *
     *   "半" と "休" は本来なら1つの文字列として並んでいたが
     *   "半" の一部がトリガーと一致したことで分割されてしまう。
     *
     *   inlineAozoraRubyText()が本当に処理すべき "《はんきゅう》" に
     *   辿り着いたとき、本来の親文字であった「半休」の "半" は既に
     *   平文として確定されている。これでは正しいルビ振りができない。
     *
     *   別の例として、「半休」に "Abbreviations" で「半日休暇」と定義
     *   していたり、RubyTextDefinition で「はんきゅう」とルビ定義して
     *   いたとしても、unmarkedText()は「半休」を発見できない。
     *   つまり、タグ付けをしない不具合が発生する。
     *
     * このような不具合の原因になるため、全角文字の一部をトリガーに
     * 使っているinline処理メソッドは無関係な全角文字で起動したときも
     * とにかくnullを返してはならない。
     *
     * このExtensionでは不具合の回避策に、次のmarkdown記号まで
     * unmarkedTextを確定せずに持ち越して処理する方法を採用した。
     * この実装の欠点としてはParsedown::line()と同じような処理を
     * 重複して書くことになる。
     *
     * 他の解決策としては、そもそもinline処理メソッドを使わず
     * unmarkedText()をoverrideして自前でルビ指定を解析する方法がある。
     * しかし、これは新たな問題として、unmarkedText()をoverrideする他の
     * trait(RubyTextDefinitionTraitなど)と競合が発生するようになる。
     *
     * 利便性を考えると、Extensionの利用者にtraitの競合回避処理は
     * 書かせたくない。単にuseとregisterだけで使えたほうが良いはず。
     */
    protected function buildAozoraRubyTextContext($Before)
    {
        // トリガー部分から1byte前進
        $text = substr($Before['text'], 1);

        // 次に見つかるmarkdown記号を処理する
        $excerpt = strpbrk($text, $this->inlineMarkerList);

        if ($excerpt === false) {
            /* 最後までunmarkedTextだった:
             * 残りはすべて平文として一括で返す。nullではない(重要)
             */
            return array(
                'position' => 0,
                'extent'   => strlen($Before['context']),
                'markup'   => $this->unmarkedText($Before['context']),
            );
        }

        $marker = $excerpt[0];

        // ここが違う: $Before['text']のoffsetに翻訳する
        $markerPosition = strpos($text, $marker) + 1;

        // ここが違う: $Before['context']のoffsetに翻訳する
        $contextOffset  = $markerPosition + (strlen($Before['context']) - strlen($Before['text']));

        // $Before['context']: "abc#ABC#abc" (len=11)
        // $Before['text']   :    "#ABC#abc" (len= 8)
        // $text             :     "ABC#abc" (len= 7)
        // $markerPositoin   :     01234
        // $contextOffset    :  01234567

        /* ここが違う: 'context' => $Before['context']
         * unmarkedTextを確定せず次のmarkdown処理に持ち越す(重要)
         */
        $Excerpt = array('text' => $excerpt, 'context' => $Before['context']);

        foreach ($this->InlineTypes[$marker] as $inlineType) {
            $Inline = $this->{'inline' . $inlineType}($Excerpt);
            if (!isset($Inline)) {
                continue;
            }

            // ここが違う: $contextOffsetを使う
            if (isset($Inline['position']) and $Inline['position'] > $contextOffset) {
                continue;
            }

            // ここが違う: $contextOffsetを使う
            if (!isset($Inline['position'])) {
                $Inline['position'] = $contextOffset;
            }

            /* ここが違う:
             * 前回のunmarkedTextは確定せず$Before['context']として持ち越してきた。
             * 'context'の先頭から$Inline['position']までが本当のunmarkedTextだった。
             */
            $unmarkedText = substr($Before['context'], 0, $Inline['position']);

            $markup  = $this->unmarkedText($unmarkedText);
            $markup .= isset($Inline['markup']) ? $Inline['markup'] : $this->element($Inline['element']);

            /* 次のmarkdown記号までを処理した:
             * 処理結果を'markup'として一括で返す。$Inlineだけをそのまま返してはダメ(重要)
             */
            return array(
                'position' => 0,

                // unmarkedTextの長さ'position'と、markdown部分の長さ'extent'の合計
                'extent'   => $Inline['position'] + $Inline['extent'],
                'markup'   => $markup,
            );
        }

        /* 次の$markerもmarkdownを意図した記号ではなかった:
         * ここ到達するのは$markerがASCII文字だがmarkdown記号ではなかった場合。
         *
         * 次の$markerを含めた位置まではすべて平文として一括で返す。
         * nullを返さないこと(重要)
         */
        return array(
            'position' => 0,
            'extent'   => $contextOffset + 1,  // トリガーの1byte分だけ追加する
            'markup'   => $this->unmarkedText(substr($Before['context'], 0, $contextOffset + 1)),
        );

        //// うわあぁ
    }

    /*
     * 同じトリガー文字で起動するinline処理メソッドの中で
     * このExtensionの実行順序が最後かどうか調べる。
     *
     * このExtensionが最後を担当している場合はtrueを返す。
     * このとき、inline処理メソッドは全角文字の分割問題を適切に防ぐ必要がある。
     * @see inlineAozoraRubyText()
     *
     * ほとんどの実用場面で "《" や "｜" の3byte目と同じトリガーを使うExtensionは
     * 他に登録されていないはず。
     */
    protected function isAozoraRubyTextLast($inlineType)
    {
        $list = $this->InlineTypes[$this->aozora_ruby_text_Triggers[$inlineType][2]];
        return ($list[count($list) - 1] === $inlineType);
    }

    public function setAozoraRubyTextEnabled($bool)
    {
        $this->aozora_ruby_text_ExtensionEnabled = $bool;
        return $this;
    }

    public function isAozoraRubyTextEnabled()
    {
        return $this->aozora_ruby_text_ExtensionEnabled;
    }

    /**
     * 親文字切り出し用の正規表現リストを設定する。
     *
     * 引数は以下の形式で指定する:
     *
     *     array(
     *       'label1' => '/(regex1)$/u',
     *       'label2' => '/(regex2)$/u',
     *       ...
     *     )
     *
     *  - $matches[1]に結果の親文字が含まれるように /(...)/ で調整する。
     *  - 正規表現の末尾記号 /$/ がマッチする場所は、"《" の直前の位置。
     *  - 各正規表現は要素順に使用する。
     *  - 'label'の値は使用しない。自由に名前を付けて良い。
     */
    public function setAozoraRubyTextCharTypePatterns($patterns)
    {
        $this->aozora_ruby_text_CharTypePatterns = $patterns;
        return $this;
    }

    public function getAozoraRubyTextCharTypePatterns()
    {
        return $this->aozora_ruby_text_CharTypePatterns;
    }

    abstract protected function buildRubyTextElement($kanji, $furigana, $attributes = null, $context = null, $position = null, $extent = null);
    abstract protected function parseRubyTextAttributeData($attributeString);
    abstract protected function matchRubyTextAttributes($target, $offset, /* out */ &$attributes, /* out */ &$extent);

    /*
     * 親文字切り出し用の正規表現リスト
     *
     * 青空文庫ルビ形式では、"｜" を置いてルビのかかる範囲を
     * 指定しないとき、範囲特定のために親文字の文字種を見る。
     *
     * そして同一文字種が続く範囲を自動的に親文字とみなし、
     * 文字種が変わる位置や括弧記号・空白を区切りとする。
     *
     * 以下の正規表現は、青空文庫公式ページの組版案内で
     * 公開されているXHTML変換スクリプトを参考にした。
     * http://kumihan.aozora.gr.jp/
     *
     * txt2xhtml-0.3.1同梱の t2hs.rb:882行目
     * char_type()メソッド内で使用されている正規表現を
     * 青空文庫公式の文字種特定用パターンと考え、そこに
     * Unicodeを前提としたいくつかの文字を独自に加えた。
     *
     * 以下の正規表現は変更することもできる。
     * @see setAozoraRubyTextCharTypePatterns()
     */
    protected $aozora_ruby_text_CharTypePatterns = array(
        // 'unused-label'  => '/(regex)$/u',

        /* 範囲指定パターン:
         *   - 全角"｜"を使った明示的な範囲指定。文字種に関係なく親文字を指定できる。
         *   - t2hs.rbの実装では半角"|"での代用を許していない。
         *   - このExtensionでもそれに従っておく。
         */
        'delimited'        => '/｜(.+?)$/u',

        /* 漢字グループ:
         *   - 青空文庫では「仝々〆〇ヶ\x{303B}」も漢字として扱うと明記している(\x{303B}は二の字点)。
         *     @see http://www.aozora.gr.jp/KOSAKU/MANUAL_2.html#ruby
         *   - t2hs.rbでは外字記述用の※も漢字に含めていたが
         *     このExtensionだけでは青空文庫外字指定形式を扱えないので保留する。
         *   - このExtension独自の制限として、"ヶ" は漢字に続く場合だけ漢字とみなす。
         *     (例: "(OK)八ヶ岳", "(NG)5ヶ条")
         */
        'kanji'            => '/((?:[\p{Han}〆]+[ヶ]*)+)$/u',

        /* 全角英数字グループ:
         *   - t2hs.rbでは、全角記号、ギリシア文字、キリル文字も合わせて同一文字種としている。
         *   - このExtensionでもそれに従う。
         */
        'zenkaku_alphanum' => '/([Ａ-Ｚａ-ｚ０-９\p{Greek}\p{Cyrillic}＆’，．－]+)$/u',

        /* 半角英数字グループ:
         *   - 全角英数字グループとは記号の種類が違う。
         *   - t2hs.rbでは半角英数記号グループに末尾専用記号(:hankaku_terminate)を定義している。
         *     :hankaku_terminate が出現した箇所は半角英数字の切れ目になる。
         *   - このExtensionではそれを少しだけ発展させて
         *     :hankaku_terminate の一部の記号は繰り返し可能にする。
         *     - [;]は元の仕様通り。その時点で終端(end;)
         *     - [.]は末尾で繰り返せる(oh...)
         *     - [!?]はそれぞれを組み合わせて繰り返し可(hey!!!?!????)
         *   - t2hs.rbでは "&" と '"' を半角英数字グループに含めているので
         *     このExtensionでも正規表現上は使っているが、実際はこの2つを
         *     半角英数字として認識できない仕様になっている。これらの文字は
         *     Parsedownがそれぞれ "&amp;"と"&quot;" に変換して確定してしまうため。
         *   - "&" や '"' を含めて親文字に指定したい場合は "｜" で範囲指定する。
         *     (例: "｜AT&T《ルビ》")
         *   - なお、以下のページから実際に変換を行ってみたところ、
         *     t2hs.rbでも "&" については半角英数字と認識されなかった。
         *     ("AT&T《ルビ》" では末尾の "T" にルビが振られる)
         *     @see http://kumihan.aozora.gr.jp/slabid-5.htm
         */
        'hankaku_alphanum' => '/([A-Za-z0-9,#\-\&\']+(?:[\;\"]|\.+|[\!\?]+)?)$/u',

        /* 全角カナグループ:
         *   - t2hs.rbではカタカナの小書き"ヵ"と"ヶ"をカタカナに含めていない。
         *   - このExtensionではそれを少しだけ緩めて、
         *     カタカナの後にあれば"ヵ"と"ヶ"もカタカナとする。
         *   - 他にもカタカナの後ろに全角の濁点・半濁点・長音記号があれば
         *     それもカタカナの一部とみなす。
         *     これにより濁点付き "ワ゛" などを2文字で入力した稀なケースにもルビが振れる。
         *   - さらに、Unicodeを前提として濁点付きワ-ヲと合字コトを追加しておく。
         */
        'zenkaku_katakana' => '/((?:[ァ-ヴ\x{30F7}-\x{30FA}ヽヾ\x{30FF}]+[゛゜ーヵヶ]*)+)$/u',

        /* 半角カナグループ:
         *   - 青空文庫では半角カナを使わないルールがある。
         *   - t2hs.rbにも半角カナ用の正規表現は定義されていない。
         *   - このExtensionでは独自に定義しておく。
         *   - 半角の濁点・半濁点は半角カナに続くものだけを半角カナの一部とみなし、
         *     他の用途で別の文字種の後に置かれていることがあっても無視する。
         */
        'hankaku_katakana' => '/((?:[ｦ-ﾝ]+[ﾞﾟ]*)+)$/u',

        /* ひらがなグループ:
         *   - ひらがなにもルビを振ることがある。(例: "てふてふ《ちょうちょう》"）
         *   - t2hs.rbでは、ひらがなに全角の濁点・半濁点・長音記号を含めていない。
         *   - このExtensionではそれを少しだけ緩めて、
         *     全角の濁点・半濁点はひらがなの後ろにあればひらがなの一部とみなす。
         *     (例: "ん゛ん゛ん゛ん゛《たすけて》！")
         *   - 長音記号に関しては元の仕様に従い、ひらがなに含めない。
         *   - ひらがなにルビを振りたい稀なケースでも、実際は前の単語の送り仮名と
         *     "｜" で区切ることになる状況が多いのではないだろうか。これを踏まえて
         *     パターンの優先順位は最後にしておく。
         */
        'hiragana'         => '/((?:\p{Hiragana}+[゛゜]*)+)$/u',
    );
}
