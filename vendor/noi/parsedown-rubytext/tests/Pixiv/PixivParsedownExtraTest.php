<?php
namespace Noi\Tests\Pixiv;

use Noi\ParsedownExtraRubyText;
use Noi\Parsedown\PixivRubyTextTrait;

class PixivParsedownExtraTest extends \Noi\Tests\ParsedownExtraRubyTextTest
{
    use PixivRubyTextTestTrait;

    protected function initParsedown()
    {
        $Parsedown = new PixivParsedownExtra();

        return $Parsedown;
    }
}

class PixivParsedownExtra extends ParsedownExtraRubyText
{
    use PixivRubyTextTrait;

    public function __construct()
    {
        parent::__construct();
        $this->registerPixivRubyTextExtension();
    }
}
