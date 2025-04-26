import api from './api';

export const categoryService = {
  getCategories: async () => {
    const response = await api.get('/categories.php');
    return response.data;
  },

  getCategoryWithLessons: async (categoryId) => {
    const response = await api.get(`/categories.php?id=${categoryId}&lessons=true`);
    return response.data;
  },
};
