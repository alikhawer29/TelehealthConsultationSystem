<?php

namespace App\Core\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NotifyAdmin extends Notification implements ShouldQueue {
	use Queueable;

	/**
	 * Create a new notification instance.
	 *
	 * @return void
	 */
	private $payload;
	public function __construct($payload) {
		$this->payload = $payload;
		$this->sender = auth('admin')->id();
	}

	/**
	 * Get the notification's delivery channels.
	 *
	 * @param  mixed  $notifiable
	 * @return array
	 */
	public function via($notifiable) {
		return ['database', 'broadcast'];
	}

	/**
	 * Get the mail representation of the notification.
	 *
	 * @param  mixed  $notifiable
	 * @return \Illuminate\Notifications\Messages\MailMessage
	 */
	public function toMail($notifiable) {
		return (new MailMessage)
			->line('The introduction to the notification.')
			->action('Notification Action', url('/'))
			->line('Thank you for using our application!');
	}

	public function toDatabase($notifiable) {
		return [
			'message' => $this->payload['message'],
			'ar_message' => $this->payload['ar_message'],
			'title' => $this->payload['title'],
			'ar_title' => $this->payload['ar_title'],
			'id' => $this->payload['id'] ?? "",
			'route' => $this->payload['route'],
		];
	}

	public function toBroadCast() {
		return new BroadcastMessage([
			'id' => $this->payload['id'] ?? '',
			'data' => [
				'title' => $this->payload['title'],
				'ar_title' => $this->payload['ar_title'],
				'message' => $this->payload['message'],
				'ar_message' => $this->payload['ar_message'],
				'route' => $this->payload['route'],
			],
			'created_at' => now(),
		]);
	}
	/**
	 * Get the array representation of the notification.
	 *
	 * @param  mixed  $notifiable
	 * @return array
	 */
	public function toArray($notifiable) {
		return [
			//
		];
	}
}
