#!/usr/bin/env python3
"""Audit OpenAPI request/response schemas for explicit, machine-readable fields."""

from __future__ import annotations

import json
import sys
from pathlib import Path

import yaml


ROOT = Path(__file__).resolve().parents[1]
SPEC_PATH = ROOT / "openapi.yaml"


def resolve(schema: dict, schemas: dict) -> dict:
    if "$ref" not in schema:
        return schema
    return schemas.get(schema["$ref"].rsplit("/", 1)[-1], {})


def collect_data_properties(value: object, location: str, found: list[str]) -> None:
    if isinstance(value, dict):
        properties = value.get("properties")
        if isinstance(properties, dict) and "data" in properties:
            found.append(f"{location}.properties.data")
        for key, child in value.items():
            collect_data_properties(child, f"{location}.{key}", found)
    elif isinstance(value, list):
        for index, child in enumerate(value):
            collect_data_properties(child, f"{location}[{index}]", found)


def main() -> int:
    spec = yaml.safe_load(SPEC_PATH.read_text(encoding="utf-8"))
    schemas = spec.get("components", {}).get("schemas", {})
    report = {"operations": 0, "request_bodies": [], "responses": [], "data_properties": []}

    for path, path_item in spec.get("paths", {}).items():
        for method, operation in path_item.items():
            if method.lower() not in {"get", "post", "put", "patch", "delete", "head", "options"}:
                continue
            report["operations"] += 1
            operation_id = operation.get("operationId", f"{method.upper()} {path}")
            request_body = operation.get("requestBody")
            if request_body:
                for media_type, media in request_body.get("content", {}).items():
                    schema_ref = media.get("schema", {})
                    schema = resolve(schema_ref, schemas)
                    report["request_bodies"].append({
                        "operation_id": operation_id,
                        "media_type": media_type,
                        "schema": schema_ref,
                        "properties": sorted(schema.get("properties", {}).keys()),
                        "required": sorted(schema.get("required", [])),
                    })

            for status, response in operation.get("responses", {}).items():
                for media_type, media in response.get("content", {}).items():
                    schema_ref = media.get("schema", {})
                    schema = resolve(schema_ref, schemas)
                    report["responses"].append({
                        "operation_id": operation_id,
                        "status": status,
                        "media_type": media_type,
                        "schema": schema_ref,
                        "properties": sorted(schema.get("properties", {}).keys()),
                        "required": sorted(schema.get("required", [])),
                    })

    data_properties: list[str] = []
    collect_data_properties(spec.get("components", {}).get("schemas", {}), "components.schemas", data_properties)
    report["data_properties"] = data_properties
    print(json.dumps(report, ensure_ascii=False, indent=2))
    if data_properties:
        print(f"FAIL: generic data properties remain: {data_properties}", file=sys.stderr)
        return 1
    print(f"PASS: explicit contract audit completed; {report['operations']} operations, no data properties.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
