<?php

namespace	omni\eveapi\event;

use	Symfony\Component\EventDispatcher\EventSubscriberInterface;
use	\Pheal\Pheal;

class	main_listener	implements	EventSubscriberInterface
{

				static	public	function	getSubscribedEvents()
				{
								return	array(
												'core.common'																											=>	'common_eveapi',
												'core.user_add_after'																			=>	'user_add_after_eveapi',
												'core.user_add_modify_data'													=>	'user_add_modify_data_eveapi',
												'core.acp_manage_group_request_data'				=>	'acp_manage_group_request_data_eveapi',
												'core.acp_manage_group_initialise_data'	=>	'acp_manage_group_initialise_data_eveapi',
												'core.acp_manage_group_display_form'				=>	'acp_manage_group_display_form_eveapi',
								);
				}

				/* @var \phpbb\config\config */
				protected	$config;

				/* @var \phpbb\controller\helper */
				protected	$helper;

				/* @var \phpbb\template\template */
				protected	$template;

				/**
					* Constructor
					*
					* @param \phpbb\controller\helper $helper Controller helper object
					* @param \phpbb\template $template Template object
					*/
				public	function	__construct( \phpbb\config\config	$config,	\phpbb\controller\helper	$helper,	\phpbb\template\template	$template	)
				{
								$this->config			=	$config;
								$this->helper			=	$helper;
								$this->template	=	$template;
				}

				public	function	acp_manage_group_request_data_eveapi(	$action,	$group_id,	$group_row,	$error,	$group_name,	$group_desc,	$group_type,	$allow_desc_bbcode,	$allow_desc_urls,	$allow_desc_smilies,	&$submit_ary,	$validation_checks	)
				{
								$submit_ary	=	array_merge($submit_ary,	array(
												'eveapi_special'		=>	request_var('group_eveapi_special',	0),
												'eveapi_ts3'						=>	request_var('group_eveapi_ts3',	0),
												'eveapi_openfire'	=>	request_var('group_eveapi_openfire',	''),
								));
				}

				public	function	acp_manage_group_initialise_data_eveapi(	$action,	$group_id,	$group_row,	$error,	$group_name,	$group_desc,	$group_type,	$allow_desc_bbcode,	$allow_desc_urls,	$allow_desc_smilies,	$submit_ary,	&$test_variables	)
				{
								$test_variables	=	array_merge($test_variables,	array(
												'eveapi_special'		=>	'int',
												'eveapi_ts3'						=>	'int',
												'eveapi_openfire'	=>	'string',
								));
				}

				public	function	acp_manage_group_display_form_eveapi(	$action,	$update,	$group_id,	$group_row,	$group_desc_data,	$group_name,	$group_type,	$group_rank,	$rank_options,	$error	)
				{
								$this->template->assign_vars(array(
												'GROUP_EVEAPI_SPECIAL'		=>	(isset($group_row['group_eveapi_special'])	&&	$group_row['group_eveapi_special'])	?	' checked="checked"'	:	'',
												'GROUP_EVEAPI_TS3'						=>	(isset($group_row['group_eveapi_ts3']))	?	$group_row['group_eveapi_ts3']	:	0,
												'GROUP_EVEAPI_OPENFIRE'	=>	(isset($group_row['group_eveapi_openfire']))	?	$group_row['group_eveapi_openfire']	:	"",
												'S_EVEAPI_VALIDATION'	=>	$this->config['eveapi_validation'],
								));
				}

				public	function	common_eveapi()
				{
								global	$phpbb_root_path;

								// Moved above masterswitch to avoid errors trying to use EVE BBcodes without having masterswitch enabled
								require($phpbb_root_path	.	"ext/omni/eveapi/includes/functions_eveapi.php");

								// Load TeamSpeak3 PHP Framework
								require($phpbb_root_path	.	"ext/omni/eveapi/library/TeamSpeak3/TeamSpeak3.php");

								// Load OpenFireUserService class
								require($phpbb_root_path	.	"ext/omni/eveapi/includes/functions_openfire.php");

								\Pheal\Core\Config::getInstance()->cache											=	new	\Pheal\Cache\MemcachedStorage();
								\Pheal\Core\Config::getInstance()->access										=	new	\Pheal\Access\StaticCheck();
								\Pheal\Core\Config::getInstance()->http_user_agent	=	"EVE API for phpBB 3.x (phpBB 3.x; Pheal)";
				}

				public	function	user_add_after_eveapi(	$user_id,	$user_row,	$cp_data	)
				{
								$characterInfo	=	eveapi_checkThisCharacter($user_row['eveapi_keyid'],	$user_row['eveapi_vcode'],	$user_row['username']);

								$sql	=	"UPDATE "	.	USERS_TABLE	.	"
																SET user_character_id = '"	.	(int)	$characterInfo["info"]["characterId"]	.	"'
																WHERE user_id = "	.	(int)	$user_id;
								$db->sql_query($sql);

								// EVE API - Add the correct groups to the user.
								eveapi_setForumGroups($user_id,	$characterInfo["forumGroups"]);
				}

				public	function	user_add_modify_data_eveapi(	$user_row,	$cp_data,	&$sql_ary	)
				{
								$sql_ary	=	array_merge($sql_ary,	array(
												'eveapi_keyid'	=>	$user_row['eveapi_keyid'],
												'eveapi_vcode'	=>	$user_row['eveapi_vcode'],
								));
				}
}
