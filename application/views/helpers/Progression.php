<?php
class Zend_View_Helper_Progression implements Zend_View_Helper_Interface {
	function progression() {
		$registry = Zend_Registry::getInstance();
		$dbc = $registry->dbc;
		$progs = mysqli_query($dbc,
			"SELECT m.id, r.name, CEIL((SELECT COUNT(*) FROM bosses WHERE mode_id =  m.id AND defeated = 1) / (SELECT COUNT(*) FROM bosses WHERE mode_id =  m.id) * 100) AS def
			FROM raids r
			JOIN modes m
			ON r.id = m.raid_id
			WHERE r.active = 1"
		);
		
		$last = '';
		$jscript = "window.addEvent('domready',function(){";
		while ($prog = mysqli_fetch_array($progs)) {
			$raids[$prog['name']][] = array('mode' => $prog['id'], 'def' => $prog['def']);
		}
		
		foreach ($raids as $name => $raid) {
			$barId = strtolower(str_replace(' ','',$name));
			echo "<div id=$barId></div>";
			$jscript .= <<<JS
new dwProgressBar({
	container : $('$barId'),
	speed : 500,
	boxID : 'box{$raid[0]['mode']}',
	'boxclass' : 'box',
	percentageID : 'perc{$raid[0]['mode']}',
	percclass : 'normal'
}).set({$raid[0]['def']});
new dwProgressBar({
	container : $('$barId'),
	speed : 500,
	boxID : 'box{$raid[1]['mode']}',
	'boxclass' : 'box',
	percentageID : 'perc{$raid[1]['mode']}',
	percclass : 'heroic'
}).set({$raid[1]['def']});
JS;
		}
		$this->view->minifyHeadScript()->appendScript($jscript . "});");
		
		$results = mysqli_query($dbc,
			"SELECT r.name AS raid, b.name AS boss, b.defeated, m.id AS mode
			FROM raids r
			JOIN modes m
			ON r.id = m.raid_id
			JOIN bosses b
			ON b.mode_id = m.id
			WHERE r.active = 1"
		);
		
		while ($result = mysqli_fetch_array($results)) {
			$bosses[$result['raid']][$result['mode']][] = array('boss' => $result['boss'], 'defeated' => $result['defeated']);
		}

		$jscript = "window.addEvent('domready',function(){";
		
		foreach ($bosses as $raidName => $raid) {
			foreach ($raid as $id => $mode) {
				$dif = $id % 2 == 0 ? "Heroic" : "Normal";
				$tipText = "<table class=progression_table><thead><tr><th colspan=2>$raidName - $dif</th></tr></thead><tbody>";
				foreach ($mode as $boss) {
					if ($boss['defeated']) {
						$icon = "class=defeated";
						$fade = "";
					} else {
						$icon = "";
						$fade = "class=not_defeated";
					}
					$tipText .= "<tr><td $icon></td><td $fade>$boss[boss]</td></tr>";
				}
				$tipText .= "</tbody></table>";
				$jscript .= <<<JS
new Tips('#box$id');
$('box$id').store('tip:text',"$tipText");
JS;
			}
		}

		$this->view->minifyHeadScript()->appendScript($jscript . "});");
		
	}
	
	public $view;
	
	public function setView(Zend_View_Interface $view)
    {
        $this->view = $view;
    }
    
    public function direct()
    {
    	
    }
}
?>