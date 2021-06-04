<?php

namespace App\Http\Controllers;

use App\Models\Inbox;
use Illuminate\Http\Request;

class HookController extends Controller
{   
    public function __construct()
    {
        $this->api = 'https://api.telegram.org/bot'.env('TOKEN');
    }
    
    public function store(Request $request)
    {
        \Log::info(json_encode(file_get_contents("php://input")));
        return $this->sendMessage();
    }

    public function sendMessage()
    {
        $update = json_decode(file_get_contents("php://input"), TRUE);
        $chatID = $update["message"]["chat"]["id"];
        $message = $update["message"]["text"] ?? null;
        $messageId = $update["message"]["message_id"];
        
        if (strpos(strtolower($message), "/start") === 0) {
            $senderName = $update["message"]["from"]['first_name'] ?? $update["message"]["from"]['username'];
            $ans = "Selamat datang $senderName. Silahkan tanyakan hal seputar Kerjaan, Programming dan IT Security (CTF), cara pakai dan aturan kamu ketik <b>/help</b>!";
            file_get_contents($this->api."/sendmessage?chat_id=".$chatID."&text=$ans&parse_mode=HTML");
            $this->forwardMessage($chatID, $messageId);
        }

        if (strpos(strtolower($message), "/help") === 0) {
            $message = <<<TEXT
            --------------------------------------------
            -------------- Bantuan dan Aturan -------------
            -----------------------------------------------
            Ada 3 status pada setiap pertanyaanmu 

            - Waiting : menunggu dikerjakan / dibaca Bos Nando
            - Progress : dalam masa pengerjaan oleh Bos Nando (jadi sabar ya ! ^_^)
            - Done : pengerjaan sudah selesai / pertanyaan sudah terjawab ^_^
            - Pending : pertanyaanmu / proses pengerjaan dipending karena ada hal lain yang harus didahulukan jadi sabar ya ^_^, Bos Nando bukan ninja yang bisa bikin bunshin :)


            1. gunakan prefix "tanya:" dan lanjutkan isi pertanyaanmu untuk bertanya, contohnya tanya:ndo uang purun ?
            2. gunakan prefix "cek:" dan isi ID Pertanyaanmu untuk mengecek progress pertanyaanmu, contohnya cek:N31337
            3. kamu akan segera dapat balasan setelah Bos Nando membaca pesanmu sesuai nomor antrian pertanyaan
            4. kamu akan mendapat notifikasi dariku jika status pertanyaanmu berubah (WAITING atau PROGRESS atau DONE atau PENDING)
            --------------------------------------------
            TEXT;
            $message = urlencode("```$message```");
            
            file_get_contents($this->api."/sendmessage?chat_id=".$chatID."&text=$message&parse_mode=markdown");
            $this->forwardMessage($chatID, $messageId);
        }

        if (strpos(strtolower($message), "tanya:") === 0) {
            $code = $this->generateRandomString(7);
            $ins = $this->insert($update, $code);
            if (!$ins) {
                file_get_contents($this->api."/sendmessage?chat_id=".$chatID."&text=Ada error nih ketika nampung pertanyaanmu, kontak bos Nando via WA ya !&parse_mode=HTML");
                die;
            }
            
            $ans = $this->replyBot($code);
            file_get_contents($this->api."/sendmessage?chat_id=".$chatID."&text=$ans&parse_mode=HTML");
            $this->forwardMessage($chatID, $messageId);
        }

        if (strpos(strtolower($message), "cek:") === 0) {
            $code = explode(":", $message)[1] ?? null;
            if (empty($code)) {
                file_get_contents($this->api."/sendmessage?chat_id=".$chatID."&text=Ada kesalahan input kode!&parse_mode=HTML");
                die;
            }
            
            $i = Inbox::where('code', $code)->first();

            if (empty($i)) {
                file_get_contents($this->api."/sendmessage?chat_id=".$chatID."&text=ID Pertanyaanmu tidak ada nih di database, cek lagi ya !&parse_mode=HTML");
                die;
            }

            $ans = "Username Penanya : $i->username\n";
            $ans .= "Nama Penanya : $i->name\n";
            $ans .= "Status : $i->status\n";
            $ans .= "Pesan : $i->message\n";
            $status = Inbox::STATUS[$i->status] ?? "STATUS TIDAK DIKETAHUI";
            $message = <<<TEXT
            --------------------------------------------
            -------------- CEK PROGRESS ----------------
            --------------------------------------------
            Username Penanya : $i->username
            Nama Penanya : $i->name
            Status : $status
            Pesan : $i->message
            --------------------------------------------
            TEXT;

            $message = urlencode("```$message```");

            file_get_contents($this->api."/sendmessage?chat_id=".$chatID."&text=$message&parse_mode=markdown");
            $this->forwardMessage($chatID, $messageId);
        }
    }

    public function replyBot($code = null)
    {
        $waiting = Inbox::waiting()->count();
        // if ($waiting == 1) {
        //     $waiting = 1;
        // } else {
        //     $waiting += 1;
        // }
        $progress = Inbox::progress()->pluck('code')->toArray();
        $progress = implode(",", $progress);
        
        $template = urlencode("===================== \n<b>ID PERTANYAAN</b> : $code\n<b>ANTRIAN KE : $waiting</b>\n<b>STATUS : WAITING \xE2\x9A\xA0</b>\n<b>Bos Nando sedang progress ID Pertanyaan lainnya : $progress 🧑‍💻</b>");
        $ans = [
            "OK, pertanyaan mu aku tampung dulu ya ! $template\n",
            "Pertanyaanmu aku tampung dulu ya !, sabar tunggu giliran antrianmu $template\n",
            "OK, noted ! sabar tunggu giliran antrianmu $template\n",
        ];

        return $ans[array_rand($ans)];
    }

    public function forwardMessage($chatId, $messageId)
    {
        return file_get_contents($this->api."/forwardMessage?chat_id=$chatId&from_chat_id=$chatId&message_id=$messageId");
    }

    public  function generateRandomString($length = 20) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function insert($post, $code = "")
    {
        try {
            $i = new Inbox;
            $i->username = $post["message"]['from']['username'] ?? "";
            $name = "Kosong";
            if (!empty($post["message"]['from']['first_name'])) {
                $name = $post["message"]['from']['first_name'];
            }
            if (!empty($post["message"]['from']['last_name'])) {
                $name .= $post["message"]['from']['last_name'];
            }
            $i->code = $code;
            $i->name = $name;
            $i->chat_id = $post["message"]["chat"]["id"];
            $i->message_id = $post["message"]["message_id"];
            $i->message = $post["message"]["text"] ?? "";
            $i->images = $post['message']['photo'][0]['file_id'] ?? null;
            $i->status = Inbox::WAITING;
            $i->save();
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return false;
        }

        return true;
    }
}
