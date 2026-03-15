<?php

namespace App\Http\Controllers\User\Chat;

use App\Models\Chat;
use App\Models\Admin;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Filters\Buyer\ChatFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\ChatRequest;
use App\Http\Requests\Chat\SendRequest;
use App\Repositories\Chat\ChatRepository;
use App\Http\Requests\Chat\CategoryRequest;
use App\Filters\User\ChatFilters as UserChatFilters;

class ChatController extends Controller
{
    private ChatRepository $chat;

    public function __construct(ChatRepository $chatRepo)
    {
        $this->chat = $chatRepo;
        $this->chat->setModel(Chat::make());
    }

    public function index(UserChatFilters $filter)
    {
        try {
            $filter->extendRequest([
                'owner' => 1,
                'sortBy' => 1
            ]);

            $data = $this->chat->paginate(
                request('per_page', 10),
                filter: $filter,
                relations: ['lastMessage.receiver', 'unreadMessagesCount', 'appointment']

            );

            $user = request()->user();
            $data->getCollection()->transform(function ($chat) use ($user) {
                $isSender = $chat->sender_id === $user->id;
                $admin = Admin::with('file')->first();
                if ($chat->chat_type == 'user_admin') {
                    $userName = $chat->receiver_id == 1 ? 'Admin' : $chat->receiver?->first_name . ' ' . $chat->receiver?->last_name;
                    $userFile = $chat->receiver_id == 1 ? $admin->file?->file_url : $chat->receiver->file?->file_url;
                } else {
                    $userName = $isSender
                        ? $chat->receiver?->first_name . ' ' . $chat->receiver?->last_name
                        : $chat->sender?->first_name . ' ' . $chat->sender?->last_name;

                    $userFile = $isSender
                        ? $chat->receiver->file?->file_url
                        : $chat->sender->file?->file_url;
                }

                return [
                    'id' => $chat?->id,
                    'sender_id' => $chat?->sender_id,
                    'receiver_id' => $chat?->receiver_id,
                    'chat_type' => $chat?->chat_type,
                    'appointment_id' => $chat?->appointment_id,
                    'created_at' => $chat?->lastMessage?->created_at,
                    'updated_at' => $chat?->lastMessage?->updated_at,
                    'last_message' => $chat?->lastMessage?->message,
                    'unread_count' => $chat?->unreadMessagesCount->count(),
                    'user_name' => $userName,
                    'user_file' => $userFile,
                    'type' => $chat?->type,
                    'status' => $chat?->status,
                    'is_live' => $chat?->appointment?->is_live ?? 0,
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
            $data = api_successWithData('Message sent successfully.', $chatWithMessages);
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
            $data = api_successWithData('Message sent successfully.', $chatWithMessages);
            return response()->json($data, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json($e->getMessage());
        }
    }

    public function show($id, $type, UserChatFilters $filter): JsonResponse
    {
        try {
            $filter->extendRequest([
                'chat_id' => $id,
                'sortBy' => 1,
                'message_type' => $type
            ]);

            // Retrieve the chat data with related messages
            $data = $this->chat->findById(
                $id,
                filter: $filter,
                relations: [
                    'messages.sender.file',
                    'messages.receiver.file',
                    'messages.sender',
                    'messages.receiver',
                    'appointment'
                ]
            );

            $admin = Admin::with('file')->first();

            // Make sure to wrap the result in a collection before calling transform
            $data = collect([$data])->transform(function ($chat) use ($admin) {
                return [
                    'id' => $chat->id,
                    'sender_id' => $chat->sender_id,
                    'receiver_id' => $chat->receiver_id,
                    'chat_type' => $chat->chat_type,
                    'purchase_order_id' => $chat->purchase_order_id,
                    'created_at' => $chat->created_at,
                    'status' => $chat->status,
                    'type' => $chat->type,
                    'is_live' => $chat?->appointment?->is_live ?? 0,
                    'messages' => $chat->messages->map(function ($message) use ($admin) {

                        return [
                            'id' => $message->id,
                            'chat_id' => $message->chat_id,
                            'sender_id' => $message->sender_id,
                            'receiver_id' => $message->receiver_id,
                            'message' => $message->message,
                            'is_read' => $message->is_read,
                            'created_at' => $message->created_at,
                            'sender' => $message->sender_id === 1 || !$message->sender ? [
                                'id' => 1,
                                'name' => 'Admin',
                                'role' => 'admin',
                                'file' => $admin?->file?->file_url,
                            ] : [
                                'id' => $message->sender->id,
                                'name' => $message->sender->first_name . ' ' . $message->sender->last_name,
                                'role' => $message->sender->role,
                                'file' => $message?->sender?->file?->file_url,
                            ],
                            'receiver' => $message->receiver_id === 1 || !$message->receiver ? [
                                'id' => 1,
                                'name' => 'Admin',
                                'role' => 'admin',
                                'file' => $admin?->file?->file_url,
                            ] : [
                                'id' => $message->receiver->id,
                                'name' => $message->receiver->first_name . ' ' . $message->receiver->last_name,
                                'role' => $message->receiver->role,
                                'file' => $message?->receiver?->file?->file_url,

                            ],
                        ];
                    }),
                ];
            });

            // Mark messages as read
            $this->chat->markMessagesAsRead($id, request()->user()->id);

            // Return the transformed data as a JSON response
            return response()->json(api_successWithData('chat details', $data->first()), Response::HTTP_OK);
        } catch (\Exception $e) {
            // Handle any errors
            return response()->json(api_error($e->getMessage()), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
