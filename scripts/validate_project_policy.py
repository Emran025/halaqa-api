#!/usr/bin/env python3
"""Validate cross-document project architecture policy consistency."""

from __future__ import annotations

import sys
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
REQUIRED_FILES = [
    "SKILL.md",
    "PROJECT_ARCHITECTURE_POLICY.md",
    "openapi.yaml",
    "REALTIME_CONTRACT.md",
    "API_FUNCTIONS_CATALOG.md",
    "references/canonical-tree.md",
    "references/placement-rules.md",
    "references/realtime-and-webrtc.md",
    "references/validation-checklist.md",
]


def text(relative: str) -> str:
    return (ROOT / relative).read_text(encoding="utf-8")


def main() -> int:
    missing = [relative for relative in REQUIRED_FILES if not (ROOT / relative).exists()]
    if missing:
        print(f"FAIL: missing policy files: {missing}")
        return 1

    skill = text("SKILL.md")
    policy = text("PROJECT_ARCHITECTURE_POLICY.md")
    openapi = text("openapi.yaml")
    realtime = text("REALTIME_CONTRACT.md")
    catalog = text("API_FUNCTIONS_CATALOG.md")
    tree = text("references/canonical-tree.md")
    placement = text("references/placement-rules.md")
    checklist = text("references/validation-checklist.md")

    required_tokens = {
        "SKILL.md": ["teacher", "student", "P2P", "WebSocket الداخلي", "STUN", "TURN"],
        "PROJECT_ARCHITECTURE_POLICY.md": ["Laravel هو الـbackend", "P2P مباشرة", "Host ICE Candidates", "لا توجد موافقة إدارية"],
        "openapi.yaml": ["direct_p2p_only", "laravel_websocket", "host_only", "/realtime/channels/authorize"],
        "REALTIME_CONTRACT.md": ["WebSocket المضمن", "WebRTC P2P", "Host ICE Candidates", "direct_connection_unavailable"],
        "references/canonical-tree.md": ["Realtime/", "RunWebSocketServerCommand.php"],
        "references/placement-rules.md": ["app/Realtime", "P2P-only"],
        "references/validation-checklist.md": ["Media Server", "STUN", "TURN"],
    }
    documents = {
        "SKILL.md": skill,
        "PROJECT_ARCHITECTURE_POLICY.md": policy,
        "openapi.yaml": openapi,
        "REALTIME_CONTRACT.md": realtime,
        "references/canonical-tree.md": tree,
        "references/placement-rules.md": placement,
        "references/validation-checklist.md": checklist,
    }
    for name, tokens in required_tokens.items():
        absent = [token for token in tokens if token not in documents[name]]
        if absent:
            print(f"FAIL: {name} missing required policy markers: {absent}")
            return 1

    forbidden_active_fragments = {
        "openapi.yaml": ["ice_servers:", "IceServer", "/broadcasting/auth"],
        "REALTIME_CONTRACT.md": ["مع TURN عند الحاجة", "استخدم STUN", "استخدم TURN", "/broadcasting/auth"],
        "API_FUNCTIONS_CATALOG.md": ["مع TURN عند الحاجة", "استخدم STUN", "استخدم TURN", "/broadcasting/auth"],
    }
    for name, fragments in forbidden_active_fragments.items():
        content = {"openapi.yaml": openapi, "REALTIME_CONTRACT.md": realtime, "API_FUNCTIONS_CATALOG.md": catalog}[name]
        for fragment in fragments:
            if fragment in content:
                print(f"FAIL: active external/rely path found in {name}: {fragment}")
                return 1

    if "P2P-only" not in policy or "P2P-only" not in placement:
        print("FAIL: P2P-only policy marker is not propagated")
        return 1

    print("PASS: Laravel-only and P2P-only policy is consistent across the RAG documents and contracts.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
