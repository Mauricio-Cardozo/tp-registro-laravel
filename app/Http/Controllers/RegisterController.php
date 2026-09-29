<?php

namespace App\Http\Controllers;

use App\Mail\WelcomeUserMail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:255'],
            // ponytail: dns hace una consulta DNS real; en tests sin internet usar solo 'email:rfc'
            'email' => ['required', 'email:rfc,dns', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'name.min' => 'El nombre debe tener al menos 3 caracteres.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'El correo no tiene un formato válido.',
            'email.max' => 'El correo no puede superar los 255 caracteres.',
            'email.unique' => 'Ya existe una cuenta con ese correo.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación no coincide con la contraseña.',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        try {
            // Solo el nombre: la contraseña jamás entra al mailable.
            Mail::to($user->email)->send(new WelcomeUserMail($user->name));
        } catch (\Throwable $e) {
            // Con ShouldQueue el worker manda el mail: los errores de SMTP ya no
            // llegan acá. Esto solo cubre que falle el encolado (jobs caído).
            Log::error('No se pudo encolar el correo de bienvenida: ' . $e->getMessage());
        }

        return redirect()->route('register.create')
            ->with('status', '¡Registro exitoso, ' . $user->name . '!');
    }
}
