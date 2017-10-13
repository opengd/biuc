<?php

declare(strict_types=1);

namespace Biuc;

require 'db.php';
require 'settings.php';
use Db;

class StoreLocal {

  private $settings;

  private $con;

  function __construct(array $settings) {
    $this->settings = $settings;
  }

  function run($limit = 0, $name = ""): string {

    $this->con = $this->openBiucDb();
    
    $nonLocals = $this->getNonLocalSet($limit, $name);

    if($nonLocals)
      $result = $this->getFiles($nonLocals, $limit);

    $this->con->close();

    return $result ? json_encode($result) : json_encode(['msg' => 'All locals are set']);
  }

  function getNonLocalSet($limit = 0, $name = "") {
    if(!$this->con)
      $this->con = $this->openBiucDb();
    
    if($name == "")
      $sql = "SELECT * FROM {$this->settings['db']['table']} WHERE local = FALSE";
    else
      $sql = "SELECT * FROM {$this->settings['db']['table']} WHERE name = '{$name}'";
  
    if($limit > 0)
      $sql = "{$sql} LIMIT {$limit}";
    
    $result = $this->con->runQuery($sql);

    if($result) {
      $ret = $result->fetch_all(MYSQLI_ASSOC);
      $result->free();
    }

    return $ret ?: false;
  }

  function setLocal($name) {
    if(!$this->con)
        $this->con = $this->openBiucDb();

    $sql = "UPDATE {$this->settings['db']['table']} set local = TRUE WHERE name = '$name'";

    return $this->con->runQuery($sql);
  }
  
  function openBiucDb()
  {
      $con = new Db\Connection ($this->settings['db']);

      $con->open();

      return $con;
  }

  function getFiles($nonLocals, $limit = 0)
  {
    if($limit > 0) 
      $i = 0;

    $result = ['download' => [], 'set' => [], 'issue' => []];
    
    foreach($nonLocals as $nonLocal) {      
      $issue = false;

      $filename = "{$this->settings['storage']['path']}{$nonLocal['name']}{$this->settings['storage']['ext']}";
      $isFileInFolder = file_exists($filename);

      if(!$isFileInFolder) {
        $fileContent = file_get_contents($nonLocal["url"]);
        
        if($fileContent) 
          $filePut = file_put_contents($filename, $fileContent); 
        
        $issue = ($fileContent && $filePut) ? false : true;   
      }

      if(!$issue) {
        $issue = $this->setLocal($nonLocal['name']);
        
        $issue = $issue ? false : true;
      }

      if($isFileInFolder && !$issue)
        array_push($result['set'], $nonLocal);
      elseif(!$isFileInFolder && !$issue)
        array_push($result['download'], $nonLocal);
      else
        array_push($result['issue'], $nonLocal);

      if($limit > 0) {
        $i++;
        if($i == $limit) 
          break;
      }
    }

    $result['set_count'] = count($result['set']);
    $result['download_count'] = count($result['download']);
    $result['issue_count'] = count($result['issue']);

    return $result;
  }
} 

if(!isset($_GET['h']) || $_GET['h'] != $settings['hash']['admin'])
    die(json_encode(['error' => 'Query is wrong']));

echo (new StoreLocal($settings))->run(isset($_GET['limit']) ? $_GET['limit'] : 0, isset($_GET['name']) ? $_GET['name'] : "");