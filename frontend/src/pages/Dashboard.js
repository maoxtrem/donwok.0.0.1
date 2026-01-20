import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import ProductService from '../api/ProductService';
import './Dashboard.css';

function Dashboard() {
  const [products, setProducts] = useState([]);
  const [cart, setCart] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  
  const { logout, user } = useAuth();
  const navigate = useNavigate();

  // Cargar productos al iniciar
  useEffect(() => {
    const fetchProducts = async () => {
      try {
        const data = await ProductService.getAllProducts();
        setProducts(data);
      } catch (err) {
        console.error(err);
        setError(err.message);
        if (err.message.includes('Sesión expirada')) {
           handleLogout();
        }
      } finally {
        setLoading(false);
      }
    };

    fetchProducts();
  }, []);

  const handleLogout = () => {
    logout();
    navigate('/login');
  };

  // --- Lógica del Carrito ---

  const addToCart = (product) => {
    setCart((prevCart) => {
      const existingItem = prevCart.find((item) => item.id === product.id);
      
      if (existingItem) {
        // Si ya existe, incrementamos cantidad
        return prevCart.map((item) =>
          item.id === product.id
            ? { ...item, quantity: item.quantity + 1 }
            : item
        );
      } else {
        // Si no existe, lo agregamos con cantidad 1
        return [...prevCart, { ...product, quantity: 1 }];
      }
    });
  };

  const removeFromCart = (productId) => {
    setCart((prevCart) => {
      const existingItem = prevCart.find((item) => item.id === productId);

      if (existingItem.quantity === 1) {
        // Si queda 1, lo eliminamos del array
        return prevCart.filter((item) => item.id !== productId);
      } else {
        // Si hay más de 1, restamos cantidad
        return prevCart.map((item) =>
          item.id === productId
            ? { ...item, quantity: item.quantity - 1 }
            : item
        );
      }
    });
  };

  const calculateTotal = () => {
    return cart.reduce((total, item) => total + (item.precioActual * item.quantity), 0);
  };

  if (loading) {
    return (
      <div className="dashboard-container">
        <div className="spinner-container">
           <div className="spinner-border" role="status">
             <span className="visually-hidden">Cargando productos...</span>
           </div>
        </div>
      </div>
    );
  }

  return (
    <div className="dashboard-container">
      {/* Panel Izquierdo: Menú de Productos */}
      <div className="products-section">
        <div className="section-header">
           <h2>Menú</h2>
           {error && <div className="alert alert-danger">{error}</div>}
        </div>

        <div className="products-grid">
          {products.map((product) => (
            <div 
              key={product.id} 
              className="product-card"
              onClick={() => addToCart(product)}
            >
              <div className="product-card-body">
                <div className="product-name">{product.nombre}</div>
                <div className="product-price">${parseFloat(product.precioActual).toFixed(2)}</div>
                {/* Indicador visual si ya está en el carrito */}
                {cart.find(item => item.id === product.id) && (
                   <div className="badge bg-primary mt-2">
                     En pedido: {cart.find(item => item.id === product.id).quantity}
                   </div>
                )}
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* Panel Derecho: Factura / Pedido */}
      <div className="invoice-section">
        <div className="invoice-header">
          <div>
            <h3>Pedido Actual</h3>
            <small>Cajero: {user?.username || 'Admin'}</small>
          </div>
          <button onClick={handleLogout} className="btn-logout">
            Salir
          </button>
        </div>

        <div className="invoice-body">
          {cart.length === 0 ? (
            <div className="empty-cart">
              <p>Selecciona productos del menú para comenzar una orden.</p>
            </div>
          ) : (
            <table className="invoice-items-table">
              <thead>
                <tr>
                  <th style={{width: '50%'}}>Producto</th>
                  <th style={{width: '25%'}}>Cant.</th>
                  <th style={{width: '25%', textAlign: 'right'}}>Total</th>
                </tr>
              </thead>
              <tbody>
                {cart.map((item) => (
                  <tr key={item.id} className="invoice-row">
                    <td>
                      <span className="item-name">{item.nombre}</span>
                      <span className="item-unit-price">${parseFloat(item.precioActual).toFixed(2)} c/u</span>
                    </td>
                    <td>
                      <div className="qty-controls">
                        <button 
                          className="btn-qty" 
                          onClick={() => removeFromCart(item.id)}
                        >-</button>
                        <span>{item.quantity}</span>
                        <button 
                          className="btn-qty" 
                          onClick={() => addToCart(item)}
                        >+</button>
                      </div>
                    </td>
                    <td className="item-total">
                      ${(item.precioActual * item.quantity).toFixed(2)}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </div>

        <div className="invoice-footer">
          <div className="total-row">
            <span className="total-label">Total</span>
            <span className="total-amount">${calculateTotal().toFixed(2)}</span>
          </div>
          <button 
            className="btn-process" 
            disabled={cart.length === 0}
            onClick={() => alert('¡Funcionalidad de Crear Pedido en desarrollo!')}
          >
            Crear Pedido
          </button>
        </div>
      </div>
    </div>
  );
}

export default Dashboard;