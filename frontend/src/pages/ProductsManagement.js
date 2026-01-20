import React, { useState, useEffect } from 'react';
import ProductService from '../api/ProductService';
import './ProductsManagement.css';

function ProductsManagement() {
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showModal, setShowModal] = useState(false);
  const [editingProduct, setEditingProduct] = useState(null);
  const [formData, setFormData] = useState({
    nombre: '',
    precioActual: '',
    costoActual: '',
    activo: true
  });

  useEffect(() => {
    fetchProducts();
  }, []);

  const fetchProducts = async () => {
    try {
      setLoading(true);
      const data = await ProductService.getAllProducts();
      setProducts(data);
    } catch (err) {
      alert('Error al cargar productos');
    } finally {
      setLoading(false);
    }
  };

  const handleOpenModal = (product = null) => {
    if (product) {
      setEditingProduct(product);
      setFormData({
        nombre: product.nombre,
        precioActual: product.precioActual,
        costoActual: product.costoActual,
        activo: product.activo
      });
    } else {
      setEditingProduct(null);
      setFormData({ nombre: '', precioActual: '', costoActual: '', activo: true });
    }
    setShowModal(true);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    try {
      const dataToSave = {
        ...formData,
        precioActual: parseFloat(formData.precioActual),
        costoActual: parseFloat(formData.costoActual)
      };

      if (editingProduct) {
        await ProductService.updateProduct(editingProduct.id, dataToSave);
      } else {
        await ProductService.createProduct(dataToSave);
      }
      setShowModal(false);
      fetchProducts();
    } catch (err) {
      alert('Error al guardar el producto');
    }
  };

  const handleDelete = async (id) => {
    if (window.confirm('¿Estás seguro de eliminar este producto?')) {
      try {
        await ProductService.deleteProduct(id);
        fetchProducts();
      } catch (err) {
        alert('Error al eliminar');
      }
    }
  };

  return (
    <div className="management-container">
      <div className="management-header">
        <h2 className="management-title">Gestión de Productos</h2>
        <button className="btn-add" onClick={() => handleOpenModal()}>
          + Nuevo Producto
        </button>
      </div>

      {loading ? (
        <div className="text-center p-5">Cargando...</div>
      ) : (
        <div className="table-container">
          <table className="custom-table">
            <thead>
              <tr>
                <th>Nombre</th>
                <th>Costo</th>
                <th>Precio Venta</th>
                <th>Estado</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              {products.map((p) => (
                <tr key={p.id}>
                  <td className="fw-bold">{p.nombre}</td>
                  <td>${parseFloat(p.costoActual).toFixed(2)}</td>
                  <td className="text-primary fw-bold">${parseFloat(p.precioActual).toFixed(2)}</td>
                  <td>
                    <span className={p.activo ? 'badge-active' : 'badge-inactive'}>
                      {p.activo ? 'Activo' : 'Inactivo'}
                    </span>
                  </td>
                  <td>
                    <div className="action-btns">
                      <button className="btn-edit" onClick={() => handleOpenModal(p)}>Editar</button>
                      <button className="btn-delete" onClick={() => handleDelete(p.id)}>Borrar</button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {showModal && (
        <div className="modal-overlay">
          <div className="modal-content">
            <h3>{editingProduct ? 'Editar Producto' : 'Nuevo Producto'}</h3>
            <form onSubmit={handleSubmit}>
              <div className="form-group">
                <label>Nombre del Producto</label>
                <input 
                  type="text" 
                  className="form-control" 
                  value={formData.nombre}
                  onChange={(e) => setFormData({...formData, nombre: e.target.value})}
                  required
                />
              </div>
              <div className="row">
                <div className="col-6">
                  <div className="form-group">
                    <label>Costo</label>
                    <input 
                      type="number" step="0.01" 
                      className="form-control" 
                      value={formData.costoActual}
                      onChange={(e) => setFormData({...formData, costoActual: e.target.value})}
                      required
                    />
                  </div>
                </div>
                <div className="col-6">
                  <div className="form-group">
                    <label>Precio Venta</label>
                    <input 
                      type="number" step="0.01" 
                      className="form-control" 
                      value={formData.precioActual}
                      onChange={(e) => setFormData({...formData, precioActual: e.target.value})}
                      required
                    />
                  </div>
                </div>
              </div>
              <div className="form-group form-check">
                <input 
                  type="checkbox" 
                  className="form-check-input" 
                  id="activoCheck"
                  checked={formData.activo}
                  onChange={(e) => setFormData({...formData, activo: e.target.checked})}
                />
                <label className="form-check-label" htmlFor="activoCheck">Producto Activo</label>
              </div>
              <div className="d-flex gap-2 justify-content-end mt-4">
                <button type="button" className="btn-cancel" onClick={() => setShowModal(false)}>Cancelar</button>
                <button type="submit" className="btn-save">Guardar Cambios</button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}

export default ProductsManagement;
