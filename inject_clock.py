import os
import re

directories = [
    'resources/views/manager',
    'resources/views/finance'
]

pattern = re.compile(r'(<div[^>]*class="[^"]*border-b border-slate-200 pb-5[^"]*"[^>]*>)(.*?)(</div>\s*(?:<!--|\n\s*@if|<div[^>]*class="[^"]*(?:bg-white|flex|grid|grid-cols|space-y)[^"]*"))', re.DOTALL)

for directory in directories:
    for root, dirs, files in os.walk(directory):
        for file in files:
            if file.endswith('.blade.php'):
                filepath = os.path.join(root, file)
                with open(filepath, 'r', encoding='utf-8') as f:
                    content = f.read()

                # Some files might already have the flex layout. Let's make sure.
                # Find the first border-b pb-5 div that acts as the header.
                
                # A safer approach is to find the header which usually contains an <h1> or <h2>, 
                # and ends before the next major block.
                # We can just look for the header div and its closing tag.
                
                def replacer(match):
                    div_tag = match.group(1)
                    inner_html = match.group(2)
                    suffix = match.group(3)
                    
                    # If it already has x-system-clock, skip
                    if 'x-system-clock' in inner_html or 'x-system-clock' in div_tag:
                        return match.group(0)
                        
                    # If the div_tag doesn't have flex, add it
                    if 'flex flex-col' not in div_tag:
                        div_tag = div_tag.replace('pb-5"', 'pb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4"')
                        
                    # The inner html might not be wrapped in a div. Let's wrap it in a div.
                    # Actually, if it's already wrapped in a div, wrapping it again is fine, or we can just leave it.
                    # Looking at manager/verification: it's not wrapped in a div.
                    # Looking at manager/vehicles: it's NOT wrapped in a div either.
                    
                    new_inner_html = f"""
                    <div>
                        {inner_html.strip()}
                    </div>
                    <div class="hidden lg:flex items-center gap-3">
                        <x-system-clock />
                    </div>
"""
                    return f"{div_tag}\n{new_inner_html}\n{suffix}"
                
                # Let's use a simpler regex that matches the header div
                # We know the header div contains <h1 ...>...</h1> and <p ...>...</p>
                header_pattern = re.compile(
                    r'(<div[^>]*class="[^"]*border-b border-slate-200 pb-5[^"]*"[^>]*>\s*)'
                    r'(<h1.*?</p>\s*)'
                    r'(</div>)',
                    re.DOTALL | re.IGNORECASE
                )
                
                new_content, count = header_pattern.subn(
                    lambda m: f"{m.group(1) if 'flex' in m.group(1) else m.group(1).replace('pb-5\"', 'pb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4\"')}\n                    <div>\n                        {m.group(2).strip()}\n                    </div>\n                    <div class=\"hidden lg:flex items-center gap-3\">\n                        <x-system-clock />\n                    </div>\n                {m.group(3)}",
                    content,
                    count=1
                )
                
                if count > 0:
                    with open(filepath, 'w', encoding='utf-8') as f:
                        f.write(new_content)
                    print(f"Updated: {filepath}")
