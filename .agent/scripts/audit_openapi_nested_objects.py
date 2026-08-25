#!/usr/bin/env python3
from pathlib import Path
import yaml

ROOT = Path(__file__).resolve().parents[1]
spec = yaml.safe_load((ROOT / 'openapi.yaml').read_text(encoding='utf-8'))
open_nodes = []

def walk(value, location):
    if isinstance(value, dict):
        if value.get('type') == 'object' and value.get('additionalProperties') is not False and '.allOf[' not in location:
            open_nodes.append(location)
        for key, child in value.items():
            walk(child, f'{location}.{key}')
    elif isinstance(value, list):
        for i, child in enumerate(value):
            walk(child, f'{location}[{i}]')

walk(spec.get('components', {}).get('schemas', {}), 'components.schemas')
print('open_nested_object_nodes', len(open_nodes))
for location in open_nodes:
    print(location)
