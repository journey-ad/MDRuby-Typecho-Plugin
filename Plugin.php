<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * MarkDown Ruby 语法扩展支持
 * 
 * @package MDRuby
 * @author journey.ad
 * @version 0.1
 * @link https://imjad.cn
 */
class MDRuby_Plugin implements Typecho_Plugin_Interface
{
	/**
	 * 激活插件方法,如果激活失败,直接抛出异常
	 * 
	 * @access public
	 * @return void
	 * @throws Typecho_Plugin_Exception
	 */
	public static function activate()
	{
		Typecho_Plugin::factory('Widget_Abstract_Contents')->markdown = ['MDRuby_Plugin', 'parse'];
		Typecho_Plugin::factory('Widget_Archive')->header = array('MDRuby_Plugin','header');
	}
	
	/**
	 * 禁用插件方法,如果禁用失败,直接抛出异常
	 * 
	 * @static
	 * @access public
	 * @return void
	 * @throws Typecho_Plugin_Exception
	 */
	public static function deactivate() {}
	
	/**
	 * 获取插件配置面板
	 * 
	 * @access public
	 * @param Typecho_Widget_Helper_Form $form 配置面板
	 * @return void
	 */
	public static function config(Typecho_Widget_Helper_Form $form)
	{
		echo "语法:<br>[原文]^(yuán wén)<br>[原文]^（yuán wén） （使用全角括号）<br>[原文]（yuán wén） （使用全角括号";
		$auto = new Typecho_Widget_Helper_Form_Element_Radio('auto',
			array(
				'1' => '是',
				'0' => '否',
			),'1', _t('自动转换词汇表'), _t('启用后将自动为文章中第一个出现的词汇加上拼写，词汇表可通过编辑 assets/words.json 配置'));
		$form->addInput($auto);

		$style = new Typecho_Widget_Helper_Form_Element_Textarea('style', null, 'ruby {
    background-color: rgba(146, 185, 204, 0.2);
    margin: 4px;
    padding: 0 2px;
    border-radius: 4px;
}
ruby > rt {
    font-style: italic;
    color: #6a8998;
    margin-right: 2px;
}', _t('自定义样式'), _t('请填入标准的 CSS 样式，为空则不启用<br>以下是例子<br>ruby {<br>&nbsp;&nbsp;&nbsp;&nbsp;background-color: rgba(146, 185, 204, 0.2);<br>&nbsp;&nbsp;&nbsp;&nbsp;margin: 4px;<br>&nbsp;&nbsp;&nbsp;&nbsp;padding: 0 2px;<br>&nbsp;&nbsp;&nbsp;&nbsp;border-radius: 4px;<br>}<br>ruby &gt; rt {<br>&nbsp;&nbsp;&nbsp;&nbsp;font-style: italic;<br>&nbsp;&nbsp;&nbsp;&nbsp;color: #6a8998;<br>&nbsp;&nbsp;&nbsp;&nbsp;margin-right: 2px;<br>}'));
		$form->addInput($style);
	}
	
	/**
	 * 个人用户的配置面板
	 * 
	 * @access public
	 * @param Typecho_Widget_Helper_Form $form
	 * @return void
	 */
	public static function personalConfig(Typecho_Widget_Helper_Form $form) {}
	
	public static function parse($text)
	{
		$config = Typecho_Widget::widget('Widget_Options')->plugin('MDRuby');
		$auto = $config->auto;

		require_once dirname(__FILE__) . '/vendor/autoload.php';

		if($auto == 1){
			$words = file_get_contents(dirname(__FILE__) . '/assets/words.json');
			$words = json_decode($words);
			
			foreach ($words as $key => $value) {
				$text = self::str_replace_first($key, "[{$key}]^({$value})", $text);
			}
		}

		$pd = new \Noi\ParsedownExtraRubyText();
		
		$content = $pd->text($text);
		
		return $content;
	}

	public static function header()
	{
		$config = Typecho_Widget::widget('Widget_Options')->plugin('MDRuby');
		$style = $config->style;
		
		if (!empty($style)) {
			echo "<style>{$style}</style>";
		}
	}
	
	private static function str_replace_first($search, $replace, $subject) {
		$pos = strpos($subject, $search);
		if ($pos !== false) {
			return substr_replace($subject, $replace, $pos, strlen($search));
		}
		return $subject;
	}
}
