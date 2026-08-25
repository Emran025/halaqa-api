#!/usr/bin/env python3
"""Static validation for the MySQL schema contract."""

from __future__ import annotations

import re
import sys
from collections import Counter
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
SQL_PATH = ROOT / "database_schema.sql"
DOC_PATH = ROOT / "DATABASE_SCHEMA_CONTRACT.md"

EXPECTED_TABLES = {
    "users", "teacher_profiles", "student_profiles", "teacher_documents",
    "halaqas", "halaqa_memberships", "registration_requests",
    "registration_request_profiles", "registration_request_availability",
    "registration_request_availability_slots", "student_availability_profiles",
    "student_availability_slots", "tracking_types", "tracking_units",
    "mistake_types", "quran_editions", "quran_surahs", "quran_pages",
    "quran_ayahs", "quran_ayah_words", "quran_range_units",
    "follow_up_plans", "follow_up_plan_details", "follow_up_items",
    "live_sessions", "session_tasks", "daily_trackings", "tracking_details",
    "mistakes", "task_notes", "task_evaluations", "session_reports",
    "notifications", "idempotency_keys", "personal_access_tokens",
    "password_reset_tokens", "audit_events", "jobs", "failed_jobs",
}


def parse_tables(sql: str) -> dict[str, str]:
    pattern = re.compile(
        r"CREATE\s+TABLE\s+([a-zA-Z0-9_]+)\s*\((.*?)\)\s*ENGINE=",
        re.IGNORECASE | re.DOTALL,
    )
    return {name: body for name, body in pattern.findall(sql)}


def parse_columns(body: str) -> set[str]:
    columns: set[str] = set()
    for line in body.splitlines():
        match = re.match(r"\s{4}([a-zA-Z0-9_]+)\s+", line)
        if match:
            name = match.group(1).upper()
            if name not in {"PRIMARY", "UNIQUE", "KEY", "CONSTRAINT", "CHECK", "FOREIGN"}:
                columns.add(match.group(1))
    return columns


def clean_names(value: str) -> list[str]:
    return [part.strip().strip('`') for part in value.split(",")]


def main() -> int:
    if not SQL_PATH.exists() or not DOC_PATH.exists():
        print("FAIL: database contract SQL or documentation is missing")
        return 1

    sql = SQL_PATH.read_text(encoding="utf-8")
    doc = DOC_PATH.read_text(encoding="utf-8")
    table_matches = re.findall(r"CREATE\s+TABLE\s+([a-zA-Z0-9_]+)\s*\(", sql, re.I)
    tables = parse_tables(sql)
    duplicate_tables = [name for name, count in Counter(table_matches).items() if count > 1]
    missing = sorted(EXPECTED_TABLES - set(tables))
    unexpected = sorted(set(tables) - EXPECTED_TABLES)
    if duplicate_tables:
        print(f"FAIL: duplicate CREATE TABLE statements: {duplicate_tables}")
        return 1
    if missing:
        print(f"FAIL: expected tables missing: {missing}")
        return 1
    if unexpected:
        print(f"WARN: additional tables are present: {unexpected}")

    table_columns = {name: parse_columns(body) for name, body in tables.items()}
    for table, columns in table_columns.items():
        if "PRIMARY KEY" not in tables[table].upper():
            print(f"FAIL: table without PRIMARY KEY: {table}")
            return 1
        if not columns:
            print(f"FAIL: no columns parsed for table: {table}")
            return 1

        for local_raw, ref_table, ref_raw in re.findall(
            r"FOREIGN\s+KEY\s*\(([^)]+)\)\s+REFERENCES\s+([a-zA-Z0-9_]+)\s*\(([^)]+)\)",
            tables[table], re.I,
        ):
            local_columns = clean_names(local_raw)
            ref_columns = clean_names(ref_raw)
            if any(column not in columns for column in local_columns):
                print(f"FAIL: FK in {table} uses missing local column(s): {local_columns}")
                return 1
            if ref_table not in tables:
                print(f"FAIL: FK in {table} references unknown table: {ref_table}")
                return 1
            if any(column not in table_columns[ref_table] for column in ref_columns):
                print(f"FAIL: FK in {table} references missing column(s) on {ref_table}: {ref_columns}")
                return 1
            if len(local_columns) != len(ref_columns):
                print(f"FAIL: FK column count mismatch in {table} -> {ref_table}")
                return 1

        for index_columns in re.findall(
            r"\b(?:UNIQUE\s+)?KEY\s+[a-zA-Z0-9_]+\s*\(([^)]+)\)",
            tables[table], re.I,
        ):
            for column in clean_names(index_columns):
                if column not in columns:
                    print(f"FAIL: index in {table} uses missing column: {column}")
                    return 1

    required_markers = [
        "registration_request_profiles", "registration_request_availability",
        "registration_request_availability_slots", "student_availability_slots",
        "follow_up_plan_details", "follow_up_items", "quran_ayah_words",
        "mistake_type_id", "direct_p2p_only", "idempotency_keys", "audit_events",
    ]
    absent_markers = [marker for marker in required_markers if marker not in sql]
    if absent_markers:
        print(f"FAIL: required schema markers are missing: {absent_markers}")
        return 1

    if "MySQL" not in doc or "Laravel" not in doc or "P2P" not in doc:
        print("FAIL: database documentation is not linked to the Laravel/P2P contract")
        return 1

    prohibited_storage = ["audio_path", "video_path", "sdp", "ice_candidate"]
    found_prohibited = [marker for marker in prohibited_storage if marker in sql.lower()]
    if found_prohibited:
        print(f"FAIL: prohibited media/signaling storage fields found: {found_prohibited}")
        return 1

    print(f"PASS: database contract validated; {len(tables)} tables, FK/index columns valid, P2P media storage absent.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
