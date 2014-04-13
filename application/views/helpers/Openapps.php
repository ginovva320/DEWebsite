<?php
class Zend_View_Helper_Openapps implements Zend_View_Helper_Interface {
	function openapps() {
		if (Zend_Auth::getInstance()->hasIdentity())
		{
			$identity = Zend_Auth::getInstance()->getIdentity();
			if ($identity['rank'] >= 20)
			{
				$registry = Zend_Registry::getInstance();
				$dbc = $registry->dbc;
				$apps = mysqli_query($dbc,
					"SELECT app.time, app.id, c.name, c.class, c.thumb,
					(SELECT COUNT(*) FROM posts p WHERE p.application_id = app.id) as comments
					FROM applications app
					JOIN accounts a
					ON a.id = app.account_id
					JOIN characters c
					ON c.id = a.main_id
					WHERE status = 'Open'
					ORDER BY time DESC"
				);
				
				if (mysqli_num_rows($apps) == 0) return;

				echo <<<EOHTML
<section id=open-apps class=lift>
	<header>
		<h1>Open Apps</h1>
	</header>
	<article>
		<table>
EOHTML;
				while ($app = mysqli_fetch_array($apps))
				{
					$time = date('M j', $app['time']);
					$url = $this->view->url(array('controller' => 'application', 'action' => 'view', 'id' => $app['id']), null, true);
					echo <<<EOHTML
			<tr>
				<td>
					<img src=$app[thumb] />
				</td>
				<td>
					<span class=$app[class] />$app[name]</span><br/>
					<a href=$url >View</a>
				</td>
				<td>
					$app[comments] comments<br/>
					$time
				</td>
			</tr>
EOHTML;
				}
			echo <<<EOHTML
		</table>
	</article>
</section>
EOHTML;
			}
		}
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