const API_URL = '/api/core';

const getBaseHeaders = () => ({
  'Content-Type': 'application/json',
  'Accept': 'application/json',
});

const getAllProducts = async () => {
  const response = await fetch(`${API_URL}/productos`, {
    method: 'GET',
    headers: getBaseHeaders(),
    credentials: 'include',
  });
  if (!response.ok) throw new Error('Sesión expirada o error de red');
  return await response.json();
};

const createProduct = async (productData) => {
  const response = await fetch(`${API_URL}/productos`, {
    method: 'POST',
    headers: getBaseHeaders(),
    credentials: 'include',
    body: JSON.stringify(productData),
  });
  if (!response.ok) throw new Error('Error al crear producto');
  return await response.json();
};

const updateProduct = async (id, productData) => {
  const response = await fetch(`${API_URL}/productos/${id}`, {
    method: 'PUT',
    headers: getBaseHeaders(),
    credentials: 'include',
    body: JSON.stringify(productData),
  });
  if (!response.ok) throw new Error('Error al actualizar producto');
  return await response.json();
};

const deleteProduct = async (id) => {
  const response = await fetch(`${API_URL}/productos/${id}`, {
    method: 'DELETE',
    headers: getBaseHeaders(),
    credentials: 'include',
  });
  if (!response.ok) throw new Error('Error al eliminar producto');
  return true;
};

const ProductService = { getAllProducts, createProduct, updateProduct, deleteProduct };
export default ProductService;
