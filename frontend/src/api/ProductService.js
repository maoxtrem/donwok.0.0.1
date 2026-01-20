const API_URL = 'http://localhost:8000';

const getAuthHeaders = () => {
  const token = localStorage.getItem('authToken');
  return {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'Authorization': `Bearer ${token}`
  };
};

const getAllProducts = async () => {
  try {
    const response = await fetch(`${API_URL}/productos`, {
      method: 'GET',
      headers: getAuthHeaders(),
    });

    if (!response.ok) {
      if (response.status === 401) {
        throw new Error('Sesión expirada');
      }
      throw new Error(`Error del servidor: ${response.status}`);
    }

    const data = await response.json();
    
    // Si la API de Symfony/API Platform devuelve un array directo o un objeto con miembros
    // Nos aseguramos de devolver siempre un array.
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
