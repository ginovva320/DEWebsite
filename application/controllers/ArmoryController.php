<?php

class ArmoryController extends Zend_Controller_Action
{

	public function init()
	{
		$registry = Zend_Registry::getInstance();
		$this->_dbc = $registry->dbc;
		$this->_armory = $registry->armory;
		$this->_armory->characterExcludeFields(array('appearance','quests'));
		if ($this->getRequest()->refresh)
		{
			$this->_armory->setCharactersCacheTTL(0);
			$this->_armory->setGuildsCacheTTL(0);
		}
		if ($this->getRequest()->name)
		{
			$this->name = ucwords(urldecode($this->getRequest()->name));
			if ($this->getRequest()->realm)
			{
				$this->char = $this->_armory->getCharacter($this->name,urldecode($this->getRequest()->realm));
			}
			else
			{
				$this->char = $this->_armory->getCharacter($this->name);
			}
		}
	}

	public function indexAction()
	{
		 
	}

	public function guildAction()
	{
		$realm = $this->getRequest()->realm ? urldecode($this->getRequest()->realm) : 'Proudmoore';
		$guildName = $this->getRequest()->name ? urldecode($this->getRequest()->name) : 'Dishonor Elite';
		
		if ($realm == 'Proudmoore')
		{
			$guild = $this->_armory->getGuild($guildName);
		}
		else
		{
			$guild = new Guild('us',$realm,$guildName);
		}
		 
		if ($guild->getData() && array_key_exists('name',$guild->getData()))
		{
			$this->view->guild = $guild;
		}
	}

	public function rosterAction()
	{
		$page = $this->getRequest()->page ? $this->getRequest()->page : 1;
		$sort = $this->getRequest()->sort ? $this->getRequest()->sort : 'rank';
		$dir = $this->getRequest()->dir ? $this->getRequest()->dir : 'asc';
		$realm = $this->getRequest()->realm ? urldecode($this->getRequest()->realm) : 'Proudmoore';
		$guildName = $this->getRequest()->guild ? urldecode($this->getRequest()->guild) : 'Dishonor Elite';
		 
		if ($realm == 'Proudmoore')
		{
			$guild = $this->_armory->getGuild($guildName);
		}
		else
		{
			$guild = new Guild('us',$realm,$guildName);
		}
		 
		if ($guild->getData() && array_key_exists('name',$guild->getData()))
		{
			$members = $guild->getMembers($sort,$dir);
			$pages = ceil(count($members)/30);
				
			$classes = new Classes('us');
			$races = new Races('us');

			if ($page > $pages) $page = 1;

			$start = 30*($page-1);
			$end = min(30*$page,count($members));
			for ($start; $start < $end; $start++)
			{
				$member = $members[$start];
				$url = $this->view->url(array('controller' => 'armory', 'action' => 'view', 'name' => urlencode($member['character']['name']), 'realm' => urlencode($member['character']['realm'])),null,true);
				$cssClass = str_replace(' ','',strtolower($classes->getClass($member['character']['class'],'name')));

				$name = "<a href='$url' class='$cssClass'>{$member['character']['name']}</a>";
				$class = $classes->getClass($member['character']['class'],'name');
				$race = $races->getRace($member['character']['race'],'name');
				$gender = $member['character']['gender'] ? 'Female' : 'Male';
				$level = $member['character']['level'];
				$rank = $member['rank'];

				$entries[] = array('name' => $name,
								   'class' => $class,
								   'race' => $race,
								   'gender' => $gender,
								   'level' => $level,
								   'rank' => $rank);

			}
			$this->view->pages = $pages;
			$this->view->members = $entries;
		}
		 
		$this->view->realm = $realm;
		$this->view->guild = $guildName;
		$this->view->dir = $dir;
		$this->view->sort = $sort;
		$this->view->page = $page;
	}

	public function viewAction()
	{
		if ($this->char->getData() && array_key_exists('name',$this->char->getData()))
		{
			$cdata = $this->char->getData();

			$this->view->minifyHeadScript()->appendFile($this->view->baseUrl().'/js/tabs.js');
			$this->view->minifyHeadScript()->appendScript(<<<JS
window.addEvent('domready',function(){
    var tabs = new TabSwapper({
		tabs: $$('#char-tabs li'),
		sections: $$('.charbox'),
		//cookieName: 'armchair',
		smooth: true,
		onActive: function(idx,sect,tab)
		{
			if(sect.get('rel') != 'loaded')
			{
				new Request.HTML({
					url: sect.get('rel'),
					append: sect,
					evalScripts: true,
					onRequest: function()
					{
						$$('.ajax-load').fade('in');
					},
					onComplete: function()
					{
						$$('.ajax-load').fade('out');
					},
					onSuccess: function()
					{
						sect.set('rel','loaded');
					}
				}).send();
			}
		}
	});
	
	$$('#char-tabs li').addEvent('active',function(){alert('shit');});
});
JS
			);

			$class = str_replace(' ','',strtolower($this->char->getClassName()));
			$title = $this->char->getCurrentTitle(FALSE);
			$guild = $cdata['guild']['name'];
			$realm = $cdata['realm'];
			if ($title != "")
			{
				$title = sprintf($title,"<span class='$class'>$this->name</span>");
			} else
			{
				$title = "<span class='$class'>$this->name</span>";
			}
			$talents = $this->char->getActiveTalents();
			$second = "<img src='http://us.media.blizzard.com/wow/icons/36/{$talents['icon']}.jpg'/><label>" . $this->char->getLevel() . ' ' . $this->char->getRaceName() . ' ' . $talents['name'] . ' ' . $this->char->getClassName() . '</label>';
				
			$specs = array(
			'Paladin' => array(
				'Protection' => 'tank',
				'Retribution' => 'melee',
				'Holy' => 'healer'),
			'Rogue' => array(
				'Subtlety' => 'melee',
				'Assassination' => 'melee',
				'Combat' => 'melee',),
			'Priest' => array(
				'Shadow' => 'dps',
				'Holy' => 'healer',
				'Discipline' => 'healer',),
			'Death Knight' => array(
				'Frost' => 'melee',
				'Blood' => 'melee',
				'Unholy' => 'melee',),
			'Shaman' => array(
				'Elemental' => 'dps',
				'Restoration' => 'healer',
				'Enhancement' => 'melee',),
			'Warlock' => array(
				'Affliction' => 'dps',
				'Demonology' => 'dps',
				'Destruction' => 'dps',),
			'Druid' => array(
				'Feral Combat' => 'melee',
				'Restoration' => 'healer',
				'Balance' => 'dps',),
			'Warrior' => array(
				'Fury' => 'melee',
				'Arms' => 'melee',
				'Protection' => 'tank',),
			'Mage' => array(
				'Frost' => 'dps',
				'Fire' => 'dps',
				'Arcane' => 'dps',),
			'Hunter' => array(
				'Beast Mastery' => 'hunter',
				'Marksmanship' => 'hunter',
				'Survival' => 'hunter')
			);
			
			$specToStats = array(
							'melee' => array('attackPower','expertiseRating','hasteRating','hitPercent','crit'),
							'dps' => array('spellPower','hasteRating','spellHitPercent','spellCrit'),
							'tank' => array('armor','hitPercent','expertiseRating','dodge','parry','block'),
							'healer' => array('spellPower','mana5','hasteRating','spellCrit'),
							'hunter' => array('rangedAttackPower','hasteRating','rangedHitPercent','rangedCrit')
			);
				
			$gear = $this->char->getGear();
			$leftSlots = array('head','neck','shoulder','back','chest','shirt','tabard','wrist');
			$rightSlots = array('hands','waist','legs','feet','finger1','finger2','trinket1','trinket2');
			$midSlots = array('mainHand','offHand','ranged');
			$stats = $this->char->getStats();
			$bgUrl = str_replace('avatar','profilemain',$this->char->getThumbnailUrl());
//			echo '<pre>';var_dump($this->char->getStats());die();
			$gUrl = $this->view->url(array('controller' => 'armory', 'action' => 'guild', 'name' => $guild, 'realm' => $realm),null,true);
			$headerHtml = <<<HTML
<header>
	<div id=char-name>$title</div>
	<div id=char-guild><a href='$gUrl'>$guild</a> - $realm</div>
	<img class='ajax-load' src='/img/ajax-loader.gif'/>
</header>			
HTML;

			echo <<<EOHTML
		<ul id=char-tabs>
			<li>Main</li>
			<li>All Stats</li>
			<li>Talents</li>
			<li>Reputations</li>
			<li>Progression</li>
			<li>Professions</li>
			<li>Titles</li>
			<li>Mounts</li>
			<li>PvP</li>
			<li>Achievements</li>
			<li>Companions</li>
		</ul>
		<section rel=loaded id=armory-char class='postframe charbox' class=lift>
		$headerHtml
			<article>
				<section id=char-info>
					<div id=char-spec>
					$second
					</div>
					<div id=char-ilvl>
						<label>$gear[averageItemLevel]</label> Item Level<br/>
						(Equipped $gear[averageItemLevelEquipped])
					</div>
				</section>
				<div id=char-gear>
					<table style="background:url('$bgUrl') no-repeat;">
EOHTML;
					for ($i = 0; $i < 8; $i++)
					{
						if ($itemL = $this->char->getItemSlot($leftSlots[$i]))
						{
							$relL = $this->getWowheadRel($itemL['tooltipParams']);
							echo <<<EOHTML
					<tr>
						<td class='right gear gear-icon'>
							<a rel='$relL' href='http://www.wowhead.com/item=$itemL[id]'>
								<img class='icon-border' src='http://wowimg.zamimg.com/images/Icon/medium/hilite/default.png'/>
								<img class='gear-icon' src='http://us.media.blizzard.com/wow/icons/36/$itemL[icon].jpg'/>
							</a>
						</td>
						<td class='left gear gear-name'>
							<a class='q$itemL[quality]' rel='$relL' href='http://www.wowhead.com/item=$itemL[id]'>$itemL[name]</a>
						</td>
EOHTML;
						}
						else
						{
							echo <<<EOHTML
					<tr>
						<td class='right gear gear-icon'>
							<div style="background: url(&quot;http://wowimg.zamimg.com/images/Icon/medium/hilite/default.png&quot;) no-repeat; width: 40px; height: 38px;"></div>
						</td>
						<td class='left gear gear-name'>
						</td>
EOHTML;
						}

//						if ($i == 1)
//						{	
//							$url = $this->char->getThumbnailUrl();
//							echo "<td colspan='4' rowspan='2' id='char-face'><img src='$url'/></td>";
//						}
//						else if ($i != 2)
//						{
							echo "<td colspan='4'></td>";
//						}

						if ($itemR = $this->char->getItemSlot($rightSlots[$i]))
						{
							$relR = $this->getWowheadRel($itemR['tooltipParams']);
							if (strstr($rightSlots[$i],'trinket') != FALSE)
							{
								$relR = '';
							}
							echo <<<EOHTML
						<td class='right gear gear-name'>
							<a class='q$itemR[quality]' rel='$relR' href='http://www.wowhead.com/item=$itemR[id]'>$itemR[name]</a>
						</td>
						<td class='left gear gear-icon'>
							<a rel='$relR' href='http://www.wowhead.com/item=$itemR[id]'>
								<img class='icon-border' src='http://wowimg.zamimg.com/images/Icon/medium/hilite/default.png'/>
								<img class='gear-icon' src='http://us.media.blizzard.com/wow/icons/36/$itemR[icon].jpg'/>
							</a>
						</td>
					</tr>
EOHTML;
						}
						else
						{
							echo <<<EOHTML
						<td class='right gear gear-name'>
						</td>
						<td class='left gear gear-icon'>
							<div style="background: url(&quot;http://wowimg.zamimg.com/images/Icon/medium/hilite/default.png&quot;) no-repeat; width: 40px; height: 38px;"></div>
						</td>
					</tr>
EOHTML;
						}

					}
						
					if ($main = $this->char->getItemSlot($midSlots[0]))
					{
						$relM = $this->getWowheadRel($main['tooltipParams']);
						echo <<<EOHTML
						<tr>
							<td></td>
							<td class='right gear gear-name'>
								<a class='q$main[quality]' rel='$relM' href='http://www.wowhead.com/item=$main[id]'>$main[name]</a>
							</td>
							<td class='left gear gear-icon'>
								<a rel='$relM' href='http://www.wowhead.com/item=$main[id]'>
									<img class='icon-border' src='http://wowimg.zamimg.com/images/Icon/medium/hilite/default.png'/>
									<img class='gear-icon' src='http://us.media.blizzard.com/wow/icons/36/$main[icon].jpg'/>
								</a>
							</td>
EOHTML;
					}
					else
					{
						echo <<<EOHTML
						<tr>
							<td></td>
							<td class='right gear gear-name'>
							</td>
							<td class='left gear gear-icon'>
								<div style="background: url(&quot;http://wowimg.zamimg.com/images/Icon/medium/hilite/default.png&quot;) no-repeat; width: 40px; height: 38px;"></div>
							</td>
EOHTML;
					}
						
					if ($off = $this->char->getItemSlot($midSlots[1]))
					{
						$relO = $this->getWowheadRel($off['tooltipParams']);
						echo <<<EOHTML
							<td class='right gear gear-icon'>
								<a rel='$relO' href='http://www.wowhead.com/item=$off[id]'>
									<img class='icon-border' src='http://wowimg.zamimg.com/images/Icon/medium/hilite/default.png'/>
									<img class='gear-icon' src='http://us.media.blizzard.com/wow/icons/36/$off[icon].jpg'/>
								</a>
							</td>
							<td class='left gear gear-name'>
								<a class='q$off[quality]' rel='$relO' href='http://www.wowhead.com/item=$off[id]'>$off[name]</a>
							</td>
EOHTML;
					}
					else
					{
						echo <<<EOHTML
							<td class='right gear gear-icon'>
								<div style="background: url(&quot;http://wowimg.zamimg.com/images/Icon/medium/hilite/default.png&quot;) no-repeat; width: 40px; height: 38px;"></div>
							</td>
							<td class='left gear gear-name'>
							</td>
EOHTML;
					}
						
					if ($ranged = $this->char->getItemSlot($midSlots[2]))
					{
						$relR = $this->getWowheadRel($ranged['tooltipParams']);
						echo <<<EOHTML
							<td class='right gear gear-icon'>
								<a rel='$relR' href='http://www.wowhead.com/item=$ranged[id]'>
									<img class='icon-border' src='http://wowimg.zamimg.com/images/Icon/medium/hilite/default.png'/>
									<img class='gear-icon' src='http://us.media.blizzard.com/wow/icons/36/$ranged[icon].jpg'/>
								</a>
							</td>
							<td class='left gear gear-name'>
								<a class='q$ranged[quality]' rel='$relR' href='http://www.wowhead.com/item=$ranged[id]'>$ranged[name]</a>
							</td>
							<td></td>
						</tr>
EOHTML;
					}
					else
					{
						echo <<<EOHTML
							<td class='right gear gear-icon'>
								<div style="background: url(&quot;http://wowimg.zamimg.com/images/Icon/medium/hilite/default.png&quot;) no-repeat; width: 40px; height: 38px;"></div>
							</td>
							<td class='left gear gear-name'>
							</td>
							<td></td>
						</tr>
EOHTML;
					}
					$powerName = ucwords(preg_replace('/(?!^)[A-Z]{2,}(?=[A-Z][a-z])|[A-Z][a-z]/', ' $0', $stats['powerType']));
					echo <<<EOHTML
					</table>
				</div>
				<table id=char-stats-tables>
					<tbody>
						<tr>
							<th>
								<table id=stats-left>
									<tr><th>Health</th><td>$stats[health]</td></tr>
									<tr><th>$powerName</th><td>$stats[power]</td></tr>
								</table>
							</th>
							<th>
								<table id=stats-mid>
									<tr><th>Stamina</th><td>$stats[sta]</td></tr>
									<tr><th>Intellect</th><td>$stats[int]</td></tr>
									<tr><th>Strength</th><td>$stats[str]</td></tr>
									<tr><th>Agility</th><td>$stats[agi]</td></tr>
									<tr><th>Spirit</th><td>$stats[spr]</td></tr>
									<tr><th>Mastery</th><td>$stats[mastery]</td></tr>
								</table>
							</th>
							<th>
								<table id=stats-right>
EOHTML;
					foreach ($specToStats[$specs[$this->char->getClassName()][$talents['name']]] as $statName)
					{
						echo '<tr><th>'.ucwords(preg_replace('/(?!^)[A-Z]{2,}(?=[A-Z][a-z])|[A-Z][a-z]/', ' $0', $statName)).'</th>';
						echo '<td>'.$stats[$statName].'</td></tr>';
					}
					$urls = array(
					$this->view->url(array('controller' => 'armory', 'action' => 'char-stats'),null,FALSE),
					$this->view->url(array('controller' => 'armory', 'action' => 'char-talents'),null,FALSE),
					$this->view->url(array('controller' => 'armory', 'action' => 'char-reputations'),null,FALSE),
					$this->view->url(array('controller' => 'armory', 'action' => 'char-progression'),null,FALSE),
					$this->view->url(array('controller' => 'armory', 'action' => 'char-professions'),null,FALSE),
					$this->view->url(array('controller' => 'armory', 'action' => 'char-titles'),null,FALSE),
					$this->view->url(array('controller' => 'armory', 'action' => 'char-mounts'),null,FALSE),
					$this->view->url(array('controller' => 'armory', 'action' => 'char-pvp'),null,FALSE),
					$this->view->url(array('controller' => 'armory', 'action' => 'char-achievements'),null,FALSE),
					$this->view->url(array('controller' => 'armory', 'action' => 'char-companions'),null,FALSE),
					);
					echo <<<EOHTML
								</table>
							</th>
						</tr>
					</tbody>
				</table>
			</article>
		</section>
	<section rel='$urls[0]' id=armory-stats class='postframe charbox' class=lift>$headerHtml</section>
	<section rel='$urls[1]' id=armory-talents class='postframe charbox' class=lift>$headerHtml</section>
	<section rel='$urls[2]' id=armory-reputations class='postframe charbox' class=lift>$headerHtml</section>
	<section rel='$urls[3]' id=armory-prog class='postframe charbox' class=lift>$headerHtml</section>
	<section rel='$urls[4]' id=armory-profs class='postframe charbox' class=lift>$headerHtml</section>
	<section rel='$urls[5]' id=armory-titles class='postframe charbox' class=lift>$headerHtml</section>
	<section rel='$urls[6]' id=armory-mounts class='postframe charbox' class=lift>$headerHtml</section>
	<section rel='$urls[7]' id=armory-pvp class='postframe charbox' class=lift>$headerHtml</section>
	<section rel='$urls[8]' id=armory-achieves class='postframe charbox' class=lift>$headerHtml</section>
	<section rel='$urls[9]' id=armory-pets class='postframe charbox' class=lift>$headerHtml</section>
EOHTML;
		}
		else {
			echo <<<EOHTML
				<section class='postframe lift shadow'>
					<p>
						This character profile could not be displayed, possibly for one of the following reasons:
					</p>

					<ul>
						<li>The character has been inactive for an extended period of time.</li>
						<li>The character name or realm was spelled incorrectly.</li>
						<li>The profile is temporarily unavailable while the character is in the midst of a process such as a realm transfer or faction change.</li>
						<li>Characters that have been deleted are no longer available.</li>
					</ul>
				</section>
EOHTML;
		}
	}

	public function charProgressionAction()
	{
		$this->_helper->layout->disableLayout();
		$this->view->raidStats = $this->char->getRaidStats('desc');
	}

	public function charStatsAction()
	{
		$this->_helper->layout->disableLayout();
		$stats =  $this->char->getStats();
		$this->view->stats = $this->char->getStats();
		$this->view->base = array(
			'Strength' => 'str',
			'Agility' => 'agi',
			'Stamina' => 'sta',
			'Intellect' => 'int',
			'Spirit' => 'spr',
			'Mastery' => 'mastery'
		);
		$this->view->melee = array(
			'Damage Min' => 'mainHandDmgMin',
			'Damage Max' => 'mainHandDmgMax',
			'MH DPS' => 'mainHandDps',
			'OH DPS' => 'offHandDps',
			'Attack Power' => 'attackPower',
			'MH Speed' => 'mainHandSpeed',
			'OH Speed' => 'offHandSpeed',
			'Haste' => 'hasteRating',
			'Hit' => 'hitPercent',
			'Crit' => 'crit',
			'Expertise' => 'expertiseRating'
		);
		$this->view->spell = array(
			'Spell Power' => 'spellPower',
			'Haste' => 'hasteRating',
			'Hit' => 'spellHitPercent',
			'Crit' => 'spellCrit',
			'Penetration' => 'spellPen',
			'Mana Regen' => 'mana5',
			'Combat Regen' => 'mana5Combat'			
		);
		$this->view->ranged = array(
			'Damage Min' => 'rangedDmgMin',
			'Damage Max' => 'rangedDmgMax',
			'DPS' => 'rangedDps',
			'Attack Power' => 'rangedAttackPower',
			'Speed' => 'rangedSpeed',
			'Haste' => 'hasteRating',
			'Hit' => 'rangedHitPercent',
			'Crit' => 'rangedCrit'
		);
		$this->view->defense = array(
			'Armor' => 'armor',
			'Dodge' => 'dodge',
			'Parry' => 'parry',
			'Block' => 'block',
			'Resilience' => 'resil'
		);
	}

	public function charProfessionsAction()
	{
		$this->_helper->layout->disableLayout();
		$this->view->profs = $this->char->getProfessions();
		$this->view->professions = $this->char->getPrimaryProfessions();
	}

	public function charTitlesAction()
	{
		$this->_helper->layout->disableLayout();
		$this->view->titles = $this->char->getTitles();
	}

	public function charMountsAction()
	{
		$this->_helper->layout->disableLayout();
		$this->view->mounts = $this->char->getMounts();
	}

	public function charPvpAction()
	{
		$this->_helper->layout->disableLayout();
		$this->view->teams = $this->char->getArenaTeams();
		$this->view->bgrating = $this->char->getBattlegroundRating();
		$this->view->bgs = $this->char->getBattlegrounds();
		$this->view->hks = $this->char->getHonorKills();
	}

	public function charAchievementsAction()
	{
		$this->_helper->layout->disableLayout();
		$this->view->achievements = $this->char->getAchievements('timestamp','desc');
		$this->view->points = $this->char->getAchievementPoints();
	}

	public function charTalentsAction()
	{
		$this->_helper->layout->disableLayout();
		$data = $this->char->getData();
		$talents = $this->char->getActiveTalents();
		$this->view->talents = $this->show_talents($data['class'],$talents['build']);
		$this->view->glyphs = $talents['glyphs'];
	}

	public function charCompanionsAction()
	{
		$this->_helper->layout->disableLayout();
		$data = $this->char->getData();
		$this->view->pets = $data['companions'];
	}

	public function charQuestsAction()
	{
		$this->_helper->layout->disableLayout();
		$this->view->quests = $this->char->getCompletedQuests('id','desc');
	}
	
	public function charReputationsAction()
	{
		$this->_helper->layout->disableLayout();
		$this->view->reps = $this->char->getReputation(TRUE,'id','desc');
	}

	function getWowheadRel($tooltipParams)
	{
		$rel = '';
		$gems = null;
		$enchant = null;
		$sock = null;
		$set = null;
		$tips = null;
		foreach ($tooltipParams as $key => $val)
		{
			if (strstr($key,'gem') != FALSE)
			{
				$gems[] = $val;
			}
			else if ($key == 'enchant')
			{
				$enchant = 'ench='.$val;
			}
			else if ($key == 'set')
			{
				$set = 'pcs=' . implode(':',$val);
			}
			else if ($key == 'extraSocket')
			{
				$sock = 'sock';
			}
		}
		 
		if ($gems != null)
		$gems = "gems=" . implode(":",$gems);
		 
		foreach (array($gems,$enchant,$set,$sock) as $tip)
		{
			if ($tip != null)
			{
				$tips[] = $tip;
			}
		}
		 
		if ($tips != null)
		$rel = implode('&amp;',$tips);

		return $rel;
	}
	
	function show_talents($class,$builddata)
	{
//		$sqlquery2 = "SELECT * FROM 'api_talent_builds' WHERE 'class_id' = '$class'";
//		$result2 = mysqli_query($this->_dbc,$sqlquery2);
//		$talents = array();
//		while($row = mysqli_fetch_array($result2))
//		{
//		$talents[$row['build']] = $row['tree'];//'';
//		}
//		sort($talents);
		
		$e = array();
//		$tree_rows = count($talents);
		$trees = $this->build_talenttree_data($class);
		
		// Talent data and build spec data
		$talentdata = $specdata = array();
		$build='1';
		
		// Temp var for talent spec detection
		$spec_points_temp = array();
		
		//foreach( $talents as $build => $builddata )
		//{
		$spc = '1';// we set this to 1 .. because we are passing 1 get it hehe #1...$build;
		$ts = $this->_talent_layer2($builddata,$class);
		
		foreach( $ts as $tree => $data )
		{
			$order = $data['order'];
			if( !isset($spec_points_temp[$build]) )
			{
				$spec_points_temp[$build] = $data['spent'];
				$specdata[$build]['order'] = $build;
				$specdata[$build]['name'] = $tree;
				$specdata[$build]['icon'] = $data['icon'];
				$specdata[$build]['background'] = $data['background'];
			}
			else if( $data['spent'] > $spec_points_temp[$build] )
			{
				$specdata[$build]['order'] = $data['order'];
				$specdata[$build]['name'] = $tree;
				$specdata[$build]['icon'] = $data['icon'];
				$specdata[$build]['background'] = $data['background'];

				// Store highest tree points to temp var
				$spec_points_temp[$build] = $data['spent'];
			}

			// Store our talent points for later use
			$specdata[$build]['points'][$order] = $data['spent'];

			// Set talent tree data
			$talentdata[$build][$order]['name'] = $tree;
			$talentdata[$build][$order]['icon'] = $data['icon'];
			$talentdata[$build][$order]['image'] = $data['background'];
			$talentdata[$build][$order]['points'] = $data['spent'];
			$talentdata[$build][$order]['talents'] = $data;
		}

		$e['talent']= array(
			'ID' => $build,
			'NAME' => $specdata[$build]['name'],
			'TYPE' => 'Active',
			'BUILD' => implode(' / ', $specdata[$build]['points']),
			'ICON' => $specdata[$build]['icon'],
			'BACKGROUND' => $specdata[$build]['background'],
			'SELECTED' => ($build == 0 ? true : false)
		);
		//aprint($talentdata);
		foreach( $talentdata as $build => $builddata )
		{
			if( $spc == $build )
			{
				// Loop trees in build
				foreach( $builddata as $treeindex => $tree )
				{
					$e['talent']['trees'][$treeindex] = array(
						'L_POINTS_SPENT' => $tree['name'].' Points Spent',
						'NAME' => $tree['name'],
						'ID' => $treeindex,
						'POINTS' => $tree['points'],
						'ICON' => $tree['icon'],
						'BACKGROUND' => $tree['image'],
						'SELECTED' => ($spc == $build ? true : false)
					);

					foreach( $tree['talents'] as $row )
					{
						if( is_array($row) )
						{

							foreach( $row as $cell )
							{
								if( isset($cell['row']) )
								{
									$e['talent']['trees'][$treeindex]['talents'][$cell['column']][$cell['row']]= array(
										'NAME' => $cell['name'],
										'RANK' => (isset($cell['rank']) ? $cell['rank'] : 0),
										'MAXRANK' => (isset($cell['maxrank']) ? $cell['maxrank'] : 0),
										'TOOLTIP' => (isset($cell['tooltip']) ? $cell['tooltip'] : ''),
										'ICON' => (isset($cell['image']) ? $cell['image'] : ''),
									
										'S_MAX' => (isset($cell['rank']) && $cell['rank'] == $cell['maxrank'] ? true : false),
										'S_ABILITY' => false,
									);
								}
							}
						}
					}
				}
			}
		}
		//}

		return $e;
		}

		function build_talent_data( $class )
		{
			$sql = "SELECT * FROM api_talents_data a WHERE a.class_id = $class ORDER BY a.tree_order, a.row, a.column";

			$t = array();
			$results = mysqli_query($this->_dbc,$sql);

			$is = '';
			$ii = '';
			if( $results && mysqli_num_rows($results) > 0 )
			{
				while( $row = mysqli_fetch_array($results) )
				{
					$is++;
					$ii++;
					$t[$row['tree']][$row['row']][$row['column']]['name'] = $row['name'];
					$t[$row['tree']][$row['row']][$row['column']]['id'] = $row['talent_id'];
					$t[$row['tree']][$row['row']][$row['column']]['tooltip'][$row['rank']] = $row['tooltip'];
					$t[$row['tree']][$row['row']][$row['column']]['icon'] = $row['texture'];
				}
			}
			return $t;
		}

		function _talent_layer2( $build, $class )
		{
			$sqlquery = "SELECT * FROM api_talenttree_data WHERE class_id = $class";
			$result = mysqli_query($this->_dbc,$sqlquery);

			$treed = array();
			while( $row = mysqli_fetch_array($result) )
			{
				$treed[$row['tree']]['background'] = $row['background'];
				$treed[$row['tree']]['icon'] = $row['icon'];
				$treed[$row['tree']]['order'] = $row['order'];
			}
			
			$talentinfo = $this->build_talent_data($class);
			
			$returndata = array();
			$talentArray = preg_split('//', $build, -1, PREG_SPLIT_NO_EMPTY);
			$i = 0;
			$t = 0;
			$n = '';
			$spent = 0;
			$dd = 0;
			foreach( $talentinfo as $ti => $talentdata )
			{
				for( $r = 1; $r < 7 + 1; $r++ )
				{
					for( $c = 1; $c < 4 + 1; $c++ )
					{
						$dd++;
						$returndata[$ti][$r][$c]['name'] = '';
						$returndata[$ti][$r][$c]['num'] = $dd;
					}
				}
				$spent = '';
				$returndata[$ti]['icon'] = $treed[$ti]['icon'];
				$returndata[$ti]['background'] = $treed[$ti]['background'];
				$returndata[$ti]['order'] = $treed[$ti]['order'];

				foreach( $talentdata as $c => $cdata )
				{
					$maxrank = 0;
					foreach( $cdata as $r => $rdata )
					{

						$max = count($rdata['tooltip']);
						$returndata[$ti][$c][$r]['name'] = $rdata['name'];
						$returndata[$ti][$c][$r]['rank'] = $talentArray[$i];
						$returndata[$ti][$c][$r]['maxrank'] = count($rdata['tooltip']);
						$returndata[$ti][$c][$r]['row'] = $r;
						$returndata[$ti][$c][$r]['column'] = $c;
						$returndata[$ti][$c][$r]['image'] = $rdata['icon'];
						$rank = '';
						if (isset($talentArray[$i]) && $talentArray[$i] != 0)
						{
							$rannk = $talentArray[$i];
						}
						else if ($talentArray[$i] == 0)
						{
							$rannk = 1;
						}
						else
						{
							$rannk = 1;
						}

						// Detect max rank and set color
						if ($talentArray[$i] < $max)
						{
							$maxc = '#6FFF5B';
						}
						else
						{
							$maxc = '#FFD200';
						}

						$tooltipp = $rdata['tooltip'][$rannk];
						$tp = 'Rank: ' . $talentArray[$i] . ' / ' . $max . '<br/><br/>' . $tooltipp;
						$returndata[$ti][$c][$r]['ttip'] = $tooltipp;
						$returndata[$ti][$c][$r]['tooltip'] = $tp;

						$spent = ($spent + $talentArray[$i]);
						if( $rdata['name'] != $n )
						{
							$i++;
						}
						$n = $rdata['name'];
					}
				}
				$returndata[$ti]['spent'] = $spent;

				$t++;
			}
			return $returndata;
		}


		function build_talenttree_data( $class )
		{
			$sql = "SELECT * FROM api_talenttree_data WHERE class_id = $class ORDER BY 'order' ASC";

			$t = array();
			$results = mysqli_query($this->_dbc,$sql);
			$is = '';
			$ii = '';

			if( $results && mysqli_num_rows($results) > 0 )
			{
				while( $row = mysqli_fetch_array($results) )
				{
					$is++;
					$ii++;
					$t[$row['tree']]['name'] = $row['tree'];
					$t[$row['tree']]['background'] = $row['background'];
					$t[$row['tree']]['icon'] = $row['icon'];
					$t[$row['tree']]['order'] = $row['order'];
				}
			}
			return $t;

		}
}
?>
