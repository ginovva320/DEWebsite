<?php
class Zend_View_Helper_RecentPosts {
	function recentPosts() {
		$registry = Zend_Registry::getInstance();
		$auth = Zend_Auth::getInstance();
		if ($auth->hasIdentity()) {
			$identity = $auth->getIdentity();
			$rank = $identity['rank'];
		} else {
			$rank = -1;
		}
		
		$dbc = $registry->dbc;
		$query = 
			"SELECT p.id AS post_id, p.time, t.title, t.id ,c.name, c.class, b.title AS board, b.id AS board_id
			FROM posts p JOIN topics t ON p.topic_id = t.id 
			JOIN accounts a ON p.account_id = a.id 
			JOIN characters c ON a.main_id = c.id
			JOIN boards b ON t.board_id = b.id
			JOIN forums f ON b.forum_id = f.id
			JOIN (select MAX(id) as max from posts group by topic_id) m ON m.max = p.id
			WHERE f.visibility <= $rank
			ORDER BY p.time DESC 
			LIMIT 5";
		$data = mysqli_query($dbc, $query);
		return $data;
	}
}
?>