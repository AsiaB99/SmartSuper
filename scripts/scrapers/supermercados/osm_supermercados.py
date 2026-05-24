#!/usr/bin/env python3
"""Exporta supermercados de OpenStreetMap a JSON normalizado para SmartSuper."""

from __future__ import annotations

import argparse
import json
import sys
import urllib.parse
import urllib.request
from typing import Any


OVERPASS_URL = "https://overpass-api.de/api/interpreter"


def build_query(area_name: str) -> str:
    return f"""
[out:json][timeout:180];
area["name"="{area_name}"]["boundary"="administrative"]->.searchArea;
(
  node["shop"="supermarket"](area.searchArea);
  way["shop"="supermarket"](area.searchArea);
  relation["shop"="supermarket"](area.searchArea);
);
out center tags;
"""


def fetch_overpass(query: str, endpoint: str) -> dict[str, Any]:
    data = urllib.parse.urlencode({"data": query}).encode("utf-8")
    request = urllib.request.Request(
        endpoint,
        data=data,
        headers={
            "User-Agent": "SmartSuper/1.0 ubicaciones-supermercados",
            "Content-Type": "application/x-www-form-urlencoded",
        },
        method="POST",
    )

    with urllib.request.urlopen(request, timeout=240) as response:
        return json.loads(response.read().decode("utf-8"))


def normalize_element(element: dict[str, Any]) -> dict[str, Any] | None:
    tags = element.get("tags") or {}
    lat = element.get("lat") or (element.get("center") or {}).get("lat")
    lon = element.get("lon") or (element.get("center") or {}).get("lon")

    if lat is None or lon is None:
        return None

    address_parts = [
        tags.get("addr:street"),
        tags.get("addr:housenumber"),
        tags.get("addr:postcode"),
        tags.get("addr:city"),
    ]
    address = ", ".join(str(part) for part in address_parts if part)

    return {
        "external_id": f"{element.get('type')}:{element.get('id')}",
        "osm_type": element.get("type"),
        "nombre": tags.get("name") or tags.get("brand") or "Supermercado OSM",
        "marca": tags.get("brand"),
        "operador": tags.get("operator"),
        "direccion": address or None,
        "latitud": lat,
        "longitud": lon,
        "website": tags.get("website"),
        "opening_hours": tags.get("opening_hours"),
        "addr:street": tags.get("addr:street"),
        "addr:housenumber": tags.get("addr:housenumber"),
        "addr:postcode": tags.get("addr:postcode"),
        "addr:city": tags.get("addr:city"),
    }


def main() -> int:
    parser = argparse.ArgumentParser(description="Exporta supermercados OSM para SmartSuper.")
    parser.add_argument("--area", default="España", help="Área administrativa OSM. Por defecto: España.")
    parser.add_argument("--endpoint", default=OVERPASS_URL, help="Endpoint Overpass.")
    parser.add_argument("--output", required=True, help="Ruta del JSON normalizado de salida.")
    args = parser.parse_args()

    payload = fetch_overpass(build_query(args.area), args.endpoint)
    supermarkets = [
        normalized
        for element in payload.get("elements", [])
        if (normalized := normalize_element(element)) is not None
    ]

    output = {
        "fuente": "osm",
        "area": args.area,
        "supermercados": supermarkets,
    }

    with open(args.output, "w", encoding="utf-8") as handle:
        json.dump(output, handle, ensure_ascii=False, indent=2)
        handle.write("\n")

    print(f"Exportados {len(supermarkets)} supermercados a {args.output}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
