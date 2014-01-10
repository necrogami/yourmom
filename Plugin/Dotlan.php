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
 * Plugin to handle Dotlan API
 */
class Plugin_Dotlan extends DASBiT_Plugin
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
    $this->_adapter = DASBiT_Database::accessDatabase('dotlan', array(
        'skills' => array(
            'skills_id'   => 'INTEGER PRIMARY KEY',
            'users_name'  => 'VARCHAR(40)',
            'skills_jdc'  => 'TINYINT(1)',
            'skills_jfc'  => 'TINYINT(1)',
            'skills_jf'   => 'TINYINT(1)',
            'skills_usage' => 'TINYINT(1)'
        )
    ));
    $this->_controller->registerCommand($this, 'skillsAdd', 'jump skills');
    $this->_controller->registerCommand($this, 'planRoute', 'jump route');
    $this->_controller->registerInterval($this, 'tickUsage', 1200);
    }

    
    public function skillsAdd (DASBiT_Irc_Request $request){
        $ident = Plugin_Evsco::userRegistered($request->getNickname());

        if ($ident === FALSE) {
            $this->_client->send('Your IRC Nick needs to be verified that it is registered send "!evsco identify"', $request, DASBiT_Irc_Client::TYPE_NOTICE);
            return;
        }

        $words = array_slice($request->getWords(), 2);

        if (count($words) < 3) {
            $this->_client->send('Not enough arguments, !jump skills <Jump drive cal level> <jump drive fuel level> <jump freighter level>', $request, DASBiT_Irc_Client::TYPE_NOTICE);
            return;
        }

        $jdc    = $words[0];
        $jfc    = $words[1];
        $jf     = $words[2];
        $select = $this->_adapter->select()->from('skills', array('users_name'))->where('users_name = ?', $request->getNickname());
        $skills = $this->_adapter->fetchRow($select);

        if ($skills === FALSE) {
        $this->_adapter->insert('skills', array(
            'users_name' => $request->getNickname(),
            'skills_jdc' => $jdc,
            'skills_jfc' => $jfc,
            'skills_jf'  => $jf
            )
        );

        $this->_client->send('Added Skills: Jump Drive Calibration: '.$jdc.' Jump Fuel Conservation: '.$jfc.' and Jump Freighters: '.$jf, $request, DASBiT_Irc_Client::TYPE_NOTICE);
        } else {
            $where[] = $this->_adapter->quoteInto('users_name = ?', $request->getNickname());
            $this->_adapter->update('skills', array(
                'skills_jdc' => $jdc,
                'skills_jfc' => $jfc,
                'skills_jf'  => $jf), $where
        );
        $this->_client->send('Updated Skills: Jump Drive Calibration: '.$jdc.' Jump Fuel Conservation: '.$jfc.' and Jump Freighters: '.$jf, $request, DASBiT_Irc_Client::TYPE_NOTICE);
        }

    }
    public function planRoute(DASBiT_Irc_Request $request){
        $ident = Plugin_Evsco::userRegistered($request->getNickname());

        if ($ident === FALSE) {
            $this->_client->send('Your IRC Nick needs to be verified that it is registered send "!evsco identify"', $request, DASBiT_Irc_Client::TYPE_NOTICE);
            return;
        }

        $select = $this->_adapter->select()->from('skills', array('users_name', 'skills_jdc', 'skills_jfc', 'skills_jf', 'skills_usage'))->where('users_name = ?', $request->getNickname());
        $skills = $this->_adapter->fetchRow($select);
        
        if ($skills === FALSE) {
            $this->_client->send('You have not inserted your skills. !jump skills <Jump drive cal level> <jump drive fuel level> <jump freighter level>', $request, DASBiT_Irc_Client::TYPE_NOTICE);
            $this->_client->send('Assuming 4/4/4', $request, DASBiT_Irc_Client::TYPE_NOTICE);
            $skills['skills_jdc'] = 4;
            $skills['skills_jfc'] = 4;
            $skills['skills_jf'] = 4;
        }
        
        if ($skills['skills_usage'] >= 3) {
            $this->_client->send('You have reached your maximum usage for the hour. Please try again later.', $request, DASBiT_Irc_Client::TYPE_NOTICE);
            return;
        } else {
            $where[] = $this->_adapter->quoteInto('users_name = ?', $request->getNickname());
            $this->_adapter->update('skills', array('skills_usage' => $skills['skills_usage']+1), $where);
        }

        $words = array_slice($request->getWords(), 2);
        $ship      = (count($words) === 2) ? $words[1] : 'Thanatos';
        $url = 'http://evemaps.dotlan.net/api/JumpPlanner.xml?' .
                'apiKey='.$this->_controller->getConfig()->eve->dotlan->apiKey.
                '&path='.$words[0].
                '&ship='.ucfirst(strtolower($ship)).
                '&jdc='.$skills['skills_jdc'].
                '&jfc='.$skills['skills_jfc'].
                '&jf='.$skills['skills_jf'];
        $xml = simplexml_load_string(Plugin_Evsco::xmlCaching($url, 60*60*24*180));
        if(isset($xml->waypoints)){
            $jump = count($xml->waypoints->waypoint);
            $from = $xml->waypoints->waypoint[0]['solarSystemName'];
            $to = $xml->waypoints->waypoint[$jump-1]['solarSystemName'];
            $this->_client->send('Jumping '.$jump.' times from '.$from.' to '.$to.' using '.$xml->fuelNeeded.' '.$xml->shipFuelType.' covering '.$xml->travelDistance.' lightyears.', $request->getNickname(), DASBiT_Irc_Client::TYPE_MESSAGE);
            foreach($xml->waypoints->waypoint as $sys){
                if($sys['solarSystemName'] == $from){
                    $route = chr(2) . 'Start: ';
                    $end = '';
                }else if($sys['solarSystemName'] == $to){
                    $route = chr(2) . 'End: ';
                    $end = chr(15) . 'covering '.$sys['distance'].' lightyears using '.$sys['fuelNeeded'].' '.$xml->shipFuelType.' to reach';
                }else{
                    $route = chr(2) . 'Jump: ';
                    $end = chr(15) . 'covering '.$sys['distance'].' lightyears using '.$sys['fuelNeeded'].' '.$xml->shipFuelType.' to reach';
                }
                $route .= chr(3) . '03' . $sys['solarSystemName'].':' . chr(3) . '02'.$sys['regionName'].' ';
                if($sys['hasStation'] == 1){
                    $route .= chr(15) . '1 station ';
                }else{
                    $route .= chr(15) . $sys['hasStation'] . ' stations ';
                }
                if($sys['allianceID'] != 0){
                    $route .= 'controlled by ' . chr(3) . '04' . $sys['allianceOrFactionName'].' ';
                }
                $route .= $end;
                $this->_client->send($route, $request->getNickname(), DASBiT_Irc_Client::TYPE_MESSAGE);
            }
        }else{
            $this->_client->send('Jump Route Not Valid', $request, DASBiT_Irc_Client::TYPE_NOTICE);
        }
    }
    public function tickUsage(){
        $select = $this->_adapter->select()->from('skills', array('users_name', 'skills_usage'))->where('skills_usage > 0');
        $skills = $this->_adapter->fetchAll($select);
        foreach ($skills as $skill) {

            if ($skill['skills_usage'] != 0) {
                $where[] = $this->_adapter->quoteInto('users_name = ?', $skill['users_name']);
                $this->_adapter->update('skills', array(
                    'skills_usage' => $skill['skills_usage'] - 1,
                    ), $where
                );
            }

        }

    }
}