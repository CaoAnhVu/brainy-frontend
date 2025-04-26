// src/components/layout/Footer.jsx
import { Link } from 'react-router-dom';

const Footer = () => {
  return (
    <footer className="bg-gray-800 text-white pt-12 pb-8">
      <div className="container mx-auto px-4">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-8">
          <div>
            <h3 className="text-xl font-bold mb-4">Brainy</h3>
            <p className="text-gray-400">Nền tảng học từ vựng hiệu quả với phương pháp spaced repetition.</p>
          </div>

          <div>
            <h3 className="text-lg font-semibold mb-4">Học tập</h3>
            <ul className="space-y-2">
              <li>
                <Link to="/flashcards" className="text-gray-400 hover:text-white">
                  Flashcards
                </Link>
              </li>
              <li>
                <Link to="/topics" className="text-gray-400 hover:text-white">
                  Chủ đề
                </Link>
              </li>
              <li>
                <Link to="/quiz" className="text-gray-400 hover:text-white">
                  Kiểm tra
                </Link>
              </li>
              <li>
                <Link to="/statistics" className="text-gray-400 hover:text-white">
                  Thống kê
                </Link>
              </li>
            </ul>
          </div>

          <div>
            <h3 className="text-lg font-semibold mb-4">Tài khoản</h3>
            <ul className="space-y-2">
              <li>
                <Link to="/login" className="text-gray-400 hover:text-white">
                  Đăng nhập
                </Link>
              </li>
              <li>
                <Link to="/register" className="text-gray-400 hover:text-white">
                  Đăng ký
                </Link>
              </li>
              <li>
                <Link to="/profile" className="text-gray-400 hover:text-white">
                  Hồ sơ
                </Link>
              </li>
              <li>
                <Link to="/settings" className="text-gray-400 hover:text-white">
                  Cài đặt
                </Link>
              </li>
            </ul>
          </div>

          <div>
            <h3 className="text-lg font-semibold mb-4">Liên hệ</h3>
            <p className="text-gray-400 mb-2">Email: contact@brainylanguage.com</p>
            <p className="text-gray-400 mb-4">Điện thoại: +84 123 456 789</p>
            <div className="flex space-x-4">
              <a href="#" className="text-gray-400 hover:text-white">
                <svg className="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 5.523 4.477 10 10 10s10-4.477 10-10zm-2 0a8 8 0 11-16 0 8 8 0 0116 0zm-9 4h2v-4h4v-2h-4V6h-2v4H9v2h4v4z" />
                </svg>
              </a>
              <a href="#" className="text-gray-400 hover:text-white">
                <svg className="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M22 12c0-5.52-4.48-10-10-10S2 6.48 2 12s4.48 10 10 10 10-4.48 10-10zM4 12c0-4.42 3.58-8 8-8s8 3.58 8 8-3.58 8-8 8-8-3.58-8-8zm11.71-3.71a.996.996 0 00-1.41 0L10 12.59 8.71 11.3a.996.996 0 10-1.41 1.41l2 2c.39.39 1.02.39 1.41 0l4-4a.996.996 0 000-1.41z" />
                </svg>
              </a>
              <a href="#" className="text-gray-400 hover:text-white">
                <svg className="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M22 12c0-5.52-4.48-10-10-10S2 6.48 2 12s4.48 10 10 10 10-4.48 10-10zM4 12c0-4.42 3.58-8 8-8s8 3.58 8 8-3.58 8-8 8-8-3.58-8-8zm3-1h10v2H7v-2z" />
                </svg>
              </a>
            </div>
          </div>
        </div>

        <div className="mt-12 pt-8 border-t border-gray-700 text-center text-gray-400">
          <p>&copy; {new Date().getFullYear()} Brainy Language. Bản quyền thuộc về Brainy.</p>
        </div>
      </div>
    </footer>
  );
};

export default Footer;
