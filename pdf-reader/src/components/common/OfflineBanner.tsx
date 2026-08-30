import { useInstallPrompt, useOnlineStatus } from '../../hooks/useOffline';
import './OfflineBanner.css';

export function OfflineBanner() {
  const online = useOnlineStatus();
  const { canInstall, installed, install } = useInstallPrompt();

  return (
    <div className="offline-banner">
      {!online && (
        <p className="offline-banner__status offline-banner__status--offline">
          Mode hors ligne — toutes les fonctions locales restent disponibles
        </p>
      )}
      {online && !installed && canInstall && (
        <button type="button" className="offline-banner__install" onClick={() => install()}>
          Installer l&apos;application (fonctionne sans internet)
        </button>
      )}
      {installed && (
        <p className="offline-banner__status offline-banner__status--installed">
          Application installée — utilisable hors ligne
        </p>
      )}
    </div>
  );
}
