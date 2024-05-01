<?php
ob_start();
error_reporting(E_ALL); // Hamma xatoliklarni aniqlash
date_default_timezone_set("Asia/Tashkent");
$sana = date('d.m.Y');
$time = date('H:i');

// Madeline proto-ni tekshirib chiqamiz
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}

// Konfiguratsiya faylini tekshirib chiqamiz
if (!file_exists('config.json')) {
    file_put_contents('config.json', '{"clock":0,"read":0,"typing":0,"online":0}');
}

// Madeline proto-ni chaqirib olish
include 'madeline.php';

use danog\MadelineProto\EventHandler;
use danog\Loop\Generic\GenericLoop;
use danog\MadelineProto\API;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Settings;

class MyEventHandler extends EventHandler
{
    const Admins = [000000000]; // Bot adminlari ID raqamlarini o'zgartiring
    const Report = 'userbotim_y'; // Xatoliklarni yuborish uchun kanal/guruh nomini o'zgartiring

    public function getReportPeers()
    {
        return [self::Report];
    }

    public function genLoop(): int
    {
        $this->account->updateStatus([
            'offline' => false
        ]);
        return 60000;
    }

    public function onStart()
    {
        $genLoop = new GenericLoop([$this, 'genLoop'], [], 'update Status');
        $genLoop->start();
    }

    public function onUpdateNewChannelMessage($update)
    {
        $this->onUpdateNewMessage($update);
    }

    public function onUpdateNewMessage($update)
    {
        // Faylni bir marta o'qish
        $config = json_decode(file_get_contents('config.json'));
        
        // Vaqtini tekshirib olish
        if (time() - $update['message']['date'] > 2) {
            return;
        }

        try {
            $text = $update['message']['message'] ?? null;
            $mid = $update['message']['id'] ?? 0;
            $fid = $update['message']['from_id']['user_id'] ?? 0;
            $rmid = $update['message']['reply_to']['reply_to_msg_id'] ?? 0;
            $peer = $this->getID($update);

            // Xabarni o'qish holatini tekshirish
            if ($config->read == 1) {
                if ($peer < 0) {
                    $this->channels->readHistory([
                        'channel' => $peer,
                        'max_id' => $mid
                    ]);
                    $this->channels->readMessageContents([
                        'channel' => $peer,
                        'id' => [$mid]
                    ]);
                } else {
                    $this->messages->readHistory([
                        'peer' => $peer,
                        'max_id' => $mid
                    ]);
                }
            }

            // Yozish holatini tekshirish
            if ($config->typing == 1) {
                $this->messages->setTyping([
                    'peer' => $peer,
                    'action' => ['_' => 'sendMessageTypingAction']
                ]);
            }

            // Adminlarga xabar yuborish
            if (in_array($fid, self::Admins)) {
                // Maqola
            } elseif ($text == ".love") {
                $this->messages->editMessage([
                    'peer' => $peer,
                    'id' => $mid,
                    'message' => '🤍',
                ]);
            }
        } catch (\Throwable $e) {
            $this->report("Surfaced: $e");
        }
    }
}

$settings = new Settings();
$settings->set('logger', ['logger_level' => Logger::LEVEL_ULTRA_VERBOSE, 'logger' => Logger::LOGGER_FILE]);
MyEventHandler::startAndLoop('madeline.session', $settings);
?>
