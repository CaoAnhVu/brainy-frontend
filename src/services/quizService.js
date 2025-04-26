import api from './api';

export const quizService = {
  // Lấy quiz dựa trên topic
  getQuizByTopic: async (topicName, limit = 10) => {
    const response = await api.get('/quiz.php', {
      params: {
        topic: topicName,
        limit,
      },
    });
    return response.data;
  },

  // Lưu kết quả quiz
  saveQuizResult: async (userId, topicName, score, totalQuestions, timeSpent) => {
    const response = await api.post('/quiz_results.php', {
      user_id: userId,
      topic: topicName,
      score,
      total_questions: totalQuestions,
      time_spent: timeSpent,
    });
    return response.data;
  },

  // Lấy lịch sử kết quả quiz
  getQuizHistory: async (userId) => {
    const response = await api.get('/quiz_results.php', {
      params: { user_id: userId },
    });
    return response.data;
  },
};
