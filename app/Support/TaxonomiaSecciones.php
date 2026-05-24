<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;

final class TaxonomiaSecciones
{
    public const SECCION_OTROS = 'Otros';

    public static function resolverParaProducto(?string $nombreProducto, ?string $seccionActual, array $categoriasExternas = []): string
    {
        $nombreProductoNormalizado = self::normalizar($nombreProducto);
        $seccionActualNormalizada = self::normalizar($seccionActual);
        $categoriasExternasNormalizadas = array_values(array_filter(array_map(
            static fn (?string $categoria): string => self::normalizar($categoria),
            $categoriasExternas
        )));

        $seccion = self::resolverPorTexto($nombreProductoNormalizado);

        if ($seccion !== null) {
            return $seccion;
        }

        foreach ($categoriasExternasNormalizadas as $categoria) {
            $seccion = self::resolverPorTexto($categoria);

            if ($seccion !== null) {
                return $seccion;
            }
        }

        $seccion = self::resolverPorTexto($seccionActualNormalizada);

        return $seccion ?? self::SECCION_OTROS;
    }

    public static function resolverParaCategoriaExterna(?string $categoria): string
    {
        return self::resolverPorTexto(self::normalizar($categoria)) ?? self::SECCION_OTROS;
    }

    /**
     * @return list<string>
     */
    public static function nombresCanonicos(): array
    {
        return [
            'Aceites, vinagres y aliños',
            'Afeitado y depilación',
            'Aguas',
            'Arroces, pastas y legumbres',
            'Aves',
            'Bebé',
            'Cafés e infusiones',
            'Carnes',
            'Cervezas',
            'Charcutería',
            'Chocolates y dulces',
            'Congelados',
            'Conservas, caldos y cremas',
            'Cuidado capilar',
            'Cuidado corporal',
            'Cuidado facial',
            'Desayuno y cereales',
            'Detergente y suavizante',
            'Frutas y verduras',
            'Galletas y bollería envasada',
            'Higiene bucal',
            'Higiene femenina',
            'Higiene y cuidado personal',
            'Huevos',
            'Lácteos',
            'Licores',
            'Limpieza del hogar',
            'Maquillaje',
            'Mascotas',
            'Otros',
            'Panadería y bollería',
            'Papel e higiene',
            'Parafarmacia',
            'Pescados y mariscos',
            'Perfumería',
            'Platos preparados',
            'Quesos',
            'Refrescos y zumos',
            'Sal, especias y condimentos',
            'Salsas y condimentos',
            'Snacks y aperitivos',
            'Vinos y cavas',
            'Yogures y postres',
            'Accesorios de belleza',
        ];
    }

    private static function resolverPorTexto(string $texto): ?string
    {
        if ($texto === '') {
            return null;
        }

        return match (true) {
            self::contieneAlguno($texto, ['gatos', 'gato', 'perros', 'perro', 'pienso', 'mascotas', 'mascota', 'comida humeda', 'alimentacion humeda', 'alimentacion seca', 'conejos y roedores', 'roedores']) => 'Mascotas',
            self::contieneAlguno($texto, ['bebe', 'puericultura', 'potito', 'potitos', 'infantil']) => 'Bebé',
            self::contieneAlguno($texto, ['compresas', 'protegeslips', 'tampones', 'higiene femenina']) => 'Higiene femenina',
            self::contieneAlguno($texto, ['higiene bucal', 'dentifricos', 'pasta de dientes', 'pasta dental', 'cepillos de dientes', 'cepillo de dientes', 'colutorio', 'enjuague bucal']) => 'Higiene bucal',
            self::contieneAlguno($texto, ['afeitado', 'afeitar', 'depilacion', 'depilar', 'maquinillas', 'maquinilla', 'cuchillas', 'masculinas', 'femeninas']) => 'Afeitado y depilación',
            self::contieneAlguno($texto, ['champu', 'cabello', 'capilar', 'coloracion', 'acondicionador', 'acondicionadores', 'mascarillas cabello', 'mascarilla cabello', 'gel y cera', 'laca']) => 'Cuidado capilar',
            self::contieneAlguno($texto, ['cuidado e higiene facial', 'cuidado facial', 'higiene facial', 'limpieza facial', 'desmaquilladores', 'desmaquillante', 'facial']) => 'Cuidado facial',
            self::contieneAlguno($texto, ['labiales', 'labial', 'ojos', 'rostro', 'maquillaje', 'pintalabios', 'pintalabios mate', 'pintalabios cremoso', 'unas', 'uñas']) => 'Maquillaje',
            self::contieneAlguno($texto, ['gel de bano', 'gel de baño', 'body lociones', 'body lociones', 'body-lociones', 'cuidado corporal', 'hidratacion', 'desodorante', 'solares', 'locion corporal', 'roll on']) => 'Cuidado corporal',
            self::contieneAlguno($texto, ['perfume', 'colonia', 'perfumeria', 'fragancia']) => 'Perfumería',
            self::contieneAlguno($texto, ['peines', 'peines y accesorios', 'accesorios de bano', 'cepillos', 'electricos y automaticos']) => 'Accesorios de belleza',
            self::contieneAlguno($texto, ['fitoterapia', 'botiquin', 'nutricion deportiva']) => 'Parafarmacia',
            self::contieneAlguno($texto, ['detergente', 'suavizante', 'quitamanchas']) => 'Detergente y suavizante',
            self::contieneAlguno($texto, ['limpieza hogar', 'limpiacristales', 'multiusos', 'limpiadores', 'limpieza calzado', 'limpiador', 'limpieza']) => 'Limpieza del hogar',
            self::contieneAlguno($texto, ['papel higienico', 'papel de cocina', 'servilletas', 'toallitas']) => 'Papel e higiene',
            self::contieneAlguno($texto, ['sushi', 'platos preparados', 'plato preparado', 'mexicano', 'asiatico', 'pizzas refrigeradas', 'pizza refrigerada']) => 'Platos preparados',
            self::contieneAlguno($texto, ['congelados', 'congelado', 'congelada', 'pizza congelada', 'pescado congelado', 'marisco congelado']) => 'Congelados',
            self::contieneAlguno($texto, ['pescado', 'marisco', 'atun', 'bonito', 'sardinas', 'surimi', 'salazones']) => 'Pescados y mariscos',
            self::contieneAlguno($texto, ['charcuteria', 'jamon', 'embutido', 'chorizo', 'salchichon', 'salami', 'pate', 'fiambre', 'york', 'cocidos de pavo', 'en lonchas']) => 'Charcutería',
            self::contieneAlguno($texto, ['aves', 'ave', 'pollo', 'pavo']) => 'Aves',
            self::contieneAlguno($texto, ['carne', 'vacuno', 'cerdo', 'hamburguesas', 'hamburguesa', 'picadas', 'picada', 'salchichas', 'salchicha', 'empanados', 'elaborados', 'preparados de carne', 'preparado de carne', 'lomo', 'milanesas']) => 'Carnes',
            self::contieneAlguno($texto, ['quesos', 'queso', 'fundidos y porciones', 'quesos para untar']) => 'Quesos',
            self::contieneAlguno($texto, ['yogures y postres', 'yogur', 'yogures', 'postres', 'natillas', 'flan', 'cuajada']) => 'Yogures y postres',
            self::contieneAlguno($texto, ['lacteos', 'lacteos', 'leche', 'mantequilla', 'nata', 'batidos', 'batido', 'bebidas vegetales', 'bebida vegetal']) => 'Lácteos',
            self::contieneAlguno($texto, ['huevos', 'huevo']) => 'Huevos',
            self::contieneAlguno($texto, ['pan y bolleria del dia', 'panaderia', 'bolleria', 'pasteleria', 'pan blanco', 'pan integral', 'panes especiales', 'pan']) => 'Panadería y bollería',
            self::contieneAlguno($texto, ['galletas', 'galleta', 'bolleria envasada', 'magdalenas', 'magdalena', 'pastelitos', 'bizcochos', 'bizcocho', 'tartas', 'tarta', 'tortitas']) => 'Galletas y bollería envasada',
            self::contieneAlguno($texto, ['desayuno', 'cereales', 'tostadas', 'minibiscotes', 'cacao soluble']) => 'Desayuno y cereales',
            self::contieneAlguno($texto, ['dulce', 'bombones', 'chocolatinas', 'chocolate', 'caramelos', 'chicles', 'gominolas', 'cacao']) => 'Chocolates y dulces',
            self::contieneAlguno($texto, ['snacks', 'aperitivos', 'patatas fritas', 'frutos secos', 'aceitunas', 'cocktail', 'maiz tostado']) => 'Snacks y aperitivos',
            self::contieneAlguno($texto, ['verduras y hortalizas', 'verdura', 'verduras', 'hortalizas', 'frutas', 'fruta', 'ensaladas', 'ensalada', 'tomate', 'naranja', 'vegetales']) => 'Frutas y verduras',
            self::contieneAlguno($texto, ['arroz', 'pasta', 'pastas', 'macarrones', 'espaguetis', 'tallarines', 'legumbres', 'pasta fresca']) => 'Arroces, pastas y legumbres',
            self::contieneAlguno($texto, ['conservas', 'conserva', 'caldo', 'caldos', 'sopa', 'sopas', 'cremas y pure', 'pure', 'tomate frito']) => 'Conservas, caldos y cremas',
            self::contieneAlguno($texto, ['salsas', 'salsa', 'mayonesa', 'ketchup', 'mostaza', 'alioli']) => 'Salsas y condimentos',
            self::contieneAlguno($texto, ['sal', 'especias', 'especia', 'sazonadores', 'sazonador', 'bicarbonato']) => 'Sal, especias y condimentos',
            self::contieneAlguno($texto, ['aceite', 'aceites', 'vinagre', 'vinagres', 'alino', 'aderezo', 'alinos']) => 'Aceites, vinagres y aliños',
            self::contieneAlguno($texto, ['cafe', 'cafes', 'te', 'infusiones', 'infusion', 'capsulas', 'nespresso']) => 'Cafés e infusiones',
            self::contieneAlguno($texto, ['cerveza', 'cervezas']) => 'Cervezas',
            self::contieneAlguno($texto, ['tinto', 'blanco do', 'vino', 'vinos', 'cavas', 'sidras', 'rioja', 'ribera del duero']) => 'Vinos y cavas',
            self::contieneAlguno($texto, ['licores', 'licor', 'ginebra', 'whisky', 'ron', 'vodka']) => 'Licores',
            self::contieneAlguno($texto, ['agua', 'aguas']) => 'Aguas',
            self::contieneAlguno($texto, ['refrescos', 'refresco', 'zumos', 'zumo', 'nectares', 'nectar', 'cola', 'energeticas', 'energetica']) => 'Refrescos y zumos',
            self::contieneAlguno($texto, ['higiene', 'cuidado personal', 'spray']) => 'Higiene y cuidado personal',
            default => null,
        };
    }

    /**
     * @param  list<string>  $keywords
     */
    private static function contieneAlguno(string $texto, array $keywords): bool
    {
        $textoConBordes = ' '.$texto.' ';

        foreach ($keywords as $keyword) {
            $keywordNormalizado = self::normalizar($keyword);

            if ($keywordNormalizado !== '' && str_contains($textoConBordes, ' '.$keywordNormalizado.' ')) {
                return true;
            }
        }

        return false;
    }

    private static function normalizar(?string $texto): string
    {
        return Str::of((string) $texto)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->trim()
            ->replaceMatches('/\s+/', ' ')
            ->value();
    }
}
