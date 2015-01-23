<?php

if	(	!defined('IN_PHPBB')	)
{
				exit;
}

class	ucp_eveapi
{
				var	$u_action;

				function	main(	$id,	$mode	)
				{
								global	$config,	$db,	$user,	$auth,	$template,	$phpbb_root_path,	$phpEx;

								$submit										=	(!empty($_POST['submit']))	?	true	:	false;
								$error											=	$data												=	array();
								$s_hidden_fields	=	'';
								$user->add_lang('mods/info_acp_eveapi');

								switch	(	$mode	)
								{
												case	'register_account':
																$this->tpl_name			=	'ucp_eveapi_account';
																$this->page_title	=	'UCP_EVEAPI_ACCOUNT';

																$data	=	array(
																				'username'					=>	utf8_normalize_nfc(request_var('username',	$user->data['username'],	true)),
																				'eveapi_keyid'	=>	request_var('eveapi_keyid',	$user->data['eveapi_keyid'],	true),
																				'eveapi_vcode'	=>	request_var('eveapi_vcode',	$user->data['eveapi_vcode'],	true),
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

																				if	(	$auth->acl_get('u_chgname')	&&	$config['allow_namechange']	)
																				{
																								$check_ary['username']	=	array(
																												array(
																																'string',
																																false,
																																$config['min_name_chars'],
																																$config['max_name_chars']),
																												array(
																																'username'),
																								);
																				}

																				$error	=	validate_data($data,	$check_ary);

																				if	(	!sizeof($error)	&&	$config['eveapi_validation']	)
																				{
																								if	(	!$auth->acl_get('u_chgname')	||	!$config['allow_namechange']	)
																								{
																												$data['username']	=	$user->data['username'];
																								}

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
																																								eveapi_setJabberAccess($user->data['user_id'],	$characterInfo['jabber'],	$extraGroups['jabber'],	$data['username']);
																																				}

																																				if	(	$config['eveapi_openfire_switch']	)
																																				{
																																								eveapi_setOpenFireAccess($user->data['user_id'],	$characterInfo['openfire'],	$extraGroups['openfire'],	$data['username']);
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
																								'user_character_id'	=>	isset($characterInfo["info"]["characterId"]) ? (int) $characterInfo["info"]["characterId"] : 0,
																								'username'							=>	($auth->acl_get('u_chgname')	&&	$config['allow_namechange'])	?	$data['username']	:	$user->data['username'],
																								'username_clean'	=>	($auth->acl_get('u_chgname')	&&	$config['allow_namechange'])	?	utf8_clean_string($data['username'])	:	$user->data['username_clean'],
																								'eveapi_keyid'			=>	$data['eveapi_keyid'],
																								'eveapi_vcode'			=>	$data['eveapi_vcode'],
																				);

																				if	(	$auth->acl_get('u_chgname')	&&	$config['allow_namechange']	&&	$data['username']	!=	$user->data['username']	)
																				{
																								add_log('user',	$user->data['user_id'],	'LOG_USER_UPDATE_NAME',	$user->data['username'],	$data['username']);
																				}

																				if	(	$data['eveapi_keyid']	!=	$user->data['eveapi_keyid']	||	$data['eveapi_vcode']	!=	$user->data['eveapi_vcode']	)
																				{
																								add_log('user',	$user->data['user_id'],	'User has updated his/her EVE API information.',	$user->data['username'],	$data['eveapi_keyid']	.	" -> "	.	$data['eveapi_vcode']);
																				}

																				$message	=	'EVEAPI_UPDATED';

																				if	(	sizeof($sql_ary)	)
																				{
																								$sql	=	'UPDATE '	.	USERS_TABLE	.	'
							SET '	.	$db->sql_build_array('UPDATE',	$sql_ary)	.	'
							WHERE user_id = '	.	$user->data['user_id'];
																								$db->sql_query($sql);
																				}
																}

																$template->assign_vars(array(
																				'ERROR'														=>	(sizeof($error))	?	implode('<br />',	$error)	:	'',
																				'USERNAME'											=>	$data['username'],
																				'EVEAPI_KEYID'							=>	$data['eveapi_keyid'],
																				'EVEAPI_VCODE'							=>	$data['eveapi_vcode'],
																				'EVEAPI_ACCESSMASK'		=>	eveapi_getAccessMask(),
																				'L_USERNAME_EXPLAIN'	=>	sprintf($user->lang[$config['allow_name_chars']	.	'_EXPLAIN'],	$config['min_name_chars'],	$config['max_name_chars']),
																				'L_TITLE'												=>	$user->lang['UCP_EVEAPI_ACCOUNT'],
																				'S_EVEAPI_VALIDATE'		=>	($config['eveapi_validation'])	?	true	:	false,
																				'S_CHANGE_USERNAME'		=>	($config['allow_namechange']	&&	$auth->acl_get('u_chgname'))	?	true	:	false,
																));
																break;

												case	'register_jabber':
																$this->tpl_name			=	'ucp_eveapi_jabber';
																$this->page_title	=	'UCP_EVEAPI_JABBER';

																if	(	(!$config['eveapi_openfire_switch']	&&	!$config['eveapi_jabber_masterswitch'])	||	empty($user->data["eveapi_keyid"])	||	empty($user->data["eveapi_vcode"])	)
																{
																				$template->assign_vars(array(
																								'L_TITLE'				=>	$user->lang['UCP_EVEAPI_JABBER'],
																								'L_DISABLED'	=>	$user->lang['EVEAPI_JABBER_DISABLED'],
																				));
																				break;
																}

																if	(	$submit	)
																{
																				$characterInfo	=	eveapi_checkThisCharacter($user->data['eveapi_keyid'],	$user->data['eveapi_vcode'],	$user->data['username']);

																				if	(	empty($characterInfo["error"])	&&	(!empty($characterInfo["forumGroups"])	||	(empty($characterInfo["forumGroups"])	&&	$config['eveapi_nonmember']))	)
																				{
																								$eveapi_forumGroups	=	$characterInfo["forumGroups"];
																								$extraGroups								=	eveapi_setForumGroups($user->data['user_id'],	$eveapi_forumGroups,	$user->data['username']);

																								$password	=	eveapi_randomString(8);
																								$result			=	eveapi_setOpenFireAccess($user->data['user_id'],	$characterInfo['openfire'],	$extraGroups['openfire'],	$user->data['username'],	$password);

																								if	(	$result	)
																								{
																												$user->data['user_jabber_password']	=	$password;
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
																				"_"),	$user->data['username']);

																$template->assign_vars(array(
																				'ERROR'													=>	(sizeof($error))	?	implode('<br />',	$error)	:	'',
																				'JABBER_USERNAME'			=>	$clean_username,
																				'JABBER_PASSWORD'			=>	$user->data['user_jabber_password'],
																				'JABBER_HOST'							=>	'almostawesome.org',
																				'L_TITLE'											=>	$user->lang['UCP_EVEAPI_JABBER'],
																				'L_JABBER'										=>	$user->lang['EVEAPI_JABBER_REGISTER'],
																				'L_JABBER_EXPLAIN'		=>	$user->lang['EVEAPI_JABBER_EXPLAIN'],
																				'L_JABBER_USERNAME'	=>	$user->lang['EVEAPI_JABBER_USERNAME'],
																				'L_JABBER_PASSWORD'	=>	$user->lang['EVEAPI_JABBER_PASSWORD'],
																				'L_JABBER_HOST'					=>	$user->lang['EVEAPI_JABBER_HOST'],
																				'L_CHANGE_REGISTER'	=>	(empty($user->data['user_jabber_password']))	?	$user->lang['REGISTER']	:	$user->lang['EVEAPI_JABBER_CHANGE_PASSWORD'],
																));
																break;

												case	'register_ts':
																$this->tpl_name			=	'ucp_eveapi_ts';
																$this->page_title	=	'UCP_EVEAPI_TS';

																if	(	!$config['eveapi_ts_masterswitch']	)
																{
																				$template->assign_vars(array(
																								'L_TITLE'				=>	$user->lang['UCP_EVEAPI_TS'],
																								'L_DISABLED'	=>	$user->lang['EVEAPI_TEAMSPEAK_DISABLED'],
																				));
																				break;
																}

																if	(	$submit	)
																{
																				try
																				{
																								$nickname	=	$config['eveapi_ts_nickname'];

																								if	(	eveapi_validateMixedalphanumeric($nickname)	!=	1	)
																								{
																												$nickname	=	"Cyerus";
																								}

																								$ts3_VirtualServer	=	TeamSpeak3::factory("serverquery://"	.	$config["eveapi_ts_username"]	.	":"	.	$config["eveapi_ts_password"]	.	"@"	.	$config["eveapi_ts_ip"]	.	":"	.	$config["eveapi_ts_port_query"]	.	"/?server_port="	.	$config["eveapi_ts_port_server"]	.	"&nickname="	.	$nickname);
																				}
																				catch	(	Exception	$e	)
																				{
																								$error[]	=	$user->lang['TEAMSPEAK_CONNECTION_FAILED'];
																				}

																				$eveapi_ts	=	eveapi_getTeamSpeakUID($user->data['username'],	$ts3_VirtualServer);

																				if	(	empty($eveapi_ts)	)
																				{
																								$error[]	=	$user->lang['TEAMSPEAK_USERNAME_NOT_FOUND'];
																				}
																				else
																				{
																								if	(	empty($user->data["eveapi_keyid"])	||	empty($user->data["eveapi_vcode"])	)
																								{
																												$error[]	=	$user->lang['TEAMSPEAK_NO_API'];
																								}
																								else
																								{
																												$sql_ary	=	array(
																																'eveapi_ts'	=>	$eveapi_ts,
																												);

																												$eveapi_teamSpeakGroups	=	array();

																												$characterInfo	=	eveapi_checkThisCharacter($user->data['eveapi_keyid'],	$user->data['eveapi_vcode'],	$user->data['username']);

																												if	(	empty($characterInfo["error"])	&&	(!empty($characterInfo["forumGroups"])	||	(empty($characterInfo["forumGroups"])	&&	$config['eveapi_nonmember']))	)
																												{
																																$eveapi_forumGroups					=	$characterInfo["forumGroups"];
																																$eveapi_teamSpeakGroups	=	$characterInfo["TSGroups"];
																																$extraGroups												=	eveapi_setForumGroups($user->data['user_id'],	$eveapi_forumGroups,	$user->data['username']);

																																if	(	$eveapi_ts	!=	$user->data['eveapi_ts']	&&	!empty($user->data['eveapi_ts'])	)
																																{
																																				eveapi_setTeamSpeakGroups($user->data['eveapi_ts'],	array(),	$user->data['username']);
																																}

																																if	(	$eveapi_ts	!=	""	)
																																{
																																				$TSverify	=	eveapi_setTeamSpeakGroups($eveapi_ts,	$eveapi_teamSpeakGroups,	$user->data['username'],	$ts3_VirtualServer,	$extraGroups["TS"]);

																																				if	(	$TSverify	!==	true	)
																																				{
																																								$error[]														=	$TSverify;
																																								$sql_ary['eveapi_ts']	=	$eveapi_ts												=	"";
																																				}
																																}

																																$user->data['eveapi_ts']	=	$eveapi_ts;
																												}

																												$sql	=	'UPDATE '	.	USERS_TABLE	.	'
  							SET '	.	$db->sql_build_array('UPDATE',	$sql_ary)	.	'
  							WHERE user_id = '	.	$user->data['user_id'];
																												$db->sql_query($sql);
																								}
																				}
																}

																$username	=	$user->data['username'];

																if	(	strlen($username)	>	30	)
																{
																				$username	=	substr($username,	0,	30);
																}

																$template->assign_vars(array(
																				'ERROR'																=>	(sizeof($error))	?	implode('<br />',	$error)	:	'',
																				'USERNAME'													=>	$username,
																				'EVEAPI_TS'												=>	(empty($user->data['eveapi_ts']))	?	$user->lang['EVEAPI_TEAMSPEAK_UID_EMPTY']	:	$user->data['eveapi_ts'],
																				'L_TITLE'														=>	$user->lang['UCP_EVEAPI_TS'],
																				'L_TEAMSPEAK'										=>	$user->lang['EVEAPI_TEAMSPEAK_REGISTER'],
																				'L_TEAMSPEAK_EXPLAIN'		=>	$user->lang['EVEAPI_TEAMSPEAK_EXPLAIN'],
																				'L_TEAMSPEAK_USERNAME'	=>	$user->lang['EVEAPI_TEAMSPEAK_USERNAME'],
																				'L_TEAMSPEAK_UID'						=>	$user->lang['EVEAPI_TEAMSPEAK_UID'],
																));
																break;
								}

								$template->assign_vars(array(
												'S_HIDDEN_FIELDS'	=>	$s_hidden_fields,
												'S_UCP_ACTION'				=>	$this->u_action,
								));
				}
}

?>