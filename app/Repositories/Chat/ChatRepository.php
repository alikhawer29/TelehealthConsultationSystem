<?php

namespace App\Repositories\Chat;

use App\Models\Chat;
use App\Models\User;
use App\Models\Admin;
use GuzzleHttp\Client;
use App\Models\Message;
use App\Core\Traits\SplitPayment;
use Illuminate\Database\Eloquent\Model;
use App\Core\Abstracts\Repository\BaseRepository;
use App\Models\Appointment;

class ChatRepository extends BaseRepository implements ChatRepositoryContract
{

    protected $model;
    use SplitPayment;


    public function setModel(Model $model)
    {
        $this->model = $model;
    }

    public function get()
    {
        try {
            $data =   $this->model->get();
            return $data;
        } catch (\Throwable $th) {
            \DB::rollBack();
            throw $th;
        }
    }

    public function status($id)
    {
        try {
            $user = request()->user();
            $this->model->where('id', $id)
                ->update([
                    'status' => \DB::raw("CASE WHEN status = 'active' THEN 'inactive' ELSE 'active' END")
                ]);

            return true;
        } catch (\Throwable $th) {
            \DB::rollBack();
            throw $th;
        }
    }

    public function updateAddress($conditionalParams, $id)
    {
        \DB::beginTransaction();
        try {
            $user = request()->user();

            $client = new Client();
            $response = $client->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'query' => [
                    'latlng' => $conditionalParams['lat'] . ',' . $conditionalParams['lng'],
                    'key' => 'AIzaSyAHPUufTlBkF5NfBT3uhS9K4BbW2N-mkb4',
                ]
            ]);

            $geocodeData = json_decode($response->getBody(), true);

            $city = null;
            $country = null;


            foreach ($geocodeData['results'][0]['address_components'] as $component) {
                if (in_array('locality', $component['types'])) {
                    $city = $component['long_name'];
                } elseif (in_array('country', $component['types'])) {
                    $country = $component['long_name'];
                }

                // Break the loop if both city and country are found
                if ($city !== null && $country !== null) {
                    break;
                }
            }


            $address =  $this->model->where('id', $id)->update([
                'lat' =>  $conditionalParams['lat'],
                'lng' =>  $conditionalParams['lng'],
                'address' =>  $city . ',' . $country,
            ]);

            \DB::commit();
            return $address;
        } catch (\Throwable $th) {
            \DB::rollBack();
            throw $th;
        }
    }

    //session chat always initiated by doctor
    //general chat always initiated by user

    public function createold(array $params)
    {
        \DB::beginTransaction();
        try {
            // Get the authenticated user (sender)
            $user = request()->user();
            $sender_role = $user->role; // Assume 'role' is a column in the users table

            if ($params['chat_type'] == 'admin') {
                $other_user = Admin::findorfail(1);
                $other_user_role = 'admin';
                $receiver_id = $other_user->id;
                $type = 'general';
                $appointmentId = 0;
            } elseif (isset($params['appointment_id']) && $params['appointment_id'] != null) {
                //Session Chat
                $other_user = User::find($params['receiver_id']);
                $other_user_role = $other_user->role;
                $receiver_id = $params['receiver_id'];
                $appointmentId = $params['appointment_id'];
                $type = 'session';
            } else {
                //General Chat
                $other_user = User::find($params['receiver_id']);
                $other_user_role = $other_user->role;
                $receiver_id = $params['receiver_id'];
                $type = 'general';
                $appointmentId = 0;
            }

            // Determine chat type based on roles
            $chat_type = '';

            if (
                ($sender_role === 'user' && $other_user_role === 'admin') ||
                ($sender_role === 'admin' && $other_user_role === 'user')
            ) {
                $chat_type = 'user_admin';
            } elseif (
                ($sender_role === 'nurse' && $other_user_role === 'admin') ||
                ($sender_role === 'admin' && $other_user_role === 'nurse')
            ) {
                $chat_type = 'nurse_admin';
            } elseif (
                ($sender_role === 'physician' && $other_user_role === 'admin') ||
                ($sender_role === 'admin' && $other_user_role === 'physician')
            ) {
                $chat_type = 'admin_physician';
            } elseif (
                ($sender_role === 'doctor' && $other_user_role === 'admin') ||
                ($sender_role === 'admin' && $other_user_role === 'doctor')
            ) {
                $chat_type = 'admin_doctor';
            } elseif (
                ($sender_role === 'user' && $other_user_role === 'nurse') ||
                ($sender_role === 'nurse' && $other_user_role === 'user')
            ) {
                $chat_type = 'user_nurse';
            } elseif (
                ($sender_role === 'user' && $other_user_role === 'doctor') ||
                ($sender_role === 'doctor' && $other_user_role === 'user')
            ) {
                $chat_type = 'user_doctor';
            } elseif (
                ($sender_role === 'user' && $other_user_role === 'physician') ||
                ($sender_role === 'physician' && $other_user_role === 'user')
            ) {
                $chat_type = 'user_physician';
            } else {
                // Handle any other cases (optional)
                $chat_type = 'general';
            }



            // Check if the chat already exists
            $chat = $this->model->where('sender_id', $user->id)
                ->where('receiver_id', $receiver_id)
                ->where('appointment_id', $appointmentId)
                ->first();

            // If no chat exists, create a new chat
            if (!$chat) {
                // Create the chat
                $data = $this->model->create(
                    [
                        'sender_id' => $user->id,
                        'receiver_id' => $receiver_id,
                        'chat_type' => $chat_type,
                        'appointment_id' => $appointmentId,
                        'type' => $type
                    ]
                );

                // Create a message for the new chat
                $message = Message::create([
                    'chat_id' => $data->id,
                    'sender_id' => $user->id,
                    'receiver_id' => $receiver_id,
                    'message' => "Hello, Doctor here, how can i help you?",
                    'is_read' => false,
                ]);
                \DB::commit();
                return $data;
            }

            \DB::commit();
            return $chat;
        } catch (\Throwable $th) {
            \DB::rollback();
            throw $th;
        }
    }

    public function create(array $params)
    {
        \DB::beginTransaction();

        try {
            $user = request()->user();
            $sender_role = $user->role;

            $type = 'general'; // default
            $appointmentId = 0;
            $receiver_id = null;
            $other_user = null;
            $other_user_role = null;

            if ($params['chat_type'] === 'admin') {
                $other_user = Admin::findOrFail(1);
                $other_user_role = 'admin';
                $receiver_id = $other_user->id;
            } elseif (!empty($params['appointment_id'])) {
                // Session chat — must be initiated by doctor
                if ($sender_role !== 'doctor') {
                    throw new \Exception("Only doctors can initiate session chats.");
                }

                $other_user = User::findOrFail($params['receiver_id']);
                $other_user_role = $other_user->role;
                $receiver_id = $other_user->id;
                $appointmentId = $params['appointment_id'];
                $type = 'session';
            } else {
                // General chat — must be initiated by user
                if ($sender_role !== 'user') {
                    throw new \Exception("Only users can initiate general chats.");
                }

                $other_user = User::findOrFail($params['receiver_id']);
                $other_user_role = $other_user->role;
                $receiver_id = $other_user->id;
            }

            // Determine chat type based on roles
            $chat_type = match (true) {
                ($sender_role === 'user' && $other_user_role === 'admin') || ($sender_role === 'admin' && $other_user_role === 'user') => 'user_admin',
                ($sender_role === 'nurse' && $other_user_role === 'admin') || ($sender_role === 'admin' && $other_user_role === 'nurse') => 'nurse_admin',
                ($sender_role === 'physician' && $other_user_role === 'admin') || ($sender_role === 'admin' && $other_user_role === 'physician') => 'admin_physician',
                ($sender_role === 'doctor' && $other_user_role === 'admin') || ($sender_role === 'admin' && $other_user_role === 'doctor') => 'admin_doctor',
                ($sender_role === 'user' && $other_user_role === 'nurse') || ($sender_role === 'nurse' && $other_user_role === 'user') => 'user_nurse',
                ($sender_role === 'user' && $other_user_role === 'doctor') || ($sender_role === 'doctor' && $other_user_role === 'user') => 'user_doctor',
                ($sender_role === 'user' && $other_user_role === 'physician') || ($sender_role === 'physician' && $other_user_role === 'user') => 'user_physician',
                default => 'general',
            };

            // Check if chat already exists
            $chat = $this->model->where('sender_id', $user->id)
                ->where('receiver_id', $receiver_id)
                ->where('appointment_id', $appointmentId)
                ->first();

            if (!$chat) {
                $chat = $this->model->create([
                    'sender_id' => $user->id,
                    'receiver_id' => $receiver_id,
                    'chat_type' => $chat_type,
                    'appointment_id' => $appointmentId,
                    'type' => $type,
                ]);

                // Add initial message for session chat (from doctor)
                if ($type === 'session') {

                    $appointment = Appointment::findOrFail($appointmentId);
                    $appointment->is_live = 1;
                    $appointment->save();

                    Message::create([
                        'chat_id' => $chat->id,
                        'sender_id' => $user->id,
                        'receiver_id' => $receiver_id,
                        'message' => "Hello, Doctor here, how can I help you?",
                        'is_read' => false,
                    ]);
                }
                if ($type === 'general') {
                    Message::create([
                        'chat_id' => $chat->id,
                        'sender_id' => $user->id,
                        'receiver_id' => $receiver_id,
                        'message' => "Hey, I’m looking for some information.",
                        'is_read' => false,
                    ]);
                }
            }

            \DB::commit();
            return $chat;
        } catch (\Throwable $th) {
            \DB::rollBack();
            throw $th;
        }
    }


    public function sendMessage(array $params)
    {
        \DB::beginTransaction();
        try {
            // Get the authenticated user (sender)
            $user = request()->user();

            $sender_id = $user->id;
            $chat = $this->model->findOrFail($params['chat_id']);
            $receiver_id = $sender_id === $chat->sender_id ? $chat->receiver_id : $chat->sender_id;

            // Create a new message
            $message = Message::create([
                'chat_id' => $params['chat_id'],
                'sender_id' => $user->id,
                'receiver_id' => $receiver_id,
                'message' => $params['message'],
                'is_read' => false,
                // 'chat_type' => $chat->chat_type,
            ]);
            \DB::commit();

            // Return the chat with all messages including the new one
            $chatWithMessages = Chat::with('messages')->find($params['chat_id']);

            return $chatWithMessages;
        } catch (\Throwable $th) {
            \DB::rollback();
            throw $th;
        }
    }

    public function markMessagesAsRead($chatId, $userId)
    {
        \DB::beginTransaction();

        try {
            // Update all unread messages for the specified chat to read
            Message::where('chat_id', $chatId)
                ->where('is_read', false) // Only mark unread messages as read
                ->where('receiver_id', $userId) // Ensure only messages for this user are marked as read
                ->where(function ($query) use ($userId) {
                    $query->where('receiver_id', $userId)
                        ->orWhere('sender_id', $userId);
                })
                ->update(['is_read' => true]);

            \DB::commit();
            return true;
        } catch (\Throwable $th) {
            \DB::rollback();
            throw $th;
        }
    }
}
