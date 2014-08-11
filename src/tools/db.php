<?php
/**
* 
*/
class db
{
	
	function select($sql)
	{
		return $app['db']->fetchAll($sql);
	}
}
?>