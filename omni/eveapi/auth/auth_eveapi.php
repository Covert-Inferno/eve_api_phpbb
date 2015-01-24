<?php

/**
	*
	* This file is part of the phpBB Forum Software package.
	*
	* @copyright (c) phpBB Limited <https://www.phpbb.com>
	* @license GNU General Public License, version 2 (GPL-2.0)
	*
	* For full copyright and license information, please see
	* the docs/CREDITS.txt file.
	*
	*/

namespace omni\eveapi\auth;

/**
	* Permission/Auth class
	*/
class	auth_eveapi extends \phpbb\auth\auth
{
				/**
					* Authentication plug-ins is largely down to Sergey Kanareykin, our thanks to him.
					*/
				function	login(	$username,	$password,	$autologin	=	false,	$viewonline	=	1,	$admin	=	0, $eveapi_use = false, $eveapi_keyid = "", $eveapi_vcode = ""	)
				{
								global	$db,	$user,	$phpbb_root_path,	$phpEx,	$phpbb_container;

								$provider_collection	=	$phpbb_container->get('auth.provider_collection');

								$provider	=	$provider_collection->get_provider();

								if	(	$provider	)
								{
												$login	=	$provider->login($username,	$password);

												// If the auth module wants us to create an empty profile do so and then treat the status as LOGIN_SUCCESS
												if	(	$login['status']	==	LOGIN_SUCCESS_CREATE_PROFILE	)
												{
																// we are going to use the user_add function so include functions_user.php if it wasn't defined yet
																if	(	!function_exists('user_add')	)
																{
																				include($phpbb_root_path	.	'includes/functions_user.'	.	$phpEx);
																}

																user_add($login['user_row'],	(isset($login['cp_data']))	?	$login['cp_data']	:	false);

																$sql				=	'SELECT user_id, username, user_password, user_passchg, user_email, user_type
																								FROM '	.	USERS_TABLE	.	"
																								WHERE username_clean = '"	.	$db->sql_escape(utf8_clean_string($username))	.	"'";
																$result	=	$db->sql_query($sql);
																$row				=	$db->sql_fetchrow($result);
																$db->sql_freeresult($result);

																if	(	!$row	)
																{
																				return	array(
																								'status'				=>	LOGIN_ERROR_EXTERNAL_AUTH,
																								'error_msg'	=>	'AUTH_NO_PROFILE_CREATED',
																								'user_row'		=>	array(
																												'user_id'	=>	ANONYMOUS),
																				);
																}

																$login	=	array(
																				'status'				=>	LOGIN_SUCCESS,
																				'error_msg'	=>	false,
																				'user_row'		=>	$row,
																);
												}

												if	(	$eveapi_use	&&	($login['status']	==	LOGIN_ERROR_ACTIVE	||	$login['status']	==	LOGIN_SUCCESS)	)
												{
																if	(	$login['status']	==	LOGIN_SUCCESS	)
																{
																				$login['status']	=	LOGIN_ERROR_ACTIVE;
																}

																if	(	!empty($eveapi_keyid)	&&	!empty($eveapi_vcode)	)
																{
																				$sql				=	'SELECT user_id, username
																												FROM '	.	USERS_TABLE	.	"
																												WHERE username_clean = '"	.	$db->sql_escape(utf8_clean_string($username))	.	"'";
																				$result	=	$db->sql_query($sql);
																				$row				=	$db->sql_fetchrow($result);
																				$db->sql_freeresult($result);

																				$characterInfo	=	eveapi_checkThisCharacter($eveapi_keyid,	$eveapi_vcode,	$row['username']);

																				if	(	empty($characterInfo["error"])	)
																				{
																								if	(	!empty($characterInfo["forumGroups"])	||	(empty($characterInfo["forumGroups"])	&&	$config['eveapi_nonmember'])	)
																								{
																												$sql	=	"UPDATE "	.	USERS_TABLE	.	"
																																				SET eveapi_keyid = "	.	$db->sql_escape($eveapi_keyid)	.	",
																																						eveapi_vcode = '"	.	$db->sql_escape($eveapi_vcode)	.	"',
																																						user_character_id = '"	.	(int)	$characterInfo["info"]["characterId"]	.	"'
																																				WHERE user_id = "	.	(int)	$row['user_id'];
																												$db->sql_query($sql);

																												$eveapi_forumGroups	=	$characterInfo["forumGroups"];
																												$extraGroups								=	eveapi_setForumGroups($row['user_id'],	$eveapi_forumGroups,	$row['username']);

																												if	(	$config['eveapi_jabber_masterswitch']	)
																												{
																																if	(	$config['eveapi_openfire_switch']	)
																																{
																																				eveapi_setOpenFireAccess($row['user_id'],	$characterInfo['openfire'],	$extraGroups['openfire'],	$row['username']);
																																}
																												}

																												$login['status']				=	LOGIN_SUCCESS;
																												$login['error_msg']	=	false;

																												user_active_flip('activate',	$row['user_id']);
																								}
																								else
																								{
																												$login['error_msg']	=	'This character is not permitted to have an account on this forum.';
																								}
																				}
																				else
																				{
																								$login['error_msg']	=	"Wrong EVE API details!";
																				}
																}
																else
																{
																				$login['error_msg']	=	"Invalid Key ID and/or Verification Code!";
																}
												}

												// If the auth provider wants us to link an empty account do so and redirect
												if	(	$login['status']	==	LOGIN_SUCCESS_LINK_PROFILE	)
												{
																// If this status exists a fourth field is in the $login array called 'redirect_data'
																// This data is passed along as GET data to the next page allow the account to be linked

																$params	=	array(
																				'mode'	=>	'login_link');
																$url				=	append_sid($phpbb_root_path	.	'ucp.'	.	$phpEx,	array_merge($params,	$login['redirect_data']));

																redirect($url);
												}

												// If login succeeded, we will log the user in... else we pass the login array through...
												if	(	$login['status']	==	LOGIN_SUCCESS	)
												{
																$old_session_id	=	$user->session_id;

																if	(	$admin	)
																{
																				global	$SID,	$_SID;

																				$cookie_expire	=	time()	-	31536000;
																				$user->set_cookie('u',	'',	$cookie_expire);
																				$user->set_cookie('sid',	'',	$cookie_expire);
																				unset($cookie_expire);

																				$SID														=	'?sid=';
																				$user->session_id	=	$_SID													=	'';
																}

																$result	=	$user->session_create($login['user_row']['user_id'],	$admin,	$autologin,	$viewonline);

																// Successful session creation
																if	(	$result	===	true	)
																{
																				// If admin re-authentication we remove the old session entry because a new one has been created...
																				if	(	$admin	)
																				{
																								// the login array is used because the user ids do not differ for re-authentication
																								$sql	=	'DELETE FROM '	.	SESSIONS_TABLE	.	"
							WHERE session_id = '"	.	$db->sql_escape($old_session_id)	.	"'
							AND session_user_id = {$login['user_row']['user_id']}";
																								$db->sql_query($sql);
																				}

																				return	array(
																								'status'				=>	LOGIN_SUCCESS,
																								'error_msg'	=>	false,
																								'user_row'		=>	$login['user_row'],
																				);
																}

																return	array(
																				'status'				=>	LOGIN_BREAK,
																				'error_msg'	=>	$result,
																				'user_row'		=>	$login['user_row'],
																);
												}

												return	$login;
								}

								trigger_error('Authentication method not found',	E_USER_ERROR);
				}
}
