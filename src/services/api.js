// services/api.js
import axios from 'axios';

const BASE_URL = 'http://localhost/brainy/api';

const axiosInstance = axios.create({
  baseURL: BASE_URL,
  headers: {
    'Content-Type': 'application/json',
  },
});

// Cấu hình interceptor để thêm token
axiosInstance.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => Promise.reject(error)
);

// API endpoints
const api = {
  // Auth endpoints
  login: (data) => axiosInstance.post('/auth.php?action=login', data),
  register: (data) => axiosInstance.post('/auth.php?action=register', data),
  logout: () => axiosInstance.post('/auth.php?action=logout'),

  // OTP verification
  verifyOtp: (data) => axiosInstance.post('/auth.php?action=verify_otp', data),
  resendOtp: (data) => axiosInstance.post('/auth.php?action=resend_otp', data),

  // Password reset
  forgotPassword: (data) => axiosInstance.post('/auth.php?action=forgot_password', data),
  verifyResetToken: (token) => axiosInstance.get(`/auth.php?action=verify_reset_token&token=${token}`),
  resetPassword: (data) => axiosInstance.post('/auth.php?action=reset_password', data),

  // Các API endpoints khác...
};

export default api;
