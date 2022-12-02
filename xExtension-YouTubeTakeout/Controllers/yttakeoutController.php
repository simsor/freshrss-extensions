<?php

/**
 * This class handles the "yttakeout" controller. It exposes a single action, "import", which
 * takes in input a CSV file containing a Google Takeout export of a user's Youtube subscriptions.
 * 
 * The file is then converted to OPML and imported in FreshRSS, in the given category.
 * 
 * @author Simon Garrelou
 */
class FreshExtension_yttakeout_Controller extends FreshRSS_ActionController {
    public function firstAction() {
		if (!FreshRSS_Auth::hasAccess()) {
			Minz_Error::error(403);
		}

		require_once(LIB_PATH . '/lib_opml.php');
	}

    /**
     * This action relies on the "import.phtml" view.
     * If it is invoked from a POST request, it expects the following parameters:
     * - "name": string representing the name of the category where the Youtube feeds will be inserted
     * - "subscriptions_csv": a file, in CSV format with a header, containing all the user subscriptions.
     * 
     * The CSV file is expected to have the following format:
     *      channel_id,channel_url,channel_name
     */
    public function importAction() {
        FreshRSS_View::prependTitle("YouTube Takeout · ");
        if (Minz_Request::isPost()) {
            $username = Minz_Session::param('currentUser');
            $opml = $this->loadCsvFile();
            $importService = new FreshRSS_Import_Service($username);

            $importService->importOpml($opml);
			if (!$importService->lastStatus()) {
				$ok = false;
				if (FreshRSS_Context::$isCli) {
					fwrite(STDERR, 'FreshRSS error during OPML import' . "\n");
				} else {
					Minz_Log::warning('Error during OPML import');
				}

                $this->view->notification = [
                    "type" => "bad",
                    "content" => "Error importing Takeout file."
                ];
			} else {
                $this->view->notification = [
                    "type" => "good",
                    "content" => "Import successful."
                ];

                Minz_Session::_param('actualize_feeds', true);
            }
        }
    }

    private function loadCsvFile()
	{
        $cat_name = (string)Minz_Request::param("name", "YouTubeTakeout");
        $f = $_FILES["subscriptions_csv"];
        $h = fopen($f["tmp_name"], "r");
        
        if ($h === false) {
            echo "Could not open tmp file!";
            return;
        }

        // Skip header
        if (fgetcsv($h) === false) {
            echo "Could not read first line";
            return;
        }

        $entries = [];
        while (($line = fgetcsv($h)) !== false) {
            if ($line === "") {
                continue;
            }
            $channel_name = $line[2];
            $channel_url = $line[1];
            $feed_url = 'https://www.youtube.com/feeds/videos.xml?channel_id=' . $line[0];

            $entries[] = [$channel_name, $feed_url, $channel_url];
        }

        fclose($h);

        return $this->buildOPML($cat_name, $entries);
	}

	private function buildOPML($cat_name, $entries)
	{
		$date = date("D, d M Y H:i:s");
		$root = new SimpleXMLElement('<opml />');
        $root->addAttribute("version", "2.0");
			$head = $root->addChild("head");
				$head->addChild("title", "YouTube Takeout");
				$head->addChild("dateCreated", $date);
			$body = $root->addChild("body");
				$outline = $body->addChild("outline");
                $outline->addAttribute("text", $cat_name);

		foreach ($entries as $entry) {
			$channel_name = $entry[0];
			$feed_url = $entry[1];
			$channel_url = $entry[2];

			$new_outline = $outline->addChild("outline");
			$new_outline->addAttribute("text", $channel_name);
			$new_outline->addAttribute("type", "rss");
			$new_outline->addAttribute("xmlUrl", $feed_url);
			$new_outline->addAttribute("htmlUrl", $channel_url);
		}

		return $root->asXML();
	}
}

?>