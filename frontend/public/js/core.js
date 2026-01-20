/**
 * CoreAPI.js - Cliente optimizado para DonWok (Basado en Sesiones)
 */
export default class CoreAPI {
  constructor() {
    // Apuntamos a los puertos expuestos en el host
    this.authBaseUrl = `http://${window.location.hostname}:8001`;
    this.coreBaseUrl = `http://${window.location.hostname}:8000`;
  }

  _url(baseUrl, endpoint) {
    const cleanEndpoint = endpoint.startsWith('/') ? endpoint : `/${endpoint}`;
    return `${baseUrl}${cleanEndpoint}`;
  }

  async request(endpoint, { method = 'GET', body = null, headers = {} } = {}) {
    const config = {
      method,
      headers: { 
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...headers 
      },
      credentials: 'include' // Crucial para enviar CORESESSID
    };

    if (body) config.body = JSON.stringify(body);

    const response = await fetch(this._url(this.coreBaseUrl, endpoint), config);

    // Si recibimos 401 y no estamos en login, redirigir
    if (response.status === 401 && !window.location.pathname.includes('/login')) {
      window.location.href = '/login?error=session_expired';
      return;
    }

    if (!response.ok) {
      const errorData = await response.json().catch(() => ({}));
      throw new Error(errorData.message || errorData.error || `Error ${response.status}`);
    }

    return response.status !== 204 ? await response.json() : true;
  }

  get(endpoint) { return this.request(endpoint); }
  post(endpoint, body) { return this.request(endpoint, { method: 'POST', body }); }
  put(endpoint, body) { return this.request(endpoint, { method: 'PUT', body }); }
  delete(endpoint) { return this.request(endpoint, { method: 'DELETE' }); }

  async loginAuth(username, password) {
    const response = await fetch(this._url(this.authBaseUrl, '/login'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ username, password })
    });

    if (!response.ok) throw new Error('Credenciales inválidas');
    const data = await response.json();
    return data.token;
  }

  async establishSession(token) {
    return this.request('/login-by-token', {
      method: 'POST',
      headers: { 'Authorization': `Bearer ${token}` }
    });
  }

  async logout() {
    try { await this.get('/logout'); } finally {
      window.location.href = '/login';
    }
  }
}