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
 * @version $Id: Channels.php 37 2009-07-26 15:38:04Z dasprid $
 */

/**
 * Plugin to handle Git Updates
 */
class Plugin_Git extends DASBiT_Plugin
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
		$this->_controller->registerCommand($this, 'gitMerge', 'git merge');
		$this->_controller->registerCommand($this, 'gitRestart', 'git restart');
		$this->_controller->registerCommand($this, 'gitCycle', 'git reload');
	}
	
	/**
    * Checks if there are any new commits to update into git.
    *
    * @param  DASBiT_Irc_Request $request
    * @return void
    */
    public function gitCheck(DASBiT_Irc_Request $request)
    {
		if (!Plugin_Users::isIdentified($request)) {
            $this->_client->send('You must be identified to check git', $request, DASBiT_Irc_Client::TYPE_NOTICE);
            return;
        }
		$fetch = `git fetch`;
		$shell = `git log --pretty=format:'%H|%an|%aD|%s' ..origin/master`;
		if(empty($shell)){
			$this->_client->send('No new commits', $request, DASBiT_Irc_Client::TYPE_MESSAGE);
			return;
		}
		$export = explode(PHP_EOL, $shell);
		$hash = '';
		$current = false;
		$commits = array();
		foreach($export as $row){
			$rowdata = explode('|', $row);
			$commits[] = 'New Commit by: ' . $rowdata[1] . ' on ' . $rowdata[2] . ' with message: ' . $rowdata[3]; 
		}
		$commit = array_reverse($commits);
		foreach($commit as $line){
			$this->_client->send($line, $request, DASBiT_Irc_Client::TYPE_MESSAGE);
		}
	}
	
	/**
    * Mergest commits into the bot.
    *
    * @param  DASBiT_Irc_Request $request
    * @return void
    */
    public function gitMerge(DASBiT_Irc_Request $request)
    {
		if (!Plugin_Users::isIdentified($request)) {
            $this->_client->send('You must be identified to merge repo\'s', $request, DASBiT_Irc_Client::TYPE_NOTICE);
            return;
        }
		$fetch = `git merge origin`;
		$this->_client->send('merging commits', $request, DASBiT_Irc_Client::TYPE_MESSAGE);
	}
	
	/**
    * Restart the bot.
    *
    * @param  DASBiT_Irc_Request $request
    * @return void
    */
    public function gitRestart(DASBiT_Irc_Request $request)
    {
		if (!Plugin_Users::isIdentified($request)) {
            $this->_client->send('You must be identified to merge repo\'s', $request, DASBiT_Irc_Client::TYPE_NOTICE);
            return;
        }
		$this->_client->send('BRB I\'m restarting. If I don\'t come back i\'ve broken something.', $request, DASBiT_Irc_Client::TYPE_MESSAGE);
		$fetch = `supervisorctl restart dasbit`;
		
	}
	
	/**
    * Run a full cycle on the bot.
    *
    * @param  DASBiT_Irc_Request $request
    * @return void
    */
    public function gitCycle(DASBiT_Irc_Request $request)
    {
		if (!Plugin_Users::isIdentified($request)) {
            $this->_client->send('You must be identified to check git', $request, DASBiT_Irc_Client::TYPE_NOTICE);
            return;
        }
		$fetch = `git fetch`;
		$shell = `git log --pretty=format:'%H|%an|%aD|%s' ..origin/master`;
		if(empty($shell)){
			$this->_client->send('No new commits', $request, DASBiT_Irc_Client::TYPE_MESSAGE);
			return;
		}
		$export = explode(PHP_EOL, $shell);
		$hash = '';
		$current = false;
		$commits = array();
		foreach($export as $row){
			$rowdata = explode('|', $row);
			$commits[] = 'New Commit by: ' . $rowdata[1] . ' on ' . $rowdata[2] . ' with message: ' . $rowdata[3]; 
		}
		$commit = array_reverse($commits);
		foreach($commit as $line){
			$this->_client->send($line, $request, DASBiT_Irc_Client::TYPE_MESSAGE);
		}
		$fetch = `git merge origin`;
		$this->_client->send('merging commits', $request, DASBiT_Irc_Client::TYPE_MESSAGE);
		$this->_client->send('BRB I\'m restarting. If I don\'t come back i\'ve broken something.', $request, DASBiT_Irc_Client::TYPE_MESSAGE);
		$fetch = `supervisorctl restart dasbit`;
		
	}
}