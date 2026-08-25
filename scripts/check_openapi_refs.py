#!/usr/bin/env python3
from pathlib import Path
import re
import yaml

spec = yaml.safe_load((Path(__file__).resolve().parents[1] / 'openapi.yaml').read_text(encoding='utf-8'))
all_refs = set()
def walk(value):
    if isinstance(value, dict):
        if '$ref' in value and isinstance(value['$ref'], str):
            all_refs.add(value['$ref'])
        for item in value.values():
            walk(item)
    elif isinstance(value, list):
        for item in value:
            walk(item)
walk(spec)
missing=[]
for ref in sorted(all_refs):
    if not ref.startswith('#/components/'):
        continue
    parts=ref.split('/')
    bucket=parts[2]
    name=parts[3] if len(parts)>3 else None
    if name not in spec.get('components',{}).get(bucket,{}):
        missing.append(ref)
print('missing_refs', len(missing))
for ref in missing:
    print(ref)
