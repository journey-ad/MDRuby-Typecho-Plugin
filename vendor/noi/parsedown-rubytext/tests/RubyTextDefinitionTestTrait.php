<?php
namespace Noi\Tests;

trait RubyTextDefinitionTestTrait
{
    /** @test */
    public function 初出の単語だけにルビを振る_MarkupAllモードを解除()
    {
        $markdown = file_get_contents(dirname(__FILE__) . '/data/definition.md');
        $expected = file_get_contents(dirname(__FILE__) . '/data/definition.first.html');

        $this->getParsedown()->setRubyTextDefinitionMarkupAll(false);

        $this->assertEquals($expected, $this->getParsedown()->text($markdown));
    }

    /** @test */
    public function 空ルビよりも前にある平文の単語にルビを振る_MarkupAllモードを解除()
    {
        $markdown = '前方の平文の漢字[漢字]^()後方の平文の漢字' .
                    "\n\n" .
                    '**[漢字]: かんじ';
        $expected = '<p>前方の平文の<ruby>漢字<rt>かんじ</rt></ruby><ruby>漢字<rt></rt></ruby>後方の平文の漢字</p>';

        $this->getParsedown()->setRubyTextDefinitionMarkupAll(false);

        // 見やすさのため
        $this->getParsedown()->setRubyTextBrackets(null, null);
        $this->assertEquals($expected, $this->getParsedown()->text($markdown));
    }

    /** @test */
    public function 初出がinlineルビの単語は自動ルビ振りをしない_MarkupAllモードを解除()
    {
        $markdown = '[手動]^(しゅどう)inline指定したルビ' .
                    "\n\n" .
                    '[確認]^()空ルビ指定したルビ' .
                    "\n\n" .
                    '**[手動]: unused' . "\n" .
                    '**[確認]: かくにん';
        $expected = '<p><ruby>手動<rt>しゅどう</rt></ruby>inline指定したルビ</p>' . "\n" .
                    '<p><ruby>確認<rt>かくにん</rt></ruby>空ルビ指定したルビ</p>';

        $this->getParsedown()->setRubyTextDefinitionMarkupAll(false);

        // 見やすさのため
        $this->getParsedown()->setRubyTextBrackets(null, null);
        $this->assertEquals($expected, $this->getParsedown()->text($markdown));
    }

    /** @test */
    public function 初出以降でも明示的に指定すればinlineルビでふりがなを振れる_MarkupAllモードを解除()
    {
        $markdown = '宅配ピザ[宅配]^(たくはい)寿司宅配そば[宅配]^(タクハイ)カレー' .
                    "\n\n" .
                    '**[宅配]: たくはい';
        $expected = '<p>' .
                      '<ruby>宅配<rt>たくはい</rt></ruby>ピザ' .
                      '<ruby>宅配<rt>たくはい</rt></ruby>寿司' .
                      '宅配そば' .
                      '<ruby>宅配<rt>タクハイ</rt></ruby>カレー' .
                    '</p>';

        $this->getParsedown()->setRubyTextDefinitionMarkupAll(false);

        // 見やすさのため
        $this->getParsedown()->setRubyTextBrackets(null, null);
        $this->assertEquals($expected, $this->getParsedown()->text($markdown));
    }

    abstract protected function getParsedown();
}
