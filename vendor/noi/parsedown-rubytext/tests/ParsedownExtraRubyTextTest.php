<?php
namespace Noi\Tests;

use Noi\ParsedownExtraRubyText;
use ParsedownExtraTest;

class ParsedownExtraRubyTextTest extends ParsedownExtraTest
{
    use RubyTextTestTrait;
    use RubyTextDefinitionTestTrait;

    protected function initParsedown()
    {
        $Parsedown = new ParsedownExtraRubyText();

        return $Parsedown;
    }
}
