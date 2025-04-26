import { useQuery, useQueryClient } from '@tanstack/react-query';
import { topicService } from '../../services/topicService';
import { flashcardService } from '../../services/flashcardService';
import { useState } from 'react';
import { toast } from 'react-hot-toast';
import { useNavigate } from 'react-router-dom';

const TopicList = () => {
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const [selectedLevel, setSelectedLevel] = useState('all');

  const { data, isLoading, error } = useQuery({
    queryKey: ['topics'],
    queryFn: topicService.getTopics,
  });

  const topics = data?.data || [];

  // Filter topics by level
  const filteredTopics = selectedLevel === 'all' ? topics : topics.filter((topic) => topic.name.startsWith(selectedLevel));

  const handleCreateFlashcards = async (topicName) => {
    try {
      const userId = 'default-user-id'; // Replace with actual user ID from auth

      toast.promise(flashcardService.createFlashcardsForTopic(userId, topicName), {
        loading: 'Creating flashcards...',
        success: () => {
          // Navigate to flashcards page after successful creation
          setTimeout(() => navigate('/flashcards'), 1000);
          return 'Flashcards created successfully!';
        },
        error: 'Failed to create flashcards',
      });
    } catch (error) {
      console.error('Error creating flashcards:', error);
    }
  };

  if (isLoading) {
    return (
      <div className="w-full flex justify-center py-12">
        <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-primary-600"></div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="text-center py-12">
        <p className="text-red-500">Failed to load topics</p>
        <button className="mt-4 btn btn-primary" onClick={() => queryClient.invalidateQueries(['topics'])}>
          Try Again
        </button>
      </div>
    );
  }

  return (
    <div>
      <div className="mb-6">
        <h2 className="text-xl font-semibold mb-4">Filter by Level</h2>
        <div className="flex flex-wrap gap-2">
          <button className={`px-4 py-2 rounded-lg ${selectedLevel === 'all' ? 'bg-primary-600 text-white' : 'bg-gray-200'}`} onClick={() => setSelectedLevel('all')}>
            All Levels
          </button>
          {['A1', 'A2', 'B1', 'B2', 'C1', 'C2'].map((level) => (
            <button key={level} className={`px-4 py-2 rounded-lg ${selectedLevel === level ? 'bg-primary-600 text-white' : 'bg-gray-200'}`} onClick={() => setSelectedLevel(level)}>
              {level}
            </button>
          ))}
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {filteredTopics.map((topic) => (
          <div key={topic.name} className="card hover:shadow-lg transition-shadow">
            <h3 className="text-xl font-bold">{topic.name}</h3>
            <p className="text-gray-600 mt-2">{topic.word_count} words</p>
            <div className="mt-4">
              <button className="btn btn-primary" onClick={() => handleCreateFlashcards(topic.name)}>
                Create Flashcards
              </button>
            </div>
          </div>
        ))}

        {filteredTopics.length === 0 && <div className="col-span-full text-center py-12 text-gray-500">No topics found for this level</div>}
      </div>
    </div>
  );
};

export default TopicList;
