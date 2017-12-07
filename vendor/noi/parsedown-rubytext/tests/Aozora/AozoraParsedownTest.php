<?php
namespace Noi\Tests\Aozora;

use Noi\ParsedownRubyText;
use Noi\Parsedown\AozoraRubyTextTrait;

class AozoraParsedownTest extends \Noi\Tests\ParsedownRubyTextTest
{
    use AozoraRubyTextTestTrait;

    protected function initParsedown()
    {
        $Parsedown = new AozoraParsedown();

        return $Parsedown;
    }
}

class AozoraParsedown extends ParsedownRubyText
{
    use AozoraRubyTextTrait;

    public function __construct()
    {
        parent::__construct();
        $this->registerAozoraRubyTextExtension();
    }
}
