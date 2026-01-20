import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';

function Login() {
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const navigate = useNavigate();

  const handleLogin = async (e) => {
    e.preventDefault();
    setError('');

    try {
      // First, get the token from the auth service
      const authResponse = await fetch('http://localhost:8001/login', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ username, password }),
      });

      if (!authResponse.ok) {
        throw new Error('Authentication failed. Check username and password.');
      }

      const { token } = await authResponse.json();

      // Then, use the token to log in to the core service
      const coreResponse = await fetch('http://localhost:8000/login-by-token', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
        },
      });

      if (!coreResponse.ok) {
        throw new Error('Core service login failed.');
      }
      
      // Store the token and navigate to the dashboard
      localStorage.setItem('authToken', token);
      navigate('/dashboard');
    } catch (err) {
      setError(err.message);
    }
  };

  const styles = {
    container: {
      display: 'flex',
      justifyContent: 'center',
      alignItems: 'center',
      height: '100vh',
      backgroundColor: '#1a1a2e',
    },
    formContainer: {
      padding: '40px',
      borderRadius: '10px',
      backgroundColor: '#162447',
      boxShadow: '0 4px 20px rgba(0, 0, 0, 0.5)',
      color: '#e4e4e4',
      width: '350px',
    },
    title: {
      textAlign: 'center',
      marginBottom: '30px',
      color: '#f0f0f0',
      fontSize: '24px',
    },
    formGroup: {
      marginBottom: '20px',
    },
    label: {
      display: 'block',
      marginBottom: '8px',
      fontSize: '14px',
      color: '#a0a0a0',
    },
    input: {
      width: '100%',
      padding: '12px',
      border: '1px solid #333',
      borderRadius: '5px',
      backgroundColor: '#1f2a52',
      color: '#e4e4e4',
      fontSize: '16px',
    },
    button: {
      width: '100%',
      padding: '12px',
      border: 'none',
      borderRadius: '5px',
      backgroundColor: '#1b98e0',
      color: 'white',
      fontSize: '16px',
      cursor: 'pointer',
      marginTop: '10px',
    },
    error: {
      color: '#ff6b6b',
      textAlign: 'center',
      marginBottom: '20px',
    },
  };

  return (
    <div style={styles.container}>
      <div style={styles.formContainer}>
        <h2 style={styles.title}>Login</h2>
        <form onSubmit={handleLogin}>
          <div style={styles.formGroup}>
            <label style={styles.label}>Username:</label>
            <input
              type="text"
              value={username}
              onChange={(e) => setUsername(e.target.value)}
              style={styles.input}
            />
          </div>
          <div style={styles.formGroup}>
            <label style={styles.label}>Password:</label>
            <input
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              style={styles.input}
            />
          </div>
          {error && <p style={styles.error}>{error}</p>}
          <button type="submit" style={styles.button}>Login</button>
        </form>
      </div>
    </div>
  );
}

export default Login;
