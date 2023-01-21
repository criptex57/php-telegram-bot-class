# php-telegram-bot-class

$bot = new Bot(  
    '5507865857:888666555-555666777He3R8PKgXxDs', //require param  
    'start text',  
    ['start button']  
);  
  
# sendMessage 
  $text,   
  $userId,   
  $keyboard = null, //exemple ['text button', 'text button sec']
  $oneTimeKey = true //show one time button

# getUpdates
Array ( [message_id] => 86 [from] => Array ( [id] => 5113795567 [is_bot] => [first_name] => Andy [last_name] => Grabers [language_code] => ru ) [chat] => Array ( [id] => 5113795567 [first_name] => Andy [last_name] => Grabers [type] => private ) [date] => 1674304372 [text] => /start [entities] => Array ( [0] => Array ( [offset] => 0 [length] => 6 [type] => bot_command ) ) )

# sendImage($chatId, $imageUrl)




