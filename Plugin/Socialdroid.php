<?php
/**
 * DASBiT
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @version $Id: Socialdroid.php 37 2009-07-26 15:38:04Z necrogami $
 */

/**
 * Plugin to handle Socialdroid
 */
class Plugin_Socialdroid extends DASBiT_Plugin
{
    /**
     * Database adapter
     *
     * @var Zend_Db_Adapter_Pdo_Sqlite
     */
    protected $_adapter;
    
    /**
     * Defined by DASBiT_Plugin
     *
     * @return void
     */
    protected function _init()
    {
		$this->_controller->registerCommand($this, 'gitCheck', 'git check');
		$this->_controller->registerHook($this, 'mibbit', 'userjoin');
		$this->_controller->registerHook($this, 'mibbitNick', 'changednick');
		$this->_controller->registerInterval($this, 'newsFeed', 120);
	}
	
	
	/**
     * Automaticly asks a mibbit user to change their nickname
     *
	 * @var Array params
     * @return void
     */
	public function mibbit(array $params)
	{
		$pos = strpos($params['nickname'], 'mib_');
		if($pos === false) {
			// not a mibbit user do nothing;
			return;
		}
		else {
			$this->_client->send($params['nickname'] . ', Can you please change your nickname replacing <name> with the name you choose type \'/nick <name>\'  this way we can distinguish who you are.', $params['channel'], DASBiT_Irc_Client::TYPE_MESSAGE);
		}
	}
	
	/**
     * Automaticly Thanks the user for changing their nick.
     *
	 * @var Array params
     * @return void
     */
	public function mibbitNick(array $params)
	{
		$pos = strpos($params['oldnickname'], 'mib_');
		if($pos === false) {
			//fail
			return;
		}
		else {
			$this->_client->send($params['newnickname'] . ', Thank you for changing your nick.', $params['newnickname'], DASBiT_Irc_Client::TYPE_MESSAGE);
		}
	}
	
	/**
	 * Automatic News feed posting
	 * 
	 * @var DASBiT_Irc_Request $request
	 * @return void
	 */
	public function newsFeed()
	{
		
	}
}
