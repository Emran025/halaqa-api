#!/usr/bin/env python3
"""Validate the Quran Halaqa OpenAPI contract."""

from __future__ import annotations

import argparse
import sys
from pathlib import Path

import yaml
from openapi_spec_validator import validate_spec


REQUIRED_PATHS = {
    "/auth/login",
    "/me",
    "/halaqas",
    "/registration-requests",
    "/quran/surahs",
    "/quran/pages/{pageNumber}",
    "/sessions",
    "/sessions/{sessionId}/tasks/{taskId}/mistakes",
    "/sessions/{sessionId}/report",
    "/students/{studentId}/progress",
    "/notifications",
}


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("contract", nargs="?", default="openapi.yaml")
    args = parser.parse_args()
    path = Path(args.contract)

    try:
        document = yaml.safe_load(path.read_text(encoding="utf-8"))
    except Exception as exc:  # noqa: BLE001
        print(f"FAIL: unable to parse YAML: {exc}")
        return 1

    try:
        validate_spec(document)
    except Exception as exc:  # noqa: BLE001
        print(f"FAIL: OpenAPI validation failed: {exc}")
        return 1

    paths = set(document.get("paths", {}))
    missing = REQUIRED_PATHS - paths
    if missing:
        print(f"FAIL: required paths are missing: {sorted(missing)}")
        return 1

    operation_ids = []
    for route, item in document.get("paths", {}).items():
        for method, operation in item.items():
            if method.lower() in {"get", "post", "put", "patch", "delete", "options", "head", "trace"}:
                operation_ids.append(operation.get("operationId"))

    if len(operation_ids) != len(set(operation_ids)):
        print("FAIL: duplicate operationId values detected")
        return 1
    if any(not operation_id for operation_id in operation_ids):
        print("FAIL: every HTTP operation must have operationId")
        return 1

    print(
        f"PASS: valid OpenAPI {document.get('openapi')} contract; "
        f"{len(paths)} paths and {len(operation_ids)} operations checked."
    )
    return 0


if __name__ == "__main__":
    sys.exit(main())
