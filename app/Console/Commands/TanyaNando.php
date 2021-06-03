<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TanyaNando extends Command
{
    public const TOKEN = '753198043:AAFzc7o5sqRxfiVe4ZU8fZTvLHlZrPPt4kg';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tanyanando:start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enable bot TanyaNando';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->api = 'https://api.telegram.org/bot'.self::TOKEN;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        return $this->sendMessage();
    }

    public function sendMessage()
    {
        $update = json_decode(file_get_contents("php://input"), TRUE);
        $chatID = $update["message"]["chat"]["id"];
        $message = $update["message"]["text"];
        
        if (strpos($message, "/start") === 0) {
            file_get_contents($this->api."/sendmessage?chat_id=".$chatID."&text=Haloo, test webhooks <code>dicoffeean.com</code>.&parse_mode=HTML");
        }
    }
}
