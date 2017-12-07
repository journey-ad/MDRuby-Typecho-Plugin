<?php
namespace Noi\Tests\Pixiv;

trait PixivRubyTextTestTrait
{
    protected function initDirs()
    {
        $dirs = parent::initDirs();

        $dirs []= dirname(__FILE__).'/data/';

        return $dirs;
    }

    abstract protected function getParsedown();
}
