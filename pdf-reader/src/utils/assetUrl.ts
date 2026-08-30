/**
 * Resolve bundled static assets from the app root (Capacitor / Vite base).
 * Route-relative paths like ./piper/wasm/ break on /library or /reader routes.
 */
export function resolveAppAsset(path: string): string {
  const cleanPath = path.replace(/^\.\//, '');
  const appRoot = new URL(import.meta.env.BASE_URL, window.location.href);
  return new URL(cleanPath, appRoot).href;
}

export function resolveAppAssetDir(path: string): string {
  const url = resolveAppAsset(path);
  return url.endsWith('/') ? url : `${url}/`;
}

export async function assertAppAsset(path: string): Promise<void> {
  const url = resolveAppAsset(path);
  const response = await fetch(url);
  if (!response.ok) {
    throw new Error(`Ressource introuvable : ${url}`);
  }
}
