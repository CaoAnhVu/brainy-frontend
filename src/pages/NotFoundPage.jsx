import React from 'react';
import { Link } from 'react-router-dom';

const NotFoundPage = () => {
  return (
    <section className="min-h-screen bg-white flex items-center justify-center py-8">
      <div className="container mx-auto px-4">
        <div className="flex flex-col items-center max-w-4xl mx-auto">
          <div className="w-full text-center mb-0">
            <h1 className="text-[100px] md:text-[120px] font-extrabold text-blue-600 leading-none mb-0">404</h1>
            <div
              className="bg-[url('https://cdn.dribbble.com/users/285475/screenshots/2083086/dribbble_1.gif')] 
                h-96 md:h-[400px] bg-center bg-no-repeat bg-contain mx-auto w-full max-w-lg"
            >
              {/* Hình ảnh từ dribbble được nhúng làm background */}
            </div>
          </div>

          <div className="text-center w-full max-w-lg mx-auto">
            <h3 className="text-3xl md:text-4xl font-bold text-gray-800 mb-4">Có vẻ như bạn đã đi lạc</h3>

            <p className="text-xl text-gray-600 mb-8 px-4">Trang bạn đang tìm kiếm không tồn tại trong hệ thống Brainy của chúng tôi!</p>

            <div className="flex flex-col sm:flex-row justify-center gap-4">
              <Link to="/" className="px-8 py-4 bg-blue-600 text-white text-lg font-medium rounded-lg hover:bg-blue-700 transition-colors shadow-md">
                Về Trang chủ
              </Link>

              <Link to="/flashcards" className="px-8 py-4 bg-gray-100 text-gray-800 text-lg font-medium rounded-lg hover:bg-gray-200 transition-colors shadow-md">
                Đến Flashcards
              </Link>
            </div>
          </div>

          <div className="mt-16 text-center">
            <p className="text-gray-500 mb-3">Hoặc bạn có thể khám phá:</p>
            <div className="flex flex-wrap justify-center gap-6">
              <Link to="/topics" className="text-blue-600 hover:underline hover:text-blue-800">
                Chủ đề
              </Link>
              <Link to="/quiz" className="text-blue-600 hover:underline hover:text-blue-800">
                Kiểm tra
              </Link>
              <Link to="/categories" className="text-blue-600 hover:underline hover:text-blue-800">
                Danh mục
              </Link>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
};

export default NotFoundPage;
