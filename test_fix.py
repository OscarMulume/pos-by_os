#!/usr/bin/env python3
import subprocess, re, os

def curl(url, method='GET', data=None, cookies=None, follow=True):
    cmd = ['curl', '-s']
    if cookies:
        cmd.extend(['-b', cookies, '-c', cookies])
    if method == 'POST':
        cmd.extend(['-X', 'POST'])
    if data:
        cmd.extend(['-d', data])
    if follow:
        cmd.append('-L')
    cmd.append(url)
    r = subprocess.run(cmd, capture_output=True, text=True)
    return r.stdout

BASE = 'http://127.0.0.2:8001'
COOKIE = '/tmp/pos_test_cookies.txt'

# Get CSRF token
html = curl(f'{BASE}/login', cookies=COOKIE)
m = re.search(r'name="_token" value="([^"]+)"', html)
token = m.group(1) if m else ''
print(f"Token: {token[:20]}...")

# Login as admin
curl(f'{BASE}/login', method='POST', data=f'username=admin&password=password123&_token={token}', cookies=COOKIE)

# Test routes
routes = [
    'admin/dashboard',
    'admin/users',
    'admin/products',
    'pos',
]

print("\n=== Routes (admin) ===")
for r in routes:
    cmd = ['curl', '-s', '-b', COOKIE, '-o', '/dev/null', '-w', '%{http_code}', '-L', f'{BASE}/{r}']
    result = subprocess.run(cmd, capture_output=True, text=True)
    code = result.stdout.strip()
    status = 'OK' if code == '200' else f'ERROR {code}'
    print(f"  {r}: {status}")

# Logout
curl(f'{BASE}/logout', cookies=COOKIE)

# Login as caissier1 (has restaurant_id=1, should work)
html = curl(f'{BASE}/login', cookies=COOKIE)
m = re.search(r'name="_token" value="([^"]+)"', html)
token = m.group(1) if m else ''
curl(f'{BASE}/login', method='POST', data=f'username=caissier1&password=password123&_token={token}', cookies=COOKIE)

print("\n=== POS (caissier1, restaurant_id=1) ===")
cmd = ['curl', '-s', '-b', COOKIE, '-o', '/dev/null', '-w', '%{http_code}', '-L', f'{BASE}/pos']
result = subprocess.run(cmd, capture_output=True, text=True)
print(f"  pos: {result.stdout.strip()}")

# Create a test cashier without restaurant
# (simulate by checking the unassigned page manually)

os.remove(COOKIE)
print("\nDone.")
