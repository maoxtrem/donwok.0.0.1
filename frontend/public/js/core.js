/**
 * CoreAPI.js - Cliente Centralizado (Gateway Mode)
 */
export default class CoreAPI {
  constructor() {
    // 🟢 Todo bajo el mismo dominio y puerto 80
    this.authBaseUrl = '/api/auth';
    this.coreBaseUrl = '/api/core';
    this.mercureUrl  = '/.well-known/mercure';
  }

  /**
   * Muestra notificaciones visuales usando SweetAlert2
   */
  notify(icon, title, text = '') {
    if (typeof Swal !== 'undefined') {
      Swal.fire({
        icon,
        title,
        text,
        timer: icon === 'success' ? 2000 : 5000,
        showConfirmButton: icon !== 'success',
        toast: icon === 'success',
        position: icon === 'success' ? 'top-end' : 'center'
      });
    } else {
      console.log(`[${icon.toUpperCase()}] ${title}: ${text}`);
    }
  }

  async request(endpoint, { method = 'GET', body = null, headers = {} } = {}) {
    const config = {
      method,
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', ...headers },
      credentials: 'include'
    };

    if (body) config.body = JSON.stringify(body);

    try {
      const response = await fetch(`${this.coreBaseUrl}${endpoint.startsWith('/') ? endpoint : '/'+endpoint}`, config);

      if (response.status === 401 && !window.location.pathname.includes('/login')) {
        this.notify('warning', 'Sesión expirada', 'Por favor, inicie sesión de nuevo.');
        setTimeout(() => window.location.href = '/login', 2000);
        return;
      }

      if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        const errorMsg = errorData.message || errorData.error || `Error ${response.status}`;
        this.notify('error', 'Error en la operación', errorMsg);
        throw new Error(errorMsg);
      }

      const data = response.status !== 204 ? await response.json() : true;
      
      // Notificar éxito en operaciones de escritura
      if (['POST', 'PUT', 'DELETE'].includes(method) && !endpoint.includes('login')) {
        this.notify('success', data.message || 'Operación completada');
      }

      return data;
    } catch (err) {
      if (!(err instanceof Error)) {
        this.notify('error', 'Error de red', 'No se pudo contactar con el servidor.');
      }
      throw err;
    }
  }

  get(endpoint) { return this.request(endpoint); }
  post(endpoint, body) { return this.request(endpoint, { method: 'POST', body }); }
  put(endpoint, body) { return this.request(endpoint, { method: 'PUT', body }); }
  delete(endpoint) { return this.request(endpoint, { method: 'DELETE' }); }

  async loginAuth(username, password) {
    try {
      const response = await fetch(`${this.authBaseUrl}/login`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ username, password })
      });

      if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(errorData.error || 'Credenciales inválidas');
      }

      const data = await response.json();
      return data.token;
    } catch (err) {
      this.notify('error', 'Error de Autenticación', err.message);
      throw err;
    }
  }

  async establishSession(token) {
    const res = await this.request('/login-by-token', {
      method: 'POST',
      headers: { 'Authorization': `Bearer ${token}` }
    });
    this.notify('success', 'Bienvenido', 'Sesión iniciada correctamente');
    return res;
  }

  async logout() {
    try { 
      await this.get('/logout'); 
    } finally { 
      window.location.href = '/login'; 
    }
  }
}
