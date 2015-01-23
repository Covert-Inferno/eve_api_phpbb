<?php

namespace	omni\eveapi\mcp;

class	timerboard
{
				var	$u_action;
				var	$p_master;

				function	timerboard(	&$p_master	)
				{
								$this->p_master	=	&$p_master;
				}

				function	main(	$id,	$mode	)
				{
								global	$auth,	$db,	$user,	$template;
								global	$config,	$action,	$phpbb_root_path,	$phpEx,	$phpbb_container;

								$this->page_title	=	'MCP_TIMERBOARD_CP';
								$timer_id									=	request_var('timer_id',	0);
								$start												=	request_var('start',	0);
								$submit											=	(isset($_POST['submit']))	?	true	:	false;
								$deletemark							=	(isset($_POST['deletemark']))	?	true	:	false;
								$editmark									=	(isset($_GET['edit']))	?	true	:	false;
								$marked											=	request_var('mark',	array(
												0));
								$pagination							=	$phpbb_container->get('pagination');

								$sort_hours	=	request_var('st',	0);
								$sort_key			=	request_var('sk',	'c');
								$sort_dir			=	request_var('sd',	'a');

								if	(	$timer_id	==	0	&&	$mode	==	'edit'	)
								{
												redirect(append_sid("{$phpbb_root_path}mcp.$phpEx",	"i=$id&amp;mode=list"));
								}

								$current_time	=	time();

								$types_list	=	array(
												'Structures'					=>	array(
																'Amarr Factory Outpost'											=>	'Amarr Factory Outpost',
																'Caldari Repair Outpost'										=>	'Caldari Repair Outpost',
																'Gallente Administrative Outpost'	=>	'Gallente Administrative Outpost',
																'Minmatar Refining Outpost'							=>	'Minmatar Refining Outpost',
																'Infrastructure Hub'														=>	'Infrastructure Hub',
																'Customs Office'																		=>	'Customs Office',
																'Sovereignty Blockade Unit'							=>	'Sovereignty Blockade Unit',
																'Territorial Claim Unit '									=>	'Territorial Claim Unit ',
												),
												'Control Towers'	=>	array(
																'Amarr Control Tower'																	=>	'Amarr Control Tower',
																'Amarr Control Tower Medium'										=>	'Amarr Control Tower Medium',
																'Amarr Control Tower Small'											=>	'Amarr Control Tower Small',
																'Angel Control Tower'																	=>	'Angel Control Tower',
																'Angel Control Tower Medium'										=>	'Angel Control Tower Medium',
																'Angel Control Tower Small'											=>	'Angel Control Tower Small',
																'Blood Control Tower'																	=>	'Blood Control Tower',
																'Blood Control Tower Medium'										=>	'Blood Control Tower Medium',
																'Blood Control Tower Small'											=>	'Blood Control Tower Small',
																'Caldari Control Tower'															=>	'Caldari Control Tower',
																'Caldari Control Tower Medium'								=>	'Caldari Control Tower Medium',
																'Caldari Control Tower Small'									=>	'Caldari Control Tower Small',
																'Dark Blood Control Tower'												=>	'Dark Blood Control Tower',
																'Dark Blood Control Tower Medium'					=>	'Dark Blood Control Tower Medium',
																'Dark Blood Control Tower Small'						=>	'Dark Blood Control Tower Small',
																'Domination Control Tower'												=>	'Domination Control Tower',
																'Domination Control Tower Medium'					=>	'Domination Control Tower Medium',
																'Domination Control Tower Small'						=>	'Domination Control Tower Small',
																'Dread Guristas Control Tower'								=>	'Dread Guristas Control Tower',
																'Dread Guristas Control Tower Medium'	=>	'Dread Guristas Control Tower Medium',
																'Dread Guristas Control Tower Small'		=>	'Dread Guristas Control Tower Small',
																'Gallente Control Tower'														=>	'Gallente Control Tower',
																'Gallente Control Tower Medium'							=>	'Gallente Control Tower Medium',
																'Gallente Control Tower Small'								=>	'Gallente Control Tower Small',
																'Guristas Control Tower'														=>	'Guristas Control Tower',
																'Guristas Control Tower Medium'							=>	'Guristas Control Tower Medium',
																'Guristas Control Tower Small'								=>	'Guristas Control Tower Small',
																'Minmatar Control Tower'														=>	'Minmatar Control Tower',
																'Minmatar Control Tower Medium'							=>	'Minmatar Control Tower Medium',
																'Minmatar Control Tower Small'								=>	'Minmatar Control Tower Small',
																'QA Control Tower'																				=>	'QA Control Tower',
																'QA Fuel Control Tower'															=>	'A Fuel Control Tower',
																'Sansha Control Tower'																=>	'Sansha Control Tower',
																'Sansha Control Tower Medium'									=>	'Sansha Control Tower Medium',
																'Sansha Control Tower Small'										=>	'Sansha Control Tower Small',
																'Serpentis Control Tower'													=>	'Serpentis Control Tower',
																'Serpentis Control Tower Medium'						=>	'Serpentis Control Tower Medium',
																'Serpentis Control Tower Small'							=>	'Serpentis Control Tower Small',
																'Shadow Control Tower'																=>	'Shadow Control Tower',
																'Shadow Control Tower Medium'									=>	'Shadow Control Tower Medium',
																'Shadow Control Tower Small'										=>	'Shadow Control Tower Small',
																'True Sansha Control Tower'											=>	'True Sansha Control Tower',
																'True Sansha Control Tower Medium'				=>	'True Sansha Control Tower Medium',
																'True Sansha Control Tower Small'					=>	'True Sansha Control Tower Small',
												),
								);

								if	(	$submit	||	$deletemark	||	$editmark	)
								{
												switch	(	$action	)
												{
																case	'create':
																				$date											=	new	DateTime();
																				$expire_days				=	request_var('timerDays',	0);
																				$expire_hours			=	request_var('timerHours',	0);
																				$expire_minutes	=	request_var('timerMinutes',	0);

																				switch	(	$expire_days	)
																				{
																								case	0:
																												break;

																								case	1:
																												$date->modify('+'	.	$expire_days	.	' day');
																												break;

																								default:
																												$date->modify('+'	.	$expire_days	.	' days');
																												break;
																				}

																				switch	(	$expire_hours	)
																				{
																								case	0:
																												break;

																								case	1:
																												$date->modify('+'	.	$expire_hours	.	' hour');
																												break;

																								default:
																												$date->modify('+'	.	$expire_hours	.	' hours');
																												break;
																				}

																				switch	(	$expire_minutes	)
																				{
																								case	0:
																												break;

																								case	1:
																												$date->modify('+'	.	$expire_minutes	.	' minute');
																												break;

																								default:
																												$date->modify('+'	.	$expire_minutes	.	' minutes');
																												break;
																				}

																				$expire	=	strtotime(date_format($date,	'Y-m-d H:i:s'));
																				$system	=	request_var('timerSystem',	'');

																				$sql				=	"SELECT mr.regionName
						FROM mapRegions mr
						LEFT JOIN mapSolarSystems mss ON mss.regionID = mr.regionID
						WHERE mss.solarSystemName = '"	.	$db->sql_escape($system)	.	"'";
																				$result	=	$db->sql_query($sql);
																				$region	=	$db->sql_fetchfield('regionName');

																				$db->sql_freeresult($result);

																				if	(	empty($region)	)
																				{
																								trigger_error('INVALID_SYSTEM_NAME');
																				}

																				$sql_ary	=	array(
																								'timerCreated'				=>	$current_time,
																								'timerType'							=>	request_var('timerType',	''),
																								'timerOwner'						=>	request_var('timerOwner',	''),
																								'timerRegion'					=>	$region,
																								'timerSystem'					=>	$system,
																								'timerAction'					=>	request_var('timerAction',	''),
																								'timerPlanet'					=>	request_var('timerPlanet',	''),
																								'timerMoon'							=>	request_var('timerMoon',	''),
																								'timerReinforced'	=>	request_var('timerReinforced',	''),
																								'timerExpired'				=>	$expire,
																								'timerMemo'							=>	request_var('timerMemo',	''),
																				);

																				$sql	=	'INSERT INTO timerboard '	.	$db->sql_build_array('INSERT',	$sql_ary);
																				$db->sql_query($sql);

																				add_log('admin',	'LOG_CREATE_TIMERBOARD');
																				redirect(append_sid("{$phpbb_root_path}mcp.$phpEx",	"i=$id&amp;mode=list"));
																				break;

																case	'edit':
																				$date											=	new	DateTime();
																				$expire_days				=	request_var('timerDays',	0);
																				$expire_hours			=	request_var('timerHours',	0);
																				$expire_minutes	=	request_var('timerMinutes',	0);

																				switch	(	$expire_days	)
																				{
																								case	0:
																												break;

																								case	1:
																												$date->modify('+'	.	$expire_days	.	' day');
																												break;

																								default:
																												$date->modify('+'	.	$expire_days	.	' days');
																												break;
																				}

																				switch	(	$expire_hours	)
																				{
																								case	0:
																												break;

																								case	1:
																												$date->modify('+'	.	$expire_hours	.	' hour');
																												break;

																								default:
																												$date->modify('+'	.	$expire_hours	.	' hours');
																												break;
																				}

																				switch	(	$expire_minutes	)
																				{
																								case	0:
																												break;

																								case	1:
																												$date->modify('+'	.	$expire_minutes	.	' minute');
																												break;

																								default:
																												$date->modify('+'	.	$expire_minutes	.	' minutes');
																												break;
																				}

																				$expire	=	strtotime(date_format($date,	'Y-m-d H:i:s'));
																				$system	=	request_var('timerSystem',	'');

																				$sql				=	"SELECT mr.regionName
						FROM mapRegions mr
						LEFT JOIN mapSolarSystems mss ON mss.regionID = mr.regionID
						WHERE mss.solarSystemName = '"	.	$db->sql_escape($system)	.	"'";
																				$result	=	$db->sql_query($sql);
																				$region	=	$db->sql_fetchfield('regionName');

																				$db->sql_freeresult($result);

																				if	(	empty($region)	)
																				{
																								trigger_error('INVALID_SYSTEM_NAME');
																				}

																				$sql_ary	=	array(
																								'timerType'							=>	request_var('timerType',	''),
																								'timerOwner'						=>	request_var('timerOwner',	''),
																								'timerRegion'					=>	$region,
																								'timerSystem'					=>	$system,
																								'timerAction'					=>	request_var('timerAction',	''),
																								'timerPlanet'					=>	request_var('timerPlanet',	''),
																								'timerMoon'							=>	request_var('timerMoon',	''),
																								'timerReinforced'	=>	request_var('timerReinforced',	''),
																								'timerExpired'				=>	$expire,
																								'timerMemo'							=>	request_var('timerMemo',	''),
																				);

																				$sql	=	'UPDATE timerboard
						SET '	.	$db->sql_build_array('UPDATE',	$sql_ary)	.	'
						WHERE timerID = '	.	(int)	$timer_id;
																				$db->sql_query($sql);

																				add_log('admin',	'LOG_EDIT_TIMERBOARD');
																				redirect(append_sid("{$phpbb_root_path}mcp.$phpEx",	"i=$id&amp;mode=list"));
																				break;

																case	'delete':
																				if	(	confirm_box(true)	)
																				{
																								if	(	!sizeof($marked)	)
																								{
																												trigger_error('NO_TIMERS_SELECTED');
																								}
																								else
																								{
																												delete_timer($marked);
																								}

																								redirect(append_sid("{$phpbb_root_path}mcp.$phpEx",	"i=$id&amp;mode=list"));
																				}
																				else
																				{
																								confirm_box(false,	$user->lang['CONFIRM_OPERATION'],	build_hidden_fields(array(
																												'deletemark'	=>	$deletemark,
																												'mark'							=>	$marked,
																												'i'										=>	$id,
																												'mode'							=>	$mode,
																												'action'					=>	$action,
																												'sk'									=>	$sort_key,
																												'sd'									=>	$sort_dir,
																								)));
																				}
																				break;
												}
								}

								switch	(	$mode	)
								{
												case	'create':
																$s_hidden_fields	=	build_hidden_fields(array(
																				'action'	=>	'create',
																));

																$type_select	=	'';

																foreach	(	$types_list	as	$category	=>	$data	)
																{
																				$type_select	.=	'<optgroup label="'	.	$category	.	'">';

																				foreach	(	$data	as	$key	=>	$value	)
																				{
																								$type_select	.=	'<option value="'	.	$key	.	'">'	.	$value	.	'</option>';
																				}

																				$type_select	.=	'</optgroup>';
																}

																$action_select	=	'<option value="Attack">Attack</option>';
																$action_select	.=	'<option value="Defend">Defend</option>';

																$reinforced_select	=	'<option value="None">None</option>';
																$reinforced_select	.=	'<option value="RF">RF</option>';
																$reinforced_select	.=	'<option value="RF1">RF1</option>';
																$reinforced_select	.=	'<option value="RF2">RF2</option>';

																$locations	=	array_combine(range(1,	40),	range(1,	40));

																$moon_select			=	$planet_select	=	'<option value="0">None</option>';

																foreach	(	$locations	as	$key	=>	$value	)
																{
																				$moon_select	.=	'<option value="'	.	$key	.	'">'	.	$value	.	'</option>';
																}

																foreach	(	$locations	as	$key	=>	$value	)
																{
																				$planet_select	.=	'<option value="'	.	$key	.	'">'	.	$value	.	'</option>';
																}

																$days				=	array_combine(range(1,	10),	range(1,	10));
																$hours			=	array_combine(range(1,	23),	range(1,	23));
																$minutes	=	array_combine(range(1,	59),	range(1,	59));

																$minutes_select	=	'<option value="0">0</option>';
																$hours_select			=	'<option value="0">0</option>';
																$days_select				=	'<option value="0">0</option>';

																foreach	(	$minutes	as	$key	=>	$value	)
																{
																				$minutes_select	.=	'<option value="'	.	$key	.	'">'	.	$value	.	'</option>';
																}

																foreach	(	$hours	as	$key	=>	$value	)
																{
																				$hours_select	.=	'<option value="'	.	$key	.	'">'	.	$value	.	'</option>';
																}

																foreach	(	$days	as	$key	=>	$value	)
																{
																				$days_select	.=	'<option value="'	.	$key	.	'">'	.	$value	.	'</option>';
																}

																// Now display the page
																$template->assign_vars(array(
																				'L_TITLE'											=>	$user->lang['MCP_TIMERBOARD_CREATE'],
																				'TYPE_SELECT'							=>	$type_select,
																				'ACTION_SELECT'					=>	$action_select,
																				'REINFORCED_SELECT'	=>	$reinforced_select,
																				'MOON_SELECT'							=>	$moon_select,
																				'PLANET_SELECT'					=>	$planet_select,
																				'DAYS_SELECT'							=>	$days_select,
																				'HOURS_SELECT'						=>	$hours_select,
																				'MINUTES_SELECT'				=>	$minutes_select,
																				'S_MCP_ACTION'				=>	build_url(array(
																								't',
																								'f',
																								'sd',
																								'st',
																								'sk',
																								'confirm_key')),
																				'S_HIDDEN_FIELDS'	=>	$s_hidden_fields,
																));

																$this->tpl_name	=	'mcp_timerboard_create';
																break;

												case	'edit':
																$sql				=	"SELECT timerID, timerCreated, timerType, timerOwner, timerRegion, timerSystem, timerAction, timerPlanet, timerMoon, timerReinforced, timerExpired, timerMemo
					FROM timerboard
					WHERE timerID = $timer_id";
																$result	=	$db->sql_query($sql);

																$timer_data	=	$db->sql_fetchrow($result);
																$db->sql_freeresult($result);

																$s_hidden_fields	=	build_hidden_fields(array(
																				'action'			=>	'edit',
																				'timer_id'	=>	$timer_data['timerID'],
																));

																$type_select	=	'';

																foreach	(	$types_list	as	$category	=>	$data	)
																{
																				$type_select	.=	'<optgroup label="'	.	$category	.	'">';

																				foreach	(	$data	as	$key	=>	$value	)
																				{
																								$type_select	.=	'<option value="'	.	$key	.	'"'	.	($key	==	$timer_data['timerType']	?	'selected'	:	'')	.	'>'	.	$value	.	'</option>';
																				}

																				$type_select	.=	'</optgroup>';
																}

																$action_select	=	'<option value="Attack"'	.	('Attack'	==	$timer_data['timerAction']	?	'selected'	:	'')	.	'>Attack</option>';
																$action_select	.=	'<option value="Defend"'	.	('Defend'	==	$timer_data['timerAction']	?	'selected'	:	'')	.	'>Defend</option>';

																$reinforced_select	=	'<option value="None"'	.	('None'	==	$timer_data['timerReinforced']	?	'selected'	:	'')	.	'>None</option>';
																$reinforced_select	.=	'<option value="RF"'	.	('RF'	==	$timer_data['timerReinforced']	?	'selected'	:	'')	.	'>RF</option>';
																$reinforced_select	.=	'<option value="RF1"'	.	('RF1'	==	$timer_data['timerReinforced']	?	'selected'	:	'')	.	'>RF1</option>';
																$reinforced_select	.=	'<option value="RF2"'	.	('RF2'	==	$timer_data['timerReinforced']	?	'selected'	:	'')	.	'>RF2</option>';

																$locations	=	array_combine(range(1,	40),	range(1,	40));

																$moon_select			=	$planet_select	=	'<option value="0">None</option>';

																foreach	(	$locations	as	$key	=>	$value	)
																{
																				$moon_select	.=	'<option value="'	.	$key	.	'"'	.	($key	==	$timer_data['timerMoon']	?	'selected'	:	'')	.	'>'	.	$value	.	'</option>';
																}

																foreach	(	$locations	as	$key	=>	$value	)
																{
																				$planet_select	.=	'<option value="'	.	$key	.	'"'	.	($key	==	$timer_data['timerPlanet']	?	'selected'	:	'')	.	'>'	.	$value	.	'</option>';
																}

																if	(	$timer_data['timerExpired']	<=	time()	)
																{
																				$time_left	=	0;
																}
																else
																{
																				$time_left	=	$timer_data['timerExpired']	-	time();
																}

																$days				=	array_combine(range(1,	10),	range(1,	10));
																$hours			=	array_combine(range(1,	23),	range(1,	23));
																$minutes	=	array_combine(range(1,	59),	range(1,	59));

																$minutes_left	=	floor(($time_left	%	3600)	/	60);
																$hours_left			=	floor(($time_left	%	86400)	/	3600);
																$days_left				=	floor($time_left	/	86400);

																$minutes_select	=	'<option value="0"'	.	(0	==	$minutes_left	?	'selected'	:	'')	.	'>0</option>';
																$hours_select			=	'<option value="0"'	.	(0	==	$hours_left	?	'selected'	:	'')	.	'>0</option>';
																$days_select				=	'<option value="0"'	.	(0	==	$days_left	?	'selected'	:	'')	.	'>0</option>';

																foreach	(	$minutes	as	$key	=>	$value	)
																{
																				$minutes_select	.=	'<option value="'	.	$key	.	'"'	.	($key	==	$minutes_left	?	'selected'	:	'')	.	'>'	.	$value	.	'</option>';
																}

																foreach	(	$hours	as	$key	=>	$value	)
																{
																				$hours_select	.=	'<option value="'	.	$key	.	'"'	.	($key	==	$hours_left	?	'selected'	:	'')	.	'>'	.	$value	.	'</option>';
																}

																foreach	(	$days	as	$key	=>	$value	)
																{
																				$days_select	.=	'<option value="'	.	$key	.	'"'	.	($key	==	$days_left	?	'selected'	:	'')	.	'>'	.	$value	.	'</option>';
																}

																// Now display the page
																$template->assign_vars(array(
																				'L_TITLE'	=>	$user->lang['MCP_TIMERBOARD_EDIT'],
																				'TYPE_SELECT'							=>	$type_select,
																				'OWNER'													=>	$timer_data['timerOwner'],
																				'SYSTEM'												=>	$timer_data['timerSystem'],
																				'ACTION_SELECT'					=>	$action_select,
																				'MOON_SELECT'							=>	$moon_select,
																				'PLANET_SELECT'					=>	$planet_select,
																				'REINFORCED_SELECT'	=>	$reinforced_select,
																				'DAYS_SELECT'							=>	$days_select,
																				'HOURS_SELECT'						=>	$hours_select,
																				'MINUTES_SELECT'				=>	$minutes_select,
																				'MEMO'														=>	$timer_data['timerMemo'],
																				'S_MCP_ACTION'				=>	build_url(array(
																								't',
																								'f',
																								'sd',
																								'st',
																								'sk',
																								'confirm_key')),
																				'S_HIDDEN_FIELDS'	=>	$s_hidden_fields,
																));

																$this->tpl_name	=	'mcp_timerboard_create';
																break;

												case	'list':
																$limit_hours		=	array(
																				0			=>	$user->lang['ALL_ENTRIES'],
																				1			=>	$user->lang['1_HOUR'],
																				7			=>	$user->lang['7_HOURS'],
																				24		=>	$user->lang['1_DAY'],
																				48		=>	$user->lang['2_DAYS'],
																				72		=>	$user->lang['3_DAYS'],
																				168	=>	$user->lang['1_WEEK']);
																$sort_by_text	=	array(
																				'e'	=>	$user->lang['TIMERBOARD_EXPIRES'],
																				'c'	=>	$user->lang['TIMERBOARD_CREATED'],
																				's'	=>	$user->lang['TIMERBOARD_SYSTEM'],
																				'r'	=>	$user->lang['TIMERBOARD_REGION'],
																				'o'	=>	$user->lang['TIMERBOARD_OWNER']);
																$sort_by_sql		=	array(
																				'e'	=>	'timerExpired',
																				'c'	=>	'timerCreated',
																				's'	=>	'timerSystem',
																				'r'	=>	'timerRegion',
																				'o'	=>	'timerOwner');

																$s_limit_hours	=	$s_sort_key				=	$s_sort_dir				=	$u_sort_param		=	'';
																gen_sort_selects($limit_hours,	$sort_by_text,	$sort_hours,	$sort_key,	$sort_dir,	$s_limit_hours,	$s_sort_key,	$s_sort_dir,	$u_sort_param);

																$sql_where	=	($sort_hours)	?	(time()	+	($sort_hours	*	3600))	:	(time()	+	31536000);
																$sql_sort		=	$sort_by_sql[$sort_key]	.	' '	.	(($sort_dir	==	'd')	?	'DESC'	:	'ASC');

																$sql				=	"SELECT timerID, timerCreated, timerType, timerOwner, timerRegion, timerSystem, timerAction, timerPlanet, timerMoon, timerReinforced, timerExpired, timerMemo
					FROM timerboard
					WHERE timerExpired <= $sql_where
					ORDER BY $sql_sort";
																$result	=	$db->sql_query_limit($sql,	$config['timerboard_display'],	$start);

																$rowset	=	array();
																while	(	$row				=	$db->sql_fetchrow($result)	)
																{
																				$rowset[]	=	$row;
																}
																$db->sql_freeresult($result);

																$sql				=	"SELECT COUNT(timerID) as total
					FROM timerboard
					WHERE timerExpired <= $sql_where";
																$result	=	$db->sql_query($sql);
																$total		=	$db->sql_fetchfield('total');

																$db->sql_freeresult($result);

																foreach	(	$rowset	as	$row	)
																{
																				$template->assign_block_vars('timerrow',	array(
																								'TIMER_ID'					=>	$row['timerID'],
																								'TYPE'									=>	$row['timerType'],
																								'OWNER'								=>	$row['timerOwner'],
																								'REGION'							=>	$row['timerRegion'],
																								'SYSTEM'							=>	$row['timerSystem'],
																								'ACTION'							=>	$row['timerAction'],
																								'LOCATION_PM'		=>	($row['timerMoon']	==	0)	?	(($row['timerPlanet']	==	0)	?	''	:	$row['timerPlanet'])	:	$row['timerPlanet']	.	'-'	.	$row['timerMoon'],
																								'REINFORCED'			=>	$row['timerReinforced'],
																								'MEMO'									=>	$row['timerMemo'],
																								'CREATE'							=>	$user->format_date($row['timerCreated']),
																								'EXPIRE'							=>	$user->format_date($row['timerExpired']),
																								'EXPIRE_RAW'			=>	($row['timerExpired']	*	1000),
																								'TIMEREXPIRED'	=>	($row['timerExpired']	<=	time())	?	' timer-expired'	:	0,
																								'COUNTDOWN'				=>	($row['timerExpired']	<=	time())	?	$user->lang['TIMERBOARD_EXPIRED']	:	seconds_2_human($row['timerExpired']	-	time()),
																								'EDITURL'						=>	append_sid("{$phpbb_root_path}mcp.$phpEx",	"i=$id&amp;mode=edit&amp;edit=1&amp;timer_id="	.	$row['timerID']),
																				));
																}
																unset($rowset);

																$s_hidden_fields	=	build_hidden_fields(array(
																				'action'	=>	'delete',
																));

																$base_url	=	$this->u_action	.	"&amp;$u_sort_param";
																$pagination->generate_template_pagination($base_url,	'pagination',	'start',	$total,	$config['timerboard_display'],	$start);

																// Now display the page
																$template->assign_vars(array(
																				'L_TITLE'	=>	$user->lang['MCP_TIMERBOARD_LIST'],
																				'S_MCP_ACTION'				=>	build_url(array(
																								't',
																								'f',
																								'sd',
																								'st',
																								'sk',
																								'confirm_key')),
																				'S_HIDDEN_FIELDS'	=>	$s_hidden_fields,
																				'S_TIMERS'								=>	($total	>	0),
																				'U_POST_ACTION'						=>	$this->u_action	.	"&amp;$u_sort_param&amp;start=$start",
																				'S_SELECT_SORT_DIR'		=>	$s_sort_dir,
																				'S_SELECT_SORT_KEY'		=>	$s_sort_key,
																				'S_SELECT_SORT_DAYS'	=>	$s_limit_hours,
																				'TOTAL'	=>	($total	==	1)	?	$user->lang['LIST_TIMER']	:	sprintf($user->lang['LIST_TIMERS'],	$total),
																));

																$this->tpl_name	=	'mcp_timerboard';
																break;
								}

								$template->assign_vars(array(
												'JSONURL'																	=>	append_sid("{$phpbb_root_path}json_system.$phpEx"),
												'L_CREATED'															=>	$user->lang['TIMERBOARD_CREATED'],
												'L_TYPE'																		=>	$user->lang['TIMERBOARD_TYPE'],
												'L_OWNER'																	=>	$user->lang['TIMERBOARD_OWNER'],
												'L_REGION'																=>	$user->lang['TIMERBOARD_REGION'],
												'L_SYSTEM'																=>	$user->lang['TIMERBOARD_SYSTEM'],
												'L_ACTION'																=>	$user->lang['TIMERBOARD_ACTION'],
												'L_LOCATION_PM'											=>	$user->lang['TIMERBOARD_LOCATION_PM'],
												'L_LOCATION_PLANET'							=>	$user->lang['TIMERBOARD_LOCATION_PLANET'],
												'L_LOCATION_MOON'									=>	$user->lang['TIMERBOARD_LOCATION_MOON'],
												'L_RF_STATUS'													=>	$user->lang['TIMERBOARD_RF_STATUS'],
												'L_DAYS_EXPIRE'											=>	$user->lang['TIMERBOARD_DAYS'],
												'L_HOURS_EXPIRE'										=>	$user->lang['TIMERBOARD_HOURS'],
												'L_MINUTES_EXPIRE'								=>	$user->lang['TIMERBOARD_MINUTES'],
												'L_RF'																				=>	$user->lang['TIMERBOARD_RF'],
												'L_COUNTDOWN'													=>	$user->lang['TIMERBOARD_COUNTDOWN'],
												'L_EXPIRES'															=>	$user->lang['TIMERBOARD_EXPIRES'],
												'L_MEMO'																		=>	$user->lang['TIMERBOARD_MEMO'],
												'L_NO_TIMERBOARD_ENTRIES'	=>	$user->lang['TIMERBOARD_NO_ENTRIES'],
								));
				}
}
