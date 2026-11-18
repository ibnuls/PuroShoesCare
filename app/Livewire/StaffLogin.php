<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class StaffLogin extends Component
{
    public string $username = '';

    public string $password = '';

    public function login()
    {
        $this->validate(['username' => 'required|string|max:40', 'password' => 'required|string|max:100']);
        $key = 'login:'.hash('sha256', request()->ip().strtolower($this->username));
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('username', 'Terlalu banyak percobaan. Coba lagi dalam satu menit.');

            return;
        }
        if (! Auth::attempt(['username' => strtolower(trim($this->username)), 'password' => $this->password, 'active' => true])) {
            RateLimiter::hit($key, 60);
            $this->reset('password');
            $this->addError('username', 'Username atau password tidak sesuai.');

            return;
        }
        RateLimiter::clear($key);
        session()->regenerate();
        $this->reset('password');
        $this->redirectRoute('staff.dashboard', navigate: true);
    }

    public function render()
    {
        return view('livewire.staff-login')->title('Masuk Staf · PURO');
    }
}
