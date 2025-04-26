// src/components/auth/LoginForm.jsx
import React, { useState } from 'react';
import { Form, Input, Button, Card, message } from 'antd';
import { UserOutlined, LockOutlined } from '@ant-design/icons';
import { useNavigate } from 'react-router-dom';
import authService from '../../services/authService';
import TwoFactorVerification from '../TwoFactorAuth/TwoFactorVerification';

const LoginForm = () => {
  const [loading, setLoading] = useState(false);
  const [show2FAModal, setShow2FAModal] = useState(false);
  const [tempToken, setTempToken] = useState(null);
  const navigate = useNavigate();

  const onFinish = async (values) => {
    try {
      setLoading(true);
      const response = await authService.login(values);

      if (response.data.requires2FA) {
        // Nếu tài khoản có bật 2FA, lưu token tạm thời và hiện modal xác thực
        setTempToken(response.data.tempToken);
        setShow2FAModal(true);
      } else {
        // Nếu không có 2FA, login bình thường
        handleLoginSuccess(response.data.token);
      }
    } catch (error) {
      message.error(error.response?.data?.message || 'Login failed');
    } finally {
      setLoading(false);
    }
  };

  const handleLoginSuccess = (token) => {
    localStorage.setItem('token', token);
    message.success('Login successful');
    navigate('/dashboard');
  };

  const handle2FASuccess = () => {
    handleLoginSuccess(tempToken);
    setShow2FAModal(false);
  };

  return (
    <>
      <Card title="Login" className="login-card">
        <Form name="login" onFinish={onFinish} autoComplete="off" layout="vertical">
          <Form.Item
            name="email"
            rules={[
              { required: true, message: 'Please input your email!' },
              { type: 'email', message: 'Please enter a valid email!' },
            ]}
          >
            <Input prefix={<UserOutlined />} placeholder="Email" size="large" />
          </Form.Item>

          <Form.Item
            name="password"
            rules={[
              { required: true, message: 'Please input your password!' },
              { min: 6, message: 'Password must be at least 6 characters!' },
            ]}
          >
            <Input.Password prefix={<LockOutlined />} placeholder="Password" size="large" />
          </Form.Item>

          <Form.Item>
            <Button type="primary" htmlType="submit" loading={loading} block size="large">
              Log in
            </Button>
          </Form.Item>

          <div className="form-footer">
            <Button type="link" onClick={() => navigate('/forgot-password')}>
              Forgot password?
            </Button>
            <Button type="link" onClick={() => navigate('/register')}>
              Register now
            </Button>
          </div>
        </Form>
      </Card>

      <TwoFactorVerification visible={show2FAModal} onSuccess={handle2FASuccess} onCancel={() => setShow2FAModal(false)} />
    </>
  );
};

export default LoginForm;
