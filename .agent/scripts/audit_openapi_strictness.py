#!/usr/bin/env python3
from pathlib import Path
import yaml

ROOT = Path(__file__).resolve().parents[1]
spec = yaml.safe_load((ROOT / 'openapi.yaml').read_text(encoding='utf-8'))
schemas = spec.get('components', {}).get('schemas', {})
open_objects=[]
dynamic_objects=[]
no_required=[]
for name, schema in schemas.items():
    if schema.get('type') != 'object':
        continue
    if schema.get('additionalProperties') is not False:
        open_objects.append(name)
    if schema.get('additionalProperties') not in (None, False):
        dynamic_objects.append(name)
    if schema.get('properties') and 'required' not in schema:
        no_required.append(name)
print('object_schemas', sum(1 for s in schemas.values() if s.get('type') == 'object'))
print('open_objects', len(open_objects))
print('dynamic_objects', dynamic_objects)
print('no_required_with_properties', no_required)
for name in open_objects:
    print('OPEN', name)
