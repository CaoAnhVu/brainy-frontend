import api from './api';

export const topicService = {
  // Lấy danh sách topics
  getTopics: async () => {
    const response = await api.get('/topics.php');
    return response.data;
  },
};
