#!/usr/bin/env python3
from pathlib import Path
import yaml

spec = yaml.safe_load((Path(__file__).resolve().parents[1] / 'openapi.yaml').read_text(encoding='utf-8'))
for name, schema in spec.get('components', {}).get('schemas', {}).items():
    props = schema.get('properties', {})
    if 'data' in props:
        data = props['data']
        if '$ref' in data:
            target = data['$ref'].rsplit('/', 1)[-1]
        elif 'items' in data:
            target = 'array:' + str(data['items'].get('$ref', data['items'].get('type', 'object')))
        else:
            target = str(data.get('type', 'object'))
        print(f'{name}\t{target}\trequired={schema.get("required", [])}')
