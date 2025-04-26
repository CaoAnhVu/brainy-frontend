import api from './api';

export const flashcardService = {
  // Lấy flashcards đến hạn ôn tập
  getDueFlashcards: async (userId) => {
    const response = await api.get('/flashcards.php', {
      params: { user_id: userId },
    });
    return response.data;
  },

  // Cập nhật flashcard sau khi đánh giá
  updateFlashcard: async (flashcardId, quality) => {
    const response = await api.put('/flashcards.php', {
      flashcard_id: flashcardId,
      quality,
    });
    return response.data;
  },

  // Tạo flashcards cho một chủ đề
  createFlashcardsForTopic: async (userId, topic) => {
    const response = await api.post('/flashcards.php', {
      user_id: userId,
      topic,
    });
    return response.data;
  },

  // Lấy thống kê flashcard
  getFlashcardStats: async (userId) => {
    const response = await api.get('/flashcard_stats.php', {
      params: { user_id: userId },
    });
    return response.data;
  },
};
