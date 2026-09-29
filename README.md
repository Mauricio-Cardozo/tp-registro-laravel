# TP Registro de usuarios + correo de bienvenida

Aplicación en Laravel 13 con SQLite. Al registrarte se crea el usuario y se
encola un correo de bienvenida; el envío lo hace un worker aparte, así que si el
SMTP de Brevo se cae, el registro no se pierde.

## Qué hace

- Formulario de registro en `/register` (Blade, validaciones en español).
- Guarda el usuario con la contraseña hasheada (bcrypt).
- Encola un `WelcomeUserMail` que lleva solo el nombre del usuario.
- El worker envía el correo por SMTP de Brevo, con 3 reintentos.
- 10 tests: 8 del registro y 2 de ejemplo que trae el skeleton.

## Requisitos

- PHP 8.3 o superior, con `pdo_sqlite`, `mbstring`, `openssl` y `dom`.
- Composer 2.
- Node no hace falta: las vistas usan CSS inline, no hay build de Vite.

## Instalación

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
```

## Configurar el .env

`.env.example` es el archivo que va al repositorio, así que **no tiene claves
reales**. `.env` es la copia local y no se sube, está en el `.gitignore`.

Para que el correo salga de verdad hay que completar:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME=el-login-que-te-muestra-brevo
MAIL_PASSWORD=xsmtpsib-TU-SMTP-KEY-DE-64-CARACTERES
MAIL_FROM_ADDRESS="no-reply@tu-remitente-verificado.com"
```

Tres detalles de Brevo que conviene tener claros:

1. **`MAIL_PASSWORD` es la SMTP key, no la API key.** El panel muestra las dos y
   son distintas. La que va acá es la que empieza con `xsmtpsib-`.
2. **`MAIL_USERNAME` no es el mail de la cuenta.** Es el "SMTP login" que aparece
   arriba en el panel, y suele ser una dirección distinta.
3. **`MAIL_FROM_ADDRESS` tiene que ser un remitente verificado.** Si se pone una
   dirección sin verificar, el servidor la rechaza y el correo no llega.

Sin esas claves el proyecto igual funciona: los tests pasan y el registro
funciona, lo que falla es el envío.

## Correr la aplicación

El envío necesita dos terminales.

```bash
# terminal 1: el worker, que es el que manda los correos
php artisan queue:work

# terminal 2: el servidor web
php artisan serve
```

Entrar a http://localhost:8000/register, registrarse y mirar la terminal del
worker: aparece `App\Mail\WelcomeUserMail .. DONE` cuando sale el correo. Al
registrarse el trabajo aparece en la tabla `jobs` y el worker lo toma.

Si las claves de Brevo no están puestas, el worker falla, reintenta 3 veces y el
trabajo termina en `failed_jobs`. El usuario queda registrado igual.

## Tests

```bash
php artisan test
php artisan test --testdox
```

Usan `RefreshDatabase` con una base SQLite en memoria (`:memory:`, configurado en
`phpunit.xml`), así que no tocan `database/database.sqlite`. Y usan
`Mail::fake()`, así que no mandan ningún correo real.

Como el Mailable implementa `ShouldQueue`, las aserciones son
`Mail::assertQueued()` y no `Mail::assertSent()`: `assertSent()` mira los correos
enviados en el momento, y acá nunca se envía nada en el request, todo va a la
cola.

### La validación de email y el DNS

El controller valida con `email:rfc,dns`, y la parte `dns` hace una consulta DNS
real para ver si el dominio tiene registro MX. Por eso los tests usan `gmail.com`
y no `example.com`, que no tiene MX y sería rechazado.

Si los tests se corren sin internet, los casos de "datos válidos" fallan con "El
correo no tiene un formato válido" aunque los datos estén bien. Es lo único que
necesita conexión a la red. La alternativa, si hace falta trabajar offline, es
cambiar la regla a `email:rfc` en el controller y sacar la comprobación DNS.

## Mirar la cola

```bash
php artisan queue:failed          # qué correos no salieron
php artisan queue:retry <id>      # reintentar uno
php artisan queue:flush           # borrar los fallidos
```

## Diferencias con la guía del TP

La guía propone algunas cosas distintas. No están mal, pero conviene saber por
qué se hicieron así:

| Guía | Acá | Por qué |
|---|---|---|
| `WelcomeUserMail($userData)` con el array de validación | `WelcomeUserMail(string $name)` | Con `ShouldQueue` el mailable se serializa entero en la tabla `jobs`. Si recibiera el array, la contraseña quedaría guardada en texto plano en la base. Hay un test que lo comprueba. |
| `build()` | `envelope()` y `content()` | Son los métodos de la API nueva de Mailables. Devuelven el asunto y la vista separados. `build()` sigue funcionando. |
| Tailwind con `@vite` | CSS inline en la vista | Los clientes de correo borran las hojas de estilo externas, así que el mail tiene que ser inline igual. Para el formulario, usar CSS propio evita depender de `npm run build` y evita el error "Vite manifest not found" en los tests. |
| Mostrar el resultado en la misma vista | `redirect` con mensaje flash | Es el flujo estándar de POST/redirect/GET: si el usuario recarga no vuelve a enviar el formulario. |

## Detalles que conviene no romper

**La contraseña no puede viajar al mailable.** Es el punto del punto 1 de la
tabla de arriba, y hay un test (`test_el_mailable_encolado_no_trae_la_contrasena`)
que falla si alguien se lo pasa.

**`example.com` no pasa la validación de email.** No tiene registro MX. Cualquier
dirección que parezca de ejemplo probablemente va a fallar.

## Estructura

```
app/Http/Controllers/RegisterController.php   create(), store()
app/Mail/WelcomeUserMail.php                  ShouldQueue, $tries = 3
resources/views/auth/register.blade.php       el formulario
resources/views/emails/welcome.blade.php      el mail, con estilos inline
tests/Feature/RegisterTest.php                los casos del registro
routes/web.php                                GET y POST /register
```

La tabla `users` y las de `jobs` / `failed_jobs` ya venían creadas en el skeleton
de Laravel, no hay migraciones propias.
