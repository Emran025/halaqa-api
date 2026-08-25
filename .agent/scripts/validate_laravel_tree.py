#!/usr/bin/env python3
"""Validate the architectural locations of a Laravel project.

Usage:
    python validate_laravel_tree.py /path/to/laravel-project

The validator is intentionally conservative: it reports violations it can
prove from paths and simple source scans, while leaving business decisions to
an agent review.
"""

from __future__ import annotations

import argparse
import re
import sys
from pathlib import Path


MISPLACED_DIRS = {
    "app/Http/Request": "Use app/Http/Requests for Form Requests.",
    "app/Resources": "Use app/Http/Resources for API Resources.",
}

REQUIRED_ROOTS = (
    "app",
    "app/Http",
    "app/Http/Controllers",
    "app/Http/Requests",
    "app/Http/Resources",
    "app/Models",
    "app/Services",
    "routes",
    "database/migrations",
    "tests",
)

CONTROLLER_QUERY_PATTERNS = (
    re.compile(r"\bDB::(?:table|select|statement|insert|update|delete)\s*\("),
    re.compile(r"\b[A-Z][A-Za-z0-9_]*::(?:where|join|with|orderBy|groupBy)\s*\("),
    re.compile(r"\bforeach\s*\("),
    re.compile(r"\bHttp::(?:get|post|put|patch|delete)\s*\("),
)


def relative(path: Path, root: Path) -> str:
    return path.relative_to(root).as_posix()


def validate(root: Path) -> list[str]:
    findings: list[str] = []
    if not (root / "artisan").exists():
        findings.append("ERROR: artisan was not found; the target may not be a Laravel project.")
        return findings

    for required in REQUIRED_ROOTS:
        if not (root / required).exists():
            findings.append(f"WARN: expected directory is missing: {required}")

    for path_text, message in MISPLACED_DIRS.items():
        if (root / path_text).exists():
            findings.append(f"ERROR: misplaced directory {path_text}. {message}")

    for path in (root / "app/Http/Controllers").rglob("*.php") if (root / "app/Http/Controllers").exists() else []:
        text = path.read_text(encoding="utf-8", errors="ignore")
        for pattern in CONTROLLER_QUERY_PATTERNS:
            if pattern.search(text):
                findings.append(
                    f"REVIEW: controller may contain business/query logic: {relative(path, root)}; "
                    "consider a Service or Query Service."
                )
                break

    for path in (root / "app/Http/Resources").rglob("*.php") if (root / "app/Http/Resources").exists() else []:
        text = path.read_text(encoding="utf-8", errors="ignore")
        if re.search(r"\b(?:save|create|update|delete|destroy)\s*\(", text):
            findings.append(
                f"ERROR: Resource appears to mutate data: {relative(path, root)}; Resources must transform output only."
            )

    for path in (root / "routes").glob("*.php") if (root / "routes").exists() else []:
        text = path.read_text(encoding="utf-8", errors="ignore")
        if re.search(r"Route::(?:get|post|put|patch|delete)[^;]*function\s*\([^)]*\)\s*\{[^}]*\b(?:DB::|Model::|->save\(|->update\()", text, re.S):
            findings.append(
                f"ERROR: route closure contains data/business logic: {relative(path, root)}; move it to a Controller."
            )

    return findings


def main() -> int:
    parser = argparse.ArgumentParser(description="Validate common Laravel architecture conventions.")
    parser.add_argument("project", nargs="?", default=".", help="Laravel project directory")
    args = parser.parse_args()
    root = Path(args.project).resolve()
    findings = validate(root)

    if not findings:
        print("PASS: no detectable placement violations found. Complete the manual checklist as well.")
        return 0

    for finding in findings:
        print(finding)
    return 1 if any(item.startswith("ERROR") for item in findings) else 0


if __name__ == "__main__":
    sys.exit(main())
