<?php
class Zend_View_Helper_Mmochamp {
	function mmochamp() {
		try{
			
			$feed = new Zend_Feed_Rss('http://www.mmo-champion.com/index.php?type=rss;action=.xml;board=2.0;sa=news;');
			$i = 0;
			$html = "";
			
			foreach($feed as $item) {
				$url = $item -> link();
				$desc = $item -> description();
				$date = date('g:ia, M j Y',strtotime($item->pubDate()));
				$html.= <<<EOHTML
		<tr>
			<td><a target=_blank href=$url>{$item->title()}</a></td>
			<td class=time>$date</td>
		</tr>
EOHTML;
				if($i++ == 4)
				break;
			}
			return $html;
		}
		
		catch (Zend_Exception $e) {
			return <<<EOHTML
		<tr><td></td></tr>
		<tr><td></td></tr>
		<tr><td style=text-align:center ><em>MMO-Champion Unavailable</em></td></tr>
		<tr><td></td></tr>
		<tr><td></td></tr>
EOHTML;
		}
		
	}
}
?>