<?php

namespace App\Livewire;

use App\Events\MessageSentEvent;
use App\Models\message;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class ChatComponent extends Component
{
    public $user;
    public $sender_id;
    public $receiver_id;
    public $messageInput='';
    public $message =[];

    public function render()
    {
        return view('livewire.chat-component');
    }

    public function mount($user_id){

        $this->user = User::whereId($user_id)->first();
        
        $currentUser = Auth::user();
        $this->sender_id = $currentUser->id;
        $this->receiver_id = $user_id;

        $messages = message::where(function($query){
            $query->where('sender_id', $this->sender_id)
                  ->where('receiver_id', $this->receiver_id);
        })->orWhere(function($query){
            $query->where('sender_id', $this->receiver_id)
                  ->where('receiver_id', $this->sender_id);
        })
        ->with('sender:id,name', 'receiver:id,name')
        ->get();

        foreach($messages as $message){
            $this->appendChatMessage($message);
        }
        
    }
    public function sendMessage(){
        $chatMessage = new message();
        $chatMessage->sender_id = $this->sender_id;
        $chatMessage->receiver_id = $this->receiver_id;
        $chatMessage->message = $this->messageInput;
        $chatMessage->save();
        
        $this->appendChatMessage($chatMessage);
        
        broadcast(new MessageSentEvent($chatMessage))->toOthers();

        $this->messageInput='';
    }

    #[On('echo-private:wechat.{sender_id},MessageSentEvent')]
    public function listenMessage($event){
        $chatMessage = message::whereId($event['message']['id'])
        ->with('sender:id,name', 'receiver:id,name')
        ->first();
        $this->appendChatMessage($chatMessage);
    }

    public function appendChatMessage($message){
        $this->message[] = [
            'id' => $message->id,
            'message' => $message->message,
            'sender' => $message->sender->name,
            'receiver' => $message->receiver->name,
        ];

    }
}
