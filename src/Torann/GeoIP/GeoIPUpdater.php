<?php namespace Torann\GeoIP;

use GuzzleHttp\Client as Client;
use Illuminate\Config\Repository;

class GeoIPUpdater
{
	/**
	 * @var array
	 */
	protected $config;

	/**
	 * @var Client
	 */
	protected $client;

	/**
	 * @param array $config
	 */
	public function __construct(Repository $config)
	{
		$this->config = $config;
	}

	/**
	 * Main update function.
	 *
	 * @return bool|string
	 */
	public function update()
	{
		if ($this->config->get('geoip.maxmind.database_path', false)) {
			return $this->updateMaxMindDatabase();
		}

		return false;
	}

	/**
	 * Update function for max mind database.
	 *
	 * @return string
	 */
	protected function updateMaxMindDatabase()
	{
		$maxMindDatabaseUrl = $this->config->get('geoip.maxmind.update_url');
		$databasePath = $this->config->get('geoip.maxmind.database_path');
		$tmpPath = $this->config->get('geoip.maxmind.tmp_path');
		$mmdb = $tmpPath = $this->config->get('geoip.maxmind.mmdb');


      	if(!is_dir($tmpPath)) {
			mkdir($tmpPath);
		}

		$tmp = $tmpPath . 'tmp.tar.gz';
        file_put_contents($tmp, fopen($maxMindDatabaseUrl, 'r'));

        $archive = new PharData($tmp);
        $archive->extractTo($tmpPath);
		$contents = scandir($tmpPath);
		$tmp_dir;
		foreach ($contents as $content) {
		    if ($content!= '.' &&  $content != '..'){
			    if(is_dir($tmpPath .$content)) {
			    	$tmp_dir = $tmpPath .$content;
			    	file_put_contents($databasePath,  fopen($tmpPath .$content.'/' . $mmdb, 'r'));
			   	}
			}
		}
		
		unlink($tmp);
		foreach (scandir($tmp_dir) as $item) {
		    if ($item == '.' || $item == '..') continue;
		    unlink($tmp_dir.DIRECTORY_SEPARATOR.$item);
		}
		rmdir($tmp_dir);

		return $databasePath;
	}
}