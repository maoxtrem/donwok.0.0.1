const API_URL_AUTH = 'http://localhost:8001';
const API_URL_CORE = 'http://localhost:8000';

const login = async (username, password) => {
  // 1. Autenticación en el servicio de Auth
  const response = await fetch(`${API_URL_AUTH}/login`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ username, password }),
    credentials: 'include', // Para manejar sesiones/cookies
  });

  if (!response.ok) {
    throw new Error('Autenticación fallida en Auth Service.');
  }

  const { token } = await response.json();
  
  // 2. Notificar al Core Service para que inicie su propia sesión
  const coreResponse = await fetch(`${API_URL_CORE}/login-by-token`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`, // El token solo se usa aquí para "vincular" la sesión
    },
    credentials: 'include',
  });

  if (!coreResponse.ok) {
    throw new Error('No se pudo establecer sesión en Core Service.');
  }

  // Guardamos algo mínimo para saber que estamos logueados en el estado de React
  localStorage.setItem('isLoggedIn', 'true');
  return { success: true };
};

const logout = async () => {
  try {
    await fetch(`${API_URL_CORE}/logout`, {
      method: 'GET',
      credentials: 'include',
    });
  } catch (e) {
    console.error("Error al cerrar sesión", e);
  }
  localStorage.removeItem('isLoggedIn');
};

const AuthService = {
  login,
  logout,
};

export default AuthService;