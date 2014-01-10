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
class Plugin_Evsco extends DASBiT_Plugin
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
    $this->_adapter = DASBiT_Database::accessDatabase('evsco_users', array(
         'users' => array(
            'users_id'      => 'INTERGER PRIMARY KEY',
            'users_name'    => 'VARCHAR(40)',
            'users_ident'   => 'INTERGER'

        )
    ));
    $this->_controller->registerCommand($this, 'identify', 'evsco identify');
    $this->_controller->registerCommand($this, 'evscoTime', 'time');
    $this->_controller->registerCommand($this, 'evscoLotterySearch', 'lottery search');
    $this->_controller->registerCommand($this, 'evscoLottery', 'lottery');
    $this->_controller->registerCommand($this, 'evscoPriceCheck', 'pc');
    $this->_controller->registerCommand($this, 'evscoServerStatus', 'tq');
    $this->_controller->registerCommand($this, 'evscoChaCha', 'chacha');
    $this->_controller->registerHook($this, 'isRegistered', 'notice');
    $this->_controller->registerHook($this, 'userRegistered', 'userRegistered');
    }
    public function identify(DASBiT_Irc_Request $request){
        $this->_client->sendRaw('NICKSERV STATUS '. $request->getNickname());
        $this->_client->sendRaw('NICKSERV ACC '. $request->getNickname());
    }
    public function isRegistered($words){
        if($words[3] === ':STATUS' || $words[3] === ':ACC'){
            switch($words[5]){
                case 0:
                case 1:
                case 2:
                    $this->_client->send('Your nickname needs to be registered.', $words[4], DASBiT_Irc_Client::TYPE_NOTICE);
                    break;
                case 3:
                    $this->_adapter->insert('users', array(
                        'users_name'     => $words[4],
                        'users_ident' => 3
                    ));
                    $this->_client->send('Your nick is now inserted into the system you can use EVSCO Services', $words[4], DASBiT_Irc_Client::TYPE_NOTICE);
                    break;
            }
        }
    }
    public static function userRegistered($name){
        $_adapter = DASBiT_Database::accessDatabase('evsco_users', array(
         'users' => array(
            'users_id'      => 'INTERGER PRIMARY KEY',
            'users_name'    => 'VARCHAR(40)',
            'users_ident'   => 'INTERGER'

        )
    ));
        $select = $_adapter
                ->select()
                ->from('users',
                        array('users_name'))
                       ->where('users_name = ?', $name);
        $ident = $_adapter->fetchRow($select);
        return $ident;
    }
    public static function xmlCaching($url, $time){
        $frontOptions = array(
            'lifetime' => $time,
            'automatic_serialization' => true
        );
        $backOptions = array(
            'cache_dir' => DATA_PATH . '/cache',
            'file_name_prefix' => 'evsco',
            'hashed_directory_level' => 2
        );
        $xmlC = Zend_Cache::factory('Core', 'File', $frontOptions, $backOptions);
        $xmlF = md5($url);
        if(!($result = $xmlC->load($xmlF))){
           $result = file_get_contents($url);
           $xmlC->save($result, $xmlF);
        }
        return $result;
    }
    public static function eveDb(){
        $config = array(
            'dbname' => DATA_PATH . '/eve.sqlite'
        );
        $db = Zend_Db::factory('Pdo_Sqlite', $config);
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        return $db;
    }
    /**
     * Lets a user know what the current eve time is.
     *
     * @param  DASBiT_Irc_Request $request
     * @return void
     */
    public function evscoTime(DASBiT_Irc_Request $request)
    {
        date_default_timezone_set('UTC');
        $eveTime = date('l jS \of F Y h:i:s A (H:i)');
        $this->_client->send('The current EVE time is: ' . $eveTime, $request);
    }
    /**
     * Searches the EVSCO Lottery for current lottieries
     *
     * @param	DASBiT_Irc_Request $request
     * @return 	void
     */

    public function evscoLottery(DASBiT_Irc_Request $request)
    {
        $contents = file_get_contents('http://evsco.net/?a=lottery&xml=1');
        $evlot = new SimpleXMLElement($contents);
        foreach($evlot->lottery as $lot){
            $this->_client->send("Lottery ".$lot[id]." for ".$lot->description.". ".$lot->sold."/".$lot->tickets." sold. ".$lot->reason." @ ".number_format($lot->price, 0, '', '.')." isk - ".$lot->url, $request);
        }

        if (count($evlot->lottery) == '0') {
            $this->_client->send("No Active Lotteries", $request, DASBiT_Irc_Client::TYPE_NOTICE);
        }

    }

    /**
     * Searches for how many tickets a user has bought for the current lotteries
     *
     * @param	DASBit_Irc_Request $request
     * @return 	void
     */

    public function evscoLotterySearch(DASBit_Irc_Request $request)
    {
        $words = array_slice($request->getWords(), 2);

        if (count($words) === 0) {
            $this->_client->send('Wrong number of arguments, !lottery search <nickname> required', $request, DASBiT_Irc_Client::TYPE_NOTICE);
            return;
        }

        $name = implode(' ', $words);
        $contents = file_get_contents('http://evsco.net/?a=lottery&xmlsearch=' . $name);
        $evlot = new SimpleXMLElement($contents);
        foreach($evlot->lottery as $lot){
            $this->_client->send("Lottery ".$lot['id']." (".$lot->reason.") - ".$lot->count." tickets for ".$lot->description.". - ".$lot->url, $request, DASBiT_Irc_Client::TYPE_NOTICE);
        }
        if(count($evlot->lottery) == '0'){
            $this->_client->send("No Tickets Found", $request, DASBiT_Irc_Client::TYPE_NOTICE);
        }
    }
    /**
     * ChaCha API Query
     */
    public function evscoChaCha (DASBiT_Irc_Request $request)
    {
	$words = array_slice($request->getWords(), 1);
	
	if (count($words) === 0) {
	    $this->_client->send('You did not type a query, !chacha <query>',  $request, DASBiT_Irc_Client::TYPE_NOTICE);
	    return;
	}
        $the_question = implode(' ', $words);
	$client = new Zend_Http_Client();
	$client->setUri('http://query.chacha.com/answer/search.json');
	$client->setParameterGet('query', $the_question);
	$client->setHeaders('apikey', 'suvf64t6nsxbu5q9mzt6qqt3');
	$response = $client->request();
	$body = $response->getBody();
	$json = Zend_Json::decode($body, Zend_Json::TYPE_OBJECT);
        $answer = str_replace(PHP_EOL, " ", $json->qvpResults[0]->answer->answer);
	if($request->getSource() == '#eve-online'){
            $this->_client->send($json->qvpResults[0]->question->suggestion . ' => ' . $answer, $request, DASBiT_Irc_Client::TYPE_NOTICE);
        }else{
            $this->_client->send($json->qvpResults[0]->question->suggestion . ' => ' . $answer, $request);
        }
    }
    /**
     * Searches for prices of items requested by the user.
     *
     * @param	DASBiT_Irc_Request $request
     * @return	void
     */
    public function evscoPriceCheck(DASBiT_Irc_Request $request)
    {
        $words = array_slice($request->getWords(), 1);

        if (count($words) === 0) {
            $this->_client->send('You did not request an item, !pc <exact item name>', $request, DASBiT_Irc_Client::TYPE_NOTICE);
            return;
        }
        $trimmsg = implode(' ', $words);
        $db = $this->eveDb();
        $itemID = $db->fetchRow('SELECT typeID, typeName from invTypes where typeName like ?', '%' . $trimmsg . '%');

        if (@$itemID->typeName !== NULL) {
            //$ecsource = "http://api.eve-central.com/api/marketstat?typeid=".$itemID->typeID;
            $emsource = 'http://eve-metrics.com/api/item.xml?type_ids='.$itemID->typeID;
            //$evecentral = simplexml_load_string(Plugin_Evsco::xmlCaching($ecsource, 60*60));
            $evemetrics = simplexml_load_string(Plugin_Evsco::xmlCaching($emsource, 60*60));
            setlocale(LC_MONETARY, 'is_IS');
            //$ecbuy = (double) $evecentral->marketstat->type->buy->avg;
            $embuy = (double) $evemetrics->type->global->buy->average;
            //$ecsell = (double) $evecentral->marketstat->type->sell->avg;
            $emsell = (double) $evemetrics->type->global->sell->average;
            //$buyavg = (($embuy + $ecbuy)/2);
            //$sellavg = (($emsell + $ecsell)/2);
            $allbuyavg = number_format($embuy, 2, ',', '.');
            $allsellavg = number_format($emsell, 2, ',', '.');
            $this->_client->send("Prices for ".$itemID->typeName.":  " . chr(3) . "Sell: ISK" . chr(3) . '04 ' . $allsellavg . chr(15) . " | Buy ISK" . chr(3) . '09 ' . $allbuyavg, $request);
        } else {
            $this->_client->send($trimmsg." doesn't exist. Try again", $request);
        }
    }
    /**
     * Searches for EVE Server Status.
     *
     * @param	DASBiT_Irc_Request $request
     * @return	void
     */
    public function evscoServerStatus(DASBiT_Irc_Request $request)
    {
        $source = 'http://api.eve-online.com/server/ServerStatus.xml.aspx';
        $status = simplexml_load_string(Plugin_Evsco::xmlCaching($source, 60*3));
        $online = $status->result->onlinePlayers;
        $sstatus = $status->result->serverOpen;

        if ($sstatus) {
            $this->_client->send('Tranquility: Online, ' . $online . ' players', $request);
        } else {
            $this->_client->send('Tranquility: Offline', $request);
        }
    }
}
