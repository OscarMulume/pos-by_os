#!/usr/bin/env python3
import subprocess
import re
import sys

def curl(url, method='GET', data=None, cookies=None, follow=True):
    cmd = ['curl', '-s']
    if cookies:
        cmd.extend(['-b', cookies, '-c', cookies])
    if method == 'POST':
        cmd.append('-X')
        cmd.append('POST')
    if data:
        cmd.extend(['-d', data])
    if follow:
        cmd.append('-L')
    cmd.append(url)
    result = subprocess.run(cmd, capture_output=True, text=True)
    return result.stdout, result.returncode

# Get login page and extract CSRF token
html, _ = curl('http://127.0.0.1:8000/login')
match = re.search(r'name="_token" value="([^"]*)"', html)
token = match.group(1) if match else ''
print(f"Token: {token[:20]}...")

# Login
cookie_file = '/tmp/pos_cookies.txt'
html, _ = curl('http://127.0.0.1:8000/login', method='POST',
               data=f'username=admin&password=password123&_token={token}',
               cookies=cookie_file)

# Test routes
routes = [
    'admin/dashboard',
    'admin/products',
    'admin/categories',
    'admin/users',
    'admin/transactions',
    'admin/restaurants',
    'admin/reports',
    'admin/settings',
    'pos',
]

print("\n=== Route Status ===")
for route in routes:
    cmd = ['curl', '-s', '-b', cookie_file, '-o', '/dev/null', '-w', '%{http_code}', '-L', f'http://127.0.0.1:8000/{route}']
    result = subprocess.run(cmd, capture_output=True, text=True)
    code = result.stdout.strip()
    status = 'OK' if code == '200' else f'ERROR {code}'
    print(f"  {route}: {status}")

import os
os.remove(cookie_file)
