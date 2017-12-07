<?php
namespace Noi\Parsedown;

/**
 * Parsedown ルビ用拡張記法Extension
 *
 * Parsedownを継承したクラスにルビ(ruby text)用の拡張記法を提供します。
 * traitなので親クラスは自由に選択できます。
 *
 * Markdown:
 *
 *   1. [親文字]^(ルビ)  -- 基本形式 [base]^(ruby)
 *   2. [親文字]^（ルビ）-- ^あり全角括弧形式
 *   3. [親文字]（ルビ） -- ^なし全角括弧形式
 *
 *   // HTML:
 *   <ruby>親文字<rp>（</rp><rt>ルビ<rt><rp>）</rp></ruby>
 *   <ruby>base<rp>（</rp><rt>ruby<rt><rp>）</rp></ruby>
 *
 * \Noi\ParsedownRubyText, \Noi\ParsedownExtraRubyTextクラスは
 * このtraitを使った実装クラスです。
 * あなた独自のParsedown派生クラスで使用するためには
 * 以下のUsageを参考にして組み込んでください。
 *
 * Usage:
 *
 *   class YourParsedown extends Parsedown [ or ParsedownExtra or etc. ] {
 *     // 1. 本traitをuse
 *     use \Noi\Parsedown\RubyTextTrait;
 *
 *     // 2. registerRubyTextExtension()をコンストラクタかどこかで実行
 *     public function __construct() {
 *       parent::__construct(); // 必要であれば呼ぶ
 *       $this->registerRubyTextExtension();
 *     }
 *   }
 *
 *   $p = new YourParsedown();
 *   echo $p->text('Parsedownはとても[便利]^(べんり)');
 *   // Output:
 *   <p>Parsedownはとても<ruby>便利<rp>（</rp><rt>べんり</rt><rp>）</rp></ruby></p>
 *
 * ルビは分かち書きすることで親文字それぞれに
 * モノルビとして割り当てることもできます。
 *
 * 例:
 *   // markdown:
 *   [日本語]^(に ほん ご)
 *
 *   // html:
 *   <ruby>
 *     日<rp>（</rp><rt>に<rt><rp>）</rp>
 *     本<rp>（</rp><rt>ほん<rt><rp>）</rp>
 *     語<rp>（</rp><rt>ご<rt><rp>）</rp>
 *   </ruby>
 *   (実際の出力は1行)
 *
 * ルビには属性値を追加設定することもできます。
 * Markdown Extra "Special Attributes" の記法と同じ{...}形式です。
 *
 * 例:
 *   // markdown:
 *   [日本語]^(にほんご){#id .classA .classB lang=ja}
 *
 *   // html:
 *   <ruby id="id" class="classA classB" lang="ja">日本語<rp>（</rp><rt>にほんご</rt><rp>）</rp></ruby>
 *
 * @see \Noi\ParsedownRubyText
 * @see \Noi\ParsedownExtraRubyText
 *
 * @copyright Copyright (c) 2015 Akihiro Yamanoi
 * @license MIT
 *
 * For the full license information, view the LICENSE file that was distributed
 * with this source code.
 */
trait RubyTextTrait
{
    // <ruby>親文字<rp>（</rp><rt>ルビ</rt><rp>）</rp></ruby>
    private $ruby_text_OpeningBracket = '（';  // 上記の前方の<rp>（</rp>に入る括弧
    private $ruby_text_ClosingBracket = '）';  //       後方の<rp>）</rp>に入る括弧

    private $ruby_text_MonoRubySeparator = ' ';

    private $ruby_text_SuteganaAllowed     = true;

    private $ruby_text_ExtensionEnabled    = true;
    private $ruby_text_ExtensionRegistered = false;

    public function registerRubyTextExtension()
    {
        if ($this->ruby_text_ExtensionRegistered) {
            return;
        }

        $this->ruby_text_ExtensionRegistered = true;

        $this->InlineTypes['['][] = 'RubyText';

        // inlineテーブルのキーを使ってstrpbrk()用の文字列を更新しておく
        $this->inlineMarkerList = join('', array_keys($this->InlineTypes));
    }

    protected function inlineRubyText($Excerpt)
    {
        if (!$this->isRubyTextEnabled()) {
            return;
        }

        if (!$this->matchRubyTextMarkdown($Excerpt['text'], $kanji, $furigana, $extent)) {
            return;
        }

        if ($this->matchRubyTextAttributes($Excerpt['text'], $extent, $attributes, $additional)) {
            $extent += $additional;
        }

        $position = strlen($Excerpt['context']) - strlen($Excerpt['text']);

        return array(
            'extent'  => $extent,
            'element' => $this->buildRubyTextElement($kanji, $furigana, $attributes, $Excerpt['context'], $position, $extent),
        );
    }

    protected function matchRubyTextMarkdown($target, /* out */ &$kanji, /* out */ &$furigana, /* out */ &$extent)
    {
        /* 以下の記法をルビ指定と解釈する:
         *   1. [親文字]^(ルビ)    -- 基本形式
         *   2. [親文字]^（ルビ）  -- ^あり全角括弧形式
         *   3. [親文字]（ルビ）   -- ^なし全角括弧形式
         *
         * これらはURLリンクの書式と似ているが ^ を置く点が異なる。
         * 1の記法はC++用のmarkdown parser実装でも採用しているものがあった。
         * http://d.hatena.ne.jp/tociyuki/20150610
         *
         * ルビはほとんどのケースで全角文字（ひらがな、カタカナ）を使って指定するため
         * ルビ用の括弧にも全角を使えたほうが入力しやすく見栄えもよくなるかもしれない。
         * 色々な原稿の応募要項でも、"ふりがなは全角カッコの中に" という指示を
         * 見かけるので、この慣例に従うため2の形式にも対応する。
         * (親文字側の括弧は半角[]のみ)
         *
         * 3の形式のようにルビの括弧に全角を使うと解析時にURLリンク記法と区別できるため
         * ^ は省略しても良いことにする。
         *
         * 以上の仕様が誤動作の原因になるようであれば考えなおす。
         */

        /* まず [親文字] にマッチするか調べる
         * 再帰的パターン(?R)を使って対応する閉じ括弧までをマッチさせる
         */
        if (!preg_match('/\[((?:(?>[^][]+)|(?R))*)\]/Au', $target, $m)) {
            return false;
        }

        $tmp_kanji = $m[1];
        $offset    = strlen($m[0]);
        $rest      = substr($target, $offset);

        if (($tmp_kanji == '') or ($rest == '')) {
            return false;
        }

        // 1. 基本形式かチェック
        if ($rest[0] == '^') {
            $rest = substr($rest, 1);
            $offset++;

            if (preg_match('/\(((?:(?>[^()]+)|(?R))*)\)/Au', $rest, $m)) {
                $kanji    = $tmp_kanji;
                $furigana = $m[1];
                $extent   = $offset + strlen($m[0]);
                return true;
            }
        }

        // 2,3. 全角括弧形式かチェック
        if (preg_match('/（((?:(?>[^（）]+)|(?R))*)）/Au', $rest, $m)) {
            $kanji    = $tmp_kanji;
            $furigana = $m[1];
            $extent   = $offset + strlen($m[0]);
            return true;
        }

        return false;
    }

    /*
     * 属性値の指定があるか調べて切り出す。
     *
     * 文字列$targetの$offset位置に属性値が指定されていれば
     * 戻り値にtrueを返す。なければfalseを返す。
     *
     * 属性値がある場合は切り出して$attributes出力引数に渡す。
     * また、$offset以降で消費した文字列の長さを$extent出力引数に渡す。
     *
     * ルビに属性値を設定するとCSSやjavascriptで表示制御などが可能になり便利。
     *
     * このメソッドはデフォルトで$targetの$offset直後から
     * 属性値の記述 "{...}" が始まると想定して処理する。
     * ($offset位置以降の空白を考慮しない)
     *
     * この振る舞いは MarkdownExtra や ParsedownExtra の
     * "Special Attributes" 処理仕様と異なる。
     * これらのクラスはそれぞれ以下のように空白を許可する:
     *
     *  * MarkdownExtra:  属性値の前に1つまで空白を許す。
     *  * ParsedownExtra: 属性値の前に複数の空白を許す。
     *
     * 正しい仕様はわからなかった。
     * そのため、このメソッドは誤作動防止のため
     * 今のところ空白を1つも許可しないことにしておく。
     */
    protected function matchRubyTextAttributes($target, $offset, /* out */ &$attributes, /* out */ &$extent)
    {
        /* ルビには属性値を追加設定できる:
         *   [親文字]^(ルビ){#id .class1 .class2 attr1=val1 attr2=val2 ...}
         *
         * $offsetは ")" の直後の位置を示している。
         * そこからの位置に "{...}" があれば属性値の指定とみなす。
         */
        if (preg_match('/{((?:(?>[^{}]+)|(?R))*)}/Au', $target, $m, 0, $offset)) {
            $attributes = $this->parseRubyTextAttributeData($m[1]);
            $extent     = strlen($m[0]);
            return true;
        }

        return false;
    }

    protected function buildRubyTextElement($kanji, $furigana, $attributes = null, $context = null, $position = null, $extent = null)
    {
        return $this->{$this->getRubyTextElementBuilderName()}($kanji, $furigana, $attributes, $context, $position, $extent);
    }

    protected function buildRubyTextElementCallback($kanji, $furigana, $attributes)
    {
        $ruby = array();

        // 熟語へのモノルビ指定に対応するため親文字とルビのペアを複数指定可能にする
        foreach ($this->parseRubyText($kanji, $furigana) as $pair) {
            $item = array(
                'base' => $pair[0],
                'rt'   => array(
                    'name'    => 'rt',
                    'handler' => 'line',
                    'text'    => $pair[1],
                ),
            );

            if (strlen($this->getRubyTextOpeningBracket())) {
                $item['rp_opening'] = array(
                    'name'    => 'rp',
                    'text'    => $this->getRubyTextOpeningBracket(),
                );
            }

            if (strlen($this->getRubyTextClosingBracket())) {
                $item['rp_closing'] = array(
                    'name'    => 'rp',
                    'text'    => $this->getRubyTextClosingBracket(),
                );
            }

            $ruby[] = $item;
        }

        return array(
            'name'    => 'ruby',
            'handler' => 'ruby_element',
            'text'    => array(
                'ruby' => $ruby,
            ),
            'attributes' => $attributes,
        );
    }

    /*
     * return array(
     *   array('親文字1', '対応するルビ1'),
     *   array('親文字2', '対応するルビ2'),
     *   ...
     * );
     */
    protected function parseRubyText($kanji, $furigana)
    {
        // ルビが分かち書きされていれば分割する
        $rubiList = $this->splitRubyText($furigana);

        if (mb_strlen($kanji, 'UTF-8') != count($rubiList)) {
            /* ルビ分割数と親文字数が異なるならモノルビを
             * 意図した分かち書きではない可能性が高い。分割せずそのまま返す
             */
            return array(array($kanji, $this->normalizeRubyTextSutegana($furigana)));
        }

        $rubiList = array_map(array($this, 'normalizeRubyTextSutegana'), $rubiList);

        // ルビの分割数と親文字数が一致すれば各要素を対応付ける
        return array_map(null, preg_split('//u', $kanji, -1, PREG_SPLIT_NO_EMPTY), $rubiList);
    }

    protected function splitRubyText($furigana)
    {
        $s = $this->getRubyTextSeparator();

        if ($s == '') {
            return array($furigana);
        }

        return explode($s, $furigana);
    }

    /*
     * 捨て仮名自動変換モードではルビに含まれる小書き文字を並字に変換する。
     *
     * ルビは小さいフォントで表示されるので印刷媒体などの慣例では
     * 読みやすさの観点からルビに小書き文字(ex. 'ぁ')を使わず
     * 並字(ex. 'あ')を使う傾向にある。
     * (例: 東京都 => "とうきようと", 百科事典 => "ひやつかじてん")
     *
     * HTMLを印刷目的で利用するためにこの慣例に従う場合でも
     * 自動変換モードに設定しておくことでMarkdown側では普通に
     * 小字を使って入力しておくことが可能になる。
     *
     * デフォルトは捨て仮名許可モードであり何も変換しない。
     * 捨て仮名自動変換モードは以下で設定する:
     *
     *     $this->setRubyTextSuteganaAllowed(false);
     *
     * 注意:
     * このメソッドは自動変換モードであればルビが<code>ブロック
     * として書かれていても小書き文字をすべて強制的に変換する。
     */
    protected function normalizeRubyTextSutegana($furigana)
    {
        if ($this->isRubyTextSuteganaAllowed()) {
            return $furigana;
        }

        return str_replace($this->ruby_text_Sutegana['from'], $this->ruby_text_Sutegana['to'], $furigana);
    }

    /*
     * return array(
     *   'id'         => 'id',
     *   'class'      => 'class1 class2 ...',
     *   'attr-name1' => 'attr-value1',
     *   'attr-name2' => 'attr-value2',
     *   ...
     * );
     */
    protected function parseRubyTextAttributeData($attributeString)
    {
        $attributes = array('id' => null, 'class' => null);

        foreach (preg_split('/\s+/', $attributeString, -1, PREG_SPLIT_NO_EMPTY) as $item) {
            if ($item[0] == '#') {
                // #id
                $attributes['id'] = substr($item, 1);
            } elseif ($item[0] == '.') {
                // .class
                $attributes['class'] .= ' ' . substr($item, 1);
            } else {
                // attr=val or attr
                $a = explode('=', $item, 2);

                if (isset($a[1])) {
                    $attributes[$a[0]] = $a[1];
                } else {
                    $attributes[$a[0]] = $a[0];
                }
            }
        }

        if (isset($attributes['class'])) {
            $attributes['class'] = ltrim($attributes['class']);
        }

        return $attributes;
    }

    // handler
    protected function ruby_element(array $Element)
    {
        $markup = '';

        /* ルビ用HTMLタグを作る
         *   <ruby>  -- これはelement()が既に付与した
         *     // この内部を用意する
         *     $Element['ruby'][0]['base']<rp>（</rp><rt>$Element['ruby'][0]['rt']</rt><rp>）</rp>
         *     $Element['ruby'][1]['base']<rp>（</rp><rt>$Element['ruby'][1]['rt']</rt><rp>）</rp>
         *     ...
         *   </ruby> -- これはelement()が後で付与する
         */
        foreach ($Element['ruby'] as $ruby) {
            $markup .= $this->line($ruby['base']);

            if (!strlen($ruby['rt']['text'])) {
                /* [振り仮名]^(ふ  が な) のようにルビの一部を省略しても
                 * 親文字との対応がズレないように空の<rt>を置く
                 */
                $markup .= $this->element($ruby['rt']);
                continue;
            }

            if (isset($ruby['rp_opening'])) {
                $markup .= $this->element($ruby['rp_opening']);
            }

            $markup .= $this->element($ruby['rt']);

            if (isset($ruby['rp_closing'])) {
                $markup .= $this->element($ruby['rp_closing']);
            }
        }

        return $markup;
    }

    protected function getRubyTextElementBuilderName()
    {
        return $this->ruby_text_ElementBuilderName;
    }

    protected function setRubyTextElementBuilderName($name)
    {
        $old = $this->ruby_text_ElementBuilderName;
        $this->ruby_text_ElementBuilderName = $name;
        return $old;
    }

    public function getRubyTextOpeningBracket()
    {
        return $this->ruby_text_OpeningBracket;
    }

    public function setRubyTextOpeningBracket($bracket)
    {
        $this->ruby_text_OpeningBracket = $bracket;
        return $this;
    }

    public function getRubyTextClosingBracket()
    {
        return $this->ruby_text_ClosingBracket;
    }

    public function setRubyTextClosingBracket($bracket)
    {
        $this->ruby_text_ClosingBracket = $bracket;
        return $this;
    }

    public function setRubyTextBrackets($opening, $closing)
    {
        $this->setRubyTextOpeningBracket($opening);
        $this->setRubyTextClosingBracket($closing);
        return $this;
    }

    public function getRubyTextBrackets()
    {
        return array($this->getRubyTextOpeningBracket(), $this->getRubyTextClosingBracket());
    }

    public function getRubyTextSeparator()
    {
        return $this->ruby_text_MonoRubySeparator;
    }

    public function setRubyTextSeparator($separator)
    {
        $this->ruby_text_MonoRubySeparator = $separator;
        return $this;
    }

    public function setRubyTextSuteganaAllowed($bool)
    {
        $this->ruby_text_SuteganaAllowed = $bool;
        return $this;
    }

    public function isRubyTextSuteganaAllowed()
    {
        return $this->ruby_text_SuteganaAllowed;
    }

    public function setRubyTextEnabled($bool)
    {
        $this->ruby_text_ExtensionEnabled = $bool;
        return $this;
    }

    public function isRubyTextEnabled()
    {
        return $this->ruby_text_ExtensionEnabled;
    }

    abstract public function line($text);
    abstract protected function element(array $Element);

    protected $ruby_text_ElementBuilderName = 'buildRubyTextElementCallback';

    protected $ruby_text_Sutegana = array(
        // 小書き文字をfromに、並字をtoに置く。ペアの要素順は合わせること
        'from' => array('ぁ', 'ぃ', 'ぅ', 'ぇ', 'ぉ', 'っ', 'ゃ', 'ゅ', 'ょ', 'ゎ', 'ァ', 'ィ', 'ゥ', 'ェ', 'ォ', 'ヵ', 'ヶ', 'ッ', 'ャ', 'ュ', 'ョ', 'ヮ'),
        'to'   => array('あ', 'い', 'う', 'え', 'お', 'つ', 'や', 'ゆ', 'よ', 'わ', 'ア', 'イ', 'ウ', 'エ', 'オ', 'カ', 'ケ', 'ツ', 'ヤ', 'ユ', 'ヨ', 'ワ'),
        // 小さいクなどは文字化けしてしまった
    );
}
