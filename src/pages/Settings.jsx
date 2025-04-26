import React from 'react';
import { Card, Tabs } from 'antd';
import TwoFactorAuth from '../components/TwoFactorAuth/TwoFactorAuth';

const { TabPane } = Tabs;

const Settings = () => {
  return (
    <div className="settings-page">
      <Card title="Settings">
        <Tabs defaultActiveKey="2fa">
          <TabPane tab="Two-Factor Authentication" key="2fa">
            <TwoFactorAuth />
          </TabPane>
          {/* Thêm các tab settings khác ở đây */}
        </Tabs>
      </Card>
    </div>
  );
};

export default Settings;
