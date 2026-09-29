<?php

namespace Tests\Feature;

use App\Mail\WelcomeUserMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ponytail: la app valida con email:rfc,dns, que hace una consulta DNS real.
     * example.com no tiene registro MX y sería rechazado, por eso el dominio
     * de ejemplo es gmail.com. Si los tests corren sin internet, ver README.
     */
    private array $validos = [
        'name' => 'Ana',
        'email' => 'ana@gmail.com',
        'password' => 'secreto123',
        'password_confirmation' => 'secreto123',
    ];

    public function test_el_formulario_de_registro_se_muestra(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertSee('name')
            ->assertSee('email')
            ->assertSee('password')
            ->assertSee('password_confirmation');
    }

    public function test_los_campos_obligatorios_se_validan(): void
    {
        $this->post('/register', [])
            ->assertSessionHasErrors(['name', 'email', 'password']);
    }

    public function test_rechaza_un_email_con_formato_invalido(): void
    {
        $this->post('/register', array_merge($this->validos, ['email' => 'esto-no-es-un-email']))
            ->assertSessionHasErrors('email');
    }

    public function test_rechaza_un_email_ya_registrado(): void
    {
        User::factory()->create(['email' => 'ana@gmail.com']);

        $this->post('/register', $this->validos)
            ->assertSessionHasErrors('email');
    }

    public function test_rechaza_una_contrasena_corta_o_sin_confirmar(): void
    {
        $this->post('/register', [
            'name' => 'Ana',
            'email' => 'ana@gmail.com',
            'password' => 'corta',
            'password_confirmation' => 'corta',
        ])->assertSessionHasErrors('password');

        $this->post('/register', [
            'name' => 'Ana',
            'email' => 'ana@gmail.com',
            'password' => 'secreto123',
            'password_confirmation' => 'otra-cosa-distinta',
        ])->assertSessionHasErrors('password');
    }

    public function test_con_datos_validos_crea_el_usuario_y_encola_el_correo(): void
    {
        Mail::fake();

        $this->post('/register', $this->validos)
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'name' => 'Ana',
            'email' => 'ana@gmail.com',
        ]);

        $user = User::where('email', 'ana@gmail.com')->first();
        $this->assertNotSame('secreto123', $user->password, 'la contraseña se guardó en texto plano');
        $this->assertTrue(Hash::check('secreto123', $user->password), 'la contraseña no se guardó hasheada');

        // El Mailable implementa ShouldQueue, así que el correo queda en la cola.
        Mail::assertQueued(WelcomeUserMail::class, function (WelcomeUserMail $mail) {
            return $mail->hasTo('ana@gmail.com') && $mail->name === 'Ana';
        });
    }

    public function test_el_mailable_encolado_no_trae_la_contrasena(): void
    {
        Mail::fake();

        $this->post('/register', $this->validos)->assertSessionHasNoErrors();

        Mail::assertQueued(WelcomeUserMail::class, function (WelcomeUserMail $mail) {
            // Con ShouldQueue el mailable se serializa entero en la tabla jobs.
            // Esto es lo que se guarda ahí, así que la contraseña no puede estar.
            $serializado = serialize($mail);

            $this->assertArrayNotHasKey('password', get_object_vars($mail));
            $this->assertStringNotContainsString('secreto123', $serializado);

            return true;
        });
    }

    public function test_con_datos_invalidos_no_se_envia_nada(): void
    {
        Mail::fake();

        $this->post('/register', [])
            ->assertSessionHasErrors(['name', 'email', 'password']);

        Mail::assertNothingSent();
        Mail::assertNothingQueued();
    }
}
