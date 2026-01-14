// public/js/auth.js

const CORE_BASE_URL = 'http://localhost:8000'; // core-dev

/**
 * LOGIN
 */
export async function login(username, password) {
    const response = await fetch(`${CORE_BASE_URL}/login`, {
        method: 'POST',
        credentials: 'include', // 🔥 sesión
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ username, password }),
    });

    if (!response.ok) {
        const error = await response.json();
        throw new Error(error.error || 'Login failed');
    }

    return await response.json();
}

/**
 * OBTENER USUARIO AUTENTICADO
 */
export async function getCurrentUser() {
    const response = await fetch(`${CORE_BASE_URL}/me`, {
        method: 'GET',
        credentials: 'include',
    });

    if (response.status === 401) {
        return null;
    }

    if (!response.ok) {
        throw new Error('Error fetching user');
    }

    return await response.json();
}

/**
 * LOGOUT
 */
export async function logout() {
    await fetch(`${CORE_BASE_URL}/logout`, {
        method: 'POST',
        credentials: 'include',
    });
}

/**
 * PROTECCIÓN DE RUTAS (frontend)
 */
export async function requireAuth(redirectTo = '/login') {
    const user = await getCurrentUser();

    if (!user) {
        window.location.href = redirectTo;
        return null;
    }

    return user;
}
