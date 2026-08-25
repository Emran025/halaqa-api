#!/usr/bin/env python3
"""Audit request/response coverage in an OpenAPI YAML contract."""

from __future__ import annotations

import argparse
from pathlib import Path
from typing import Any

import yaml

HTTP_METHODS = {"get", "post", "put", "patch", "delete", "options", "head", "trace"}


def ref_name(value: Any) -> str:
    if isinstance(value, dict) and "$ref" in value:
        return value["$ref"].split("/")[-1]
    return "inline"


def media_schema(content: Any) -> str:
    if not isinstance(content, dict):
        return "none"
    schemas = []
    for media_type, media in content.items():
        if isinstance(media, dict):
            schemas.append(f"{media_type}:{ref_name(media.get('schema'))}")
    return ", ".join(schemas) or "none"


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("contract", nargs="?", default="openapi.yaml")
    args = parser.parse_args()
    document = yaml.safe_load(Path(args.contract).read_text(encoding="utf-8"))
    schemas = document.get("components", {}).get("schemas", {})
    operations = []
    missing_request = []
    missing_response_schema = []

    for path, path_item in document.get("paths", {}).items():
        for method, operation in path_item.items():
            if method.lower() not in HTTP_METHODS:
                continue
            operation_id = operation.get("operationId", "<missing>")
            request_body = operation.get("requestBody")
            responses = operation.get("responses", {})
            response_details = []
            for status, response in responses.items():
                if isinstance(response, dict) and "$ref" in response:
                    response_details.append(f"{status}:shared:{ref_name(response)}")
                elif isinstance(response, dict):
                    response_details.append(f"{status}:{media_schema(response.get('content'))}")
                    if status not in {"204", "304"} and not response.get("content"):
                        missing_response_schema.append(f"{method.upper()} {path} ({operation_id}) -> {status}")
            request_details = "none"
            if request_body:
                if "$ref" in request_body:
                    request_details = f"shared:{ref_name(request_body)}"
                else:
                    request_details = media_schema(request_body.get("content"))
            elif method.lower() in {"post", "put", "patch"} and operation_id not in {
                "logout", "activateHalaqa", "deactivateHalaqa", "acceptSession", "leaveSession",
                "endSession", "reconnectSession", "cancelSession", "completeFollowUpItem", "markNotificationRead",
                "markAllNotificationsRead", "acceptRegistrationRequest",
            }:
                missing_request.append(f"{method.upper()} {path} ({operation_id})")
            operations.append((method.upper(), path, operation_id, request_details, "; ".join(response_details)))

    print(f"OpenAPI version: {document.get('openapi')}")
    print(f"Paths: {len(document.get('paths', {}))}; operations: {len(operations)}")
    print(f"Schemas: {len(schemas)}")
    print("\nOperation coverage:")
    for method, path, operation_id, request, responses in operations:
        print(f"- {method:6} {path:62} {operation_id:42} request={request} responses={responses}")
    print("\nMissing request bodies requiring review:")
    for item in missing_request:
        print(f"- {item}")
    print("\nResponses without explicit content requiring review:")
    for item in missing_response_schema:
        print(f"- {item}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
