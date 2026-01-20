/**
 * CoreAPI.js - Cliente optimizado con notificaciones SweetAlert2
 */
export default class CoreAPI {
  constructor() {
    this.authBaseUrl = `http://${window.location.hostname}:8001`;
    this.coreBaseUrl = `http://${window.location.hostname}:8000`;
  }

  _url(baseUrl, endpoint) {
    const cleanEndpoint = endpoint.startsWith('/') ? endpoint : `/${endpoint}`;
    return `${baseUrl}${cleanEndpoint}`;
  }

  /**
   * Notificación profesional usando SweetAlert2
   */
  notify(title, message, icon = 'info') {
    const Toast = Swal.mixin({
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 3000,
      timerProgressBar: true,
      didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer)
        toast.addEventListener('mouseleave', Swal.resumeTimer)
      }
    });

    Toast.fire({
      icon: icon,
      title: title,
      text: message
    });
  }

  async request(endpoint, { method = 'GET', body = null, headers = {} } = {}) {
    const config = {
      method,
      headers: { 
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...headers 
      },
      credentials: 'include'
    };

    if (body) config.body = JSON.stringify(body);

    try {
      const response = await fetch(this._url(this.coreBaseUrl, endpoint), config);
      const data = response.status !== 204 ? await response.json().catch(() => ({})) : { message: 'Operación exitosa' };

      // 1. Manejo de Sesión Expirada
      if (response.status === 401 && !window.location.pathname.includes('/login')) {
        this.notify('Sesión Expirada', 'Por favor, inicia sesión de nuevo', 'warning');
        setTimeout(() => window.location.href = '/login', 1500);
        return;
      }

      // 2. Manejo de Errores (4xx, 5xx)
      if (!response.ok) {
        const errorMsg = data.message || data.error || `Error ${response.status}`;
        this.notify('Error', errorMsg, 'error');
        throw new Error(errorMsg);
      }

      // 3. Notificación de Éxito (Para métodos que modifican data)
      if (['POST', 'PUT', 'DELETE'].includes(method)) {
        this.notify('Completado', data.message || 'La operación se realizó con éxito', 'success');
      }

      return data;
    } catch (error) {
      console.error(`[CoreAPI] ${method} ${endpoint}:`, error.message);
      throw error;
    }
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

    const data = await response.json().catch(() => ({}));
    if (!response.ok) {
      this.notify('Acceso Denegado', data.error || 'Credenciales incorrectas', 'error');
      throw new Error('Login failed');
    }
    return data.token;
  }

  async establishSession(token) {
    try {
      await this.request('/login-by-token', {
        method: 'POST',
        headers: { 'Authorization': `Bearer ${token}` }
      });
      this.notify('Bienvenido', 'Has iniciado sesión correctamente', 'success');
    } catch (e) {
      throw e;
    }
  }

  async logout() {
    try { 
      await this.get('/logout'); 
      this.notify('Adiós', 'Cerrando sesión...', 'info');
    } finally {
      window.location.href = '/login';
    }
  }
}
