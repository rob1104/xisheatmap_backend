<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\TarjetaDescuentoEnviada;
use App\Models\TarjetaDescuento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class TarjetaController extends Controller
{
    public function index(Request $request)
    {
        $buscar = $request->input('search');

        $tarjetas = TarjetaDescuento::with(['ineRecord', 'brigadista'])
            ->when($buscar, function ($query, $buscar) {
                $query->where('curp', 'like', "%{$buscar}%")
                    ->orWhere('email_envio', 'like', "%{$buscar}%")
                    ->orWhereHas('ineRecord', function($q) use ($buscar) {
                        $q->where('nombre', 'like', "%{$buscar}%")
                            ->orWhere('apellido_paterno', 'like', "%{$buscar}%");
                    });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Tarjetas/Index', [
            'tarjetas' => $tarjetas,
            'filters' => $request->only(['search'])
        ]);
    }

    public function updateEmail(Request $request, TarjetaDescuento $tarjeta)
    {
        $request->validate([
            'email_envio' => 'required|email'
        ]);
        $tarjeta->update(['email_envio' => $request->email_envio]);
        return back()->with('success', "Correo actualizado correctamente.");
    }

    public function reenviarCorreo(TarjetaDescuento $tarjeta)
    {
        if (!$tarjeta->image_path) {
            return back()->with('error', 'La imagen de la tarjeta no existe. Debe generarse primero.');
        }

        try {
            Mail::to($tarjeta->email_envio)->send(new TarjetaDescuentoEnviada($tarjeta));

            $tarjeta->update(['enviado_por_correo' => true]);

            return back()->with('success', "Tarjeta reenviada con éxito a {$tarjeta->email_envio}");
        } catch (\Exception $e) {
            return back()->with('error', "Fallo al enviar: " . $e->getMessage());
        }
    }
}
