// src/pages/TwoFactorAuthPage.jsx
import React from 'react';
import { Card, Breadcrumb } from 'antd';
import { Link } from 'react-router-dom';
import { HomeOutlined, LockOutlined } from '@ant-design/icons';
import TwoFactorAuth from '../components/TwoFactorAuth/TwoFactorAuth';

const TwoFactorAuthPage = () => {
  return (
    <div className="container mx-auto px-4 py-6">
      {/* Breadcrumb navigation */}
      <Breadcrumb className="mb-6">
        <Breadcrumb.Item>
          <Link to="/">
            <HomeOutlined /> Home
          </Link>
        </Breadcrumb.Item>
        <Breadcrumb.Item>
          <Link to="/settings">
            <LockOutlined /> Security Settings
          </Link>
        </Breadcrumb.Item>
        <Breadcrumb.Item>Two-Factor Authentication</Breadcrumb.Item>
      </Breadcrumb>

      {/* Main content */}
      <Card
        title="Two-Factor Authentication"
        className="shadow-md"
        extra={
          <Link to="/settings" className="text-blue-600">
            Back to Settings
          </Link>
        }
      >
        <div className="max-w-2xl mx-auto">
          <div className="mb-6">
            <h2 className="text-lg font-medium mb-2">Enhance Your Account Security</h2>
            <p className="text-gray-600">
              Two-factor authentication adds an extra layer of security to your account. In addition to your password, you'll need to enter a code from your authenticator app when signing in.
            </p>
          </div>

          {/* 2FA Component */}
          <TwoFactorAuth />
        </div>
      </Card>
    </div>
  );
};

export default TwoFactorAuthPage;
