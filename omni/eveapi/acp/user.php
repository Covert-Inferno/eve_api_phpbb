<?php

namespace	omni\eveapi\acp;

class	user
{
				var	$u_action;
				var	$p_master;

				function	user(	&$p_master	)
				{
								$this->p_master	=	&$p_master;
				}

				function	main(	$id,	$mode	)
				{
								global	$config,	$db,	$user,	$auth,	$template,	$cache;
								global	$phpbb_root_path,	$phpbb_admin_path,	$phpEx,	$table_prefix,	$file_uploads;
								global	$phpbb_dispatcher,	$request;
								global	$phpbb_container;

								$user->add_lang(array(
												'posting',
												'ucp',
												'acp/users'));
								$this->tpl_name	=	'acp_users';

								$error				=	array();
								$username	=	utf8_normalize_nfc(request_var('username',	'',	true));
								$user_id		=	request_var('u',	0);

								$submit	=	(isset($_POST['update'])	&&	!isset($_POST['cancel']))	?	true	:	false;

								$form_name	=	'omni/eveapi/user';
								add_form_key($form_name);

								// Show user selection mask
								if	(	!$username	&&	!$user_id	)
								{
												$this->page_title	=	'SELECT_USER';

												$template->assign_vars(array(
																'U_ACTION'										=>	$this->u_action,
																'ANONYMOUS_USER_ID'	=>	ANONYMOUS,
																'S_SELECT_USER'					=>	true,
																'U_FIND_USERNAME'			=>	append_sid("{$phpbb_root_path}memberlist.$phpEx",	'mode=searchuser&amp;form=select_user&amp;field=username&amp;select_single=true'),
												));

												return;
								}

								if	(	!$user_id	)
								{
												$sql					=	'SELECT user_id
																				FROM '	.	USERS_TABLE	.	"
																				WHERE username_clean = '"	.	$db->sql_escape(utf8_clean_string($username))	.	"'";
												$result		=	$db->sql_query($sql);
												$user_id	=	(int)	$db->sql_fetchfield('user_id');
												$db->sql_freeresult($result);

												if	(	!$user_id	)
												{
																trigger_error($user->lang['NO_USER']	.	adm_back_link($this->u_action),	E_USER_WARNING);
												}
								}

								// Generate content for all modes
								$sql						=	'SELECT u.*, s.*
																FROM '	.	USERS_TABLE	.	' u
																	LEFT JOIN '	.	SESSIONS_TABLE	.	' s ON (s.session_user_id = u.user_id)
																WHERE u.user_id = '	.	$user_id	.	'
																ORDER BY s.session_time DESC';
								$result			=	$db->sql_query_limit($sql,	1);
								$user_row	=	$db->sql_fetchrow($result);
								$db->sql_freeresult($result);

								if	(	!$user_row	)
								{
												trigger_error($user->lang['NO_USER']	.	adm_back_link($this->u_action),	E_USER_WARNING);
								}

								// Generate overall "header" for user admin
								$s_form_options	=	'';

								// Build modes dropdown list
								$sql				=	'SELECT module_mode, module_auth
												FROM '	.	MODULES_TABLE	.	"
												WHERE module_basename = 'acp_eveapi_user'
													AND module_enabled = 1
													AND module_class = 'acp'
												ORDER BY left_id, module_mode";
								$result	=	$db->sql_query($sql);

								$dropdown_modes	=	array();
								while	(	$row												=	$db->sql_fetchrow($result)	)
								{
												if	(	!$this->p_master->module_auth_self($row['module_auth'])	)
												{
																continue;
												}

												$dropdown_modes[$row['module_mode']]	=	true;
								}
								$db->sql_freeresult($result);

								foreach	(	$dropdown_modes	as	$module_mode	=>	$null	)
								{
												$selected	=	($mode	==	$module_mode)	?	' selected="selected"'	:	'';
												$s_form_options	.=	'<option value="'	.	$module_mode	.	'"'	.	$selected	.	'>'	.	$user->lang['ACP_USER_'	.	strtoupper($module_mode)]	.	'</option>';
								}

								$template->assign_vars(array(
												'U_BACK'											=>	$this->u_action,
												'U_MODE_SELECT'				=>	append_sid("{$phpbb_admin_path}index.$phpEx",	"i=$id&amp;u=$user_id"),
												'U_ACTION'									=>	$this->u_action	.	'&amp;u='	.	$user_id,
												'S_FORM_OPTIONS'			=>	$s_form_options,
												'MANAGED_USERNAME'	=>	$user_row['username'])
								);

								// Prevent normal users/admins change/view founders if they are not a founder by themselves
								if	(	$user->data['user_type']	!=	USER_FOUNDER	&&	$user_row['user_type']	==	USER_FOUNDER	)
								{
												trigger_error($user->lang['NOT_MANAGE_FOUNDER']	.	adm_back_link($this->u_action),	E_USER_WARNING);
								}

								$this->page_title	=	$user_row['username']	.	' :: '	.	$user->lang('ACP_USER_'	.	strtoupper($mode));

								switch	(	$mode	)
								{
												case	'eveapi_account':
																include($phpbb_root_path	.	'includes/functions_user.'	.	$phpEx);
																$user->add_lang('mods/info_acp_eveapi');

																$data	=	array(
																				'username'					=>	utf8_normalize_nfc(request_var('user',	$user_row['username'],	true)),
																				'eveapi_keyid'	=>	request_var('eveapi_keyid',	$user_row['eveapi_keyid'],	true),
																				'eveapi_vcode'	=>	request_var('eveapi_vcode',	$user_row['eveapi_vcode'],	true),
																);

																if	(	$submit	)
																{
																				$check_ary	=	array(
																								'eveapi_keyid'	=>	array(
																												array(
																																'string',
																																false,
																																1,
																																15),
																								),
																								'eveapi_vcode'	=>	array(
																												array(
																																'string',
																																false,
																																64,
																																64),
																								),
																				);

																				$check_ary['username']	=	array(
																								array(
																												'string',
																												false,
																												$config['min_name_chars'],
																												$config['max_name_chars']),
																								array(
																												'username'),
																				);

																				$error	=	validate_data($data,	$check_ary);

																				if	(	!sizeof($error)	&&	$config['eveapi_validation']	)
																				{
																								$eveapi_teamSpeakGroups	=	array();
																								$characterInfo										=	eveapi_checkThisCharacter($data['eveapi_keyid'],	$data['eveapi_vcode'],	$data['username']);

																								if	(	empty($characterInfo["error"])	)
																								{
																												if	(	!empty($characterInfo["forumGroups"])	||	(empty($characterInfo["forumGroups"])	&&	$config['eveapi_nonmember'])	)
																												{
																																$eveapi_forumGroups					=	$characterInfo["forumGroups"];
																																$eveapi_teamSpeakGroups	=	$characterInfo["TSGroups"];
																																$extraGroups												=	eveapi_setForumGroups($user->data['user_id'],	$eveapi_forumGroups,	$data['username']);

																																if	(	$config['eveapi_jabber_masterswitch']	)
																																{
																																				if	(	$config['eveapi_ejabber_switch']	)
																																				{
																																								eveapi_setJabberAccess($user_row['user_id'],	$characterInfo['jabber'],	$extraGroups['jabber'],	$data['username']);
																																				}

																																				if	(	$config['eveapi_openfire_switch']	)
																																				{
																																								eveapi_setOpenFireAccess($user_row['user_id'],	$characterInfo['openfire'],	$extraGroups['openfire'],	$data['username']);
																																				}
																																}
																												}
																												else
																												{
																																$error[]	=	'This character is not permitted to have an account on this forum.';
																												}
																								}
																								else
																								{
																												$error	=	array_merge($error,	$characterInfo["error"]);
																								}
																				}

																				$sql_ary	=	array(
																								'user_character_id'	=>	isset($characterInfo["info"]["characterId"])	?	(int)	$characterInfo["info"]["characterId"]	:	0,
																								'username'										=>	$data['username'],
																								'username_clean'				=>	utf8_clean_string($data['username']),
																								'eveapi_keyid'						=>	$data['eveapi_keyid'],
																								'eveapi_vcode'						=>	$data['eveapi_vcode'],
																				);

																				if	(	$data['username']	!=	$user_row['username']	)
																				{
																								add_log('user',	$user_row['user_id'],	'LOG_USER_UPDATE_NAME',	$user_row['username'],	$data['username']);
																				}

																				if	(	$data['eveapi_keyid']	!=	$user_row['eveapi_keyid']	||	$data['eveapi_vcode']	!=	$user_row['eveapi_vcode']	)
																				{
																								add_log('user',	$user_row['user_id'],	'User has updated his/her EVE API information.',	$user_row['username'],	$data['eveapi_keyid']	.	" -> "	.	$data['eveapi_vcode']);
																				}

																				$message	=	'EVEAPI_UPDATED';

																				if	(	sizeof($sql_ary)	)
																				{
																								$sql	=	'UPDATE '	.	USERS_TABLE	.	'
																																SET '	.	$db->sql_build_array('UPDATE',	$sql_ary)	.	'
																																WHERE user_id = '	.	$user_row['user_id'];
																								$db->sql_query($sql);
																								add_log('admin',	'LOG_USER_USER_UPDATE',	$data['username']);
																				}
																}

																$inactive_reason	=	'';
																if	(	$user_row['user_type']	==	USER_INACTIVE	)
																{
																				$inactive_reason	=	$user->lang['INACTIVE_REASON_UNKNOWN'];

																				switch	(	$user_row['user_inactive_reason']	)
																				{
																								case	INACTIVE_REGISTER:
																												$inactive_reason	=	$user->lang['INACTIVE_REASON_REGISTER'];
																												break;

																								case	INACTIVE_PROFILE:
																												$inactive_reason	=	$user->lang['INACTIVE_REASON_PROFILE'];
																												break;

																								case	INACTIVE_MANUAL:
																												$inactive_reason	=	$user->lang['INACTIVE_REASON_MANUAL'];
																												break;

																								case	INACTIVE_REMIND:
																												$inactive_reason	=	$user->lang['INACTIVE_REASON_REMIND'];
																												break;

																								case	INACTIVE_EVEAPI_INVALID:
																												$inactive_reason	=	$user->lang['EVEAPI_INVALID_API_KEY'];
																												break;

																								case	INACTIVE_EVEAPI_NONMEMBER:
																												$inactive_reason	=	$user->lang['EVEAPI_NOT_ALLOWED_ACCOUNT'];
																												break;
																				}
																}

																$template->assign_vars(array(
																				'S_EVEAPI_REGISTER'				=>	true,
																				'USER'																	=>	$data['username'],
																				'EVEAPI_KEYID'									=>	$data['eveapi_keyid'],
																				'EVEAPI_VCODE'									=>	$data['eveapi_vcode'],
																				'EVEAPI_ACCESSMASK'				=>	eveapi_getAccessMask(),
																				'L_NAME_CHARS_EXPLAIN'	=>	$user->lang($config['allow_name_chars']	.	'_EXPLAIN',	$user->lang('CHARACTERS',	(int)	$config['min_name_chars']),	$user->lang('CHARACTERS',	(int)	$config['max_name_chars'])),
																				'L_EVEAPI_ACCOUNT'					=>	$user->lang['UCP_EVEAPI_ACCOUNT'],
																				'S_USER_INACTIVE'						=>	($user_row['user_type']	==	USER_INACTIVE)	?	true	:	false,
																				'USER_INACTIVE_REASON'	=>	$inactive_reason,
																));
																break;

												case	'eveapi_jabber':
																$user->add_lang('mods/info_acp_eveapi');

																if	(	$submit	)
																{
																				$characterInfo	=	eveapi_checkThisCharacter($user_row['eveapi_keyid'],	$user_row['eveapi_vcode'],	$user_row['username']);

																				if	(	empty($characterInfo["error"])	&&	(!empty($characterInfo["forumGroups"])	||	(empty($characterInfo["forumGroups"])	&&	$config['eveapi_nonmember']))	)
																				{
																								$eveapi_forumGroups	=	$characterInfo["forumGroups"];
																								$extraGroups								=	eveapi_setForumGroups($user_row['user_id'],	$eveapi_forumGroups,	$user_row['username']);

																								$password	=	eveapi_randomString(8);
																								$result			=	eveapi_setOpenFireAccess($user_row['user_id'],	$characterInfo['openfire'],	$extraGroups['openfire'],	$user_row['username'],	$password);

																								if	(	$result	)
																								{
																												$user_row['user_jabber_password']	=	$password;
																								}
																								else
																								{
																												$error[]	=	$user->lang['JABBER_REGISTRATION_FAILED'];
																								}
																				}
																}

																$clean_username	=	str_replace(array(
																				" ",
																				"'"),	array(
																				"_",
																				"_"),	$user_row['username']);

																$template->assign_vars(array(
																				'S_EVEAPI_JABBER'			=>	true,
																				'JABBER_USERNAME'			=>	$clean_username,
																				'JABBER_PASSWORD'			=>	$user_row['user_jabber_password'],
																				'JABBER_HOST'							=>	$config['eveapi_jabber_hostname'],
																				'L_EVEAPI_JABBER'			=>	$user->lang['UCP_EVEAPI_JABBER'],
																				'L_JABBER'										=>	$user->lang['EVEAPI_JABBER_REGISTER'],
																				'L_JABBER_EXPLAIN'		=>	$user->lang['EVEAPI_JABBER_EXPLAIN'],
																				'L_JABBER_USERNAME'	=>	$user->lang['EVEAPI_JABBER_USERNAME'],
																				'L_JABBER_PASSWORD'	=>	$user->lang['EVEAPI_JABBER_PASSWORD'],
																				'L_JABBER_HOST'					=>	$user->lang['EVEAPI_JABBER_HOST'],
																));
																break;

												case	'eveapi_ts':
																$user->add_lang('mods/info_acp_eveapi');

																if	(	$submit	)
																{
																				try
																				{
																								$nickname	=	$config['eveapi_ts_nickname'];

																								if	(	eveapi_validateMixedalphanumeric($nickname)	!=	1	)
																								{
																												$nickname	=	"phpBBbot";
																								}

																								$ts3_VirtualServer	=	TeamSpeak3::factory("serverquery://"	.	$config["eveapi_ts_username"]	.	":"	.	$config["eveapi_ts_password"]	.	"@"	.	$config["eveapi_ts_ip"]	.	":"	.	$config["eveapi_ts_port_query"]	.	"/?server_port="	.	$config["eveapi_ts_port_server"]	.	"&nickname="	.	$nickname);
																				}
																				catch	(	Exception	$e	)
																				{
																								$error[]	=	$user->lang['TEAMSPEAK_CONNECTION_FAILED'];
																				}

																				$eveapi_ts	=	eveapi_getTeamSpeakUID($user_row['username'],	$ts3_VirtualServer);

																				if	(	empty($eveapi_ts)	)
																				{
																								$error[]	=	$user->lang['TEAMSPEAK_USERNAME_NOT_FOUND'];
																				}
																				else
																				{
																								if	(	empty($user_row["eveapi_keyid"])	||	empty($user_row["eveapi_vcode"])	)
																								{
																												$error[]	=	$user->lang['TEAMSPEAK_NO_API'];
																								}
																								else
																								{
																												$sql_ary	=	array(
																																'eveapi_ts'	=>	$eveapi_ts,
																												);

																												$eveapi_teamSpeakGroups	=	array();

																												$characterInfo	=	eveapi_checkThisCharacter($user_row['eveapi_keyid'],	$user_row['eveapi_vcode'],	$user_row['username']);

																												if	(	empty($characterInfo["error"])	&&	(!empty($characterInfo["forumGroups"])	||	(empty($characterInfo["forumGroups"])	&&	$config['eveapi_nonmember']))	)
																												{
																																$eveapi_forumGroups					=	$characterInfo["forumGroups"];
																																$eveapi_teamSpeakGroups	=	$characterInfo["TSGroups"];
																																$extraGroups												=	eveapi_setForumGroups($user_row['user_id'],	$eveapi_forumGroups,	$user_row['username']);

																																if	(	$eveapi_ts	!=	$user_row['eveapi_ts']	&&	!empty($user_row['eveapi_ts'])	)
																																{
																																				eveapi_setTeamSpeakGroups($user_row['eveapi_ts'],	array(),	$user_row['username']);
																																}

																																if	(	$eveapi_ts	!=	""	)
																																{
																																				$TSverify	=	eveapi_setTeamSpeakGroups($eveapi_ts,	$eveapi_teamSpeakGroups,	$user_row['username'],	$ts3_VirtualServer,	$extraGroups["TS"]);

																																				if	(	$TSverify	!==	true	)
																																				{
																																								$error[]														=	$TSverify;
																																								$sql_ary['eveapi_ts']	=	$eveapi_ts												=	"";
																																				}
																																}

																																$user_row['eveapi_ts']	=	$eveapi_ts;
																												}

																												$sql	=	'UPDATE '	.	USERS_TABLE	.	'
																																				SET '	.	$db->sql_build_array('UPDATE',	$sql_ary)	.	'
																																				WHERE user_id = '	.	$user_row['user_id'];
																												$db->sql_query($sql);
																								}
																				}
																}

																$username	=	$user_row['username'];

																if	(	strlen($username)	>	30	)
																{
																				$username	=	substr($username,	0,	30);
																}

																$template->assign_vars(array(
																				'S_EVEAPI_TS'										=>	true,
																				'USERNAME'													=>	$username,
																				'EVEAPI_TS'												=>	(empty($user_row['eveapi_ts']))	?	$user->lang['EVEAPI_TEAMSPEAK_UID_EMPTY']	:	$user_row['eveapi_ts'],
																				'L_EVEAPI_TS'										=>	$user->lang['UCP_EVEAPI_TS'],
																				'L_TEAMSPEAK'										=>	$user->lang['EVEAPI_TEAMSPEAK_REGISTER'],
																				'L_TEAMSPEAK_EXPLAIN'		=>	$user->lang['EVEAPI_TEAMSPEAK_EXPLAIN'],
																				'L_TEAMSPEAK_USERNAME'	=>	$user->lang['EVEAPI_TEAMSPEAK_USERNAME'],
																				'L_TEAMSPEAK_UID'						=>	$user->lang['EVEAPI_TEAMSPEAK_UID'],
																));
																break;
								}

								// Assign general variables
								$template->assign_vars(array(
												'S_ERROR'			=>	(sizeof($error))	?	true	:	false,
												'ERROR_MSG'	=>	(sizeof($error))	?	implode('<br />',	$error)	:	'')
								);
				}
}
