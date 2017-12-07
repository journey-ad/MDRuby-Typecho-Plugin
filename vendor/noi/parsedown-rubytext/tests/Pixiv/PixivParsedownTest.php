<?php
namespace Noi\Tests\Pixiv;

use Noi\ParsedownRubyText;
use Noi\Parsedown\PixivRubyTextTrait;

class PixivParsedownTest extends \Noi\Tests\ParsedownRubyTextTest
{
    use PixivRubyTextTestTrait;

    protected function initParsedown()
    {
        $Parsedown = new PixivParsedown();

        return $Parsedown;
    }
}

class PixivParsedown extends ParsedownRubyText
{
    use PixivRubyTextTrait;

    public function __construct()
    {
        parent::__construct();
        $this->registerPixivRubyTextExtension();
    }
}
