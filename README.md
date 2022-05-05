# TelegramListener
用來作為client端取得telegram重要通知並且實時語音提醒
在本地端用xampp 簡單建立一個php執行環境
接著在windows上安裝python環境
可參考 https://ithelp.ithome.com.tw/articles/10210071
1.pip install playsound

2.請申請tts_key https://www.voicerss.org/pricing/ 申請個免費的就可以 將api token存下來 等下執行load時會要求你輸入

3..php qq.php init

4..php qq.php load

&nbsp;&nbsp;&nbsp;&nbsp;如果是第一次使用會需要登入telegram，會跳出提示 
  
&nbsp;&nbsp;&nbsp;&nbsp;Do you want to login as user or bot (u/b)? 這邊選擇 u 表示以user身分登入tg而非以機器人身分
  
&nbsp;&nbsp;&nbsp;&nbsp;Enter your phone number: 請填入telegram帳號所綁定的電話號碼 前面須加上國際區碼 例如: +886962236077
  
&nbsp;&nbsp;&nbsp;&nbsp;這時候會需要到telegram上看一下官方頻道推給你的驗證碼(如下附圖)，要是過太久沒推送驗證碼就從新執行程式再嘗試一次

&nbsp;&nbsp;&nbsp;&nbsp;實測過如果電腦時間不準的話會送不出code
  
  
![image](https://user-images.githubusercontent.com/17896103/166940937-b2753f20-4c5f-4647-ae92-9934f0771bc1.png)

使用load指令後，請到all_chat_data.json將欲監聽的群組id記錄下來，填入qq.php的important_groups陣列中

最後 直接執行 php qq.php 成功執行便會維持以下畫面
![image](https://user-images.githubusercontent.com/17896103/166965853-58d9e9c7-a9a8-4f16-975a-1af74d8be814.png)

目前為了節省tts使用次數，做成只有重要人士 在重要群組的tag或者發話 或者對你的私訊 或者 是帶網址的訊息才會廣播
邏輯可以自己改，但非常不建議用機器人自動回覆訊息去搶單，因為萬一發生你預想外的狀況 程式沒辦法替你負責
