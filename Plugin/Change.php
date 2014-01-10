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
 * @version $Id: Svn.php 70 2010-04-15 23:52:02Z necrogami $
 */

/**
 * Plugin to handle EVE Services Corporation
 */
class Plugin_Change extends DASBiT_Plugin
{


    /**
     * Users Database Adapter
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
    	$this->_adapter = DASBiT_Database::accessDatabase('change', array(
        	'change' => array(
            	'change_id'			=> 'INTERGER PRIMARY KEY',
				'change_name'		=> 'VARCHAR(100)',
            	'change_quarter'	=> 'INTERGER',
				'change_dime'		=> 'INTERGER',
				'change_nickle'		=> 'INTERGER',
				'change_penny'		=> 'INTERGER'
        	)
    	));
    	$this->_controller->registerCommand($this, 'changeAdd', 'change add');
		$this->_controller->registerCommand($this, 'changeRemove', 'change remove');
    	$this->_controller->registerCommand($this, 'changeTotal', 'change total');
    }

    /**
     * Lets a user know what the current eve time is.
     *
     * @param  DASBiT_Irc_Request $request
     * @return void
     */
	public function changeAdd(DASBiT_Irc_Request $request)
	{
		$words = array_slice($request->getWords(), 2);

        if (count($words) < 4) {
            $this->_client->send('Wrong number of arguments, !change add <quarters> <dimes> <nickles> <pennies>', $request, DASBiT_Irc_Client::TYPE_NOTICE);
            return;
        }
		$this->_client->send("I have added: Quarters, Dimes, Nickles and Pennies.", $request, DASBiT_Irc_Client::TYPE_NOTICE);
	}

    /**
     * Lets a user know what the current eve time is.
     *
     * @param  DASBiT_Irc_Request $request
     * @return void
     */
	public function changeRemove(DASBiT_Irc_Request $request)
	{
		$words = array_slice($request->getWords(), 2);

        if (count($words) < 4) {
            $this->_client->send('Wrong number of arguments, !change remove <quarters> <dimes> <nickles> <pennies>', $request, DASBiT_Irc_Client::TYPE_NOTICE);
            return;
        }
	}

    /**
     * Lets a user know what the current eve time is.
     *
     * @param  DASBiT_Irc_Request $request
     * @return void
     */
	public function changeTotal(DASBiT_Irc_Request $request)
	{
		
	}
}
