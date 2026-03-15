<?php

namespace App\Http\Controllers\Nurse\Chat;

use App\Models\Chat;
use App\Models\Admin;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Filters\Doctor\ChatFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\ChatRequest;
use App\Http\Requests\Chat\SendRequest;
use App\Repositories\Chat\ChatRepository;
use App\Http\Requests\Chat\CategoryRequest;

class ChatController extends Controller
{
    private ChatRepository $chat;

    public function __construct(ChatRepository $chatRepo)
    {
        $this->chat = $chatRepo;
        $this->chat->setModel(Chat::make());
    }

    public function index(ChatFilters $filter)
    {
        try {
            $filter->extendRequest([
                'owner' => 1,
                'sortBy' => 1
            ]);

            $data = $this->chat->paginate(
                request('per_page', 10),
                filter: $filter,
                relations: ['lastMessage.receiver', 'unreadMessagesCount']

            );

            $user = request()->user();
            $admin = Admin::with('file')->first();

            $data->getCollection()->transform(function ($chat) use ($user, $admin) {
                $isSender = $chat->sender_id === $user->id;
                // Determine user_name and user_file
                if ($chat->chat_type == 'nurse_admin') {
                    $userName = $chat->receiver_id == 1 ? 'Admin' : $chat->receiver?->first_name . ' ' . $chat->receiver?->last_name;
                    $userFile = $chat->receiver_id == 1 ? $admin->file?->file_url : $chat->receiver?->file?->file_url;
                } else {
                    $userName = $isSender
                        ? $chat?->receiver?->first_name . ' ' . $chat?->receiver?->last_name
                        : $chat?->sender?->first_name . ' ' . $chat?->sender?->last_name;

                    $userFile = $isSender
                        ? $chat?->receiver?->file?->file_url
                        : $chat?->sender?->file?->file_url;
                }

                return [
                    'id' => $chat->id,
                    'sender_id' => $chat->sender_id,
                    'receiver_id' => $chat->receiver_id,
                    'chat_type' => $chat->chat_type,
                    'appointment_id' => $chat->appointment_id,
                    'created_at' => $chat->created_at,
                    'updated_at' => $chat->updated_at,
                    'last_message' => $chat->lastMessage->message,
                    'unread_count' => $chat->unreadMessagesCount->count(),
                    'user_name' => $userName,
                    'user_file' => $userFile,
                    'status' => $chat->status,
                ];
            });
            $data = api_successWithData('chat listing', $data);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function create(ChatRequest $request): JsonResponse
    {
        try {
            $params = $request->validated();
            $chatWithMessages = $this->chat->create($params);
            $data = api_success('Message sent successfully.', $chatWithMessages);
            return response()->json($data, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json($e->getMessage());
        }
    }

    public function send(SendRequest $request): JsonResponse
    {
        try {
            $params = $request->validated();
            $chatWithMessages = $this->chat->sendMessage($params);
            $data = api_success('Message sent successfully.', $chatWithMessages);
            return response()->json($data, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json($e->getMessage());
        }
    }

    public function show($id, ChatFilters $filter): JsonResponse
    {
        try {

            $filter->extendRequest([
                'chat_id' => $id
            ]);

            $data = $this->chat->paginate(
                request('per_page', 10),
                filter: $filter,
                relations: [
                    'messages.sender.file',
                    'messages.receiver.file',
                    'messages.sender',
                    'messages.receiver',
                ]
            );

            $admin = Admin::with('file')->first();

            $data->getCollection()->transform(function ($chat) use ($admin) {
                return [
                    'id' => $chat->id,
                    'sender_id' => $chat->sender_id,
                    'receiver_id' => $chat->receiver_id,
                    'chat_type' => $chat->chat_type,
                    'purchase_order_id' => $chat->purchase_order_id,
                    'created_at' => $chat->created_at,
                    'status' => $chat->status,
                    'messages' => $chat->messages->map(function ($message) use ($admin) {
                        return [
                            'id' => $message->id,
                            'chat_id' => $message->chat_id,
                            'sender_id' => $message->sender_id,
                            'receiver_id' => $message->receiver_id,
                            'message' => $message->message,
                            'is_read' => $message->is_read,
                            'created_at' => $message->created_at,
                            'sender' => $message->sender_id !== 1 ? [
                                'id' => $message->sender->id,
                                'name' => $message->sender->first_name . ' ' . $message->sender->first_name,
                                'role' => $message->sender->role,
                                'file' => $message?->sender?->file?->file_url,

                            ] : [
                                'id' => 1,
                                'name' => 'Admin',
                                'role' => 'admin',
                                'file' => $admin?->file?->file_url,

                            ],
                            'receiver' => $message->receiver_id !== 1  ? [
                                'id' => $message->receiver->id,
                                'name' => $message->receiver->first_name . ' ' . $message->receiver->first_name,
                                'role' => $message->receiver->role,
                                'file' => $message?->receiver?->file?->file_url,

                            ] : [
                                'id' => 1,
                                'name' => 'Admin',
                                'role' => 'admin',
                                'file' => $admin?->file?->file_url,

                            ],

                        ];
                    }),

                ];
            });
            // Mark messages as read using the service
            $this->chat->markMessagesAsRead($id, request()->user()->id); // Pass the authenticated user ID

            $data = api_successWithData('chat details', $data);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
