@echo off
chcp 65001 >nul
title Pharmacie Nouvelle Eve — Vendeur PC

set "URL=https://mapharmaciepk.xo.je/vendeur/"
set "SHORTCUT=%USERPROFILE%\Desktop\Pharmacie Vendeur.lnk"

echo.
echo  ================================================
echo   Pharmacie Nouvelle Eve — Installation Vendeur PC
echo  ================================================
echo.
echo  Ce script cree un raccourci sur le Bureau et ouvre
echo  le portail vendeur dans Chrome ou Edge.
echo.

powershell -NoProfile -ExecutionPolicy Bypass -Command ^
  "$ws = New-Object -ComObject WScript.Shell; ^
   $s = $ws.CreateShortcut('%SHORTCUT%'); ^
   $s.TargetPath = 'cmd.exe'; ^
   $s.Arguments = '/c start \"\" \"%URL%\"'; ^
   $s.WorkingDirectory = '%USERPROFILE%'; ^
   $s.IconLocation = 'imageres.dll,109'; ^
   $s.Description = 'Portail vendeur Pharmacie Nouvelle Eve'; ^
   $s.Save()"

if exist "%SHORTCUT%" (
  echo  [OK] Raccourci cree : %SHORTCUT%
) else (
  echo  [INFO] Raccourci non cree — ouverture directe du navigateur.
)

echo.
echo  Ouverture de %URL%
echo.
start "" "%URL%"

echo  Pour installer comme application :
echo    Chrome/Edge ^> menu ^> Installer l'application
echo.
pause
