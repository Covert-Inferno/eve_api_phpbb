<?php

namespace	omni\eveapi\mcp;

class	timerboard_info
{
				function	module()
				{
								return	array(
												'filename'	=>	'\omni\eveapi\mcp\timerboard',
												'title'				=>	'MCP_TIMERBOARD',
												'version'		=>	'0.0.1',
												'modes'				=>	array(
																'list'			=>	array(
																				'title'	=>	'MCP_TIMERBOARD_LIST',
																				'auth'		=>	'acl_m_timerboard',
																				'cat'			=>	array(
																								'MCP_TIMERBOARD')
																),
																'create'	=>	array(
																				'title'	=>	'MCP_TIMERBOARD_CREATE',
																				'auth'		=>	'acl_m_timerboard',
																				'cat'			=>	array(
																								'MCP_TIMERBOARD')
																),
																'edit'			=>	array(
																				'title'	=>	'MCP_TIMERBOARD_EDIT',
																				'auth'		=>	'acl_m_timerboard',
																				'cat'			=>	array(
																								'MCP_TIMERBOARD')
																),
												),
								);
				}
}
