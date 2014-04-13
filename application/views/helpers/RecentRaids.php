<?php
class Zend_View_Helper_RecentRaids {
	function recentRaids() {
		$url = 'http://www.worldoflogs.com/feeds/guilds/26793/raids/?t=xml';
		$client = new Zend_Http_Client($url);
		$response = $client->request();
		$xml = $response->getBody();
		$xml = new SimpleXMLElement($xml);

		$i = 1;
		$raids = $xml->xpath('/Guild/Raids/Raid[Participants[Participant[@name="Stind"]]]');
		
		foreach ($raids as $raid) {
			$dur = $raid['duration'];
			$duration = floor($dur/(1000*60*60)) . 'hr ' . floor($dur/(60*1000) - 60*floor($dur/(1000*60*60))) . 'm';
			$dif = $raid->Zones->Zone[0]['difficulty'] == 'H' ? 'Heroic' : 'Normal';
			echo '<tr>';
			echo '<td><a target=_blank href=http://www.worldoflogs.com/reports/' . $raid['id'] . '>' . $raid->Zones->Zone[0]['name'] . ' - ' . $dif . '</a></td>';
//			echo '<td><table>';
//			foreach ($raid->Bosses->Boss as $boss) {
//				$url = 'http://www.wowhead.com/npc=' . $boss['id'];
//				echo '<tr><td class=log_' . $boss['difficulty'] . '><a href=' . $url . '>Boss</a></td></tr>';
//			}
//			echo '</table></td>';
			echo '<td class=time>' . date('g:ia, M j Y',floatval($raid['date'])/1000) . '</td>';
			echo '<td>' . $duration . '</td>';
			echo '<td class=log_kills>' . $raid['killCount'] . '</td>';
			echo '<td class=log_wipes>' . $raid['wipeCount'] . '</td>';
			echo '</tr>';
			if ($i++ == 5)
				break;
		}
	}
}
?>