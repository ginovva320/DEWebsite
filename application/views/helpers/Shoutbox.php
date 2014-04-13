<?php
class Zend_View_Helper_Shoutbox {
	function shoutbox() {
		$registry = Zend_Registry::getInstance();
		$dbc = $registry->dbc;
		$shouts = mysqli_query($dbc,
		"SELECT s.text, s.time, c.class, c.name
		FROM (SELECT text, time, account_id FROM shouts s ORDER BY time DESC LIMIT 15) s
		JOIN accounts a
		ON s.account_id = a.id
		JOIN characters c
		ON a.main_id = c.id
		ORDER BY s.time ASC"
		);

		return $shouts;
	}
}
?>