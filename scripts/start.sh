#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"
if python3 -m venv .venv >/dev/null 2>&1 && [ -f .venv/bin/activate ]; then
  # shellcheck disable=SC1091
  source .venv/bin/activate
fi
python3 -m pip install -q -r backend/requirements.txt
export PYTHONPATH="$ROOT"
export PATH="$HOME/.local/bin:$PATH"
HOST="${HOST:-0.0.0.0}"
PORT="${PORT:-8000}"
echo "SCADA moteur electrique -> http://127.0.0.1:${PORT}"
exec python -m uvicorn backend.app.main:app --host "$HOST" --port "$PORT"
