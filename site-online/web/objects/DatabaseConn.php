<?php

/*
 * The DatabaseConn class allows for database params to be defined,
 * but restricts editing of variables once set. This allows for the
 * vars to be defined once, and not be concerned about changes.
 */

class DatabaseConn{
	private $server;
	private $username;
	private $password;
	private $database;

	function DatabaseConn($server, $username, $password, $database){
		$this->server = $server;
		$this->username = $username;
		$this->password = $password;
		$this->database = $database;
	}
	function get($var){
		return $this->$var;
	}
}