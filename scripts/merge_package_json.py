import json
import re
import sys

def merge_package_json(file_path):
    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()

    # 使用更加健壯的匹配方式
    marker_start = "<<<<<<< HEAD"
    marker_middle = "======="
    marker_end = ">>>>>>>"
    
    if marker_start not in content:
        print("No conflict markers found.")
        return

    try:
        parts = content.split(marker_start)
        pre_conflict = parts[0]
        rest = parts[1].split(marker_end)
        conflict_block = rest[0]
        post_conflict = rest[1]
        
        conflict_parts = conflict_block.split(marker_middle)
        head_part = conflict_parts[0].strip()
        theirs_part = conflict_parts[1].strip()
        # 移除 commit ID
        theirs_part = re.sub(r' [a-f0-9]{7,40}.*', '', theirs_part).strip()
    except:
        print("Failed to split conflict markers")
        sys.exit(1)

    def parse_part(part):
        try: return json.loads(part)
        except: pass
        try: return json.loads('{' + part + '}')
        except: pass
        if part.endswith('}'):
            try: return json.loads('{' + part[:-1].strip() + '}')
            except: pass
        raise Exception("Failed to parse JSON")

    try:
        head_data = parse_part(head_part)
        theirs_data = parse_part(theirs_part)
    except:
        sys.exit(1)

    merged = head_data.copy()
    for key in ['scripts', 'dependencies', 'devDependencies', 'workspaces']:
        if key in theirs_data:
            if key not in merged: merged[key] = {}
            if isinstance(merged[key], dict):
                for k, v in theirs_data[key].items():
                    if k not in merged[key]: merged[key][k] = v
            elif isinstance(merged[key], list):
                merged[key] = list(set(merged[key] + theirs_data[key]))
    
    with open(file_path, 'w', encoding='utf-8') as f:
        json.dump(merged, f, indent=4, ensure_ascii=False)
    print(f"Successfully merged {file_path}")

if __name__ == "__main__":
    merge_package_json(sys.argv[1])
