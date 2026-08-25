#!/usr/bin/env python3
"""Validate high-value cross-contract invariants between OpenAPI and MySQL."""

from __future__ import annotations

import sys
from pathlib import Path

import yaml


ROOT = Path(__file__).resolve().parents[1]


def schema(spec: dict, name: str) -> dict:
    return spec["components"]["schemas"][name]


def main() -> int:
    spec = yaml.safe_load((ROOT / "openapi.yaml").read_text(encoding="utf-8"))
    sql = (ROOT / "database_schema.sql").read_text(encoding="utf-8")
    alignment = (ROOT / "DATABASE_API_ALIGNMENT.md").read_text(encoding="utf-8")

    paths = spec.get("paths", {})
    if "/realtime/channels/authorize" not in paths or "/broadcasting/auth" in paths:
        print("FAIL: realtime authorization path is not Laravel-internal")
        return 1

    expected_enums = {
        "MistakeType": {"none", "memory", "grammar", "pronunciation", "timing"},
        "TaskType": {"memorization", "review", "recitation"},
        "Frequency": {"daily", "onceAWeek", "twiceAWeek", "thriceAWeek"},
        "TrackingStatus": {"draft", "in_progress", "completed", "cancelled"},
    }
    for name, expected in expected_enums.items():
        actual = set(schema(spec, name).get("enum", []))
        if actual != expected:
            print(f"FAIL: {name} enum mismatch; actual={sorted(actual)}, expected={sorted(expected)}")
            return 1

    realtime = schema(spec, "RealtimeSession")
    required = set(realtime.get("required", []))
    properties = realtime.get("properties", {})
    for field in {"direct_p2p_only", "signaling_transport", "ice_candidate_policy", "media_transport"}:
        if field not in required and field not in properties:
            print(f"FAIL: RealtimeSession missing P2P field: {field}")
            return 1
    if "ice_servers" in properties or "IceServer" in spec["components"]["schemas"]:
        print("FAIL: OpenAPI still exposes ICE server configuration")
        return 1

    required_tables = [
        "registration_request_availability", "registration_request_availability_slots",
        "follow_up_plans", "follow_up_items", "quran_ayah_words", "mistakes",
        "session_reports", "notifications", "idempotency_keys", "audit_events",
    ]
    for table in required_tables:
        if f"CREATE TABLE {table}" not in sql:
            print(f"FAIL: alignment-critical table missing from SQL: {table}")
            return 1
        if table not in alignment:
            print(f"FAIL: table missing from alignment matrix: {table}")
            return 1

    for marker in ["P2P", "Laravel", "mistake_type_id", "direct_p2p_only"]:
        if marker not in alignment:
            print(f"FAIL: alignment matrix missing marker: {marker}")
            return 1

    print("PASS: OpenAPI and MySQL high-value invariants are aligned.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
