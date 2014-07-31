<?php
// Datasource plugin for influxdb, target is defined as:
// influxdb:host:database:username:password:seriesin:seriesout

class WeatherMapDataSource_influxdb extends WeatherMapDataSource {
	
	private $regex_pattern = "/^influxdb:(.*):(.*):(.*):(.*):(.*):(.*)$/";

	function Init(&$map)
	{
		return(TRUE);
	}


	function Recognise($targetstring)
	{
		if(preg_match($this->regex_pattern,$targetstring,$matches))
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	function ReadData($targetstring, &$map, &$item)
	{
		$data[IN] = NULL;
		$data[OUT] = NULL;
		$data_time = time();

		if(preg_match($this->regex_pattern,$targetstring,$matches))
		{
			$host = $matches[1];
			$database = $matches[2];
			$username = urlencode($matches[3]);
			$password = urlencode($matches[4]);
			$seriesin = $matches[5];
			$seriesout = $matches[6];


			$buffer = "";
			$query = urlencode("select mean(value) from $seriesin where time > now() - 3m");
			$file = "http://$host:8086/db/$database/series?q=$query&u=$username&p=$password";
			$fp = fopen($file,"r");
			while (!feof($fp)) {
				$buffer .= fgets($fp,4096);
			}
			fclose($fp);
			$decoded = json_decode($buffer);
			$data[IN] = round($decoded[0]->points[0][1]);
			
			$buffer = "";
			$query = urlencode("select mean(value) from $seriesout where time > now() - 3m");
			$file = "http://$host:8086/db/$database/series?q=$query&u=$username&p=$password";
			$fp = fopen($file,"r");
			while (!feof($fp)) {
				$buffer .= fgets($fp,4096);
			}
			fclose($fp);
			$decoded = json_decode($buffer);
			$data[OUT] = round($decoded[0]->points[0][1]);
		}
		return( array($data[IN], $data[OUT], $data_time) );
	}
}
?>
