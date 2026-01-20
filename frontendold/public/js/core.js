// core.js
export default class CoreAPI {
  constructor() {
    // URLs integradas
    this.authBaseUrl = 'http://localhost:8001';
    this.coreBaseUrl = 'http://localhost:8000';
    this.token = null;
  }

  // Método genérico para Core (usa sesión cookie)
  async request(endpoint, { method = 'GET', body = null } = {}) {
    const response = await fetch(`${this.coreBaseUrl}${endpoint}`, {
      method,
      headers: { 'Content-Type': 'application/json' },
      body: body ? JSON.stringify(body) : null,
      credentials: 'include' // usa la sesión creada en Core
    });

    if (!response.ok) {
      let errorMsg = 'Error en la petición';
      try {
        const error = await response.json();
        errorMsg = error.message || error.error || errorMsg;
      } catch (_) {}
      throw new Error(errorMsg);
    }

    return response.status !== 204 ? await response.json() : true;
  }

  // Métodos CRUD estándar
  get(endpoint) { return this.request(endpoint, { method: 'GET' }); }
  post(endpoint, body) { return this.request(endpoint, { method: 'POST', body }); }
  put(endpoint, body) { return this.request(endpoint, { method: 'PUT', body }); }
  delete(endpoint) { return this.request(endpoint, { method: 'DELETE' }); }

  // 🔐 Flujo de autenticación
  async loginAuth(username, password) {
    const response = await fetch(`${this.authBaseUrl}/login`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ username, password })
    });

    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.error || 'Error en Auth login');
    }

    const data = await response.json();
    this.token = data.token; // guardamos JWT
    return this.token;
  }

  async loginCoreWithToken() {
    if (!this.token) throw new Error('No hay token disponible. Primero llama a loginAuth.');

    const response = await fetch(`${this.coreBaseUrl}/login-by-token`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${this.token}`
      },
      credentials: 'include' // Core crea la sesión
    });

    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.error || 'Error en Core login con token');
    }

    return await response.json();
  }

  async logout() {
    await this.request('/logout', { method: 'GET' });
    this.token = null;
    return true;
  }
}
