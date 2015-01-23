<?php

namespace	omni\eveapi\cron;

class	cron
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

				public	function	cron()
				{
								if	(	!function_exists('user_delete')	)
								{
												include($phpbb_root_path	.	'includes/functions_user.'	.	$phpEx);
								}

								$cronlog	=	new	Logging();
//cronlog("Starting");
// This script will take a while.  We should disable max_execution_time.
								@ini_set('max_execution_time',	900);

								if	(	$config['eveapi_validation']	)
								{
												// *******************************************************************
												// ** STANDINGS START ************************************************
												// *******************************************************************

												$characterID											=	0;
												$standingsCharacterAPI	=	array();

												if	(	$config['eveapi_s_masterswitch']	&&	$config['eveapi_s_character']	&&	$config['eveapi_s_charactername']	!=	""	)
												{
																$standingsCharacterAPI	=	eveapi_getStandingsCharacterAPI();
																$pheal																	=	new	\Pheal\Pheal($standingsCharacterAPI['keyID'],	$standingsCharacterAPI['vCode']);

																try
																{
																				$result	=	$pheal->accountScope->APIKeyInfo();
																				if	(	$result->key->accessMask	&	16	&&	($result->key->type	==	"Character"	||	$result->key->type	==	"Account")	)
																				{
																								foreach	(	$result->key->characters	as	$character	)
																								{
																												if	(	strtolower($character->characterName)	==	strtolower($standingsCharacterAPI['characterName'])	)
																												{
																																$characterID			=	$character->characterID;
																																$characterName	=	$character->characterName;
																												}
																								}
																				}
																				else
																				{
																								cronlog("Wrong accessMask",	"Standings Character -> "	.	$standingsCharacterAPI['characterName']);
																				}
																}
																catch	(	\Pheal\Exceptions\PhealHTTPException	$e	)
																{
																				// HTTP error
																				cronlog('EVE API HTTP error: '	.	$e->getCode()	.	' -> '	.	$e->getMessage(),	"Standings Character -> "	.	$standingsCharacterAPI['characterName']);
																}
																catch	(	\Pheal\Exceptions\PhealAPIException	$e	)
																{
																				// API error
																				cronlog('EVE API Key error: '	.	$e->getCode()	.	' -> '	.	$e->getMessage(),	"Standings Character -> "	.	$standingsCharacterAPI['characterName']);
																}
																catch	(	\Pheal\Exceptions\PhealConnectionException	$e	)
																{
																				// Connection error
																				cronlog('EVE API Connection error: '	.	$e->getCode()	.	' -> '	.	$e->getMessage(),	"Standings Character -> "	.	$standingsCharacterAPI['characterName']);
																}
																catch	(	\Pheal\Exceptions\PhealException	$e	)
																{
																				// Any other error, in other words "Unknown error"
																				cronlog('EVE API Generic error: '	.	$e->getCode()	.	' -> '	.	$e->getMessage(),	"Standings Character -> "	.	$standingsCharacterAPI['characterName']);
																}
																catch	(	Exception	$e	)
																{
																				// Any other error, in other words "Unknown error"
																				cronlog('EVE API Generic error: '	.	$e->getCode()	.	' -> '	.	$e->getMessage(),	"Standings Character -> "	.	$standingsCharacterAPI['characterName']);
																}

																if	(	$characterID	>	0	)
																{
																				try
																				{
																								$result	=	$pheal->charScope->contactList(array(
																												"characterID"	=>	$characterID));

																								//cronlog("Removing old Character-level standings");
																								$db->sql_query('DELETE FROM '	.	TBL_EVEAPI_STANDINGS	.	' WHERE standingsType = '	.	EVEAPI_STANDINGSTYPE_CHARACTER);

																								foreach	(	$result->contactList	as	$contact	)
																								{
																												if	(	$contact->contactID	<	10000000	)
																																continue;		// Filter out NPCs (agents)
																												if	(	$contact->standing	!=	-10	&&	$contact->standing	!=	-5	&&	$contact->standing	!=	0	&&	$contact->standing	!=	5	&&	$contact->standing	!=	10	)
																																continue;

																												$sql	=	'INSERT INTO '	.	TBL_EVEAPI_STANDINGS	.	' '	.	$db->sql_build_array('INSERT',	array(
																																				"standingsName"			=>	$contact->contactName,
																																				"standingsAmount"	=>	$contact->standing,
																																				"standingsType"			=>	EVEAPI_STANDINGSTYPE_CHARACTER
																												));
																												$db->sql_query($sql);
																								}
																								//cronlog("New Character-level standings successfully imported!", "Standings Character -> " . $standingsCharacterAPI['characterName']);
																				}
																				catch	(	\Pheal\Exceptions\PhealHTTPException	$e	)
																				{
																								// HTTP error
																								cronlog('EVE API HTTP error: '	.	$e->getCode()	.	' -> '	.	$e->getMessage(),	"Standings Character -> "	.	$standingsCharacterAPI['characterName']);
																				}
																				catch	(	\Pheal\Exceptions\PhealAPIException	$e	)
																				{
																								// API error
																								cronlog('EVE API Key error: '	.	$e->getCode()	.	' -> '	.	$e->getMessage(),	"Standings Character -> "	.	$standingsCharacterAPI['characterName']);
																				}
																				catch	(	\Pheal\Exceptions\PhealConnectionException	$e	)
																				{
																								// Connection error
																								cronlog('EVE API Connection error: '	.	$e->getCode()	.	' -> '	.	$e->getMessage(),	"Standings Character -> "	.	$standingsCharacterAPI['characterName']);
																				}
																				catch	(	\Pheal\Exceptions\PhealException	$e	)
																				{
																								// Any other error, in other words "Unknown error"
																								cronlog('EVE API Generic error: '	.	$e->getCode()	.	' -> '	.	$e->getMessage(),	"Standings Character -> "	.	$standingsCharacterAPI['characterName']);
																				}
																				catch	(	Exception	$e	)
																				{
																								// Any other error, in other words "Unknown error"
																								cronlog('EVE API Generic error: '	.	$e->getCode()	.	' -> '	.	$e->getMessage(),	"Standings Character -> "	.	$standingsCharacterAPI['characterName']);
																				}
																}
												}

												$characterID	=	0;
												if	(	$config['eveapi_s_masterswitch']	&&	($config['eveapi_s_corporation']	||	$config['eveapi_s_alliance'])	&&	$config['eveapi_s_charactername']	!=	""	&&	$config['eveapi_s_corp_keyid']	>	0	&&	$config['eveapi_s_corp_vcode']	!=	""	)
												{
																if	(	empty($standingsCharacterAPI)	)
																{
																				$standingsCharacterAPI	=	eveapi_getStandingsCharacterAPI();
																}

																$pheal							=	new	\Pheal\Pheal($config['eveapi_s_corp_keyid'],	$config['eveapi_s_corp_vcode']);
																$characterID	=	0;

																try
																{
																				$result	=	$pheal->accountScope->APIKeyInfo();
																				if	(	$result->key->accessMask	&	16	&&	$result->key->type	==	"Corporation"	&&	strtolower($result->key->characters[0]->characterName)	==	strtolower($standingsCharacterAPI['characterName'])	)
																				{
																								$characterID	=	$result->key->characters[0]->characterID;
																				}
																				else
																				{
																								cronlog("Wrong accessMask",	"Standings Character -> "	.	$standingsCharacterAPI['characterName']);
																				}
																}
																catch	(	\Pheal\Exceptions\PhealHTTPException	$e	)
																{
																				// HTTP error
																				cronlog('EVE API HTTP error: '	.	$e->getCode()	.	' -> '	.	$e->getMessage(),	"Standings Character -> "	.	$standingsCharacterAPI['characterName']);
																}
																catch	(	\Pheal\Exceptions\PhealAPIException	$e	)
																{
																				// API error
																				cronlog('EVE API Key error: '	.	$e->getCode()	.	' -> '	.	$e->getMessage(),	"Standings Character -> "	.	$standingsCharacterAPI['characterName']);
																}
																catch	(	\Pheal\Exceptions\PhealConnectionException	$e	)
																{
																				// Connection error
																				cronlog('EVE API Connection error: '	.	$e->getCode()	.	' -> '	.	$e->getMessage(),	"Standings Character -> "	.	$standingsCharacterAPI['characterName']);
																}
																catch	(	\Pheal\Exceptions\PhealException	$e	)
																{
																				// Any other error, in other words "Unknown error"
																				cronlog('EVE API Generic error: '	.	$e->getCode()	.	' -> '	.	$e->getMessage(),	"Standings Character -> "	.	$standingsCharacterAPI['characterName']);
																}
																catch	(	Exception	$e	)
																{
																				// Any other error, in other words "Unknown error"
																				cronlog('EVE API Generic error: '	.	$e->getCode()	.	' -> '	.	$e->getMessage(),	"Standings Character -> "	.	$standingsCharacterAPI['characterName']);
																}

																if	(	$characterID	>	0	)
																{
																				try
																				{
																								$result	=	$pheal->corpScope->contactList();

																								//cronlog("Removing old Corporation-level standings");
																								$db->sql_query('DELETE FROM '	.	TBL_EVEAPI_STANDINGS	.	' WHERE standingsType = '	.	EVEAPI_STANDINGSTYPE_CORPORATION);

																								if	(	$config['eveapi_s_corporation']	)
																								{
																												foreach	(	$result->corporateContactList	as	$contact	)
																												{
																																if	(	$contact->standing	!=	-10	&&	$contact->standing	!=	-5	&&	$contact->standing	!=	0	&&	$contact->standing	!=	5	&&	$contact->standing	!=	10	)
																																				continue;

																																$sql	=	'INSERT INTO '	.	TBL_EVEAPI_STANDINGS	.	' '	.	$db->sql_build_array('INSERT',	array(
																																								"standingsName"			=>	$contact->contactName,
																																								"standingsAmount"	=>	$contact->standing,
																																								"standingsType"			=>	EVEAPI_STANDINGSTYPE_CORPORATION
																																));
																																$db->sql_query($sql);
																												}
																												//cronlog("New Corporation-level standings successfully imported!", "Standings Character -> " . $standingsCharacterAPI['characterName']);
																								}

																								//cronlog("Removing old Alliance-level standings");
																								$db->sql_query('DELETE FROM '	.	TBL_EVEAPI_STANDINGS	.	' WHERE standingsType = '	.	EVEAPI_STANDINGSTYPE_ALLIANCE);

																								if	(	$config['eveapi_s_alliance']	)
																								{
																												foreach	(	$result->allianceContactList	as	$contact	)
																												{
																																if	(	$contact->standing	!=	-10	&&	$contact->standing	!=	-5	&&	$contact->standing	!=	0	&&	$contact->standing	!=	5	&&	$contact->standing	!=	10	)
																																				continue;

																																$sql	=	'INSERT INTO '	.	TBL_EVEAPI_STANDINGS	.	' '	.	$db->sql_build_array('INSERT',	array(
																																								"standingsName"			=>	$contact->contactName,
																																								"standingsAmount"	=>	$contact->standing,
																																								"standingsType"			=>	EVEAPI_STANDINGSTYPE_ALLIANCE
																																));
																																$db->sql_query($sql);
																												}
																												//cronlog("New Alliance-level standings successfully imported!", "Standings Character -> " . $standingsCharacterAPI['characterName']);
																								}
																				}
																				catch	(	\Pheal\Exceptions\PhealHTTPException	$e	)
																				{
																								// HTTP error
																								cronlog('EVE API HTTP error: '	.	$e->getCode()	.	' -> '	.	$e->getMessage(),	"Standings Character -> "	.	$standingsCharacterAPI['characterName']);
																				}
																				catch	(	\Pheal\Exceptions\PhealAPIException	$e	)
																				{
																								// API error
																								cronlog('EVE API Key error: '	.	$e->getCode()	.	' -> '	.	$e->getMessage(),	"Standings Character -> "	.	$standingsCharacterAPI['characterName']);
																				}
																				catch	(	\Pheal\Exceptions\PhealConnectionException	$e	)
																				{
																								// Connection error
																								cronlog('EVE API Connection error: '	.	$e->getCode()	.	' -> '	.	$e->getMessage(),	"Standings Character -> "	.	$standingsCharacterAPI['characterName']);
																				}
																				catch	(	\Pheal\Exceptions\PhealException	$e	)
																				{
																								// Any other error, in other words "Unknown error"
																								cronlog('EVE API Generic error: '	.	$e->getCode()	.	' -> '	.	$e->getMessage(),	"Standings Character -> "	.	$standingsCharacterAPI['characterName']);
																				}
																				catch	(	Exception	$e	)
																				{
																								// Any other error, in other words "Unknown error"
																								cronlog('EVE API Generic error: '	.	$e->getCode()	.	' -> '	.	$e->getMessage(),	"Standings Character -> "	.	$standingsCharacterAPI['characterName']);
																				}
																}
												}

												// *******************************************************************
												// ** STANDINGS END **************************************************
												// *******************************************************************
												// Get rid of old Jabber access
												//cronlog("Clearing ejabberd accesslist");
												$sql	=	'DELETE FROM eveapi_jabber WHERE userID > 0 ';
												$db->sql_query($sql);

												// *******************************************************************
												// ** ACTUAL CRONJOB START *******************************************
												// *******************************************************************
												if	(	$config['eveapi_ts_masterswitch']	)
												{
																$nickname	=	$config['eveapi_ts_nickname'];
																if	(	eveapi_validateMixedalphanumeric($nickname)	!=	1	)
																{
																				$nickname	=	"Cyerus";
																}

																try
																{
																				$ts3_VirtualServer	=	TeamSpeak3::factory("serverquery://"	.	$config["eveapi_ts_username"]	.	":"	.	$config["eveapi_ts_password"]	.	"@"	.	$config["eveapi_ts_ip"]	.	":"	.	$config["eveapi_ts_port_query"]	.	"/?server_port="	.	$config["eveapi_ts_port_server"]	.	"&nickname="	.	$nickname);
																}
																catch	(	TeamSpeak3_Exception	$e	)
																{
																				cronlog('TeamSpeak error: '	.	$e->getCode()	.	' -> '	.	$e->getMessage());
																				$ts3_VirtualServer	=	false;
																}
												}

												if	(	($config['eveapi_ts_masterswitch']	&&	$ts3_VirtualServer)	||	$config['eveapi_jabber_masterswitch']	)
												{
																$sql				=	'SELECT user_id, username, eveapi_keyid, eveapi_vcode, eveapi_ts
                FROM '	.	USERS_TABLE	.	'
                WHERE user_type = 0
                ORDER BY username';
																$result	=	$db->sql_query($sql);

																while	(	$row	=	$db->sql_fetchrow($result)	)
																{
																				if	(	!empty($row["eveapi_keyid"])	&&	!empty($row["eveapi_vcode"])	)
																				{
																								try
																								{
																												$characterInfo	=	eveapi_checkThisCharacter($row['eveapi_keyid'],	$row['eveapi_vcode'],	$row['username']);

																												if	(	empty($characterInfo["error"])	)
																												{
																																$eveapi_forumGroups	=	array();
																																$eveapi_TSGroups				=	array();

																																$sql	=	"UPDATE "	.	USERS_TABLE	.	"
																																SET user_character_id = '"	.	(int)	$characterInfo["info"]["characterId"]	.	"'
																																WHERE user_id = "	.	(int)	$row['user_id'];
																																$db->sql_query($sql);

																																if	(	!empty($characterInfo["forumGroups"])	||	(empty($characterInfo["forumGroups"])	&&	$config['eveapi_nonmember'])	)
																																{
																																				$eveapi_forumGroups	=	$characterInfo["forumGroups"];
																																				$eveapi_TSGroups				=	$characterInfo["TSGroups"];

																																				$extraGroups	=	eveapi_setForumGroups($row['user_id'],	$eveapi_forumGroups,	$row['username']);

																																				if	(	$config['eveapi_ts_masterswitch']	&&	!empty($row['eveapi_ts'])	)
																																				{
																																								eveapi_setTeamSpeakGroups($row['eveapi_ts'],	$eveapi_TSGroups,	$row['username'],	$ts3_VirtualServer,	$extraGroups["TS"]);
																																				}

																																				if	(	$config['eveapi_jabber_masterswitch']	)
																																				{
																																								if	(	$config['eveapi_ejabber_switch']	)
																																								{
																																												eveapi_setJabberAccess($row['user_id'],	$characterInfo['jabber'],	$extraGroups['jabber'],	$row['username']);
																																								}

																																								if	(	$config['eveapi_openfire_switch']	)
																																								{
																																												eveapi_setOpenFireAccess($row['user_id'],	$characterInfo['openfire'],	$extraGroups['openfire'],	$row['username']);
																																								}
																																				}
																																}
																																else
																																{
																																				// no valid forumgroups found, probably meaning the user isn't part of any entity and should be disabled
																																				eveapi_disableForumAccount($row['user_id'],	$row['username'],	$row['eveapi_ts'],	INACTIVE_EVEAPI_NONMEMBER,	$ts3_VirtualServer);
																																}
																												}
																												else
																												{
																																// error reported by eveapi_checkThisCharacter(), now use $characterInfo["disable"] to determine whether or not to disable.
																																if	(	$characterInfo["disable"]	)
																																{
																																				eveapi_disableForumAccount($row['user_id'],	$row['username'],	$row['eveapi_ts'],	INACTIVE_EVEAPI_INVALID,	$ts3_VirtualServer);
																																}
																												}
																								}
																								catch	(	\Pheal\Exceptions\PhealHTTPException	$e	)
																								{
																												// HTTP error
																												cronlog('EVE API HTTP error: '	.	$e->getCode()	.	' -> '	.	$e->getMessage(),	$row['username']);
																								}
																								catch	(	\Pheal\Exceptions\PhealAPIException	$e	)
																								{
																												// API error
																												cronlog('EVE API Key error: '	.	$e->getCode()	.	' -> '	.	$e->getMessage(),	$row['username']);
																								}
																								catch	(	\Pheal\Exceptions\PhealConnectionException	$e	)
																								{
																												// Connection error
																												cronlog('EVE API Connection error: '	.	$e->getCode()	.	' -> '	.	$e->getMessage(),	$row['username']);
																								}
																								catch	(	\Pheal\Exceptions\PhealException	$e	)
																								{
																												// Any other error, in other words "Unknown error"
																												cronlog('EVE API Generic error: '	.	$e->getCode()	.	' -> '	.	$e->getMessage(),	$row['username']);
																								}
																								catch	(	Exception	$e	)
																								{
																												// Any other error, in other words "Unknown error"
																												cronlog('EVE API Generic error: '	.	$e->getCode()	.	' -> '	.	$e->getMessage(),	$row['username']);
																								}
																				}
																				else
																				{
																								// Disable user, no valid API information found!
																								eveapi_disableForumAccount($row['user_id'],	$row['username'],	$row['eveapi_ts'],	INACTIVE_EVEAPI_INVALID,	$ts3_VirtualServer);
																				}
																}

																$db->sql_freeresult($result);
												}
												else
												{
																cronlog("Quiting the cronjob, as no connection to the TeamSpeak 3 server could be made while TeamSpeak 3 Integration is enabled.");
												}

												// *******************************************************************
												// ** ACTUAL CRONJOB END *********************************************
												// *******************************************************************
								}

								cronlog("Finished Successfully");
				}
}
