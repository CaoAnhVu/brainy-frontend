import { Routes, Route } from 'react-router-dom';
import { Toaster } from 'react-hot-toast';
import MainLayout from './components/layout/MainLayout';
import AuthLayout from './components/layout/AuthLayout';
import HomePage from './pages/HomePage';
import LoginPage from './pages/LoginPage';
import RegisterPage from './pages/RegisterPage';
import FlashcardPage from './pages/FlashcardPage';
import TopicsPage from './pages/TopicsPage';
import QuizPage from './pages/QuizPage';
import NotFoundPage from './pages/NotFoundPage';
import { AuthProvider } from './context/AuthProvider';
import ForgotPasswordPage from './pages/ForgotPasswordPage';
import ResetPasswordPage from './pages/ResetPasswordPage';
import OTPVerificationPage from './pages/OTPVerificationPage';
import TwoFactorAuthPage from './pages/TwoFactorAuthPage';

function App() {
  return (
    <>
      <AuthProvider>
        <Routes>
          {/* Layout cho các trang Authentication */}
          <Route element={<AuthLayout />}>
            <Route path="/login" element={<LoginPage />} />
            <Route path="/register" element={<RegisterPage />} />
            <Route path="/forgot-password" element={<ForgotPasswordPage />} />
            <Route path="/reset-password/:token" element={<ResetPasswordPage />} />
            <Route path="/verify-otp" element={<OTPVerificationPage />} />
          </Route>

          {/* Layout chính với header và footer */}
          <Route element={<MainLayout />}>
            <Route path="/" element={<HomePage />} />
            <Route path="flashcards" element={<FlashcardPage />} />
            <Route path="topics" element={<TopicsPage />} />
            <Route path="quiz" element={<QuizPage />} />
            <Route path="settings/2fa" element={<TwoFactorAuthPage />} />
          </Route>

          <Route path="*" element={<NotFoundPage />} />
        </Routes>
      </AuthProvider>
      <Toaster position="top-right" />
    </>
  );
}

export default App;
