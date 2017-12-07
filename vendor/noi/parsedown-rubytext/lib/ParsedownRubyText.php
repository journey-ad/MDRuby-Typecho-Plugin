<?php
namespace Noi;

use Parsedown;
use Noi\Parsedown\RubyTextTrait;
use Noi\Parsedown\RubyTextDefinitionTrait;

/**
 * Parsedown ルビ用拡張記法Extension実装クラス
 *
 * Markdown:
 *   1. [親文字]^(ルビ)  -- inlineルビ形式 [base]^(ruby)
 *   2. [親文字]^（ルビ）-- ^あり全角括弧形式
 *   3. [親文字]（ルビ） -- ^なし全角括弧形式
 *   4. **[親文字]: ルビ -- ルビ定義形式。Link定義のように1行で書いてください
 *                          文書内の "親文字" にルビを振ります
 *
 *   // HTML:
 *   <ruby>親文字<rp>（</rp><rt>ルビ<rt><rp>）</rp></ruby>
 *   <ruby>base<rp>（</rp><rt>ruby<rt><rp>）</rp></ruby>
 *
 * Usage:
 *   $p = new Noi\ParsedownRubyText();
 *   echo $p->text('Parsedownはとても[便利]^(べんり)');
 *   // Output:
 *   <p>Parsedownはとても<ruby>便利<rp>（</rp><rt>べんり</rt><rp>）</rp></ruby></p>
 *
 * ルビ用拡張記法の詳細は以下のクラスで確認してください。
 * @see \Noi\Parsedown\RubyTextTrait
 * @see \Noi\Parsedown\RubyTextDefinitionTrait
 *
 * ParsedownExtraから派生した実装クラスもあります。
 * @see \Noi\ParsedownExtraRubyText
 *
 * @copyright Copyright (c) 2015 Akihiro Yamanoi
 * @license MIT
 *
 * For the full license information, view the LICENSE file that was distributed
 * with this source code.
 */
class ParsedownRubyText extends Parsedown
{
    use RubyTextTrait;
    use RubyTextDefinitionTrait;

    public function __construct()
    {
        $this->registerRubyTextExtension();
        $this->registerRubyTextDefinitionExtension();
    }
}
