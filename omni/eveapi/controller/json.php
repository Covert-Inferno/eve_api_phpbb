<?php

namespace	omni\eveapi\controller;

class	json
{
				/* @var \phpbb\config\config */
				protected	$config;

				/* @var \phpbb\controller\helper */
				protected	$helper;

				/**
					* Constructor
					*
					* @param \phpbb\config\config		$config
					* @param \phpbb\controller\helper	$helper
					*/
				public	function	__construct(	\phpbb\config\config	$config,	\phpbb\controller\helper	$helper	)
				{
								$this->config	=	$config;
								$this->helper	=	$helper;
				}

				public	function	search($query)
				{
								$matches = array();

								if (!empty($query))
								{
									$sql = "SELECT solarSystemName
										FROM mapSolarSystems
										WHERE LOWER(solarSystemName) LIKE LOWER('" . $db->sql_escape($query) . "%')
										ORDER BY solarSystemName ASC LIMIT 10";
									$result = $db->sql_query($sql);

									while ($row = $db->sql_fetchrow($result))
									{
										$matches[] = array('system' => $row['solarSystemName']);
									}

									$db->sql_freeresult($result);
								}

								header('content-type: application/json; charset=utf-8');

								echo json_encode($matches);
				}
}
