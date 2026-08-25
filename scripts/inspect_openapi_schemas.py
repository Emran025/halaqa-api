"""Print OpenAPI schema properties for contract review."""
from pathlib import Path
import yaml

ROOT = Path(__file__).resolve().parents[1]
doc = yaml.safe_load((ROOT / "openapi.yaml").read_text(encoding="utf-8"))
for name, schema in doc.get("components", {}).get("schemas", {}).items():
    if isinstance(schema, dict) and "properties" in schema:
        props = ", ".join(schema["properties"].keys())
        required = ", ".join(schema.get("required", []))
        print(f"{name}: [{props}] | required=[{required}]")
