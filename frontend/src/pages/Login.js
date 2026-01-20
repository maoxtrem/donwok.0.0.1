import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import './Login.css'; // Importamos los estilos personalizados

function Login() {
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false); // Estado de carga
  const navigate = useNavigate();
  const { login } = useAuth();

  const handleLogin = async (e) => {
    e.preventDefault();
    setError('');
    
    // Validación básica frontend
    if (!username.trim() || !password.trim()) {
      setError('Por favor, completa todos los campos.');
      return;
    }

    setLoading(true);

    try {
      await login(username, password);
      navigate('/dashboard');
    } catch (err) {
      setError(err.message || 'Error al iniciar sesión. Verifica tus credenciales.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="login-container">
      {/* Decoración de fondo */}
      <div className="login-bg-decoration">
        <div className="circle circle-1"></div>
        <div className="circle circle-2"></div>
      </div>

      <div className="login-card fade-in">
        <div className="text-center mb-4">
            {/* Aquí podrías poner tu logo <img src={logo} alt="Logo" height="50" /> */}
            <h2 className="login-title">DonWok Access</h2>
            <p className="text-muted small">Ingresa tus credenciales para continuar</p>
        </div>
          
        <form onSubmit={handleLogin}>
          {/* Input Usuario */}
          <div className="mb-4">
            <label htmlFor="username" className="input-label">Usuario</label>
            <input
              id="username"
              type="text"
              className="form-control custom-input"
              placeholder="nombre@empresa.com"
              value={username}
              onChange={(e) => setUsername(e.target.value)}
              disabled={loading}
              autoFocus
            />
          </div>

          {/* Input Password */}
          <div className="mb-4">
            <label htmlFor="password" className="input-label">Contraseña</label>
            <input
              id="password"
              type="password"
              className="form-control custom-input"
              placeholder="••••••••"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              disabled={loading}
            />
          </div>

          {/* Mensaje de Error */}
          {error && (
            <div className="alert alert-danger text-center p-2 mb-4 small shadow-sm" role="alert">
              {error}
            </div>
          )}

          {/* Botón Submit */}
          <button type="submit" className="btn btn-login w-100 btn-lg" disabled={loading}>
            {loading ? (
              <span>
                <span className="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                Validando...
              </span>
            ) : (
              'Iniciar Sesión'
            )}
          </button>
        </form>
      </div>
    </div>
  );
}

export default Login;
