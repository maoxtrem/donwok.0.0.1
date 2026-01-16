// auth.js
export default class Auth {
  constructor({ authBaseUrl, coreBaseUrl }) {
    this.authBaseUrl = authBaseUrl; // ejemplo: 'http://localhost:8001'
    this.coreBaseUrl = coreBaseUrl; // ejemplo: 'http://localhost:8000'
    this.token = null;
  }

  // 1️⃣ Login al Auth Service y obtener token
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
    this.token = data.token; // Guardamos el JWT
    return data.token;
  }

  // 2️⃣ Enviar token al Core para crear sesión
  async loginCoreWithToken() {
    if (!this.token) {
      throw new Error('No hay token disponible. Primero llama a loginAuth.');
    }

    const response = await fetch(`${this.coreBaseUrl}/login-by-token`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${this.token}`
      }
    });

    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.error || 'Error en Core login con token');
    }

    return await response.json();
  }

  // 3️⃣ Obtener información del usuario desde Core
  async me() {
    const response = await fetch(`${this.coreBaseUrl}/me`, {
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include' // Importante si Core usa sesión cookie
    });

    if (!response.ok) {
      throw new Error('No autorizado o error al obtener /me');
    }

    return await response.json();
  }

  // 4️⃣ Logout del Core
  async logout() {
    const response = await fetch(`${this.coreBaseUrl}/logout`, {
      method: 'GET',
      credentials: 'include'
    });

    if (!response.ok) {
      throw new Error('Error al cerrar sesión');
    }

    this.token = null; // Limpiamos token local
    return true;
  }
}
