#!/usr/bin/env python3
"""
Fix JSON files with multiline solution strings by:
1. Finding solution values that span multiple lines
2. Extracting the content across all lines
3. Re-encoding as a single-line string with proper \n escapes
"""
import re
import json
import glob

def fix_file(filepath):
    print(f"Processing: {filepath}")
    with open(filepath, 'r', encoding='utf-8') as f:
        lines = f.readlines()
    
    result_lines = []
    i = 0
    in_solution = False
    solution_content = []
    solution_prefix = ""
    
    while i < len(lines):
        line = lines[i]
        
        # Check if this line starts a solution field
        # Match: whitespace+"solution": "something
        if not in_solution and re.search(r'^\s*"solution"\s*:\s*"', line):
            # Check if solution is complete on this line (ends with ",)
            # Pattern: "solution": "...", or "solution": "...",\n
            match = re.search(r'^(\s*"solution"\s*:\s*)(.+)', line)
            if match:
                prefix = match.group(1)
                rest = match.group(2)
                # Find where the string ends - should be at ", after the value
                # We need to find the closing ", after the value
                # The value content is between the opening " after : and the ", that closes it
                
                # Count quotes to find proper end
                # The string starts with " after : and ends with ",
                # But the content might have escaped quotes \"
                
                # Simple heuristic: if there's a ", later in the line (not \",), that's the end
                # Actually, let's just see if the line has ", at the end (with possible whitespace)
                if re.search(r'",\s*$', rest):
                    # Solution is complete on one line - no fix needed
                    result_lines.append(line)
                else:
                    # Solution spans multiple lines
                    in_solution = True
                    solution_prefix = prefix
                    solution_content = [rest]
        elif in_solution:
            # Check if this line ends the solution
            # Solution ends when we find ", (a quote-comma at the start of what looks like content)
            # or when we find a line that starts a new JSON field (starts with whitespace+"something":)
            
            stripped = line.strip()
            
            # If line starts with "carriers" or "distractors" or similar, solution ended
            if stripped.startswith('"carriers"') or stripped.startswith('"distractors"') or stripped.startswith('"notes"'):
                # End of solution - join content and properly escape
                full_content = '\n'.join(solution_content)
                # Remove trailing comma if present
                full_content = full_content.rstrip(',')
                # Re-encode for JSON
                encoded = json.dumps(full_content)
                result_lines.append(solution_prefix + encoded + '",\n')
                result_lines.append(line)
                in_solution = False
                solution_content = []
            elif stripped == '],' or stripped == '},' or stripped == '"}]':
                # More variations of solution end
                full_content = '\n'.join(solution_content)
                full_content = full_content.rstrip(',').rstrip('"').rstrip(']').rstrip('}')
                encoded = json.dumps(full_content)
                result_lines.append(solution_prefix + encoded + '",\n')
                in_solution = False
                solution_content = []
                result_lines.append(line)
            else:
                # Continuation line
                solution_content.append(line.rstrip('\n'))
        else:
            result_lines.append(line)
        
        i += 1
    
    if in_solution and solution_content:
        # Unclosed solution - fix it
        full_content = '\n'.join(solution_content)
        encoded = json.dumps(full_content)
        result_lines.append(solution_prefix + encoded + '"\n')
    
    fixed_content = ''.join(result_lines)
    
    # Validate
    try:
        json.loads(fixed_content)
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(fixed_content)
        print(f"  Fixed: {filepath}")
        return True
    except json.JSONDecodeError as e:
        print(f"  Still invalid: {filepath}: {e}")
        # Debug: show around the error
        return False

for f in glob.glob('/home/sites/php-duplication-samples/gen/recipes/L05_refactorability/*.json'):
    fix_file(f)
