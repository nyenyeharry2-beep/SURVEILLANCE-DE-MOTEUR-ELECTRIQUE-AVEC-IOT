/**
 * Resolve bundled assets from the Capacitor app root (not route-relative).
 */
export function resolveRootAsset(relativePath: string): string {
  const clean = relativePath.replace(/^\.\//, '');
  return `${window.location.origin}/${clean}`;
}

export function resolveRootAssetDir(relativePath: string): string {
  const url = resolveRootAsset(relativePath);
  return url.endsWith('/') ? url : `${url}/`;
}
