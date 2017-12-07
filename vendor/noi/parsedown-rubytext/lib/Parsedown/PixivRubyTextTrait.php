<?php
namespace Noi\Parsedown;

/**
 * Parsedown Pixivルビ形式Extension
 *
 * Parsedown継承クラスに「Pixivルビ形式」を
 * Markdownの拡張記法として導入します。
 * traitなので親クラスは自由に選択できます。
 *
 * Markdown:
 *
 *   * [[rb: 親文字 > ルビ ]] -- Pixivルビ形式
 *
 *     // HTML:
 *     <ruby>親文字<rp>（</rp><rt>ルビ<rt><rp>）</rp></ruby>
 *
 * Pixivルビ形式は、pixiv.netの投稿小説で採用されているルビ形式です。
 * このExtensionを組み込むことで、pixiv.net風のルビ指定が可能になります。
 *
 * 入力書式の具体例はピクシブ公式サイトで確認してください。
 * @see http://www.pixiv.net/
 * @see http://help.pixiv.net/202/
 * @see http://www.pixiv.net/novel/show.php?id=4701718
 *
 * このExtensionの目的は、ピクシブ投稿用の原稿データを
 * Markdownとしても利用しやすくすることです。
 *
 * 動作は \Noi\Parsedown\RubyTextTrait に依存しています。
 * あなた独自のParsedown派生クラスにPixivルビ形式を導入するには
 * 以下のUsageを参考にして両方のtraitを組み込んでください。
 *
 * Usage:
 *
 *     class YourParsedown extends Parsedown [ or ParsedownExtra or etc. ] {
 *       // 1. RubyTextTrait と PixivRubyTextTrait をuse
 *       use \Noi\Parsedown\RubyTextTrait;
 *       use \Noi\Parsedown\PixivRubyTextTrait;
 *
 *       // 2. registerPixivRubyTextExtension()をコンストラクタかどこかで実行
 *       public function __construct() {
 *         parent::__construct(); // 必要であれば呼ぶ
 *
 *         $this->registerPixivRubyTextExtension();
 *
 *         // 3. RubyTextTraitの拡張記法 "[親文字]^(ルビ)" が不要なら以下は省略可能
 *         // $this->registerRubyTextExtension();
 *       }
 *     }
 *
 *     $p = new YourParsedown();
 *     echo $p->text('Parsedownはとても[[rb:便利 > べんり]]');
 *     // Output:
 *     <p>Parsedownはとても<ruby>便利<rp>（</rp><rt>べんり</rt><rp>）</rp></ruby></p>
 *
 * \Noi\ParsedownRubyText または \Noi\ParsedownExtraRubyText は
 * 既に RubyTextTrait を組み込み済みです。これらから派生させた
 * 独自クラスに組み込む場合、useするtraitはこのExtension
 * (\Noi\Parsedown\PixivRubyTextTrait)だけでOKです。
 *
 * PixivRubyTextTrait は RubyTextTrait のルビ振り機能に
 * 依存して動作するため、モノルビ割り当ても使用可能です。
 * (これはPixivルビ形式の公式仕様ではありません)
 *
 * 例:
 *     // markdown:
 *     [[rb: 日本語 > に ほん ご ]]
 *
 *     // html:
 *     <ruby>
 *       日<rp>（</rp><rt>に<rt><rp>）</rp>
 *       本<rp>（</rp><rt>ほん<rt><rp>）</rp>
 *       語<rp>（</rp><rt>ご<rt><rp>）</rp>
 *     </ruby>
 *     (実際の出力は1行)
 *
 * また、ルビに属性値を追加設定することもできます。
 * Markdown Extra "Special Attributes" と同じ書式です。
 * (これもPixivルビ形式の公式仕様ではなく独自拡張です)
 *
 * 例:
 *     // markdown:
 *     [[rb: 日本語 > にほんご]]{#id .classA .classB lang=ja}
 *
 *     // html:
 *     <ruby id="id" class="classA classB" lang="ja">日本語<rp>（</rp><rt>にほんご</rt><rp>）</rp></ruby>
 *
 * @see \Noi\Parsedown\RubyTextTrait
 * @see \Noi\ParsedownRubyText
 * @see \Noi\ParsedownExtraRubyText
 *
 * @copyright Copyright (c) 2015 Akihiro Yamanoi
 * @license MIT
 *
 * For the full license information, view the LICENSE file that was distributed
 * with this source code.
 */
trait PixivRubyTextTrait
{
    private $pixiv_ruby_text_ExtensionEnabled    = true;
    private $pixiv_ruby_text_ExtensionRegistered = false;

    public function registerPixivRubyTextExtension()
    {
        if ($this->pixiv_ruby_text_ExtensionRegistered) {
            return;
        }

        $this->pixiv_ruby_text_ExtensionRegistered = true;

        $this->InlineTypes['['][] = 'PixivRubyText';
        $this->inlineMarkerList = join('', array_keys($this->InlineTypes));
    }

    /**
     * Pixivルビ形式を解析してルビ情報を返す。
     *
     * このinline処理メソッドは '[' をトリガー文字として動作する。
     * 実際のMarkdown書式は以下の通り:
     *
     *     [[rb: 親文字 > ルビ ]]
     *
     *   * "[[rb:"  - 開始括弧の各文字の間は空白を許可しない。
     *   * "]]"     - (同上)
     *   * "親文字" - 親文字の前後にある空白は無視する。
     *   * "ルビ"   - ルビの前後にある空白は無視する。
     *   * ">"      - 親文字とルビは ">" で区切る。
     *                もしも ">" が複数あるなら
     *                最後の1つを区切りに使用して
     *                それ以外は親文字の一部とみなす。
     *                (例: "[[rb: 親文字A>親文字B>親文字C>ルビ ]]")
     *
     * なお、このExtensionは、pixiv.net公式仕様とは異なり
     * 空ルビを許可する。
     * これはRubyTextTraitの仕様に従うため。
     *
     * 2015年9月19日時点のピクシブ小説の仕様では
     * ">" の後が空白の場合は無効な指定とみなされ平文になる。
     * (例: "[[rb: 無効 > ]]", "[[rb: これ > も > 無効 > ]]")
     */
    protected function inlinePixivRubyText($Excerpt)
    {
        if (!$this->isPixivRubyTextEnabled()) {
            return null;
        }

        if (!$this->matchPixivRubyTextMarkdown($Excerpt['text'], $kanji, $furigana, $extent)) {
            return null;
        }

        /*
         * 追加の属性値があれば切り出す。
         *
         * 属性値はルビの閉じ括弧の直後に "{...}" の形式で指定する:
         *
         *     [[rb: 親文字 > ふりがな]]{#id .class lang=ja}
         *
         * 属性値指定はPixivルビ形式の公式仕様ではなく
         * このExtensionの独自仕様。
         */
        if ($this->matchRubyTextAttributes($Excerpt['text'], $extent, $attributes, $additional)) {
            $extent += $additional;
        }

        $position = strlen($Excerpt['context']) - strlen($Excerpt['text']);

        return array(
            'extent'   => $extent,
            'element'  => $this->buildRubyTextElement($kanji, $furigana, $attributes, $Excerpt['context'], $position, $extent),
        );
    }

    protected function matchPixivRubyTextMarkdown($text, /* out */ &$kanji, /* out */ &$furigana, /* out */ &$extent)
    {
        if (!preg_match('/\[\[rb:(.*?)\]\]/Au', $text, $m)) {
            // 無効な書式: 正しい括弧がない
            return false;
        }

        $pos = strrpos($m[1], '>');
        if ($pos === false) {
            // 無効な書式: ">" がない
            return false;
        }

        $tmp_kanji = trim(substr($m[1], 0, $pos));
        if ($tmp_kanji === '') {
            // 無効な書式: 親文字が空
            return false;
        }

        // 有効な書式
        $kanji    = $tmp_kanji;
        $furigana = trim(substr($m[1], $pos + 1));
        $extent   = strlen($m[0]);
        return true;
    }

    public function setPixivRubyTextEnabled($bool)
    {
        $this->pixiv_ruby_text_ExtensionEnabled = $bool;
        return $this;
    }

    public function isPixivRubyTextEnabled()
    {
        return $this->pixiv_ruby_text_ExtensionEnabled;
    }

    abstract protected function matchRubyTextAttributes($target, $offset, /* out */ &$attributes, /* out */ &$extent);
    abstract protected function parseRubyTextAttributeData($attributeString);
    abstract protected function buildRubyTextElement($kanji, $furigana, $attributes = null, $context = null, $position = null, $extent = null);
}
