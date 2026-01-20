const API_URL_AUTH = '/api/auth';
const API_URL_CORE = '/api/core';

const login = async (username, password) => {
  const response = await fetch(`${API_URL_AUTH}/login`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ username, password }),
    credentials: 'include',
  });

  if (!response.ok) throw new Error('Autenticación fallida.');

  const { token } = await response.json();
  
  const coreResponse = await fetch(`${API_URL_CORE}/login-by-token`, {
    method: 'POST',
    headers: { 'Authorization': `Bearer ${token}` },
    credentials: 'include',
  });

  if (!coreResponse.ok) throw new Error('Sesión en Core fallida.');

  localStorage.setItem('isLoggedIn', 'true');
  return { success: true };
};

const logout = async () => {
  try {
    await fetch(`${API_URL_CORE}/logout`, {
      method: 'GET',
      credentials: 'include',
    });
  } catch (e) {}
  localStorage.removeItem('isLoggedIn');
};

const AuthService = { login, logout };
export default AuthService;
