// pages/FlashcardPage.jsx
import { useState, useEffect } from 'react';
// eslint-disable-next-line
import { motion } from 'framer-motion';
import api from '../services/api';

const FlashcardPage = () => {
  const [flashcards, setFlashcards] = useState([]);
  const [currentIndex, setCurrentIndex] = useState(0);
  const [isFlipped, setIsFlipped] = useState(false);
  const [isLoading, setIsLoading] = useState(true);
  const [stats, setStats] = useState(null);

  useEffect(() => {
    const loadFlashcards = async () => {
      try {
        const response = await api.get('/flashcards.php');
        setFlashcards(response.data.data);

        const statsResponse = await api.get('/flashcard_stats.php');
        setStats(statsResponse.data.data);

        setIsLoading(false);
      } catch (error) {
        console.error('Error loading flashcards:', error);
        setIsLoading(false);
      }
    };

    loadFlashcards();
  }, []);

  const handleFlip = () => {
    setIsFlipped(!isFlipped);
  };

  const handleRating = async (quality) => {
    try {
      if (flashcards.length === 0) return;

      const flashcard = flashcards[currentIndex];

      await api.put('/flashcards.php', {
        flashcard_id: flashcard.id,
        quality: quality,
      });

      // Move to next flashcard
      if (currentIndex < flashcards.length - 1) {
        setCurrentIndex(currentIndex + 1);
      } else {
        // Reload cards if we're at the end
        const response = await api.get('/flashcards.php');
        setFlashcards(response.data.data);
        setCurrentIndex(0);
      }

      setIsFlipped(false);
    } catch (error) {
      console.error('Error updating flashcard:', error);
    }
  };

  if (isLoading) {
    return (
      <div className="flex justify-center items-center h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
      </div>
    );
  }

  if (flashcards.length === 0) {
    return (
      <div className="container mx-auto px-4 py-8">
        <h1 className="text-2xl font-bold mb-4">Không có flashcard</h1>
        <p className="text-gray-600 mb-4">Bạn đã hoàn thành tất cả flashcard cho hôm nay!</p>
        <a href="/topics" className="bg-blue-600 text-white px-4 py-2 rounded-lg">
          Khám phá thêm chủ đề
        </a>
      </div>
    );
  }

  const flashcard = flashcards[currentIndex];

  return (
    <div className="container mx-auto px-4 py-8">
      {/* Hiển thị thống kê */}
      {stats && (
        <div className="grid grid-cols-5 gap-4 mb-8">
          <div className="bg-white rounded-lg shadow p-4 text-center">
            <div className="text-2xl font-bold">{stats.total}</div>
            <div className="text-sm text-gray-600">Tổng số</div>
          </div>
          <div className="bg-blue-50 rounded-lg shadow p-4 text-center">
            <div className="text-2xl font-bold text-blue-600">{stats.new}</div>
            <div className="text-sm text-gray-600">Mới</div>
          </div>
          <div className="bg-yellow-50 rounded-lg shadow p-4 text-center">
            <div className="text-2xl font-bold text-yellow-600">{stats.learning}</div>
            <div className="text-sm text-gray-600">Đang học</div>
          </div>
          <div className="bg-green-50 rounded-lg shadow p-4 text-center">
            <div className="text-2xl font-bold text-green-600">{stats.review}</div>
            <div className="text-sm text-gray-600">Ôn tập</div>
          </div>
          <div className="bg-purple-50 rounded-lg shadow p-4 text-center">
            <div className="text-2xl font-bold text-purple-600">{stats.known}</div>
            <div className="text-sm text-gray-600">Đã thuộc</div>
          </div>
        </div>
      )}

      {/* Flashcard */}
      <div className="flex flex-col items-center">
        <div className="mb-4 text-center">
          <h1 className="text-2xl font-bold">Flashcard</h1>
          <p className="text-gray-600">
            {currentIndex + 1} / {flashcards.length}
          </p>
        </div>

        <div className="w-full max-w-md h-80">
          <motion.div
            className="w-full h-full rounded-xl shadow-lg cursor-pointer"
            onClick={handleFlip}
            initial={false}
            animate={{ rotateY: isFlipped ? 180 : 0 }}
            transition={{ duration: 0.5 }}
            style={{ perspective: '1000px', transformStyle: 'preserve-3d' }}
          >
            {/* Front of card */}
            <div className={`absolute w-full h-full bg-white rounded-xl p-8 flex flex-col justify-between backface-hidden ${isFlipped ? 'hidden' : ''}`} style={{ backfaceVisibility: 'hidden' }}>
              <div className="text-center">
                <h2 className="text-3xl font-bold text-gray-800">{flashcard.word}</h2>
                {flashcard.phonetic && <p className="text-gray-500 mt-2">{flashcard.phonetic}</p>}
                {flashcard.pos && <p className="text-blue-600 mt-1 font-medium">{flashcard.pos}</p>}
              </div>
              <div className="text-center text-gray-500">Nhấp để lật</div>
            </div>

            {/* Back of card */}
            <div
              className={`absolute w-full h-full bg-white rounded-xl p-8 flex flex-col justify-between backface-hidden ${!isFlipped ? 'hidden' : ''}`}
              style={{ backfaceVisibility: 'hidden', transform: 'rotateY(180deg)' }}
            >
              <div>
                <h3 className="text-xl font-medium text-gray-700 mb-4">Định nghĩa:</h3>
                <p className="text-gray-800">{flashcard.definition}</p>

                {flashcard.example && (
                  <div className="mt-4">
                    <h3 className="text-xl font-medium text-gray-700 mb-2">Ví dụ:</h3>
                    <p className="text-gray-600 italic">{flashcard.example}</p>
                  </div>
                )}
              </div>

              <div className="mt-4">
                <h3 className="text-sm font-medium text-gray-700 mb-2">Bạn thuộc từ này như thế nào?</h3>
                <div className="flex justify-between">
                  <button
                    onClick={(e) => {
                      e.stopPropagation();
                      handleRating(1);
                    }}
                    className="px-3 py-1 bg-red-500 text-white rounded-md text-sm"
                  >
                    Không nhớ
                  </button>
                  <button
                    onClick={(e) => {
                      e.stopPropagation();
                      handleRating(3);
                    }}
                    className="px-3 py-1 bg-yellow-500 text-white rounded-md text-sm"
                  >
                    Khó nhớ
                  </button>
                  <button
                    onClick={(e) => {
                      e.stopPropagation();
                      handleRating(5);
                    }}
                    className="px-3 py-1 bg-green-500 text-white rounded-md text-sm"
                  >
                    Dễ nhớ
                  </button>
                </div>
              </div>
            </div>
          </motion.div>
        </div>
      </div>
    </div>
  );
};

export default FlashcardPage;
