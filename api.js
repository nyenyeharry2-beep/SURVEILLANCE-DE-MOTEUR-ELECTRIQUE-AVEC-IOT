const API = ".";

function passwordB64(password) {
  return btoa(unescape(encodeURIComponent(password || "")));
}

async function request(path, options = {}) {
  const headers = {
    Accept: "application/json",
    ...(options.headers || {}),
  };
  if (options.body && typeof options.body === "string" && !headers["Content-Type"]) {
    headers["Content-Type"] = "application/json";
  }

  const res = await fetch(`${API}/${path}`, {
    credentials: "include",
    headers,
    ...options,
  });

  let data = {};
  try {
    data = await res.json();
  } catch {
    data = {};
  }

  if (!res.ok) {
    const error = new Error(data.error || `Erreur HTTP ${res.status}`);
    error.status = res.status;
    throw error;
  }

  return data;
}

function authBody(fields) {
  const body = new URLSearchParams();
  Object.entries(fields).forEach(([key, value]) => {
    if (value != null && value !== "") body.set(key, String(value));
  });
  return body;
}

export function login(email, password) {
  return request("login.php", {
    method: "POST",
    body: authBody({
      email,
      passwordB64: passwordB64(password),
    }),
  });
}

export function register(name, email, password) {
  return request("register.php", {
    method: "POST",
    body: authBody({
      name,
      email,
      passwordB64: passwordB64(password),
    }),
  });
}

export function logout() {
  return request("logout.php", { method: "POST" });
}

export function me() {
  return request("me.php");
}

export function fetchEtat() {
  return request("etat.php");
}

export function sendCommande(etatCommande) {
  return request("commande.php", {
    method: "POST",
    body: JSON.stringify({ etatCommande }),
  });
}

export function toUser(payload) {
  const u = payload?.user;
  if (!u) return null;
  return {
    id: u.id,
    email: u.email,
    displayName: u.nom || u.email,
  };
}
