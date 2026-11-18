<?php

namespace App\Livewire;

use App\Services\Assistant;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

class Chatbot extends Component
{
    public string $question = '';

    public array $history = [];

    public ?array $response = null;

    public function send(Assistant $assistant)
    {
        $this->validate(['question' => 'required|string|min:1|max:1200', 'history' => 'array|max:8', 'history.*.role' => 'required|in:user,assistant', 'history.*.text' => 'required|string|max:2500']);
        $key = 'chat:'.hash('sha256', request()->ip());
        if (RateLimiter::tooManyAttempts($key, 15)) {
            $this->response = $assistant->fallback($this->question, 'RATE_LIMIT');
            $this->dispatch('chat-answer', question: $this->question, response: $this->response);
            $this->question = '';

            return;
        }
        RateLimiter::hit($key, 60);
        $this->response = $assistant->ask($this->question, $this->history);
        $this->dispatch('chat-answer', question: $this->question, response: $this->response);
        $this->question = '';
    }

    public function render()
    {
        return view('livewire.chatbot');
    }
}
