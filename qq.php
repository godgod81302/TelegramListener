<?php
require_once 'madeline.php';
require __DIR__.'/vendor/autoload.php';
set_time_limit(0);
class myProcess{
    private $app_id;
    private $api_hash;
    private $important_groups =[1400370402,1283996796,1362366154,1447023252,1283233252,703102017];
    private $important_users = [592088422,1042442912,1129597748];
    //客服群id
    private $fae_group_id = 1400370402;
    //產品群id
    private $product_group_id = 1283996796;
    //後端組id
    private $backend_group_id = 1362366154;
    //测试-后端
    private $test_group_id = 1447023252;
    //运维+后端
    private $ops_group_id = 1283233252;


    public function __construct() {

        $client = new Predis\Client(['host' => "127.0.0.1", "port" => 6379]);

        $client->set("tg_listener_run",true);

        $this->app_id = 18333171;
        $this->api_hash = 'dc8e192668840830bb8ad66c1691fa52';
        $MadelineProto = new \danog\MadelineProto\API('session.madeline',['app_info' => ['api_id' => $this->app_id, 'api_hash' => $this->api_hash], 'updates' => ['handle_updates' => false]]);
        $MadelineProto->start();
        // $MadelineProto->async(false);
        // $id =  $this->getMessages($MadelineProto);
        
        do{
            $list_of_channels = $MadelineProto->getFullDialogs();

            $play_audio_array = [];
            foreach ($list_of_channels as $i => $peer) {

                if ( isset($peer['notify_settings']['mute_until']) ){
                    if ( $peer['notify_settings']['mute_until']>0 ){
                        continue;
                    }
                }
                // $this->get_pwr_chat($MadelineProto,);
                if ( $peer['unread_count'] == 0 ){
                    continue;
                }
                $peer_string = '';
                if ( isset($peer['peer']['user_id']) ){
                    $peer_string = 'user#'.$peer['peer']['user_id'];
                }
                if ( isset($peer['peer']['channel_id']) ){
                    $peer_string = 'channel#'.$peer['peer']['channel_id'];
                }
                if ( isset($peer['peer']['chat_id']) ){
                    $peer_string = 'chat#'.$peer['peer']['chat_id'];
                }
                


                //未來還要處理私訊，目前getHistory只能拿到群組訊息
                $messages_Messages = $MadelineProto->messages->getHistory([
                                    'peer' => $peer_string, 
                                    'offset_id' => 0, 
                                    'offset_date' => 0,
                                    'add_offset' => 0, 
                                    'limit' =>  $peer['unread_count'], 
                                    'max_id' => 0,
                                    'min_id' => 0
                ]);

                if (count($messages_Messages['messages']) == 0){
                    continue;
                }
                else{

                    if ( isset($messages_Messages['messages']) ){
                        foreach( $messages_Messages['messages'] as $messages ){
                            //有可能是某人做了某事，沒講話就不會有message，例如: 某人離開群組
                            if (!isset($messages['message'])){
                               continue;
                            }
                            //整理陣列，拿出有用資料
                            //該則留言的重要程度 0為重要人士私訊 1為重要人士群組發言 2為群組被tag 3為群組重要群組case訊息 4為一般訊息
                            $tmp = [];
                            $tmp['priority'] = 4;
                            $tmp['play_message'] = '';
                            if ( count($messages_Messages['chats'])==0 ){

                                $tmp['from_user_id'] = $messages['peer_id']['user_id'];
                                if ( in_array($tmp['from_user_id'],$this->important_users)){
                                    //被重要人物私訊
                                    if ( $tmp['priority'] > 0 ){
                                        $tmp['priority']=0;
                                    }
                                }
                            }
                            else{
                                $tmp['group_id'] = $messages_Messages['chats'][0]['id'];
                                if( !in_array($tmp['group_id'],$this->important_groups) ){
                                    continue;
                                }
                                $tmp['group_title'] = $messages_Messages['chats'][0]['title'];    
                            }
                            if( isset($messages['entities'])){
                                foreach( $messages['entities'] as $k => $entity ){
                                    if ( $entity['_']=='messageEntityUrl' ){
                                        $messages['message'] = str_replace(substr($messages['message'],$entity['offset'],$entity['length']),'網址',$messages['message']);
                                        if ( $tmp['priority'] > 3 ){
                                            $tmp['priority']=3;
                                        }
                                    }
                                    if ( $entity['_']=='messageEntityMention' ){
                                        if ( substr($messages['message'],$entity['offset'],$entity['length'])=='@apex7414' ){
                                            if ( $tmp['priority'] > 1 ){
                                                $tmp['priority']=1;
                                            }
                                        }
                                    }
                                    if (isset($messages['from_id']['user_id'])){
                                        $tmp['from_user_id'] = $messages['from_id']['user_id'];
                                        if ( in_array($messages['from_id']['user_id'],$this->important_users)){
                                            if ( $tmp['priority'] > 2 ){
                                                $tmp['priority']=2;
                                            }
                                        }
                                    }
                                    $messages['message'] = substr($messages['message'],0,200);
 
                                    //表示這是最後 做處理
                                    if ( !isset($messages['entities'][$k+1]) ){
                                        if ( $tmp['priority'] == 0 ){
                                            $tmp['play_message'] = '重要组长私讯'.$messages['message'];
                                        }
                                        if ( $tmp['priority'] == 1 ){
                                            $tmp['play_message'] = '高等群组被tag'.$messages['message'];
                                        }
                                        if ( $tmp['priority'] == 2 ){
                                            $tmp['play_message'] = '中等组长群组讯息'.$messages['message'];
                                        }
                                        if ( $tmp['priority'] == 3 ){
                                            $tmp['play_message'] = '中等有case'.$messages['message'];
                                        }
                                    }
                                }
                            }
                            else{
                                if (isset($messages['from_id']['user_id'])){
                                    $tmp['from_user_id'] = $messages['from_id']['user_id'];
                                    if ( in_array($messages['from_id']['user_id'],$this->important_users)){
                                        if ( $tmp['priority'] > 2 ){
                                            $tmp['priority']=2;
                                            $tmp['play_message'] = '中等组长群组讯息'.$messages['message'];
                                        }
                                    }
                                }
                                if ( $tmp['priority']==0){
                                    $tmp['play_message'] = '重要组长私讯'.$messages['message'];
                                }
                            }

                            $tmp['message_id'] = $messages['id'];
                            if ( $tmp['priority'] == 4 ){
                                $tmp['play_message'] = '一般'.$messages['message'];
                            }
                            if ( $tmp['priority'] != 4){
                                array_push($play_audio_array,$tmp); 
                            }
                        }    
                    }   
                }
            }
            $this->sortArrByOneField($play_audio_array,'priority');
            // $played_message_array = $client->hmget("played_message_data");
            // print_r($played_message_array);exit;

            foreach( $play_audio_array as $play_audio ){
                $temp = [];
                $message_data = $client->hget("played_message_data",$play_audio['message_id']);
                if ( empty($message_data) ){
                    $set_result = $client->hset("played_message_data",$play_audio['message_id'],json_encode(["count"=>0,"priority"=>$play_audio['priority'],"time"=>strtotime("now")]));
                    $message_data = $client->hget("played_message_data",$play_audio['message_id']);
                }

                $temp = json_decode($message_data,true);
                if ( $temp['count'] == 0){
                    $this->tts($play_audio['play_message']);
                    $set_result = $client->hset("played_message_data",$play_audio['message_id'],json_encode(["count"=>1,"priority"=>$play_audio['priority'],"time"=>strtotime("now")]));
                }
                else if ( $temp['priority'] == 1 && $temp["count"] < 4 && (strtotime("now")-$temp["time"]) > 20 ){
                    $this->tts($play_audio['play_message']);
                    $set_result = $client->hset("played_message_data",$play_audio['message_id'],json_encode(["count"=>$temp["count"]+1,"priority"=>$play_audio['priority'],"time"=>strtotime("now")]));
                }
                else if ( $temp['priority'] == 0 && $temp["count"] < 4 && (strtotime("now")-$temp["time"]) > 20 ){
                    $this->tts($play_audio['play_message']);
                    $set_result = $client->hset("played_message_data",$play_audio['message_id'],json_encode(["count"=>$temp["count"]+1,"priority"=>$play_audio['priority'],"time"=>strtotime("now")]));     
                }
            }

        }while( $client->get("tg_listener_run") );

        // $content = $MadelineProto->get_full_info('@apexwu0817'); 
        // $me = $MadelineProto->getSelf();
        // file_put_contents("chat_log.json",json_encode($id));

    }
    //抓取所有群組資料
    private function getAllChats($MadelineProto){
        return $MadelineProto->messages->getAllChats()['chats'];
    }
    //根据群组信息获取群组所有用户信息（$groupInfo可以是邀请链接或id，例如’https://t.me/danogentili‘/’chat#492772765’/’channel#38575794’）
    private function get_pwr_chat($MadelineProto,$group_info){
        return $MadelineProto->get_pwr_chat($group_info);
    }
    //根据username获取用户详细信息
    private function get_full_info($MadelineProto,$username){
        return $MadelineProto->get_full_info($username);
    }
    //发送消息（文中 username为获取的用户username，传入时前面加前缀@，如@test， u s e r n a m e 为 获 取 的 用 户 u s e r n a m e ， 传 入 时 前 面 加 前 缀 @ ， 如 @ t e s t ， message则直接为想要发送的消息）
    private function sendMessage($MadelineProto,$peer,$message){
        return $MadelineProto->messages->sendMessage(['peer' => $peer, 'message' => $message]);
    }
    //getArmyMessage
    private function getMessages($MadelineProto){
        return $MadelineProto->messages->getMessages(['id' => [478156358], ]);
    }
    private function sortArrByOneField(&$array, $field, $desc = false){
        $fieldArr = array();
        foreach ($array as $k => $v) {
        $fieldArr[$k] = $v[$field];
        }
        $sort = $desc == false ? SORT_ASC : SORT_DESC;
        array_multisort($fieldArr, $sort, $array);
    }
    private function down_mp3($url,$fileName="test.mp3"){
        header ( "Content-Type:audio/mpeg");
        header ( "accept-encoding: gzip, deflate, br");
        header ( "accept-language: zh-TW,zh;q=0.9,en-US;q=0.8,en;q=0.7");
        header ( "cache-control: max-age=0");
        $fileName = "test.mp3";
        $file = file_get_contents($url);
        $fp = fopen($fileName, 'w');
        fwrite($fp, $file);
        fclose($fp);

      }
    private function tts($command){
        $char = "，。、！？：；﹑•＂…‘’“”〝〞∕¦‖—　〈〉﹞﹝「」‹›〖〗】【»«』『〕〔》《﹐¸﹕︰﹔！¡？¿﹖﹌﹏﹋＇´ˊˋ―﹫︳︴¯＿￣﹢﹦﹤‐­˜﹟﹩﹠﹪﹡﹨﹍﹉﹎﹊ˇ︵︶︷︸︹︿﹀︺︽︾ˉ﹁﹂﹃﹄︻︼()";
        $pattern = array(

            "/[[:punct:]]/i", //英文标点符号
            
            '/['.$char.']/u', //中文标点符号
            
            '/[ ]{2,}/'
            
            );
        $command = preg_replace($pattern, ' ', $command);
        $command = str_replace(" ","",$command);
        $command = str_replace("　","",$command);

        $google_tts_url = "https://api.voicerss.org/?key=9afcdd1a0e164e539f26b2c285a9282c&hl=zh-cn&c=MP3&src=".$command;
        $tts_data = $this->down_mp3($google_tts_url);
        $result = exec("py test.py");

    }

    
}

$process = new myProcess($argv);
