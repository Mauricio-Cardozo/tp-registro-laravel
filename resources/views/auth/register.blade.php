<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registro</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f3f4f6; display: grid; place-items: center; min-height: 100vh; margin: 0; }
        form { background: #fff; padding: 2rem; border-radius: .5rem; box-shadow: 0 1px 3px rgba(0,0,0,.1); width: 320px; }
        label { display: block; margin-bottom: .25rem; font-size: .875rem; }
        input { width: 100%; box-sizing: border-box; padding: .5rem; margin-bottom: .25rem; border: 1px solid #d1d5db; border-radius: .25rem; }
        .error { color: #b91c1c; font-size: .8rem; margin: 0 0 .5rem; }
        .status { color: #15803d; font-size: .875rem; margin-bottom: 1rem; }
        button { width: 100%; padding: .6rem; background: #2563eb; color: #fff; border: 0; border-radius: .25rem; cursor: pointer; }
    </style>
</head>
<body>
    <form method="POST" action="{{ route('register.store') }}">
        @csrf

        @if (session('status'))
            <p class="status">{{ session('status') }}</p>
        @endif

        <label for="name">Nombre</label>
        <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus>
        @error('name') <p class="error">{{ $message }}</p> @enderror

        <label for="email">Correo</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required>
        @error('email') <p class="error">{{ $message }}</p> @enderror

        <label for="password">Contraseña</label>
        <input id="password" name="password" type="password" required>
        @error('password') <p class="error">{{ $message }}</p> @enderror

        <label for="password_confirmation">Confirmar contraseña</label>
        <input id="password_confirmation" name="password_confirmation" type="password" required>

        <p><button type="submit">Registrarse</button></p>
    </form>
</body>
</html>
