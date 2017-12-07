<?php
namespace Noi\Tests\Aozora;

use Noi\ParsedownExtraRubyText;
use Noi\Parsedown\AozoraRubyTextTrait;

class AozoraParsedownExtraTest extends \Noi\Tests\ParsedownExtraRubyTextTest
{
    use AozoraRubyTextTestTrait;

    protected function initParsedown()
    {
        $Parsedown = new AozoraParsedownExtra();

        return $Parsedown;
    }
}

class AozoraParsedownExtra extends ParsedownExtraRubyText
{
    use AozoraRubyTextTrait;

    public function __construct()
    {
        parent::__construct();
        $this->registerAozoraRubyTextExtension();
    }
}
