<?php
/**
 * Convert a comma separated file into an associated array.
 * The first row should contain the array keys.
 * 
 * Example:
 * 
 * @param string $filename Path to the CSV file
 * @param string $delimiter The separator used in the file
 * @return array
 * @link http://gist.github.com/385876
 * @author Jay Williams <http://myd3.com/>
 * @copyright Copyright (c) 2010, Jay Williams
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */
function csv_to_array($filename='', $delimiter=',')
{
	if(!file_exists($filename) || !is_readable($filename))
		return FALSE;
	
	$header = NULL;
	$data = array();
	if (($handle = fopen($filename, 'r')) !== FALSE)
	{
		while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE)
		{
			if(!$header)
				$header = $row;
			else
				$data[] = array_combine($header, $row);
		}
		fclose($handle);
	}
	return $data;
}

$geoCodes = csv_to_array('postal-geo.csv');
$postalCodes = csv_to_array('postal-text.csv');

$indexedGeo = [];

foreach ($geoCodes as $geoCode) {
    $indexedGeo[$geoCode['Postal Code']] = $geoCode;
}

$counter = 0;
$exceptions = 0;

$output = [
    [ 'postcode', 'name', 'state_id', 'latitude', 'longitude', 'source' ],
];

foreach ($postalCodes as &$postalCode) {
    $counter++;
    if(!empty($indexedGeo[$postalCode['STR-CODE']])) {
        $geo = $indexedGeo[$postalCode['STR-CODE']];
        $postalCode['LAT'] = $geo['Latitude'];
        $postalCode['LONG'] = $geo['Longitude'];
    } elseif(!empty($indexedGeo[$postalCode['BOX-CODE']])) {
        $geo = $indexedGeo[$postalCode['BOX-CODE']];
        $postalCode['LAT'] = $geo['Latitude'];
        $postalCode['LONG'] = $geo['Longitude'];
    } else {
        $postalCode['LAT'] = '';
        $postalCode['LONG'] = '';
        $exceptions++;
    }

    if (!empty($postalCode['STR-CODE'])) {
        $output[] = [
            $postalCode['STR-CODE'],
            $postalCode['SUBURB'],
            '',
            $postalCode['LAT'],
            $postalCode['LONG'],
            json_encode($postalCode),
        ];
    }

    if (!empty($postalCode['BOX-CODE'])) {
        $output[] = [
            $postalCode['BOX-CODE'],
            $postalCode['SUBURB'],
            '',
            $postalCode['LAT'],
            $postalCode['LONG'],
            json_encode($postalCode),
        ];
    }
}

$fp = fopen('suburbs.csv', 'w');

foreach ($output as $fields) {
    fputcsv($fp, $fields);
}

fclose($fp);

echo "Total $counter\n";
echo "Exceptions $exceptions\n";
?>
