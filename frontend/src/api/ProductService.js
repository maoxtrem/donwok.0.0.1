const API_URL = 'http://localhost:8000';

const getBaseHeaders = () => {
  return {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  };
};

const getAllProducts = async () => {
  try {
    const response = await fetch(`${API_URL}/productos`, {
      method: 'GET',
      headers: getBaseHeaders(),
      credentials: 'include', // Clave para que el navegador envíe la cookie de sesión automáticamente
    });

    if (!response.ok) {
      if (response.status === 401) {
        throw new Error('Sesión expirada');
      }
      throw new Error(`Error del servidor: ${response.status}`);
    }

    const data = await response.json();
    return Array.isArray(data) ? data : (data['hydra:member'] || data.items || []);
  } catch (error) {
    console.error("Error en ProductService.getAllProducts:", error);
    throw error;
  }
};

const ProductService = {
  getAllProducts,
};

export default ProductService;