#!/bin/bash
COOKIE_JAR=$(mktemp)
TOKEN=*** -s -c "$COOKIE_JAR" http://127.0.0.1:8000/login 2>/dev/null | grep -o 'name="_token" value="[^"]*"' | head -1 | sed 's/name="_token" value="//;s/"//')
curl -s -b "$COOKIE_JAR" -c "$COOKIE_JAR" -X POST http://127.0.0.1:8000/login \
  -d "username=admin&password=password123&_token=$TOKEN" -L -o /dev/null

echo "=== Admin Pages ==="
for route in "admin/dashboard" "admin/products" "admin/categories" "admin/users" "admin/transactions" "admin/restaurants" "admin/reports" "admin/settings" "pos"; do
  CODE=$(curl -s -b "$COOKIE_JAR" "http://127.0.0.1:8000/$route" -L -o /dev/null -w "%{http_code}")
  echo "$route: $CODE"
done

rm -f "$COOKIE_JAR"
