"""Deep cross-document consistency audit for the Quran Halaqa contracts."""
from __future__ import annotations

import re
import sys
from pathlib import Path
from typing import Any

import yaml

ROOT = Path(__file__).resolve().parents[1]


def read(relative: str) -> str:
    return (ROOT / relative).read_text(encoding="utf-8")


def schema_enum(doc: dict[str, Any], name: str) -> set[str]:
    value = doc.get("components", {}).get("schemas", {}).get(name, {})
    return set(value.get("enum", []))


def sql_check_enum(sql: str, table: str, column: str) -> set[str]:
    pattern = rf"CREATE TABLE {re.escape(table)}\s*\((.*?)(?:\) ENGINE=|\);)"
    match = re.search(pattern, sql, re.S)
    if not match:
        return set()
    checks = re.findall(
        rf"CHECK\s*\(\s*{re.escape(column)}\s+IN\s*\(([^)]*)\)\)",
        match.group(1),
        re.I,
    )
    if not checks:
        return set()
    return set(re.findall(r"'([^']+)'", checks[0]))


def normalized_path(path: str) -> str:
    return re.sub(r"\{[^}]+\}", "{id}", path.rstrip("/")) or "/"


def main() -> int:
    openapi_text = read("openapi.yaml")
    openapi = yaml.safe_load(openapi_text)
    alignment = read("DATABASE_API_ALIGNMENT.md")
    catalog = read("API_FUNCTIONS_CATALOG.md")
    db_contract = read("DATABASE_SCHEMA_CONTRACT.md")
    sql = read("database_schema.sql")
    audit_doc = read("OPENAPI_COMPLETENESS_AUDIT.md")
    realtime = read("REALTIME_CONTRACT.md")
    policy = read("PROJECT_ARCHITECTURE_POLICY.md")

    errors: list[str] = []
    warnings: list[str] = []

    paths = openapi.get("paths", {})
    openapi_routes = {normalized_path(path) for path in paths}
    documented_route_text = alignment + "\n" + catalog
    for stale in (
        "POST /registrations",
        "/me/teacher-profile/documents",
        "GET/PATCH /me/student-availability",
    ):
        if stale in documented_route_text:
            errors.append(f"stale endpoint wording remains in alignment/catalog: {stale}")

    for route in ("/me/teacher-documents", "/registration-requests", "/students/{id}/availability"):
        if normalized_path(route) not in openapi_routes:
            errors.append(f"expected OpenAPI route missing: {route}")

    # Every endpoint mentioned in the catalog/alignment should resolve to an OpenAPI path,
    # while ignoring prose examples and placeholder query strings.
    route_mentions = set(re.findall(r"(?<![A-Za-z0-9])(/[A-Za-z][A-Za-z0-9_./{}-]*)", documented_route_text))
    ignored_prefixes = ("/api/v1", "/auth/password/*", "/notifications", "/quran")
    for mention in sorted(route_mentions):
        if mention.endswith(":") or mention in ignored_prefixes:
            continue
        if "?" in mention:
            mention = mention.split("?", 1)[0]
        candidate = normalized_path(mention)
        if candidate.startswith("/api/v1"):
            continue
        if candidate not in openapi_routes and not candidate.startswith("/auth/password"):
            warnings.append(f"documented route not matched exactly to OpenAPI: {mention}")

    # High-value enum parity between SQL checks and OpenAPI.
    enum_pairs = {
        "UserStatus": ("users", "status"),
        "HalaqaStatus": ("halaqas", "status"),
        "MembershipStatus": ("halaqa_memberships", "status"),
        "RegistrationState": ("registration_requests", "state"),
        "SessionState": ("live_sessions", "state"),
        "ReportState": ("session_reports", "state"),
        "TaskType": ("tracking_types", "code"),
        "TrackingUnit": ("tracking_units", "code"),
        "MistakeSource": ("mistakes", "source_role"),
        "AttendanceType": ("daily_trackings", "attendance_type"),
        "FollowUpItemState": ("follow_up_items", "state"),
    }
    for schema_name, (table, column) in enum_pairs.items():
        api_values = schema_enum(openapi, schema_name)
        sql_values = sql_check_enum(sql, table, column)
        if sql_values and api_values and api_values != sql_values:
            errors.append(
                f"enum mismatch {schema_name}/{table}.{column}: "
                f"OpenAPI={sorted(api_values)} SQL={sorted(sql_values)}"
            )

    task_schema = openapi.get("components", {}).get("schemas", {}).get("SessionTask", {})
    task_api = set(task_schema.get("properties", {}).get("state", {}).get("enum", []))
    task_sql = sql_check_enum(sql, "session_tasks", "state")
    if task_api and task_sql and task_api != task_sql:
        errors.append(
            f"session task state mismatch: OpenAPI={sorted(task_api)} SQL={sorted(task_sql)}"
        )

    follow_up_item = openapi.get("components", {}).get("schemas", {}).get("FollowUpItem", {})
    follow_up_required = set(follow_up_item.get("required", []))
    required_follow_up_fields = {"plan_id", "plan_detail_id", "student_id", "scheduled_for", "timezone", "state", "rescheduled_from_id"}
    if not required_follow_up_fields.issubset(follow_up_required):
        errors.append("FollowUpItem is missing plan/reschedule fields required by follow_up_items")

    # Notification vocabulary must stay explicit and never regress to a generic data field.
    if "payload JSON NOT NULL" not in sql:
        errors.append("notifications.payload is missing from SQL")
    notification_lines = [
        line for line in db_contract.splitlines()
        if line.strip().startswith("`notifications`")
    ]
    if not notification_lines or "`payload`" not in notification_lines[0]:
        errors.append("DATABASE_SCHEMA_CONTRACT does not describe notifications.payload")
    if any("`data`" in line and "لا يستخدم العقد حقلًا عامًا" not in line for line in notification_lines):
        errors.append("DATABASE_SCHEMA_CONTRACT still describes notifications.data")
    if "NotificationPayload" not in openapi_text or "payload:" not in openapi_text:
        errors.append("NotificationPayload/payload is not explicit in OpenAPI")

    # Settled decisions must not remain listed as unresolved domain decisions.
    settled_phrases = (
        "هل يملك المعلم حلقة واحدة أم عدة حلقات؟",
        "هل الجلسة فردية فقط أم يمكن أن تكون جماعية؟",
        "هل الطالب يستطيع الانضمام إلى أكثر من حلقة؟",
    )
    for phrase in settled_phrases:
        if phrase in audit_doc:
            errors.append(f"stale unresolved decision remains in completeness audit: {phrase}")

    # Field-level invariants for the highest-risk cross-document areas.
    quran_page = openapi.get("components", {}).get("schemas", {}).get("QuranPage", {})
    if "edition_id" not in quran_page.get("required", []):
        errors.append("QuranPage must expose required edition_id")
    for schema_name in ("Surah", "Ayah"):
        schema_value = openapi.get("components", {}).get("schemas", {}).get(schema_name, {})
        if "edition_id" not in schema_value.get("required", []):
            errors.append(f"{schema_name} must expose required edition_id")
    mushaf = openapi.get("components", {}).get("schemas", {}).get("MushafState", {})
    if not {"edition_id", "page_number", "version"}.issubset(set(mushaf.get("required", []))):
        errors.append("MushafState is missing edition_id/page_number/version requirements")
    for column in ("session_id", "edition_id", "page_number", "updated_by_user_id", "version"):
        if not re.search(rf"CREATE TABLE session_mushaf_states.*?^\s+{column}\b", sql, re.S | re.M):
            errors.append(f"session_mushaf_states is missing required column: {column}")
    halaqa = openapi.get("components", {}).get("schemas", {}).get("Halaqa", {})
    if not {"gender", "country", "residence", "timezone"}.issubset(set(halaqa.get("required", []))):
        errors.append("Halaqa response is missing required persisted scope fields")
    if "description VARCHAR(1000)" not in sql:
        errors.append("halaqas.description is missing from SQL")
    if "suspended" in str(openapi.get("components", {}).get("schemas", {}).get("MembershipStatus", {})):
        errors.append("MembershipStatus must not use suspended")
    if "pending" in str(openapi.get("components", {}).get("schemas", {}).get("UserStatus", {})):
        errors.append("UserStatus must not use pending")

    # Policy/realtime/database agreement on direct P2P. Host ICE belongs to the
    # signaling contract; the database contract must explicitly prohibit ICE storage.
    for required in ("direct_connection_unavailable", "P2P", "Laravel"):
        if required not in policy or required not in realtime or required not in db_contract:
            errors.append(f"P2P/Laravel marker missing from one of the authoritative documents: {required}")
    if "Host ICE" not in policy or "Host ICE" not in realtime:
        errors.append("Host ICE policy is missing from the policy or realtime contract")

    # Names presented as OpenAPI schemas in the alignment matrix must exist.
    declared_schemas = set(openapi.get("components", {}).get("schemas", {}))
    alignment_tokens = set(re.findall(r"`([A-Z][A-Za-z0-9]+)`", alignment))
    schema_like = {
        token for token in alignment_tokens
        if token.endswith(("Input", "Response", "CollectionResponse", "Profile", "Preferences", "Detail", "Type", "State", "Task", "Session", "Report", "Evaluation", "Mistake", "Note", "Ayah", "Surah", "Page", "Edition", "Membership", "Tracking", "Notification"))
        and not token.endswith(("Resource", "Service", "Policy", "Query"))
    }
    undeclared = sorted(schema_like - declared_schemas)
    if undeclared:
        errors.append(f"alignment names missing from OpenAPI components.schemas: {undeclared}")

    operation_count = sum(
        1 for item in paths.values() if isinstance(item, dict)
        for method in item if method.lower() in {"get", "post", "put", "patch", "delete", "head", "options", "trace"}
    )
    print(f"paths={len(paths)} operations={operation_count}")
    print(f"errors={len(errors)} warnings={len(warnings)}")
    for item in errors:
        print(f"ERROR: {item}")
    for item in warnings:
        print(f"WARNING: {item}")
    if errors:
        return 1
    print("PASS: deep cross-document consistency audit completed.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
