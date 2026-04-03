<?php

namespace Tests\Unit;

use App\Models\Lista;
use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    public function test_lista_model_has_expected_base_configuration(): void
    {
        $lista = new Lista();

        $this->assertSame('listas', $lista->getTable());
        $this->assertFalse($lista->timestamps);
        $this->assertSame(
            ['nombre_lista', 'estado', 'fecha_creacion'],
            $lista->getFillable()
        );
    }
}
