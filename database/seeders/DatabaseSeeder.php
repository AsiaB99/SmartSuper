<?php

namespace Database\Seeders;

use App\Models\Seccion;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use JsonException;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Crear usuario de prueba
        $testUser = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'rol' => 'cliente',
        ]);

        // Crear admin
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'rol' => 'admin',
        ]);

        foreach ($this->resolverSeccionesCatalogo() as $seccion) {
            Seccion::query()->firstOrCreate(['nombre_seccion' => $seccion]);
        }

        // El catálogo y los precios reales se incorporan desde importaciones
        // y validación manual, no desde datos de demostración.
    }

    /**
     * @return list<string>
     */
    private function resolverSeccionesCatalogo(): array
    {
        $rutaCatalogo = (string) env('SECCIONES_CATALOGO_PATH', storage_path('app/scraping'));

        if (! File::isDirectory($rutaCatalogo)) {
            return $this->seccionesFallback();
        }

        $secciones = collect(File::allFiles($rutaCatalogo))
            ->filter(fn (\SplFileInfo $archivo): bool => str_ends_with($archivo->getFilename(), '-normalizado.json'))
            ->map(fn (\SplFileInfo $archivo): ?string => $this->extraerSeccionDesdeArchivo($archivo->getPathname()))
            ->filter(fn (?string $seccion): bool => $seccion !== null)
            ->unique()
            ->sort()
            ->values()
            ->all();

        return $secciones !== [] ? $secciones : $this->seccionesFallback();
    }

    private function extraerSeccionDesdeArchivo(string $rutaArchivo): ?string
    {
        try {
            $payload = json_decode(File::get($rutaArchivo), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (! is_array($payload)) {
            return null;
        }

        $nombre = $payload['categoria']['nombre'] ?? null;

        if (! is_string($nombre)) {
            return null;
        }

        $nombre = trim($nombre);

        if ($nombre === '') {
            return null;
        }

        return mb_substr($nombre, 0, 50);
    }

    /**
     * @return list<string>
     */
    private function seccionesFallback(): array
    {
        return [
            'Aceite, vinagre y sal',
            'Agua',
            'Aperitivos',
            'Arroz y pasta',
            'Bebidas',
            'Bebé',
            'Carnes',
            'Congelados',
            'Droguería y limpieza',
            'Fruta',
            'Huevos, leche y mantequilla',
            'Pan de molde y hamburguesa',
            'Pescado',
            'Postres y yogures',
            'Verdura',
        ];
    }
}
