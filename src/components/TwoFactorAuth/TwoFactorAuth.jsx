import React, { useState, useEffect } from 'react';
import { Button, Card, Input, message, Steps, QRCode, Alert, Typography } from 'antd';
import twoFactorAuthService from '../../services/twoFactorAuthService';

const { Title, Text } = Typography;
const { Step } = Steps;

const TwoFactorAuth = () => {
  const [current, setCurrent] = useState(0);
  const [loading, setLoading] = useState(false);
  const [status, setStatus] = useState(null);
  const [setupData, setSetupData] = useState(null);
  const [verificationCode, setVerificationCode] = useState('');
  const [backupCodes, setBackupCodes] = useState([]);

  useEffect(() => {
    checkStatus();
  }, []);

  const checkStatus = async () => {
    try {
      const response = await twoFactorAuthService.getStatus();
      setStatus(response.data);
    } catch (error) {
      message.error(`Failed to check 2FA status: ${error.message}`);
    }
  };

  const startSetup = async () => {
    try {
      setLoading(true);
      const response = await twoFactorAuthService.getSetupData();
      setSetupData(response.data);
      setCurrent(1);
    } catch (error) {
      message.error(`Failed to start 2FA setup: ${error.message}`);
    } finally {
      setLoading(false);
    }
  };

  const verifyAndEnable = async () => {
    try {
      setLoading(true);
      await twoFactorAuthService.enable2FA(verificationCode);
      const backupResponse = await twoFactorAuthService.getBackupCodes();
      setBackupCodes(backupResponse.data.codes);
      setCurrent(2);
      message.success('2FA has been enabled successfully');
    } catch (error) {
      message.error(`Failed to verify code: ${error.message}`);
    } finally {
      setLoading(false);
    }
  };

  const disable2FA = async () => {
    try {
      setLoading(true);
      await twoFactorAuthService.disable2FA(verificationCode);
      message.success('2FA has been disabled');
      checkStatus();
    } catch (error) {
      message.error(`Failed to disable 2FA: ${error.message}`);
    } finally {
      setLoading(false);
    }
  };

  const steps = [
    {
      title: 'Start',
      content: (
        <div>
          <Title level={4}>Two-Factor Authentication Setup</Title>
          <Text>Enhance your account security by enabling two-factor authentication. You'll need an authenticator app like Google Authenticator or Authy.</Text>
          <Button type="primary" onClick={startSetup} loading={loading}>
            Start Setup
          </Button>
        </div>
      ),
    },
    {
      title: 'Setup',
      content: setupData && (
        <div>
          <Title level={4}>Scan QR Code</Title>
          <QRCode value={setupData.qrCodeUrl} />
          <Text>Secret key: {setupData.secretKey}</Text>
          <Input placeholder="Enter 6-digit code" value={verificationCode} onChange={(e) => setVerificationCode(e.target.value)} style={{ marginTop: 16 }} />
          <Button type="primary" onClick={verifyAndEnable} loading={loading}>
            Verify and Enable
          </Button>
        </div>
      ),
    },
    {
      title: 'Backup Codes',
      content: (
        <div>
          <Title level={4}>Save Your Backup Codes</Title>
          <Alert message="Important" description="Save these backup codes in a secure place. You'll need them if you lose access to your authenticator app." type="warning" showIcon />
          <Card style={{ marginTop: 16 }}>
            {backupCodes.map((code, index) => (
              <Text key={index} copyable style={{ display: 'block' }}>
                {code}
              </Text>
            ))}
          </Card>
          <Button type="primary" onClick={() => window.print()} style={{ marginTop: 16 }}>
            Print Backup Codes
          </Button>
        </div>
      ),
    },
  ];

  if (status?.enabled) {
    return (
      <Card>
        <Alert message="2FA is enabled" description="Two-factor authentication is currently active on your account." type="success" showIcon style={{ marginBottom: 16 }} />
        <Input placeholder="Enter verification code to disable 2FA" value={verificationCode} onChange={(e) => setVerificationCode(e.target.value)} style={{ marginBottom: 16 }} />
        <Button danger onClick={disable2FA} loading={loading}>
          Disable 2FA
        </Button>
      </Card>
    );
  }

  return (
    <Card>
      <Steps current={current} items={steps} style={{ marginBottom: 24 }} />
      <div>{steps[current].content}</div>
    </Card>
  );
};

export default TwoFactorAuth;
