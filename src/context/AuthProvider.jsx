// src/context/AuthProvider.jsx
import { useState, useEffect } from 'react';
import { authService } from '../services/authService';
import { AuthContext } from './AuthContext';

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    // Check if user is logged in
    const storedUser = localStorage.getItem('user');
    if (storedUser) {
      setUser(JSON.parse(storedUser));
    }
    setLoading(false);
  }, []);

  const login = async (email, password) => {
    try {
      setLoading(true);
      const data = await authService.login(email, password);

      localStorage.setItem('token', data.token);
      localStorage.setItem('refreshToken', data.refresh_token);
      localStorage.setItem('user', JSON.stringify(data.user));

      setUser(data.user);
      setError(null);
      return true;
    } catch (err) {
      setError(err.response?.data?.message || 'Đăng nhập thất bại');
      return false;
    } finally {
      setLoading(false);
    }
  };

  const register = async (userData) => {
    try {
      setLoading(true);
      const data = await authService.register(userData);

      localStorage.setItem('token', data.token);
      localStorage.setItem('refreshToken', data.refresh_token);
      localStorage.setItem('user', JSON.stringify(data.user));

      setUser(data.user);
      setError(null);
      return true;
    } catch (err) {
      setError(err.response?.data?.message || 'Đăng ký thất bại');
      return false;
    } finally {
      setLoading(false);
    }
  };

  const logout = () => {
    authService.logout();
    setUser(null);
  };

  const value = {
    user,
    loading,
    error,
    login,
    register,
    logout,
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
};
