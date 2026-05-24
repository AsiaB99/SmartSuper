#!/usr/bin/env python3
"""Obtiene y normaliza productos de una categoría de Consum."""

from __future__ import annotations

import argparse
import json
import math
import sys
from dataclasses import asdict, dataclass
from pathlib import Path
from urllib.error import HTTPError, URLError
from urllib.parse import urlencode
from urllib.request import Request, urlopen


@dataclass
class ProductoConsum:
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
    fuente: str = "consum"


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Extrae productos de una categoría de Consum."
    )
    parser.add_argument("--category-id", help="ID de categoría, por ejemplo 2811.")
    parser.add_argument("--json-file", help="Ruta a un JSON guardado desde Network.")
    parser.add_argument("--referer-url", help="URL de referencia de la categoría en Consum.")
    parser.add_argument("--postal-code", help="Código postal usado para contextualizar el catálogo.")
    parser.add_argument("--warehouse-id", help="Identificador interno si luego quieres asociarlo a una fuente concreta.")
    parser.add_argument("--page", type=int, default=1, help="Página inicial.")
    parser.add_argument("--limit", type=int, default=20, help="Límite por página.")
    parser.add_argument("--order-by-id", type=int, default=5, help="Orden usado por Consum.")
    parser.add_argument("--x-tol-zone", default="0", help="Cabecera X-TOL-ZONE.")
    parser.add_argument("--x-tol-channel", default="1", help="Cabecera X-TOL-CHANNEL.")
    parser.add_argument("--x-tol-locale", default="es", help="Cabecera X-TOL-LOCALE.")
    parser.add_argument("--x-tol-app", default="shop-front", help="Cabecera X-TOL-APP.")
    parser.add_argument("--x-tol-shipping-zone", default="0D", help="Cabecera X-TOL-SHIPPING-ZONE.")
    parser.add_argument("--x-tol-currency", default="EUR", help="Cabecera X-TOL-CURRENCY.")
    parser.add_argument("--fetch-all-pages", action="store_true", help="Recorre todas las páginas disponibles de la categoría.")
    parser.add_argument("--output", help="Ruta opcional para guardar el JSON normalizado.")
    return parser.parse_args()


def build_api_url(args: argparse.Namespace, page: int) -> str:
    query = urlencode(
        {
            "page": page,
            "limit": args.limit,
            "offset": (page - 1) * args.limit,
            "orderById": args.order_by_id,
            "showProducts": "true",
            "originProduct": "undefined",
            "showRecommendations": "false",
            "categories": args.category_id,
        }
    )
    return f"https://tienda.consum.es/api/rest/V1.0/catalog/product?{query}"


def fetch_page(args: argparse.Namespace, page: int) -> dict:
    if page == args.page and args.json_file:
        return json.loads(Path(args.json_file).read_text(encoding="utf-8"))

    if not args.category_id:
        raise ValueError("Debes indicar --category-id para consultar la API de Consum.")

    referer = args.referer_url or (
        f"https://tienda.consum.es/es/c/despensa/{args.category_id}?orderById={args.order_by_id}&showProducts=true&page={page}"
    )

    headers = {
        "accept": "application/json, text/plain, */*",
        "referer": referer,
        "user-agent": (
            "Mozilla/5.0 (Windows NT 10.0; Win64; x64) "
            "AppleWebKit/537.36 (KHTML, like Gecko) "
            "Chrome/148.0.0.0 Safari/537.36"
        ),
        "x-tol-zone": args.x_tol_zone,
        "x-tol-channel": args.x_tol_channel,
        "x-tol-locale": args.x_tol_locale,
        "x-tol-app": args.x_tol_app,
        "x-tol-shipping-zone": args.x_tol_shipping_zone,
        "x-tol-currency": args.x_tol_currency,
    }

    request = Request(build_api_url(args, page), headers=headers, method="GET")

    try:
        with urlopen(request, timeout=30) as response:
            return json.loads(response.read().decode("utf-8"))
    except HTTPError as error:
        body = error.read().decode("utf-8", errors="ignore")
        raise RuntimeError(
            f"Consum API devolvió HTTP {error.code}: {body[:500]}"
        ) from error
    except URLError as error:
        raise RuntimeError(f"No se pudo conectar con Consum API: {error}") from error


def parse_decimal(value) -> float | None:
    if value is None or value == "":
        return None
    if isinstance(value, (int, float)):
        return float(value)
    return float(str(value).replace(",", "."))


def get_price_entry(price_data: dict, entry_id: str) -> dict | None:
    for entry in price_data.get("prices", []):
        if entry.get("id") == entry_id:
            return entry
    return None


def get_best_current_price(price_data: dict) -> float | None:
    offer = get_price_entry(price_data, "OFFER_PRICE")
    if offer is not None:
        return parse_decimal(offer.get("value", {}).get("centAmount"))

    regular = get_price_entry(price_data, "PRICE")
    if regular is not None:
        return parse_decimal(regular.get("value", {}).get("centAmount"))

    return None


def get_previous_price(price_data: dict) -> float | None:
    regular = get_price_entry(price_data, "PRICE")
    offer = get_price_entry(price_data, "OFFER_PRICE")

    if regular is None or offer is None:
        return None

    return parse_decimal(regular.get("value", {}).get("centAmount"))


def get_unit_price(price_data: dict) -> float | None:
    offer = get_price_entry(price_data, "OFFER_PRICE")
    if offer is not None:
        return parse_decimal(offer.get("value", {}).get("centUnitAmount"))

    regular = get_price_entry(price_data, "PRICE")
    if regular is not None:
        return parse_decimal(regular.get("value", {}).get("centUnitAmount"))

    return None


def build_format_and_size(description: str | None) -> tuple[str | None, str | None]:
    if not description:
        return None, None

    parts = description.rsplit(" ", 2)
    if len(parts) >= 2:
        size = " ".join(parts[-2:])
        return description, size
    return description, None


def is_available(product: dict) -> bool:
    return product.get("productData", {}).get("availability") == "1"


def get_primary_category_name(product: dict) -> str | None:
    categories = product.get("categories", [])
    for category in categories:
        if category.get("type") == 0:
            return category.get("name")
    return categories[0].get("name") if categories else None


def normalize_products(payload: dict, args: argparse.Namespace) -> list[ProductoConsum]:
    items: list[ProductoConsum] = []

    for product in payload.get("products", []):
        product_data = product.get("productData", {})
        price_data = product.get("priceData", {})
        description = product_data.get("description")
        formato, tamano = build_format_and_size(description)

        items.append(
            ProductoConsum(
                external_id=str(product.get("id")) if product.get("id") is not None else None,
                nombre=product_data.get("name"),
                marca=product_data.get("brand", {}).get("name"),
                formato=formato,
                precio=get_best_current_price(price_data),
                precio_anterior=get_previous_price(price_data),
                precio_unidad=get_unit_price(price_data),
                unidad_ref=price_data.get("unitPriceUnitType"),
                tamano=tamano,
                imagen=product_data.get("imageURL"),
                url_producto=product_data.get("url"),
                disponible=is_available(product),
                codigo_postal=args.postal_code,
                warehouse_id=args.warehouse_id,
                categoria_id=str(args.category_id) if args.category_id else None,
                categoria_nombre=get_primary_category_name(product),
            )
        )

    return items


def build_output(
    category_id: str | None,
    total_count: int,
    total_pages: int,
    productos: list[ProductoConsum],
) -> dict:
    return {
        "supermercado": {
            "cadena": "Consum",
        },
        "categoria": {
            "id": category_id,
        },
        "total_productos_categoria": total_count,
        "total_paginas": total_pages,
        "total_productos_extraidos": len(productos),
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

    first_page = fetch_page(args, args.page)
    total_count = int(first_page.get("totalCount", 0))
    total_pages = max(1, math.ceil(total_count / args.limit)) if args.fetch_all_pages else 1

    all_products = normalize_products(first_page, args)

    if args.fetch_all_pages:
        for page in range(args.page + 1, total_pages + 1):
            payload = fetch_page(args, page)
            all_products.extend(normalize_products(payload, args))

    emit_output(
        build_output(args.category_id, total_count, total_pages, all_products),
        args.output,
    )
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
