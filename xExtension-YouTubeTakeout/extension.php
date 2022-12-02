<?php

/**
 * This extension imports a Google Takeout export, more specifically the CSV export of YouTube subscriptions.
 * 
 * YTTakeoutExtension sets up the "FreshExtension_yttakeout_Controller" controller, and adds an entry to the
 * "Configuration" menu to run the import.
 * 
 * Latest version at: https://github.com/simsor/freshrss-extensions/tree/main/xExtension-YouTubeTakeout
 * 
 * @author Simon Garrelou
 */
class YTTakeoutExtension extends Minz_Extension
{
	public function init()
	{
		$this->registerController("yttakeout");
		$this->registerViews();

		$this->registerHook("menu_configuration_entry", array($this, "configureEntry"));
	}

	public function configureEntry()
	{
		$u = [
			'c' => 'yttakeout',
			'a' => 'import'
		];
		$active = Minz_Request::controllerName() == "yttakeout" ? "active" : "";

		return '<li class="item '. $active .'"><a href="' . Minz_Url::display($u) . '">YouTube Takeout</a></li>';
	}
}

?>
