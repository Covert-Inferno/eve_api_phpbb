<?php

namespace	omni\eveapi\acp;

class	eveapi_info
{
				function	module()
				{
								return	array(
												'filename'	=>	'\omni\eveapi\acp\eveapi',
												'title'				=>	'ACP_CAT_EVEAPI',
												'version'		=>	'0.0.1',
												'modes'				=>	array(
																'general'								=>	array(
																				'title'	=>	'GENERAL_SETTINGS',
																				'auth'		=>	'acl_a_board',
																				'cat'			=>	array(
																								'ACP_BOARD_CONFIGURATION')),
																'corporation'				=>	array(
																				'title'	=>	'EVEAPI_CORPORATIONS',
																				'auth'		=>	'acl_a_board',
																				'cat'			=>	array(
																								'ACP_BOARD_CONFIGURATION')),
																'alliance'							=>	array(
																				'title'	=>	'EVEAPI_ALLIANCES',
																				'auth'		=>	'acl_a_board',
																				'cat'			=>	array(
																								'ACP_BOARD_CONFIGURATION')),
																'standings'						=>	array(
																				'title'	=>	'EVEAPI_STANDINGS',
																				'auth'		=>	'acl_a_board',
																				'cat'			=>	array(
																								'ACP_BOARD_CONFIGURATION')),
																'factionwarfare'	=>	array(
																				'title'	=>	'EVEAPI_FW',
																				'auth'		=>	'acl_a_board',
																				'cat'			=>	array(
																								'ACP_BOARD_CONFIGURATION')),
																'teamspeak3'					=>	array(
																				'title'	=>	'EVEAPI_TS',
																				'auth'		=>	'acl_a_board',
																				'cat'			=>	array(
																								'ACP_BOARD_CONFIGURATION')),
																'jabber'									=>	array(
																				'title'	=>	'EVEAPI_JABBER',
																				'auth'		=>	'acl_a_board',
																				'cat'			=>	array(
																								'ACP_BOARD_CONFIGURATION')),
																'accessmask'					=>	array(
																				'title'	=>	'EVEAPI_ACCESSMASK',
																				'auth'		=>	'acl_a_board',
																				'cat'			=>	array(
																								'ACP_BOARD_CONFIGURATION')),
												),
								);
				}
}
