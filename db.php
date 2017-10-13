<?php

namespace Db;

class Connection {

    public $server;
    public $user;
    public $password;
    public $database;

    private $con;

    function __construct(array $config = [])
    {
        $this->server = isset($config['server']) ? $config['server'] : '';
        $this->user = isset($config['user']) ? $config['user'] : '';
        $this->password = isset($config['password']) ? $config['password'] : '';
        $this->database = isset($config['database']) ? $config['database'] : ''; 
    }

    function runQuery($sql)
    {
        if(!$this->con)
            return false;

        $result = $this->con->query($sql);

        if (!$result)
            return false;

        return $result;
    }

    function escapeString($str)
    {
        return $this->con || $str || is_string($str) ? $this->con->real_escape_string($str) : false;
    }

    function open()
    {
        $this->con = new \mysqli($this->server, $this->user, $this->password, $this->database);

        if($this->con->connect_errno)
            die('Could not connect: ' . $this->con->connect_error);

        return $this;
    }

    function close()
    {
        if(!$this->con)
            return false;

        $this->con->close();

        return true;
    }
}