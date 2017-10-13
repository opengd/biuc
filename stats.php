<?php 

namespace Biuc;

require 'db.php';
require 'settings.php';
use Db;

class Stats {
  
  private $settings;

  private $con;

  function __construct(array $settings) {
    $this->settings = $settings;
  }

  function run($limit = 0, $name = ""): string {

    $this->con = (new Db\Connection($this->settings['db']))->open();
    
    $stats = $this->getStats();

    $this->con->close();

    return $stats ? json_encode($stats) : json_encode(['msg' => 'Error']);
  }

  function getStats() {
    if(!$this->con)
      $this->con = $this->openBiucDb();

    $stats = [
      'total_count' => "SELECT count(*) FROM {$this->settings['db']['table']}",
      'album_count' => "SELECT count(*) FROM {$this->settings['db']['table']} WHERE query NOT LIKE '%\" + \"artist\"'",
      'artist_count' => "SELECT count(*) FROM {$this->settings['db']['table']} WHERE query LIKE '%\" + \"artist\"'",
      'local_count_false' => "SELECT count(*) FROM {$this->settings['db']['table']} WHERE local = false",
      'local_count_true' => "SELECT count(*) FROM {$this->settings['db']['table']} WHERE local = true",  
    ];

    foreach($stats as $key => $value) {      
      $result = $this->con->runQuery($value);

      if($result) {
        $ret = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();
      }

      if($ret)
        $stats[$key] = $ret;
    }

    return $stats;
  }
}

if(!isset($_GET['h']) || $_GET['h'] != $settings['hash']['admin'])
    die(json_encode(['error' => 'Query is wrong']));

echo (new Stats($settings))->run();