<?php
namespace Noi\Tests\Aozora;

trait AozoraRubyTextTestTrait
{
    protected function initDirs()
    {
        $dirs = parent::initDirs();

        $dirs []= dirname(__FILE__).'/data/';

        return $dirs;
    }

    abstract protected function getParsedown();
}
