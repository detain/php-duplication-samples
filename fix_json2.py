#!/usr/bin/env python3
"""
Fix JSON files that have solution fields with improperly encoded newlines.
"""
import re
import json
import glob
import sys

def fix_file(filepath):
    print(f"Processing: {filepath}")
    try:
        with open(filepath, 'r', encoding='utf-8') as f:
            raw = f.read()
        
        # Try to parse as-is first
        try:
            data = json.loads(raw)
            print(f"  Already valid JSON, checking solution fields...")
            fixed = False
            for set_data in data.get('sets', []):
                if 'solution' in set_data and isinstance(set_data['solution'], str):
                    sol = set_data['solution']
                    # Check if it contains actual newlines (invalid in JSON)
                    if '\n' in sol:
                        print(f"  Found actual newlines in solution for {set_data.get('set_id', 'unknown')}")
                        # Re-encode through JSON to get proper escape sequences
                        re_encoded = json.dumps(sol)
                        set_data['solution'] = json.loads(re_encoded)
                        fixed = True
            if fixed:
                output = json.dumps(data, indent=4, ensure_ascii=False)
                output = output.replace('\\n', '\n')  # Keep newlines as actual chars for readability
                # Actually no - keep them as \n escapes for valid JSON
                with open(filepath, 'w', encoding='utf-8') as f:
                    f.write(output)
                print(f"  Fixed solution fields")
            else:
                print(f"  No fixes needed")
            return True
        except json.JSONDecodeError as e:
            print(f"  JSON parse error: {e}")
            return False
    except Exception as e:
        print(f"  Error: {e}")
        return False

for f in glob.glob('/home/sites/php-duplication-samples/gen/recipes/L05_refactorability/*.json'):
    fix_file(f)
