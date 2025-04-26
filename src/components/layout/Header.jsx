// src/components/layout/Header.jsx
import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../../hooks/useAuth';

const Header = () => {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const [isMenuOpen, setIsMenuOpen] = useState(false);

  const handleLogout = () => {
    logout();
    navigate('/login');
  };

  return (
    <header className="bg-white shadow">
      <div className="container mx-auto px-4 py-4">
        <div className="flex justify-between items-center">
          <div className="flex items-center">
            <Link to="/" className="text-2xl font-bold text-blue-600">
              Brainy
            </Link>
          </div>

          <div className="hidden md:flex space-x-6">
            <Link to="/dashboard" className="text-gray-700 hover:text-blue-600">
              Trang chủ
            </Link>
            <Link to="/categories" className="text-gray-700 hover:text-blue-600">
              Danh mục
            </Link>
            <Link to="/flashcards" className="text-gray-700 hover:text-blue-600">
              Flashcards
            </Link>
            <Link to="/topics" className="text-gray-700 hover:text-blue-600">
              Chủ đề
            </Link>
            <Link to="/quiz" className="text-gray-700 hover:text-blue-600">
              Kiểm tra
            </Link>
          </div>

          <div className="hidden md:flex items-center">
            {user ? (
              <div className="relative">
                <button onClick={() => setIsMenuOpen(!isMenuOpen)} className="flex items-center space-x-2">
                  <div className="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">{user.username?.charAt(0).toUpperCase() || 'U'}</div>
                  <span>{user.username}</span>
                </button>

                {isMenuOpen && (
                  <div className="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1">
                    <Link to="/profile" className="block px-4 py-2 text-gray-700 hover:bg-blue-50" onClick={() => setIsMenuOpen(false)}>
                      Hồ sơ
                    </Link>
                    <Link to="/settings" className="block px-4 py-2 text-gray-700 hover:bg-blue-50" onClick={() => setIsMenuOpen(false)}>
                      Cài đặt
                    </Link>
                    <button onClick={handleLogout} className="block w-full text-left px-4 py-2 text-gray-700 hover:bg-blue-50">
                      Đăng xuất
                    </button>
                  </div>
                )}
              </div>
            ) : (
              <div className="space-x-4">
                <Link to="/login" className="text-gray-700 hover:text-blue-600">
                  Đăng nhập
                </Link>
                <Link to="/register" className="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                  Đăng ký
                </Link>
              </div>
            )}
          </div>

          {/* Mobile menu button */}
          <div className="md:hidden">
            <button onClick={() => setIsMenuOpen(!isMenuOpen)} className="text-gray-700">
              <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                {isMenuOpen ? (
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                ) : (
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
                )}
              </svg>
            </button>
          </div>
        </div>

        {/* Mobile menu */}
        {isMenuOpen && (
          <div className="md:hidden mt-4 pt-4 border-t">
            <div className="flex flex-col space-y-3">
              <Link to="/dashboard" className="text-gray-700 hover:text-blue-600" onClick={() => setIsMenuOpen(false)}>
                Trang chủ
              </Link>
              <Link to="/categories" className="text-gray-700 hover:text-blue-600" onClick={() => setIsMenuOpen(false)}>
                Danh mục
              </Link>
              <Link to="/flashcards" className="text-gray-700 hover:text-blue-600" onClick={() => setIsMenuOpen(false)}>
                Flashcards
              </Link>
              <Link to="/topics" className="text-gray-700 hover:text-blue-600" onClick={() => setIsMenuOpen(false)}>
                Chủ đề
              </Link>
              <Link to="/quiz" className="text-gray-700 hover:text-blue-600" onClick={() => setIsMenuOpen(false)}>
                Kiểm tra
              </Link>

              {user ? (
                <>
                  <Link to="/profile" className="text-gray-700 hover:text-blue-600" onClick={() => setIsMenuOpen(false)}>
                    Hồ sơ
                  </Link>
                  <Link to="/settings" className="text-gray-700 hover:text-blue-600" onClick={() => setIsMenuOpen(false)}>
                    Cài đặt
                  </Link>
                  <button onClick={handleLogout} className="text-left text-gray-700 hover:text-blue-600">
                    Đăng xuất
                  </button>
                </>
              ) : (
                <>
                  <Link to="/login" className="text-gray-700 hover:text-blue-600" onClick={() => setIsMenuOpen(false)}>
                    Đăng nhập
                  </Link>
                  <Link to="/register" className="text-gray-700 hover:text-blue-600" onClick={() => setIsMenuOpen(false)}>
                    Đăng ký
                  </Link>
                </>
              )}
            </div>
          </div>
        )}
      </div>
    </header>
  );
};

export default Header;
