<?php

namespace Tests\Unit;

use App\Support\TaxonomiaSecciones;
use PHPUnit\Framework\TestCase;

class TaxonomiaSeccionesTest extends TestCase
{
    public function test_clasifica_producto_por_nombre_antes_que_por_seccion_actual(): void
    {
        $this->assertSame(
            'Aceites, vinagres y aliños',
            TaxonomiaSecciones::resolverParaProducto(
                'Aceite de oliva virgen extra',
                'Sushi del Día',
            )
        );
    }

    public function test_clasifica_producto_por_categoria_externa_cuando_el_nombre_no_ayuda(): void
    {
        $this->assertSame(
            'Aceites, vinagres y aliños',
            TaxonomiaSecciones::resolverParaProducto(
                'Selección Chef Hacendado',
                'Tinto otras DO',
                ['Aceite de oliva', 'Vinagre y otros aderezos']
            )
        );
    }

    public function test_reduce_seccion_especifica_de_mascotas_a_categoria_generica(): void
    {
        $this->assertSame(
            'Mascotas',
            TaxonomiaSecciones::resolverParaCategoriaExterna('Gatos')
        );
    }
}
