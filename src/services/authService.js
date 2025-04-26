// src/services/authService.js
import axios from 'axios';
import { API_URL } from '../config';

class AuthService {
  async login(credentials) {
    const response = await axios.post(`${API_URL}/auth/login`, credentials);
    return response;
  }

  async register(userData) {
    const response = await axios.post(`${API_URL}/auth/register`, userData);
    return response;
  }

  async forgotPassword(email) {
    const response = await axios.post(`${API_URL}/auth/forgot-password`, { email });
    return response;
  }

  async resetPassword(token, newPassword) {
    const response = await axios.post(`${API_URL}/auth/reset-password`, {
      token,
      newPassword,
    });
    return response;
  }

  async logout() {
    localStorage.removeItem('token');
    // Thêm logic xóa token ở server nếu cần
  }

  getToken() {
    return localStorage.getItem('token');
  }

  isAuthenticated() {
    return !!this.getToken();
  }
}

export default new AuthService();
