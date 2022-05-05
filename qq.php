<?php
require_once 'madeline.php';
require __DIR__.'/vendor/autoload.php';
set_time_limit(0);
class myProcess{
    private $app_id;
    private $api_hash;
    private $tts_key;
    private $important_groups =[1400370402,1283996796,1362366154,1447023252,1283233252,703102017];
    private $important_users = [];



    public function __construct($argv) {

        if ( isset($argv[1]) ){
            if( $argv[1]=="init" ){
                echo "Do you have already get your telegram app_id and api_hash(y/n)?";
                $handle = fopen ("php://stdin","r");
                $line = fgets($handle);
                if(trim($line) != 'y'){
                    echo "請參考 https://my.telegram.org/auth ，創建自己的app，並存下app_id以及api_hash\n";
                    exit;
                }else{
                    $env_data = file_get_contents("env_data.json");
                    $env_data_array = json_decode($env_data,true);
                    echo "請輸入app_id:\n";
                    $handle = fopen ("php://stdin","r");
                    $line = fgets($handle);
                    $env_data_array["app_id"] = str_replace(array("\r","\n","\r\n","\n\r"),'',$line);
                    echo "請輸入api_hash:\n";
                    $handle = fopen ("php://stdin","r");
                    $line = fgets($handle);
                    $env_data_array["api_hash"] = str_replace(array("\r","\n","\r\n","\n\r"),'',$line);
                    echo "儲存中...\n";
                    file_put_contents("env_data.json",json_encode($env_data_array));
                    echo "完成\n";
                    exit;
                }
                echo "Do you have already get your tts_key(y/n)?";
                if(trim($line) != 'y'){
                    echo "請前往 https://www.voicerss.org/pricing/ 辦個帳號，申請tts服務(免費)\n";
                    exit;
                }else{
                    $env_data = file_get_contents("env_data.json");
                    $env_data_array = json_decode($env_data,true);
                    echo "請輸入tts_key:\n";
                    $handle = fopen ("php://stdin","r");
                    $line = fgets($handle);
                    $env_data_array["tts_key"] = str_replace(array("\r","\n","\r\n","\n\r"),'',$line);
                    echo "儲存中...\n";
                    file_put_contents("env_data.json",json_encode($env_data_array));
                    echo "完成\n";
                    exit;
                }
            }

        }
        $client = new Predis\Client(['host' => "127.0.0.1", "port" => 6379]);

        $client->set("tg_listener_run",true);
        $env_data = file_get_contents("env_data.json");
        $env_data_array = json_decode($env_data,true);
        $this->app_id = str_replace(array("\r","\n","\r\n","\n\r"),'',$env_data_array["app_id"]);
        $this->api_hash = str_replace(array("\r","\n","\r\n","\n\r"),'',$env_data_array["api_hash"]);
        if ( isset($env_data_array["tts_key"]) ){
            $this->tts_key = $env_data_array["tts_key"];
        }
        if ( isset($env_data_array["important_users"]) ){
            $this->important_users = $env_data_array["important_users"];
        }
        $this->important_users = $env_data_array["important_users"];

        $MadelineProto = new \danog\MadelineProto\API('session.madeline',['app_info' => ['api_id' => $this->app_id, 'api_hash' => $this->api_hash], 'updates' => ['handle_updates' => false]]);
        $MadelineProto->start();
        // $MadelineProto->async(false);
        // $id =  $this->getMessages($MadelineProto);
        if ( isset($argv[1]) ){
            if( $argv[1]=="load" ){
                $env_data = file_get_contents("env_data.json");
                $env_data_array = json_decode($env_data,true);
                $me = $MadelineProto->getSelf();
                $env_data_array['myusername'] = '@'.$me["username"];
                file_put_contents("env_data.json",json_encode($env_data_array));
                echo "Do you wish to load all your groupdata(y/n)?";
                $handle = fopen ("php://stdin","r");
                $line = fgets($handle);
                if(trim($line) != 'y'){
                    exit;
                }else{
                    file_put_contents("all_chat_data.json",json_encode($MadelineProto->messages->getAllChats()['chats']));
                    print_r($MadelineProto->messages->getAllChats()['chats']);
                    echo "相關資料已儲存到all_chat_data.json\n";
                    echo "稍後請自行到該檔案中取得需要監聽的群組id，並將那些id手動新增到qq.php裡的important_groups\n";
                    echo "要繼續取得重要人士id嗎?(y/n)\n";
                    $handle = fopen ("php://stdin","r");
                    $line = fgets($handle);
                    if(trim($line) != 'y'){
                        exit;
                    }
                    do{
                        $bool_keep = true;
                        echo "請輸入您想儲存的目標人士的tg帳號，想結束請輸入n例如 @xxxx :\n";
                        $handle = fopen ("php://stdin","r");
                        $line = fgets($handle);
                        if(trim($line) == 'n'){
                            exit;
                        }
                        $leader_info = $this->get_full_info($MadelineProto,str_replace(array("\r","\n","\r\n","\n\r"),'',$line));
                        if ( isset($leader_info["User"]["id"]) ){
                            echo "名稱:".$leader_info["User"]["first_name"]."\n";
                            $env_data = file_get_contents("env_data.json");
                            $env_data_array = json_decode($env_data,true);
                            if ( isset($env_data_array["important_users"]) ){
                                if ( !in_array($leader_info["User"]["id"],$env_data_array["important_users"]) ){
                                    array_push($env_data_array["important_users"],$leader_info["User"]["id"]);
                                    file_put_contents("env_data.json",json_encode($env_data_array));
                                    echo "新增完成"."\n";
                                }
                                else{
                                    echo "已存在無須再次新增"."\n";
                                }
                            }
                            else{
                                $env_data_array["important_users"]  = [$leader_info["User"]["id"],];
                                file_put_contents("env_data.json",json_encode($env_data_array));
                                echo "完成\n";
                            }
                        }
                        else{
                            echo "找不到該帳號\n";
                            echo "是否結束?(y/n)\n";
                            $handle = fopen ("php://stdin","r");
                            $line = fgets($handle);
                            if(trim($line) == 'y'){
                                exit;
                            }
                        }
                    }while($bool_keep);

                    exit;
                }
            }

        }
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
                                        if ( substr($messages['message'],$entity['offset'],$entity['length'])==$env_data_array["myusername"] ){
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
        if ( empty($this->tts_key) ){echo "錯誤，env_data中還沒有tts_key";exit;}
        $google_tts_url = "https://api.voicerss.org/?key=".$this->tts_key."&hl=zh-cn&c=MP3&src=".$command;
        $tts_data = $this->down_mp3($google_tts_url);
        $result = exec("py test.py");
    }

    
}

$process = new myProcess($argv);
