<?php

require __DIR__ . '/vendor/autoload.php';

if (php_sapi_name() != "cli") {
    die("RUN SCRIPT IN CLI ONLY!");
}

try {
    writeLog("=========================================================", false);
    $dotenv = new Dotenv\Dotenv(__DIR__);
    $dotenv->load();  
} catch (Exception $e) {
    echo "Please provide .env config . see .env.example!\n";die();
}

function writeLog($message = "", $postToSlack = true) {
  if ($postToSlack) {
    postToSlack($message);
  }
  echo "[" . date('d-m-Y H:i:s') . "] " . $message . "\n";  
}
/**
 * Post to Slack
 * @return void
 */ 
function postToSlack($message)
{
  if ((bool)getenv('SLACK') && trim($message)!=='') {
    $slack = new Maknz\Slack\Client(getenv('SLACK_URL'));
    $slack->send('<!channel> *[Hayya ‘alash Sholāh]* '. $message);
  }
}

/**
 * Multi-array search
 *
 * @param array $array
 * @param array $search
 * @return array
*/
function multi_array_search($array, $search)
{
    // Create the result array
    $result = array();
    // Iterate over each array element
    foreach ($array as $key => $value)
    {
        // Iterate over each search condition
        foreach ($search as $k => $v)
        {
            // If the array element does not meet the search condition then continue to the next element
            if (!isset($value[$k]) || $value[$k] != $v)
            {
                continue 2;
            }
        }
        // Add the array element's key to the result array
        $result[] = $key;
    }
    // Return the result array
    return $result;
}


const ADZAN_FILE = __DIR__ . '/adzan.json';

$adzan = file_get_contents(ADZAN_FILE);

$adzanArray = json_decode($adzan, true);
$dateNow = new \Datetime();
$dateNow->modify(sprintf('+%s minutes', getenv('AZAN_NOTIF_LEFT')));
$timeNow = $dateNow->format('H') .":". $dateNow->format('i');

$condition = [
    'year' => $dateNow->format('Y'),
    'month' => $dateNow->format('m'),
    'date' => $dateNow->format('d')
];
$result = multi_array_search($adzanArray, $condition);

if (count($result) > 0) {
    $data = $adzanArray[reset($result)];

    unset($data['year']);
    unset($data['month']);
    unset($data['date']);

    // adzab time
    print_r($data);

    $imsyak     = substr($data['imsyak'], 0, 5);
    $fajr       = substr($data['fajr'], 0, 5);
    $syuruq     = substr($data['syuruq'], 0, 5);
    $dzuhr      = substr($data['dzuhr'], 0, 5);
    $ashr       = substr($data['ashr'], 0, 5);
    $maghrib    = substr($data['maghrib'], 0, 5);
    $isha       = substr($data['isha'], 0, 5);

    foreach ($data as $key => $azanTime) {
      $azanTime = substr($azanTime, 0, 5);
      if ($azanTime == $timeNow) {
        // post to slack
        $message = sprintf("%s menit menuju adzan _%s_ waktu DKI Jakarta. Yuk jalan.", getenv('AZAN_NOTIF_LEFT'), $key);
        postToSlack($message);
      }
    }
}

writeLog("=========================================================", false);
