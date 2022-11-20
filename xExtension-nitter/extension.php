<?php

/**
 * Changes all references to "twitter.com" to a specified Nitter instance.
 * 
 * Latest version at: https://github.com/simsor/freshrss-extensions/tree/main/xExtension-nitter
 * 
 * @author Simon Garrelou
 */
class NitterExtension extends Minz_Extension
{
	/**
	 * The Nitter instance to redirect to. Can be changed in the settings
	 * @var string
	 */
	protected $instance = "nitter.net";

	public function init()
	{
		$this->registerHook('entry_before_display', array($this, 'changeTwitterURL'));
	}

	public function loadConfigValues()
	{
		if (!class_exists('FreshRSS_Context', false) || null === FreshRSS_Context::$user_conf) {
			return;
        }

		if (FreshRSS_Context::$user_conf->nitter_instance != '') {
			$this->instance = FreshRSS_Context::$user_conf->nitter_instance;
		}
	}

	public function changeTwitterURL($entry)
	{
		$this->loadConfigValues();

		$url = $entry->link();
		if (!$this->isTwitterURL($url)) {
			return $entry;
		}

		$url = str_replace("www.twitter.com", $this->instance, $url);
		$url = str_replace("twitter.com", $this->instance, $url);

		$entry->_link($url);

		return $entry;
	}

	private function isTwitterURL($url)
	{
		return stripos($url, "twitter.com") != false;
	}

	public function handleConfigureAction()
	{
		$this->loadConfigValues();

		if (Minz_Request::isPost()) {
			FreshRSS_Context::$user_conf->nitter_instance = (string)Minz_Request::param("instance", "");
			FreshRSS_Context::$user_conf->save();
		}
	}
}

?>
