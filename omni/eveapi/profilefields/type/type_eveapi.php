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

namespace	omni\eveapi\profilefields\type;

class	type_eveapi	extends	\phpbb\profilefields\type\type_string_common
{
				/**
					* User object
					* @var \phpbb\config\config
					*/
				protected	$config;

				/**
					* Request object
					* @var \phpbb\request\request
					*/
				protected	$request;

				/**
					* Construct
					*
					* @param	\phpbb\request\request		$request	Request object
					* @param	\phpbb\user					$user		User object
					*/
				public	function	__construct(	\phpbb\config\config	$config,	\phpbb\request\request	$request	)
				{
								$this->config		=	$config;
								$this->request	=	$request;
				}

				/**
					* {@inheritDoc}
					*/
				public	function	get_name_short()
				{
								return	'eveapi';
				}

				/**
					* {@inheritDoc}
					*/
				public	function	validate_profile_field(	&$field_value,	$field_data	)
				{
								if	(	!$this->config['eveapi_validation']	)
								{
												return	false;
								}

								if	(	trim($field_value)	===	''	)
								{
												return	$this->user->lang('FIELD_REQUIRED',	$this->get_field_name($field_data['lang_name']));
								}

								$eveapi_keyid	=	request_var('eveapi_keyid',	'');
								$eveapi_vcode	=	request_var('eveapi_vcode',	'');
								$username					=	request_var('username',	'');

								$characterInfo	=	eveapi_checkThisCharacter($eveapi_keyid,	$eveapi_vcode,	$username);

								if	(	!empty($characterInfo["error"])	)
								{
												return	$characterInfo["error"];
								}
								else	if	(	empty($characterInfo["forumGroups"])	&&	!$this->config['eveapi_nonmember']	)
								{
												return	$this->user->lang('EVEAPI_NOT_ALLOWED_ACCOUNT');
								}

								return	false;
				}
}
