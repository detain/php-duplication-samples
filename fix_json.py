#!/usr/bin/env python3
import json
import glob
import re

def fix_json_solution(filepath):
    try:
        with open(filepath, 'r') as f:
            content = f.read()
        
        # The problem: solution field starts with \"<?php instead of "<?php
        # And ends with ...\\n", instead of ...\n",
        
        # Find and replace the malformed solution field
        # Pattern: "solution": \"<?php ... \",
        # Should be: "solution": "<?php ... ",
        
        # Replace \" at start of solution value (but not in other places)
        content = re.sub(r'"solution": \\"', '"solution": "', content)
        
        # The end of solution value has \", before "carriers"
        # We need to find \n", followed by newline and indentation before "carriers"
        content = re.sub(r'\\n",\n(\s*"carriers")', r'\n",\n\1', content)
        
        with open(filepath, 'w') as f:
            f.write(content)
        
        # Verify the JSON is valid
        try:
            json.loads(content)
            print(f'Fixed and verified: {filepath}')
            return True
        except json.JSONDecodeError as e:
            print(f'Fixed but INVALID: {filepath} - {e}')
            return False
    except Exception as e:
        print(f'Error: {filepath} - {e}')
        return False

for f in glob.glob('gen/recipes/L05_refactorability/*.json'):
    fix_json_solution(f)
