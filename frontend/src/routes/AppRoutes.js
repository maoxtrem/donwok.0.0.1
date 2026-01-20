import React from 'react';
import { Routes, Route, Navigate } from 'react-router-dom';
import Login from '../pages/Login';
import Dashboard from '../pages/Dashboard';
import ProductsManagement from '../pages/ProductsManagement'; // Nueva importación
import PrivateRoute from '../components/PrivateRoute';

const AppRoutes = () => {
  return (
    <Routes>
      <Route path="/login" element={<Login />} />
      <Route 
        path="/dashboard" 
        element={
          <PrivateRoute>
            <Dashboard />
          </PrivateRoute>
        } 
      />
      <Route 
        path="/productos" 
        element={
          <PrivateRoute>
            <ProductsManagement />
          </PrivateRoute>
        } 
      />
      {/* Redireccionar raíz a /login por defecto */}
      <Route path="/" element={<Navigate replace to="/login" />} />
      
      {/* Capturar cualquier ruta desconocida y enviar a login */}
      <Route path="*" element={<Navigate replace to="/login" />} />
    </Routes>
  );
};

export default AppRoutes;
