#!/usr/bin/env python3
"""Obtiene y normaliza productos de una categoría de Mercadona."""

from __future__ import annotations

import argparse
import json
import re
import sys
from dataclasses import asdict, dataclass
from pathlib import Path
from urllib.error import HTTPError, URLError
from urllib.request import Request, urlopen


@dataclass
class ProductoMercadona:
    external_id: str | None
    nombre: str | None
    marca: str | None
    formato: str | None
    precio: float | None
    precio_anterior: float | None
    precio_unidad: float | None
    unidad_ref: str | None
    tamano: str | None
    imagen: str | None
    url_producto: str | None
    disponible: bool
    codigo_postal: str | None
    warehouse_id: str | None
    categoria_id: str | None
    categoria_nombre: str | None
    fuente: str = "mercadona"


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Extrae productos de una categoría de Mercadona."
    )
    parser.add_argument(
        "--category-url",
        help="URL de la categoría, por ejemplo https://tienda.mercadona.es/categories/112",
    )
    parser.add_argument(
        "--json-file",
        help="Ruta a un JSON de respuesta guardado desde Network.",
    )
    parser.add_argument(
        "--postal-code",
        help="Código postal usado para contextualizar el catálogo.",
    )
    parser.add_argument(
        "--warehouse-id",
        help="Identificador de almacén devuelto/empleado por Mercadona, por ejemplo 4410.",
    )
    parser.add_argument(
        "--lang",
        default="es",
        help="Idioma del endpoint de Mercadona API.",
    )
    parser.add_argument(
        "--cookie",
        help="Cabecera Cookie completa si la API exige contexto de sesión.",
    )
    parser.add_argument(
        "--customer-device-id",
        help="Valor de x-customer-device-id visto en DevTools.",
    )
    parser.add_argument(
        "--x-version",
        help="Valor de x-version visto en DevTools.",
    )
    parser.add_argument(
        "--output",
        help="Ruta opcional para guardar el JSON normalizado.",
    )
    return parser.parse_args()


def build_api_url(category_url: str, warehouse_id: str, lang: str) -> str:
    category_id = extract_category_id(category_url)
    return (
        f"https://tienda.mercadona.es/api/categories/{category_id}/"
        f"?lang={lang}&wh={warehouse_id}"
    )


def extract_category_id(category_url: str) -> str:
    match = re.search(r"/categories/(\d+)", category_url)
    if not match:
        raise ValueError("No se pudo extraer el ID de categoría desde la URL.")
    return match.group(1)


def fetch_payload(args: argparse.Namespace) -> dict:
    if args.json_file:
        return json.loads(Path(args.json_file).read_text(encoding="utf-8"))

    if not args.category_url or not args.warehouse_id:
        raise ValueError(
            "Debes indicar --json-file o bien --category-url junto con --warehouse-id."
        )

    api_url = build_api_url(args.category_url, args.warehouse_id, args.lang)
    headers = {
        "accept": "*/*",
        "accept-language": "es-ES,es;q=0.5",
        "content-type": "application/json",
        "referer": args.category_url,
        "user-agent": (
            "Mozilla/5.0 (Windows NT 10.0; Win64; x64) "
            "AppleWebKit/537.36 (KHTML, like Gecko) "
            "Chrome/148.0.0.0 Safari/537.36"
        ),
    }

    if args.cookie:
        headers["cookie"] = args.cookie
    if args.customer_device_id:
        headers["x-customer-device-id"] = args.customer_device_id
    if args.x_version:
        headers["x-version"] = args.x_version

    request = Request(api_url, headers=headers, method="GET")

    try:
        with urlopen(request, timeout=30) as response:
            return json.loads(response.read().decode("utf-8"))
    except HTTPError as error:
        body = error.read().decode("utf-8", errors="ignore")
        raise RuntimeError(
            f"Mercadona API devolvió HTTP {error.code}: {body[:500]}"
        ) from error
    except URLError as error:
        raise RuntimeError(f"No se pudo conectar con Mercadona API: {error}") from error


def parse_decimal(value) -> float | None:
    if value is None or value == "":
        return None
    if isinstance(value, (int, float)):
        return float(value)

    match = re.search(r"(\d+[\.,]?\d*)", str(value).strip())
    if not match:
        return None

    return float(match.group(1).replace(",", "."))


def extract_brand(nombre: str | None) -> str | None:
    if not nombre:
        return None

    marcas = ["Hacendado", "Deliplus", "Bosque Verde", "Compy"]
    for marca in marcas:
        if marca.lower() in nombre.lower():
            return marca
    return None


def build_size_text(price_instructions: dict, packaging: str | None) -> str | None:
    unit_size = parse_decimal(price_instructions.get("unit_size"))
    size_format = price_instructions.get("size_format")

    parts = []
    if packaging:
        parts.append(packaging)
    if unit_size is not None:
        parts.append(format_number(unit_size))
    if size_format:
        parts.append(str(size_format))

    return " ".join(parts) if parts else None


def format_number(value: float) -> str:
    if value.is_integer():
        return str(int(value))
    return str(value)


def is_product_available(product: dict) -> bool:
    return product.get("published", False) and not product.get("unavailable_from")


def iter_category_products(payload: dict):
    for category in payload.get("categories", []):
        category_id = stringify(category.get("id"))
        category_name = category.get("name")
        for product in category.get("products", []):
            yield category_id, category_name, product


def normalize_products(payload: dict, args: argparse.Namespace) -> list[ProductoMercadona]:
    items: list[ProductoMercadona] = []

    for category_id, category_name, product in iter_category_products(payload):
        instructions = product.get("price_instructions", {})
        items.append(
            ProductoMercadona(
                external_id=stringify(product.get("id")),
                nombre=product.get("display_name") or product.get("name"),
                marca=product.get("brand")
                or extract_brand(product.get("display_name") or product.get("name")),
                formato=product.get("packaging"),
                precio=parse_decimal(instructions.get("unit_price")),
                precio_anterior=parse_decimal(instructions.get("previous_unit_price")),
                precio_unidad=parse_decimal(instructions.get("reference_price")),
                unidad_ref=stringify(instructions.get("reference_format")),
                tamano=build_size_text(instructions, product.get("packaging")),
                imagen=product.get("thumbnail") or product.get("photo"),
                url_producto=product.get("share_url"),
                disponible=is_product_available(product),
                codigo_postal=args.postal_code,
                warehouse_id=args.warehouse_id,
                categoria_id=category_id,
                categoria_nombre=category_name,
            )
        )

    return items


def stringify(value) -> str | None:
    if value is None or value == "":
        return None
    return str(value)


def build_output(payload: dict, productos: list[ProductoMercadona], args: argparse.Namespace) -> dict:
    return {
        "supermercado": {
            "cadena": "Mercadona",
            "codigo_postal": args.postal_code,
            "warehouse_id": args.warehouse_id,
        },
        "categoria": {
            "id": stringify(payload.get("id"))
            or (extract_category_id(args.category_url) if args.category_url else None),
            "nombre": payload.get("name"),
        },
        "total_productos": len(productos),
        "productos": [asdict(producto) for producto in productos],
    }


def emit_output(content: dict, output_path: str | None) -> None:
    text = json.dumps(content, ensure_ascii=False, indent=2)

    if output_path:
        Path(output_path).write_text(text, encoding="utf-8")
        return

    sys.stdout.write(text)
    sys.stdout.write("\n")


def main() -> int:
    args = parse_args()
    payload = fetch_payload(args)
    productos = normalize_products(payload, args)
    emit_output(build_output(payload, productos, args), args.output)
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
