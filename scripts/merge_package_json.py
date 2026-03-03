import json
import subprocess
import sys
import os

def run_command(cmd):
    try:
        return subprocess.check_output(cmd, shell=True, stderr=subprocess.DEVNULL).decode('utf-8')
    except subprocess.CalledProcessError:
        return None

def deep_merge(target, source):
    """遞歸合併兩個字典"""
    for key, value in source.items():
        if isinstance(value, dict) and key in target and isinstance(target[key], dict):
            deep_merge(target[key], value)
        else:
            target[key] = value
    return target

def merge_package_json(file_path):
    print(f"嘗試穩健合併: {file_path}")
    
    # 1. 取得兩個版本的完整內容 (Stage 2 是 HEAD, Stage 3 是 THEIRS)
    head_content = run_command(f"git show :2:{file_path}")
    theirs_content = run_command(f"git show :3:{file_path}")
    
    if head_content is None or theirs_content is None:
        print("無法從 Git Index 取得檔案內容，回退到基礎合併邏輯。")
        # 這裡可以保留之前的簡單邏輯作為 fallback，但為了安全我們直接報錯
        sys.exit(1)

    try:
        head_data = json.loads(head_content)
        theirs_data = json.loads(theirs_content)
    except json.JSONDecodeError as e:
        print(f"JSON 解析失敗: {e}")
        sys.exit(1)

    # 2. 進行深度合併 (優先保留 HEAD 的版本資訊，合併 dependencies 等)
    merged_data = head_data.copy()
    
    # 我們只針對特定鍵值進行合併，避免污染全域設定
    keys_to_merge = ['scripts', 'dependencies', 'devDependencies', 'workspaces', 'keywords']
    for key in keys_to_merge:
        if key in theirs_data:
            if key not in merged_data:
                merged_data[key] = theirs_data[key]
            elif isinstance(merged_data[key], dict) and isinstance(theirs_data[key], dict):
                deep_merge(merged_data[key], theirs_data[key])
            elif isinstance(merged_data[key], list) and isinstance(theirs_data[key], list):
                merged_data[key] = list(set(merged_data[key] + theirs_data[key]))

    # 3. 寫回檔案 (使用 4 格縮排符合專案規範)
    with open(file_path, 'w', encoding='utf-8') as f:
        json.dump(merged_data, f, indent=4, ensure_ascii=False)
    
    print(f"✓ 已完成 {file_path} 的深度合併")

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("Usage: python3 merge_package_json.py <file_path>")
        sys.exit(1)
    merge_package_json(sys.argv[1])
