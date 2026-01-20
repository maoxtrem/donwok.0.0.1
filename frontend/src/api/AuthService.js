const API_URL_AUTH = 'http://localhost:8001';
const API_URL_CORE = 'http://localhost:8000';

const login = async (username, password) => {
  const response = await fetch(`${API_URL_AUTH}/login`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ username, password }),
  });

  if (!response.ok) {
    throw new Error('Authentication failed. Check username and password.');
  }

  const { token } = await response.json();
  
  const coreResponse = await fetch(`${API_URL_CORE}/login-by-token`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
    },
  });

  if (!coreResponse.ok) {
    throw new Error('Core service login failed.');
  }

  localStorage.setItem('authToken', token);
  return { token };
};

const logout = () => {
  const token = localStorage.getItem('authToken');
  if (token) {
    fetch(`${API_URL_CORE}/logout`, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
      },
    });
  }
  localStorage.removeItem('authToken');
};

const AuthService = {
  login,
  logout,
};

export default AuthService;
