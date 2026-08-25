#!/usr/bin/env python3
"""Close top-level OpenAPI object schemas to make the wire contract strict."""

from pathlib import Path
import re

ROOT = Path(__file__).resolve().parents[1]
PATH = ROOT / "openapi.yaml"
lines = PATH.read_text(encoding="utf-8").splitlines(keepends=True)
out = []
inserted = []
for index, line in enumerate(lines):
    out.append(line)
    match = re.match(r"^    ([A-Za-z][A-Za-z0-9_]*):\s*$", line)
    if not match or index + 1 >= len(lines):
        continue
    if not re.match(r"^      type: object\s*$", lines[index + 1]):
        continue
    if index + 2 < len(lines) and re.match(r"^      additionalProperties:", lines[index + 2]):
        continue
    out.append("      additionalProperties: false\n")
    inserted.append(match.group(1))
PATH.write_text("".join(out), encoding="utf-8")
print(f"PASS: closed {len(inserted)} top-level object schemas")
