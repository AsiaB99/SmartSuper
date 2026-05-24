<?php

namespace Tests\Feature;

use App\Mail\ContactoWebMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PaginaPublicaControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_are_accessible(): void
    {
        $this->get(route('aviso-legal'))
            ->assertOk()
            ->assertSeeText('Aviso legal');

        $this->get(route('privacidad'))
            ->assertOk()
            ->assertSeeText('Politica de privacidad');

        $this->get(route('contacto'))
            ->assertOk()
            ->assertSeeText('Contacto');
    }

    public function test_contact_form_requires_required_fields(): void
    {
        $this->from(route('contacto'))
            ->post(route('contacto.enviar'), [])
            ->assertRedirect(route('contacto'))
            ->assertSessionHasErrors(['nombre', 'email', 'mensaje']);
    }

    public function test_contact_form_submits_successfully_with_valid_data(): void
    {
        Mail::fake();
        Config::set('services.contacto.email', 'smartsuper.dev@gmail.com');

        $this->from(route('contacto'))
            ->post(route('contacto.enviar'), [
                'nombre' => 'Cliente SmartSuper',
                'email' => 'cliente@example.com',
                'asunto' => 'Sugerencia',
                'mensaje' => 'Hola, me gustaria sugerir una mejora para la comparativa de precios.',
            ])
            ->assertRedirect(route('contacto'))
            ->assertSessionHas('status');

        Mail::assertSent(ContactoWebMail::class, function (ContactoWebMail $mail): bool {
            return $mail->hasTo('smartsuper.dev@gmail.com')
                && $mail->datos['nombre'] === 'Cliente SmartSuper'
                && $mail->datos['email'] === 'cliente@example.com';
        });
    }

    public function test_contact_form_rejects_honeypot_field(): void
    {
        $this->from(route('contacto'))
            ->post(route('contacto.enviar'), [
                'nombre' => 'Cliente SmartSuper',
                'email' => 'cliente@example.com',
                'asunto' => 'Sugerencia',
                'mensaje' => 'Hola, me gustaria sugerir una mejora para la comparativa de precios.',
                'empresa' => 'Spam Bot',
            ])
            ->assertRedirect(route('contacto'))
            ->assertSessionHasErrors(['empresa']);
    }
}
