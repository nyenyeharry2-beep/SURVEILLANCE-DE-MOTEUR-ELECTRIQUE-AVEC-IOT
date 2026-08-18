@echo off
cd /d "%~dp0.."
if not exist .venv (
  python -m venv .venv
)
call .venv\Scripts\activate
pip install -q -r backend\requirements.txt
echo SCADA moteur electrique -> http://127.0.0.1:8000
python -m uvicorn backend.app.main:app --host 0.0.0.0 --port 8000
