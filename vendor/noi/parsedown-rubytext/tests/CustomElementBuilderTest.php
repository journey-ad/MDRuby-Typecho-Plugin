<?php
namespace Noi\Tests;

use Noi\ParsedownRubyText;
use Noi\ParsedownExtraRubyText;

trait CustomElementBuilderTestTrait
{
    /** @test */
    public function 独自ハンドラでルビ要素を置換できる()
    {
        $markdown = '[東京]^(とうきょう)';
        $default  = '<ruby>東京<rt>とうきょう</rt></ruby>';
        $expected = '<ruby>東京<rt>Tokyo</rt></ruby>';

        // 独自ハンドラに置換させる: "とうきょう" => "Tokyo"
        $this->parsedown->setRuby('東京', 'Tokyo');

        // 以下は見やすさのため
        $this->parsedown->setRubyTextBrackets(null, null);

        // test: default
        $this->assertEquals($default, $this->parsedown->line($markdown));

        // test: MyElementBuilder
        $this->parsedown->registerMyElementBuilder();
        $this->assertEquals($expected, $this->parsedown->line($markdown));
    }

    /** @test */
    public function 独自ハンドラに処理中の文字列と開始オフセットと処理済みの長さ情報を渡す()
    {
        $markdown         = 'TEST[確認]^(かくにん)test';
        $expectedContext  = 'TEST[確認]^(かくにん)test';
        $expectedPosition = 4;
        $expectedExtent   = strlen('[確認]^(かくにん)');

        $this->parsedown->registerMyElementBuilder();
        $this->parsedown->line($markdown);

        // 追加引数の確認
        $this->assertEquals($expectedContext,  $this->parsedown->context);
        $this->assertEquals($expectedPosition, $this->parsedown->position);
        $this->assertEquals($expectedExtent,   $this->parsedown->extent);
    }

    abstract public function initParsedown();

    public function setUp()
    {
        $this->parsedown = $this->initParsedown();
    }

    protected $parsedown;
}

trait MyElementBuilderTrait
{
    private $orig_handler;
    private $table;

    public $context;
    public $position;
    public $extent;

    // 独自のElementBuilderの例: 登録済みルビを置換するハンドラ
    protected function my_handler($kanji, $furigana, $attributes, $context, $position, $extent)
    {
        if (isset($this->table[$kanji])) {
            $furigana = $this->table[$kanji];
        }

        $this->context  = $context;
        $this->position = $position;
        $this->extent   = $extent;

        return $this->{$this->orig_handler}($kanji, $furigana, $attributes);
    }

    public function setRuby($base, $ruby)
    {
        $this->table[$base] = $ruby;
    }

    public function registerMyElementBuilder()
    {
        $this->orig_handler = $this->setRubyTextElementBuilderName('my_handler');
    }
}

class CustomElementBuilderParsedownRubyTextTest extends \PHPUnit_Framework_TestCase
{
    use CustomElementBuilderTestTrait;

    protected function initParsedown()
    {
        return new MyElementBuilder_ParsedownRubyText();
    }
}

class CustomElementBuilderParsedownExtraRubyTextTest extends \PHPUnit_Framework_TestCase
{
    use CustomElementBuilderTestTrait;

    protected function initParsedown()
    {
        return new MyElementBuilder_ParsedownExtraRubyText();
    }
}

// Parsedown
class MyElementBuilder_ParsedownRubyText extends ParsedownRubyText
{
    use MyElementBuilderTrait;
}

// ParsedownExtra
class MyElementBuilder_ParsedownExtraRubyText extends ParsedownExtraRubyText
{
    use MyElementBuilderTrait;
}
