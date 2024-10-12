<?php

namespace App\Http\Controllers;

use App\Http\Resources\MessageResource;
use App\Models\ChatRoom;
use App\Models\Message;
use App\Models\User;
use App\Repositories\MessageRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    public function __construct(private MessageRepository $messageRepository){}
    public function index(Request $request)
    {
        return $this->messageRepository->getMessages($request);
    }

    public function store(Request $request)
    {   
        return $this->messageRepository->createMessage($request);
    }
}
