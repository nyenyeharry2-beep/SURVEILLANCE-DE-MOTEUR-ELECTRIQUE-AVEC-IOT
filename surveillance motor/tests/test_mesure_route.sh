#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"
php -v >/dev/null

PORT="${PORT:-8765}"
php -S "127.0.0.1:${PORT}" router.php >/tmp/lumen-php-server.log 2>&1 &
PID=$!
cleanup() { kill "$PID" 2>/dev/null || true; }
trap cleanup EXIT
sleep 0.4

fail() { echo "FAIL: $*" >&2; exit 1; }

code_body() {
  local path="$1" method="${2:-GET}" extra="${3:-}"
  curl -sS -D /tmp/lumen-hdr.txt -o /tmp/lumen-body.txt -m 5 \
    -X "$method" $extra "http://127.0.0.1:${PORT}${path}"
}

post_json() {
  local path="$1"
  curl -sS -D /tmp/lumen-hdr.txt -o /tmp/lumen-body.txt -m 5 \
    -X POST \
    -H "Content-Type: application/json" \
    --data '{"x":1}' \
    "http://127.0.0.1:${PORT}${path}"
}

expect_json_ok() {
  local path="$1"
  code_body "$path" GET
  grep -q 'HTTP/1.1 200' /tmp/lumen-hdr.txt || fail "$path status $(head -1 /tmp/lumen-hdr.txt)"
  grep -q '"ok":true' /tmp/lumen-body.txt || fail "$path body $(cat /tmp/lumen-body.txt)"
}

expect_json_ok "/ping.php"
expect_json_ok "/ping"
expect_json_ok "/mesure.php"
expect_json_ok "/mesure"

post_json "/mesure.php"
grep -q 'HTTP/1.1 403' /tmp/lumen-hdr.txt || fail "POST /mesure.php sans clé: $(head -1 /tmp/lumen-hdr.txt) $(cat /tmp/lumen-body.txt)"
grep -q 'Clé appareil' /tmp/lumen-body.txt || fail "POST /mesure.php message $(cat /tmp/lumen-body.txt)"
if grep -qi 'nginx' /tmp/lumen-body.txt; then
  fail "POST a renvoyé une page nginx"
fi

post_json "/mesure"
grep -q 'HTTP/1.1 403' /tmp/lumen-hdr.txt || fail "POST /mesure sans clé: $(head -1 /tmp/lumen-hdr.txt) $(cat /tmp/lumen-body.txt)"
if grep -qi 'nginx' /tmp/lumen-body.txt; then
  fail "POST /mesure a renvoyé une page nginx"
fi

echo "OK: routes ping/mesure GET 200, POST sans clé 403 JSON (pas de 404 nginx)"
