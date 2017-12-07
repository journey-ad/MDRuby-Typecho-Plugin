<?php
namespace Noi;

use ParsedownExtra;
use Noi\Parsedown\RubyTextTrait;
use Noi\Parsedown\RubyTextDefinitionTrait;

/**
 * ParsedownExtra ルビ用拡張記法Extension実装クラス
 *
 * @see \Noi\ParsedownRubyText
 * @see \Noi\Parsedown\RubyTextTrait
 * @see \Noi\Parsedown\RubyTextDefinitionTrait
 *
 * @copyright Copyright (c) 2015 Akihiro Yamanoi
 * @license MIT
 *
 * For the full license information, view the LICENSE file that was distributed
 * with this source code.
 */
class ParsedownExtraRubyText extends ParsedownExtra
{
    use RubyTextTrait;
    use RubyTextDefinitionTrait;

    public function __construct()
    {
        parent::__construct();
        $this->registerRubyTextExtension();
        $this->registerRubyTextDefinitionExtension();
    }
}
