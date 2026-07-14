#!/usr/bin/env python3
"""
Fix JSON files with multiline solution strings by finding and properly escaping the content.
This version handles actual newline bytes within string values.
"""
import re
import json
import glob

def find_solution_boundaries(content):
    """
    Find all solution field boundaries in raw JSON content.
    Returns list of (start_pos, end_pos) for each solution value.
    """
    solutions = []
    search_pos = 0
    
    while True:
        # Find next "solution": pattern
        idx = content.find(b'"solution":', search_pos)
        if idx == -1:
            break
        
        # Find the opening quote after "solution":
        # The format is "solution": "value"
        value_start = content.find(b'"', idx + len('"solution":'))
        if value_start == -1:
            break
        
        # Now find the end of the string value
        # We need to track quote levels and escape sequences
        pos = value_start + 1
        in_escape = False
        
        while pos < len(content):
            byte = content[pos:pos+1]
            
            if in_escape:
                in_escape = False
                pos += 1
                continue
            
            if byte == b'\\':
                in_escape = True
                pos += 1
                continue
            
            if byte == b'"':
                # End of string
                end_pos = pos
                solutions.append((value_start + 1, end_pos))
                break
            
            pos += 1
        
        search_pos = pos
    
    return solutions

def fix_file(filepath):
    print(f"Processing: {filepath}")
    with open(filepath, 'rb') as f:
        content = f.read()
    
    # Check if file is already valid
    try:
        json.loads(content.decode('utf-8'))
        print(f"  Already valid: {filepath}")
        return True
    except:
        pass
    
    # Find solution boundaries
    solutions = find_solution_boundaries(content)
    print(f"  Found {len(solutions)} solution fields")
    
    if not solutions:
        print(f"  No solutions found to fix")
        return False
    
    # For each solution, extract the content and re-encode properly
    # We need to work backwards to not disturb positions
    result = bytearray(content)
    offset = 0
    
    for start, end in solutions:
        # Extract raw content (may contain actual newlines)
        raw_content = content[start:end]
        
        # Try to decode as utf-8
        try:
            text_content = raw_content.decode('utf-8')
        except:
            print(f"  Could not decode solution content")
            continue
        
        # Re-encode through JSON to get proper escape sequences
        json_encoded = json.dumps(text_content)
        # json_encoded includes surrounding quotes, we only want inner content
        escaped_content = json_encoded[1:-1]  # Remove surrounding quotes
        
        # Calculate the replacement
        replacement = escaped_content.encode('utf-8')
        
        # Calculate size difference
        old_len = end - start
        new_len = len(replacement)
        diff = new_len - old_len
        
        # Replace in result
        # Adjust positions by cumulative offset
        adj_start = start + offset
        adj_end = end + offset
        
        result = bytearray(content[:adj_start]) + replacement + bytearray(content[adj_end:])
        content = bytes(result)
        offset += diff
    
    # Validate final result
    try:
        json.loads(content.decode('utf-8'))
        with open(filepath, 'wb') as f:
            f.write(content)
        print(f"  Fixed: {filepath}")
        return True
    except json.JSONDecodeError as e:
        print(f"  Still invalid: {filepath}: {e}")
        return False

for f in glob.glob('/home/sites/php-duplication-samples/gen/recipes/L05_refactorability/*.json'):
    fix_file(f)
