import React, { useState } from 'react';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { topicService } from '../../services/topicService';
import Card from '../common/Card';
import Button from '../common/Button';
import Spinner from '../common/Spinner';
import EmptyState from '../common/EmptyState';

const QuizTopicSelector = ({ onSelectTopic }) => {
  const [selectedLevel, setSelectedLevel] = useState('all');
  const queryClient = useQueryClient();

  const { data, isLoading, error } = useQuery({
    queryKey: ['topics'],
    queryFn: topicService.getTopics,
  });

  if (isLoading) {
    return <Spinner className="py-12" size="lg" />;
  }

  if (error) {
    return (
      <EmptyState title="Failed to load topics" description="There was an error loading the topics. Please try again." actionText="Retry" onAction={() => queryClient.invalidateQueries(['topics'])} />
    );
  }

  const topics = data?.data || [];

  // Filter topics by level
  const filteredTopics = selectedLevel === 'all' ? topics : topics.filter((topic) => topic.name.startsWith(selectedLevel));

  return (
    <div>
      <div className="mb-6">
        <h2 className="text-xl font-semibold mb-4">Select a Topic for Quiz</h2>
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
          <Card key={topic.name} className="hover:shadow-lg transition-shadow">
            <h3 className="text-xl font-bold">{topic.name}</h3>
            <p className="text-gray-600 mt-2">{topic.word_count} words</p>
            <div className="mt-4">
              <Button onClick={() => onSelectTopic(topic.name)}>Start Quiz</Button>
            </div>
          </Card>
        ))}

        {filteredTopics.length === 0 && (
          <div className="col-span-full">
            <EmptyState title="No topics found" description="No topics found for this level. Please select another level." />
          </div>
        )}
      </div>
    </div>
  );
};

export default QuizTopicSelector;
