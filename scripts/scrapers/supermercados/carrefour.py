#!/usr/bin/env python3
"""Obtiene y normaliza productos de una categoría de Carrefour."""

from __future__ import annotations

import argparse
import json
import math
import re
import sys
from dataclasses import asdict, dataclass
from pathlib import Path
from http.cookiejar import CookieJar
from urllib.error import HTTPError, URLError
from urllib.request import Request, urlopen

try:
    import browser_cookie3
except ImportError:  # pragma: no cover - dependencia opcional en entorno local
    browser_cookie3 = None


@dataclass
class ProductoCarrefour:
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
    fuente: str = "carrefour"


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Extrae productos de una categoría de Carrefour."
    )
    parser.add_argument("--category-id", help="ID de categoría, por ejemplo cat20018.")
    parser.add_argument(
        "--api-path",
        help=(
            "Ruta relativa del endpoint PLP, por ejemplo "
            "'supermercado/frescos/carne/cat20018/c'."
        ),
    )
    parser.add_argument("--json-file", help="Ruta a un JSON guardado desde Network.")
    parser.add_argument("--postal-code", help="Código postal del contexto de entrega.")
    parser.add_argument("--sale-point", help="Identificador del punto de venta, por ejemplo 005290.")
    parser.add_argument("--delivery-type", default="A_DOMICILIO", help="Valor de delivery_type.")
    parser.add_argument("--cookie", help="Cabecera Cookie completa si la petición la necesita.")
    parser.add_argument(
        "--cookie-file",
        help="Ruta a un archivo de texto con la cabecera Cookie completa.",
    )
    parser.add_argument("--session-id", help="Valor de session_id si aplica.")
    parser.add_argument(
        "--canary-group",
        default="marketpay_food,one_cart_cellar,one_cart_non_food",
        help="Cabecera c4-canary-group.",
    )
    parser.add_argument(
        "--referer-url",
        help="URL de referencia de la categoría en Carrefour.",
    )
    parser.add_argument(
        "--use-browser-cookies",
        action="store_true",
        help="Lee automáticamente cookies de Carrefour desde el navegador local.",
    )
    parser.add_argument(
        "--browser",
        default="brave",
        choices=["brave", "chrome", "chromium", "edge"],
        help="Navegador del que leer cookies si usas --use-browser-cookies.",
    )
    parser.add_argument("--offset", type=int, default=0, help="Offset inicial.")
    parser.add_argument("--page-size", type=int, default=24, help="Tamaño estimado de página.")
    parser.add_argument("--fetch-all-pages", action="store_true", help="Recorre todas las páginas disponibles de la categoría.")
    parser.add_argument(
        "--debug-response",
        action="store_true",
        help="Muestra información de depuración sobre la respuesta cruda de Carrefour.",
    )
    parser.add_argument(
        "--raw-output",
        help="Ruta opcional para guardar la respuesta cruda de Carrefour antes de normalizar.",
    )
    parser.add_argument("--output", help="Ruta opcional para guardar el JSON normalizado.")
    return parser.parse_args()


def build_api_url(api_path: str, offset: int) -> str:
    return (
        "https://www.carrefour.es/cloud-api/plp-food-papi/v1/"
        f"{api_path.lstrip('/')}"
        f"?offset={offset}&platform=Desktop&_maxreflevel=3&preview=false"
    )


def build_default_api_path(category_id: str | None) -> str:
    if not category_id:
        raise ValueError("Debes indicar --api-path o --category-id.")

    return f"supermercado/frescos/carne/{category_id}/c"


def resolve_api_path(args: argparse.Namespace) -> str:
    return args.api_path or build_default_api_path(args.category_id)


def fetch_payload(args: argparse.Namespace, offset: int | None = None) -> dict:
    if args.json_file and (offset is None or offset == args.offset):
        return json.loads(Path(args.json_file).read_text(encoding="utf-8"))

    api_path = resolve_api_path(args)

    referer = args.referer_url or f"https://www.carrefour.es/{api_path.lstrip('/')}"

    if offset:
        referer = f"{referer}?offset={max(0, offset - args.page_size)}"

    headers = {
        "accept": "application/json, text/plain, */*",
        "accept-language": "es-ES,es;q=0.6",
        "c4-canary-group": args.canary_group,
        "cookie_banner_version": "3",
        "delivery_type": args.delivery_type,
        "display_cookie_banner": "true",
        "from_app": "false",
        "referer": referer,
        "sale_point": args.sale_point or "",
        "postal_code": args.postal_code or "",
        "user-agent": (
            "Mozilla/5.0 (Windows NT 10.0; Win64; x64) "
            "AppleWebKit/537.36 (KHTML, like Gecko) "
            "Chrome/148.0.0.0 Safari/537.36"
        ),
    }

    cookie_header = read_cookie_file(args.cookie_file) or args.cookie or load_browser_cookie_header(args)
    if cookie_header:
        headers["cookie"] = cookie_header
    if args.session_id:
        headers["session_id"] = args.session_id

    request = Request(build_api_url(api_path, offset or args.offset), headers=headers, method="GET")

    try:
        with urlopen(request, timeout=30) as response:
            return json.loads(response.read().decode("utf-8"))
    except HTTPError as error:
        body = error.read().decode("utf-8", errors="ignore")
        raise RuntimeError(
            f"Carrefour API devolvió HTTP {error.code}: {body[:500]}"
        ) from error
    except URLError as error:
        raise RuntimeError(f"No se pudo conectar con Carrefour API: {error}") from error


def parse_euro_amount(value: str | None) -> float | None:
    if not value:
        return None

    match = re.search(r"(\d+[\.,]\d+)", value)
    if not match:
        return None

    return float(match.group(1).replace(",", "."))


def load_browser_cookie_header(args: argparse.Namespace) -> str | None:
    if not args.use_browser_cookies:
        return None

    if browser_cookie3 is None:
        raise RuntimeError(
            "Falta la dependencia opcional browser-cookie3. "
            "Instálala con: pip install browser-cookie3"
        )

    loader_map = {
        "brave": browser_cookie3.brave,
        "chrome": browser_cookie3.chrome,
        "chromium": browser_cookie3.chromium,
        "edge": browser_cookie3.edge,
    }

    jar_loader = loader_map[args.browser]
    jar: CookieJar = jar_loader(domain_name="carrefour.es")
    cookies = [f"{cookie.name}={cookie.value}" for cookie in jar]

    if not cookies:
        raise RuntimeError(
            "No se encontraron cookies de Carrefour en el navegador. "
            "Abre Carrefour en ese navegador, selecciona tu tienda y vuelve a ejecutar."
        )

    return "; ".join(cookies)


def read_cookie_file(path: str | None) -> str | None:
    if not path:
        return None

    cookie_text = Path(path).read_text(encoding="utf-8").strip()
    return cookie_text or None


def build_previous_price(item: dict) -> float | None:
    badge = item.get("badge_map", {})
    promotions = badge.get("promotions", [])

    if not promotions:
        return None

    return None


def build_size_text(item: dict) -> str | None:
    name = item.get("name")
    if not name:
        return None

    match = re.search(r"(\d+(?:[\.,]\d+)?\s*(?:g|gr|kg|ml|l|cl|ud|unidades))", name, re.IGNORECASE)
    if not match:
        return None

    return match.group(1)


def build_format_text(item: dict) -> str | None:
    return item.get("name")


def normalize_products(payload: dict, args: argparse.Namespace) -> list[ProductoCarrefour]:
    items: list[ProductoCarrefour] = []

    for item in extract_items(payload):
        items.append(
            ProductoCarrefour(
                external_id=item.get("sms") or item.get("sku_id") or item.get("product_id"),
                nombre=item.get("name"),
                marca=item.get("brand"),
                formato=build_format_text(item),
                precio=parse_euro_amount(item.get("price")),
                precio_anterior=build_previous_price(item),
                precio_unidad=parse_euro_amount(item.get("price_per_unit")),
                unidad_ref=item.get("measure_unit"),
                tamano=build_size_text(item),
                imagen=(item.get("images") or {}).get("desktop")
                or (item.get("images") or {}).get("mobile"),
                url_producto=build_product_url(item.get("url")),
                disponible=(item.get("units_in_stock") or 0) > 0,
                codigo_postal=args.postal_code,
                warehouse_id=args.sale_point,
                categoria_id=args.category_id,
                categoria_nombre=extract_category_name(payload),
            )
        )

    return items


def extract_items(payload: dict) -> list[dict]:
    root_items = payload.get("items")
    if isinstance(root_items, list):
        return root_items

    results = payload.get("results")
    if isinstance(results, dict) and isinstance(results.get("items"), list):
        return results["items"]

    return []


def extract_category_name(payload: dict) -> str | None:
    category_name = payload.get("category_name")
    if category_name:
        return category_name

    category = payload.get("category")
    if isinstance(category, dict):
        for key in ("name", "display_name", "title"):
            name = category.get(key)
            if isinstance(name, str) and name:
                return name

    return None


def build_product_url(path: str | None) -> str | None:
    if not path:
        return None

    if path.startswith("http://") or path.startswith("https://"):
        return path

    return f"https://www.carrefour.es{path}"


def build_output(payload: dict, productos: list[ProductoCarrefour], args: argparse.Namespace) -> dict:
    return {
        "supermercado": {
            "cadena": "Carrefour",
            "codigo_postal": args.postal_code,
            "warehouse_id": args.sale_point,
            "delivery_type": args.delivery_type,
        },
        "categoria": {
            "id": args.category_id,
            "nombre": extract_category_name(payload),
        },
        "total_productos_categoria": extract_total_results(payload),
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


def emit_raw_output(payload: dict, output_path: str | None) -> None:
    if not output_path:
        return

    Path(output_path).write_text(
        json.dumps(payload, ensure_ascii=False, indent=2),
        encoding="utf-8",
    )


def debug_payload(payload: dict) -> None:
    if not isinstance(payload, dict):
        print(f"[debug] Tipo de respuesta inesperado: {type(payload).__name__}", file=sys.stderr)
        return

    keys = ", ".join(sorted(payload.keys()))
    print(f"[debug] Claves raíz: {keys}", file=sys.stderr)

    items = extract_items(payload)
    if isinstance(items, list):
        print(f"[debug] Número de items: {len(items)}", file=sys.stderr)

    results = payload.get("results")
    if isinstance(results, dict):
        print(f"[debug] results: {json.dumps(results, ensure_ascii=False)}", file=sys.stderr)

    if "message" in payload:
        print(f"[debug] message: {payload['message']}", file=sys.stderr)


def main() -> int:
    args = parse_args()
    payload = fetch_payload(args, args.offset)
    emit_raw_output(payload, args.raw_output)

    if args.debug_response:
        debug_payload(payload)

    productos = normalize_products(payload, args)

    if args.fetch_all_pages:
        total_results = extract_total_results(payload)
        total_pages = max(1, math.ceil(total_results / args.page_size)) if total_results else 1

        for page_index in range(1, total_pages):
            next_offset = args.offset + (page_index * args.page_size)
            page_payload = fetch_payload(args, next_offset)
            productos.extend(normalize_products(page_payload, args))

    emit_output(build_output(payload, productos, args), args.output)
    return 0


def extract_total_results(payload: dict) -> int:
    results = payload.get("results")
    if isinstance(results, dict):
        if isinstance(results.get("numResults"), int):
            return results["numResults"]
        if isinstance(results.get("total_results"), int):
            return results["total_results"]

        pagination = results.get("pagination")
        if isinstance(pagination, dict) and isinstance(pagination.get("total_results"), int):
            return pagination["total_results"]

    pagination = payload.get("pagination")
    if isinstance(pagination, dict) and isinstance(pagination.get("total"), int):
        return pagination["total"]

    return len(extract_items(payload))


if __name__ == "__main__":
    raise SystemExit(main())
