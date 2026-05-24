<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactoRequest;
use App\Mail\ContactoWebMail;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class PaginaPublicaController extends Controller
{
    public function avisoLegal(): View
    {
        return view('informacion.aviso-legal');
    }

    public function privacidad(): View
    {
        return view('informacion.privacidad');
    }

    public function contacto(): View
    {
        return view('informacion.contacto');
    }

    public function enviarContacto(StoreContactoRequest $request): RedirectResponse
    {
        $datos = $request->validated();

        unset($datos['empresa']);

        try {
            Mail::to((string) config('services.contacto.email'))
                ->send(new ContactoWebMail($datos, $request->ip(), $request->userAgent()));
        } catch (\Throwable $exception) {
            Log::error('Error al enviar contacto web', [
                'nombre' => $datos['nombre'],
                'email' => $datos['email'],
                'asunto' => $datos['asunto'] ?? null,
                'error' => $exception->getMessage(),
            ]);

            return redirect()
                ->route('contacto')
                ->withInput()
                ->withErrors([
                    'contacto' => 'No hemos podido enviar tu mensaje ahora mismo. Intentalo de nuevo en unos minutos.',
                ]);
        }

        return redirect()
            ->route('contacto')
            ->with('status', 'Hemos recibido tu mensaje. Te responderemos lo antes posible.');
    }
}
