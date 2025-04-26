import React, { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { topicService } from '../services/topicService';
import { flashcardService } from '../services/flashcardService';
import { useNavigate } from 'react-router-dom';
import { toast } from 'react-hot-toast';
import Card from '../components/common/Card';
import Button from '../components/common/Button';
import Spinner from '../components/common/Spinner';
import EmptyState from '../components/common/EmptyState';

const TopicsPage = () => {
  const navigate = useNavigate();
  const [selectedLevel, setSelectedLevel] = useState('all');
  const [isCreatingFlashcards, setIsCreatingFlashcards] = useState(false);

  // Fetch all topics
  const { data, isLoading, error, refetch } = useQuery({
    queryKey: ['topics'],
    queryFn: topicService.getTopics,
  });

  const topics = data?.data || [];

  // Filter topics by level
  const filteredTopics = selectedLevel === 'all' ? topics : topics.filter((topic) => topic.name.startsWith(selectedLevel));

  // Group topics by main category (first part of name)
  const groupedTopics = filteredTopics.reduce((groups, topic) => {
    const parts = topic.name.split(' - ');
    const mainCategory = parts[0]; // A1, A2, B1, etc.

    if (!groups[mainCategory]) {
      groups[mainCategory] = [];
    }

    groups[mainCategory].push(topic);
    return groups;
  }, {});

  // Handle creating flashcards for a topic
  const handleCreateFlashcards = async (topicName) => {
    try {
      setIsCreatingFlashcards(true);
      const userId = 'default-user-id'; // Replace with actual user ID from auth

      await toast.promise(flashcardService.createFlashcardsForTopic(userId, topicName), {
        loading: 'Creating flashcards...',
        success: () => {
          setTimeout(() => navigate('/flashcards'), 1000);
          return 'Flashcards created successfully!';
        },
        error: 'Failed to create flashcards',
      });
    } catch (error) {
      console.error('Error creating flashcards:', error);
    } finally {
      setIsCreatingFlashcards(false);
    }
  };

  // Render level filters
  const renderLevelFilters = () => (
    <div className="mb-8">
      <h2 className="text-xl font-semibold mb-4">Filter by Level</h2>
      <div className="flex flex-wrap gap-2">
        <button className={`px-4 py-2 rounded-lg transition-colors ${selectedLevel === 'all' ? 'bg-primary-600 text-white' : 'bg-gray-200 hover:bg-gray-300'}`} onClick={() => setSelectedLevel('all')}>
          All Levels
        </button>
        {['A1', 'A2', 'B1', 'B2', 'C1', 'C2'].map((level) => (
          <button
            key={level}
            className={`px-4 py-2 rounded-lg transition-colors ${selectedLevel === level ? 'bg-primary-600 text-white' : 'bg-gray-200 hover:bg-gray-300'}`}
            onClick={() => setSelectedLevel(level)}
          >
            {level}
          </button>
        ))}
      </div>
    </div>
  );

  // Render main category section
  const renderCategorySection = (category, topicsList) => (
    <div key={category} className="mb-10">
      <h2 className="text-2xl font-bold mb-4 pb-2 border-b border-gray-200">{category} Topics</h2>
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {topicsList.map((topic) => (
          <TopicCard key={topic.name} topic={topic} onCreateFlashcards={handleCreateFlashcards} isLoading={isCreatingFlashcards} />
        ))}
      </div>
    </div>
  );

  // Loading state
  if (isLoading) {
    return (
      <div className="flex justify-center items-center py-16">
        <Spinner size="lg" />
      </div>
    );
  }

  // Error state
  if (error) {
    return <EmptyState title="Failed to load topics" description="There was an error loading the vocabulary topics. Please try again." actionText="Retry" onAction={refetch} />;
  }

  return (
    <div>
      <section className="mb-8">
        <h1 className="text-3xl font-bold mb-2">Vocabulary Topics</h1>
        <p className="text-gray-600">Browse and study vocabulary by topic and difficulty level</p>
      </section>

      {renderLevelFilters()}

      {Object.keys(groupedTopics).length > 0 ? (
        Object.entries(groupedTopics)
          .sort(([a], [b]) => a.localeCompare(b)) // Sort categories alphabetically
          .map(([category, topicsList]) => renderCategorySection(category, topicsList))
      ) : (
        <EmptyState
          title="No topics found"
          description={
            selectedLevel === 'all' ? 'No vocabulary topics are available. Please add some topics first.' : `No vocabulary topics found for level ${selectedLevel}. Please select another level.`
          }
        />
      )}
    </div>
  );
};

// Topic Card Component
const TopicCard = ({ topic, onCreateFlashcards, isLoading }) => {
  // Extract detailed topic name (without level prefix)
  const topicName = topic.name.split(' - ')[1] || topic.name;

  // Handle click on create flashcards button
  const handleClick = (e) => {
    e.preventDefault();
    onCreateFlashcards(topic.name);
  };

  return (
    <Card className="h-full flex flex-col transition-transform hover:-translate-y-1 hover:shadow-lg">
      <div className="flex-grow">
        <h3 className="text-xl font-bold text-gray-800 mb-2">{topicName}</h3>
        <div className="flex items-center gap-2 mb-4">
          <span className="px-2 py-1 bg-primary-100 text-primary-800 rounded-md text-sm font-medium">{topic.name.split(' - ')[0]}</span>
          <span className="text-gray-600 text-sm">{topic.word_count} words</span>
        </div>
      </div>

      <div className="mt-4 flex flex-col gap-2">
        <Button onClick={handleClick} disabled={isLoading} className="w-full">
          Create Flashcards
        </Button>
        <Button variant="outline" to={`/quiz?topic=${encodeURIComponent(topic.name)}`} className="w-full">
          Take Quiz
        </Button>
      </div>
    </Card>
  );
};

export default TopicsPage;
