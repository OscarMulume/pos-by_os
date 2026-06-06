#!/bin/bash
COOKIE_JAR=$(mktemp)
TOKEN=$(curl -s -c "$COOKIE_JAR" http://127.0.0.1:8000/login 2>/dev/null | grep -o 'name="_token" value="[^"]*"' | head -1 | sed 's/name="_token" value="//;s/"//')
echo "Token: $TOKEN"
curl -s -b "$COOKIE_JAR" -c "$COOKIE_JAR" -X POST http://127.0.0.1:8000/login \
  -d "username=admin&password=password123&_token=$TOKEN" -L -o /dev/null -w "Login: %{http_code} "
curl -s -b "$COOKIE_JAR" http://127.0.0.1:8000/admin/dashboard -L -o /tmp/dashboard.html -w "Dashboard: %{http_code}"
echo ""
grep -oE '<title>[^<]*</title>' /tmp/dashboard.html | head -3
rm -f "$COOKIE_JAR" /tmp/dashboard.html
