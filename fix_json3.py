#!/usr/bin/env python3
"""
Fix JSON files with multiline solution strings by converting actual newlines to \\n escape sequences.
"""
import re
import json
import glob

def fix_multiline_solution(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Pattern to find solution field that spans multiple lines
    # Match "solution": " followed by content until a line that starts with whitespace + "carriers" or "distractors"
    # The solution value ends with ",
    
    # Find solution field - might span multiple lines
    # We'll process line by line and identify solution value lines
    
    lines = content.split('\n')
    result_lines = []
    i = 0
    solution_mode = False
    solution_lines = []
    solution_indent = ""
    
    while i < len(lines):
        line = lines[i]
        
        # Check if this line starts a solution field
        if '"solution"' in line and '": "' in line:
            # This line has the start of solution value
            # Extract the part after ": "
            match = re.search(r'(": ")(.+)', line)
            if match:
                solution_mode = True
                solution_indent = " " * (len(line) - len(line.lstrip()))
                prefix = line[:match.start(2)]
                rest = match.group(2)
                
                # Check if the solution value is complete on this line (ends with ",)
                if '",' in rest:
                    # Complete on one line - no fix needed
                    result_lines.append(line)
                    solution_mode = False
                else:
                    # Continues on next lines
                    solution_lines = [rest]
                    result_lines.append(prefix)  # "solution": "
        elif solution_mode:
            # Check if this line continues the solution or ends it
            stripped = line.strip()
            
            # If line starts with "carriers" or "distractors" (after whitespace), solution is done
            if stripped.startswith('"carriers"') or stripped.startswith('"distractors"') or stripped.startswith('"notes"'):
                # End of solution - join all lines and reconstruct
                full_solution = '\n'.join(solution_lines)
                # Properly escape for JSON
                escaped = json.dumps(full_solution)[1:-1]  # Remove surrounding quotes
                result_lines.append(escaped + '",')
                result_lines.append(line)
                solution_mode = False
                solution_lines = []
            elif stripped == '],' or stripped == '}':
                # End of solution (no trailing comma) - join all lines and reconstruct
                full_solution = '\n'.join(solution_lines)
                escaped = json.dumps(full_solution)[1:-1]
                result_lines.append(escaped + '",')
                solution_mode = False
                solution_lines = []
                # Check if the current line needs modification
                if stripped == '],':
                    result_lines.append(line.replace('],', ']'))
                else:
                    result_lines.append(line)
            else:
                # Continuation line
                solution_lines.append(line)
        else:
            result_lines.append(line)
        
        i += 1
    
    fixed_content = '\n'.join(result_lines)
    
    # Validate
    try:
        json.loads(fixed_content)
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(fixed_content)
        print(f"Fixed: {filepath}")
        return True
    except json.JSONDecodeError as e:
        print(f"Failed to fix {filepath}: {e}")
        return False

for f in glob.glob('/home/sites/php-duplication-samples/gen/recipes/L05_refactorability/*.json'):
    fix_multiline_solution(f)
